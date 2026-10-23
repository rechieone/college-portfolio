<?php // login.php
    $host = 'localhost';
    $data = 'mydb'; // Ваша база данных
    $user = 'root';
    $pass = ''; // Впишите ваш пароль от MySQL Workbench внутрь кавычек, если он есть
    $chrs = 'utf8mb4';
    $attr = "mysql:host=$host;dbname=$data;charset=$chrs";
    $opts =
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
?>
