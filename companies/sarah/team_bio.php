<?php
declare(strict_types=1);

session_start();

// Constant(s)
const UNIVERSITY_NAME = 'Your University Name';
const SCHOOL_YEAR = '2025–2026';

// Team + members (use at least 3 variables of different types per member)
$teamName = 'Team Awesome';

$member1_name = 'Sarah Mai';
$member1_age = 20;            // int
$member1_gpa = 3.72;          // float
$member1_major = 'Software Engineering';

$member2_name = 'Alicia Kim';
$member2_age = 19;            // int
$member2_gpa = 3.90;          // float
$member2_major = 'Software Engineering';

// $member3_name = 'Sam';
// $member3_age = 21;            // int
// $member3_gpa = 3.88;          // float
// $member3_major = 'Cybersecurity';

// // (Optional) Add a 4th member if your group has 4
// $hasMember4 = true;
// $member4_name = 'Taylor';
// $member4_age = 22;            // int
// $member4_gpa = 3.61;          // float
// $member4_major = 'Software Engineering';

$members = [
  [
    'name' => $member1_name,
    'age' => $member1_age,
    'gpa' => $member1_gpa,
    'major' => $member1_major,
  ],
  [
    'name' => $member2_name,
    'age' => $member2_age,
    'gpa' => $member2_gpa,
    'major' => $member2_major,
  ],
  [
    'name' => $member3_name,
    'age' => $member3_age,
    'gpa' => $member3_gpa,
    'major' => $member3_major,
  ],
];

if ($hasMember4) {
  $members[] = [
    'name' => $member4_name,
    'age' => $member4_age,
    'gpa' => $member4_gpa,
    'major' => $member4_major,
  ];
}

// Forms / processing
$visitorName = '';
$greeting = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Greeting form
  if (isset($_POST['visitor_name'])) {
    $visitorName = trim((string)$_POST['visitor_name']);
    if ($visitorName !== '') {
      $safeName = htmlspecialchars($visitorName, ENT_QUOTES, 'UTF-8');
      $safeTeam = htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8');
      $greeting = "Welcome, {$safeName}! You are visiting {$safeTeam}'s page.";
    }
  }

  // Password form
  if (isset($_POST['group_password'])) {
    $password = (string)$_POST['group_password'];
    // Change this to whatever password your group decides on:
    $correctPassword = 'letmein';

    if (hash_equals($correctPassword, $password)) {
      $_SESSION['unlocked_fun_fact'] = true;
    } else {
      $_SESSION['unlocked_fun_fact'] = false;
    }
  }
}

$unlocked = !empty($_SESSION['unlocked_fun_fact']);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?> — Team Bio</title>
    <style>
      :root {
        --bg: #0b1020;
        --card: rgba(255, 255, 255, 0.06);
        --cardBorder: rgba(255, 255, 255, 0.12);
        --text: rgba(255, 255, 255, 0.92);
        --muted: rgba(255, 255, 255, 0.72);
        --accent: #7c5cff;
        --accent2: #20d3b0;
        --danger: #ff5c7c;
      }

      * { box-sizing: border-box; }
      body {
        margin: 0;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
        color: var(--text);
        background:
          radial-gradient(1200px 600px at 20% 10%, rgba(124, 92, 255, 0.28), transparent 60%),
          radial-gradient(900px 500px at 80% 0%, rgba(32, 211, 176, 0.20), transparent 55%),
          radial-gradient(1000px 700px at 50% 100%, rgba(255, 92, 124, 0.10), transparent 50%),
          var(--bg);
        min-height: 100vh;
      }

      .wrap {
        max-width: 980px;
        margin: 0 auto;
        padding: 28px 18px 54px;
      }

      header {
        display: grid;
        gap: 8px;
        margin-bottom: 18px;
        padding: 18px;
        border: 1px solid var(--cardBorder);
        background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
        border-radius: 16px;
      }

      header .kicker {
        color: var(--muted);
        font-size: 14px;
        letter-spacing: 0.3px;
      }

      header h1 {
        margin: 0;
        font-size: 28px;
        line-height: 1.15;
      }

      header .meta {
        color: var(--muted);
        font-size: 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
      }

      .grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 14px;
      }

      .card {
        grid-column: span 12;
        border: 1px solid var(--cardBorder);
        background: var(--card);
        border-radius: 16px;
        padding: 16px;
        backdrop-filter: blur(8px);
      }

      @media (min-width: 800px) {
        .card.members { grid-column: span 8; }
        .card.forms { grid-column: span 4; }
      }

      .members-list {
        display: grid;
        gap: 12px;
        margin-top: 12px;
      }

      .member {
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: 14px;
        padding: 12px 12px;
        background: rgba(0,0,0,0.10);
      }

      .member h3 {
        margin: 0 0 6px 0;
        font-size: 18px;
      }

      .pillrow {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .pill {
        font-size: 13px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.14);
        background: rgba(255,255,255,0.06);
        color: var(--muted);
      }

      .pill strong {
        color: var(--text);
        font-weight: 650;
      }

      form {
        display: grid;
        gap: 10px;
        margin-top: 10px;
      }

      label {
        font-size: 14px;
        color: var(--muted);
      }

      input[type="text"],
      input[type="password"] {
        width: 100%;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.18);
        background: rgba(0,0,0,0.20);
        color: var(--text);
        outline: none;
      }

      input[type="text"]:focus,
      input[type="password"]:focus {
        border-color: rgba(124, 92, 255, 0.55);
        box-shadow: 0 0 0 4px rgba(124, 92, 255, 0.14);
      }

      button {
        cursor: pointer;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.18);
        background: linear-gradient(135deg, rgba(124, 92, 255, 0.92), rgba(32, 211, 176, 0.76));
        color: #0b1020;
        font-weight: 700;
      }

      .message {
        margin-top: 10px;
        padding: 10px 12px;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,0.14);
        background: rgba(255,255,255,0.06);
        color: var(--text);
      }

      .message.bad {
        border-color: rgba(255, 92, 124, 0.45);
        background: rgba(255, 92, 124, 0.10);
      }

      .message.good {
        border-color: rgba(32, 211, 176, 0.45);
        background: rgba(32, 211, 176, 0.10);
      }

      footer {
        margin-top: 14px;
        color: var(--muted);
        font-size: 13px;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <header>
        <div class="kicker"><?php echo htmlspecialchars(UNIVERSITY_NAME, ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars(SCHOOL_YEAR, ENT_QUOTES, 'UTF-8'); ?></div>
        <h1><?php echo htmlspecialchars($teamName, ENT_QUOTES, 'UTF-8'); ?> — Bio Page</h1>
        <div class="meta">
          <div><strong>Members:</strong> <?php echo count($members); ?></div>
          <div><strong>Lab:</strong> PHP Team Bio Page</div>
        </div>
      </header>

      <div class="grid">
        <section class="card members">
          <h2 style="margin:0;">Meet the team</h2>
          <div class="members-list">
            <?php foreach ($members as $m): ?>
              <article class="member">
                <h3><?php echo htmlspecialchars((string)$m['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="pillrow">
                  <div class="pill"><strong>Age:</strong> <?php echo (int)$m['age']; ?></div>
                  <div class="pill"><strong>GPA:</strong> <?php echo number_format((float)$m['gpa'], 2); ?></div>
                  <div class="pill"><strong>Major:</strong> <?php echo htmlspecialchars((string)$m['major'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>

        <aside class="card forms">
          <h2 style="margin:0;">Visitor tools</h2>

          <form method="post" action="">
            <div>
              <label for="visitor_name">Your name</label>
              <input
                id="visitor_name"
                name="visitor_name"
                type="text"
                placeholder="Type your name…"
                value="<?php echo htmlspecialchars($visitorName, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="name"
              />
            </div>
            <button type="submit">Say hello</button>
          </form>

          <?php if ($greeting !== ''): ?>
            <div class="message good"><?php echo $greeting; ?></div>
          <?php endif; ?>

          <hr style="border:0;border-top:1px solid rgba(255,255,255,0.12); margin: 14px 0;">

          <form method="post" action="">
            <div>
              <label for="group_password">Group password (to unlock a secret)</label>
              <input
                id="group_password"
                name="group_password"
                type="password"
                placeholder="Enter password…"
                autocomplete="off"
              />
            </div>
            <button type="submit">Unlock</button>
          </form>

          <?php if (isset($_POST['group_password'])): ?>
            <?php if ($unlocked): ?>
              <div class="message good">Unlocked! Your session will remember this on refresh.</div>
            <?php else: ?>
              <div class="message bad">That password wasn’t correct. Try again.</div>
            <?php endif; ?>
          <?php endif; ?>

          <?php if ($unlocked): ?>
            <div class="message" style="margin-top: 12px;">
              <strong>Secret fun fact:</strong>
              Our group’s “power snack” is popcorn during late-night coding.
            </div>
          <?php endif; ?>
        </aside>
      </div>

      <footer>
        Tip: Edit <code>UNIVERSITY_NAME</code>, <code>$teamName</code>, and member info in <code>team_bio.php</code> to match your group.
      </footer>
    </div>
  </body>
</html>

