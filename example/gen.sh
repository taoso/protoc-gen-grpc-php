rm -rf out
mkdir -p out
protoc --php_out=out --grpc-php_out=require_version=dev-master,composer_name=grpc/hello-sdk:out ./helloworld.proto
git init out > /dev/null
