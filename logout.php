<?php
/**
 * logout.php
 * Tanca la sessió de l'usuari i redirigeix al login.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

iniciarSessio();
tancarSessio();

header('Location: ' . BASE_URL . 'login.php');
exit;