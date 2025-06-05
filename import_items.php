<?php
// Конфигурация
$config = [
    'db' => [
        'host' => 'localhost',
        'port' => '5432',
        'name' => 'postgres',
        'user' => 'Jhate',
        'pass' => '2005'
    ],
    'json_file' => 'items.json' // Путь к вашему JSON-файлу
];

try {
    // Подключение к БД
    $dsn = "pgsql:host={$config['db']['host']};port={$config['db']['port']};dbname={$config['db']['name']}";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Чтение JSON-файла
    if (!file_exists($config['json_file'])) {
        throw new Exception("JSON file not found: {$config['json_file']}");
    }
    
    $jsonData = file_get_contents($config['json_file']);
    $itemsData = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }

    // Создание таблицы (если не существует)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS items (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            cost INTEGER NOT NULL,
            effects JSONB,
            image VARCHAR(255),
            dname VARCHAR(100),
            qual VARCHAR(50),
            behavior VARCHAR(100),
            lore TEXT,
            cooldown INTEGER,
            attributes JSONB,
            components JSONB,
            created BOOLEAN DEFAULT FALSE,
            charges BOOLEAN DEFAULT FALSE
        )
    ");

    // Подготовка запроса
    $insertQuery = "
        INSERT INTO items (
            name, cost, effects, image, dname, qual, behavior, 
            lore, cooldown, attributes, components, created, charges
        ) VALUES (
            :name, :cost, :effects, :image, :dname, :qual, :behavior, 
            :lore, :cd, :attrib, :components, :created, :charges
        )
        ON CONFLICT (name) DO UPDATE SET
            cost = EXCLUDED.cost,
            effects = EXCLUDED.effects,
            image = EXCLUDED.image,
            dname = EXCLUDED.dname,
            qual = EXCLUDED.qual,
            behavior = EXCLUDED.behavior,
            lore = EXCLUDED.lore,
            cooldown = EXCLUDED.cooldown,
            attributes = EXCLUDED.attributes,
            components = EXCLUDED.components,
            created = EXCLUDED.created,
            charges = EXCLUDED.charges
    ";
    
    $stmt = $pdo->prepare($insertQuery);

    // Обработка каждого предмета
    $processed = 0;
    foreach ($itemsData as $itemName => $itemData) {
        // Стандартизация данных
        $effects = [
            'abilities' => $itemData['abilities'] ?? [],
            'notes' => $itemData['notes'] ?? null,
            'dmg_type' => $itemData['dmg_type'] ?? null,
            'hint' => $itemData['hint'] ?? []
        ];
        
        $stmt->execute([
            ':name' => $itemName,
            ':cost' => $itemData['cost'] ?? 0,
            ':effects' => json_encode($effects),
            ':image' => $itemData['img'] ?? null,
            ':dname' => $itemData['dname'] ?? '',
            ':qual' => $itemData['qual'] ?? '',
            ':behavior' => $itemData['behavior'] ?? '',
            ':lore' => $itemData['lore'] ?? '',
            ':cd' => $itemData['cd'] ?? 0,
            ':attrib' => json_encode($itemData['attrib'] ?? $itemData['attrib'] ?? []),
            ':components' => json_encode($itemData['components'] ?? []),
            ':created' => $itemData['created'] ?? false,
            ':charges' => $itemData['charges'] ?? false
        ]);
        
        $processed++;
    }

    echo "Successfully processed $processed items.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>