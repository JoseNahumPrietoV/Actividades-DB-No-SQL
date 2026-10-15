// Front-end/js/cambiar_password.js
// Flujo guiado de cambio de contraseña exclusivo para la Zona de Seguridad
// Paso 1: Verificación de correo detectado en sesión activa
// Paso 2: Validación OTP en modal flotante (6 dígitos / 15 min)
// Paso 3: Confirmación de nueva contraseña y redirección segura con sesión activa

document.addEventListener('DOMContentLoaded', function () {
    // --- Referencias Paso 1: Enviar Código con Correo Detectado ---
    const formPaso1 = document.getElementById('form-solicitar-codigo');
    const inputEmail = document.getElementById('email_recuperacion');
    const btnSolicitarOtp = document.getElementById('btnSolicitarOtp');
    const alertaPaso1 = document.getElementById('alertaPaso1');
    const cardPaso1 = document.getElementById('card-paso1-email');

    // --- Referencias Paso 2: Modal Flotante OTP ---
    const modalOverlay = document.getElementById('otpModalOverlay');
    const formVerifyOtp = document.getElementById('formVerifyOtpModal');
    const inputOtpCode = document.getElementById('inputOtpCode');
    const otpHiddenEmail = document.getElementById('otpHiddenEmail');
    const otpDisplayEmail = document.getElementById('otpDisplayEmail');
    const otpAlertBox = document.getElementById('otpAlertBox');
    const btnSubmitOtp = document.getElementById('btnSubmitOtp');
    const btnResendOtp = document.getElementById('btnResendOtp');
    const btnCloseModal = document.getElementById('btnCloseOtpModal');
    const timerDisplay = document.getElementById('otpTimer');

    // --- Referencias Paso 3: Confirmación de Contraseña ---
    const cardPaso2 = document.getElementById('card-paso2-password');
    const formCambiarPass = document.getElementById('form-cambiar-password');
    const emailConfirmacion = document.getElementById('email_confirmacion');
    const recuperacionToken = document.getElementById('recuperacion_token');
    const nuevaPassword = document.getElementById('nueva_password');
    const confirmPassword = document.getElementById('confirm_password');
    const btnSubmitPass = document.getElementById('btnSubmitPassword');
    const alertaPaso3 = document.getElementById('alertaPaso3');

    let countdownInterval = null;

    // --- Funciones de Utilidad para Alertas ---
    function mostrarAlerta(container, mensaje, tipo = 'error') {
        if (!container) return;
        container.className = 'alert-otp ' + (tipo === 'success' ? 'alert-success' : 'alert-error');
        container.innerHTML = (tipo === 'success' 
            ? '<i class="fa-solid fa-circle-check"></i> ' 
            : '<i class="fa-solid fa-circle-exclamation"></i> ') + '<span>' + mensaje + '</span>';
        container.style.display = 'flex';
    }

    function limpiarAlerta(container) {
        if (!container) return;
        container.style.display = 'none';
        container.innerHTML = '';
    }

    // --- Temporizador Regresivo de 15 Minutos ---
    function iniciarTemporizador(duracionSegundos) {
        clearInterval(countdownInterval);
        let restante = duracionSegundos;

        function actualizarVista() {
            const minutos = Math.floor(restante / 60);
            const segundos = restante % 60;
            if (timerDisplay) {
                timerDisplay.textContent = 
                    String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
            }

            if (restante <= 0) {
                clearInterval(countdownInterval);
                mostrarAlerta(otpAlertBox, 'El código ha expirado. Por favor solicita uno nuevo.', 'error');
            } else {
                restante--;
            }
        }

        actualizarVista();
        countdownInterval = setInterval(actualizarVista, 1000);
    }

    // Helper para disparar el envío SMTP en segundo plano sin bloquear la UI
    function dispararEnvioSmtpAsync(urlEndpoint, contenedorAlerta) {
        const bgData = new FormData();
        bgData.append('ajax', '1');
        bgData.append('fase', 'enviar_smtp');

        fetch(urlEndpoint, {
            method: 'POST',
            body: bgData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json().catch(() => null))
        .then(data => {
            if (data && data.status === 'error' && contenedorAlerta) {
                mostrarAlerta(contenedorAlerta, data.message || 'No se pudo enviar el correo.', 'error');
            }
        })
        .catch(err => {
            console.error('Error en despacho SMTP asíncrono:', err);
        });
    }

    // =========================================================================
    // PASO 1: Enviar OTP al correo detectado de la sesión (Transición 0ms)
    // =========================================================================
    if (formPaso1) {
        formPaso1.addEventListener('submit', function (e) {
            e.preventDefault();
            limpiarAlerta(alertaPaso1);

            const email = inputEmail ? inputEmail.value.trim() : '';
            if (!email) {
                mostrarAlerta(alertaPaso1, 'No se pudo detectar el correo de tu sesión. Por favor recarga la página.', 'error');
                return;
            }

            // 1. TRANSICIÓN OPTIMISTA INSTANTÁNEA (0ms):
            // Como el usuario ya tiene sesión activa y correo verificado, abrimos el modal al instante
            if (otpHiddenEmail) otpHiddenEmail.value = email;
            if (otpDisplayEmail) otpDisplayEmail.textContent = email;
            if (emailConfirmacion) emailConfirmacion.value = email;
            if (inputOtpCode) inputOtpCode.value = '';
            limpiarAlerta(otpAlertBox);

            if (modalOverlay) {
                modalOverlay.classList.add('active');
                requestAnimationFrame(() => {
                    if (inputOtpCode) inputOtpCode.focus();
                });
            }

            // Iniciar temporizador (15 minutos = 900 seg) de inmediato
            iniciarTemporizador(900);

            // 2. Generar OTP en MongoDB y despachar SMTP en segundo plano sin bloquear la UI
            const formData = new FormData();
            formData.append('email_recuperacion', email);
            formData.append('ajax', '1');
            formData.append('fase', 'generar');

            fetch('../Back-end/conexion/procesar_solicitud_recuperacion.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    dispararEnvioSmtpAsync('../Back-end/conexion/procesar_solicitud_recuperacion.php', otpAlertBox);
                } else {
                    mostrarAlerta(otpAlertBox, data.message || 'No se pudo generar el código de verificación.', 'error');
                }
            })
            .catch(err => {
                console.error('Error al solicitar OTP:', err);
                mostrarAlerta(otpAlertBox, 'Error de conexión al generar el código. Intenta darle en Reenviar código.', 'error');
            });
        });
    }

    // =========================================================================
    // PASO 2: Verificación OTP en el Modal
    // =========================================================================
    if (inputOtpCode) {
        inputOtpCode.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }

    if (formVerifyOtp) {
        formVerifyOtp.addEventListener('submit', function (e) {
            e.preventDefault();
            limpiarAlerta(otpAlertBox);

            const email = otpHiddenEmail ? otpHiddenEmail.value.trim() : '';
            const codigo = inputOtpCode ? inputOtpCode.value.trim() : '';

            if (codigo.length !== 6) {
                mostrarAlerta(otpAlertBox, 'Ingresa los 6 dígitos del código de verificación.', 'error');
                if (inputOtpCode) inputOtpCode.focus();
                return;
            }

            const textoOriginal = btnSubmitOtp ? btnSubmitOtp.innerHTML : '';
            if (btnSubmitOtp) {
                btnSubmitOtp.disabled = true;
                btnSubmitOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando código...';
            }

            const formData = new FormData();
            formData.append('email', email);
            formData.append('codigo_otp', codigo);
            formData.append('ajax', '1');

            fetch('../Back-end/conexion/validar_codigo_recuperacion.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                return res.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (err) {
                        throw new Error(text || 'Respuesta no válida del servidor.');
                    }
                });
            })
            .then(data => {
                if (btnSubmitOtp) {
                    btnSubmitOtp.disabled = false;
                    btnSubmitOtp.innerHTML = textoOriginal;
                }

                if (data.status === 'success') {
                    clearInterval(countdownInterval);

                    if (recuperacionToken && data.token) {
                        recuperacionToken.value = data.token;
                    }
                    if (emailConfirmacion && data.email) {
                        emailConfirmacion.value = data.email;
                    }

                    // Transición instantánea (0ms) hacia el Paso 3 (Confirmar Nueva Contraseña)
                    if (modalOverlay) modalOverlay.classList.remove('active');
                    if (cardPaso1) cardPaso1.style.display = 'none';
                    if (cardPaso2) {
                        cardPaso2.style.display = 'block';
                        mostrarAlerta(alertaPaso3, data.message || '¡Código confirmado con éxito! Crea tu nueva contraseña.', 'success');
                        requestAnimationFrame(() => {
                            if (nuevaPassword) nuevaPassword.focus();
                        });
                    }
                } else {
                    mostrarAlerta(otpAlertBox, data.message || 'El código es incorrecto o ya expiró.', 'error');
                    if (inputOtpCode) inputOtpCode.select();
                }
            })
            .catch(err => {
                if (btnSubmitOtp) {
                    btnSubmitOtp.disabled = false;
                    btnSubmitOtp.innerHTML = textoOriginal;
                }
                console.error('Error al validar OTP:', err);
                mostrarAlerta(otpAlertBox, 'Error de conexión: ' + err.message, 'error');
            });
        });
    }

    // Reenviar código OTP (Respuesta en 0ms + despacho SMTP en segundo plano)
    if (btnResendOtp) {
        btnResendOtp.addEventListener('click', function (e) {
            e.preventDefault();
            limpiarAlerta(otpAlertBox);

            const email = otpHiddenEmail ? otpHiddenEmail.value.trim() : '';
            if (!email) {
                mostrarAlerta(otpAlertBox, 'No se pudo identificar el correo electrónico.', 'error');
                return;
            }

            // Actualizar UI y temporizador en 0ms
            iniciarTemporizador(900);
            mostrarAlerta(otpAlertBox, '¡Nuevo código enviado a tu correo!', 'success');
            if (inputOtpCode) {
                inputOtpCode.value = '';
                inputOtpCode.focus();
            }

            const formData = new FormData();
            formData.append('email', email);
            formData.append('ajax', '1');
            formData.append('fase', 'generar');

            fetch('../Back-end/conexion/reenviar_otp_recuperacion.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    dispararEnvioSmtpAsync('../Back-end/conexion/reenviar_otp_recuperacion.php', otpAlertBox);
                } else {
                    mostrarAlerta(otpAlertBox, data.message || 'No se pudo reenviar el código.', 'error');
                }
            })
            .catch(err => {
                console.error('Error al reenviar OTP:', err);
                mostrarAlerta(otpAlertBox, 'Error al reenviar: ' + err.message, 'error');
            });
        });
    }

    // Cerrar modal
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function () {
            if (confirm('¿Deseas cancelar la verificación? Tendrás que solicitar un nuevo código.')) {
                if (modalOverlay) modalOverlay.classList.remove('active');
                clearInterval(countdownInterval);
            }
        });
    }

    // =========================================================================
    // PASO 3: Guardar Nueva Contraseña y Redirigir Manteniendo Sesión
    // =========================================================================
    if (formCambiarPass) {
        formCambiarPass.addEventListener('submit', function (e) {
            e.preventDefault();
            limpiarAlerta(alertaPaso3);

            const pass1 = nuevaPassword ? nuevaPassword.value : '';
            const pass2 = confirmPassword ? confirmPassword.value : '';

            if (pass1.length < 6) {
                mostrarAlerta(alertaPaso3, 'La nueva contraseña debe tener al menos 6 caracteres.', 'error');
                if (nuevaPassword) nuevaPassword.focus();
                return;
            }

            if (pass1 !== pass2) {
                mostrarAlerta(alertaPaso3, 'Las contraseñas no coinciden. Por favor verifícalas.', 'error');
                if (confirmPassword) confirmPassword.focus();
                return;
            }

            const textoOriginal = btnSubmitPass ? btnSubmitPass.innerHTML : '';
            if (btnSubmitPass) {
                btnSubmitPass.disabled = true;
                btnSubmitPass.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Actualizando contraseña...';
            }

            const formData = new FormData(formCambiarPass);
            formData.append('origen', 'seguridad');
            formData.append('ajax', '1');

            fetch('../Back-end/conexion/procesar_cambiar_password.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                return res.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (err) {
                        throw new Error(text || 'Respuesta no válida del servidor.');
                    }
                });
            })
            .then(data => {
                if (data.status === 'success') {
                    mostrarAlerta(alertaPaso3, data.message || '¡Contraseña actualizada con éxito!', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || '../Back-end/cliente/Miperfil.php';
                    }, 300);
                } else {
                    if (btnSubmitPass) {
                        btnSubmitPass.disabled = false;
                        btnSubmitPass.innerHTML = textoOriginal;
                    }
                    mostrarAlerta(alertaPaso3, data.message || 'Error al actualizar la contraseña.', 'error');
                }
            })
            .catch(err => {
                if (btnSubmitPass) {
                    btnSubmitPass.disabled = false;
                    btnSubmitPass.innerHTML = textoOriginal;
                }
                console.error('Error al guardar contraseña:', err);
                mostrarAlerta(alertaPaso3, err.message || 'Error al conectar con el servidor.', 'error');
            });
        });
    }
});

// Función para alternar visibilidad de contraseña
function alternarVisibilidadPassword(idInput, btn) {
    const input = document.getElementById(idInput);
    if (!input) return;

    const icon = btn.querySelector('i') || btn;
    const isPassword = input.type === 'password';

    input.type = isPassword ? 'text' : 'password';

    if (icon) {
        if (isPassword) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
}

window.mostrarPassword = alternarVisibilidadPassword;
window.alternarVisibilidadPassword = alternarVisibilidadPassword;
