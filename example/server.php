<?php
require __DIR__.'/vendor/autoload.php';

use Helloworld\HelloRequest;
use Helloworld\HelloReply;

class GreeterService extends Helloworld\AbstractGreeterService
{
    public function SayHello(HelloRequest $request, &$code, &$msg) : HelloReply
    {
        $name = $request->getName() ?? 'world';

        $reply = new HelloReply;
        $reply->setMessage('Hello '.$name);
        return $reply;
    }
}

$s = new Lv\Grpc\SwooleServer('127.0.0.1', 8080);
$s->addService(new GreeterService);

$s->run();
