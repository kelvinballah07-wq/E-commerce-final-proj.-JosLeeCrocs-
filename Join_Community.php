<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

require_once 'Connection.php';

$username = $_SESSION['username'] ?? 'User';
$user_id  = $_SESSION['user_id'];

/* ── Stats ─────────────────────────────────────────── */
$active_members = $conn->query("SELECT COUNT(*) as c FROM community_members WHERE status='active'")->fetch_assoc()['c'] ?? $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$patterns_shared = $conn->query("SELECT COUNT(*) as c FROM discussion_topics")->fetch_assoc()['c'] ?? 25;
$countries_count = $conn->query("SELECT COUNT(DISTINCT country) as c FROM community_members WHERE country IS NOT NULL AND country != ''")->fetch_assoc()['c'] ?: 15;
$events_yearly   = $conn->query("SELECT COUNT(*) as c FROM community_events WHERE YEAR(event_date)=YEAR(NOW()) AND status!='cancelled'")->fetch_assoc()['c'] ?: 12;

/* ── Upcoming events ────────────────────────────────── */
$ev_res = $conn->query("SELECT * FROM community_events WHERE event_date >= NOW() AND status='upcoming' ORDER BY event_date ASC LIMIT 4");
$upcoming_events = [];
if ($ev_res && $ev_res->num_rows) while ($r = $ev_res->fetch_assoc()) $upcoming_events[] = $r;

/* ── Discussion topics ──────────────────────────────── */
$dt_res = $conn->query("SELECT dt.*, u.username as author_name FROM discussion_topics dt LEFT JOIN users u ON dt.created_by=u.id WHERE dt.status='active' ORDER BY dt.last_activity DESC LIMIT 4");
$discussion_topics = [];
if ($dt_res && $dt_res->num_rows) while ($r = $dt_res->fetch_assoc()) $discussion_topics[] = $r;

/* ── Join / update form ─────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_community'])) {
    $fullname    = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);
    $skill_level = mysqli_real_escape_string($conn, $_POST['skill_level']);
    $interests   = mysqli_real_escape_string($conn, $_POST['interests']);
    $country     = mysqli_real_escape_string($conn, $_POST['country'] ?? '');
    $city        = mysqli_real_escape_string($conn, $_POST['city']    ?? '');

    $chk = $conn->prepare("SELECT id FROM community_members WHERE user_id=?");
    $chk->bind_param("i", $user_id); $chk->execute();
    $exists = $chk->get_result()->num_rows > 0; $chk->close();

    if ($exists) {
        $s = $conn->prepare("UPDATE community_members SET fullname=?,email=?,skill_level=?,interests=?,country=?,city=?,last_active=NOW() WHERE user_id=?");
        $s->bind_param("ssssssi",$fullname,$email,$skill_level,$interests,$country,$city,$user_id);
        $_SESSION['community_message'] = $s->execute() ? "Profile updated!" : "Error: ".$conn->error;
    } else {
        $s = $conn->prepare("INSERT INTO community_members (user_id,fullname,email,skill_level,interests,country,city,join_date,status,last_active) VALUES (?,?,?,?,?,?,?,NOW(),'active',NOW())");
        $s->bind_param("issssss",$user_id,$fullname,$email,$skill_level,$interests,$country,$city);
        $_SESSION['community_message'] = $s->execute() ? "Welcome to the community, $fullname!" : "Error: ".$conn->error;
    }
    $s->close();
    header("Location: Join_Community.php"); exit();
}

/* ── Membership status ──────────────────────────────── */
$ms = $conn->prepare("SELECT * FROM community_members WHERE user_id=?");
$ms->bind_param("i",$user_id); $ms->execute();
$ms_res    = $ms->get_result();
$user_member = $ms_res->fetch_assoc();
$is_member = ($ms_res->num_rows > 0);
$ms->close();

/* ── Time-elapsed helper ────────────────────────────── */
function time_ago($dt) {
    $diff = (new DateTime())->diff(new DateTime($dt));
    foreach (['y'=>'year','m'=>'month','d'=>'day','h'=>'hour','i'=>'minute','s'=>'second'] as $k=>$v)
        if ($diff->$k) return $diff->$k." $v".($diff->$k>1?'s':'')." ago";
    return "just now";
}

/* ── Curated YouTube crochet tutorials ─────────────── */
$tutorials = [
    [
        'id'      => 'aAxGTnVNJiE',
        'title'   => 'How to Crochet for Absolute Beginners: Part 1',
        'channel' => 'Bella Coco Crochet'
    ],
    [
        'id'      => 'vVMEnfilMTo',
        'title'   => 'How to Crochet for Absolute Beginners (Step-by-Step)',
        'channel' => 'Sarah Maker'
    ],
    [
        'id'      => 'zzWX2dx8ufc',
        'title'   => 'LEARN TO CROCHET — Slow Step-By-Step for Beginners',
        'channel' => 'Sewrella'
    ],
    [
        'id'      => 'axV4cFeMlFY',
        'title'   => 'How to Crochet a Granny Square — No Magic Ring Needed',
        'channel' => 'TL Yarn Crafts'
    ],
    [
        'id'      => 'z_CS3TUADYg',
        'title'   => 'Easy Magic Ring / Magic Circle — Crochet Tutorial',
        'channel' => 'Bella Coco Crochet'
    ],
    [
        'id'      => 'lCcHmw0FGsc',
        'title'   => '10 Easy Crochet Projects You Can Make in 2025',
        'channel' => 'Easy Crochet'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Community - JosLee Crocs</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
:root{
  --purple:#6a11cb; --blue:#2575fc; --honey:#E8B86B;
  --cream:#FEF7E8; --terracotta:#D97A5C; --olive:#7A8B5E;
  --dark:#1a1a2e; --card:#fff; --radius:20px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#ced6fc 0%,#764ba2 100%);min-height:100vh;padding:20px;}

/* ── NAV ── */
.navbar{background:#fff;padding:1rem 2rem;border-radius:50px;display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;box-shadow:0 5px 20px rgba(0,0,0,.1);flex-wrap:wrap;gap:1rem;}
.logo{font-size:1.4rem;font-weight:700;color:var(--purple);}
.nav-links{display:flex;gap:1.5rem;flex-wrap:wrap;}
.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color .2s;}
.nav-links a:hover{color:var(--purple);}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,var(--purple),var(--blue));border-radius:30px;padding:3rem;text-align:center;color:#fff;margin-bottom:2rem;}
.hero h1{font-size:2.4rem;margin-bottom:.8rem;}
.hero p{font-size:1.1rem;opacity:.9;}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.5rem;margin-bottom:2rem;}
.stat-card{background:#fff;border-radius:var(--radius);padding:1.5rem;text-align:center;box-shadow:0 5px 15px rgba(0,0,0,.08);}
.stat-number{font-size:2.4rem;font-weight:700;color:var(--purple);}
.stat-label{color:#666;margin-top:.4rem;}

/* ── SECTION TITLE ── */
.section-title{text-align:center;font-size:1.9rem;color:#fff;margin:2rem 0 1.5rem;}

/* ── FEATURES ── */
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:3rem;}
.feature-card{background:#fff;border-radius:var(--radius);padding:2rem;text-align:center;transition:transform .3s,box-shadow .3s;box-shadow:0 5px 15px rgba(0,0,0,.08);cursor:pointer;text-decoration:none;display:block;color:inherit;}
.feature-card:hover{transform:translateY(-8px);box-shadow:0 15px 30px rgba(0,0,0,.15);}
.feature-icon{font-size:3rem;margin-bottom:1rem;}
.feature-card h3{color:#333;margin-bottom:.8rem;}
.feature-card p{color:#666;line-height:1.6;font-size:.95rem;}
.feature-badge{display:inline-block;background:var(--purple);color:#fff;font-size:.75rem;padding:3px 10px;border-radius:20px;margin-top:.8rem;}
.feature-badge.green{background:var(--olive);}
.feature-badge.orange{background:#ff9800;}

/* ── MEET BANNER ── */
.meet-banner{background:linear-gradient(135deg,#00897b,#00bcd4);border-radius:var(--radius);padding:2rem;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;}
.meet-banner h2{font-size:1.6rem;}
.meet-banner p{opacity:.9;margin-top:.4rem;}
.btn-meet{background:#fff;color:#00897b;font-weight:700;padding:.9rem 2rem;border-radius:50px;text-decoration:none;font-size:1rem;transition:transform .2s,box-shadow .2s;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:8px;}
.btn-meet:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.2);}

/* ── YOUTUBE TUTORIALS ── */
.tutorials-section{background:#fff;border-radius:30px;padding:2rem;margin-bottom:2rem;}
.tutorials-section h2{color:#333;margin-bottom:1.5rem;text-align:center;}
.tutorials-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem;}
.tutorial-card{border-radius:var(--radius);overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.1);transition:transform .3s;}
.tutorial-card:hover{transform:translateY(-4px);}
.thumbnail-wrap{position:relative;cursor:pointer;}
.thumbnail-wrap img{width:100%;display:block;aspect-ratio:16/9;object-fit:cover;}
.play-btn{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:56px;height:56px;background:rgba(255,0,0,.85);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;pointer-events:none;}
.tutorial-info{padding:1rem;}
.tutorial-info h4{color:#333;margin-bottom:.3rem;font-size:.95rem;}
.tutorial-info span{color:#888;font-size:.82rem;}
.btn-watch{display:inline-flex;align-items:center;gap:6px;background:#ff0000;color:#fff;padding:.5rem 1.2rem;border-radius:20px;font-size:.85rem;font-weight:600;text-decoration:none;margin-top:.8rem;transition:background .2s;}
.btn-watch:hover{background:#cc0000;}

/* ── VIDEO MODAL ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:#000;border-radius:16px;overflow:hidden;width:90%;max-width:860px;position:relative;}
.modal-box iframe{width:100%;aspect-ratio:16/9;border:none;}
.modal-close{position:absolute;top:10px;right:14px;background:rgba(255,255,255,.15);color:#fff;border:none;border-radius:50%;width:36px;height:36px;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;z-index:10;}
.modal-close:hover{background:rgba(255,255,255,.3);}

/* ── EVENTS ── */
.events-section{background:#fff;border-radius:30px;padding:2rem;margin-bottom:2rem;}
.events-section h2{color:#333;margin-bottom:1.5rem;text-align:center;}
.event-item{display:flex;justify-content:space-between;align-items:center;padding:1rem;background:#f8f9fa;border-radius:15px;margin-bottom:.8rem;flex-wrap:wrap;gap:1rem;}
.event-info h4{color:#333;margin-bottom:.3rem;}
.event-info p{color:#666;font-size:.9rem;}
.event-date{background:var(--purple);color:#fff;padding:.4rem 1rem;border-radius:25px;font-size:.85rem;}
.btn-join-event{background:#ff9800;color:#fff;border:none;padding:.5rem 1.4rem;border-radius:25px;cursor:pointer;font-weight:600;transition:background .2s;display:inline-flex;align-items:center;gap:6px;}
.btn-join-event:hover{background:#e68900;}

/* ── FORUM ── */
.forum-section{background:#fff;border-radius:30px;padding:2rem;margin-bottom:2rem;}
.forum-section h2{color:#333;margin-bottom:1.5rem;text-align:center;}
.topic-item{padding:1rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;}
.topic-item:last-child{border-bottom:none;}
.topic-title{font-weight:600;color:#333;}
.topic-meta{color:#888;font-size:.8rem;margin-top:.2rem;}
.btn-discuss{background:var(--olive);color:#fff;border:none;padding:.4rem 1.1rem;border-radius:20px;cursor:pointer;font-weight:600;transition:background .2s;}
.btn-discuss:hover{background:#5a7a45;}

/* ── JOIN FORM ── */
.join-form-section{background:#fff;border-radius:30px;padding:2rem;margin-bottom:2rem;}
.join-form-section h2{color:#333;margin-bottom:1.5rem;text-align:center;}
.form-group{margin-bottom:1.4rem;}
.form-group label{display:block;margin-bottom:.4rem;color:#333;font-weight:500;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:.8rem;border:1px solid #ddd;border-radius:10px;font-size:1rem;transition:border-color .2s;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--purple);}
.form-group textarea{resize:vertical;min-height:100px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.btn-submit{background:linear-gradient(135deg,var(--purple),var(--blue));color:#fff;border:none;padding:1rem 2rem;border-radius:50px;font-size:1rem;font-weight:700;cursor:pointer;width:100%;transition:transform .2s;}
.btn-submit:hover{transform:translateY(-2px);}
.message{padding:1rem;border-radius:10px;margin-bottom:1rem;text-align:center;}
.message-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
.message-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}

/* ── WHATSAPP CTA ── */
.whatsapp-banner{background:linear-gradient(135deg,#25d366,#128c7e);border-radius:var(--radius);padding:1.8rem 2rem;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:2rem;}
.whatsapp-banner h3{font-size:1.4rem;}
.whatsapp-banner p{opacity:.9;font-size:.95rem;margin-top:.3rem;}
.btn-whatsapp{background:#fff;color:#25d366;font-weight:700;padding:.8rem 1.8rem;border-radius:50px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:transform .2s;}
.btn-whatsapp:hover{transform:translateY(-2px);}

footer{text-align:center;color:#fff;margin-top:2rem;padding:1rem;}

@media(max-width:768px){
  .navbar{flex-direction:column;text-align:center;}
  .hero h1{font-size:1.7rem;}
  .form-row{grid-template-columns:1fr;}
  .meet-banner{flex-direction:column;text-align:center;}
  .whatsapp-banner{flex-direction:column;text-align:center;}
}
</style>
</head>
<body>
<div class="container" style="max-width:1200px;margin:0 auto;">

  <!-- NAV -->
  <nav class="navbar">
    <div class="logo">🧶 JosLee Crocs</div>
    <div class="nav-links">
      <a href="Dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
      <a href="Service.php"><i class="fas fa-store"></i> Shop</a>
      <a href="Contact.php"><i class="fas fa-envelope"></i> Contact</a>
    </div>
    <span>👋 <?php echo htmlspecialchars($username); ?></span>
  </nav>

  <!-- HERO -->
  <div class="hero">
    <h1>🧶 Welcome to the JosLee Crocs Community!</h1>
    <p>Join thousands of crochet enthusiasts — share patterns, join live sessions, and grow together</p>
  </div>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-number"><?php echo number_format($active_members); ?>+</div><div class="stat-label">Active Members</div></div>
    <div class="stat-card"><div class="stat-number"><?php echo number_format($patterns_shared); ?>+</div><div class="stat-label">Patterns Shared</div></div>
    <div class="stat-card"><div class="stat-number"><?php echo number_format($countries_count); ?>+</div><div class="stat-label">Countries</div></div>
    <div class="stat-card"><div class="stat-number"><?php echo number_format($events_yearly); ?>+</div><div class="stat-label">Events Yearly</div></div>
  </div>

  <!-- GOOGLE MEET LIVE BANNER -->
  <div class="meet-banner">
    <div>
      <h2><i class="fas fa-video"></i> Join Our Weekly Live Crochet Session</h2>
      <p>Every Saturday at 10:00 AM (Kigali time) — beginners welcome!</p>
    </div>
    <a href="https://meet.google.com/landing" target="_blank" rel="noopener" class="btn-meet">
      <i class="fas fa-video"></i> Join Google Meet Now
    </a>
  </div>

  <!-- FEATURES -->
  <h2 class="section-title">What Our Community Offers</h2>
  <div class="features-grid">

    <a href="https://www.ravelry.com/patterns/library" target="_blank" rel="noopener" class="feature-card">
      <div class="feature-icon">📚</div>
      <h3>Pattern Library</h3>
      <p>Thousands of free and premium crochet patterns on Ravelry — the world's largest knit &amp; crochet community.</p>
      <span class="feature-badge">Open Ravelry →</span>
    </a>

    <a href="https://www.reddit.com/r/crochet/" target="_blank" rel="noopener" class="feature-card">
      <div class="feature-icon">💬</div>
      <h3>Discussion Forum</h3>
      <p>Ask questions, share WIPs, and get advice from 1M+ crocheters on Reddit's r/crochet community.</p>
      <span class="feature-badge">Open Reddit →</span>
    </a>

    <a href="#tutorials" class="feature-card" onclick="document.getElementById('tutorials').scrollIntoView({behavior:'smooth'});return false;">
      <div class="feature-icon">🎥</div>
      <h3>Video Tutorials</h3>
      <p>Curated YouTube tutorials from the world's best crochet teachers — from beginner basics to advanced techniques.</p>
      <span class="feature-badge green">Watch Below →</span>
    </a>

    <a href="https://meet.google.com/landing" target="_blank" rel="noopener" class="feature-card">
      <div class="feature-icon">🎉</div>
      <h3>Virtual Meetups</h3>
      <p>Join our weekly Google Meet crochet-alongs. See each other's work, get help in real time.</p>
      <span class="feature-badge">Join Google Meet →</span>
    </a>

    <a href="https://www.pinterest.com/search/pins/?q=crochet+contest" target="_blank" rel="noopener" class="feature-card">
      <div class="feature-icon">🏆</div>
      <h3>Contests &amp; Awards</h3>
      <p>Monthly crochet challenges and contests. Browse inspiration and submit your entries on Pinterest.</p>
      <span class="feature-badge orange">Browse on Pinterest →</span>
    </a>

    <a href="Service.php" class="feature-card">
  <div class="feature-icon">🛍️</div>
  <h3>Member Discounts</h3>
  <p>Exclusive deals on yarn and knitted products — one of the best online crochet supply stores.</p>
  <span class="feature-badge">Shop →</span>
</a>

  </div>

  <!-- YOUTUBE TUTORIALS -->
  <div class="tutorials-section" id="tutorials">
    <h2><i class="fab fa-youtube" style="color:#ff0000"></i> Crochet Video Tutorials</h2>
    <div class="tutorials-grid">
      <?php foreach ($tutorials as $t): ?>
      <div class="tutorial-card">
        <div class="thumbnail-wrap" onclick="openVideo('<?php echo $t['id']; ?>')">
          <img src="https://img.youtube.com/vi/<?php echo $t['id']; ?>/hqdefault.jpg" alt="<?php echo htmlspecialchars($t['title']); ?>" loading="lazy">
          <div class="play-btn"><i class="fas fa-play" style="margin-left:4px"></i></div>
        </div>
        <div class="tutorial-info">
          <h4><?php echo htmlspecialchars($t['title']); ?></h4>
          <span><?php echo htmlspecialchars($t['channel']); ?></span><br>
          <a href="https://www.youtube.com/watch?v=<?php echo $t['id']; ?>" target="_blank" rel="noopener" class="btn-watch">
            <i class="fab fa-youtube"></i> Watch on YouTube
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:1.5rem;">
      <a href="https://www.youtube.com/results?search_query=crochet+tutorial+beginners" target="_blank" rel="noopener"
         style="background:linear-gradient(135deg,#ff0000,#cc0000);color:#fff;padding:.9rem 2.2rem;border-radius:50px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
        <i class="fab fa-youtube"></i> Browse More on YouTube
      </a>
    </div>
  </div>

  <!-- VIDEO MODAL -->
  <div class="modal-overlay" id="videoModal">
    <div class="modal-box">
      <button class="modal-close" onclick="closeVideo()"><i class="fas fa-times"></i></button>
      <iframe id="modalIframe" src="" allowfullscreen allow="autoplay; encrypted-media"></iframe>
    </div>
  </div>

  <!-- WHATSAPP GROUP -->
  <div class="whatsapp-banner">
    <div>
      <h3><i class="fab fa-whatsapp"></i> Join Our WhatsApp Group</h3>
      <p>Get daily crochet tips, pattern drops, and event reminders right in your WhatsApp!</p>
    </div>
    <a href="https://wa.me/250798696026?text=Hi!%20I%20want%20to%20join%20the%20JosLee%20Crocs%20community%20WhatsApp%20group%20🧶" target="_blank" rel="noopener" class="btn-whatsapp">
      <i class="fab fa-whatsapp"></i> Message Us on WhatsApp
    </a>
  </div>

  <!-- UPCOMING EVENTS -->
  <div class="events-section">
    <h2>📅 Upcoming Community Events</h2>
    <div>
      <?php if (!empty($upcoming_events)): ?>
        <?php foreach ($upcoming_events as $ev): ?>
        <div class="event-item">
          <div class="event-info">
            <h4><?php echo htmlspecialchars($ev['event_name']); ?></h4>
            <p><?php echo htmlspecialchars($ev['event_description']); ?></p>
            <p>📍 <?php echo htmlspecialchars($ev['location']); ?></p>
          </div>
          <span class="event-date"><?php echo date('M d, Y', strtotime($ev['event_date'])); ?></span>
          <button class="btn-join-event" onclick="joinEvent('<?php echo htmlspecialchars($ev['event_name']); ?>')">
            <i class="fas fa-video"></i> Join Event
          </button>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="event-item">
          <div class="event-info">
            <h4>🗓️ Weekly Live Crochet Session — Every Saturday 10 AM</h4>
            <p>Join our recurring Google Meet session open to all skill levels.</p>
          </div>
          <span class="event-date">Every Sat</span>
          <a href="https://meet.google.com/landing" target="_blank" rel="noopener" class="btn-join-event">
            <i class="fas fa-video"></i> Join Meet
          </a>
        </div>
        <div class="event-item">
          <div class="event-info">
            <h4>🧶 Monthly Crochet-Along Challenge</h4>
            <p>Join our monthly project challenge — this month: Granny Square Blanket!</p>
          </div>
          <span class="event-date">Monthly</span>
          <a href="https://www.ravelry.com/groups/kal-cal-central" target="_blank" rel="noopener" class="btn-join-event">
            <i class="fas fa-external-link-alt"></i> Join on Ravelry
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- DISCUSSIONS -->
  <div class="forum-section">
    <h2>💬 Hot Discussions</h2>
    <?php if (!empty($discussion_topics)): ?>
      <?php foreach ($discussion_topics as $t): ?>
      <div class="topic-item">
        <div>
          <div class="topic-title"><?php echo htmlspecialchars($t['title']); ?></div>
          <div class="topic-meta">
            By <?php echo htmlspecialchars($t['author_name'] ?? 'Anonymous'); ?> •
            <?php echo $t['replies']; ?> replies •
            <?php echo time_ago($t['last_activity']); ?>
          </div>
        </div>
        <button class="btn-discuss" onclick="openDiscussion(<?php echo $t['id']; ?>)">
          <i class="fas fa-comments"></i> Join Discussion
        </button>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="topic-item">
        <div>
          <div class="topic-title">No discussions yet — start the first one on Reddit!</div>
        </div>
        <a href="https://www.reddit.com/r/crochet/submit" target="_blank" rel="noopener" class="btn-discuss">
          <i class="fas fa-paper-plane"></i> Post on Reddit
        </a>
      </div>
    <?php endif; ?>
  </div>

  <!-- JOIN FORM -->
  <div class="join-form-section">
    <h2><?php echo $is_member ? '✨ Update Your Profile' : '✨ Become a Member Today!'; ?></h2>

    <?php if (isset($_SESSION['community_message'])): ?>
      <div class="message message-success"><?php echo $_SESSION['community_message']; unset($_SESSION['community_message']); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_member['fullname'] ?? $username); ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user_member['email'] ?? $_SESSION['email'] ?? ''); ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Country</label>
          <input type="text" name="country" value="<?php echo htmlspecialchars($user_member['country'] ?? ''); ?>" placeholder="Your country">
        </div>
        <div class="form-group">
          <label>City</label>
          <input type="text" name="city" value="<?php echo htmlspecialchars($user_member['city'] ?? ''); ?>" placeholder="Your city">
        </div>
      </div>
      <div class="form-group">
        <label>Crochet Skill Level</label>
        <select name="skill_level" required>
          <option value="">Select your level</option>
          <?php foreach (['beginner'=>'Beginner (Just starting)','intermediate'=>'Intermediate (Comfortable with basics)','advanced'=>'Advanced (Experienced)','expert'=>'Expert (Design my own patterns)'] as $val=>$label): ?>
            <option value="<?php echo $val; ?>" <?php echo ($user_member['skill_level'] ?? '') == $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>What would you like to learn or share?</label>
        <textarea name="interests" placeholder="I'm interested in..."><?php echo htmlspecialchars($user_member['interests'] ?? ''); ?></textarea>
      </div>
      <button type="submit" name="join_community" class="btn-submit">
        <?php echo $is_member ? '🌟 Update Profile' : '🌟 Join Community'; ?>
      </button>
    </form>
  </div>

  <footer>
    <p>&copy; 2026 JosLee Crocs — Crafting Community Together 🧶</p>
  </footer>
</div>

<!-- SCRIPTS -->
<script>
function openVideo(id) {
  document.getElementById('modalIframe').src = 'https://www.youtube.com/embed/' + id + '?autoplay=1';
  document.getElementById('videoModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeVideo() {
  document.getElementById('modalIframe').src = '';
  document.getElementById('videoModal').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('videoModal').addEventListener('click', function(e) {
  if (e.target === this) closeVideo();
});

function joinEvent(name) {
  if (confirm('Join "' + name + '" via Google Meet?\n\nClick OK to open Google Meet.')) {
    window.open('https://meet.google.com/landing', '_blank');
  }
}

function openDiscussion(id) {
  window.open('https://www.reddit.com/r/crochet/', '_blank');
}
</script>
</body>
</html>
