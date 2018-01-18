<?php
namespace Lv\Grpc;

use Google\Protobuf\Internal\Message;

trait CurlClientTrait
{
    use BinNameTrait;

    private $host;
    private $curl;
    private $reply_metadata = [];

    /**
     * create a grpc service client
     *
     * @param $host server host, e.g. 127.0.0.1:1024
     * @param $options client options, include:
     *      - use_http1, indicate whether use http/1.1, default false
     *      - connect_timeout_ms, connect timout, default 10ms
     *      - timeout_ms, timout, default 30ms
     */
    public function __construct(string $host= '', array $options = [])
    {
        $this->host = $host;
        $this->curl = curl_init();

        $use_http1 = empty($options['use_http1']) ? false : true;
        if (!$use_http1) {
            curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
        }

        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1 );

        $timeout_ms = (int)($options['timeout_ms'] ?? 30);
        $connect_timeout_ms = (int)($options['connect_timeout_ms'] ?? 10);
        curl_setopt($this->curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout_ms);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout_ms);

        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, function ($curl, $header_line) {
            if (strpos($header_line, ':')) {
                list($name, $value) = explode(':', $header_line);
                $this->reply_metadata[trim($name)] = trim($value);
            }

            return strlen($header_line);
        });
    }

    private function send(string $path, Context $context, Message $request, Message $reply)
    {
        $this->reply_metadata = [];

        $url = $this->host.$path;
        curl_setopt($this->curl, CURLOPT_URL, $url);

        if ($context->getMetadata('content-type') === 'application/grpc+json') {
            $data = $request->serializeToJsonString();
        } else {
            $data = $request->serializeToString();
        }
        $data = pack('CN', 0, strlen($data)).$data;
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        $header_lines = [];

        if (!$context->getMetadata('content-type')) {
            $header_lines[] = 'Content-Type: application/grpc+proto';
        }

        foreach ($context->getAndClearAllMetadata() as $name => $value) {
            $header_lines[] = "$name: $value";
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header_lines);

        $data = curl_exec($this->curl);

        foreach ($this->reply_metadata as $name => $value) {
            $context->setMetadata($name, $value);
        }

        if (isset($this->reply_metadata['grpc-status'])) {
            if($this->reply_metadata['grpc-status'] == Status::OK) {
                if ($context->getMetadata('content-type') === 'application/grpc+json') {
                    $reply->mergeFromJsonString(substr($data, 5));
                } else {
                    $reply->mergeFromString(substr($data, 5));
                }
            }

            $status = (int) $this->reply_metadata['grpc-status'];
            $message = $this->reply_metadata['grpc-message']
                ?? Status::STATUS_TO_MSG[$status] ?? Status::UNKNOWN;

            $context->setStatus($status);
            $context->setMessage($message);
        } else {
            $context->setStatus(Status::INTERNAL);
        }
    }

    public function getMethods()
    {
        return [];
    }

    public function newContext() : Context
    {
        return new CurlContext;
    }

    public function getLastErrno()
    {
        return curl_errno($this->curl);
    }

    public function getLastError()
    {
        return curl_error($this->curl);
    }
}
