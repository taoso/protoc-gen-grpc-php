<?php
namespace Lv\Grpc;

interface Service
{
    /**
     * return uri method map of current service
     *
     * ONLY USED BY SERVER
     */
    function getMethods();

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
