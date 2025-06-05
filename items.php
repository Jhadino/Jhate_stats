<?php
header('Content-Type: text/html; charset=utf-8');
include 'db.php';


$itemsPerPage = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;


$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = $search 
    ? $pdo->prepare("SELECT * FROM items WHERE display_name ILIKE ? AND display_name NOT ILIKE '%Recipe%' LIMIT ? OFFSET ?")
    : $pdo->prepare("SELECT * FROM items WHERE display_name NOT ILIKE '%Recipe%' LIMIT ? OFFSET ?");
    
if ($search) {
    $query->execute(["%$search%", $itemsPerPage, $offset]);
} else {
    $query->execute([$itemsPerPage, $offset]);
}
$items = $query->fetchAll(PDO::FETCH_ASSOC);


$countQuery = $search 
    ? $pdo->prepare("SELECT COUNT(*) FROM items WHERE display_name ILIKE ? AND display_name NOT ILIKE '%Recipe%'")
    : $pdo->prepare("SELECT COUNT(*) FROM items WHERE display_name NOT ILIKE '%Recipe%'");
    
if ($search) {
    $countQuery->execute(["%$search%"]);
} else {
    $countQuery->execute();
}
$totalItems = $countQuery->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);



function processAttributes($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = processAttributes($value);
            } else {
                // Заменяем {value} на фактическое число из данных
                if (strpos($value, '{value}') !== false && is_numeric($key)) {
                    $parts = explode(',', $value);
                    if (count($parts) >= 2) {
                        $numValue = trim($parts[1]);
                        $data[$key] = str_replace('{value}', $numValue, trim($parts[2]));
                    }
                }
            }
        }
    }
    return $data;
}


function shouldDisplay($value) {
    if ($value === false || $value === 'false' || $value === 'N/A' || $value === '[]' || $value === null || $value === '0' || $value === 0 || $value === '') {
        return false;
    }
    return true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOTA 2 Items</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Oxanium:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #e4a101;
            --primary-dark: #d88c00;
            --secondary-color: #1a3e72;
            --dark-color: #0f1923;
            --light-color: #f8f9fa;
            --background-dark: #121a24;
            --background-light: #1e2a3a;
            --text-color: #eaeaea;
            --text-secondary: #b8b8b8;
            --accent-color: #4e9bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --border-radius: 8px;
            --transition: all 0.3s ease;
            --box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--background-dark), var(--background-light));
            color: var(--text-color);
            line-height: 1.6;
            padding: 0;
            background-attachment: fixed;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .back-button {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .back-button:hover {
            color: var(--primary-dark);
        }
        
        .back-button i {
            margin-right: 5px;
        }
        
        .header {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-family: 'Oxanium', sans-serif;
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        /* Поиск */
        .search-container {
            margin: 30px 0;
            text-align: center;
        }
        
        .search-box {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            border-radius: 30px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            outline: none;
            transition: var(--transition);
        }
        
        .search-input:focus {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        /* Карточки */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        
        .item-card {
            background: rgba(26, 62, 114, 0.2);
            border: 1px solid rgba(78, 155, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            backdrop-filter: blur(5px);
            border-left: 3px solid var(--primary-color);
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            border-left-color: var(--primary-dark);
        }
        
        .item-name {
            font-family: 'Oxanium', sans-serif;
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .item-cost {
            background: var(--primary-color);
            color: var(--dark-color);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .item-image {
            text-align: center;
            margin: 15px 0;
            flex-shrink: 0;
        }
        
        .item-image img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: var(--border-radius);
            border: 2px solid var(--primary-color);
            background: rgba(0, 0, 0, 0.3);
            transition: var(--transition);
        }
        
        .item-card:hover .item-image img {
            transform: scale(1.05);
            box-shadow: 0 0 15px rgba(228, 161, 1, 0.3);
        }
        
        .item-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed rgba(78, 155, 255, 0.3);
            flex-grow: 1;
            overflow: hidden;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .detail-label {
            color: var(--text-secondary);
            min-width: 100px;
            font-weight: 500;
        }
        
        .detail-value {
            flex: 1;
            word-break: break-word;
        }
        
        .section-title {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 15px 0 8px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        .info-list {
            list-style-type: none;
            margin-left: 5px;
            font-size: 14px;
        }
        
        .info-list li {
            padding: 3px 0;
            position: relative;
            padding-left: 15px;
            line-height: 1.4;
        }
        
        .info-list li::before {
            content: '•';
            color: var(--primary-color);
            position: absolute;
            left: 0;
        }
        
        /* Пагинация */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            gap: 8px;
        }
        
        .page-link {
            display: inline-block;
            padding: 8px 15px;
            background: rgba(26, 62, 114, 0.3);
            color: var(--text-color);
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid rgba(78, 155, 255, 0.1);
        }
        
        .page-link:hover, .page-link.active {
            background: var(--primary-color);
            color: var(--dark-color);
            font-weight: bold;
        }
        
        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        /* Сообщение о пустом результате */
        .empty-message {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
            font-size: 1.2rem;
            grid-column: 1 / -1;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .items-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Menu</a>
        
        <div class="header">
            <h1>DOTA 2 Items</h1>
            <p>Browse the collection of powerful in-game items</p>
        </div>
        

        <div class="search-container">
            <form method="GET" action="">
                <input type="hidden" name="page" value="1">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search items..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </form>
        </div>
        
        <div class="items-grid">
        <?php if (empty($items)): ?>
            <div class="empty-message">
                <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 15px; color: var(--primary-color);"></i>
                <p>No items found matching your search</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <div class="item-card">
                    <div class="item-name">
                        <?= htmlspecialchars($item['display_name']) ?>
                        <?php if (shouldDisplay($item['cost'])): ?>
                            <span class="item-cost"><?= $item['cost'] ?> gold</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-image">
                        <?php if (!empty($item['image_url']) && shouldDisplay($item['image_url'])): 
                            $imageUrl = $item['image_url'];
                            if (!preg_match('/^https?:\/\//i', $imageUrl)) {
                                $imageUrl = 'https://cdn.cloudflare.steamstatic.com' . $imageUrl;
                            }
                            ?>
                            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                 alt="<?= htmlspecialchars($item['display_name']) ?>"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                            <div class="no-image" style="display:none">No image</div>
                        <?php else: ?>
                            <div class="no-image">No image</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="item-details">
                        <?php if (shouldDisplay($item['qual'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Quality:</div>
                            <div class="detail-value"><?= htmlspecialchars($item['qual']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (shouldDisplay($item['behavior'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Behavior:</div>
                            <div class="detail-value"><?= htmlspecialchars($item['behavior']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (shouldDisplay($item['damage_type'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Damage:</div>
                            <div class="detail-value"><?= htmlspecialchars($item['damage_type']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (shouldDisplay($item['cooldown'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Cooldown:</div>
                            <div class="detail-value"><?= $item['cooldown'] ?>s</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (shouldDisplay($item['mana_cost'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Mana Cost:</div>
                            <div class="detail-value"><?= htmlspecialchars($item['mana_cost']) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (shouldDisplay($item['attributes'])):
                            $attributes = json_decode($item['attributes'], true);
                            if (json_last_error() === JSON_ERROR_NONE && !empty($attributes)): 
                                $attributes = processAttributes($attributes); ?>
                                <div class="section-title">
                                    <i class="fas fa-chart-line"></i> Attributes
                                </div>
                                <ul class="info-list">
                                    <?php foreach ($attributes as $key => $value): 
                                        if (is_array($value)) {
                                            $value = implode(', ', array_filter($value, 'shouldDisplay'));
                                        }
                                        if (shouldDisplay($value)): ?>
                                            <li><strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars($value) ?></li>
                                        <?php endif;
                                    endforeach; ?>
                                </ul>
                            <?php endif;
                        endif;
                        
                        if (shouldDisplay($item['abilities'])):
                            $abilities = json_decode($item['abilities'], true);
                            if (json_last_error() === JSON_ERROR_NONE && !empty($abilities)): 
                                $abilities = processAttributes($abilities); ?>
                                <div class="section-title">
                                    <i class="fas fa-magic"></i> Abilities
                                </div>
                                <ul class="info-list">
                                    <?php foreach ($abilities as $ability): 
                                        if (is_array($ability)) {
                                            $ability = implode(', ', array_filter($ability, 'shouldDisplay'));
                                        }
                                        if (shouldDisplay($ability)): ?>
                                            <li><?= htmlspecialchars($ability) ?></li>
                                        <?php endif;
                                    endforeach; ?>
                                </ul>
                            <?php endif;
                        endif;
                        
                        if (shouldDisplay($item['lore'])): ?>
                            <div class="section-title">
                                <i class="fas fa-book"></i> Lore
                            </div>
                            <div class="info-list" style="font-style: italic;">
                                <?= nl2br(htmlspecialchars($item['lore'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>