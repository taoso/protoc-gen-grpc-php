<?php
require __DIR__.'/vendor/autoload.php';

// init stub
$service = new Helloworld\GreeterServiceStub(['127.0.0.1:50051'], [
    // 'use_http1' => true, // set true for fpm server
]);

// init context
$context = $service->newContext();
$context->setMetadata('a-bin', '海涛');
//$context->setMetadata('content-type', 'application/grpc+json');

// init request
$request = new Helloworld\HelloRequest;
$request->setName("海涛");

// call service
$reply = $service->SayHello($context, $request);

// echo reply
var_dump([$context->getStatus(), $context->getMessage(), $reply->getMessage()]);
var_dump($context->getMetadata('b-bin'));
