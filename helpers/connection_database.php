<?php
require_once "helpers/headers.php";

$link = mysqli_connect("127.0.0.1", "food-delivery", "34DHZraa5", "food-delivery");
if (!$link) {
    setHTTPStatus('500', mysqli_connect_error());
    exit;
}