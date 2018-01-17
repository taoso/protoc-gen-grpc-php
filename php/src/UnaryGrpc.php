<?php
namespace Lv\Grpc;

use Google\Protobuf\Internal\Message;

trait UnaryGrpc
{
    private $path_service = [];
    private $not_found_service;

    private function doRequest(Session $session)
    {
        $status = Status::OK;
        $data = substr($session->getBody(), 5);

        try {
            $service = $this->getService($session->getPath());

            /** @var Message $message */
            $message = $service($data, $status, $msg);
            if ($status === Status::OK) {
                $data = $message->serializeToString();
                $session->setMetadata('content-type', 'application/grpc+proto');
                $session->end(Status::OK, pack('CN', 0, strlen($data)).$data);
            } else {
                $session->end($status);
            }
        } catch (GPBDecodeException $e) {
            $session->end(Status::INVALID_ARGUMENT);
        } catch (Throwable $t) {
            // TODO more error code
            $session->end(Status::INTERNAL);
        }
    }

    private function getService($path)
    {
        if (isset($this->path_service[$path])) {
            return $this->path_service[$path];
        }

        if (!isset($this->not_found_service)) {
            $this->not_found_service = new NotFoundService;
        }

        return $this->not_found_service;
    }

    public function addService($service)
    {
        foreach ($service::PATH_METHOD as $path => $method) {
            $this->path_service[$path] = [$service, $method];
        }
    }
}
