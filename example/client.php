<?php
require __DIR__.'/vendor/autoload.php';

// init stub
$service = new Helloworld\GreeterServiceStub(['127.0.0.1:8080'], [
    // 'use_http1' => true, // set true for fpm server
]);

// init request
$request = new Helloworld\HelloRequest;
$request->setName("海涛");

$context = $request->context();
$context->setMetadata('a-bin', '海涛');
$context->setMetadata('content-type', 'application/json');
// $context->setMetadata('content-type', 'application/grpc+json');
// $context->setMetadata('content-type', 'application/grpc+proto');

// call service
$reply = $service->SayHello($request);
$context = $reply->context();

// echo reply
var_dump([$context->getStatus(), $context->getMessage(), $reply->getMessage()]);
var_dump($context->getMetadata('b-bin'));
