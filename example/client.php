<?php
require __DIR__.'/vendor/autoload.php';

$client = new Helloworld\GreeterClient('127.0.0.1:8080');

$context = $client->newContext();
$context->setMetadata('a', 1);
$context->setMetadata('content-type', 'application/grpc+json');
$request = new Helloworld\HelloRequest;
$request->setName("Haitao Lv");

$reply = $client->SayHello($context, $request);
var_dump([$context->getStatus(), $context->getMessage(), $reply->getMessage()]);
var_dump($context->getMetadata('b'));
