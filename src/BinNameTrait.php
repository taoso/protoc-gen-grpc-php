<?php
namespace Lv\Grpc;

trait BinNameTrait
{
    private function isBinName($name)
    {
        $len = strlen($name);
        return $len > 4 && substr($name, $len - 4) === '-bin';
    }
}
