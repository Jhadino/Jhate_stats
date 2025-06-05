<?php
$host = 'localhost';
$db = 'postgres'; 
$user = 'Jhate';  
$pass = '2005';   

try {
    // Создаем подключение к базе данных
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    

    $pdo->exec("SET NAMES 'UTF8'");
    


} catch (PDOException $e) {
    echo 'Ошибка подключения: ' . $e->getMessage();
}
?>
