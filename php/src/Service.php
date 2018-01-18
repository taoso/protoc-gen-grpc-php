<?php
namespace Lv\Grpc;

interface Service
{
    /**
     * return uri method map of current service
     */
    function getMethods();
}
