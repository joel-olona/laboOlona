<?php

namespace App\Manager\Mercure;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureManager
{
    public function __construct(
        private HubInterface $hub
    ){}

    public function publish( string $topic, string $item, array $data,string $message ,  string $status='success' )
    {
        $update = new Update($topic, json_encode(
            [
                'status' => $status,
                'item' => $item,
                'data' => $data,
                'message' => $message
            ]
        ));

        $this->hub->publish($update);
    }
}