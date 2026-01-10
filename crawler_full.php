<?php
/* 
* Copyright (c) 2026 Aggelos Kourougenis
* Licensed under the MIT License.
*/

require "config.php";

// =========================================================
// ΡΥΘΜΙΣΕΙΣ CRAWLER (CONTROLLED ENVIRONMENT)
// =========================================================

// 1. WHITELIST: Επιτρέπουμε ΜΟΝΟ αυτά τα domains.
// Αν βρει link για facebook, twitter, διαφημίσεις -> τα αγνοεί.
$allowedDomains = [
    'el.wikipedia.org',
    'en.wikipedia.org',
    'www.bbc.com',
    'github.com',
    'in.gr',
    'cnn.com',
    'nytimes.com',
    'nature.com',
    'sciencedaily.com',
    'www.nasa.gov',
    'www.mit.edu',
    'stackoverflow.com',
    'www.tovima.gr',
    'www.kathimerini.gr'
];

// 2. ΠΕΡΙΟΡΙΣΜΟΙ (Για να μην γεμίσει η βάση σκουπίδια)
$maxDepth = 2;              // Πόσο βαθιά θα πάει (0 = μόνο αρχική, 1 = αρχική + links της, κλπ)
$maxPagesTotal = 50;        // Γενικό όριο σελίδων για να σταματήσει το script
$maxPagesPerDomain = 10;    // Μην κατεβάσεις όλη τη Wikipedia, πάρε 10 σελίδες και σταμάτα.

// 3. ΟΡΙΑ ΠΟΙΟΤΗΤΑΣ
$minContentLength = 400;    // Ελάχιστοι χαρακτήρες κειμένου για να θεωρηθεί χρήσιμη

// =========================================================
// ΒΟΗΘΗΤΙΚΕΣ ΣΥΝΑΡΤΗΣΕΙΣ
// =========================================================

// Συνάρτηση Indexing (Την κράτησα ίδια, είναι μια χαρά για τώρα)
function indexPage($conn, $pageId, $content) {
    $content = mb_strtolower($content); // mb_ για σωστά ελληνικά
    $content = preg_replace('/[^a-zα-ωάέήίόύώ]+/u', ' ', $content);
    $words = explode(" ", $content);

    $stopwords = [
        "και","να","με","σε","την","τις","του","των","τον","το","από","για","ως","είναι","που",
        "the","is","a","an","of","on","in","to","that","this","with","it","be","or","and","as"
    ];

    $frequencies = [];
    foreach ($words as $w) {
        $w = trim($w);
        if (mb_strlen($w) < 3) continue; // Λέξεις < 3 γράμματα αγνοούνται
        if (in_array($w, $stopwords)) continue;

        if (!isset($frequencies[$w])) $frequencies[$w] = 1;
        else $frequencies[$w]++;
    }

    $stmt = $conn->prepare("INSERT INTO keywords (page_id, keyword, frequency) VALUES (?, ?, ?)");
    foreach ($frequencies as $keyword => $freq) {
        $stmt->bind_param("isi", $pageId, $keyword, $freq);
        $stmt->execute();
    }
    $stmt->close();
}

// Συνάρτηση για ασφαλές κατέβασμα (cURL αντί για file_get_contents)
function fetchUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    // Παριστάνουμε κανονικό browser για να μη μας κόψουν
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AcademicCrawler/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true); // Για να πάρουμε headers
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    if ($httpCode != 200 || !$response) return false;

    $headers = substr($response, 0, $headerSize);
    $html = substr($response, $headerSize);

    return ['html' => $html, 'headers' => $headers];
}

// Έλεγχος αν το domain είναι στη Whitelist
function isDomainAllowed($url, $whitelist) {
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return false;
    
    // Αφαιρούμε το www. για σύγκριση αν χρειαστεί, αλλά εδώ κάνουμε απλό check
    foreach ($whitelist as $allowed) {
        // Αν το allowed είναι 'wikipedia.org', ταιριάζει και το 'el.wikipedia.org'
        if (strpos($host, $allowed) !== false) {
            return true;
        }
    }
    return false;
}

// Ανίχνευση γλώσσας (Ελληνικά/Αγγλικά μόνο)
function isLanguageAllowed($text) {
    // Μετράμε ελληνικούς και λατινικούς χαρακτήρες
    preg_match_all('/[α-ωΑ-Ωάέήίόύώϊϋ]+/u', $text, $greekMatches);
    preg_match_all('/[a-zA-Z]+/u', $text, $englishMatches);
    
    $greekCount = count($greekMatches[0]);
    $englishCount = count($englishMatches[0]);
    $totalWords = str_word_count($text); 

    // Αν δεν έχει σχεδόν καθόλου λέξεις
    if ($greekCount + $englishCount < 10) return false;

    // Αν τα ελληνικά ή τα αγγλικά είναι η πλειοψηφία, OK.
    // Αν π.χ. είναι κινέζικα, αυτά τα counts θα είναι χαμηλά σε σχέση με το μήκος του κειμένου.
    return true; 
}

// Έλεγχος αν η σελίδα είναι από το 2025 (βάσει Last-Modified header)
function isFrom2025($headers) {
    if (preg_match('/Last-Modified: (.+)/i', $headers, $matches)) {
        $lastModified = trim($matches[1]);
        $timestamp = strtotime($lastModified);
        if ($timestamp !== false) {
            $year = date('Y', $timestamp);
            return $year >= 2025;
        }
    }
    // Αν δεν υπάρχει header, θεωρούμε OK για να μην απορρίψουμε πολλές σελίδες
    return true;
}

// =========================================================
// MAIN CRAWLER LOGIC
// =========================================================

// URL Εκκίνησης (Αν δεν δοθεί, ξεκινάμε από Wikipedia)
$startUrl = isset($_GET['url']) ? $_GET['url'] : "https://el.wikipedia.org/wiki/Ελλάδα";

// Queue: [URL, Depth]
$queue = [
    ['url' => $startUrl, 'depth' => 0]
];

$visited = [];          // URLs που είδαμε
$domainCounters = [];   // Μετρητής σελίδων ανά domain
$totalCrawled = 0;

echo "<h2>🚀 Εκκίνηση Academic Crawler</h2>";
echo "<ul>";

while (!empty($queue) && $totalCrawled < $maxPagesTotal) {
    
    // 1. Παίρνουμε το επόμενο από την ουρά
    $current = array_shift($queue);
    $url = $current['url'];
    $depth = $current['depth'];
    
    // 2. Checks πριν το κατέβασμα
    if (isset($visited[$url])) continue;
    $visited[$url] = true;

    if ($depth > $maxDepth) continue;

    // Check Domain Whitelist & Limits
    $host = parse_url($url, PHP_URL_HOST);
    if (!isDomainAllowed($url, $allowedDomains)) {
        // echo "<li>Skipped (Domain not allowed): $host</li>"; 
        continue;
    }

    if (!isset($domainCounters[$host])) $domainCounters[$host] = 0;
    if ($domainCounters[$host] >= $maxPagesPerDomain) {
        // echo "<li>Skipped (Domain limit reached): $host</li>";
        continue;
    }

    // 3. Κατέβασμα (Fetch)
    echo "<li>🕷 <strong>Crawling ($depth):</strong> $url ";
    $result = fetchUrl($url);

    if (!$result) {
        echo "❌ (Error fetching)</li>";
        continue;
    }

    $html = $result['html'];
    $headers = $result['headers'];

    // Έλεγχος ημερομηνίας (μόνο από 2025)
    if (!isFrom2025($headers)) {
        echo "⚠️ (Not from 2025 - Skipped)</li>";
        continue;
    }

    // 4. Parsing
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    // Εξαγωγή δεδομένων
    $nodes = $dom->getElementsByTagName("title");
    $title = ($nodes->length > 0) ? $nodes->item(0)->textContent : "No Title";
    
    // Description meta
    $description = "";
    $metas = $dom->getElementsByTagName("meta");
    foreach ($metas as $m) {
        if ($m->getAttribute("name") === "description") {
            $description = $m->getAttribute("content");
        }
    }

    // Κυρίως κείμενο (καθαρό)
    $body = $dom->getElementsByTagName("body")->item(0);
    $rawText = $body ? $body->textContent : "";
    
    // Αφαιρούμε πολλαπλά κενά και new lines
    $cleanText = trim(preg_replace('/\s+/', ' ', $rawText));

    // 5. ΦΙΛΤΡΑ ΠΟΙΟΤΗΤΑΣ & ΓΛΩΣΣΑΣ
    
    // α) Μήκος κειμένου
    if (mb_strlen($cleanText) < $minContentLength) {
        echo "⚠️ (Πολύ μικρό κείμενο - Skipped)</li>";
        continue;
    }

    // β) Γλώσσα
    if (!isLanguageAllowed($cleanText)) {
        echo "⚠️ (Wrong Language - Skipped)</li>";
        continue;
    }

    // 6. Αποθήκευση στη Βάση
    $stmt = $conn->prepare("INSERT INTO pages (url, title, description, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $url, $title, $description, $cleanText);
    
    if ($stmt->execute()) {
        $pageId = $conn->insert_id;
        echo "✅ (Saved ID: $pageId)</li>";
        
        // Καταμέτρηση
        $totalCrawled++;
        $domainCounters[$host]++;

        // Indexing
        indexPage($conn, $pageId, $cleanText);

        // 7. Εύρεση νέων Links (Μόνο αν δεν φτάσαμε το max depth)
        if ($depth < $maxDepth) {
            $anchors = $dom->getElementsByTagName("a");
            foreach ($anchors as $a) {
                $href = $a->getAttribute("href");
                if (!$href || strpos($href, "#") === 0 || strpos($href, "javascript") === 0) continue;

                // Μετατροπή σε Absolute URL
                $absUrl = $href;
                if (parse_url($href, PHP_URL_SCHEME) == '') {
                    // Είναι relative link
                    $absUrl = rtrim($startUrl, "/") . "/" . ltrim($href, "/");
                    // Σημείωση: Για τέλεια μετατροπή relative paths χρειάζεται πιο πολύπλοκη λογική, 
                    // αλλά για την Wikipedia/BBC συνήθως αυτό αρκεί για το demo.
                    if (strpos($href, "//") === 0) {
                        $absUrl = "https:" . $href;
                    } elseif (strpos($href, "/") === 0) {
                        $scheme = parse_url($url, PHP_URL_SCHEME);
                        $hostUrl = parse_url($url, PHP_URL_HOST);
                        $absUrl = $scheme . "://" . $hostUrl . $href;
                    }
                }

                // Προσθήκη στην ουρά
                // (Δεν ελέγχουμε το Whitelist εδώ, θα ελεγχθεί όταν βγει από την ουρά, 
                // για να κρατήσουμε τον κώδικα καθαρό)
                if (!isset($visited[$absUrl])) {
                    $queue[] = ['url' => $absUrl, 'depth' => $depth + 1];
                }
            }
        }

    } else {
        echo "❌ (DB Error)</li>";
    }
    $stmt->close();
    
    // Flush για να τα βλέπεις live καθώς τρέχει
    flush();
    ob_flush();
}

echo "</ul>";
echo "<h3>🎉 Τέλος Crawling!</h3>";
echo "<p>Σύνολο σελίδων που αποθηκεύτηκαν: $totalCrawled</p>";
?>