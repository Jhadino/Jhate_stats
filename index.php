<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добро пожаловать в Jhate Stats<3</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Oxanium:wght@600;700&display=swap" rel="stylesheet">
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
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--background-dark), var(--background-light));
            color: var(--text-color);
            min-height: 100vh;
            line-height: 1.6;
            padding: 0;
            background-attachment: fixed;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
            position: relative;
        }
        
        header::after {
            content: '';
            display: block;
            width: 150px;
            height: 4px;
            background: var(--primary-color);
            margin: 1rem auto;
            border-radius: 2px;
        }
        
        h1 {
            font-family: 'Oxanium', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            margin-bottom: 1rem;
            letter-spacing: 1px;
        }
        
        .subtitle {
            color: var(--text-secondary);
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        nav {
            margin-top: 2rem;
        }
        
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .nav-card {
            background: rgba(26, 62, 114, 0.2);
            border: 1px solid rgba(78, 155, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            transition: var(--transition);
            backdrop-filter: blur(5px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
            border-color: rgba(78, 155, 255, 0.3);
            background: rgba(26, 62, 114, 0.3);
        }
        
        .nav-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .nav-card h2 {
            font-family: 'Oxanium', sans-serif;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }
        
        .nav-card p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .nav-card a {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            background: var(--primary-color);
            color: var(--dark-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .nav-card a:hover {
            background: transparent;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
	.stats-counters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin: 3rem 0;
    padding: 2rem;
    background: rgba(26, 62, 114, 0.2);
    border-radius: var(--border-radius);
    border: 1px solid rgba(78, 155, 255, 0.1);
}

.counter {
    text-align: center;
    padding: 1.5rem;
    background: rgba(10, 25, 47, 0.4);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.counter:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.counter i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.count {
    font-family: 'Oxanium', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0.5rem 0;
}

.counter-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}
        
        footer {
            text-align: center;
            margin-top: 4rem;
            padding: 2rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2rem;
            }
            
            .nav-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Jhate Stats</h1>
            <p class="subtitle">Всесторонняя статистика по героям, предметам и матчам Dota 2</p>
        </header>
                <div class="stats-counters">
            <?php
            include 'db.php';
            
            // Получаем статистику из БД
            $usersCount = $pdo->query("SELECT COUNT(DISTINCT personaname) AS unique_count 
FROM players;")->fetchColumn();
            $matchesCount = $pdo->query("SELECT COUNT(*) FROM matches")->fetchColumn();
            $heroesCount = $pdo->query("SELECT COUNT(*) FROM heroes")->fetchColumn();
            ?>
            
            <div class="counter">
                <i class="fas fa-users"></i>
                <div class="count" data-target="<?= $usersCount ?>">0</div>
                <div class="counter-label">Зарегистрированных игроков</div>
            </div>
            
            <div class="counter">
                <i class="fas fa-trophy"></i>
                <div class="count" data-target="<?= $matchesCount ?>">0</div>
                <div class="counter-label">Записанных матчей</div>
            </div>
            
            <div class="counter">
                <i class="fas fa-user-ninja"></i>
                <div class="count" data-target="<?= $heroesCount ?>">0</div>
                <div class="counter-label">Уникальных героев</div>
            </div>
        </div>

        <nav>
            <div class="nav-grid">
                <div class="nav-card">
                    <i class="fas fa-user-ninja"></i>
                    <h2>Герои</h2>
                    <p>Полная статистика по всем героям Dota 2, их характеристикам и способностям</p>
                    <a href="heroes.php">Перейти</a>
                </div>
                
                <div class="nav-card">
                    <i class="fas fa-shopping-bag"></i>
                    <h2>Предметы</h2>
                    <p>Информация о всех предметах в игре, их стоимости и эффективности</p>
                    <a href="items.php">Перейти</a>
                </div>
                
                <div class="nav-card">
                    <i class="fas fa-trophy"></i>
                    <h2>Матчи</h2>
                    <p>Загрузить матч для статистики</p>
                    <a href="matches.php">Перейти</a>
                </div>
                
                <div class="nav-card">
                    <i class="fas fa-users"></i>
                    <h2>Пользователи</h2>
                    <p>Рейтинги и статистика игроков вашего сообщества</p>
                    <a href="users.php">Перейти</a>
                </div>
                
                <div class="nav-card">
                    <i class="fas fa-chart-bar"></i>
                    <h2>Статистика</h2>
                    <p>Подробная аналитика и графики по матчам и игровым показателям</p>
                    <a href="match_stats.php">Перейти</a>
                </div>
            </div>
        </nav>
        
        <footer>
            <p>Jhate stats 2025</p>
        </footer>
    </div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.count');
    const speed = 200; // Скорость анимации
    
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const increment = target / speed;
        
        if (count < target) {
            const updateCount = () => {
                const current = +counter.innerText;
                if (current < target) {
                    counter.innerText = Math.ceil(current + increment);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        }
    });
});
</script>
</body>
</html>