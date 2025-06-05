<?php
header('Content-Type: text/html; charset=utf-8');
include 'db.php';


$attribute = isset($_GET['attribute']) ? $_GET['attribute'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';


$sql = "SELECT * FROM heroes WHERE 1=1"; // Начальное условие для удобства добавления фильтров
$params = [];

if (!empty($search)) {
    $sql .= " AND LOWER(name) LIKE LOWER(:search)";
    $params[':search'] = "%$search%";
}

if ($attribute !== 'all') {
    $sql .= " AND primary_attribute = :attribute";
    $params[':attribute'] = $attribute;
}

$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$heroes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOTA 2 Heroes</title>
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

body, html {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
}

body {
  font-family: 'Roboto', sans-serif;
  background: linear-gradient(135deg, var(--background-dark), var(--background-light));
  color: var(--text-color);
  line-height: 1.6;
  background-attachment: fixed;
}

/* Основная структура */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  width: 100%;
}

.page-title {
  font-family: 'Oxanium', sans-serif;
  color: var(--primary-color);
  font-size: 2.5rem;
  margin-bottom: 1.5rem;
  text-align: center;
}

/* Фильтры */
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 30px;
  align-items: center;
}

.attribute-filter {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 8px 16px;
  border-radius: var(--border-radius);
  background: rgba(26, 62, 114, 0.2);
  color: var(--text-color);
  border: 1px solid var(--primary-color);
  cursor: pointer;
  transition: var(--transition);
  font-weight: 500;
}

.filter-btn.active {
  background: var(--primary-color);
  color: var(--dark-color);
}

.filter-btn:hover {
  background: var(--primary-dark);
  color: var(--dark-color);
}

.search-box {
  flex: 1;
  min-width: 250px;
  max-width: 400px;
  position: relative;
}

.search-input {
  width: 100%;
  padding: 10px 15px 10px 40px;
  border-radius: var(--border-radius);
  background: rgba(26, 62, 114, 0.2);
  border: 1px solid var(--primary-color);
  color: var(--text-color);
  font-size: 1rem;
}

.search-icon {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary-color);
}

/* Сетка героев */
.heroes-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 25px;
  margin-top: 20px;
  justify-content: flex-start;
  padding: 0;
  width: 100%;
}

/* Карточка героя */
.hero-card {
  flex: 1 0 calc(20% - 20px);
  min-width: calc(20% - 20px);
  max-width: 300px; 
  background: rgba(26, 62, 114, 0.2);
  border-radius: var(--border-radius);
  overflow: hidden;
  transition: var(--transition);
  border-left: 3px solid var(--primary-color);
  box-shadow: var(--box-shadow);
  margin-bottom: 25px;
}

/* Стиль для единственной карточки */
.heroes-grid.single-card {
  justify-content: center;
}

.heroes-grid.single-card .hero-card {
  flex: 0 0 auto; /* Убираем растягивание */
  width: auto;
  max-width: 400px; /* Фиксируем максимальную ширину */
  min-width: 250px; /* Минимальная ширина */
}

/* Изображение героя */
.hero-image-container {
  height: 180px;
  position: relative;
  overflow: hidden;
}

.heroes-grid.single-card .hero-image-container {
  height: 180px;
}

.hero-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: var(--transition);
}

.hero-card:hover .hero-image {
  transform: scale(1.05);
}

.no-image {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.2);
  color: var(--text-secondary);
  font-style: italic;
}

/* Атрибуты */
.hero-attribute {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: var(--dark-color);
}

.attribute-str { background-color: #d32f2f; }
.attribute-agi { background-color: #388e3c; }
.attribute-int { background-color: #1976d2; }

/* Информация о герое */
.hero-info {
  padding: 15px;
}

.hero-name {
  font-weight: 500;
  font-size: 1.2rem;
  margin-bottom: 5px;
  color: var(--primary-color);
  font-family: 'Oxanium', sans-serif;
}

.hero-role {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-bottom: 10px;
}

.hero-link {
  display: inline-block;
  padding: 8px 15px;
  background-color: var(--primary-color);
  color: var(--dark-color);
  text-decoration: none;
  border-radius: var(--border-radius);
  font-size: 0.9rem;
  font-weight: 500;
  transition: var(--transition);
}

.hero-link:hover {
  background-color: var(--primary-dark);
}
.back-button {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
            transition: var(--transition);
        }

.back-button i {
            margin-right: 5px;
        }
.back-button:hover {
            color: var(--primary-dark);
        }

/* Сообщение об отсутствии результатов */
.no-results {
  grid-column: 1 / -1;
  text-align: center;
  padding: 50px;
  color: var(--text-secondary);
  font-style: italic;
  width: 100%;
}

/* Адаптивность */
@media (max-width: 1200px) {
  .hero-card {
    flex: 1 0 calc(25% - 20px);
    min-width: calc(25% - 20px);
  }
  .heroes-grid.single-card .hero-card {
    flex: 0 0 calc(40% - 20px);
    max-width: calc(40% - 20px);
  }
}

@media (max-width: 992px) {
  .hero-card {
    flex: 1 0 calc(33.333% - 20px);
    min-width: calc(33.333% - 20px);
  }
}

@media (max-width: 768px) {
  .container {
    padding: 1.5rem;
  }
  
  .hero-card {
    flex: 1 0 calc(50% - 20px);
    min-width: calc(50% - 20px);
  }
  
  .heroes-grid.single-card .hero-card {
    flex: 0 0 calc(70% - 20px);
    max-width: calc(70% - 20px);
  }
  
  .filters {
    flex-direction: column;
  }
  
  .search-box {
    max-width: 100%;
  }
}

@media (max-width: 576px) {
  .container {
    padding: 1rem;
  }
  
  .hero-card {
    flex: 1 0 100%;
    min-width: 100%;
  }
  
  .heroes-grid.single-card .hero-card {
    flex: 0 0 90%;
    max-width: 90%;
  }
  
  .page-title {
    font-size: 2rem;
  }
}

/* Дополнительные элементы */
.clear-search {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--primary-color);
  cursor: pointer;
  display: none;
}

.search-input:not(:placeholder-shown) + .search-icon + .clear-search {
  display: block;
}
    </style>
</head>
<body>
    <div class="container">
	<a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Menu</a>
        <h1 class="page-title">DOTA 2 Heroes</h1>
        
        <div class="filters">
            <div class="attribute-filter">
                <a href="?attribute=all" class="filter-btn <?php echo $attribute === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?attribute=str" class="filter-btn <?php echo $attribute === 'str' ? 'active' : ''; ?>">str</a>
                <a href="?attribute=agi" class="filter-btn <?php echo $attribute === 'agi' ? 'active' : ''; ?>">agi</a>
                <a href="?attribute=int" class="filter-btn <?php echo $attribute === 'int' ? 'active' : ''; ?>">int</a>
            </div>
            
            <div class="search-box">
    <i class="fas fa-search search-icon"></i>
    <form method="get" action="">
        <?php if ($attribute !== 'all'): ?>
            <input type="hidden" name="attribute" value="<?php echo htmlspecialchars($attribute); ?>">
        <?php endif; ?>
        <input type="text" name="search" class="search-input" placeholder="Search heroes..." 
               value="<?php echo htmlspecialchars($search); ?>">
    </form>
</div>
        
        <div class="heroes-grid">
            <?php if (count($heroes) > 0): ?>
                <?php foreach ($heroes as $hero): ?>
                    <div class="hero-card">
                        <div class="hero-image-container">
                            <?php if (!empty($hero['image'])): 
                                $imageData = stream_get_contents($hero['image']);
                                $imageBase64 = base64_encode($imageData); ?>
                                <img src="data:image/png;base64,<?php echo $imageBase64; ?>" alt="<?php echo htmlspecialchars($hero['name']); ?>" class="hero-image">
                            <?php else: ?>
                                <div class="no-image">No image available</div>
                            <?php endif; ?>
                            
                            <div class="hero-attribute attribute-<?php echo htmlspecialchars(strtolower($hero['primary_attribute'])); ?>">
                                <?php echo strtoupper(substr($hero['primary_attribute'], 0, 1)); ?>
                            </div>
                        </div>
                        
                        <div class="hero-info">
                            <h3 class="hero-name"><?php echo htmlspecialchars($hero['name']); ?></h3>
                            <p class="hero-role"><?php echo htmlspecialchars($hero['role']); ?></p>
                            <a href="hero.php?id=<?php echo $hero['id']; ?>" class="hero-link">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 15px; color: var(--primary-color);"></i>
                    <h3>No heroes found</h3>
                    <p>Try changing your search criteria or filter</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-input');
        let searchTimer;
        

        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                e.target.form.submit();
            }, 5000);
        });
        

        const searchBox = document.querySelector('.search-box');
        const clearBtn = document.createElement('span');
        clearBtn.innerHTML = '&times;';
        clearBtn.style.position = 'absolute';
        clearBtn.style.right = '15px';
        clearBtn.style.top = '50%';
        clearBtn.style.transform = 'translateY(-50%)';
        clearBtn.style.cursor = 'pointer';
        clearBtn.style.color = 'var(--primary-color)';
        clearBtn.style.display = searchInput.value ? 'block' : 'none';
        
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            searchInput.form.submit();
        });
        
        searchInput.addEventListener('input', function() {
            clearBtn.style.display = this.value ? 'block' : 'none';
        });
        
        searchBox.appendChild(clearBtn);
    });
</script>
</body>
</html>