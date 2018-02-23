<?php
namespace Lv\Grpc;

class SimpleContext implements Context
{
    private $status;
    private $message;
    private $metadata = [];
    private $metadata_lower = [];

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
        $name = strtolower(trim($name));
        return $this->metadata_lower[$name] ?? null;
    }

    public function setMetadata(string $name, string $value)
    {
        $name = trim($name);
        $this->metadata[$name] = $value;
        $this->metadata_lower[strtolower($name)] = $value;
    }

    public function getAllMetadata() : array
    {
        return $this->metadata;
    }
}
