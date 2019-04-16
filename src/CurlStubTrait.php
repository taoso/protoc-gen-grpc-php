<?php
namespace Lv\Grpc;

trait CurlStubTrait
{
    private $hosts;
    private $options = [];
    private $curl;
    private $reply_metadata = [];
    private $index = -1;
    private $index_max;
    private $host;

    private $last_errno;
    private $last_error;

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

                $this->reply_metadata[$name] = $value;
            }

            return strlen($header_line);
        });

        return $curl;
    }

    private function send(string $path, Message $request, Message $reply)
    {
        $retry_num = (int) ($this->options['retry_num'] ?? 0);

        do {
            $this->doSend($path, $request, $reply);

            // only retry for connect error
            if ($this->getLastErrno() !== CURLE_COULDNT_CONNECT) {
                return;
            }
        } while ($retry_num-- > 0);
    }

    private function doSend(string $path, Message $request, Message $reply)
    {
        $this->last_errno = 0;
        $this->last_error = '';

        $curl = $this->getCurl();
        $url = $this->host.$path;
        curl_setopt($curl, CURLOPT_URL, $url);

        $request_context = $request->context();

        $content_type = $request_context->getMetadata('content-type');

        $header_lines = [];

        if (!$content_type) {
            $content_type = 'application/grpc+proto';
            $header_lines[] = "Content-Type: $content_type";
        }

        if ($content_type === 'application/grpc+proto') {
            $data = $request->serializeToString();
        } else {
            $data = $request->serializeToJsonString();
        }

        if ($content_type !== 'application/json') {
            $data = pack('CN', 0, strlen($data)).$data;
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        foreach ($request_context->getAllMetadata() as $name => $value) {
            $header_lines[] = "$name: $value";
        }

        $header_lines[] = "te: trailers";

        curl_setopt($curl, CURLOPT_HTTPHEADER, $header_lines);

        $data = curl_exec($curl);

        if ($data === false) {
            $this->last_errno = curl_errno($curl);
            $this->last_error = curl_error($curl);
        }

        curl_close($curl);

        $reply_context = new SimpleContext($this->reply_metadata);
        $reply->context($reply_context);

        if (isset($this->reply_metadata['grpc-status'])) {
            $status = (int) $reply_context->getMetadata('grpc-status');
            if ($status === Status::OK) {
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
                default:
                    $status = Status::UNKNOWN;
                }
            }

            $message = $reply_context->getMetadata('grpc-message');
            if (!$message) {
                $message = Status::getStatusMessage($status);
            }

            $reply_context->setStatus($status);
            $reply_context->setMessage($message);
        } else {
            $reply_context->setStatus(Status::UNKNOWN);
            $reply_context->setMessage(Status::getStatusMessage(Status::UNKNOWN));
        }

        $this->reply_metadata = [];
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
        return $this->last_errno;
    }

    public function getLastError()
    {
        return $this->last_error;
    }
}
