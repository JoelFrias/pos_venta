
function navigateTo(page) {
    window.location.href = page; // Cambia la URL en la misma pestaña
}

function toggleNav() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active'); // Añade o quita la clase active para mostrar/ocultar el menú
}
