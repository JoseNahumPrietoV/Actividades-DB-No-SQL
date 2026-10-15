<?php
// tools/test_smtp_email.php

require_once dirname(__DIR__) . '/Back-end/servicios/EmailService.php';

use Snackver\Servicios\EmailService;

echo "========================================================\n";
echo "PRUEBA DE ENVÍO DE CORREO OTP (Gmail SMTP / PHPMailer)\n";
echo "========================================================\n\n";

$emailPrueba = $argv[1] ?? 'destinatario.prueba@gmail.com';
$nombrePrueba = 'Estudiante Tester';
$codigoOtp = (string)random_int(100000, 999999);

echo "Destinatario: $emailPrueba\n";
echo "Código generado: $codigoOtp\n\n";

$tipoPrueba = $argv[2] ?? 'ambos'; // 'verificacion', 'recuperacion', or 'ambos'

if ($tipoPrueba === 'verificacion' || $tipoPrueba === 'ambos') {
    echo "--- 1. Probando Correo de Verificación de Cuenta ---\n";
    $res1 = EmailService::enviarCodigoVerificacion($emailPrueba, $nombrePrueba, $codigoOtp);
    if ($res1['status'] === 'success') {
        echo "✅ ÉXITO Verificación: " . $res1['message'] . "\n";
    } elseif ($res1['status'] === 'simulated') {
        echo "ℹ️ SIMULADO: " . $res1['message'] . "\n";
    } else {
        echo "❌ ERROR: " . $res1['message'] . "\n";
    }
}

if ($tipoPrueba === 'recuperacion' || $tipoPrueba === 'ambos') {
    echo "\n--- 2. Probando Correo de Recuperación de Contraseña ---\n";
    $codigoRecup = (string)random_int(100000, 999999);
    $res2 = EmailService::enviarCodigoRecuperacion($emailPrueba, $nombrePrueba, $codigoRecup);
    if ($res2['status'] === 'success') {
        echo "✅ ÉXITO Recuperación: " . $res2['message'] . "\n";
    } elseif ($res2['status'] === 'simulated') {
        echo "ℹ️ SIMULADO: " . $res2['message'] . "\n";
    } else {
        echo "❌ ERROR: " . $res2['message'] . "\n";
    }
}

echo "\n📧 Revisa la bandeja de entrada o spam de: $emailPrueba\n";

echo "\n========================================================\n";
