<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "ims";

$conn = new mysqli($host, $username, $password, $database, 3307);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

?>