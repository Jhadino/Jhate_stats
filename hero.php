<?php
header('Content-Type: text/html; charset=utf-8');
include 'db.php';

if (isset($_GET['id'])) {
    $hero_id = intval($_GET['id']);

    $stmt = $pdo->prepare("SELECT * FROM heroes WHERE id = :id");
    $stmt->execute(['id' => $hero_id]);
    $hero = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hero) {
        // Check if image exists
        if (!empty($hero['image'])) {
            $imageData = stream_get_contents($hero['image']);
            $imageBase64 = base64_encode($imageData);
        } else {
            $imageBase64 = null;
        }

        $stmtAbilities = $pdo->prepare("SELECT * FROM abilities WHERE hero_id = :hero_id");
        $stmtAbilities->execute(['hero_id' => $hero_id]);
        $abilities = $stmtAbilities->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "Hero not found.";
        exit;
    }
} else {
    echo "Hero ID not specified.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hero['name']); ?> - DOTA 2</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
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
        
        .hero-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(78, 155, 255, 0.2);
        }
        
        .hero-title {
            font-family: 'Oxanium', sans-serif;
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .hero-description {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-style: italic;
        }
        
        .hero-content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 30px;
        }
        
        .hero-image-container {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
        }
        
        .hero-image {
            width: 100%;
            border-radius: var(--border-radius);
            border: 3px solid var(--primary-color);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .hero-image:hover {
            transform: scale(1.02);
        }
        
        .no-image {
            background: rgba(0, 0, 0, 0.2);
            padding: 100px 20px;
            text-align: center;
            border-radius: var(--border-radius);
            color: var(--text-secondary);
            font-style: italic;
            border: 1px dashed var(--text-secondary);
        }
        
        .hero-details {
            flex: 2;
            min-width: 300px;
        }
        
        .stats-section {
            background: rgba(26, 62, 114, 0.2);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 30px;
            border-left: 3px solid var(--primary-color);
        }
        
        .section-title {
            font-family: 'Oxanium', sans-serif;
            color: var(--primary-color);
            font-size: 1.4rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-item {
            background: rgba(26, 62, 114, 0.1);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            border-bottom: 2px solid var(--primary-color);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .attributes-row {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        .attribute {
            flex: 1;
            text-align: center;
            padding: 15px;
            border-radius: var(--border-radius);
            background: rgba(26, 62, 114, 0.2);
        }
        
        .attribute-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .attribute-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .abilities-section {
            margin-top: 30px;
        }
        
        .abilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .ability-card {
            background: rgba(26, 62, 114, 0.2);
            border-radius: var(--border-radius);
            padding: 15px;
            border-left: 3px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .ability-card:hover {
            transform: translateY(-5px);
            background: rgba(26, 62, 114, 0.3);
        }
        
        .ability-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .ability-image {
            width: 50px;
            height: 50px;
            border-radius: var(--border-radius);
            border: 2px solid var(--primary-color);
            margin-right: 15px;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .ability-name {
            font-weight: 500;
            font-size: 1.1rem;
            color: var(--primary-color);
        }
        
        .ability-essence {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-style: italic;
            margin-top: 3px;
        }
        
        .ability-description {
            margin-top: 10px;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .attributes-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="heroes.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to Heroes</a>
        
        <div class="hero-header">
            <h1 class="hero-title"><?php echo htmlspecialchars($hero['name']); ?></h1>
            <p class="hero-description"><?php echo htmlspecialchars($hero['description']); ?></p>
        </div>
        
        <div class="hero-content">
            <div class="hero-image-container">
                <?php if ($imageBase64): ?>
                    <img src="data:image/png;base64,<?php echo $imageBase64; ?>" alt="<?php echo htmlspecialchars($hero['name']); ?>" class="hero-image">
                <?php else: ?>
                    <div class="no-image">No image available</div>
                <?php endif; ?>
            </div>
            
            <div class="hero-details">
                <div class="stats-section">
                    <h2 class="section-title"><i class="fas fa-chart-line"></i> Role & Stats</h2>
                    
                    <div class="stat-item">
                        <div class="stat-label">Role</div>
                        <div class="stat-value"><?php echo htmlspecialchars($hero['role']); ?></div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">Health</div>
                            <div class="stat-value"><?php echo htmlspecialchars($hero['health']); ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Mana</div>
                            <div class="stat-value"><?php echo htmlspecialchars($hero['mana']); ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Base Damage</div>
                            <div class="stat-value"><?php echo htmlspecialchars($hero['base_damage']); ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Base Speed</div>
                            <div class="stat-value"><?php echo htmlspecialchars($hero['base_speed']); ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Attack Type</div>
                            <div class="stat-value"><?php echo htmlspecialchars($hero['attack_type']); ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Attack Range</div>
                            <div class="stat-value"><?php echo htmlspecialchars($hero['attack_range']); ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-label">Primary Attribute</div>
                            <div class="stat-value"><?php echo htmlspecialchars($hero['primary_attribute']); ?></div>
                        </div>
                    </div>
                    
                    <h3 class="section-title" style="font-size: 1.2rem; margin-top: 20px;"><i class="fas fa-dumbbell"></i> Attributes</h3>
                    
                    <div class="attributes-row">
                        <div class="attribute">
                            <div class="attribute-value"><?php echo htmlspecialchars($hero['strength']); ?></div>
                            <div class="attribute-label">Strength</div>
                        </div>
                        
                        <div class="attribute">
                            <div class="attribute-value"><?php echo htmlspecialchars($hero['agility']); ?></div>
                            <div class="attribute-label">Agility</div>
                        </div>
                        
                        <div class="attribute">
                            <div class="attribute-value"><?php echo htmlspecialchars($hero['intelligence']); ?></div>
                            <div class="attribute-label">Intelligence</div>
                        </div>
                    </div>
                </div>
                
                <div class="abilities-section">
                    <h2 class="section-title"><i class="fas fa-fire"></i> Abilities</h2>
                    
                    <div class="abilities-grid">
                        <?php foreach ($abilities as $ability): ?>
                            <div class="ability-card">
                                <div class="ability-header">
                                    <?php if (!empty($ability['image'])): 
                                        $abilityImageData = stream_get_contents($ability['image']);
                                        $abilityImageBase64 = base64_encode($abilityImageData); ?>
                                        <img src="data:image/png;base64,<?php echo $abilityImageBase64; ?>" alt="<?php echo htmlspecialchars($ability['name']); ?>" class="ability-image">
                                    <?php else: ?>
                                        <div class="ability-image" style="display: flex; align-items: center; justify-content: center; background: rgba(228, 161, 1, 0.1);">
                                            <i class="fas fa-question" style="color: var(--primary-color);"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <div class="ability-name"><?php echo htmlspecialchars($ability['name']); ?></div>
                                        <?php if (!empty($ability['essence'])): ?>
                                            <div class="ability-essence"><?php echo htmlspecialchars($ability['essence']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="ability-description">
                                    <?php echo htmlspecialchars($ability['description']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>