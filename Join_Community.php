<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Connection.php';

$username = $_SESSION['username'] ?? 'User';
$user_id = $_SESSION['user_id'];

// Get Active Members count (from community_members table)
$members_sql = "SELECT COUNT(*) as count FROM community_members WHERE status = 'active'";
$members_result = $conn->query($members_sql);
if ($members_result && $members_result->num_rows > 0) {
    $active_members = $members_result->fetch_assoc()['count'];
} else {
    // Fallback to users table if community_members is empty
    $fallback_sql = "SELECT COUNT(*) as count FROM users";
    $fallback_result = $conn->query($fallback_sql);
    $active_members = $fallback_result->fetch_assoc()['count'] ?? 0;
}

// Get Patterns Shared count (from discussion_topics table)
$patterns_sql = "SELECT COUNT(*) as count FROM discussion_topics";
$patterns_result = $conn->query($patterns_sql);
if ($patterns_result && $patterns_result->num_rows > 0) {
    $patterns_shared = $patterns_result->fetch_assoc()['count'];
} else {
    // Fallback to booking_items
    $fallback_sql = "SELECT COUNT(DISTINCT product_id) as count FROM booking_items";
    $fallback_result = $conn->query($fallback_sql);
    $patterns_shared = $fallback_result->fetch_assoc()['count'] ?? 25;
}

// Get Countries count (from community_members table)
$countries_sql = "SELECT COUNT(DISTINCT country) as count FROM community_members WHERE country IS NOT NULL AND country != ''";
$countries_result = $conn->query($countries_sql);
if ($countries_result && $countries_result->num_rows > 0) {
    $countries_count = $countries_result->fetch_assoc()['count'];
    if ($countries_count == 0) $countries_count = 15;
} else {
    $countries_count = 15;
}

// Get Events Yearly count (from community_events table)
$events_sql = "SELECT COUNT(*) as count FROM community_events WHERE YEAR(event_date) = YEAR(NOW()) AND status != 'cancelled'";
$events_result = $conn->query($events_sql);
if ($events_result && $events_result->num_rows > 0) {
    $events_yearly = $events_result->fetch_assoc()['count'];
    if ($events_yearly == 0) $events_yearly = 12;
} else {
    $events_yearly = 12;
}

// Fetch upcoming events from database
$upcoming_events_sql = "SELECT * FROM community_events WHERE event_date >= NOW() AND status = 'upcoming' ORDER BY event_date ASC LIMIT 4";
$upcoming_events_result = $conn->query($upcoming_events_sql);
$upcoming_events = [];
if ($upcoming_events_result && $upcoming_events_result->num_rows > 0) {
    while ($row = $upcoming_events_result->fetch_assoc()) {
        $upcoming_events[] = $row;
    }
}

// Fetch discussion topics from database
$discussion_topics_sql = "SELECT dt.*, u.username as author_name 
                          FROM discussion_topics dt 
                          LEFT JOIN users u ON dt.created_by = u.id 
                          WHERE dt.status = 'active' 
                          ORDER BY dt.last_activity DESC LIMIT 4";
$discussion_topics_result = $conn->query($discussion_topics_sql);
$discussion_topics = [];
if ($discussion_topics_result && $discussion_topics_result->num_rows > 0) {
    while ($row = $discussion_topics_result->fetch_assoc()) {
        $discussion_topics[] = $row;
    }
}

// Handle Join Community Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_community'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $skill_level = mysqli_real_escape_string($conn, $_POST['skill_level']);
    $interests = mysqli_real_escape_string($conn, $_POST['interests']);
    $country = mysqli_real_escape_string($conn, $_POST['country'] ?? '');
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    
    // Check if user already joined
    $check_sql = "SELECT id FROM community_members WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE community_members SET fullname = ?, email = ?, skill_level = ?, interests = ?, country = ?, city = ?, last_active = NOW() WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssssssi", $fullname, $email, $skill_level, $interests, $country, $city, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['community_message'] = "Your community profile has been updated!";
            $_SESSION['community_message_type'] = "success";
        } else {
            $_SESSION['community_message'] = "Error updating profile: " . $conn->error;
            $_SESSION['community_message_type'] = "error";
        }
        $update_stmt->close();
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO community_members (user_id, fullname, email, skill_level, interests, country, city, join_date, status, last_active) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active', NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("issssss", $user_id, $fullname, $email, $skill_level, $interests, $country, $city);
        
        if ($insert_stmt->execute()) {
            $_SESSION['community_message'] = "Welcome to the community, $fullname! You are now a member.";
            $_SESSION['community_message_type'] = "success";
        } else {
            $_SESSION['community_message'] = "Error joining community: " . $conn->error;
            $_SESSION['community_message_type'] = "error";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
    
    header("Location: Join_Community.php");
    exit();
}

// Get user's community membership status
$user_member_sql = "SELECT * FROM community_members WHERE user_id = ?";
$user_member_stmt = $conn->prepare($user_member_sql);
$user_member_stmt->bind_param("i", $user_id);
$user_member_stmt->execute();
$user_member_result = $user_member_stmt->get_result();
$user_member = $user_member_result->fetch_assoc();
$is_member = ($user_member_result->num_rows > 0);
$user_member_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Our Community - JosLee Crocs</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #ced6fc 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        /* Navigation Bar */
        .navbar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #6a11cb;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #6a11cb;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 30px;
            padding: 3rem;
            text-align: center;
            color: white;
            margin-bottom: 2rem;
        }

        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #6a11cb;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }

        /* Community Sections */
        .section-title {
            text-align: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 2rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: #333;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Events Section */
        .events-section {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .events-section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .events-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .event-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 15px;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .event-info h4 {
            color: #333;
            margin-bottom: 0.3rem;
        }

        .event-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .event-date {
            background: #6a11cb;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
        }

        .btn-join-event {
            background: #ff9800;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-join-event:hover {
            background: #e68900;
        }

        /* Discussion Forum */
        .forum-section {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .forum-section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .topic-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .topic-item:last-child {
            border-bottom: none;
        }

        .topic-title {
            font-weight: 600;
            color: #333;
        }

        .topic-meta {
            color: #888;
            font-size: 0.8rem;
        }

        .btn-discuss {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            cursor: pointer;
        }

        /* Join Form */
        .join-form-section {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .join-form-section h2 {
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: transform 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Footer */
        footer {
            text-align: center;
            color: white;
            margin-top: 2rem;
            padding: 1rem;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                text-align: center;
            }
            
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .event-item {
                flex-direction: column;
                text-align: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="logo">🧶 JosLee Crocs</div>
            <div class="nav-links">
                <a href="Dashboard.php">🏠 Go to Dashboard</a>  
            </div>
            <div style="display: flex; gap: 1rem;">
                <span>👋 <?php echo htmlspecialchars($username); ?></span>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="hero">
            <h1>🧶 Welcome to the JosLee Crocs Community! 🧵</h1>
            <p>Join thousands of crochet enthusiasts sharing patterns, tips, and inspiration</p>
        </div>

        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($active_members); ?>+</div>
                <div class="stat-label">Active Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($patterns_shared); ?>+</div>
                <div class="stat-label">Patterns Shared</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($countries_count); ?>+</div>
                <div class="stat-label">Countries</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($events_yearly); ?>+</div>
                <div class="stat-label">Events Yearly</div>
            </div>
        </div>

        <!-- Features Grid -->
        <h2 class="section-title">What Our Community Offers</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h3>Pattern Library</h3>
                <p>Access hundreds of free and premium crochet patterns shared by community members.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>Discussion Forums</h3>
                <p>Ask questions, share tips, and get help from experienced crocheters.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎥</div>
                <h3>Video Tutorials</h3>
                <p>Learn new stitches and techniques with our step-by-step video guides.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎉</div>
                <h3>Virtual Meetups</h3>
                <p>Join weekly zoom crochet-alongs and monthly themed challenges.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🏆</div>
                <h3>Contests & Awards</h3>
                <p>Participate in monthly crochet contests and win amazing prizes.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛍️</div>
                <h3>Member Discounts</h3>
                <p>Get exclusive discounts on yarn, hooks, and crochet supplies.</p>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="events-section">
            <h2>📅 Upcoming Community Events</h2>
            <div class="events-list">
                <?php if (!empty($upcoming_events)): ?>
                    <?php foreach ($upcoming_events as $event): ?>
                        <div class="event-item">
                            <div class="event-info">
                                <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                                <p><?php echo htmlspecialchars($event['event_description']); ?></p>
                                <p>📍 <?php echo htmlspecialchars($event['location']); ?></p>
                            </div>
                            <div class="event-date"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                            <button class="btn-join-event" onclick="registerForEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['event_name']); ?>')">Join Event</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="event-item">
                        <div class="event-info">
                            <h4>More events coming soon!</h4>
                            <p>Check back later for upcoming community events.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Discussion Forum -->
        <div class="forum-section">
            <h2>💬 Hot Discussions</h2>
            <?php if (!empty($discussion_topics)): ?>
                <?php foreach ($discussion_topics as $topic): ?>
                    <div class="topic-item">
                        <div>
                            <div class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></div>
                            <div class="topic-meta">
                                Posted by <?php echo htmlspecialchars($topic['author_name'] ?? 'Anonymous'); ?> • 
                                <?php echo $topic['replies']; ?> replies • 
                                Last active <?php echo time_elapsed_string($topic['last_activity']); ?>
                            </div>
                        </div>
                        <button class="btn-discuss" onclick="joinDiscussion(<?php echo $topic['id']; ?>, '<?php echo htmlspecialchars($topic['title']); ?>')">Join Discussion</button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="topic-item">
                    <div>
                        <div class="topic-title">No discussions yet. Be the first to start!</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Join Community Form -->
        <div class="join-form-section">
            <h2><?php echo $is_member ? '✨ Update Your Profile' : '✨ Become a Member Today!'; ?></h2>
            
            <?php if (isset($_SESSION['community_message'])): ?>
                <div class="message message-<?php echo $_SESSION['community_message_type'] ?? 'success'; ?>">
                    <?php 
                        echo $_SESSION['community_message'];
                        unset($_SESSION['community_message']);
                        unset($_SESSION['community_message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($user_member['fullname'] ?? $username); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_member['email'] ?? $_SESSION['email'] ?? ''); ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" id="country" value="<?php echo htmlspecialchars($user_member['country'] ?? ''); ?>" placeholder="Your country">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($user_member['city'] ?? ''); ?>" placeholder="Your city">
                    </div>
                </div>
                <div class="form-group">
                    <label>Crochet Skill Level</label>
                    <select name="skill_level" id="skillLevel" required>
                        <option value="">Select your level</option>
                        <option value="beginner" <?php echo ($user_member['skill_level'] ?? '') == 'beginner' ? 'selected' : ''; ?>>Beginner (Just starting)</option>
                        <option value="intermediate" <?php echo ($user_member['skill_level'] ?? '') == 'intermediate' ? 'selected' : ''; ?>>Intermediate (Comfortable with basics)</option>
                        <option value="advanced" <?php echo ($user_member['skill_level'] ?? '') == 'advanced' ? 'selected' : ''; ?>>Advanced (Experienced crocheter)</option>
                        <option value="expert" <?php echo ($user_member['skill_level'] ?? '') == 'expert' ? 'selected' : ''; ?>>Expert (Design my own patterns)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>What would you like to learn/share?</label>
                    <textarea name="interests" id="interests" placeholder="I'm interested in..."><?php echo htmlspecialchars($user_member['interests'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="join_community" class="btn-submit"><?php echo $is_member ? '🌟 Update Profile 🌟' : '🌟 Join Community 🌟'; ?></button>
            </form>
        </div>

        <footer>
            <p>&copy; 2026 JosLee Crocs - Crafting Community Together 🧶</p>
        </footer>
    </div>

    <script>
        function registerForEvent(eventId, eventName) {
            alert(`You have successfully registered for "${eventName}"!\n\nCheck your email for details and reminders.`);
            // You can add AJAX call here to save registration to event_registrations table
        }
        
        function joinDiscussion(topicId, topicTitle) {
            alert(`Joining discussion: "${topicTitle}"\n\nYou will be redirected to the discussion forum.`);
            // You can redirect to a discussion page: window.location.href = "Discussion.php?id=" + topicId;
        }
    </script>
</body>
</html>

<?php
// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    
    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
