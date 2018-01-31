<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

if (isset($_SERVER['HTTP_SECRET']) && $_SERVER['HTTP_SECRET'] == getenv("SECRET"))
{
    $mysqli = new mysqli(
        getenv("DB_HOST"),
        getenv("DB_USER"),
        getenv("DB_PASSWORD"),
        getenv("DB_DATABASE")
    );
    $mysqli->set_charset("utf8");

    $pusher_options = array(
        'cluster' => getenv("PUSHER_CLUSTER"),
        'encrypted' => true
    );

    $pusher = new Pusher\Pusher(
        getenv("PUSHER_KEY"),
        getenv("PUSHER_SECRET"),
        getenv("PUSHER_APP_ID"),
        $pusher_options
    );

    $raw = file_get_contents("php://input");

    if (1 == preg_match('/^(.*)\|.*([0-9]+) user.*load average: ([0-9\.]+), ([0-9\.]+), ([0-9\.]+)/', $raw, $match))
    {
        $hostname = $match[1];
        $u  = intval($match[2]);
        $l1 = floatval($match[3]);
        $l5 = floatval($match[4]);
        $l15 = floatval($match[5]);
    }

    $mysqli->query("UPDATE host_status SET last_contact=UNIX_TIMESTAMP(), users=${u}, load_1min=${l1}, load_5min=${l5}, load_15min=${l15} WHERE `hostname`='${hostname}'");
    $pusher->trigger("status", "update", [
        "hostname" => $hostname,
        "users" => $u,
        "load_1min" => $l1,
        "load_5min" => $l5,
        "load_15min" => $l15,
    ]);
}
