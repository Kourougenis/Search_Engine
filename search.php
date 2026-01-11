<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis 
* Licensed under the MIT License. 
*/

require "config.php";

/* -------------------------------
   1. Initialization & Defaults
-------------------------------- */
$queryRaw = "";
$cleanWords = [];
$finalResults = [];
$totalResults = 0;
$totalPages = 0;

// Ρυθμίσεις σελιδοποίησης
$resultsPerPage = 10;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

/* -------------------------------
   2. Helper Functions
-------------------------------- */
function highlightText($text, $words) {
    // Καθαρισμός αρχικού κειμένου για ασφάλεια (XSS prevention)
    $safe = htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    
    if (empty($words)) return $safe;

    foreach ($words as $w) {
        if (mb_strlen($w, 'UTF-8') < 1) continue;
        // Case-insensitive replacement με υποστήριξη Unicode
        $safe = preg_replace(
            '/(' . preg_quote($w, '/') . ')/iu',
            '<span class="highlight">$1</span>',
            $safe
        );
    }
    return $safe;
}

/* -------------------------------
   3. SEARCH LOGIC
-------------------------------- */
if (isset($_GET['q']) && mb_strlen(trim($_GET['q']), 'UTF-8') >= 1) {

    $queryRaw = trim($_GET['q']);
    
    // Καθαρισμός του query για αναζήτηση
    $query = mb_strtolower($queryRaw, 'UTF-8');
    // Κρατάμε γράμματα (ελληνικά/αγγλικά) και αριθμούς
    $query = preg_replace('/[^a-z0-9α-ωάέήίόύώϊϋΐΰ\s]+/iu', ' ', $query);
    $query = preg_replace('/\s+/u', ' ', $query);
    $query = trim($query);

    $words = explode(" ", $query);
    foreach ($words as $w) {
        if (mb_strlen($w, 'UTF-8') >= 1) {
            $cleanWords[] = $w;
        }
    }

    if (count($cleanWords) > 0) {

        // --- A. COUNT TOTAL RESULTS (Για τη σελιδοποίηση) ---
        $placeholders = implode(" OR ", array_fill(0, count($cleanWords), "keywords.keyword LIKE ?"));
        
        $countSql = "
            SELECT COUNT(DISTINCT pages.id) AS total
            FROM keywords
            JOIN pages ON keywords.page_id = pages.id
            WHERE {$placeholders}
        ";

        $countStmt = $conn->prepare($countSql);
        $types = str_repeat("s", count($cleanWords));
        $params = array_map(fn($w) => "%$w%", $cleanWords);
        
        $countStmt->bind_param($types, ...$params);
        $countStmt->execute();
        $resCount = $countStmt->get_result()->fetch_assoc();
        $totalResults = (int)($resCount['total'] ?? 0);
        $countStmt->close();

        // Υπολογισμός σελίδων
        $totalPages = ($totalResults > 0) ? ceil($totalResults / $resultsPerPage) : 1;
        if ($currentPage > $totalPages) $currentPage = $totalPages;
        
        $offset = ($currentPage - 1) * $resultsPerPage;

        // --- B. FETCH RESULTS ---
        // Σημείωση: Το Score είναι άθροισμα συχνοτήτων (Frequency)
        $sql = "
            SELECT pages.id, pages.url, pages.title, pages.description,
                   SUM(keywords.frequency) AS score
            FROM keywords
            JOIN pages ON keywords.page_id = pages.id
            WHERE {$placeholders}
            GROUP BY pages.id
            ORDER BY score DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        // Προσθέτουμε τύπους για το LIMIT (i) και OFFSET (i)
        $bindTypes = $types . "ii"; 
        $params[] = $resultsPerPage;
        $params[] = $offset;

        $stmt->bind_param($bindTypes, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            // PHP Boosting: Προσθέτουμε πόντους αν η λέξη υπάρχει στον τίτλο
            $boost = 0;
            foreach ($cleanWords as $w) {
                if (mb_stripos($row['title'], $w, 0, 'UTF-8') !== false) $boost += 15;
                if (mb_stripos($row['description'], $w, 0, 'UTF-8') !== false) $boost += 5;
            }
            $row['score'] += $boost;
            $finalResults[] = $row;
        }
        $stmt->close();
        
        // (Προαιρετικά) Re-sort τα 10 αποτελέσματα της τρέχουσας σελίδας με βάση το νέο score
        usort($finalResults, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Engine Results</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Πρόσθετο CSS για τη Σελιδοποίηση */
        .pagination-container {
            margin-top: 30px;
            margin-bottom: 50px;
            text-align: center;
        }
        .pagination {
            display: inline-block;
            padding: 0;
            margin: 0;
        }
        .pagination a, .pagination span {
            color: #333;
            float: left;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 4px;
            transition: background-color .3s;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        .pagination a:hover:not(.active) {background-color: #ddd;}
        .pagination .disabled {
            color: #ccc;
            pointer-events: none;
            border-color: #eee;
        }
        
        /* Dark mode support για pagination */
        body.dark-mode .pagination a {
            background-color: #444;
            color: #fff;
            border-color: #555;
        }
        body.dark-mode .pagination a.active {
            background-color: #66bb6a;
            border-color: #66bb6a;
        }
        body.dark-mode .pagination a:hover:not(.active) {
            background-color: #555;
        }
        
        /* Fix για μεγάλα URLs σε μικρές οθόνες */
        .url {
            max-width: 100%;
            word-break: break-all;
            display: block;
        }
    </style>
</head>
<body>

<button id="darkToggle" class="dark-toggle">🌙 Dark mode</button>

<header class="site-header">
    <h1><a href="search.php" style="text-decoration:none; color:inherit;">Search Engine</a></h1>
    <p class="tagline">Fast and intelligent content search</p>
</header>

<div class="search-box">
    <form method="get" action="search.php">
        <input
            type="text"
            name="q"
            placeholder="Search..."
            value="<?php echo htmlspecialchars($queryRaw, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >
        <button type="submit">🔍︎</button>
    </form>
</div>

<?php if (!empty($queryRaw)): ?>
    <h2>Αποτελέσματα για: <em><?php echo htmlspecialchars($queryRaw); ?></em></h2>
    
    <div class="search-stats">
        <p>
            ၊၊||၊ Βρέθηκαν <strong><?php echo $totalResults; ?></strong> αποτελέσματα
            (Σελίδα <strong><?php echo $currentPage; ?></strong> από <strong><?php echo $totalPages; ?></strong>)
        </p>
    </div>
<?php endif; ?>

<?php if (!empty($queryRaw) && $totalResults === 0): ?>
    <div class="no-results">
        <p>Δεν βρέθηκαν αποτελέσματα. Δοκιμάστε άλλες λέξεις κλειδιά.</p>
    </div>
<?php endif; ?>

<div class="results-container">
    <?php foreach ($finalResults as $row): ?>
    <div class="result">
        <a href="click.php?page_id=<?php echo $row['id']; ?>&q=<?php echo urlencode($queryRaw); ?>&url=<?php echo urlencode($row['url']); ?>" target="_blank">
            <h3><?php echo highlightText($row['title'], $cleanWords); ?></h3>
        </a>
        <p><?php echo highlightText($row['description'], $cleanWords); ?></p>
        <div class="meta-info">
            <small class="url"><?php echo htmlspecialchars($row['url']); ?></small> 
            <span class="score-badge">Score: <?php echo (int)$row['score']; ?></span>
        </div>
        <hr>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination-container">
    <div class="pagination">
        
        <?php if ($currentPage > 1): ?>
            <a href="?<?php echo http_build_query(['q' => $queryRaw, 'page' => $currentPage - 1]); ?>">&laquo; Previous</a>
        <?php else: ?>
            <span class="disabled">&laquo; </span>
        <?php endif; ?>

        <?php
        $range = 2; 
        for ($i = 1; $i <= $totalPages; $i++):
            if ($i == 1 || $i == $totalPages || ($i >= $currentPage - $range && $i <= $currentPage + $range)):
        ?>
            <a href="?<?php echo http_build_query(['q' => $queryRaw, 'page' => $i]); ?>" 
               class="<?php echo ($i == $currentPage) ? 'active' : ''; ?>">
               <?php echo $i; ?>
            </a>
        <?php elseif ($i == $currentPage - $range - 1 || $i == $currentPage + $range + 1): ?>
            <span class="dots">...</span>
        <?php endif; endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="?<?php echo http_build_query(['q' => $queryRaw, 'page' => $currentPage + 1]); ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled"> &raquo;</span>
        <?php endif; ?>
        
    </div>
</div>
<?php endif; ?>

<script src="assets/js/darkmode.js"></script>
</body>
</html>