// Front-end/js/recuperacion_wizard.js
// Flujo secuencial en 3 pasos para la recuperación de contraseña:
// Paso 1: Ingreso de correo -> Envío de OTP
// Paso 2: Modal flotante de verificación de código OTP (6 dígitos)
// Paso 3: Ventana de Confirmación de Contraseña -> Actualización en base de datos

document.addEventListener('DOMContentLoaded', function () {
    // --- Referencias Paso 1: Solicitar Código ---
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

    // --- Funciones Auxiliares de Alertas ---
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
    // PASO 1: Enviar solicitud de recuperación con el correo (Transición 0ms)
    // =========================================================================
    if (formPaso1) {
        formPaso1.addEventListener('submit', function (e) {
            e.preventDefault();
            limpiarAlerta(alertaPaso1);

            const email = inputEmail ? inputEmail.value.trim() : '';
            if (!email) {
                mostrarAlerta(alertaPaso1, 'Por favor ingresa tu correo electrónico.', 'error');
                if (inputEmail) inputEmail.focus();
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                mostrarAlerta(alertaPaso1, 'Por favor ingresa un correo electrónico válido.', 'error');
                if (inputEmail) inputEmail.focus();
                return;
            }

            const textoOriginal = btnSolicitarOtp ? btnSolicitarOtp.innerHTML : '';
            if (btnSolicitarOtp) {
                btnSolicitarOtp.disabled = true;
                btnSolicitarOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparando código...';
            }

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
                if (btnSolicitarOtp) {
                    btnSolicitarOtp.disabled = false;
                    btnSolicitarOtp.innerHTML = textoOriginal;
                }

                if (data.status === 'success') {
                    // Configurar datos en el Modal OTP al instante
                    if (otpHiddenEmail) otpHiddenEmail.value = data.email || email;
                    if (otpDisplayEmail) otpDisplayEmail.textContent = data.email || email;
                    if (emailConfirmacion) emailConfirmacion.value = data.email || email;
                    if (inputOtpCode) inputOtpCode.value = '';
                    limpiarAlerta(otpAlertBox);

                    // Abrir el modal flotante inmediatamente (0ms sin esperar a Gmail SMTP)
                    if (modalOverlay) {
                        modalOverlay.classList.add('active');
                        requestAnimationFrame(() => {
                            if (inputOtpCode) inputOtpCode.focus();
                        });
                    }

                    // Iniciar temporizador de 15 minutos (900 seg)
                    iniciarTemporizador(900);

                    // Despachar correo SMTP en segundo plano
                    dispararEnvioSmtpAsync('../Back-end/conexion/procesar_solicitud_recuperacion.php', otpAlertBox);
                } else {
                    mostrarAlerta(alertaPaso1, data.message || 'Error al enviar código de recuperación.', 'error');
                }
            })
            .catch(err => {
                if (btnSolicitarOtp) {
                    btnSolicitarOtp.disabled = false;
                    btnSolicitarOtp.innerHTML = textoOriginal;
                }
                console.error('Error al solicitar recuperación:', err);
                mostrarAlerta(alertaPaso1, err.message || 'Error de conexión con el servidor. Intenta de nuevo.', 'error');
            });
        });
    }

    // =========================================================================
    // PASO 2: Manejo del Modal de Verificación OTP
    // =========================================================================
    // Filtro numérico de 6 dígitos
    if (inputOtpCode) {
        inputOtpCode.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }

    // Confirmar código OTP (Transición inmediata al Paso 3)
    if (formVerifyOtp) {
        formVerifyOtp.addEventListener('submit', function (e) {
            e.preventDefault();
            limpiarAlerta(otpAlertBox);

            const email = otpHiddenEmail ? otpHiddenEmail.value.trim() : '';
            const codigo = inputOtpCode ? inputOtpCode.value.trim() : '';

            if (codigo.length !== 6) {
                mostrarAlerta(otpAlertBox, 'Por favor ingresa los 6 dígitos del código.', 'error');
                if (inputOtpCode) inputOtpCode.focus();
                return;
            }

            const textoOriginal = btnSubmitOtp ? btnSubmitOtp.innerHTML : '';
            if (btnSubmitOtp) {
                btnSubmitOtp.disabled = true;
                btnSubmitOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
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

                    // Guardar token y email autorizado
                    if (recuperacionToken && data.token) {
                        recuperacionToken.value = data.token;
                    }
                    if (emailConfirmacion && data.email) {
                        emailConfirmacion.value = data.email;
                    }

                    // Transición instantánea (0ms) al Paso 3: Confirmación de Contraseña
                    if (modalOverlay) modalOverlay.classList.remove('active');
                    if (cardPaso1) cardPaso1.style.display = 'none';
                    if (cardPaso2) {
                        cardPaso2.style.display = 'block';
                        mostrarAlerta(alertaPaso3, data.message || '¡Código verificado con éxito! Ingresa tu nueva contraseña.', 'success');
                        requestAnimationFrame(() => {
                            if (nuevaPassword) nuevaPassword.focus();
                        });
                    }
                } else {
                    mostrarAlerta(otpAlertBox, data.message || 'El código es incorrecto o ha expirado.', 'error');
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

            // Actualizar UI y temporizador en 0ms de forma optimista
            iniciarTemporizador(900);
            mostrarAlerta(otpAlertBox, '¡Nuevo código enviado! Revisa tu bandeja.', 'success');
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

    // Botón Cerrar Modal
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function () {
            if (confirm('¿Deseas cancelar la verificación? Si sales, tendrás que ingresar tu correo de nuevo.')) {
                if (modalOverlay) modalOverlay.classList.remove('active');
                clearInterval(countdownInterval);
            }
        });
    }

    // =========================================================================
    // PASO 3: Ventana "Confirmación de Contraseña"
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
                mostrarAlerta(alertaPaso3, 'Las contraseñas no coinciden. Por favor verifica ambos campos.', 'error');
                if (confirmPassword) confirmPassword.focus();
                return;
            }

            const textoOriginal = btnSubmitPass ? btnSubmitPass.innerHTML : '';
            if (btnSubmitPass) {
                btnSubmitPass.disabled = true;
                btnSubmitPass.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Restableciendo...';
            }

            const formData = new FormData(formCambiarPass);
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
                        window.location.href = data.redirect || 'login.php';
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
                console.error('Error al cambiar contraseña:', err);
                mostrarAlerta(alertaPaso3, err.message || 'Error al conectar con el servidor. Intenta de nuevo.', 'error');
            });
        });
    }
});

// Función global para mostrar/ocultar contraseña con el ícono del ojo
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

// Compatibilidad con el nombre anterior 'mostrarPassword' usado en otros scripts
window.mostrarPassword = alternarVisibilidadPassword;
window.alternarVisibilidadPassword = alternarVisibilidadPassword;
