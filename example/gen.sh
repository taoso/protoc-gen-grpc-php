#!/usr/bin/env bash
rm -rf sdk
mkdir -p sdk
protoc --my-php_out=base_message='\Lv\Grpc\Message':sdk --plugin=protoc-gen-my-php=../vendor/bin/protoc-gen-plugin.php --plugin=protoc-gen-grpc-php=./vendor/bin/protoc-gen-grpc-php --grpc-php_out=composer_name=lvht/hello-sdk:sdk ./helloworld.proto
git init sdk > /dev/null
