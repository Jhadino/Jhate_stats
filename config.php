<?php
$host = getenv('localhost'); // Переменные окружения (безопасно)
$db   = getenv('postgres');
$user = getenv('Jhate');
$pass = getenv('2005');
$dsn = "pgsql:host=$host;dbname=$db";
$pdo = new PDO($dsn, $user, $pass);