<?php
namespace Lv\Grpc\Demo;

use Lv\Grpc\Context;
use Helloworld\HelloRequest;
use Helloworld\HelloReply;

class GreeterService implements \Helloworld\GreeterService
{
    use \Helloworld\GreeterServiceTrait;

    public function SayHello(Context $context, HelloRequest $request) : HelloReply
    {
        $a = $context->getMetadata('a-bin');
        $context->setMetadata('b-bin', "你好".$a);

        $name = $request->getName() ?? 'world';

        $reply = new HelloReply;
        $reply->setMessage('Hello '.$name);
        return $reply;
    }
}
