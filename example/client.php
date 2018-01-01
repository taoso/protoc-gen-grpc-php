<?php
require __DIR__.'/vendor/autoload.php';

$client = new Helloworld\GreeterClient('127.0.0.1:8080');

$request = new Helloworld\HelloRequest;
$request->setName("Haitao Lv");

$reply = $client->SayHello($request, $code, $msg);
var_dump([$code, $msg, $reply->getMessage()]);
