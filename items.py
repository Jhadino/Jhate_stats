import psycopg2
import json
from psycopg2 import sql

def import_items(json_file_path, db_credentials):
    """Импортирует предметы Dota 2 из JSON в PostgreSQL с обработкой различных форматов данных"""
    
    # Подключение к БД
    conn = psycopg2.connect(**db_credentials)
    cur = conn.cursor()
    
    # Создаем таблицу с более гибкой структурой
    cur.execute("""
        CREATE TABLE IF NOT EXISTS items (
            id SERIAL PRIMARY KEY,
            item_key VARCHAR(100) UNIQUE NOT NULL,
            item_id INTEGER,
            display_name VARCHAR(100) NOT NULL,
            qual VARCHAR(50),
            cost INTEGER DEFAULT 0,
            behavior VARCHAR(255),
            damage_type VARCHAR(50),
            cooldown INTEGER,
            mana_cost VARCHAR(20),  -- Changed to VARCHAR to handle both boolean and numeric
            health_cost VARCHAR(20), -- Changed to VARCHAR to handle both boolean and numeric
            lore TEXT,
            image_url VARCHAR(255),
            components JSONB DEFAULT '[]'::JSONB,
            attributes JSONB DEFAULT '[]'::JSONB,
            abilities JSONB DEFAULT '[]'::JSONB,
            notes TEXT,
            created BOOLEAN DEFAULT FALSE,
            charges BOOLEAN DEFAULT FALSE
        )
    """)
    
    # Загрузка JSON
    with open(json_file_path, 'r', encoding='utf-8') as f:
        try:
            items_data = json.load(f)
        except json.JSONDecodeError as e:
            print(f"Ошибка чтения JSON: {e}")
            return False
    
    # Счетчики для статистики
    total = 0
    success = 0
    skipped = 0
    
    # Функция для преобразования значений mana_cost и health_cost
    def parse_cost_value(value):
        if isinstance(value, bool):
            return 'true' if value else 'false'
        elif isinstance(value, (int, float)):
            return str(value)
        return 'false'  # default
    
    # Вставка данных
    for item_key, item in items_data.items():
        total += 1
        
        try:
            # Используем item_key как fallback для display_name
            display_name = item.get('dname', item_key)
            
            # Обработка cooldown - преобразуем false в NULL
            cooldown = item.get('cd')
            if cooldown is False:
                cooldown = None
            
            # Подготовка значений
            values = {
                'item_key': item_key,
                'item_id': item.get('id'),
                'display_name': display_name,
                'qual': item.get('qual'),
                'cost': item.get('cost', 0),
                'behavior': item.get('behavior'),
                'damage_type': item.get('dmg_type'),
                'cooldown': cooldown,
                'mana_cost': parse_cost_value(item.get('mc', False)),
                'health_cost': parse_cost_value(item.get('hc', False)),
                'lore': item.get('lore'),
                'image_url': item.get('img'),
                'components': json.dumps(item.get('components', [])),
                'attributes': json.dumps(item.get('attrib', [])),
                'abilities': json.dumps(item.get('abilities', [])),
                'notes': item.get('notes'),
                'created': item.get('created', False),
                'charges': item.get('charges', False)
            }
            
            # SQL-запрос
            query = sql.SQL("""
                INSERT INTO items (
                    item_key, item_id, display_name, qual, cost, behavior, damage_type,
                    cooldown, mana_cost, health_cost, lore, image_url, components,
                    attributes, abilities, notes, created, charges
                ) VALUES (
                    %(item_key)s, %(item_id)s, %(display_name)s, %(qual)s, %(cost)s, 
                    %(behavior)s, %(damage_type)s, %(cooldown)s, %(mana_cost)s, 
                    %(health_cost)s, %(lore)s, %(image_url)s, %(components)s, 
                    %(attributes)s, %(abilities)s, %(notes)s, %(created)s, %(charges)s
                )
                ON CONFLICT (item_key) DO UPDATE SET
                    item_id = EXCLUDED.item_id,
                    display_name = EXCLUDED.display_name,
                    qual = EXCLUDED.qual,
                    cost = EXCLUDED.cost,
                    behavior = EXCLUDED.behavior,
                    damage_type = EXCLUDED.damage_type,
                    cooldown = EXCLUDED.cooldown,
                    mana_cost = EXCLUDED.mana_cost,
                    health_cost = EXCLUDED.health_cost,
                    lore = EXCLUDED.lore,
                    image_url = EXCLUDED.image_url,
                    components = EXCLUDED.components,
                    attributes = EXCLUDED.attributes,
                    abilities = EXCLUDED.abilities,
                    notes = EXCLUDED.notes,
                    created = EXCLUDED.created,
                    charges = EXCLUDED.charges
            """)
            
            cur.execute(query, values)
            success += 1
            
        except Exception as e:
            print(f"Ошибка при обработке предмета {item_key}: {e}")
            conn.rollback()
            skipped += 1
    
    # Применяем изменения
    conn.commit()
    
    # Закрываем соединение
    cur.close()
    conn.close()
    
    # Статистика
    print(f"Импорт завершен. Обработано: {total}, Успешно: {success}, Пропущено: {skipped}")
    return True

# Конфигурация
config = {
    'json_file_path': 'items.json',
    'db_credentials': {
        'dbname': 'postgres',
        'user': 'Jhate',
        'password': '2005',
        'host': 'localhost'
    }
}

# Запуск импорта
if __name__ == "__main__":
    import_items(**config)
