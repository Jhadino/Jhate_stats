<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use DiDom\Document;

$dbConfig = [
    'host'     => 'localhost',
    'dbname'   => 'postgres',
    'user'     => 'Jhate',
    'password' => '2005',
    'port'     => 5432
];

try {
    $dbconn = pg_connect(
        "host={$dbConfig['host']} 
         dbname={$dbConfig['dbname']} 
         user={$dbConfig['user']} 
         password={$dbConfig['password']} 
         port={$dbConfig['port']}"
    ) or die('Не удалось подключиться к БД: ' . pg_last_error());

    $heroId = 1;
    $html = file_get_contents('anti-mage_abilities.html');
    if ($html === false) {
        die('Не удалось загрузить HTML файл');
    }

    // Загрузка JSON файла с описаниями способностей
    $jsonFile = 'abilities.json';
    if (!file_exists($jsonFile)) {
        die("JSON файл с описаниями не найден: $jsonFile");
    }
    
    $jsonData = file_get_contents($jsonFile);
    if ($jsonData === false) {
        die("Не удалось прочитать JSON файл");
    }
    
    $abilitiesData = json_decode($jsonData, true);
    if ($abilitiesData === null) {
        die("Ошибка декодирования JSON: " . json_last_error_msg());
    }

    $document = new Document($html);
    $client = new Client([
        'timeout' => 10,
        'verify' => false
    ]);

    // Ищем все блоки способностей
    $abilityBlocks = $document->find('.ability-block, [class*="ability-"], .tw-bg-dark');
    if (!$abilityBlocks) {
        die('Не найдено блоков способностей');
    }

    $abilitiesCount = 0;

    foreach ($abilityBlocks as $block) {
        try {
            // Название способности
            $nameTag = $block->first('.ability-title, h1, h2, h3, [class*="title"], .x-tooltip-title');
            if (!$nameTag) continue;
            
            $name = trim($nameTag->text());
            
            // Поиск lore в JSON
            $lore = '';
            foreach ($abilitiesData as $key => $ability) {
                if (isset($ability['dname']) && strcasecmp(trim($ability['dname']), $name) === 0) {
                    $lore = isset($ability['lore']) ? trim($ability['lore']) : '';
                    break;
                }
            }

            // Описание способности (из HTML как в оригинале)
            $description = '';
            $descriptionTags = [
                '.ability-description',
                '.tooltip-description',
                '[class*="desc"]',
                '[class*="description"]',
                'ya-tr-span'
            ];
            
            foreach ($descriptionTags as $selector) {
                $descTag = $block->first($selector);
                if ($descTag) {
                    // Проверяем наличие перевода в data-translation
                    if ($descTag->hasAttribute('data-translation')) {
                        $description = trim($descTag->getAttribute('data-translation'));
                        break;
                    }
                    // Если перевода нет, используем обычный текст
                    $text = trim($descTag->text());
                    if (!empty($text)) {
                        $description = $text;
                        break;
                    }
                }
            }

            // Изображение (как в оригинале)
            $imgTag = $block->first('img.ability-icon, img, [class*="icon"]');
            $imageUrl = $imgTag ? $imgTag->attr('src') : '';
            $imageData = null;
            
            if (!empty($imageUrl)) {
                if (strpos($imageUrl, 'http') !== 0) {
                    $imageUrl = 'https://www.dotabuff.com/' . ltrim($imageUrl, '/');
                }

                try {
                    $response = $client->get($imageUrl, ['verify' => false]);
                    if ($response->getStatusCode() === 200) {
                        $imageData = pg_escape_bytea($response->getBody()->getContents());
                    }
                } catch (Exception $e) {
                    error_log("Ошибка загрузки изображения: " . $e->getMessage());
                }
            }

            $essence = [];
            $abilityType = '';
            $details = '';
            
            // Тип способности (Q/W/E/R) (как в оригинале)
            $abilityKeyElement = $block->first('[class*="key"], [class*="letter"], .tw-text-white');
            if ($abilityKeyElement) {
                $details = trim($abilityKeyElement->text());
            }

            // Основной тип способности (Passive/Active) (как в оригинале)
            $abilityTypeText = '';
            $statsContainer = $block->first('.tw-px-4.tw-py-2, .ability-stats');
            if ($statsContainer) {
                foreach ($statsContainer->find('p') as $p) {
                    $text = trim($p->text());
                    if (strpos($text, 'ABILITY:') !== false) {
                        $abilityTypeText = $text;
                        break;
                    }
                }
            }
            
            if ($abilityTypeText && preg_match('/ABILITY:\s*(.+)/i', $abilityTypeText, $matches)) {
                $abilityType = trim($matches[1]);
            }

            // Собираем все характеристики способности (как в оригинале)
            if ($statsContainer) {
                foreach ($statsContainer->find('p, ya-tr-span') as $element) {
                    if ($element->hasAttribute('data-translation')) {
                        $text = trim($element->getAttribute('data-translation'));
                    } else {
                        $text = trim($element->text());
                    }
                    
                    if (!empty($text) && strpos($text, 'ABILITY:') === false) {
                        $essence[] = $text;
                    }
                }
            }

            // Альтернативный поиск характеристик (как в оригинале)
            if (empty($essence)) {
                $altStats = $block->find('div > p, li, span[class*="stat"], ya-tr-span');
                foreach ($altStats as $stat) {
                    if ($stat->hasAttribute('data-translation')) {
                        $text = trim($stat->getAttribute('data-translation'));
                    } else {
                        $text = trim($stat->text());
                    }
                    
                    if (!empty($text) && strpos($text, 'ABILITY:') === false) {
                        $essence[] = $text;
                    }
                }
            }

            // Удаляем дубликаты и пустые элементы
            $essence = array_unique(array_filter($essence));
            $formattedEssence = implode("\n", $essence);

            // Вставка в БД (добавлено поле lore)
            $query = "
                INSERT INTO abilities (hero_id, name, description, image, essence, ability_type, details, lore)
                VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
            ";

            $params = [
                $heroId,
                $name,
                $description,
                $imageData,
                $formattedEssence,
                $abilityType,
                $details,
                $lore
            ];

            $result = pg_query_params($dbconn, $query, $params);
            
            if ($result) {
                $abilitiesCount++;
                echo "=== Успешно сохранена способность ===\n";
                echo "Название: $name\n";
                echo "Описание: " . (!empty($description) ? $description : "(нет описания)") . "\n";
                if (!empty($lore)) {
                    echo "Lore: $lore\n";
                }
                echo "Характеристики:\n" . (!empty($formattedEssence) ? $formattedEssence : "(нет данных)") . "\n";
                echo "Тип: " . (!empty($abilityType) ? $abilityType : "(не указан)") . "\n";
                echo "Клавиша: " . (!empty($details) ? $details : "(не указана)") . "\n\n";
            } else {
                error_log("Ошибка при вставке способности '$name': " . pg_last_error($dbconn));
            }

        } catch (Exception $e) {
            error_log("Ошибка при обработке способности '$name': " . $e->getMessage());
            continue;
        }
    }

    echo "Итог: успешно сохранено $abilitiesCount способностей для героя с ID $heroId\n";
    pg_close($dbconn);

} catch (Exception $e) {
    die("Критическая ошибка: " . $e->getMessage());
}