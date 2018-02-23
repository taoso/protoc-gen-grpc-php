<?php
namespace Lv\Grpc;

class SimpleContext implements Context
{
    use BinNameTrait;

    private $status;
    private $message;
    private $metadata = [];
    private $metadata_lower = [];

    public function __construct(array $raw_metadata = [])
    {
        if ($raw_metadata) {
            $this->metadata = $raw_metadata;

            foreach ($raw_metadata as $name => $value) {
                $this->metadata_lower[strtolower($name)] = $value;
            }
        }
    }

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
        $name = strtolower($name);
        $value = $this->metadata_lower[$name] ?? null;
        if ($value && $this->isBinName($name)) {
            $value = base64_decode($value);
        }

        return $value;
    }

    public function setMetadata(string $name, string $value)
    {
        if ($this->isBinName($name)) {
            $value = base64_encode($value);
        }

        $this->metadata[$name] = $value;
        $this->metadata_lower[strtolower($name)] = $value;
    }

    public function getAllMetadata() : array
    {
        return $this->metadata;
    }
}
