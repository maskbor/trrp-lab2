<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once './Lib/PHPExcel/PHPExcel.php';
require_once './Lib/PHPExcel/PHPExcel/Writer/Excel2007.php';

include_once './Config/config.php';

include_once './des.php';
include_once './rsa.php';

$db_sqlite = new SQLite3($config['sqlite_db_name']);
if (!$db_sqlite)
    exit("Не удалось создать/открыть базу данных SQLite");
if (!$db_sqlite->exec(file_get_contents('./DB/sql/createSQLiteDB.sql')))
    exit($db_sqlite->lastErrorMsg());

foreach ($argv as $arg) {
    if ($arg === "createTestData") {
        createTestData($db_sqlite);
    }
    if ($arg === "exportSQLiteToMysql") {
        exportSQLiteToMysql($config['socket_host'], $config['socket_port'], $db_sqlite);
    }
}

function createTestData($db_sqlite)
{
    if (!$db_sqlite->exec(file_get_contents('./DB/sql/createTestData.sql')))
        exit($db_sqlite->lastErrorMsg());
    echo "Добавлено " . $db_sqlite->changes() . " строк\n";
}

function exportSQLiteToMysql($address, $port, $db)
{
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
     
    socket_connect($socket, $address, $port);
    $msg = json_encode(['function' => 'get_public_key'])."\n";
    socket_write($socket, $msg, strlen($msg));
     
    $publicKey = socket_read($socket, 204800);

    echo "publicKey $publicKey\n";
    $desKey = openssl_random_pseudo_bytes(32);
    $msg = json_encode(['function' => 'save_key', 'data' => rsaEncode($publicKey, $desKey)])."\n";
    socket_write($socket, $msg, strlen($msg));
    $status = socket_read($socket, 204800);

    $result = $db->query("select * from waybill");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        socket_write($socket, json_encode(['function' => 'add_row', 'data' => desEncrypt(json_encode($row), $desKey)])."\n");
        $status = socket_read($socket, 204800);
        echo "add row status $status";
    }

    socket_write($socket, json_encode(['function' => 'close'])."\n");
    socket_close($socket);
}

