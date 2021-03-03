<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once './Lib/PHPExcel/PHPExcel.php';
require_once './Lib/PHPExcel/PHPExcel/Writer/Excel2007.php';

include_once './Config/config.php';

include_once './des.php';
include_once './rsa.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


foreach ($argv as $arg) {
    if ($arg === "createTestData") {
        createTestData($db_sqlite);
    }

    
    if ($arg === "genKeys") {
        genKeys();
        echo "OK";
    }

    if ($arg === "runSocketServer") {
        runSocketServer(
            $config['socket_host'],
            $config['socket_port'],
            $config['rabbitmq_host'],
            $config['rabbitmq_port'],
            $config['rabbitmq_user'],
            $config['rabbitmq_password']
        );
    }
}

function runSocketServer($address, $port, $rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_password)
{
    set_time_limit(0);

    $max_clients    = 10;
    $client_sockets = array();
    $client_keys = array();
    $master         = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    $res            = true;

    $res &= @socket_bind($master, $address, $port);
    $res &= @socket_listen($master);

    $connection = new AMQPStreamConnection($rabbitmq_host, $rabbitmq_port, $rabbitmq_user, $rabbitmq_password);

    $channel = $connection->channel();
    $channel->queue_declare('waybills', false, false, false, false);

    if (!$res) {
        die("Невозможно привязать и прослушивать $address: $port\n");
    } else {
        echo " [x] Awaiting TCP requests on $address: $port\n";
    }

    $read = array($master);
    $NULL = NULL;

    while (true) {
        $read = $client_sockets;
        $read[] = $master;

        $num_changed = socket_select($read, $NULL, $NULL, 0, 10);
        if ($num_changed) {
            if (in_array($master, $read)) {
                if (count($client_sockets) < $max_clients) {
                    $client_sockets[] = socket_accept($master);
                    echo "Принято подключение (" . count($client_sockets)  . " clients)\n";
                }
            }
        }
        foreach ($client_sockets as $key => $client) {
            $input = socket_read($client, 204800);
            if (!empty($input)) echo "$input\n";
            if ($input === false) {
                socket_shutdown($client);
                unset($client_sockets[$key]);
                unset($client_keys[$key]);
            } else {
                $input = json_decode(trim($input), true);
            }

            if ($input['function'] == 'get_public_key') {
                socket_write($client, file_get_contents('./public-key.pem'), strlen(file_get_contents('./public-key.pem')));
            }
            if ($input['function'] == 'save_key') {
                echo "save key " . rsaDecode(file_get_contents('./private-key.pem'), $input['data']) . "\n";
                $client_keys[$key] = rsaDecode(file_get_contents('./private-key.pem'), $input['data']);
                socket_write($client, 'ok');
            }
            if ($input['function'] == 'add_row') {
                echo "add row\n";
                
                $msg = new AMQPMessage(desDecrypt($input['data'], $client_keys[$key]));
                $channel->basic_publish($msg, '', 'waybills');
                socket_write($client, 'ok');
            }
            if ($input['function'] == 'close') {
                echo "connection close\n";
                socket_close($client);
                unset($client_sockets[$key]);
                unset($client_keys[$key]);

                $channel->close();
                $connection->close();
            }
        } // END FOREACH

        // END IF ($num_changed)
    } // END WHILE
}
