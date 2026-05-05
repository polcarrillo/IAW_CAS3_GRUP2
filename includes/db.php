<?php
/**
 * db.php
 * Gestió de la connexió a la base de dades mitjançant PDO.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/config.php';

/**
 * Retorna una instància PDO connectada a la base de dades.
 * Utilitza el patró Singleton per evitar múltiples connexions.
 *
 * @return PDO Instància de la connexió PDO.
 * @throws PDOException Si la connexió falla.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producció mai mostris el missatge d'error directament
            error_log('Error de connexió BD: ' . $e->getMessage());
            die('Error de connexió a la base de dades. Contacta amb l\'administrador.');
        }
    }

    return $pdo;
}