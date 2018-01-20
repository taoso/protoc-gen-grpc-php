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
     * get grpc metadata. if the name has '-bin', the value
     * will be base64_decoded.
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
     *
     * FOR CLIENT ONLY!
     */
    function getAndClearAllMetadata() : array;
}
