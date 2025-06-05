<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include 'db.php';


if (!$pdo) {
    die('<div class="alert alert-danger">Database connection error. Please check db.php configuration</div>');
}

try {
    // SQL query for match statistics
    $sql = "
        SELECT 
            m.match_id,
            EXTRACT(EPOCH FROM m.start_time) AS match_time,
            m.radiant_win,
            (SELECT SUM(p.kills) FROM players p WHERE p.match_id = m.match_id AND p.player_slot < 128) AS radiant_kills,
            (SELECT SUM(p.kills) FROM players p WHERE p.match_id = m.match_id AND p.player_slot >= 128) AS dire_kills
        FROM matches m
        ORDER BY m.start_time DESC
        LIMIT 20
    ";

    // Execute query
    $stmt = $pdo->query($sql);
    
    if (!$stmt) {
        throw new PDOException("Match query execution error");
    }

    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get players and heroes for each match
    foreach ($matches as &$match) {
        // Query players for current match
        $playersStmt = $pdo->prepare("
            SELECT 
                p.id AS player_id,
                COALESCE(p.personaname, 'Anonymous') AS username,
                p.kills,
                p.deaths,
                p.assists,
                p.hero_id,
                COALESCE(h.name, 'Unknown Hero') AS hero_name,
                COALESCE(h.image, '') AS thumbnail_image 
            FROM players p
            LEFT JOIN heroes h ON p.hero_id = h.standard_hero_id
            WHERE p.match_id = :match_id
            ORDER BY p.player_slot
        ");
        
        $playersStmt->execute(['match_id' => $match['match_id']]);
        $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Split players into teams (Radiant < 128, Dire >= 128)
        $radiant_team = array_slice($players, 0, 5);
        $dire_team = array_slice($players, 5, 5);
        $match['players'] = array_merge($radiant_team, $dire_team);

        // Get items for each player
        foreach ($match['players'] as &$player) {
            $itemsStmt = $pdo->prepare("
                SELECT 
                    i.item_id AS id,
                    COALESCE(i.display_name, 'Unknown Item') AS name,
                    COALESCE(i.image_url, '') AS image 
                FROM player_items pi
                LEFT JOIN items i ON pi.item_id = i.item_id
                WHERE pi.player_id = :player_id
                ORDER BY pi.slot_index
            ");
            
            $itemsStmt->execute(['player_id' => $player['player_id']]);
            $player['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($player);
    }
    unset($match);

} catch (PDOException $e) {
    die('<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOTA 2 Match History</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
            position: relative;
        }
        
        .header::after {
            content: '';
            display: block;
            width: 150px;
            height: 4px;
            background: var(--primary-color);
            margin: 1rem auto;
            border-radius: 2px;
        }
        
        .header h1 {
            font-family: 'Oxanium', sans-serif;
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .header p {
            color: var(--text-secondary);
        }
        
        .matches-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
            margin-top: 20px;
        }
        
        .matches-table thead th {
            background-color: var(--secondary-color);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .matches-table tbody tr {
            background: rgba(26, 62, 114, 0.2);
            border: 1px solid rgba(78, 155, 255, 0.1);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }
        
        .matches-table tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
            border-color: rgba(78, 155, 255, 0.3);
            background: rgba(26, 62, 114, 0.3);
        }
        
        .matches-table td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            border: none;
            color: var(--text-color);
        }
        
        .match-id {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .match-time {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .match-result {
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .result-win {
            background-color: rgba(40, 167, 69, 0.2);
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }
        
        .result-loss {
            background-color: rgba(220, 53, 69, 0.2);
            color: #F44336;
            border: 1px solid #F44336;
        }
        
        .team-column {
            border-left: 1px solid rgba(78, 155, 255, 0.1);
            border-right: 1px solid rgba(78, 155, 255, 0.1);
        }
        
        .player-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 5px 0;
            padding: 10px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .player-card:hover {
            background-color: rgba(26, 62, 114, 0.4);
        }
        
        .hero-image-container {
            position: relative;
            width: 60px;
            height: 60px;
            margin-bottom: 5px;
        }
        
        .hero-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .hero-image:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px var(--primary-color);
        }
        
        .player-name {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100px;
        }
        
        .hero-name {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .score-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: var(--primary-color);
            color: var(--dark-color);
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            margin: 0 5px;
        }
        
        .no-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: rgba(233, 236, 239, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 0.7rem;
            border: 3px solid var(--text-secondary);
        }
        
        .player-items {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 5px;
            margin-top: 5px;
        }
        
        .item-container {
            position: relative;
            width: 24px;
            height: 24px;
        }
        
        .item-icon {
            width: 100%;
            height: 100%;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid rgba(228, 161, 1, 0.3);
            transition: var(--transition);
        }
        
        .item-icon:hover {
            transform: scale(1.2);
            border-color: var(--primary-color);
            box-shadow: 0 0 8px var(--primary-color);
        }
        
        .no-item {
            width: 100%;
            height: 100%;
            border-radius: 4px;
            background-color: rgba(233, 236, 239, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 0.5rem;
            border: 1px solid var(--text-secondary);
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: var(--border-radius);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
            color: #ffc107;
        }
        
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            margin-bottom: 20px;
            background-color: var(--primary-color);
            color: var(--dark-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            border: 2px solid transparent;
        }
        
        .back-button:hover {
            background-color: transparent;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        @media (max-width: 1200px) {
            .matches-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Menu</a>
        <div class="header">
            <h1>DOTA 2 Match History</h1>
            <p>Complete statistics of recent matches</p>
        </div>

        <?php if (!empty($matches)): ?>
            <table class="matches-table">
                <thead>
                    <tr>
                        <th>Match ID</th>
                        <th>Time</th>
                        <th>Result</th>
                        <th colspan="5" class="team-column">Radiant</th>
                        <th colspan="5" class="team-column">Dire</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matches as $match): 
                        $result = $match['radiant_win'] ? 'Radiant Victory' : 'Dire Victory';
                        $resultClass = $match['radiant_win'] ? 'result-win' : 'result-loss';
                    ?>
                        <tr>
                            <td>
                                <a href="match_details.php?match_id=<?= $match['match_id'] ?>" class="match-id">
                                    #<?= $match['match_id'] ?>
                                </a>
                            </td>
                            <td class="match-time">
                                <?= date('M d, Y H:i', $match['match_time']) ?>
                            </td>
                            <td>
                                <span class="match-result <?= $resultClass ?>">
                                    <?= $result ?>
                                </span>
                                <div style="margin-top: 5px;">
                                    <span class="score-badge"><?= $match['radiant_kills'] ?? 0 ?></span>
                                    <span>:</span>
                                    <span class="score-badge"><?= $match['dire_kills'] ?? 0 ?></span>
                                </div>
                            </td>
                            
                            <!-- Radiant Team (first 5 players) -->
                            <?php for ($i = 0; $i < 5; $i++): 
                                $player = $match['players'][$i] ?? null;
                            ?>
                                <td class="team-column">
                                    <?php if ($player): ?>
                                        <div class="player-card">
                                            <div class="hero-image-container">
                                                <?php if (!empty($player['thumbnail_image'])): ?>
                                                    <?php 
                                                        
                                                        $imageData = $player['thumbnail_image'];
                                                        // Определяем тип изображения (предполагаем PNG, но можно реализовать детектирование)
                                                        $imageType = 'image/png';
                                                        
                                                       
                                                        if (is_resource($imageData)) {
                                                            $imageData = stream_get_contents($imageData);
                                                        }
                                                        
                                                        // Если данные не пустые
                                                        if (!empty($imageData)) {
                                                            echo '<img src="data:' . $imageType . ';base64,' . base64_encode($imageData) . '" 
                                                                 class="hero-image" 
                                                                 alt="' . htmlspecialchars($player['hero_name']) . '"
                                                                 title="' . htmlspecialchars($player['hero_name']) . '">';
                                                        } else {
                                                            echo '<div class="no-image">No hero</div>';
                                                        }
                                                    ?>
                                                <?php else: ?>
                                                    <div class="no-image">No hero</div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="player-name" title="<?= htmlspecialchars($player['username']) ?>">
                                                <?= htmlspecialchars($player['username']) ?>
                                            </div>
                                            <div class="hero-name">
                                                <?= htmlspecialchars($player['hero_name']) ?>
                                            </div>
                                            
                                            <div class="player-items">
                                                <?php foreach ($player['items'] as $item): ?>
                                                    <div class="item-container">
                                                        <?php if (!empty($item['image'])): 
                                                            $imageUrl = $item['image'];
                                                            if (!preg_match('/^https?:\/\//i', $imageUrl)) {
                                                                $imageUrl = 'https://cdn.cloudflare.steamstatic.com' . $imageUrl;
                                                            }
                                                            ?>
                                                            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                                 class="item-icon"
                                                                 title="<?= htmlspecialchars($item['name']) ?>"
                                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                            <div class="no-item" style="display:none">?</div>
                                                        <?php else: ?>
                                                            <div class="no-item">?</div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            
                            <!-- Dire Team (next 5 players) -->
                            <?php for ($i = 5; $i < 10; $i++): 
                                $player = $match['players'][$i] ?? null;
                            ?>
                                <td class="team-column">
                                    <?php if ($player): ?>
                                        <div class="player-card">
                                            <div class="hero-image-container">
                                                <?php if (!empty($player['thumbnail_image'])): ?>
                                                    <?php 
                                                        $imageData = $player['thumbnail_image'];
                                                        $imageType = 'image/png';
                                                        
                                                        if (is_resource($imageData)) {
                                                            $imageData = stream_get_contents($imageData);
                                                        }
                                                        
                                                        if (!empty($imageData)) {
                                                            echo '<img src="data:' . $imageType . ';base64,' . base64_encode($imageData) . '" 
                                                                 class="hero-image" 
                                                                 alt="' . htmlspecialchars($player['hero_name']) . '"
                                                                 title="' . htmlspecialchars($player['hero_name']) . '">';
                                                        } else {
                                                            echo '<div class="no-image">No hero</div>';
                                                        }
                                                    ?>
                                                <?php else: ?>
                                                    <div class="no-image">No hero</div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="player-name" title="<?= htmlspecialchars($player['username']) ?>">
                                                <?= htmlspecialchars($player['username']) ?>
                                            </div>
                                            <div class="hero-name">
                                                <?= htmlspecialchars($player['hero_name']) ?>
                                            </div>
                                            
                                            <div class="player-items">
                                                <?php foreach ($player['items'] as $item): ?>
                                                    <div class="item-container">
                                                        <?php if (!empty($item['image'])): 
                                                            $imageUrl = $item['image'];
                                                            if (!preg_match('/^https?:\/\//i', $imageUrl)) {
                                                                $imageUrl = 'https://cdn.cloudflare.steamstatic.com' . $imageUrl;
                                                            }
                                                            ?>
                                                            <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                                 class="item-icon"
                                                                 title="<?= htmlspecialchars($item['name']) ?>"
                                                                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                                            <div class="no-item" style="display:none">?</div>
                                                        <?php else: ?>
                                                            <div class="no-item">?</div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">
                No matches found in the database. Please check if the database is properly populated.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
// Close connection
$pdo = null;

?>