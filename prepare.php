<?php

function prepare_data() {
    $mysqli = new mysqli(
        getenv("DB_HOST"),
        getenv("DB_USER"),
        getenv("DB_PASSWORD"),
        getenv("DB_DATABASE")
    );
    $mysqli->set_charset("utf8");

    $data = [];

    $result = $mysqli->query("SELECT * FROM host_status ORDER BY hostname ASC");
    while ($row = $result->fetch_assoc())
        $data[$row["hostname"]] = $row;

    return $data;
}
