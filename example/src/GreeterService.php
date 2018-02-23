<?php
namespace Lv\Grpc\Demo;

use Helloworld\HelloRequest;
use Helloworld\HelloReply;

class GreeterService implements \Helloworld\GreeterService
{
    use \Helloworld\GreeterServiceTrait;

    public function SayHello(HelloRequest $request) : HelloReply
    {
        $context = $request->context();
        $a = $context->getMetadata('a-bin');
        $context->setMetadata('b-bin', "你好".$a);

        $name = $request->getName() ?? 'world';

        $reply = new HelloReply;
        $reply->setMessage('Hello '.$name);
        return $reply;
    }
}
