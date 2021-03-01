<?php
require_once __DIR__ . '/vendor/autoload.php';

include_once './Config/config.php';

require_once './DB/DB.php';

require_once './Models/Waybill.php';
require_once './Models/Vehicle.php';
require_once './Models/Responsible.php';
require_once './Models/Region.php';
require_once './Models/Norma.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection(
    $config['rabbitmq_host'],
    $config['rabbitmq_port'],
    $config['rabbitmq_user'],
    $config['rabbitmq_password']
);

$channel = $connection->channel();
$channel->queue_declare('waybills', false, false, false, false);

$db = new DB(
    $config['mysql_db_host'],
    $config['mysql_db_user'],
    $config['mysql_db_password'],
    $config['mysql_db_name']
);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

//Функция, которая будет обрабатывать данные, полученные из очереди
$callback = function ($msg) {
    echo " [x] Received ", $msg->body, "\n";

    global $db;
    $waybill = new Waybill($db);
    $vehicle = new Vehicle($db);
    $responsible = new Responsible($db);
    $region = new Region($db);
    $norma = new Norma($db);

    $row = json_decode($msg->body, true);
    $id_region = $region->findOrCreate([
        'region' => $row['region']
    ]);
    $id_norma = $norma->findOrCreate([
        'winter_highway' => $row['winter_highway'],
        'winter_city' => $row['winter_city'],
        'summer_highway' => $row['summer_highway'],
        'summer_city' => $row['summer_city'],
    ]);
    $id_responsible = $responsible->findOrCreate([
        'fio' => $row['responsible'],
        'phone' => $row['phone']
    ]);
    $id_vehicle = $vehicle->findOrCreate([
        'name' => $row['vehicles'],
        'regNumber' => $row['reg_number'],
        'id_norma' => $id_norma,
        'fuel' => $row['fuel'],
        'odometer' => $row['odometer'],
        'id_responsible' => $id_responsible
    ]);
    $id_waybill = $waybill->findOrCreate([
        'id_region' => $id_region,
        'id_vehicle' => $id_vehicle,
        'fuel_add' => $row['fuel_add'],
        'fuel_start' => $row['fuel_start'],
        'fuel_end' => $row['fuel_end'],
        'odometer_start' => $row['odometer_start'],
        'odometer_end' => $row['odometer_end'],
        'is_city' => $row['is_city'],
        'comment' => $row['comment'],
    ]);
};

//Уходим слушать сообщения из очереди в бесконечный цикл
$channel->basic_consume('waybills', '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}

//Не забываем закрыть соединение и канал
$channel->close();
$connection->close();
