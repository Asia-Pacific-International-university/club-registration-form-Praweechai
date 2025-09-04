<?php
// Club Registration Form Processing
// Steps 3-6: PHP Processing, Validation, Array Storage, and Enhanced Features

// Start session for data persistence
session_start();

// Initialize registrations array in session if not exists
if (!isset($_SESSION['registrations'])) {
    $_SESSION['registrations'] = [];
}

// Load registrations from JSON file if it exists
$jsonFile = 'registrations.json';
$registrations = [];

if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $registrations = json_decode($jsonData, true) ?: [];
}

// Merge session data with file data
$allRegistrations = array_merge($registrations, $_SESSION['registrations']);

// Check if the form was submitted using POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize variables
    $name = '';
    $email = '';
    $club = '';
    $phone = '';
    $interests = [];
    $experience = '';
    $errors = [];
    
    // Extract and sanitize form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $club = isset($_POST['club']) ? trim($_POST['club']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $experience = isset($_POST['experience']) ? trim($_POST['experience']) : '';
    
    // Handle interests (checkboxes)
    if (isset($_POST['interests']) && is_array($_POST['interests'])) {
        $interests = $_POST['interests'];
    }
    
    // Step 4: Data Validation
    // Validate name (required, not empty)
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long.";
    }
    
    // Validate email (required, proper format)
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Validate club selection (required)
    if (empty($club)) {
        $errors[] = "Please select a club.";
    }
    
    // Validate phone (optional but if provided, should be valid)
    if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]+$/', $phone)) {
        $errors[] = "Please enter a valid phone number.";
    }
    
    // If no errors, process the registration
    if (empty($errors)) {
        // Create registration data array
        $registration = [
            'id' => uniqid(),
            'name' => htmlspecialchars($name),
            'email' => htmlspecialchars($email),
            'club' => htmlspecialchars($club),
            'phone' => htmlspecialchars($phone),
            'interests' => array_map('htmlspecialchars', $interests),
            'experience' => htmlspecialchars($experience),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add to registrations array
        $allRegistrations[] = $registration;
        $_SESSION['registrations'][] = $registration;
        
        // Save to JSON file for persistence
        file_put_contents($jsonFile, json_encode($allRegistrations, JSON_PRETTY_PRINT));
        
        // Display success page
        displaySuccessPage($registration, $allRegistrations);
    } else {
        // Display form with errors
        displayFormWithErrors($errors, $name, $email, $club, $phone, $interests, $experience);
    }
} else {
    // If accessed directly, show the form
    displayFormWithErrors([], '', '', '', '', [], '');
}

// Function to display success page
function displaySuccessPage($registration, $allRegistrations) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Successful</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            .success-container {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                border-radius: 8px;
                padding: 2em;
                margin: 2em 0;
            }
            .registration-details {
                background: #f8f9fa;
                padding: 1em;
                border-radius: 6px;
                margin: 1em 0;
            }
            .registrations-list {
                background: #e9ecef;
                padding: 1em;
                border-radius: 6px;
                margin: 1em 0;
                max-height: 300px;
                overflow-y: auto;
            }
            .registration-item {
                background: white;
                padding: 0.5em;
                margin: 0.5em 0;
                border-radius: 4px;
                border-left: 4px solid #71C9CE;
            }
            .search-box {
                width: 100%;
                padding: 0.5em;
                margin: 1em 0;
                border: 1px solid #71C9CE;
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Registration Successful!</h1>
        </header>
        <main>
            <div class="success-container">
                <h2>✅ Thank you for registering!</h2>
                <div class="registration-details">
                    <h3>Your Registration Details:</h3>
                    <p><strong>Name:</strong> <?php echo $registration['name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $registration['email']; ?></p>
                    <p><strong>Club:</strong> <?php echo $registration['club']; ?></p>
                    <?php if (!empty($registration['phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo $registration['phone']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($registration['interests'])): ?>
                        <p><strong>Interests:</strong> <?php echo implode(', ', $registration['interests']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($registration['experience'])): ?>
                        <p><strong>Experience:</strong> <?php echo $registration['experience']; ?></p>
                    <?php endif; ?>
                    <p><strong>Registration Time:</strong> <?php echo $registration['timestamp']; ?></p>
                </div>
            </div>
            
            <div class="registrations-list">
                <h3>All Registrations (<?php echo count($allRegistrations); ?> total)</h3>
                <input type="text" class="search-box" id="searchBox" placeholder="Search registrations..." onkeyup="filterRegistrations()">
                <div id="registrationsContainer">
                    <?php foreach (array_reverse($allRegistrations) as $reg): ?>
                        <div class="registration-item" data-name="<?php echo strtolower($reg['name']); ?>" data-club="<?php echo strtolower($reg['club']); ?>">
                            <strong><?php echo $reg['name']; ?></strong> - <?php echo $reg['club']; ?>
                            <br><small><?php echo $reg['email']; ?> | <?php echo $reg['timestamp']; ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="text-align: center; margin: 2em 0;">
                <a href="index.html" style="background: #71C9CE; color: white; padding: 0.7em 1.5em; text-decoration: none; border-radius: 6px; display: inline-block; margin-right: 1em;">Register Another Student</a>
                <a href="view-registrations.php" style="background: #6c757d; color: white; padding: 0.7em 1.5em; text-decoration: none; border-radius: 6px; display: inline-block;">View All Registrations</a>
            </div>
        </main>
        <footer>
            <p>&copy; 2024 Student Club Registration System</p>
        </footer>
        
        <script>
            function filterRegistrations() {
                const searchTerm = document.getElementById('searchBox').value.toLowerCase();
                const items = document.querySelectorAll('.registration-item');
                
                items.forEach(item => {
                    const name = item.getAttribute('data-name');
                    const club = item.getAttribute('data-club');
                    
                    if (name.includes(searchTerm) || club.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
        </script>
    </body>
    </html>
    <?php
}

// Function to display form with errors
function displayFormWithErrors($errors, $name, $email, $club, $phone, $interests, $experience) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Club Registration Form</title>
        <link rel="stylesheet" href="styles.css">
        <style>
            .error-messages {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
                padding: 1em;
                border-radius: 6px;
                margin: 1em 0;
            }
            .error-messages ul {
                margin: 0;
                padding-left: 1.5em;
            }
            .form-group {
                margin: 1em 0;
            }
            .checkbox-group {
                display: flex;
                flex-wrap: wrap;
                gap: 1em;
                margin: 0.5em 0;
            }
            .checkbox-item {
                display: flex;
                align-items: center;
                gap: 0.5em;
            }
            textarea {
                width: 100%;
                padding: 0.5em;
                border: 1px solid #71C9CE;
                border-radius: 6px;
                background: #E3FDFD;
                font-size: 1em;
                font-family: Arial, sans-serif;
                resize: vertical;
                min-height: 100px;
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Student Club Registration</h1>
            <p>Join one of our exciting student clubs!</p>
        </header>

        <main>
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <h3>Please fix the following errors:</h3>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="process.php">
                <div class="form-group">
                    <label for="name">Name: *</label><br>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email: *</label><br>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number:</label><br>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="(123) 456-7890">
                </div>

                <div class="form-group">
                    <label for="club">Select Club: *</label><br>
                    <select id="club" name="club" required>
                        <option value="">-- Please choose a club --</option>
                        <option value="Art Club" <?php echo ($club === 'Art Club') ? 'selected' : ''; ?>>Art Club</option>
                        <option value="Science Club" <?php echo ($club === 'Science Club') ? 'selected' : ''; ?>>Science Club</option>
                        <option value="Music Club" <?php echo ($club === 'Music Club') ? 'selected' : ''; ?>>Music Club</option>
                        <option value="Drama Club" <?php echo ($club === 'Drama Club') ? 'selected' : ''; ?>>Drama Club</option>
                        <option value="Programming Club" <?php echo ($club === 'Programming Club') ? 'selected' : ''; ?>>Programming Club</option>
                        <option value="Sports Club" <?php echo ($club === 'Sports Club') ? 'selected' : ''; ?>>Sports Club</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Interests (select all that apply):</label><br>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="interest-art" name="interests[]" value="Art" <?php echo in_array('Art', $interests) ? 'checked' : ''; ?>>
                            <label for="interest-art">Art</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="interest-science" name="interests[]" value="Science" <?php echo in_array('Science', $interests) ? 'checked' : ''; ?>>
                            <label for="interest-science">Science</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="interest-music" name="interests[]" value="Music" <?php echo in_array('Music', $interests) ? 'checked' : ''; ?>>
                            <label for="interest-music">Music</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="interest-sports" name="interests[]" value="Sports" <?php echo in_array('Sports', $interests) ? 'checked' : ''; ?>>
                            <label for="interest-sports">Sports</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="interest-technology" name="interests[]" value="Technology" <?php echo in_array('Technology', $interests) ? 'checked' : ''; ?>>
                            <label for="interest-technology">Technology</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="experience">Previous Experience (optional):</label><br>
                    <textarea id="experience" name="experience" placeholder="Tell us about any relevant experience you have..."><?php echo htmlspecialchars($experience); ?></textarea>
                </div>

                <input type="submit" name="submit" value="Register">
            </form>
        </main>

        <footer>
            <p>&copy; 2024 Student Club Registration System</p>
        </footer>
    </body>
    </html>
    <?php
}
?>