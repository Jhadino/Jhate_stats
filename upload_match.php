<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');


function log_message($message) {
    $log_file = __DIR__.'/debug.log';
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($log_file, $timestamp.' '.$message.PHP_EOL, FILE_APPEND);
}

session_start();
log_message('Сессия стартована');

// Подключение к БД
try {
    include 'db.php';
    log_message('Подключение к БД успешно');
} catch (Exception $e) {
    log_message('Ошибка подключения к БД: '.$e->getMessage());
    $_SESSION['error'] = "Database connection failed";
    header("Location: matches.php");
    exit;
}


function normalize_string($value) {
    if (!is_string($value)) return $value;
    $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value);
    if (class_exists('Normalizer')) {
        $value = Normalizer::normalize($value, Normalizer::FORM_KD);
    }
    $value = preg_replace('/[^\x20-\x7E]/', '', $value);
    return trim($value);
}


function validate_boolean($value) {
    if (is_bool($value)) return $value;
    if (is_int($value)) return (bool)$value;
    if (is_string($value)) {
        $value = strtolower(trim($value));
        if ($value === '' || $value === 'false' || $value === '0' || $value === 'no') return false;
        return true;
    }
    return false;
}


function get_hero_id_by_name($pdo, $hero_name) {
    if (empty($hero_name)) {
        error_log("Empty hero name");
        return null;
    }
    
    $normalized_name = normalize_string($hero_name);
    $stmt = $pdo->prepare("SELECT standard_hero_id FROM heroes WHERE name = :hero_name LIMIT 1");
    $stmt->execute([':hero_name' => $normalized_name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        error_log("Hero not found: " . $hero_name);
    }
    
    return $result ? $result['standard_hero_id'] : null;
}


function get_player_info($pdo, $player_id) {
    $stmt = $pdo->prepare("SELECT account_id, personaname FROM players WHERE id = :player_id LIMIT 1");
    $stmt->execute([':player_id' => $player_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function import_match_from_opendota($pdo, $match_id) {
    try {
        $url = "https://api.opendota.com/api/matches/{$match_id}";
        $response = file_get_contents($url);
        
        if ($response === FALSE) {
            throw new Exception("Не удалось получить данные матча");
        }
        
        $match_data = json_decode($response, true);
        
        if (!$match_data || isset($match_data['error'])) {
            throw new Exception("Матч не найден или произошла ошибка API");
        }
        
        $stmt = $pdo->prepare("SELECT standard_hero_id, name FROM heroes");
        $stmt->execute();
        $heroes_map = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $post_data = [
            'match_id' => $match_data['match_id'],
            'duration' => $match_data['duration'],
            'start_time' => date('Y-m-d H:i:s', $match_data['start_time']),
            'radiant_win' => $match_data['radiant_win'],
            'players' => [
                'radiant' => [],
                'dire' => []
            ]
        ];
        
        foreach ($match_data['players'] as $player) {
            $team = ($player['player_slot'] < 128) ? 'radiant' : 'dire';
            $index = $player['player_slot'] % 128;
            
            
            $account_id = $player['account_id'] ?? null;
            $personaname = 'Unknown';
            $player_id = null;
            
            if ($account_id) {
                $stmt = $pdo->prepare("SELECT id, personaname FROM players WHERE account_id = ?");
                $stmt->execute([$account_id]);
                $player_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($player_info) {
                    $player_id = $player_info['id'];
                    $personaname = $player_info['personaname'] ?? $personaname;
                }
            }
            
            $post_data['players'][$team][$index] = [
                'hero_name' => $heroes_map[$player['hero_id']] ?? 'Unknown Hero',
                'standard_hero_id' => $player['hero_id'],
                'player_id' => $player_id,
                'account_id' => $account_id,
                'personaname' => $player['personaname'] ?? $personaname,
                'kills' => $player['kills'],
                'deaths' => $player['deaths'],
                'assists' => $player['assists'],
                'gpm' => $player['gold_per_min'],
                'xpm' => $player['xp_per_min'],
                'hero_damage' => $player['hero_damage'],
                'tower_damage' => $player['tower_damage'],
                'items' => []
            ];
            

            for ($i = 0; $i < 6; $i++) {
                $item_key = 'item_' . $i;
                if (!empty($player[$item_key])) {
                    $post_data['players'][$team][$index]['items'][$i] = $player[$item_key];
                }
            }
        }
        
        return save_manual_match($pdo, $post_data);
        
    } catch (Exception $e) {
        error_log("Ошибка импорта матча: " . $e->getMessage());
        throw $e;
    }
}



function save_manual_match($pdo, $post_data) {
    $pdo->beginTransaction();
    $transactionActive = true;
    
    try {
        // 1. Validate input data
        if (empty($post_data['match_id'])) {
            throw new Exception("Match ID is required");
        }

        // 2. Save basic match info
       $radiant_win = isset($post_data['radiant_win']) ? validate_boolean($post_data['radiant_win']) : false;

$stmt = $pdo->prepare("
    INSERT INTO matches (
        match_id, duration, start_time, radiant_win, league_id, league_name
    ) VALUES (
        :match_id, :duration, :start_time, :radiant_win, :league_id, :league_name
    )
    ON CONFLICT (match_id) DO UPDATE SET
        duration = EXCLUDED.duration,
        start_time = EXCLUDED.start_time,
        radiant_win = EXCLUDED.radiant_win,
        league_id = EXCLUDED.league_id,
        league_name = EXCLUDED.league_name
");

$start_time = !empty($post_data['start_time']) ? date('Y-m-d H:i:s', strtotime($post_data['start_time'])) : date('Y-m-d H:i:s');

$stmt->execute([
    ':match_id' => $post_data['match_id'],
    ':duration' => $post_data['duration'] ?? 0,
    ':start_time' => $start_time,
    ':radiant_win' => $radiant_win ? 'true' : 'false',
    ':league_id' => $post_data['league_id'] ?? null,
    ':league_name' => normalize_string($post_data['league_name'] ?? '')
]);

        // 3. Delete old match data
        $pdo->exec("DELETE FROM players WHERE match_id = " . $pdo->quote($post_data['match_id']));
        $pdo->exec("DELETE FROM player_items WHERE player_id IN (
            SELECT id FROM players WHERE match_id = " . $pdo->quote($post_data['match_id']) . "
        )");

        // 4. Save players
        foreach (['radiant', 'dire'] as $team) {
            if (empty($post_data['players'][$team]) || !is_array($post_data['players'][$team])) {
                continue;
            }

            foreach ($post_data['players'][$team] as $index => $player) {
                if (empty($player['standard_hero_id'])) {
                    throw new Exception("Hero ID is required for $team player " . ($index + 1));
                }

                $slot_offset = ($team === 'dire') ? 128 : 0;
                $hero_id = $player['standard_hero_id'];
                $account_id = $player['account_id'] ?? null;
                $personaname = normalize_string($player['personaname'] ?? 'Unknown');


                $stmt = $pdo->prepare("SELECT 1 FROM heroes WHERE standard_hero_id = ?");
                $stmt->execute([$hero_id]);
                if (!$stmt->fetch()) {
                    error_log("Hero with standard_hero_id {$hero_id} not found in local DB");
                    continue;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO players (
                        match_id, player_slot, hero_id, kills, deaths, assists,
                        gold_per_min, xp_per_min, hero_damage, tower_damage, account_id, personaname
                    ) VALUES (
                        :match_id, :player_slot, :hero_id, :kills, :deaths, :assists,
                        :gold_per_min, :xp_per_min, :hero_damage, :tower_damage, :account_id, :personaname
                    )
                ");
                $stmt->execute([
                    ':match_id' => $post_data['match_id'],
                    ':player_slot' => $index + $slot_offset,
                    ':hero_id' => $hero_id,
                    ':kills' => (int)($player['kills'] ?? 0),
                    ':deaths' => (int)($player['deaths'] ?? 0),
                    ':assists' => (int)($player['assists'] ?? 0),
                    ':gold_per_min' => (int)($player['gpm'] ?? 0),
                    ':xp_per_min' => (int)($player['xpm'] ?? 0),
                    ':hero_damage' => (int)($player['hero_damage'] ?? 0),
                    ':tower_damage' => (int)($player['tower_damage'] ?? 0),
                    ':account_id' => $account_id,
                    ':personaname' => $personaname
                ]);
                
                $player_id = $pdo->lastInsertId();
                
                // Save items
                if (!empty($player['items']) && is_array($player['items'])) {
                    foreach ($player['items'] as $slot => $item_id) {
                        if (!empty($item_id)) {
                            $pdo->prepare("
                                INSERT INTO player_items (player_id, slot_type, slot_index, item_id)
                                VALUES (:player_id, 'main', :slot, :item_id)
                            ")->execute([
                                ':player_id' => $player_id,
                                ':slot' => $slot,
                                ':item_id' => $item_id
                            ]);
                        }
                    }
                }
            }
        }

        $pdo->commit();
        return $post_data['match_id'];
        
    } catch (Exception $e) {
        if ($transactionActive) {
            $pdo->rollBack();
        }
        error_log("Match save error: " . $e->getMessage());
        throw $e;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['import_match_id'])) {
            // Обработка импорта матча по ID
            $match_id = (int)$_POST['import_match_id'];
            log_message("Начало импорта матча ID: $match_id");
            
            if ($match_id <= 0) {
                throw new Exception("Неверный ID матча");
            }
            
            $result = import_match_from_opendota($pdo, $match_id);
            
            if ($result) {
                $_SESSION['success'] = "Матч #{$match_id} успешно импортирован!";
                log_message("Матч #{$match_id} успешно импортирован");
            } else {
                throw new Exception("Не удалось импортировать матч #{$match_id}");
            }
        }
        elseif (isset($_POST['players'])) {
            // Обработка ручного ввода матча
            log_message('Обработка ручного добавления матча');
            $result = save_manual_match($pdo, $_POST);
            
            if ($result) {
                $_SESSION['success'] = "Матч успешно добавлен!";
                log_message("Матч успешно добавлен вручную");
            } else {
                throw new Exception("Ошибка при добавлении матча");
            }
        }
        else {
            throw new Exception("Не получены данные для обработки");
        }
        
        header("Location: matches.php");
        exit;
        
    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        log_message("Ошибка: $error_msg");
        $_SESSION['error'] = $error_msg;
        header("Location: matches.php");
        exit;
    }
}


log_message('Не POST-запрос, перенаправление');
header("Location: matches.php");
exit;