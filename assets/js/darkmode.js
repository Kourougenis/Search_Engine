// Copyright (c) 2026 Aggelos Kourougenis
// Licensed under the MIT License

const toggle = document.getElementById("darkToggle");

function updateButton() {
    if (document.body.classList.contains("dark")) {
        toggle.textContent = "☀️ Light mode";
    } else {
        toggle.textContent = "🌙 Dark mode";
    }
}

// Load preference
if (localStorage.getItem("darkMode") === "on") {
    document.body.classList.add("dark");
}
updateButton();

toggle.addEventListener("click", () => {
    document.body.classList.toggle("dark");

    if (document.body.classList.contains("dark")) {
        localStorage.setItem("darkMode", "on");
    } else {
        localStorage.setItem("darkMode", "off");
    }

    updateButton();
});
