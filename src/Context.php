<?php
namespace Lv\Grpc;

interface Context
{
    /**
     * set grpc status
     */
    function setStatus(int $status);

    /**
     * get grpc status
     */
    function getStatus() : int;

    /**
     * set grpc message
     */
    function setMessage(string $message);

    /**
     * get grpc message
     */
    function getMessage() : string;

    /**
     * Get grpc metadata. if the name has '-bin', the value
     * will be base64_decoded.
     *
     * The $name MUST be case-insensitive.
     *
     * @return string|null
     */
    function getMetadata(string $name);

    /**
     * set grpc metadata. if the name has '-bin', the value
     * will be base64_encoded.
     */
    function setMetadata(string $name, string $value);

    /**
     * get and clear all grpc metadata
     */
    function getAllMetadata() : array;
}
