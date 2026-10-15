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


    // // --- Funcionalidad 2: Alerta en el botón de búsqueda ---
    // const searchButton = document.querySelector('.search-btn');

    // // Escuchamos el evento de clic en el botón
    // searchButton.addEventListener('click', function(event) {
    //     // Prevenimos el comportamiento por defecto del botón (que no intente enviar un formulario)
    //     event.preventDefault(); 
        
    //     // Mostramos una alerta al usuario
    //     alert('¡La función de búsqueda estará disponible muy pronto!');
    // });

});



