<?php
namespace Lv\Grpc;

interface Context
{
    /**
     * set grpc status
     */
    function setStatus(int $status);

    /**
     * set a grpc metadata named by $name
     */
    function setMetadata($name);

    /**
     * get a grpc metadata named by $name
     */
    function getMetadata($name);

    /**
     * get all grpc metadata
     */
    function getAllMetadata() : array;
}
