(function() {
    const isDarkMode = localStorage.getItem("darkMode") === "enabled";
    document.body.classList.toggle("dark-mode", isDarkMode);
})();

document.addEventListener("DOMContentLoaded", function () {
    const toggleDarkMode = document.getElementById("toggleDarkMode");
    const body = document.body;

    function applyDarkMode(isDark) {
        body.classList.toggle("dark-mode", isDark);
    }

    // Leer estado guardado en localStorage
    const isDarkMode = localStorage.getItem("darkMode") === "enabled";
    applyDarkMode(isDarkMode);

    if (toggleDarkMode) {
        toggleDarkMode.checked = isDarkMode;
        toggleDarkMode.addEventListener("change", function () {
            const isChecked = this.checked;
            applyDarkMode(isChecked);
            localStorage.setItem("darkMode", isChecked ? "enabled" : "disabled");
        });
    }
});