<?php
include 'db.php'; // Подключение к базе данных
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data: " . print_r($_POST, true));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $role = trim($_POST['role']);
    $description = trim($_POST['description']);
    
    // Атрибуты 
    $intelligence = (int)$_POST['intelligence'];
    $agility = (int)$_POST['agility'];
    $strength = (int)$_POST['strength'];
    $base_speed = (int)$_POST['base_speed'];
    $base_damage = (int)$_POST['base_damage'];
    $mana = (int)$_POST['mana'];
    $health = (int)$_POST['health'];
    $primary_attribute = trim($_POST['primary_attribute']);
    $attack_range = (int)$_POST['attack_range'];
    $attack_type = trim($_POST['attack_type']);
    
    // Сбор способностей из POST-запроса
    $abilities = [];
    $descriptions = [];
    $essences = [];
    $abilityImages = [];
    
    for ($i = 1; $i <= 4; $i++) {
        if (isset($_POST["ability_{$i}_name"]) && !empty(trim($_POST["ability_{$i}_name"]))) {
            $abilities[] = trim($_POST["ability_{$i}_name"]);
            $descriptions[] = isset($_POST["ability_{$i}_description"]) ? trim($_POST["ability_{$i}_description"]) : null;
            $essences[] = isset($_POST["ability_{$i}_essence"]) ? trim($_POST["ability_{$i}_essence"]) : null;

            // Проверка загруженного изображения способности
            if (isset($_FILES["ability_{$i}_image"]) && $_FILES["ability_{$i}_image"]['error'] === UPLOAD_ERR_OK) {
                $fileType = mime_content_type($_FILES["ability_{$i}_image"]['tmp_name']);
                $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];

                if (in_array($fileType, $allowedTypes)) {
                    $abilityImage = file_get_contents($_FILES["ability_{$i}_image"]['tmp_name']);
                    $abilityImages[] = pg_escape_bytea($abilityImage);
                } else {
                    echo "Ошибка: Неподдерживаемый тип файла для способности {$i}.";
                    exit;
                }
            } else {
                echo "Изображение способности {$i} не было загружено.";
                exit;
            }
        }
    }

    // Обработка и сохранение данных в базу данных
    
}
?>
