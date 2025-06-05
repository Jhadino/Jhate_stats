<?php
include 'db.php';

$account_id = (int)$_POST['account_id'];
$personaname = trim($_POST['personaname']);

// Загрузка аватара
$uploadDir = 'uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$avatarPath = '';

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $fileName = uniqid() . '_' . basename($_FILES['avatar']['name']);
    $targetPath = $uploadDir . $fileName;
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = $_FILES['avatar']['type'];
    
    if (in_array($fileType, $allowedTypes)) {
        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            header("Location: users.php?error=upload_error");
            exit;
        }
        $avatarPath = $targetPath;
    } else {
        header("Location: users.php?error=invalid_file");
        exit;
    }
}

try {
    // Проверяем существование игрока
    $stmt = $pdo->prepare("SELECT account_id FROM players WHERE account_id = ? OR personaname = ?");
    $stmt->execute([$account_id, $personaname]);
    $existingPlayer = $stmt->fetch();
    
    if ($existingPlayer) {
        // Если игрок существует - обновляем только аватар
        if (!empty($avatarPath)) {
            $stmt = $pdo->prepare("UPDATE players SET avatar_path = ? WHERE account_id = ?");
            $stmt->execute([$avatarPath, $existingPlayer['account_id']]);
        }
    } else {
        // Если игрока нет - создаем нового
        $stmt = $pdo->prepare("INSERT INTO players (account_id, personaname, avatar_path) VALUES (?, ?, ?)");
        $stmt->execute([$account_id, $personaname, $avatarPath]);
    }
    
    header("Location: users.php");
    exit;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: users.php?error=db_error");
    exit;
}