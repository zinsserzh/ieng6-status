<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

require_once('prepare.php');

header('Content-Type: application/json');
echo json_encode(prepare_data());
