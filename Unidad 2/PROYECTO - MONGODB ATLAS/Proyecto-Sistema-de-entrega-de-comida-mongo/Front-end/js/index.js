// assets/js/main.js

// Espera a que todo el contenido de la página se cargue antes de ejecutar el script
document.addEventListener('DOMContentLoaded', function() {

    // --- Funcionalidad 1: Header "pegajoso" al hacer scroll ---
    const header = document.querySelector('header');

    // Escuchamos el evento de scroll en la ventana
    window.addEventListener('scroll', function() {
        // Si el scroll vertical es mayor a 50px...
        if (window.scrollY > 50) {
            // ...agregamos la clase 'scrolled' al header
            header.classList.add('scrolled');
        } else {
            // ...de lo contrario, la quitamos
            header.classList.remove('scrolled');
        }
    });

    // --- Funcionalidad 2: Precarga predictiva instantánea de categorías (InstantClick a 0 ms) ---
    const categoryLinks = document.querySelectorAll('.category-link');
    categoryLinks.forEach(function(link) {
        const prefetch = function() {
            const url = link.getAttribute('href');
            if (url && !link.dataset.prefetched) {
                link.dataset.prefetched = 'true';
                fetch(url, { priority: 'low' }).catch(function() {});
            }
        };
        link.addEventListener('mouseenter', prefetch, { passive: true });
        link.addEventListener('touchstart', prefetch, { passive: true });
    });
});
