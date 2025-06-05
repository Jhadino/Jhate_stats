<?php
include 'db.php';

// Get players, heroes, and items
$players = $pdo->query("SELECT MIN(id) as id, account_id, TRIM(personaname) AS personaname 
                        FROM players 
                        WHERE personaname IS NOT NULL 
                        AND personaname != '' 
                        AND personaname != 'unknown'
                        GROUP BY personaname, account_id
                        ORDER BY personaname")->fetchAll(PDO::FETCH_ASSOC);
$heroes = $pdo->query("SELECT id, name, standard_hero_id FROM heroes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->query("SELECT item_id, display_name FROM items ORDER BY display_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Dota 2 Match</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Oxanium:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<div class="match-import-section">
    <h3 class="team-title"><i class="fas fa-cloud-download-alt"></i> Импорт матча по ID</h3>
    <form action="upload_match.php" method="post" id="import-form">
        <div class="form-group">
            <label for="import_match_id">ID матча с OpenDota</label>
            <div class="import-controls">
                <input type="number" id="import_match_id" name="import_match_id" 
                       class="form-control" placeholder="Например: 12345678" min="1" required>
                <button type="submit" class="btn btn-import">
                    <i class="fas fa-download"></i> Импортировать
                </button>
            </div>
        </div>
    </form>
</div>

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
.match-import-section {
    margin-bottom: 30px;
    padding: 20px;
    background: rgba(26, 62, 114, 0.1);
    border-radius: var(--border-radius);
    border-left: 3px solid var(--accent-color);
}

.import-controls {
    display: flex;
    gap: 10px;
}

.btn-import {
    width: auto;
    padding: 10px 20px;
    margin: 0;
    background-color: var(--accent-color);
    color: white;
}

.btn-import:hover {
    background-color: #3a86ff;
}
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem 0;
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

        .match-form {
            background: rgba(26, 62, 114, 0.2);
            border: 1px solid rgba(78, 155, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            margin-top: 20px;
            backdrop-filter: blur(5px);
        }

        .team-section {
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(26, 62, 114, 0.1);
            border-radius: var(--border-radius);
            border-left: 3px solid var(--primary-color);
        }

        .team-title {
            color: var(--primary-color);
            font-family: 'Oxanium', sans-serif;
            margin-bottom: 20px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
        }

        .team-title i {
            margin-right: 10px;
        }

        .player-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(26, 62, 114, 0.2);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .player-row:hover {
            background: rgba(26, 62, 114, 0.3);
        }

        .player-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            color: var(--dark-color);
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }

        .form-group {
            margin-bottom: 15px;
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            background: rgba(26, 62, 114, 0.3);
            border: 1px solid rgba(78, 155, 255, 0.2);
            border-radius: var(--border-radius);
            color: var(--text-color);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(228, 161, 1, 0.2);
        }

        .form-control-select {
            width: 100%;
            padding: 10px 15px;
            background: rgba(26, 62, 114, 0.3);
            border: 1px solid rgba(78, 155, 255, 0.2);
            border-radius: var(--border-radius);
            color: var(--text-color);
            font-size: 0.95rem;
            transition: var(--transition);
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23e4a101'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
        }

        .form-control-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(228, 161, 1, 0.2);
        }

        .stats-row {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .stat-group {
            flex: 1;
            min-width: 120px;
        }

        .items-group {
            flex: 2;
        }

        .match-info {
            margin-top: 30px;
            padding: 20px;
            background: rgba(26, 62, 114, 0.1);
            border-radius: var(--border-radius);
        }

        .match-info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: var(--dark-color);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            width: 100%;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
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

        .search-container {
            position: relative;
        }

        .search-input {
            padding-left: 35px;
            background: rgba(26, 62, 114, 0.3);
            border: 1px solid rgba(78, 155, 255, 0.2);
            color: var(--text-color);
        }

        .search-icon {
            position: absolute;
            left: 10px;
            top: 35px;
            color: var(--text-secondary);
            cursor: pointer;
            z-index: 2;
        }

        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            background: var(--dark-color);
            border: 1px solid rgba(78, 155, 255, 0.2);
            border-radius: var(--border-radius);
            z-index: 100;
            display: none;
            box-shadow: var(--box-shadow);
            margin-top: 5px;
        }

        .search-dropdown.active {
            display: block;
        }

        .search-dropdown-item {
            padding: 10px 15px;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 1px solid rgba(78, 155, 255, 0.1);
            font-size: 0.9rem;
        }

        .search-dropdown-item:hover {
            background: rgba(78, 155, 255, 0.1);
            color: var(--primary-color);
        }

        .search-dropdown-item.selected {
            background: rgba(78, 155, 255, 0.2);
            color: var(--primary-color);
            font-weight: 500;
        }

        .search-dropdown-item.disabled {
            opacity: 0.5;
            pointer-events: none;
            text-decoration: line-through;
            background: rgba(220, 53, 69, 0.1);
        }

        .search-input-container {
            position: relative;
            margin-bottom: 15px;
        }

        .hidden-select {
            display: none;
        }

        /* Стили для предметов */
        .items-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .item-slot {
            margin-bottom: 10px;
        }

        .item-slot-label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }

        .item-select-container {
            position: relative;
        }

        .item-search-input {
            width: 100%;
            padding: 8px 30px 8px 10px;
            background: rgba(26, 62, 114, 0.3);
            border: 1px solid rgba(78, 155, 255, 0.2);
            border-radius: var(--border-radius);
            color: var(--text-color);
            font-size: 0.85rem;
        }

        .item-search-icon {
            position: absolute;
            right: 10px;
            top: 8px;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .item-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: var(--dark-color);
            border: 1px solid rgba(78, 155, 255, 0.2);
            border-radius: var(--border-radius);
            z-index: 100;
            display: none;
            box-shadow: var(--box-shadow);
        }

        .item-dropdown.active {
            display: block;
        }

        .item-dropdown-item {
            padding: 8px 10px;
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 1px solid rgba(78, 155, 255, 0.1);
            font-size: 0.8rem;
        }

        .item-dropdown-item:hover {
            background: rgba(78, 155, 255, 0.1);
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .player-row {
                flex-direction: column;
            }
            
            .stats-row {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .search-dropdown {
                max-height: 200px;
            }
            
            .items-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .search-dropdown, .item-dropdown {
            animation: fadeIn 0.2s ease-out;
        }

        /* Кастомный скроллбар */
        .search-dropdown::-webkit-scrollbar,
        .item-dropdown::-webkit-scrollbar {
            width: 6px;
        }

        .search-dropdown::-webkit-scrollbar-track,
        .item-dropdown::-webkit-scrollbar-track {
            background: rgba(26, 62, 114, 0.2);
            border-radius: 3px;
        }

        .search-dropdown::-webkit-scrollbar-thumb,
        .item-dropdown::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .search-dropdown::-webkit-scrollbar-thumb:hover,
        .item-dropdown::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
.result-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.result-btn {
    flex: 1;
    padding: 10px;
    background: rgba(26, 62, 114, 0.3);
    border: 1px solid rgba(78, 155, 255, 0.2);
    border-radius: var(--border-radius);
    color: var(--text-secondary);
    cursor: pointer;
    transition: var(--transition);
    user-select: none;
}

.result-btn.active {
    background: var(--primary-color);
    color: var(--dark-color) !important; /* Важно для переопределения */
    font-weight: bold;
    border-color: var(--primary-dark);
}
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> Назад</a>
        
        <div class="header">
            <h1>Добавить матч Dota 2</h1>
            <p>Заполните информацию о матче</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form action="upload_match.php" method="post" class="match-form" id="match-form">
            <input type="hidden" name="match_id" value="<?= time() ?>">
            
            <!-- Radiant Team -->
            <div class="team-section">
                <h3 class="team-title"><i class="fas fa-sun"></i> Команда Radiant</h3>
                
                <?php for ($i = 0; $i < 5; $i++): ?>
                <div class="player-row">
                    <div class="player-number"><?= $i + 1 ?></div>
                    
                    <input type="hidden" name="players[radiant][<?= $i ?>][hero_name]" id="hero_name_radiant_<?= $i ?>">
                    <input type="hidden" name="players[radiant][<?= $i ?>][standard_hero_id]" id="standard_hero_id_radiant_<?= $i ?>">
                    <input type="hidden" name="players[radiant][<?= $i ?>][account_id]" id="account_id_radiant_<?= $i ?>">
    <input type="hidden" name="players[radiant][<?= $i ?>][personaname]" id="personaname_radiant_<?= $i ?>">
                    <div class="form-group search-container">
                        <label for="player_search_radiant_<?= $i ?>">Игрок</label>
                        <i class="fas fa-search search-icon"></i>
                        <select id="player_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][player_id]" class="hidden-select">
                            <option value="">Выберите игрока</option>
                            <?php foreach ($players as $player): ?>
                                <option value="<?= $player['id'] ?>" 
                                        data-account-id="<?= $player['account_id'] ?>" 
                                        data-personaname="<?= htmlspecialchars($player['personaname']) ?>">
                                    <?= htmlspecialchars($player['personaname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="search-input-container">
                            <input type="text" 
                                   id="player_search_radiant_<?= $i ?>" 
                                   class="form-control search-input" 
                                   placeholder="Поиск игрока..." 
                                   required
                                   data-target="player_radiant_<?= $i ?>">
                            <div class="search-dropdown"></div>
                            <div class="error-message">Пожалуйста, выберите игрока</div>
                        </div>
                    </div>
                    
                    <div class="form-group search-container">
                        <label for="hero_search_radiant_<?= $i ?>">Hero</label>
                        <i class="fas fa-search search-icon"></i>
                        <select id="hero_radiant_<?= $i ?>" class="hidden-select">
                            <option value="">Select hero</option>
                            <?php foreach ($heroes as $hero): ?>
                                <option value="<?= $hero['id'] ?>" 
                                        data-standard-hero-id="<?= $hero['standard_hero_id'] ?>" 
                                        data-hero-name="<?= htmlspecialchars($hero['name']) ?>">
                                    <?= htmlspecialchars($hero['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="search-input-container">
                            <input type="text" 
                                   id="hero_search_radiant_<?= $i ?>" 
                                   class="form-control search-input" 
                                   placeholder="Search hero..." 
                                   required
                                   data-target="hero_radiant_<?= $i ?>">
                            <div class="search-dropdown"></div>
                            <div class="error-message">Пожалуйста, выберите героя</div>
                        </div>
                    </div>
                    
                    <!-- Items -->
                    <div class="items-group">
                        <label>Items</label>
                        <div class="items-container">
                            <?php for ($slot = 0; $slot < 6; $slot++): ?>
                                <div class="item-slot">
                                    <span class="item-slot-label">Slot <?= $slot + 1 ?></span>
                                    <div class="item-select-container">
                                        <select name="players[radiant][<?= $i ?>][items][<?= $slot ?>]" class="hidden-select">
                                            <option value="">No item</option>
                                            <?php foreach ($items as $item): ?>
                                                <option value="<?= $item['item_id'] ?>"><?= htmlspecialchars($item['display_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" class="item-search-input" placeholder="Search item...">
                                        <i class="fas fa-search item-search-icon"></i>
                                        <div class="item-dropdown"></div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="stats-row">
                        <div class="stat-group">
                            <label for="kills_radiant_<?= $i ?>">Kills</label>
                            <input type="number" id="kills_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][kills]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="deaths_radiant_<?= $i ?>">Deaths</label>
                            <input type="number" id="deaths_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][deaths]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="assists_radiant_<?= $i ?>">Assists</label>
                            <input type="number" id="assists_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][assists]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="gpm_radiant_<?= $i ?>">GPM</label>
                            <input type="number" id="gpm_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][gpm]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="xpm_radiant_<?= $i ?>">XPM</label>
                            <input type="number" id="xpm_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][xpm]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="hero_damage_radiant_<?= $i ?>">Hero Damage</label>
                            <input type="number" id="hero_damage_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][hero_damage]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="tower_damage_radiant_<?= $i ?>">Tower Damage</label>
                            <input type="number" id="tower_damage_radiant_<?= $i ?>" name="players[radiant][<?= $i ?>][tower_damage]" class="form-control" min="0" value="0" required>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Dire Team -->
            <div class="team-section">
                <h3 class="team-title"><i class="fas fa-moon"></i> Команда Dire</h3>
                
                <?php for ($i = 0; $i < 5; $i++): ?>
                <div class="player-row">
                    <div class="player-number"><?= $i + 1 ?></div>
                    
                    <input type="hidden" name="players[dire][<?= $i ?>][hero_name]" id="hero_name_dire_<?= $i ?>">
                    <input type="hidden" name="players[dire][<?= $i ?>][standard_hero_id]" id="standard_hero_id_dire_<?= $i ?>">
                     <input type="hidden" name="players[dire][<?= $i ?>][account_id]" id="account_id_dire_<?= $i ?>">
    <input type="hidden" name="players[dire][<?= $i ?>][personaname]" id="personaname_dire_<?= $i ?>">
                    <div class="form-group search-container">
                        <label for="player_search_dire_<?= $i ?>">Игрок</label>
                        <i class="fas fa-search search-icon"></i>
                        <select id="player_dire_<?= $i ?>" name="players[dire][<?= $i ?>][player_id]" class="hidden-select">
                            <option value="">Выберите игрока</option>
                            <?php foreach ($players as $player): ?>
                                <option value="<?= $player['id'] ?>" 
                                        data-account-id="<?= $player['account_id'] ?>" 
                                        data-personaname="<?= htmlspecialchars($player['personaname']) ?>">
                                    <?= htmlspecialchars($player['personaname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="search-input-container">
                            <input type="text" 
                                   id="player_search_dire_<?= $i ?>" 
                                   class="form-control search-input" 
                                   placeholder="Поиск игрока..." 
                                   required
                                   data-target="player_dire_<?= $i ?>">
                            <div class="search-dropdown"></div>
                            <div class="error-message">Пожалуйста, выберите игрока</div>
                        </div>
                    </div>
                    
                    <div class="form-group search-container">
                        <label for="hero_search_dire_<?= $i ?>">Hero</label>
                        <i class="fas fa-search search-icon"></i>
                        <select id="hero_dire_<?= $i ?>" class="hidden-select">
                            <option value="">Select hero</option>
                            <?php foreach ($heroes as $hero): ?>
                                <option value="<?= $hero['id'] ?>" 
                                        data-standard-hero-id="<?= $hero['standard_hero_id'] ?>" 
                                        data-hero-name="<?= htmlspecialchars($hero['name']) ?>">
                                    <?= htmlspecialchars($hero['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="search-input-container">
                            <input type="text" 
                                   id="hero_search_dire_<?= $i ?>" 
                                   class="form-control search-input" 
                                   placeholder="Search hero..." 
                                   required
                                   data-target="hero_dire_<?= $i ?>">
                            <div class="search-dropdown"></div>
                            <div class="error-message">Пожалуйста, выберите героя</div>
                        </div>
                    </div>
                    
                    <!-- Items -->
                    <div class="items-group">
                        <label>Items</label>
                        <div class="items-container">
                            <?php for ($slot = 0; $slot < 6; $slot++): ?>
                                <div class="item-slot">
                                    <span class="item-slot-label">Slot <?= $slot + 1 ?></span>
                                    <div class="item-select-container">
                                        <select name="players[dire][<?= $i ?>][items][<?= $slot ?>]" class="hidden-select">
                                            <option value="">No item</option>
                                            <?php foreach ($items as $item): ?>
                                                <option value="<?= $item['item_id'] ?>"><?= htmlspecialchars($item['display_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" class="item-search-input" placeholder="Search item...">
                                        <i class="fas fa-search item-search-icon"></i>
                                        <div class="item-dropdown"></div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="stats-row">
                        <div class="stat-group">
                            <label for="kills_dire_<?= $i ?>">Kills</label>
                            <input type="number" id="kills_dire_<?= $i ?>" name="players[dire][<?= $i ?>][kills]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="deaths_dire_<?= $i ?>">Deaths</label>
                            <input type="number" id="deaths_dire_<?= $i ?>" name="players[dire][<?= $i ?>][deaths]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="assists_dire_<?= $i ?>">Assists</label>
                            <input type="number" id="assists_dire_<?= $i ?>" name="players[dire][<?= $i ?>][assists]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="gpm_dire_<?= $i ?>">GPM</label>
                            <input type="number" id="gpm_dire_<?= $i ?>" name="players[dire][<?= $i ?>][gpm]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="xpm_dire_<?= $i ?>">XPM</label>
                            <input type="number" id="xpm_dire_<?= $i ?>" name="players[dire][<?= $i ?>][xpm]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="hero_damage_dire_<?= $i ?>">Hero Damage</label>
                            <input type="number" id="hero_damage_dire_<?= $i ?>" name="players[dire][<?= $i ?>][hero_damage]" class="form-control" min="0" value="0" required>
                        </div>
                        
                        <div class="stat-group">
                            <label for="tower_damage_dire_<?= $i ?>">Tower Damage</label>
                            <input type="number" id="tower_damage_dire_<?= $i ?>" name="players[dire][<?= $i ?>][tower_damage]" class="form-control" min="0" value="0" required>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Match info -->
            <div class="match-info">
                <h3 class="team-title"><i class="fas fa-info-circle"></i> Информация о матче</h3>
                
                <div class="match-info-row">
                    <div class="form-group">
                        <label>Результат</label>
                        <input type="hidden" id="radiant_win" name="radiant_win" value="true">
                        <div class="result-buttons">
                            <button type="button" class="result-btn active" data-value="true">Победа Radiant</button>
                            <button type="button" class="result-btn" data-value="false">Победа Dire</button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="match_duration">Длительность (минуты)</label>
                        <input type="number" id="match_duration" name="duration" class="form-control" min="0" value="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="match_time">Время начала</label>
                        <input type="datetime-local" id="match_time" name="start_time" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn"><i class="fas fa-plus-circle"></i> Добавить матч</button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    // Player and hero search functionality
    function setupSearch(container) {
    const select = container.querySelector('select.hidden-select');
    const searchInput = container.querySelector('.search-input');
    const dropdown = container.querySelector('.search-dropdown');
    const errorMessage = container.querySelector('.error-message');
    const searchIcon = container.querySelector('.search-icon');
    const isHeroSearch = select.id.includes('hero_');
    
    // Храним уже добавленные никнеймы
    const addedNicknames = new Set();
    
    function populateDropdown() {
        dropdown.innerHTML = '';
        addedNicknames.clear(); // Очищаем при каждом новом поиске
        const filter = searchInput.value.toLowerCase();
        
        Array.from(select.options).forEach(option => {
            // Пропускаем пустые варианты и варианты с "unknown"
            if (option.value === '' || option.text.toLowerCase().includes('unknown')) return;
            
            // Проверяем на дубликаты
            const nickname = option.text.trim().toLowerCase();
            if (addedNicknames.has(nickname)) return;
            
            if (filter && !option.text.toLowerCase().includes(filter)) return;
            
            const item = document.createElement('div');
            item.className = 'search-dropdown-item';
            item.textContent = option.text;
            item.dataset.value = option.value;
            
            if (isHeroSearch) {
                item.dataset.standardHeroId = option.dataset.standardHeroId;
                item.dataset.heroName = option.dataset.heroName;
            } else {
                item.dataset.accountId = option.dataset.accountId;
                item.dataset.personaname = option.dataset.personaname;
            }
            
            item.addEventListener('click', function() {
                select.value = option.value;
                searchInput.value = option.text.trim();
                
                if (isHeroSearch) {
                    const playerIndex = select.id.split('_')[2];
                    const team = select.id.includes('radiant') ? 'radiant' : 'dire';
                    
                    document.getElementById(`hero_name_${team}_${playerIndex}`).value = option.dataset.heroName;
                    document.getElementById(`standard_hero_id_${team}_${playerIndex}`).value = option.dataset.standardHeroId;
                } else {
                    const playerIndex = select.id.split('_')[2];
                    const team = select.id.includes('radiant') ? 'radiant' : 'dire';
                    
                    document.getElementById(`account_id_${team}_${playerIndex}`).value = option.dataset.accountId;
                    document.getElementById(`personaname_${team}_${playerIndex}`).value = option.dataset.personaname;
                }
                
                dropdown.classList.remove('active');
                errorMessage.style.display = 'none';
                searchInput.style.borderColor = '';
            });
            
            dropdown.appendChild(item);
            addedNicknames.add(nickname); // Добавляем никнейм в Set
        });
    }
        
        searchInput.addEventListener('input', function() {
            populateDropdown();
            dropdown.classList.add('active');
        });
        
        searchInput.addEventListener('focus', function() {
            populateDropdown();
            dropdown.classList.add('active');
        });
        
        searchIcon.addEventListener('click', () => searchInput.focus());
        
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        // Validation
        searchInput.addEventListener('blur', function() {
            if (!select.value) {
                errorMessage.style.display = 'block';
                searchInput.style.borderColor = 'var(--danger-color)';
            }
        });
    }
    
    // Item search functionality
    function setupItemSearch(container) {
        const select = container.querySelector('select.hidden-select');
        const searchInput = container.querySelector('.item-search-input');
        const dropdown = container.querySelector('.item-dropdown');
        const searchIcon = container.querySelector('.item-search-icon');
        
        function populateItemDropdown() {
            dropdown.innerHTML = '';
            const filter = searchInput.value.toLowerCase();
            
            Array.from(select.options).forEach(option => {
                if (option.value === '') return;
                if (filter && !option.text.toLowerCase().includes(filter)) return;
                
                const item = document.createElement('div');
                item.className = 'item-dropdown-item';
                item.textContent = option.text;
                item.dataset.value = option.value;
                
                item.addEventListener('click', function() {
                    select.value = option.value;
                    searchInput.value = option.text;
                    dropdown.classList.remove('active');
                });
                
                dropdown.appendChild(item);
            });
        }
        
        searchInput.addEventListener('input', function() {
            populateItemDropdown();
            dropdown.classList.add('active');
        });
        
        searchInput.addEventListener('focus', function() {
            populateItemDropdown();
            dropdown.classList.add('active');
        });
        
        searchIcon.addEventListener('click', () => searchInput.focus());
        
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }
    
    // Initialize all search fields
    document.querySelectorAll('.search-container').forEach(container => {
        setupSearch(container);
    });
    
    document.querySelectorAll('.item-select-container').forEach(container => {
        setupItemSearch(container);
    });
    
    // Result buttons
    document.querySelectorAll('.result-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.result-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('radiant_win').value = this.dataset.value;
        });
    });
    
    // Set default datetime
    const now = new Date();
    const timeString = now.toISOString().slice(0, 16);
    document.getElementById('match_time').value = timeString;
    
    // Form validation
    document.getElementById('match-form').addEventListener('submit', function(e) {
        let isValid = true;
        const errorMessages = [];
        
        // Проверка выбора героев
        document.querySelectorAll('[id^="hero_search_"]').forEach(input => {
            const selectId = input.dataset.target;
            const select = document.getElementById(selectId);
            
            if (!select.value) {
                const team = selectId.includes('radiant') ? 'Radiant' : 'Dire';
                const playerNum = selectId.match(/\d+/)[0];
                isValid = false;
                errorMessages.push(`Please select a hero for ${team} player ${playerNum}`);
                
                // Показать ошибку
                const container = input.closest('.search-container');
                container.querySelector('.error-message').style.display = 'block';
                input.style.borderColor = 'var(--danger-color)';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n' + errorMessages.join('\n'));
        }
    });
    

    

});
    </script>
</body>
</html>