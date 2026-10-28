<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "smart_recruitment";

$conn = mysqli_connect($host, $user, $password, $database);

if(!$conn){
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Sorry, something went wrong. Please try again later.");
}

?>