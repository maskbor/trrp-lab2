<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/rsa.php';
require_once __DIR__ . './Config/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RpcServer
{
    public function __construct($host, $port, $user, $password)
    {
        $connection = new AMQPStreamConnection(
            $host,
            $port,
            $user,
            $password
        );

        $channel = $connection->channel();
        $desKeys = [];

        $channel->queue_declare('rpc_queue', false, false, false, false);

        echo " [x] Awaiting RPC requests\n";
        $callback = function ($req) {
            echo ' [.] ', $req->body, "\n";

            $body = json_decode($req->body, true);

            if ($body['function'] === 'get_public_key')
                $msg = new AMQPMessage(
                    file_get_contents('./public-key.pem'),
                    array('correlation_id' => $req->get('correlation_id'))
                );
            if ($body['function'] === 'save_key') {
                $desKeys[$req->get('reply_to')] = rsaDecode(file_get_contents('./private-key.pem'), $body['data']);
                $msg = new AMQPMessage(
                    'ok',
                    array('correlation_id' => $req->get('correlation_id'))
                );
            }

            $req->delivery_info['channel']->basic_publish(
                $msg,
                '',
                $req->get('reply_to')
            );
            $req->ack();
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume('rpc_queue', '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
