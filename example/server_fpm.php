<?php
require __DIR__.'/vendor/autoload.php';

$s = new Lv\Grpc\FpmServer;
$s->addService(new Lv\Grpc\Demo\GreeterService);

$s->run();
