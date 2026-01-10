<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis 
* Licensed under the MIT License. 
*/

require "config.php"; // σύνδεση DB

// --------------------------------------------------
// 1. Παίρνουμε ένα URL από το χρήστη ή manual
// --------------------------------------------------
$url = isset($_GET['url']) ? $_GET['url'] : "https://example.com";

// --------------------------------------------------
// 2. Κατεβάζουμε το HTML της σελίδας
// --------------------------------------------------
$html = @file_get_contents($url);

if (!$html) {
    die("❌ Δεν μπόρεσα να κατεβάσω το URL: $url");
}

// --------------------------------------------------
// 3. Χρησιμοποιούμε DOMDocument για parsing HTML
// --------------------------------------------------
libxml_use_internal_errors(true); 
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();

$title = "";
$description = "";
$content = "";

// --------------------------------------------------
// 4. Τίτλος (tag: <title>)
// --------------------------------------------------
$nodes = $dom->getElementsByTagName("title");
if ($nodes->length > 0) {
    $title = $nodes->item(0)->textContent;
}

// --------------------------------------------------
// 5. Description (meta tag)
// --------------------------------------------------
$metas = $dom->getElementsByTagName("meta");
foreach ($metas as $meta) {
    if ($meta->getAttribute("name") === "description") {
        $description = $meta->getAttribute("content");
        break;
    }
}

// --------------------------------------------------
// 6. Κείμενο όλης της σελίδας (όχι HTML tags)
// --------------------------------------------------
$body = $dom->getElementsByTagName("body")->item(0);
if ($body) {
    $content = strip_tags($body->textContent);
}

// --------------------------------------------------
// 7. Αποθήκευση στη βάση
// --------------------------------------------------
$stmt = $conn->prepare("
    INSERT INTO pages (url, title, description, content)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("ssss", $url, $title, $description, $content);
$stmt->execute();

// --------------------------------------------------
// 8. Επιβεβαίωση
// --------------------------------------------------
echo "✅ Σελίδα αποθηκεύτηκε στη βάση επιτυχώς!<br>";
echo "📌 URL: $url<br>";
echo "📄 Title: $title<br>";
echo "📝 Description: $description<br>";
echo "📚 Content length: " . strlen($content) . " χαρακτήρες<br>";
?>
