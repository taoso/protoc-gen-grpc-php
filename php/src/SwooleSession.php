<?php
namespace Lv\Grpc;

use Swoole\Http\Request;
use Swoole\Http\Response;

class SwooleSession implements Session
{
    use BinNameTrait;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    private $is_http2 = false;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;

        $http_version = $this->request->server['server_protocol'];
        $this->is_http2 = substr($http_version, 0, 6) === 'HTTP/2';
    }

    public function getPath()
    {
        return $this->request->server['request_uri'];
    }

    public function getBody()
    {
        return $this->request->rawcontent();
    }

    public function getMetadata(string $name)
    {
        $value = $this->request->header[$name] ?? null;
        if ($this->isBinName($name) && $value) {
            $value = base64_decode($value);
        }

        return $value;
    }

    public function setMetadata(string $name, string $value)
    {
        if ($this->isBinName($name)) {
            $value = base64_encode($value);
        }

        return $this->response->header($name, $value, 0);
    }

    public function setStatus(int $status)
    {
        $this->setMetadata('grpc-status', $status);
    }

    public function setMessage(string $message)
    {
        $this->setMetadata('grpc-message', $message);
    }

    public function end($status = null, string $body = null)
    {
        if ($this->is_http2) {
            $this->response->trailer('grpc-status', $status, 0);
        } else {
            $this->response->header('grpc-status', $status, 0);
        }

        return $this->response->end($body);
    }

    public function getMessage() : string
    {
        throw new \RuntimeException('unsupport '.__METHOD__);
    }

    public function getStatus() : int
    {
        throw new \RuntimeException('unsupport '.__METHOD__);
    }

    public function getAndClearAllMetadata() : array
    {
        throw new \RuntimeException('unsupport '.__METHOD__);
    }
}
