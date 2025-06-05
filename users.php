<?php
header('Content-Type: text/html; charset=utf-8');
include 'db.php';

$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'user_exists':
            $error = 'User with that nickname already exists';
            break;
        case 'upload_error':
            $error = 'Error loading the avatar';
            break;
        case 'invalid_file':
            $error = 'Invalid file type. Only JPEG, PNG and GIF are allowed';
            break;
    }
}


$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 12; // Количество игроков на странице

// Подготовка SQL запроса с учетом поиска
$sql = "
    SELECT 
        account_id,
        personaname,
        avatar_path,
        COUNT(*) as matches_played,
        AVG(kills) as avg_kills,
        AVG(deaths) as avg_deaths,
        AVG(assists) as avg_assists,
        AVG(gold_per_min) as avg_gpm,
        AVG(xp_per_min) as avg_xpm,
        AVG(hero_damage) as avg_hero_damage,
        AVG(tower_damage) as avg_tower_damage,
	calculate_kda(AVG(kills), AVG(deaths), AVG(assists)) as kda_ratio 

    FROM players
    WHERE personaname IS NOT NULL
";


if (!empty($search)) {
    $sql .= " AND personaname LIKE :search";
}

$sql .= " GROUP BY account_id, personaname, avatar_path ORDER BY kda_ratio DESC";

$countSql = "SELECT COUNT(DISTINCT account_id) as total FROM players WHERE personaname IS NOT NULL";
if (!empty($search)) {
    $countSql .= " AND personaname LIKE :search_count";
}

$countStmt = $pdo->prepare($countSql);
if (!empty($search)) {
    $countStmt->execute([':search_count' => "%$search%"]);
} else {
    $countStmt->execute();
}
$totalPlayers = $countStmt->fetchColumn();
$totalPages = ceil($totalPlayers / $perPage);


$sql .= " LIMIT :perPage OFFSET :offset";
$offset = ($page - 1) * $perPage;


$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOTA 2 Players</title>
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
    
    .players-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .player-card {
        background: rgba(26, 62, 114, 0.2);
        border: 1px solid rgba(78, 155, 255, 0.1);
        border-radius: var(--border-radius);
        padding: 20px;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        backdrop-filter: blur(5px);
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .player-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
        border-color: rgba(78, 155, 255, 0.3);
        background: rgba(26, 62, 114, 0.3);
    }
    
    .player-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        margin-bottom: 15px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid var(--primary-color);
    }

    .player-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        transition: var(--transition);
    }

    .player-avatar:hover img {
        transform: scale(1.1);
        box-shadow: 0 0 20px var(--primary-color);
    }

    .player-avatar .no-image {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: rgba(233, 236, 239, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        font-size: 2rem;
        font-family: 'Oxanium', sans-serif;
    }
    
    .player-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 5px;
        text-align: center;
    }
    
    .player-stats {
        width: 100%;
        margin-top: 15px;
    }
    
    .stat-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(78, 155, 255, 0.1);
    }
    
    .stat-label {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .stat-value {
        font-weight: 500;
        color: var(--text-color);
    }
    
    .kda-value {
        color: var(--primary-color);
        font-weight: 700;
    }
    
    .add-player-form {
        background: rgba(26, 62, 114, 0.2);
        border: 1px solid rgba(78, 155, 255, 0.1);
        border-radius: var(--border-radius);
        padding: 30px;
        box-shadow: var(--box-shadow);
        margin-top: 40px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .form-title {
        text-align: center;
        margin-bottom: 20px;
        color: var(--primary-color);
        font-family: 'Oxanium', sans-serif;
        font-size: 1.5rem;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-secondary);
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        background: rgba(26, 62, 114, 0.3);
        border: 1px solid rgba(78, 155, 255, 0.2);
        border-radius: var(--border-radius);
        color: var(--text-color);
        font-size: 1rem;
        transition: var(--transition);
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 2px rgba(228, 161, 1, 0.2);
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
    
    .empty-message {
        text-align: center;
        padding: 50px;
        background: rgba(26, 62, 114, 0.2);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        border: 1px solid rgba(78, 155, 255, 0.1);
        grid-column: 1 / -1;
    }
    
    .empty-message h3 {
        color: var(--text-secondary);
    }
    
    .empty-message p {
        color: var(--text-secondary);
    }
    
    .avatar-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--primary-color);
        margin: 0 auto 15px;
        display: block;
    }
    
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .file-input-button {
        border: 1px dashed var(--primary-color);
        border-radius: var(--border-radius);
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
    }
    
    .file-input-button:hover {
        background: rgba(78, 155, 255, 0.1);
    }
    
    .file-input {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    
    .file-input-label {
        display: block;
        color: var(--text-secondary);
        margin-bottom: 10px;
    }
    
    .file-name {
        margin-top: 10px;
        font-size: 0.9rem;
        color: var(--primary-color);
    }
    
    .error-message {
        color: var(--danger-color);
        background: rgba(220, 53, 69, 0.1);
        padding: 10px 15px;
        border-radius: var(--border-radius);
        border: 1px solid var(--danger-color);
        margin-bottom: 20px;
        text-align: center;
    }

    /* Search Styles */
    .search-container {
        margin: 2rem auto;
        max-width: 600px;
        width: 100%;
    }
    
    .search-form {
        display: flex;
        width: 100%;
        box-shadow: var(--box-shadow);
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    
    .search-input {
        flex-grow: 1;
        padding: 12px 20px;
        background: rgba(26, 62, 114, 0.3);
        border: none;
        color: var(--text-color);
        font-size: 1rem;
        transition: var(--transition);
    }
    
    .search-input:focus {
        outline: none;
        background: rgba(26, 62, 114, 0.5);
    }
    
    .search-input::placeholder {
        color: var(--text-secondary);
        opacity: 0.7;
    }
    
    .search-button {
        padding: 0 20px;
        background-color: var(--primary-color);
        color: var(--dark-color);
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .search-button:hover {
        background-color: var(--primary-dark);
    }

    /* Pagination Styles */
    .pagination-container {
        display: flex;
        justify-content: center;
        margin: 2rem 0;
    }
    
    .pagination {
        display: flex;
        gap: 5px;
        background: rgba(26, 62, 114, 0.2);
        padding: 10px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        border: 1px solid rgba(78, 155, 255, 0.1);
    }
    
    .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 10px;
        background-color: rgba(26, 62, 114, 0.3);
        color: var(--text-color);
        border: 1px solid rgba(78, 155, 255, 0.2);
        border-radius: var(--border-radius);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 500;
    }
    
    .page-link:hover {
        background-color: rgba(78, 155, 255, 0.1);
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }
    
    .page-link.active {
        background-color: var(--primary-color);
        color: var(--dark-color);
        border-color: var(--primary-color);
        transform: none;
        box-shadow: 0 0 10px rgba(228, 161, 1, 0.5);
    }
    
    .page-link.disabled {
        opacity: 0.5;
        pointer-events: none;
        cursor: not-allowed;
    }
    
    .page-link i {
        font-size: 0.9rem;
    }
    
    .page-dots {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 10px;
        color: var(--text-secondary);
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .players-grid {
            grid-template-columns: 1fr;
        }
        
        .add-player-form {
            padding: 20px;
        }
        
        .search-container {
            padding: 0 15px;
        }
        
        .pagination {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .page-link {
            min-width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 480px) {
        .search-form {
            flex-direction: column;
            box-shadow: none;
        }
        
        .search-input {
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            margin-bottom: 1px;
        }
        
        .search-button {
            padding: 12px;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }
        
        .pagination {
            gap: 3px;
            padding: 8px;
        }
        
        .page-link {
            min-width: 30px;
            height: 30px;
            font-size: 0.8rem;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to menu</a>
        
        <div class="header">
            <h1>DOTA 2 Players</h1>
            <p>All registered players with their statistics</p>
        </div>
        

        <div class="search-container">
            <form class="search-form" method="get" action="">
                <input type="text" name="search" class="search-input" placeholder="Search players by name..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <input type="hidden" name="page" value="1">
            </form>
        </div>
        
        <?php if (!empty($players)): ?>
            <div class="players-grid">
                <?php foreach ($players as $player): ?>
                    <div class="player-card">
                        <div class="player-avatar">
                            <?php if (!empty($player['avatar_path'])): 
                                $avatarUrl = $player['avatar_path'];
                                if (!preg_match('/^https?:\/\//i', $avatarUrl)) {
                                    // If you have a base URL for local avatars, prepend it here
                                    // $avatarUrl = '/path/to/avatars/' . $avatarUrl;
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" 
                                     alt="<?php echo htmlspecialchars($player['personaname']); ?>"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div class="no-image" style="display:none">
                                    <?php echo strtoupper(substr($player['personaname'], 0, 1)); ?>
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <?php echo strtoupper(substr($player['personaname'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="player-name"><?php echo htmlspecialchars($player['personaname']); ?></h3>
                        
                        <div class="player-stats">
                            <div class="stat-item">
                                <span class="stat-label">KDA:</span>
                                <span class="stat-value kda-value"><?php echo number_format($player['kda_ratio'], 2); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Matches:</span>
                                <span class="stat-value"><?php echo htmlspecialchars($player['matches_played']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg Kills:</span>
                                <span class="stat-value"><?php echo number_format($player['avg_kills'], 1); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg Deaths:</span>
                                <span class="stat-value"><?php echo number_format($player['avg_deaths'], 1); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg Assists:</span>
                                <span class="stat-value"><?php echo number_format($player['avg_assists'], 1); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg GPM:</span>
                                <span class="stat-value"><?php echo number_format($player['avg_gpm']); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Avg XPM:</span>
                                <span class="stat-value"><?php echo number_format($player['avg_xpm']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            

            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="page-link" title="Previous">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        
                        <?php

                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        if ($start > 1) {
                            echo '<a href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').'" class="page-link">1</a>';
                            if ($start > 2) echo '<span class="page-link">...</span>';
                        }
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor;
                        
                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) echo '<span class="page-link">...</span>';
                            echo '<a href="?page='.$totalPages.(!empty($search) ? '&search='.urlencode($search) : '').'" class="page-link">'.$totalPages.'</a>';
                        }
                        ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="page-link" title="Next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-message">
                <h3>No players found</h3>
                <p><?php echo !empty($search) ? 'Try another search query' : 'Players will appear here when they are registered in the system.'; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="add-player-form">
            <h2 class="form-title">Add New Player</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="upload_user.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="account_id">Account ID:</label>
                    <input type="number" name="account_id" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="personaname">Nickname:</label>
                    <input type="text" name="personaname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="file-input-label">Avatar (optional):</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-button">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 10px;"></i>
                            <p>Click to upload avatar</p>
                            <p class="file-name" id="file-name">No file selected</p>
                            <input type="file" name="avatar" id="avatar" class="file-input" accept="image/jpeg,image/png,image/gif">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn">Add Player</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('avatar').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
            document.getElementById('file-name').textContent = fileName;
            
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    const preview = document.createElement('img');
                    preview.src = event.target.result;
                    preview.className = 'avatar-preview';
                    
                    const container = document.querySelector('.file-input-button');
                    const oldPreview = container.querySelector('img');
                    if (oldPreview) {
                        container.replaceChild(preview, oldPreview);
                    } else {
                        const icon = container.querySelector('i');
                        const text = container.querySelector('p');
                        if (icon) container.removeChild(icon);
                        if (text) container.removeChild(text);
                        
                        const fileNameElement = container.querySelector('.file-name');
                        container.insertBefore(preview, fileNameElement);
                    }
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>