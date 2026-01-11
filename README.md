# PHP Search Engine
A full-stack Search Engine built from scratch using PHP and MySQL. This project features a web crawler, an indexing algorithm, a search interface with ranking logic, and an admin panel for analytics.

> **Live Demo:** [Click here to try the Search Engine](https://aggelos.infinityfree.me/search-engine/search.php)

<details open> <summary><strong> Project Screenshots (Click to collapse/expand)</strong></summary>


<h3>1. Search Interface (Dark Mode)</h3>


<img src="./screenshots/home.PNG" alt="Search Home Page" width="700">



<h3>2. Search Results with Highlighting</h3>


<img src="./screenshots/results.PNG" alt="Search Results" width="700">



<h3>3. Admin Analytics Panel</h3>


<img src="./screenshots/admin.PNG" alt="Admin Dashboard" width="700">

</details>

## Features
Web Crawler: Fetches pages, parses HTML, respects robots.txt logic, and follows links recursively (crawler_full.php).

Indexer: Tokenizes content, removes stopwords, and calculates keyword frequency.

Search Algorithm:

Matches keywords against the indexed database.

Ranking System: Scores results based on keyword frequency + Title/Description boosting.

Highlights search terms in results.

Admin Panel: View crawl stats, top keywords, and click tracking logs.

UI/UX: Responsive design with Dark Mode toggle and Live Autocomplete.

## Live Demo Links
You can test the project live on the production server (InfinityFree):

## Search Interface: https://aggelos.infinityfree.me/search-engine/search.php

## Crawler Mechanism: https://aggelos.infinityfree.me/search-engine/crawler_full.php

## Admin Panel: https://aggelos.infinityfree.me/search-engine/admin.php

**Admin Panel Login Password:** admin123

## Installation (Run Locally)
To run this project on your local machine (e.g., using XAMPP, WAMP, or Docker), follow these steps:

<h3>1. Clone the Repository</h3>
git clone https://github.com/Kourougenis/Search_Engine.git cd Search_Engine

<h3>2. Database Setup</h3>
Open phpMyAdmin (usually http://localhost/phpmyadmin).

Create a new database named search_engine.

Import the provided database_schema_for_search_engine.sql file located in the root directory.

<h3>3. Configuration</h3>
Open the config.php file.

Ensure the database credentials match your local setup:

$host = "localhost"; $user = "root"; $pass = ""; // default for XAMPP $db = "search_engine";

Set the environment to development if needed:

define('APP_ENV', 'development');

<h3>4. Start the Application</h3>
The provided database is pre-filled with indexed pages, so you can use the application immediately.

## Main Interfaces:

##🔎︎ Search Engine: http://localhost/Search_Engine/search.php

##⚙️ Admin Panel: http://localhost/Search_Engine/admin.php (Password: admin123)

##🕷️ Crawler Tools (Optional): If you want to index new content or expand the database:

## Full Crawler: Visit http://localhost/Search_Engine/crawler_full.php to start the automated bot.

## Single Page: Visit http://localhost/Search_Engine/crawler.php?url=https://el.wikipedia.org/wiki/Programmer to index a specific URL manually.

## Project Structure
search.php - Main search interface and logic.

crawler_full.php - Advanced crawler with depth limit, domain whitelist, and politeness policies.

admin.php - Dashboard for statistics (Requires login).

indexer.php - Logic for processing page content into keywords.

autocomplete.php - API endpoint for the search bar suggestions.

assets/ - CSS styles and JavaScript files (Dark mode, AJAX).

## Security
SQL Injection Protection: Uses Prepared Statements (mysqli::prepare) for all database queries.

XSS Protection: Outputs are sanitized using htmlspecialchars.

CSRF Protection: Admin forms utilize CSRF tokens.

## License
Copyright (c) 2026 Aggelos Kourougenis. Licensed under the MIT License.
