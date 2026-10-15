function validarPasswords() {
    var p1 = document.getElementById("nueva_password").value;
    var p2 = document.getElementById("confirm_password").value;
    var error = document.getElementById("error-message");

    if (p1.length < 8) {
        error.textContent = "La contraseña debe tener al menos 8 caracteres.";
        return false;
    }

    if (p1 !== p2) {
        error.textContent = "Las contraseñas no coinciden.";
        return false;
    }

    return true;
}

function mostrarPassword(idInput, btn) {
    var input = document.getElementById(idInput);
    if (input.type === "password") {
        input.type = "text";
        btn.classList.remove("fa-eye-slash");
        btn.classList.add("fa-eye");
    } else {
        input.type = "password";
        btn.classList.remove("fa-eye");
        btn.classList.add("fa-eye-slash");
    }
}