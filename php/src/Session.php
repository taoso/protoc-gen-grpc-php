<?php
namespace Lv\Grpc;

interface Session extends Context
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
     * set response to client
     */
    function end(int $status = Status::OK, string $body = null);
}
