<?php
require __DIR__.'/vendor/autoload.php';

$s = new Lv\Grpc\SwooleServer('127.0.0.1', 50051);
$s->addService(new Lv\Grpc\Demo\GreeterService);

$s->run();
