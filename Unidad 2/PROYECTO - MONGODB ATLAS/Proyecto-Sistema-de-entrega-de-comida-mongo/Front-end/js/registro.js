function mostrarPassword(inputId, button) {
    // Selecciona el campo de contraseña
    const input = document.getElementById(inputId);
    
    // Selecciona el ícono <i> que está dentro del botón
    const icon = button.querySelector('i');

    // Cambia el tipo de input (de password a text y viceversa)
    // 🔑 CORRECCIÓN: El tipo de input debe ser 'password'
    if (input.type === "password") { 
        input.type = "text";
        // Cambia las clases del ícono (ojo cerrado a ojo abierto)
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        // 🔑 CORRECCIÓN: El tipo de input debe ser 'password'
        input.type = "password"; 
        // Revierte las clases del ícono (ojo abierto a ojo cerrado)
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
}