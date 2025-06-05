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
        
        // Получаем маппинг hero_id -> name из БД
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
            
            // Получаем информацию об игроке из нашей БД по account_id
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
                'player_id' => $player_id, // Добавляем ID игрока из нашей БД
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
            
            // Добавляем предметы (6 слотов инвентаря)
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