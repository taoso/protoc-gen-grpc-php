<?php
namespace Lv\Grpc;

use Google\Protobuf\Internal\Message;

trait CurlClientTrait
{
    use BinNameTrait;

    private $authority;
    private $curl;
    private $reply_metadata = [];

    public function __construct(string $authority = '')
    {
        $this->authority = $authority;
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1 );

        curl_setopt($this->curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, 10);
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, 30);

        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, function ($curl, $header_line) {
            if (strpos($header_line, ':')) {
                list($name, $value) = explode(':', $header_line);
                $this->reply_metadata[trim($name)] = trim($value);
            }

            return strlen($header_line);
        });
    }

    public function useHttp1()
    {
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }

    public function useHttp2()
    {
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
    }

    public function setTimeout(int $timeout_ms) : self
    {
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout_ms);
        return $this;
    }

    public function setConnectTimeout(int $timeout_ms) : self
    {
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $timeout_ms);
        return $this;
    }

    private function send(string $path, Context $context, Message $request, Message $reply)
    {
        $this->reply_metadata = [];

        $url = $this->authority.$path;
        curl_setopt($this->curl, CURLOPT_URL, $url);

        $data = $request->serializeToString();
        $data = pack('CN', 0, strlen($data)).$data;
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        $header_lines = [];
        foreach ($context->getAndClearAllMetadata() as $name => $value) {
            $header_lines[] = "$name: $value";
        }
        $header_lines[] = 'Content-Type: application/grpc+proto';
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header_lines);

        $data = curl_exec($this->curl);

        foreach ($this->reply_metadata as $name => $value) {
            $context->setMetadata($name, $value);
        }

        if (isset($this->reply_metadata['grpc-status'])) {
            if($this->reply_metadata['grpc-status'] == Status::OK) {
                $reply->mergeFromString(substr($data, 5));
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
