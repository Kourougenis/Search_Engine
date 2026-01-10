<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis 
* Licensed under the MIT License. 
*/

require "config.php";

// -------------------------------
// 1. Παίρνουμε το page_id από URL
// -------------------------------
if (!isset($_GET['id'])) {
    die("❌ Δεν δόθηκε page_id");
}

$page_id = intval($_GET['id']);

// -------------------------------
// 2. Παίρνουμε το content από τη βάση
// -------------------------------
$res = $conn->query("SELECT content FROM pages WHERE id = $page_id");

if ($res->num_rows == 0) {
    die("❌ Δεν βρέθηκε σελίδα με id = $page_id");
}

$row = $res->fetch_assoc();
$content = $row['content'];

// -------------------------------
// 3. Καθαρισμός κειμένου
// -------------------------------
$content = strtolower($content);                 // πεζά γράμματα
$content = preg_replace('/[^a-zα-ωάέίήόύώ]+/u', ' ', $content); // βγάζουμε σύμβολα

$words = explode(" ", $content);

// -------------------------------
// 4. Stopwords (άχρηστες λέξεις)
// -------------------------------
$stopwords = [
    "και","να","με","σε","την","τις","του","των","τον","το","από","για","ως",
    "the","is","a","an","of","on","in","to","that","this","with","it","be","or"
];

// -------------------------------
// 5. Μετράμε συχνότητες
// -------------------------------
$frequencies = [];

foreach ($words as $w) {
    $w = trim($w);
    if (strlen($w) < 2) continue;              // πολύ μικρές λέξεις
    if (in_array($w, $stopwords)) continue;    // άχρηστες

    if (!isset($frequencies[$w])) {
        $frequencies[$w] = 1;
    } else {
        $frequencies[$w]++;
    }
}

// -------------------------------
// 6. Αποθήκευση keywords στη βάση
// -------------------------------
$stmt = $conn->prepare("
    INSERT INTO keywords (page_id, keyword, frequency)
    VALUES (?, ?, ?)
");

foreach ($frequencies as $keyword => $freq) {
    $stmt->bind_param("isi", $page_id, $keyword, $freq);
    $stmt->execute();
}

// -------------------------------
// 7. Επιβεβαίωση
// -------------------------------
echo "✅ Indexing ολοκληρώθηκε!<br>";
echo "Σελίδα ID: $page_id<br>";
echo "Αποθηκευμένα keywords: " . count($frequencies) . "<br>";
