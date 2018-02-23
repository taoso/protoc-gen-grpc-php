<?php
namespace Lv\Grpc;

trait CurlStubTrait
{
    use BinNameTrait;

    private $hosts;
    private $options = [];
    private $curl;
    private $reply_metadata = [];
    private $index = -1;
    private $index_max;
    private $host;

    /**
     * create a grpc service client
     *
     * @param array $hosts server host list, e.g. [127.0.0.1:1024, 127.0.0.1:1025]
     * @param array $options client options, include:
     *      - use_http1, indicate whether use http/1.1, default false
     *      - connect_timeout_ms, connect timout, default 10ms
     *      - timeout_ms, timout, default 30ms
     *      - retry_num, retry count, default no retry
     */
    public function __construct(array $hosts = [], array $options = [])
    {
        $this->setHosts($hosts);
        $this->setOptions($options);
    }

    /**
     * should only be called before and send
     */
    public function setHosts(array $hosts)
    {
        $this->hosts = $hosts;
        $this->index_max = count($hosts);
    }

    /**
     * should only be called before and send
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    private function getCurl()
    {
        $this->index++;
        if ($this->index >= $this->index_max) {
            $this->index = 0;
        }

        $this->host = $this->hosts[$this->index];

        if (isset($this->curls[$this->index])) {
            return $this->curls[$this->index];
        }

        $options = $this->options;

        $curl = curl_init();

        $use_http1 = empty($options['use_http1']) ? false : true;
        if (!$use_http1) {
            /*
             * Option CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE was added in libcurl v7.49.0
             * If curl extension was built with libcurl <7.49.0 and curl installed in your system is >=7.49.0
             * you need to manualy define this constant
             */
            defined('CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE') or define('CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE', 5);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
        }

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $timeout_ms = (int)($options['timeout_ms'] ?? 30);
        $connect_timeout_ms = (int)($options['connect_timeout_ms'] ?? 10);
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeout_ms);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, $connect_timeout_ms);

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header_line) {
            if (strpos($header_line, ':')) {
                list($name, $value) = explode(':', $header_line);
                $name = trim($name);
                $value = trim($value);

                if ($this->isBinName($name)) {
                    $value = base64_decode($value);
                }

                $this->reply_metadata[$name] = $value;
            }

            return strlen($header_line);
        });

        $this->curls[$this->index] = $curl;

        return $curl;
    }

    private function send(string $path, Message $request, Message $reply)
    {
        $retry_num = (int) ($this->options['retry_num'] ?? 0);

        do {
            $this->curl = $this->getCurl();

            $this->doSend($path, $request, $reply);

            // only retry for connect error
            if ($this->getLastErrno() !== CURLE_COULDNT_CONNECT) {
                return;
            }
        } while ($retry_num-- > 0);
    }

    private function doSend(string $path, Message $request, Message $reply)
    {
        $this->reply_metadata = [];

        $url = $this->host.$path;
        curl_setopt($this->curl, CURLOPT_URL, $url);
        $request_context = $request->context();

        $content_type = $request_context->getMetadata('content-type');

        if (!$content_type) {
            $content_type = 'application/grpc+proto';
        }

        if ($content_type === 'application/grpc+proto') {
            $data = $request->serializeToString();
        } else {
            $data = $request->serializeToJsonString();
        }

        if ($content_type !== 'application/json') {
            $data = pack('CN', 0, strlen($data)).$data;
        }

        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        $header_lines = [];

        if (!$request_context->getMetadata('content-type')) {
            $header_lines[] = 'Content-Type: application/grpc+proto';
        }

        foreach ($request_context->getAllMetadata() as $name => $value) {
            if ($this->isBinName($name)) {
                $value = base64_encode($value);
            }

            $header_lines[] = "$name: $value";
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header_lines);

        $data = curl_exec($this->curl);

        $reply_context = $reply->context();
        foreach ($this->reply_metadata as $name => $value) {
            $reply_context->setMetadata($name, $value);
        }

        $grpc_status = (int) $reply_context->getMetadata('grpc-status');
        if ($grpc_status !== null) {
            if ($grpc_status === Status::OK) {
                switch ($reply_context->getMetadata('content-type')) {
                case 'application/grpc+json':
                    // TODO support compression
                    $reply->mergeFromJsonString(substr($data, 5));
                    break;
                case 'application/grpc+proto':
                    // TODO support compression
                    $reply->mergeFromString(substr($data, 5));
                    break;
                case 'application/json':
                    $reply->mergeFromJsonString($data);
                    break;
                }
            }

            $status = (int) $reply_context->getMetadata('grpc-status');
            $message = $reply_context->getMetadata('grpc-message');
            if (!$message) {
                $message = Status::getStatusMessage($status);
            }

            $reply_context->setStatus($status);
            $reply_context->setMessage($message);
        } else {
            $reply_context->setStatus(Status::INTERNAL);
        }
    }

    /**
     * @throws \RuntimeException
     */
    public function getMethods()
    {
        throw new \RuntimeException(__METHOD__ . ' can only called in server');
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
