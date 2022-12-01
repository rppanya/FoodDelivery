<?php
    $link = mysqli_connect("127.0.0.1", "food-delivery", "34DHZraa5", "food-delivery");
    if (!$link) {
        echo "Ошибка: невозможно установить соединение с MySQL." . PHP_EOL;
        echo "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }