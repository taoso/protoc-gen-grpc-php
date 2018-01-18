<?php
namespace Lv\Grpc;

use Google\Protobuf\Internal\Message;

trait UnaryGrpc
{
    private $uri_method = [];
    private $not_found_service;

    private function doRequest(Session $session)
    {
        $status = Status::OK;
        $data = substr($session->getBody(), 5);

        try {
            $service = $this->getService($session->getPath());

            /** @var Message $message */
            $message = $service($session, $data);
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

    private function getService(string $uri)
    {
        if (isset($this->uri_method[$uri])) {
            return $this->uri_method[$uri];
        }

        if (!isset($this->not_found_service)) {
            $this->not_found_service = new NotFoundService;
        }

        return $this->not_found_service;
    }

    public function addService(Service $service)
    {
        foreach ($service->getMethods() as $uri => $method) {
            $this->uri_method[$uri] = [$service, $method];
        }
    }
}
