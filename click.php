<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis
* Licensed under the MIT License.
*/

require "config.php";

$pageId = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;
$query  = isset($_GET['q']) ? trim($_GET['q']) : "";
$url    = isset($_GET['url']) ? $_GET['url'] : "";

if ($pageId > 0 && $query !== "" && $url !== "") {

    $stmt = $conn->prepare("
        INSERT INTO click_logs (page_id, query, clicked_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->bind_param("is", $pageId, $query);
    $stmt->execute();
}

// ΑΣΦΑΛΕΙΑ: αν δεν υπάρχει URL, γύρνα πίσω
if ($url === "") {
    header("Location: search.php");
    exit;
}

header("Location: $url");
exit;
