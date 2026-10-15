// Front-end/js/verificar_modal.js
// Manejo del modal flotante de verificación OTP y envío AJAX del registro

document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('register-form');
    if (!registerForm) return;

    const modalOverlay = document.getElementById('otpModalOverlay');
    const formVerifyOtp = document.getElementById('formVerifyOtpModal');
    const inputOtpCode = document.getElementById('inputOtpCode');
    const otpHiddenEmail = document.getElementById('otpHiddenEmail');
    const otpDisplayEmail = document.getElementById('otpDisplayEmail');
    const otpAlertBox = document.getElementById('otpAlertBox');
    const registroAlertBox = document.getElementById('registroAlertBox');
    const btnSubmitOtp = document.getElementById('btnSubmitOtp');
    const btnResendOtp = document.getElementById('btnResendOtp');
    const btnCloseModal = document.getElementById('btnCloseOtpModal');
    const timerDisplay = document.getElementById('otpTimer');
    const btnRegister = registerForm.querySelector('button[type="submit"]');

    let countdownInterval = null;

    // Helper: mostrar mensaje dentro del modal OTP
    function showOtpAlert(message, type = 'error') {
        if (!otpAlertBox) return;
        otpAlertBox.className = 'alert-otp ' + (type === 'success' ? 'alert-success' : 'alert-error');
        otpAlertBox.innerHTML = (type === 'success' ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-exclamation"></i> ') + '<span>' + message + '</span>';
        otpAlertBox.style.display = 'flex';
    }

    function clearOtpAlert() {
        if (otpAlertBox) {
            otpAlertBox.style.display = 'none';
            otpAlertBox.innerHTML = '';
        }
    }

    // Helper: mostrar alerta en la página de registro
    function showRegistroAlert(message) {
        if (!registroAlertBox) {
            alert(message);
            return;
        }
        registroAlertBox.className = 'alert-otp alert-error';
        registroAlertBox.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> <span>' + message + '</span>';
        registroAlertBox.style.display = 'flex';
        registroAlertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearRegistroAlert() {
        if (registroAlertBox) {
            registroAlertBox.style.display = 'none';
            registroAlertBox.innerHTML = '';
        }
    }

    // Temporizador regresivo de 15 minutos (900s)
    function startTimer(durationSeconds) {
        clearInterval(countdownInterval);
        let remaining = durationSeconds;

        function updateDisplay() {
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            if (timerDisplay) {
                timerDisplay.textContent = 
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                showOtpAlert('El código ha expirado. Por favor solicita uno nuevo.', 'error');
            } else {
                remaining--;
            }
        }

        updateDisplay();
        countdownInterval = setInterval(updateDisplay, 1000);
    }

    // 1. Interceptar envío de formulario de registro
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        clearRegistroAlert();

        // Validaciones en cliente
        const radioCliente = document.getElementById('cliente');
        const isCliente = radioCliente ? radioCliente.checked : true;

        const passInput = isCliente ? document.getElementById('password_cliente') : document.getElementById('password_vendedor');
        const confirmInput = isCliente ? document.getElementById('confirm_password_cliente') : document.getElementById('confirm_password_vendedor');

        if (passInput && confirmInput && passInput.value !== confirmInput.value) {
            showRegistroAlert('Las contraseñas no coinciden. Por favor verifica ambos campos.');
            confirmInput.focus();
            return;
        }

        if (passInput && passInput.value.length < 6) {
            showRegistroAlert('La contraseña debe tener al menos 6 caracteres.');
            passInput.focus();
            return;
        }

        const formData = new FormData(registerForm);
        formData.append('ajax', '1');

        const originalBtnText = btnRegister.innerHTML;
        btnRegister.disabled = true;
        btnRegister.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';

        fetch('../Back-end/conexion/procesar_registro.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (err) {
                    throw new Error(text || 'Respuesta inválida del servidor');
                }
            });
        })
        .then(data => {
            btnRegister.disabled = false;
            btnRegister.innerHTML = originalBtnText;

            if (data.status === 'success') {
                if (otpHiddenEmail) otpHiddenEmail.value = data.email;
                if (otpDisplayEmail) otpDisplayEmail.textContent = data.email;
                clearOtpAlert();
                if (inputOtpCode) {
                    inputOtpCode.value = '';
                }

                // Abrir ventana flotante (modal)
                if (modalOverlay) {
                    modalOverlay.classList.add('active');
                    setTimeout(() => {
                        if (inputOtpCode) inputOtpCode.focus();
                    }, 250);
                }

                // Iniciar temporizador de 15 minutos
                startTimer(900);
            } else {
                showRegistroAlert(data.message || 'No se pudo completar el registro.');
            }
        })
        .catch(err => {
            btnRegister.disabled = false;
            btnRegister.innerHTML = originalBtnText;
            console.error('Error al registrar:', err);
            showRegistroAlert(err.message || 'Ocurrió un error al procesar el registro. Intenta nuevamente.');
        });
    });

    // 2. Formato del input de código (sólo dígitos, max 6)
    if (inputOtpCode) {
        inputOtpCode.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }

    // 3. Envío de verificación de código OTP desde el modal
    if (formVerifyOtp) {
        formVerifyOtp.addEventListener('submit', function(e) {
            e.preventDefault();
            clearOtpAlert();

            const email = otpHiddenEmail ? otpHiddenEmail.value.trim() : '';
            const code = inputOtpCode ? inputOtpCode.value.trim() : '';

            if (code.length !== 6) {
                showOtpAlert('Por favor ingresa los 6 dígitos del código.', 'error');
                if (inputOtpCode) inputOtpCode.focus();
                return;
            }

            const originalBtnText = btnSubmitOtp ? btnSubmitOtp.innerHTML : '';
            if (btnSubmitOtp) {
                btnSubmitOtp.disabled = true;
                btnSubmitOtp.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
            }

            const formData = new FormData();
            formData.append('email', email);
            formData.append('codigo_otp', code);
            formData.append('ajax', '1');

            fetch('../Back-end/conexion/procesar_verificacion_otp.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (err) {
                        throw new Error(text || 'Respuesta inválida del servidor');
                    }
                });
            })
            .then(data => {
                if (btnSubmitOtp) {
                    btnSubmitOtp.disabled = false;
                    btnSubmitOtp.innerHTML = originalBtnText;
                }

                if (data.status === 'success') {
                    clearInterval(countdownInterval);
                    showOtpAlert(data.message || '¡Cuenta verificada con éxito!', 'success');
                    
                    let targetUrl = data.redirect || '../Back-end/cliente/dashboard_cliente.php';
                    if (targetUrl.startsWith('../cliente/') || targetUrl.startsWith('../vendedor/') || targetUrl.startsWith('../admin/')) {
                        targetUrl = '../Back-end/' + targetUrl.substring(3);
                    }
                    // Redirigir al dashboard respectivo
                    setTimeout(() => {
                        window.location.href = targetUrl;
                    }, 350);
                } else {
                    showOtpAlert(data.message || 'El código es incorrecto o ha expirado.', 'error');
                    if (inputOtpCode) inputOtpCode.select();
                }
            })
            .catch(err => {
                if (btnSubmitOtp) {
                    btnSubmitOtp.disabled = false;
                    btnSubmitOtp.innerHTML = originalBtnText;
                }
                console.error('Error al verificar OTP:', err);
                showOtpAlert('Error al conectar con el servidor: ' + err.message, 'error');
            });
        });
    }

    // 4. Reenviar código OTP desde el modal
    if (btnResendOtp) {
        btnResendOtp.addEventListener('click', function(e) {
            e.preventDefault();
            clearOtpAlert();

            const email = otpHiddenEmail ? otpHiddenEmail.value.trim() : '';
            if (!email) {
                showOtpAlert('No se encontró el correo para reenviar el código.', 'error');
                return;
            }

            btnResendOtp.style.pointerEvents = 'none';
            const originalText = btnResendOtp.textContent;
            btnResendOtp.textContent = 'Enviando nuevo código...';

            const formData = new FormData();
            formData.append('email', email);
            formData.append('ajax', '1');

            fetch('../Back-end/conexion/reenviar_otp.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (err) {
                        throw new Error(text || 'Respuesta inválida del servidor');
                    }
                });
            })
            .then(data => {
                btnResendOtp.style.pointerEvents = 'auto';
                btnResendOtp.textContent = originalText;

                if (data.status === 'success') {
                    showOtpAlert(data.message || '¡Nuevo código enviado! Revisa tu correo.', 'success');
                    startTimer(900);
                } else {
                    showOtpAlert(data.message || 'No se pudo reenviar el código.', 'error');
                }
            })
            .catch(err => {
                btnResendOtp.style.pointerEvents = 'auto';
                btnResendOtp.textContent = originalText;
                console.error('Error al reenviar OTP:', err);
                showOtpAlert('Error al reenviar código: ' + err.message, 'error');
            });
        });
    }

    // 5. Botón cerrar modal
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function() {
            if (confirm('¿Deseas completar la verificación más tarde? Podrás verificar tu cuenta al iniciar sesión con tu correo y contraseña.')) {
                clearInterval(countdownInterval);
                if (modalOverlay) modalOverlay.classList.remove('active');
                window.location.href = 'login.php?pendiente=1';
            }
        });
    }
});
