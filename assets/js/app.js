// Copyright (c) 2026 Aggelos Kourougenis
// Licensed under the MIT License

document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("search-input");
    const box = document.getElementById("autocomplete-box");

    if (!input || !box) return;

    let timeoutId = null;

    input.addEventListener("input", () => {
        const query = input.value.trim();

        // Αν είναι πολύ μικρό, καθάρισε το κουτί
        if (query.length < 2) {
            box.innerHTML = "";
            box.style.display = "none";
            return;
        }

        // Μικρό delay για να μην κάνουμε fetch σε κάθε γράμμα instant
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            fetchSuggestions(query);
        }, 200);
    });

    // Όταν πατήσεις κάπου έξω, να κλείνει το κουτί
    document.addEventListener("click", (e) => {
        if (e.target !== input && !box.contains(e.target)) {
            box.innerHTML = "";
            box.style.display = "none";
        }
    });

    function fetchSuggestions(query) {
        fetch("autocomplete.php?q=" + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                showSuggestions(data);
            })
            .catch(err => {
                console.error("Autocomplete error:", err);
            });
    }

    function showSuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0) {
            box.innerHTML = "";
            box.style.display = "none";
            return;
        }

        box.innerHTML = "";
        box.style.display = "block";

        suggestions.forEach(word => {
            const item = document.createElement("div");
            item.className = "autocomplete-item";
            item.textContent = word;

            item.addEventListener("click", () => {
                input.value = word;
                box.innerHTML = "";
                box.style.display = "none";
            });

            box.appendChild(item);
        });
    }
});
