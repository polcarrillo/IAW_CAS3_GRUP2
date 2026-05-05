<?php
/**
 * auth.php
 * Funcions d'autenticació i control d'accés.
 * Gestiona sessions d'usuari amb dos rols: professorat i alumnat.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/db.php';

/** Rol de professorat */
define('ROL_PROFESSOR', 'professor');
/** Rol d'alumnat */
define('ROL_ALUMNE', 'alumne');

/**
 * Inicia la sessió PHP de forma segura si no està iniciada.
 *
 * @return void
 */
function iniciarSessio(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

/**
 * Comprova si l'usuari ha iniciat sessió.
 *
 * @return bool True si hi ha sessió activa.
 */
function estaAutenticat(): bool {
    iniciarSessio();
    return isset($_SESSION['usuari_id'], $_SESSION['rol']);
}

/**
 * Comprova si l'usuari actual té rol de professor.
 *
 * @return bool True si és professor.
 */
function esProfessor(): bool {
    return estaAutenticat() && $_SESSION['rol'] === ROL_PROFESSOR;
}

/**
 * Comprova si l'usuari actual té rol d'alumne.
 *
 * @return bool True si és alumne.
 */
function esAlumne(): bool {
    return estaAutenticat() && $_SESSION['rol'] === ROL_ALUMNE;
}

/**
 * Redirigeix a login si l'usuari no està autenticat.
 *
 * @return void
 */
function requerirAutenticacio(): void {
    if (!estaAutenticat()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Redirigeix si l'usuari no és professor.
 *
 * @return void
 */
function requerirProfessor(): void {
    requerirAutenticacio();
    if (!esProfessor()) {
        header('Location: ' . BASE_URL . 'alumnat/index.php');
        exit;
    }
}

/**
 * Autentica un usuari a partir del correu i contrasenya.
 * Cerca a la taula unificada Usuaris i comprova el rol.
 *
 * @param string $correu      Correu electrònic de l'usuari.
 * @param string $contrasenya Contrasenya en text pla.
 * @return bool True si l'autenticació és correcta.
 */
function autenticar(string $correu, string $contrasenya): bool {
    iniciarSessio();
    $db = getDB();

    $stmt = $db->prepare(
        "SELECT id, nom, cognom1, contrasenya_hash, rol 
         FROM Usuaris 
         WHERE correu = ? AND actiu = 1 
         LIMIT 1"
    );
    $stmt->execute([$correu]);
    $usuari = $stmt->fetch();

    if ($usuari && password_verify($contrasenya, $usuari['contrasenya_hash'])) {
        $_SESSION['usuari_id'] = $usuari['id'];
        $_SESSION['nom']       = $usuari['nom'] . ' ' . $usuari['cognom1'];
        $_SESSION['correu']    = $correu;
        $_SESSION['rol']       = $usuari['rol'];
        session_regenerate_id(true);
        registrarAcces($usuari['id'], $usuari['rol']);
        return true;
    }

    return false;
}

/**
 * Tanca la sessió de l'usuari actual.
 *
 * @return void
 */
function tancarSessio(): void {
    iniciarSessio();
    $_SESSION = [];
    session_destroy();
}

/**
 * Registra l'accés de l'usuari a la taula de logs.
 *
 * @param int    $idUsuari ID de l'usuari.
 * @param string $rol      Rol de l'usuari (professor/alumne).
 * @return void
 */
function registrarAcces(int $idUsuari, string $rol): void {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO LogAccesos (idUsuari, rol, dataHora, ip) 
             VALUES (?, ?, NOW(), ?)"
        );
        $stmt->execute([$idUsuari, $rol, $_SERVER['REMOTE_ADDR'] ?? 'desconegut']);
    } catch (PDOException $e) {
        error_log('Error registrant accés: ' . $e->getMessage());
    }
}

/**
 * Escapa una cadena per mostrar-la de forma segura en HTML.
 *
 * @param string $text Text a escapar.
 * @return string Text escapat.
 */
function h(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}