// inciamos el Documento para que se ejecute el codigo de la pagina
document.addEventListener('DOMContentLoaded', function() {

    // Selección de Elementos de que se van a manipular cada elementos

    const sidebar = document.getElementById('sidebar'); //Obtiene el elemento del menú lateral.
    const toggleBtn = document.getElementById('toggleMenu'); //Obtiene el botón para alternar el menú.
    const mobileToggle = document.getElementById('mobileToggle');//Obtiene el botón específico para dispositivos móviles (si existe).
    const overlay = document.getElementById('overlay'); //: Obtiene el elemento que cubre el contenido principal cuando el menú está abierto (usualmente un fondo oscuro).
    const menuItems = document.querySelectorAll('.menu li'); //menuItems: Selecciona todos los elementos de la lista dentro del menú.
    
    // Mejora de Accesibilidad
    menuItems.forEach(item => {
        const text = item.textContent.trim();
        const icon = item.querySelector('i').outerHTML;
        item.innerHTML = `${icon}<span>${text}</span>`;
    });

    //  Función para Restablecer el Estado del Menú
    function resetMenuState() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            mobileToggle.classList.remove('hidden');
        } else {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            mobileToggle.classList.add('hidden');
        }
        toggleBtn.querySelector('i').style.transform = 'rotate(0)';
    }

    //  Función para Alternar el Sidebar
    function toggleSidebar(e) {
        e.stopPropagation();
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            mobileToggle.classList.toggle('hidden');
        } else {
            sidebar.classList.toggle('collapsed');
            // Ensure mobile classes are removed in desktop view
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
        
        // Rotate toggle button icon
        const icon = e.currentTarget.querySelector('i');
        icon.style.transform = (sidebar.classList.contains('collapsed') || sidebar.classList.contains('active'))
            ? 'rotate(180deg)' 
            : 'rotate(0)';
    }

    // Toggle sidebar events
    toggleBtn.addEventListener('click', function(e) {
        toggleSidebar(e);
        if (window.innerWidth <= 768) {
            mobileToggle.classList.remove('hidden');
        }
    });
    
    mobileToggle.addEventListener('click', function(e) {
        toggleSidebar(e);
        this.classList.add('hidden');
    });

    // Cierre del Sidebar al Hacer Clic en el Overlay
    overlay.addEventListener('click', function() {
        resetMenuState();
    });

    // Manejo de Redimensionamiento de Ventana
    let timeout;
    window.addEventListener('resize', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            resetMenuState();
            
            // Reset menu state based on screen size
            if (window.innerWidth > 768) {
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    sidebar.classList.remove('collapsed');
                }
            }
        }, 250);
    });

    // Prevención de Cierre del Sidebar al Hacer Clic en el Sidebar
    sidebar.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // por ultimo Cierre del Sidebar al Hacer Clic en los Elementos del Menú en Móvil
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                resetMenuState();
            }
        });
    });

    //  Configuración Inicial
    resetMenuState();
});