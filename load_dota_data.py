import requests
import psycopg2
from datetime import datetime, timezone
import sys
import json
import re
import logging
import unicodedata

# Настройка логирования
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('dota_loader.log'),
        logging.StreamHandler()
    ]
)

# Конфигурация подключения к БД
DB_CONFIG = {
    "dbname": "postgres",
    "user": "Jhate",
    "password": "2005",
    "host": "localhost",
    "port": "5432"
}

OPENDOTA_URL = "https://api.opendota.com/api/matches/"


def check_db_connection():
    try:
        conn = psycopg2.connect(**DB_CONFIG)
        conn.close()
        return True
    except Exception as e:
        logging.error(f"Database connection failed: {str(e)}")
        return False


def normalize_string(value):
    """Нормализация строк с удалением проблемных символов"""
    if not isinstance(value, str):
        return value
        
    try:
        value = re.sub(r'[\x00-\x1F\x7F-\x9F]', '', value)
        value = unicodedata.normalize('NFKD', value)
        value = value.encode('ascii', 'ignore').decode('ascii')
        return value.strip()
    except Exception as e:
        logging.error(f"Ошибка нормализации строки: {str(e)}")
        return ""

def fetch_match_data(match_id):
    """Получение данных матча с OpenDota API"""
    try:
        logging.info(f"Запрашиваю данные для матча {match_id}...")
        response = requests.get(f"{OPENDOTA_URL}{match_id}", timeout=30)
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        logging.error(f"Ошибка API: {str(e)}")
        return None

def save_player_items(cursor, player_id, player_data):
    """Сохраняет предметы игрока в БД"""
    try:
        for slot in range(6):
            item_id = player_data.get(f"item_{slot}", 0)
            if item_id > 0:
                cursor.execute("""
                    INSERT INTO player_items (player_id, slot_type, slot_index, item_id)
                    VALUES (%s, 'main', %s, %s)
                    ON CONFLICT DO NOTHING;
                """, (player_id, slot, item_id))
        
        for slot in range(3):
            item_id = player_data.get(f"backpack_{slot}", 0)
            if item_id > 0:
                cursor.execute("""
                    INSERT INTO player_items (player_id, slot_type, slot_index, item_id)
                    VALUES (%s, 'backpack', %s, %s)
                    ON CONFLICT DO NOTHING;
                """, (player_id, slot, item_id))
        
        neutral_id = player_data.get("item_neutral", 0)
        if neutral_id > 0:
            cursor.execute("""
                INSERT INTO player_items (player_id, slot_type, slot_index, item_id)
                VALUES (%s, 'neutral', 0, %s)
                ON CONFLICT DO NOTHING;
            """, (player_id, neutral_id))
    except Exception as e:
        logging.error(f"Ошибка сохранения предметов: {str(e)}")

def save_to_db(match_data):
    if not match_data:
        return False
        
    conn = None
    try:
        conn = psycopg2.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        league_data = match_data.get("league", {})
        league_name = normalize_string(league_data.get("name")) if league_data else None
        
        start_time = None
        if match_data.get("start_time"):
            dt = datetime.fromtimestamp(match_data["start_time"], tz=timezone.utc)
            start_time = dt.replace(tzinfo=None)
        
        cursor.execute("""
            INSERT INTO matches (match_id, duration, start_time, radiant_win, league_id, league_name)
            VALUES (%s, %s, %s, %s, %s, %s)
            ON CONFLICT (match_id) DO NOTHING;
        """, (
            match_data["match_id"],
            match_data.get("duration", 0),
            start_time,
            match_data.get("radiant_win", False),
            league_data.get("leagueid") if league_data else None,
            league_name
        ))
        
        for player in match_data.get("players", []):
            personaname = normalize_string(player.get("personaname"))
            account_id = player.get("account_id")
            
            if account_id and not isinstance(account_id, int):
                try:
                    account_id = int(account_id)
                except (ValueError, TypeError):
                    account_id = None
            
            cursor.execute("""
                INSERT INTO players (
                    match_id, player_slot, hero_id, kills, deaths, assists,
                    gold_per_min, xp_per_min, hero_damage, tower_damage, account_id, personaname
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                RETURNING id;
            """, (
                match_data["match_id"],
                player.get("player_slot", 0),
                player.get("hero_id", 0),
                player.get("kills", 0),
                player.get("deaths", 0),
                player.get("assists", 0),
                player.get("gold_per_min", 0),
                player.get("xp_per_min", 0),
                player.get("hero_damage", 0),
                player.get("tower_damage", 0),
                account_id,
                personaname
            ))
            
            player_id = cursor.fetchone()[0]
            save_player_items(cursor, player_id, player)
        
        for pick_ban in match_data.get("picks_bans", []):
            cursor.execute("""
                INSERT INTO picks_bans (match_id, is_pick, hero_id, team)
                VALUES (%s, %s, %s, %s)
                ON CONFLICT DO NOTHING;
            """, (
                match_data["match_id"],
                pick_ban.get("is_pick", False),
                pick_ban.get("hero_id", 0),
                pick_ban.get("team", 0)
            ))
        
        conn.commit()
        logging.info(f"Матч {match_data['match_id']} успешно сохранен!")
        return True
        
    except Exception as e:
        logging.error(f"Ошибка при сохранении: {str(e)}", exc_info=True)
        if conn:
            conn.rollback()
        return False
    finally:
        if conn:
            conn.close()

def import_single_match(match_id):
    """Функция для импорта одного матча по ID"""
    if not check_db_connection():
        return False
    
    match_data = fetch_match_data(match_id)
    if not match_data:
        return False
        
    return save_to_db(match_data)

if __name__ == "__main__":
    if len(sys.argv) > 1:
        match_id = sys.argv[1]
        logging.info(f"Импорт матча {match_id}")
        import_single_match(match_id)
    else:
        logging.error("Не указан ID матча для импорта")
