<?php
namespace Lv\Grpc;

use Google\Protobuf\Internal\Message;
use Google\Protobuf\Internal\GPBDecodeException;

trait UnaryGrpc
{
    private $uri_method = [];
    private $not_found_service;

    private function doRequest(Session $session)
    {
        $data = $session->getBody();
        $content_type = $session->getMetadata('content-type');

        $is_grpc = substr($content_type, 0, 17) === 'application/grpc+';

        if ($is_grpc) {
            $options = unpack('Cflag/Nlength', substr($data, 0, 5));

            if ($options['flag']) {
                // TODO support compress
                return $session->end(Status::UNIMPLEMENTED);
            }

            if ($options['length'] + 5 !== strlen($data)) {
                return $session->end(Status::INVALID_ARGUMENT);
            }

            $data = substr($data, 5);
        } elseif ($content_type !== 'application/json') {
            return $session->end(Status::INVALID_ARGUMENT);
        }

        try {
            $service = $this->getService($session->getUri());

            /** @var Message $message */
            $message = $service($session, $data);

            $grpc_status = $session->getStatus();
            $grpc_message = $session->getMessage();
            if ($grpc_message) {
                $session->setMetadata('grpc-message', $grpc_message);
            }

            if ($grpc_status === Status::OK) {
                if ($content_type === 'application/grpc+proto') {
                    $data = $message->serializeToString();
                } else {
                    $data = $message->serializeToJsonString();
                }

                if ($is_grpc) {
                    $data = pack('CN', 0, strlen($data)).$data;
                }
                $session->setMetadata('Content-Type', $content_type);
                $session->end(Status::OK, $data);
            } else {
                $session->end($grpc_status);
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
