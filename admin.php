<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis
* Licensed under the MIT License.
*/

require "config.php";
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// -------------------------------
// SIMPLE ADMIN PASSWORD
// -------------------------------
$ADMIN_PASSWORD = "admin123";

// -------------------------------
// POST → LOGIN (μόνη περίπτωση πρόσβασης)
// -------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // -------------------------------
    // LOGOUT
    // -------------------------------
    if (isset($_POST['logout'], $_POST['csrf_token']) &&
        hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {

        session_unset();
        session_destroy();

        header("Location: admin.php");
    exit;
}


    $enteredPassword = trim($_POST["password"] ?? "");

    if ($enteredPassword === "") {
        header("Location: admin.php");
        exit;
    }

    if ($enteredPassword !== $ADMIN_PASSWORD) {
        $_SESSION["admin_error"] = true;
        header("Location: admin.php");
        exit;
    }

    // ✅ Σωστός κωδικός
    session_regenerate_id(true);
    $_SESSION["admin_logged_once"] = true;

} else {
    // ❌ ΚΑΘΕ απλό GET → reset login
    unset($_SESSION["admin_logged_once"]);
}

// -------------------------------
// FLASH ERROR (1 φορά)
// -------------------------------
$showError = isset($_SESSION["admin_error"]);
unset($_SESSION["admin_error"]);

// -------------------------------
// LOGIN GATE
// -------------------------------
$isAllowed = isset($_SESSION["admin_logged_once"]) && $_SESSION["admin_logged_once"] === true;

// -------------------------------
// LOGIN PAGE (ΠΑΝΤΑ πρώτη)
// -------------------------------
if (!$isAllowed):
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<button id="darkToggle" class="dark-toggle">🌙 Dark mode</button>

<div class="admin-container">
    <h1>Admin Login</h1>

    <?php if ($showError): ?>
        <p style="color:red;">⚠️ Λάθος κωδικός ⚠️</p>
    <?php endif; ?>

    <form method="post" action="admin.php" autocomplete="off">
        <input type="password" name="password" placeholder="Κωδικός admin 🔒" required>
        <br><br>
        <button type="submit">Είσοδος</button>
    </form>
</div>

<script src="assets/js/darkmode.js"></script>
</body>
</html>
<?php
exit;
endif;
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <form method="post" style="text-align:right;">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <button type="submit" name="logout">Logout</button>
</form>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<button id="darkToggle" class="dark-toggle">🌙 Dark mode</button>

<div class="admin-container">
    <h1>Admin Panel</h1>
    <p class="subtitle">Στατιστικά μηχανής αναζήτησης</p>

    <div class="admin-grid">

        <?php
        $resPages = $conn->query("SELECT COUNT(*) AS total FROM pages");
        $totalPages = $resPages->fetch_assoc()['total'];
        ?>
        <div class="card">
            <h2>Σελίδες:</h2>
            <p>Συνολικές: <strong><?php echo $totalPages; ?></strong></p>
        </div>

        <?php
        $resKeywords = $conn->query("SELECT COUNT(*) AS total FROM keywords");
        $totalKeywords = $resKeywords->fetch_assoc()['total'];
        ?>
        <div class="card">
            <h2>Keywords:</h2>
            <p>Συνολικά: <strong><?php echo $totalKeywords; ?></strong></p>
        </div>

        <div class="card">
            <h2>Top 10 Keywords:</h2>
            <ul>
                <?php
                $topRes = $conn->query("
                    SELECT keyword, SUM(frequency) AS freq
                    FROM keywords
                    GROUP BY keyword
                    ORDER BY freq DESC
                    LIMIT 10
                ");
                while ($row = $topRes->fetch_assoc()):
                ?>
                <li><?php echo htmlspecialchars($row['keyword']); ?> — <strong><?php echo $row['freq']; ?></strong></li>
                <?php endwhile; ?>
            </ul>
        </div>

        <div class="card">
            <h2>🕷 Τελευταίες Crawled Σελίδες 🕷</h2>
            <ol>
                <?php
                $lastPages = $conn->query("
                    SELECT url, title
                    FROM pages
                    ORDER BY id DESC
                    LIMIT 10
                ");
                while ($p = $lastPages->fetch_assoc()):
                ?>
                <li>
                    <a href="<?php echo $p['url']; ?>" target="_blank">
                        <?php echo htmlspecialchars($p['title']); ?>
                    </a>
                </li>
                <?php endwhile; ?>
            </ol>
        </div>

        <div class="card">
            <h2>Click Tracking:</h2>

            <?php
            $clickStats = $conn->query("
                SELECT pages.title, pages.url, COUNT(click_logs.id) AS clicks
                FROM click_logs
                JOIN pages ON click_logs.page_id = pages.id
                GROUP BY pages.id
                ORDER BY clicks DESC
                LIMIT 10
            ");

            ?>

            <ul>
                <?php while ($c = $clickStats->fetch_assoc()): ?>
                    <li>
                        <a href="<?php echo $c['url']; ?>" target="_blank">
                            <?php echo htmlspecialchars($c['title']); ?>
                        </a>
                        — <strong><?php echo $c['clicks']; ?></strong>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>


    </div>
</div>

<script src="assets/js/darkmode.js"></script>
</body>
</html>
