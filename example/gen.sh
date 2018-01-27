rm -rf sdk
mkdir -p sdk
protoc --php_out=sdk --plugin=protoc-gen-grpc-php=./vendor/bin/protoc-gen-grpc-php --grpc-php_out=composer_name=lvht/hello-sdk:sdk ./helloworld.proto
git init sdk > /dev/null
