// Front-end/js/login.js
// Manejo de visibilidad de contraseñas, envío AJAX de inicio de sesión y ventana modal de verificación OTP

function mostrarPassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = button.querySelector('i');

    if (input.type === "password") {
        input.type = "text";
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    } else {
        input.type = "password";
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const loginAlertBox = document.getElementById('loginAlertBox');
    const btnLoginSubmit = document.getElementById('btnLoginSubmit');

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

    let countdownInterval = null;

    // Helper: mostrar mensaje en loginAlertBox
    function showLoginAlert(message, type = 'error') {
        if (!loginAlertBox) return;
        loginAlertBox.className = 'alert-otp ' + (type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-error'));
        loginAlertBox.innerHTML = '<i class="fa-solid ' + (type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation') + '"></i> <span>' + message + '</span>';
        loginAlertBox.style.display = 'flex';
    }

    function clearLoginAlert() {
        if (loginAlertBox) {
            loginAlertBox.style.display = 'none';
            loginAlertBox.innerHTML = '';
        }
    }

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

    // 1. Envío AJAX del formulario de inicio de sesión
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            clearLoginAlert();

            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');

            if (!emailInput.value.trim() || !passInput.value) {
                showLoginAlert('Por favor completa todos los campos.');
                return;
            }

            const formData = new FormData(loginForm);
            formData.append('ajax', '1');

            const originalBtnText = btnLoginSubmit ? btnLoginSubmit.innerHTML : 'Iniciar Sesión';
            if (btnLoginSubmit) {
                btnLoginSubmit.disabled = true;
                btnLoginSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verificando...';
            }

            fetch('../Back-end/conexion/procesar_login.php', {
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
                if (btnLoginSubmit) {
                    btnLoginSubmit.disabled = false;
                    btnLoginSubmit.innerHTML = originalBtnText;
                }

                if (data.status === 'success') {
                    showLoginAlert('¡Inicio de sesión exitoso! Redirigiendo...', 'success');
                    let targetUrl = data.redirect || '../Back-end/cliente/dashboard_cliente.php';
                    if (targetUrl.startsWith('../cliente/') || targetUrl.startsWith('../vendedor/') || targetUrl.startsWith('../admin/')) {
                        targetUrl = '../Back-end/' + targetUrl.substring(3);
                    }
                    setTimeout(() => {
                        window.location.href = targetUrl;
                    }, 250);
                } else if (data.status === 'unverified') {
                    // Abrir modal de verificación inmediatamente
                    if (otpHiddenEmail) otpHiddenEmail.value = data.email;
                    if (otpDisplayEmail) otpDisplayEmail.textContent = data.email;
                    clearOtpAlert();
                    if (inputOtpCode) inputOtpCode.value = '';

                    if (modalOverlay) {
                        modalOverlay.classList.add('active');
                        setTimeout(() => {
                            if (inputOtpCode) inputOtpCode.focus();
                        }, 250);
                    }

                    startTimer(900);
                } else {
                    showLoginAlert(data.message || 'El correo electrónico o la contraseña son incorrectos.', 'error');
                }
            })
            .catch(err => {
                if (btnLoginSubmit) {
                    btnLoginSubmit.disabled = false;
                    btnLoginSubmit.innerHTML = originalBtnText;
                }
                console.error('Error al iniciar sesión:', err);
                showLoginAlert('Ocurrió un error al procesar el inicio de sesión. Intenta nuevamente.');
            });
        });
    }

    // 2. Formato del input de código OTP (sólo dígitos, max 6)
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

    // 5. Botón cerrar modal en login
    if (btnCloseModal) {
        btnCloseModal.addEventListener('click', function() {
            if (modalOverlay) modalOverlay.classList.remove('active');
            clearInterval(countdownInterval);
            showLoginAlert('Tu cuenta continúa en pausa. Debes verificar el código para acceder a tu panel.', 'warning');
        });
    }

    // 6. Cerrar al hacer clic en el fondo exterior del modal
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                modalOverlay.classList.remove('active');
                clearInterval(countdownInterval);
                showLoginAlert('Tu cuenta continúa en pausa. Debes verificar el código para acceder a tu panel.', 'warning');
            }
        });
    }

    // 7. Auto-apertura si el servidor indicó abrir el modal OTP (ej. redirect con $_SESSION['abrir_modal_otp'])
    if (window.autoOpenOtpEmail && typeof window.autoOpenOtpEmail === 'string' && window.autoOpenOtpEmail.trim() !== '') {
        const targetEmail = window.autoOpenOtpEmail.trim();
        if (otpHiddenEmail) otpHiddenEmail.value = targetEmail;
        if (otpDisplayEmail) otpDisplayEmail.textContent = targetEmail;
        clearOtpAlert();
        if (inputOtpCode) inputOtpCode.value = '';

        if (modalOverlay) {
            modalOverlay.classList.add('active');
            setTimeout(() => {
                if (inputOtpCode) inputOtpCode.focus();
            }, 250);
        }

        startTimer(900);
    }
});