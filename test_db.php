<?php
/*
* Copyright (c) 2026 Aggelos Kourougenis 
* Licensed under the MIT License. 
*/

require "config.php";

echo "Σύνδεση OK<br>";

$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    echo $row[0] . "<br>";
}
