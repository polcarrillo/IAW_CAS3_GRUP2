<?php
/**
 * index.php
 * Punt d'entrada principal. Redirigeix a la pàgina corresponent segons el rol.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

iniciarSessio();

if (!estaAutenticat()) {
    header('Location: ' . BASE_URL . 'login.php');
} elseif (esProfessor()) {
    header('Location: ' . BASE_URL . 'professorat/index.php');
} else {
    header('Location: ' . BASE_URL . 'alumnat/index.php');
}
exit;