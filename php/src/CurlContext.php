<?php
namespace Lv\Grpc;

class CurlContext implements Context
{
    private $status;
    private $message;
    private $metadata = [];

    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    public function getStatus() : int
    {
        return $this->status;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getMessage() : string
    {
        return $this->message;
    }

    public function getMetadata(string $name)
    {
        return $this->metadata[$name] ?? null;
    }

    public function setMetadata(string $name, string $value)
    {
        $this->metadata[$name] = $value;
    }

    public function getAndClearAllMetadata() : array
    {
        $metadata = $this->metadata;
        $this->metadata = [];

        return $metadata;
    }
}
