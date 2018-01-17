<?php
namespace Lv\Grpc;

class DefaultContext
{
    private $status;
    private $metadata = [];

    public function setStatus(int $status)
    {
        $this->status = $status;
    }

    public function setMetadata($name)
    {
        $this->metadata[$name] = $name;
    }

    function getMetadata($name)
    {
        return $this->metadata[$name] ?? null;
    }

    function getAllMetadata() : array
    {
        return $this->metadata;
    }
}
