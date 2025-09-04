<?php
// View All Registrations Page
// Displays all registered students with search and filter functionality

// Start session for data access
session_start();

// Load registrations from JSON file if it exists
$jsonFile = 'registrations.json';
$registrations = [];

if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $registrations = json_decode($jsonData, true) ?: [];
}

// Merge with session data
$sessionRegistrations = isset($_SESSION['registrations']) ? $_SESSION['registrations'] : [];
$allRegistrations = array_merge($registrations, $sessionRegistrations);

// Remove duplicates based on email (in case same registration exists in both)
$uniqueRegistrations = [];
$seenEmails = [];
foreach ($allRegistrations as $reg) {
    if (!in_array($reg['email'], $seenEmails)) {
        $uniqueRegistrations[] = $reg;
        $seenEmails[] = $reg['email'];
    }
}

// Sort by timestamp (newest first)
usort($uniqueRegistrations, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Handle search functionality
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredRegistrations = $uniqueRegistrations;

if (!empty($searchTerm)) {
    $filteredRegistrations = array_filter($uniqueRegistrations, function($reg) use ($searchTerm) {
        $searchLower = strtolower($searchTerm);
        return (
            strpos(strtolower($reg['name']), $searchLower) !== false ||
            strpos(strtolower($reg['email']), $searchLower) !== false ||
            strpos(strtolower($reg['club']), $searchLower) !== false
        );
    });
}

// Handle club filter
$selectedClub = isset($_GET['club']) ? $_GET['club'] : '';
if (!empty($selectedClub)) {
    $filteredRegistrations = array_filter($filteredRegistrations, function($reg) use ($selectedClub) {
        return $reg['club'] === $selectedClub;
    });
}

// Get unique clubs for filter dropdown
$clubs = array_unique(array_column($uniqueRegistrations, 'club'));
sort($clubs);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Registrations</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .view-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2em;
        }
        
        .filters-section {
            background: #f8f9fa;
            padding: 1.5em;
            border-radius: 8px;
            margin-bottom: 2em;
            display: flex;
            gap: 1em;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5em;
        }
        
        .filter-group label {
            font-weight: bold;
            color: #333;
        }
        
        .filter-input {
            padding: 0.5em;
            border: 1px solid #71C9CE;
            border-radius: 4px;
            font-size: 1em;
        }
        
        .filter-button {
            background: #71C9CE;
            color: white;
            border: none;
            padding: 0.5em 1em;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        
        .filter-button:hover {
            background: #222;
        }
        
        .clear-filters {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5em 1em;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
        }
        
        .clear-filters:hover {
            background: #5a6268;
        }
        
        .stats-section {
            background: #e3fdfd;
            padding: 1em;
            border-radius: 8px;
            margin-bottom: 2em;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1em;
            margin-top: 1em;
        }
        
        .stat-item {
            background: white;
            padding: 1em;
            border-radius: 6px;
            border-left: 4px solid #71C9CE;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #71C9CE;
        }
        
        .registrations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1em;
        }
        
        .registration-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5em;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .registration-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .card-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1em;
            margin-bottom: 1em;
        }
        
        .student-name {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .club-badge {
            background: #71C9CE;
            color: white;
            padding: 0.3em 0.8em;
            border-radius: 20px;
            font-size: 0.9em;
            display: inline-block;
            margin-top: 0.5em;
        }
        
        .card-details {
            color: #666;
            line-height: 1.6;
        }
        
        .card-details p {
            margin: 0.5em 0;
        }
        
        .interests-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3em;
            margin: 0.5em 0;
        }
        
        .interest-tag {
            background: #e9ecef;
            color: #495057;
            padding: 0.2em 0.6em;
            border-radius: 12px;
            font-size: 0.8em;
        }
        
        .timestamp {
            color: #999;
            font-size: 0.9em;
            font-style: italic;
        }
        
        .no-results {
            text-align: center;
            padding: 3em;
            color: #666;
        }
        
        .back-link {
            display: inline-block;
            background: #71C9CE;
            color: white;
            padding: 0.7em 1.5em;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 2em;
        }
        
        .back-link:hover {
            background: #222;
        }
    </style>
</head>
<body>
    <header>
        <h1>Student Club Registrations</h1>
        <p>View and manage all student registrations</p>
    </header>

    <div class="view-container">
        <a href="index.html" class="back-link">← Back to Registration Form</a>
        
        <!-- Statistics Section -->
        <div class="stats-section">
            <h2>Registration Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($uniqueRegistrations); ?></div>
                    <div>Total Registrations</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($clubs); ?></div>
                    <div>Active Clubs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($filteredRegistrations); ?></div>
                    <div>Filtered Results</div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" style="display: flex; gap: 1em; align-items: end; flex-wrap: wrap; width: 100%;">
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" class="filter-input" 
                           placeholder="Search by name, email, or club..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="club">Filter by Club:</label>
                    <select id="club" name="club" class="filter-input">
                        <option value="">All Clubs</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?php echo htmlspecialchars($club); ?>" 
                                    <?php echo ($selectedClub === $club) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($club); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="filter-button">Apply Filters</button>
                <a href="view-registrations.php" class="clear-filters">Clear All</a>
            </form>
        </div>

        <!-- Registrations Display -->
        <?php if (empty($filteredRegistrations)): ?>
            <div class="no-results">
                <h3>No registrations found</h3>
                <p>
                    <?php if (!empty($searchTerm) || !empty($selectedClub)): ?>
                        Try adjusting your search criteria or <a href="view-registrations.php">clear all filters</a>.
                    <?php else: ?>
                        No students have registered yet. <a href="index.html">Be the first to register!</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="registrations-grid">
                <?php foreach ($filteredRegistrations as $reg): ?>
                    <div class="registration-card">
                        <div class="card-header">
                            <h3 class="student-name"><?php echo htmlspecialchars($reg['name']); ?></h3>
                            <span class="club-badge"><?php echo htmlspecialchars($reg['club']); ?></span>
                        </div>
                        
                        <div class="card-details">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($reg['email']); ?></p>
                            
                            <?php if (!empty($reg['phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($reg['phone']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($reg['interests'])): ?>
                                <p><strong>Interests:</strong></p>
                                <div class="interests-list">
                                    <?php foreach ($reg['interests'] as $interest): ?>
                                        <span class="interest-tag"><?php echo htmlspecialchars($interest); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($reg['experience'])): ?>
                                <p><strong>Experience:</strong></p>
                                <p style="font-style: italic; background: #f8f9fa; padding: 0.5em; border-radius: 4px;">
                                    "<?php echo htmlspecialchars($reg['experience']); ?>"
                                </p>
                            <?php endif; ?>
                            
                            <p class="timestamp">
                                Registered: <?php echo date('M j, Y \a\t g:i A', strtotime($reg['timestamp'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>&copy; 2024 Student Club Registration System</p>
    </footer>
</body>
</html>
