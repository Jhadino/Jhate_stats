<?php
require_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$match_id = isset($_GET['match_id']) ? (int)$_GET['match_id'] : 0;

function getMatchData($pdo, $match_id) {

    $stmt = $pdo->prepare("
        SELECT 
            m.match_id as id,
            m.start_time as date,
            m.radiant_win,
            m.duration,
            m.league_id,
            m.league_name
        FROM matches m
        WHERE m.match_id = ?
    ");
    $stmt->execute([$match_id]);
    $matchData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$matchData) return null;


    $stmt = $pdo->prepare("
        SELECT 
            p.id as player_id,
            COALESCE(p.personaname, 'Anonymous') as username,
            p.kills,
            p.deaths,
            p.assists,
            p.hero_id,
            p.gold_per_min,
            p.xp_per_min,
            p.hero_damage,
            p.tower_damage,
            p.player_slot,
            COALESCE(h.name, 'Unknown Hero') as hero_name,
            h.image as hero_image,
            h.primary_attribute,
            h.attack_type
        FROM players p
        LEFT JOIN heroes h ON p.hero_id = h.standard_hero_id
        WHERE p.match_id = ?
        ORDER BY p.player_slot
    ");
    $stmt->execute([$match_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Split players into teams (Radiant < 128, Dire >= 128)
    $radiant_players = array_filter($players, function($p) {
        return $p['player_slot'] < 128;
    });
    $dire_players = array_filter($players, function($p) {
        return $p['player_slot'] >= 128;
    });

    // Get items for all players
    $items = [];
    if (!empty($players)) {
        $playerIds = array_column($players, 'player_id');
        $placeholders = implode(',', array_fill(0, count($playerIds), '?'));
        
        $stmt = $pdo->prepare("
            SELECT 
                pi.player_id,
                pi.slot_index as slot,
                i.item_id as id,
                i.display_name as name,
                i.image_url as image
            FROM player_items pi
            JOIN items i ON pi.item_id = i.item_id
            WHERE pi.player_id IN ($placeholders)
            ORDER BY pi.player_id, pi.slot_index
        ");
        $stmt->execute($playerIds);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get picks and bans
    $stmt = $pdo->prepare("
        SELECT 
            pb.is_pick,
            pb.hero_id,
            pb.team,
            h.name as hero_name,
            h.image as hero_image
        FROM picks_bans pb
        LEFT JOIN heroes h ON pb.hero_id = h.standard_hero_id
        WHERE pb.match_id = ?
        ORDER BY pb.id
    ");
    $stmt->execute([$match_id]);
    $picks_bans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data structure
    $matchData['radiant_players'] = [];
    $matchData['dire_players'] = [];
    $matchData['picks_bans'] = $picks_bans;

    // Process Radiant players
    foreach ($radiant_players as $player) {
        $playerItems = array_filter($items, function($i) use ($player) {
            return $i['player_id'] == $player['player_id'];
        });

        $matchData['radiant_players'][] = [
            'player_id' => $player['player_id'],
            'username' => $player['username'],
            'kills' => $player['kills'],
            'deaths' => $player['deaths'],
            'assists' => $player['assists'],
            'gold_per_min' => $player['gold_per_min'],
            'xp_per_min' => $player['xp_per_min'],
            'hero_damage' => $player['hero_damage'],
            'tower_damage' => $player['tower_damage'],
            'hero_id' => $player['hero_id'],
            'hero_name' => $player['hero_name'],
            'hero_image' => $player['hero_image'],
            'primary_attribute' => $player['primary_attribute'],
            'attack_type' => $player['attack_type'],
            'items' => $playerItems
        ];
    }

    // Process Dire players
    foreach ($dire_players as $player) {
        $playerItems = array_filter($items, function($i) use ($player) {
            return $i['player_id'] == $player['player_id'];
        });

        $matchData['dire_players'][] = [
            'player_id' => $player['player_id'],
            'username' => $player['username'],
            'kills' => $player['kills'],
            'deaths' => $player['deaths'],
            'assists' => $player['assists'],
            'gold_per_min' => $player['gold_per_min'],
            'xp_per_min' => $player['xp_per_min'],
            'hero_damage' => $player['hero_damage'],
            'tower_damage' => $player['tower_damage'],
            'hero_id' => $player['hero_id'],
            'hero_name' => $player['hero_name'],
            'hero_image' => $player['hero_image'],
            'primary_attribute' => $player['primary_attribute'],
            'attack_type' => $player['attack_type'],
            'items' => $playerItems
        ];
    }

    return $matchData;
}

function calculateKDA($kills, $deaths, $assists) {
    if ($deaths == 0) return $kills + $assists;
    return round(($kills + $assists) / $deaths, 2);
}

$matchData = $match_id ? getMatchData($pdo, $match_id) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Details</title>
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
        
        .match-details {
            background: rgba(26, 62, 114, 0.2);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .match-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(78, 155, 255, 0.2);
        }
        
        .match-id {
            font-weight: 700;
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .match-time {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .match-result {
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 1.1rem;
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
        
        .teams-container {
            display: flex;
            gap: 20px;
            margin-top: 2rem;
        }
        
        .team {
            flex: 1;
            background: rgba(26, 62, 114, 0.3);
            border-radius: var(--border-radius);
            padding: 15px;
        }
        
        .team-radiant {
            border-top: 3px solid #4CAF50;
        }
        
        .team-dire {
            border-top: 3px solid #F44336;
        }
        
        .team-header {
            font-family: 'Oxanium', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .team-radiant .team-header {
            color: #4CAF50;
        }
        
        .team-dire .team-header {
            color: #F44336;
        }
        
        .players-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .player-card {
            display: flex;
            background: rgba(26, 62, 114, 0.4);
            border-radius: var(--border-radius);
            padding: 15px;
            transition: var(--transition);
        }
        
        .player-card:hover {
            background: rgba(78, 155, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .hero-info {
            display: flex;
            flex: 1;
            gap: 15px;
        }
        
        .hero-image-container {
            position: relative;
            width: 80px;
            height: 80px;
            flex-shrink: 0;
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
        
        .player-details {
            flex: 1;
        }
        
        .player-name {
            font-weight: 500;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .hero-name {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }
        
        .player-kda {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100px;
            padding: 0 15px;
            border-left: 1px solid rgba(78, 155, 255, 0.2);
            border-right: 1px solid rgba(78, 155, 255, 0.2);
        }
        
        .kda-score {
            font-family: 'Oxanium', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .kda-stats {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .items-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            width: 180px;
            align-content: center;
        }
        
        .item-container {
            position: relative;
            width: 40px;
            height: 40px;
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
            font-size: 0.8rem;
            border: 1px solid var(--text-secondary);
        }
        
        .picks-bans-section {
            background: rgba(26, 62, 114, 0.2);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }
        
        .picks-bans-header {
            font-family: 'Oxanium', sans-serif;
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .picks-bans-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        
        .picks-bans-team {
            flex: 1;
        }
        
        .picks-bans-title {
            text-align: center;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .picks-bans-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        
        .hero-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid;
            transition: var(--transition);
        }
        
        .pick-icon {
            border-color: #4CAF50;
        }
        
        .ban-icon {
            border-color: #F44336;
            opacity: 0.7;
        }
        
        .hero-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 0 10px currentColor;
        }
        
        .additional-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .stat-item {
            background: rgba(26, 62, 114, 0.3);
            padding: 5px 10px;
            border-radius: 4px;
        }
        
        .attribute-icon {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 3px;
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            padding: 15px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .teams-container {
                flex-direction: column;
            }
            
            .player-card {
                flex-direction: column;
            }
            
            .hero-info {
                margin-bottom: 15px;
            }
            
            .player-kda {
                flex-direction: row;
                width: auto;
                justify-content: space-between;
                padding: 10px 0;
                border-left: none;
                border-right: none;
                border-top: 1px solid rgba(78, 155, 255, 0.2);
                border-bottom: 1px solid rgba(78, 155, 255, 0.2);
                margin-bottom: 15px;
            }
            
            .items-container {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="matches.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Matches
        </a>
        
        <div class="header">
            <h1>Match Details</h1>
            <p>Detailed statistics and player performance</p>
        </div>
        
        <?php if ($matchData): ?>
        <div class="match-details">
            <!-- Match info -->
            <div class="match-info">
                <div>
                    <span class="match-id">Match #<?= htmlspecialchars($matchData['id']) ?></span>
                    <span class="match-time">
                        <?= date('M d, Y H:i', strtotime($matchData['date'])) ?>
                        <?php if ($matchData['duration']): ?>
                            • Duration: <?= gmdate('H:i:s', $matchData['duration']) ?>
                        <?php endif; ?>
                        <?php if ($matchData['league_name']): ?>
                            • <?= htmlspecialchars($matchData['league_name']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="match-result <?= $matchData['radiant_win'] ? 'result-win' : 'result-loss' ?>">
                    <?= $matchData['radiant_win'] ? 'Radiant Victory' : 'Dire Victory' ?>
                </div>
            </div>


            <?php if (!empty($matchData['picks_bans'])): ?>
            <div class="picks-bans-section">
                <div class="picks-bans-header">Picks & Bans</div>
                <div class="picks-bans-container">
                    <div class="picks-bans-team">
                        <div class="picks-bans-title">Radiant</div>
                        <div class="picks-bans-list">
                            <?php foreach ($matchData['picks_bans'] as $pb): ?>
                                <?php if ($pb['team'] == 0): ?>
                                    <div class="hero-image-container">
                                        <?php if (!empty($pb['hero_image'])): ?>
                                            <?php 
                                                $imageData = $pb['hero_image'];
                                                $imageType = 'image/png';
                                                
                                                if (is_resource($imageData)) {
                                                    $imageData = stream_get_contents($imageData);
                                                }
                                                
                                                if (!empty($imageData)) {
                                                    echo '<img src="data:' . $imageType . ';base64,' . base64_encode($imageData) . '" 
                                                         class="hero-icon ' . ($pb['is_pick'] ? 'pick-icon' : 'ban-icon') . '"
                                                         alt="' . htmlspecialchars($pb['hero_name']) . '"
                                                         title="' . htmlspecialchars($pb['hero_name']) . ' (' . ($pb['is_pick'] ? 'Pick' : 'Ban') . ')">';
                                                } else {
                                                    echo '<div class="no-image">No hero</div>';
                                                }
                                            ?>
                                        <?php else: ?>
                                            <div class="no-image">No hero</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="picks-bans-team">
                        <div class="picks-bans-title">Dire</div>
                        <div class="picks-bans-list">
                            <?php foreach ($matchData['picks_bans'] as $pb): ?>
                                <?php if ($pb['team'] == 1): ?>
                                    <div class="hero-image-container">
                                        <?php if (!empty($pb['hero_image'])): ?>
                                            <?php 
                                                $imageData = $pb['hero_image'];
                                                $imageType = 'image/png';
                                                
                                                if (is_resource($imageData)) {
                                                    $imageData = stream_get_contents($imageData);
                                                }
                                                
                                                if (!empty($imageData)) {
                                                    echo '<img src="data:' . $imageType . ';base64,' . base64_encode($imageData) . '" 
                                                         class="hero-icon ' . ($pb['is_pick'] ? 'pick-icon' : 'ban-icon') . '"
                                                         alt="' . htmlspecialchars($pb['hero_name']) . '"
                                                         title="' . htmlspecialchars($pb['hero_name']) . ' (' . ($pb['is_pick'] ? 'Pick' : 'Ban') . ')">';
                                                } else {
                                                    echo '<div class="no-image">No hero</div>';
                                                }
                                            ?>
                                        <?php else: ?>
                                            <div class="no-image">No hero</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            

            <div class="teams-container">

                <div class="team team-radiant">
                    <div class="team-header">Radiant Team</div>
                    <div class="players-list">
                        <?php foreach ($matchData['radiant_players'] as $player): ?>
                        <div class="player-card">
                            <!-- Hero info -->
                            <div class="hero-info">
                                <div class="hero-image-container">
                                    <?php if (!empty($player['hero_image'])): ?>
                                        <?php 
                                            $imageData = $player['hero_image'];
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
                                <div class="player-details">
                                    <div class="player-name"><?= htmlspecialchars($player['username']) ?></div>
                                    <div class="hero-name"><?= htmlspecialchars($player['hero_name']) ?></div>
                                    <div class="additional-stats">
                                        <div class="stat-item">
                                            <?php if ($player['primary_attribute']): ?>
                                                <img src="images/<?= strtolower($player['primary_attribute']) ?>_icon.png" 
                                                     class="attribute-icon"
                                                     alt="<?= $player['primary_attribute'] ?>">
                                            <?php endif; ?>
                                            <?= $player['attack_type'] ?? 'Melee' ?>
                                        </div>
                                        <div class="stat-item">GPM: <?= $player['gold_per_min'] ?></div>
                                        <div class="stat-item">XPM: <?= $player['xp_per_min'] ?></div>
                                        <div class="stat-item">Hero DMG: <?= number_format($player['hero_damage']) ?></div>
                                        <div class="stat-item">Tower DMG: <?= number_format($player['tower_damage']) ?></div>
                                    </div>
                                </div>
                            </div>
                            

                            <div class="player-kda">
                                <div class="kda-score"><?= calculateKDA($player['kills'], $player['deaths'], $player['assists']) ?></div>
                                <div class="kda-stats"><?= $player['kills'] ?>/<?= $player['deaths'] ?>/<?= $player['assists'] ?></div>
                            </div>
                            

                            <div class="items-container">
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
                        <?php endforeach; ?>
                    </div>
                </div>
                

                <div class="team team-dire">
                    <div class="team-header">Dire Team</div>
                    <div class="players-list">
                        <?php foreach ($matchData['dire_players'] as $player): ?>
                        <div class="player-card">
                            <div class="hero-info">
                                <div class="hero-image-container">
                                    <?php if (!empty($player['hero_image'])): ?>
                                        <?php 
                                            $imageData = $player['hero_image'];
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
                                <div class="player-details">
                                    <div class="player-name"><?= htmlspecialchars($player['username']) ?></div>
                                    <div class="hero-name"><?= htmlspecialchars($player['hero_name']) ?></div>
                                    <div class="additional-stats">
                                        <div class="stat-item">
                                            <?php if ($player['primary_attribute']): ?>
                                                <img src="images/<?= strtolower($player['primary_attribute']) ?>_icon.png" 
                                                     class="attribute-icon"
                                                     alt="<?= $player['primary_attribute'] ?>">
                                            <?php endif; ?>
                                            <?= $player['attack_type'] ?? 'Melee' ?>
                                        </div>
                                        <div class="stat-item">GPM: <?= $player['gold_per_min'] ?></div>
                                        <div class="stat-item">XPM: <?= $player['xp_per_min'] ?></div>
                                        <div class="stat-item">Hero DMG: <?= number_format($player['hero_damage']) ?></div>
                                        <div class="stat-item">Tower DMG: <?= number_format($player['tower_damage']) ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="player-kda">
                                <div class="kda-score"><?= calculateKDA($player['kills'], $player['deaths'], $player['assists']) ?></div>
                                <div class="kda-stats"><?= $player['kills'] ?>/<?= $player['deaths'] ?>/<?= $player['assists'] ?></div>
                            </div>
                            
                            <div class="items-container">
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
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="error-message">
            <?php if ($match_id): ?>
                Match not found or error loading match data.
            <?php else: ?>
                Please specify match ID in URL parameter (match_id).
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>