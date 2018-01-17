<?php
namespace Lv\Grpc;

interface Session
{
    /**
     * get http request path
     * For example, http://foo.com/bar?a=1
     *
     * return /bar
     */
    function getPath();

    /**
     * get http request body
     */
    function getBody();

    /**
     * get grpc metadata. if the name has '-bin', the value
     * will be base64_decoded.
     */
    function getMetadata($name);

    /**
     * set grpc metadata. if the name has '-bin', the value
     * will be base64_encoded.
     */
    function setMetadata(string $name, string $value);

    /**
     * set response to client
     */
    function end(int $status = Status::OK, string $body = null);
}
