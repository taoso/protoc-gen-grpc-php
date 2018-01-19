<?php
namespace Lv\Grpc;

use Google\Protobuf\GPBEmpty;

class NotFoundService
{
    private $empty_reply;

    public function __construct()
    {
        $this->empty_reply = new GPBEmpty;
    }

    public function __invoke(Context $context, $data)
    {
        $context->setStatus(Status::NOT_FOUND);
        return $this->empty_reply;
    }
}
