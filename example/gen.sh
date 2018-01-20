rm -rf sdk
mkdir -p sdk
protoc --php_out=sdk --grpc-php_out=composer_name=lvht/hello-sdk:sdk ./helloworld.proto
git init sdk > /dev/null
