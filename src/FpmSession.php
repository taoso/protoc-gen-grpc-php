<?php
namespace Lv\Grpc;

class FpmSession implements Session
{
    use BinNameTrait;

    private $post;
    private $server;

    private $grpc_status = Status::OK;

    private $grpc_message = '';

    private $is_http2 = false;

    public function __construct(array $post, array $server)
    {
        $this->post = $post;
        $this->server = $server;
    }

    public function getUri()
    {
        return $this->server['REQUEST_URI'];
    }

    public function getBody()
    {
        return file_get_contents('php://input');
    }

    public function getMetadata(string $name)
    {
        $key = 'HTTP_'.strtoupper(str_replace('-', '_', $name));
        $value = $this->server[$key] ?? null;
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

        header("$name: $value");
    }

    public function setStatus(int $status)
    {
        $this->grpc_status = $status;
    }

    public function setMessage(string $message)
    {
        $this->grpc_message = $message;
    }

    public function end(int $status = Status::OK, string $body = null)
    {
        header("grpc-status: $status");
        echo $body;
    }

    public function getMessage() : string
    {
        return $this->grpc_message;
    }

    public function getStatus() : int
    {
        return $this->grpc_status;
    }

    public function getAllMetadata() : array
    {
        $headers = [];

        foreach ($this->server as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $name = substr_replace('_', '-', substr($name, 5));

                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
