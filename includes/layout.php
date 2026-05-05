<?php
/**
 * layout.php
 * Funcions per generar la capçalera i el peu de pàgina HTML comuns.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/auth.php';

/**
 * Genera la capçalera HTML de la pàgina.
 *
 * @param string $titol Títol de la pàgina.
 * @return void
 */
function capçalera(string $titol = ''): void {
    $nomUsuari = $_SESSION['nom'] ?? '';
    $rol       = $_SESSION['rol'] ?? '';
    $titolComp = $titol ? h($titol) . ' | ' . APP_NAME : APP_NAME;
    ?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titolComp ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #333; }
        header {
            background: #1a4f8a;
            color: white;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 60px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        header h1 { font-size: 1.1rem; font-weight: 600; }
        header .user-info { font-size: 0.9rem; display: flex; align-items: center; gap: 1rem; }
        header .badge {
            background: #f0a500;
            color: #fff;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        header .badge.alumne { background: #27ae60; }
        nav {
            background: #15407a;
            padding: 0 2rem;
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
        }
        nav a {
            color: #cce0ff;
            text-decoration: none;
            padding: 0.7rem 1rem;
            font-size: 0.88rem;
            display: inline-block;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            transition: all 0.2s;
        }
        nav a:hover, nav a.actiu { color: white; border-bottom-color: #f0a500; }
        main { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
        .page-title { font-size: 1.5rem; color: #1a4f8a; margin-bottom: 1.5rem; font-weight: 700; }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th { background: #1a4f8a; color: white; padding: 0.7rem 1rem; text-align: left; }
        td { padding: 0.65rem 1rem; border-bottom: 1px solid #eee; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f0f4ff; }
        .btn {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.85; }
        .btn-primary { background: #1a4f8a; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f0a500; color: white; }
        .btn-danger  { background: #e74c3c; color: white; }
        .btn-sm { padding: 0.3rem 0.8rem; font-size: 0.8rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.9rem; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.55rem 0.8rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { outline: none; border-color: #1a4f8a; }
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge-estat {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .badge-ok   { background: #d4edda; color: #155724; }
        .badge-inc  { background: #f8d7da; color: #721c24; }
        footer { text-align: center; padding: 2rem; color: #999; font-size: 0.82rem; }
        a.logout { color: #ffcdd2; font-size: 0.85rem; }
        a.logout:hover { color: white; }
    </style>
</head>
<body>
<header>
    <h1><img src="<?= BASE_URL ?>imatges/montsia-removebg-preview.png" alt="Institut Montsià" style="height:44px; vertical-align:middle; margin-right:0.5rem;"><?= APP_NAME ?></h1>
    <div class="user-info">
        <span><?= h($nomUsuari) ?></span>
        <span class="badge <?= $rol === ROL_ALUMNE ? 'alumne' : '' ?>"><?= h($rol) ?></span>
        <a class="logout" href="<?= BASE_URL ?>logout.php">Tancar sessió</a>
    </div>
</header>
    <?php
    // Menú de navegació segons el rol
    if ($rol === ROL_PROFESSOR): ?>
<nav>
    <a href="<?= BASE_URL ?>professorat/index.php">Inici</a>
    <a href="<?= BASE_URL ?>professorat/dispositius_aula.php">Dispositius per Aula</a>
    <a href="<?= BASE_URL ?>professorat/dispositius_tipus.php">Per Tipus i Assignat</a>
    <a href="<?= BASE_URL ?>professorat/alumne_dispositius.php">Cerca Alumne</a>
    <a href="<?= BASE_URL ?>professorat/incidencies.php">Incidències</a>
    <a href="<?= BASE_URL ?>professorat/nou_alumne.php">Nou Alumne</a>
    <a href="<?= BASE_URL ?>professorat/nou_maquinari.php">Nou Maquinari</a>
</nav>
    <?php elseif ($rol === ROL_ALUMNE): ?>
<nav>
    <a href="<?= BASE_URL ?>alumnat/index.php">Els meus dispositius</a>
</nav>
    <?php endif; ?>
<main>
    <?php if ($titol): ?>
    <h2 class="page-title"><?= h($titol) ?></h2>
    <?php endif; ?>
<?php
}

/**
 * Genera el peu de pàgina HTML.
 *
 * @return void
 */
function peu(): void {
    ?>
</main>
<footer>Institut Montsià &mdash; Gestió de Material &mdash; Curs 2025-2026</footer>
</body>
</html>
    <?php
}

/**
 * Mostra un missatge d'alerta (èxit o error) si n'hi ha a la sessió.
 *
 * @return void
 */
function mostrarMissatge(): void {
    if (!empty($_SESSION['missatge'])) {
        $tipus = $_SESSION['tipus_missatge'] ?? 'success';
        echo '<div class="alert alert-' . h($tipus) . '">' . h($_SESSION['missatge']) . '</div>';
        unset($_SESSION['missatge'], $_SESSION['tipus_missatge']);
    }
}

/**
 * Estableix un missatge de feedback a la sessió per mostrar a la pròxima pàgina.
 *
 * @param string $missatge Text del missatge.
 * @param string $tipus    Tipus: 'success' o 'error'.
 * @return void
 */
function setMissatge(string $missatge, string $tipus = 'success'): void {
    iniciarSessio();
    $_SESSION['missatge']      = $missatge;
    $_SESSION['tipus_missatge'] = $tipus;
}