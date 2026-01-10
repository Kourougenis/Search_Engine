<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis
* Licensed under the MIT License.
*/

require "config.php";

header('Content-Type: application/json; charset=UTF-8');

// 1. Παίρνουμε το q από το GET
if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 1) {
    echo json_encode([]);
    exit;
}

$q = strtolower(trim($_GET['q']));
$q = preg_replace('/[^a-zα-ωάέήίόύώ]+/u', ' ', $q);

// 2. Ψάχνουμε στα keywords
$sql = "
    SELECT keyword, SUM(frequency) AS score
    FROM keywords
    WHERE keyword LIKE ?
    GROUP BY keyword
    ORDER BY score DESC
    LIMIT 10
";

$stmt = $conn->prepare($sql);
$like = $q . '%'; // ξεκινάει με το q
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];

while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row['keyword'];
}

// 3. Επιστρέφουμε JSON
echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
