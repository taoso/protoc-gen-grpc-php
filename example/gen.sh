rm -rf out
mkdir -p out
protoc --php_out=out --grpc-php_out=composer_name=lvht/hello-sdk:out ./helloworld.proto
git init out > /dev/null
