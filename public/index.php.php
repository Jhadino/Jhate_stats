<?php
echo "DOTA Site is working!";
?>
<?php
// Проверка расширений
if (!extension_loaded('pdo') || !extension_loaded('pdo_pgsql')) {
    die("PDO или pdo_pgsql не установлены. Обратитесь в поддержку Render.");
}

// Подключение к PostgreSQL
try {
    $pdo = new PDO(
        "pgsql:host=" . getenv('DB_HOST') . ";port=5432;dbname=" . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    echo "<h1>DOTA Site is working!</h1>";
    echo "Database version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
} catch (PDOException $e) {
    die("<h2>Database error:</h2><pre>" . $e->getMessage() . "</pre>");
}
?>
