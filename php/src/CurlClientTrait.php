<?php
namespace Lv\Grpc;

use Google\Protobuf\Internal\Message;

trait CurlClientTrait
{
    use BinNameTrait;

    private $authority;
    private $curl;
    private $request_metadata = [];
    private $reply_metadata = [];

    public function __construct(string $authority = '')
    {
        $this->authority = $authority;
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, ['Content-Type: application/grpc+proto']); 

        curl_setopt($this->curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, 10);
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, 30);

        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, function ($curl, $header_line) {
            if (strpos($header_line, ':')) {
                list($name, $value) = explode(':', $header_line);
                $this->reply_metadata[trim($name)] = trim($value);
            }

            return strlen($header_line);
        });
    }

    public function useHttp1()
    {
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    }

    public function useHttp2()
    {
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
    }

    public function setTimeout(int $timeout_ms) : self
    {
        curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, $timeout_ms);
        return $this;
    }

    public function setConnectTimeout(int $timeout_ms) : self
    {
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT_MS, $timeout_ms);
        return $this;
    }

    public function setAuthority(string $authority) : self
    {
        $this->authority = $authority;
        return $this;
    }

    public function setMetadata($name, $value)
    {
        if (is_null($value)) {
            unset($this->request_metadata[$value]);
            return $this;
        }

        if ($this->isBinName($name)) {
            $value = base64_encode($value);
        }

        $this->request_metadata[$name] = $value;
        return $this;
    }

    public function getMetadata($name)
    {
        $value = $this->reply_metadata[$name] ?? null;
        if ($value && $this->isBinName($name)) {
            $value = base64_decode($value);
        }

        return $value;
    }

    private function send(string $path, Message $request, Message $reply)
    {
        $this->request_metadata = [];

        $url = $this->authority.$path;
        curl_setopt($this->curl, CURLOPT_URL, $url);

        $data = $request->serializeToString();
        $data = pack('CN', 0, strlen($data)).$data;
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

        $header_lines = [];
        foreach ($this->request_metadata as $name => $value) {
            $header_lines[] = "$name : $value";
        }
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header_lines);

        $data = curl_exec($this->curl);

        if (isset($this->reply_metadata['grpc-status'])) {
            if($this->reply_metadata['grpc-status'] == Status::OK) {
                $reply->mergeFromString(substr($data, 5));
            }

            $status = (int) $this->reply_metadata['grpc-status'];
            $msg = Status::STATUS_TO_MSG[$status] ?? Status::UNKNOWN;
        } else {
            $status = -curl_errno($this->curl);
            $msg = curl_error($this->curl);
        }

        return [$status, $msg];
    }
}
