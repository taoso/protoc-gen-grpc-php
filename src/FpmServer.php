<?php
namespace Lv\Grpc;

class FpmServer
{
    use UnaryGrpc;

    public function run()
    {
        $session = new FpmSession($_POST, $_SERVER);
        $session->setMetadata('lv-bin', '海涛');
        $this->doRequest($session);
    }
}
