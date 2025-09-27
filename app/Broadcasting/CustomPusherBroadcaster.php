<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Pusher\Pusher;

class CustomPusherBroadcaster extends PusherBroadcaster
{
    /**
     * Create a new Pusher instance.
     *
     * @param  array  $config
     * @return \Pusher\Pusher
     */
    protected function pusher(array $config)
    {
        $pusher = new Pusher(
            $config['key'],
            $config['secret'],
            $config['app_id'],
            $config['options'] ?? []
        );

        if ($config['options']['host'] ?? false) {
            // Força o host correto
            $pusher->setHost($config['options']['host']);
        }

        return $pusher;
    }
}
