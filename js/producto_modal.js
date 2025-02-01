// Obtener referencias a los elementos del DOM
const openModalBtn = document.getElementById('openModalBtn');
const modal = document.getElementById('myModal');
const closeBtn = document.querySelector('.close');
const submitBtn = document.getElementById('submitBtn');

// Abrir el modal al hacer clic en el botón
openModalBtn.addEventListener('click', () => {
    modal.style.display = 'flex';
});

// Cerrar el modal al hacer clic en la "x"
closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

// Cerrar el modal al hacer clic fuera del contenido del modal
window.addEventListener('click', (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

// Manejar el envío del formulario (aquí puedes agregar tu lógica)
submitBtn.addEventListener('click', () => {
    const tipoProducto = document.getElementById('tipoProducto').value;
    if (tipoProducto) {
        alert(`Categoría "${tipoProducto}" registrada correctamente.`);
        modal.style.display = 'none';
    } else {
        alert('Por favor, ingresa un tipo de producto.');
    }
});