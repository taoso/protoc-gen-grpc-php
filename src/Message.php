<?php
namespace Lv\Grpc;

class Message extends \Google\Protobuf\Internal\Message
{
    private $context;

    /**
     * Get or set or create a context object for request or reply
     */
    public function context(Context $context = null) : Context
    {
        if (!$this->context) {
            $this->context = $context ?: new SimpleContext;
        }

        return $this->context;
    }
}
