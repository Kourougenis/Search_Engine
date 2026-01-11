<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis
* Licensed under the MIT License.
*/
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Search Engine</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <button id="darkToggle" class="dark-toggle">🌙 Dark</button>

    <h1 style="font-size: 41px;">Search Engine</h1>

    <div style="position: relative; max-width: 400px; margin-left: 200px;">
        <form action="search.php" method="get" autocomplete="off">
            <input 
                type="text" 
                id="search-input" 
                name="q" 
                placeholder="Τι θες να ψάξεις;" 
                required
            >
            <button type="submit">Αναζήτηση</button>
        </form>

        <div id="autocomplete-box"></div>
    </div>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/darkmode.js"></script>

</body>
</html>