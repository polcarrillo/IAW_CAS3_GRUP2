<?php
/**
 * logout.php
 * Tanca la sessió de l'usuari i redirigeix al login.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

iniciarSessio();
tancarSessio();

header('Location: ' . BASE_URL . 'login.php');
exit;