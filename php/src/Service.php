<?php
namespace Lv\Grpc;

interface Service
{
    /**
     * return uri method map of current service
     */
    function getMethods();

    /**
     * generate new context.
     *
     * ONLY USED BY CLIENT
     */
    function newContext() : Context;

    /**
     * get request error code.
     *
     * ONLY USED BY CLIENT
     */
    function getLastErrno();

    /**
     * get request error message.
     *
     * ONLY USED BY CLIENT
     */
    function getLastError();
}
