<?php
namespace Lv\Grpc;

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

class SwooleServer
{
    use UnaryGrpc;

    /**
     * @var Server
     */
    private $server;

    public function __construct(string $addr, int $port, array $settings = [])
    {
        $server = new Server($addr, $port);
        $server->set([
            'open_http2_protocol' => true,
        ]);

        $server->on('request', [$this, 'onRequest']);

        $this->server = $server;
    }

    public function onRequest(Request $request, Response $response)
    {
        $session = new SwooleSession($request, $response);
        $this->doRequest($session);
    }

    public function run()
    {
        $this->server->start();
    }
}
