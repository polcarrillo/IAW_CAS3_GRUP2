<?php
/**
 * login.php
 * Pàgina d'inici de sessió.
 * Gestiona l'autenticació de professors i alumnes.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/config.php';

iniciarSessio();

// Si ja ha iniciat sessió, redirigeix al panell corresponent
if (estaAutenticat()) {
    if (esProfessor()) {
        header('Location: ' . BASE_URL . 'professorat/index.php');
    } else {
        header('Location: ' . BASE_URL . 'alumnat/index.php');
    }
    exit;
}

$error = '';

// Processa el formulari de login (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correu      = trim($_POST['correu'] ?? '');
    $contrasenya = $_POST['contrasenya'] ?? '';

    if (empty($correu) || empty($contrasenya)) {
        $error = 'Has d\'introduir el correu i la contrasenya.';
    } elseif (!filter_var($correu, FILTER_VALIDATE_EMAIL)) {
        $error = 'El format del correu electrònic no és vàlid.';
    } elseif (autenticar($correu, $contrasenya)) {
        // Autenticació correcta: redirigeix segons el rol
        if (esProfessor()) {
            header('Location: ' . BASE_URL . 'professorat/index.php');
        } else {
            header('Location: ' . BASE_URL . 'alumnat/index.php');
        }
        exit;
    } else {
        $error = 'Correu o contrasenya incorrectes.';
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accés - <?= APP_NAME ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a4f8a 0%, #15407a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box {
            background: white;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
        }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo h1 { color: #1a4f8a; font-size: 1.4rem; margin-top: 0.5rem; }
        .logo p { color: #888; font-size: 0.85rem; margin-top: 0.3rem; }
        .form-group { margin-bottom: 1.1rem; }
        label { display: block; font-weight: 600; margin-bottom: 0.35rem; font-size: 0.9rem; color: #444; }
        input {
            width: 100%;
            padding: 0.65rem 0.9rem;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: #1a4f8a; }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: #1a4f8a;
            color: white;
            border: none;
            border-radius: 7px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        .btn-login:hover { background: #15407a; }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 0.7rem 0.9rem;
            border-radius: 7px;
            margin-bottom: 1rem;
            font-size: 0.88rem;
        }
        .hint { font-size: 0.78rem; color: #aaa; text-align: center; margin-top: 1.2rem; }
    </style>
</head>
<body>
<div class="login-box">
    <div class="logo">
        <img src="imatges/montsia-removebg-preview.png" alt="Institut Montsià" style="height:80px;">
        <h1>Institut Montsià</h1>
        <p>Gestió de Material Informàtic</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="correu">Correu electrònic</label>
            <input
                type="email"
                id="correu"
                name="correu"
                placeholder="nom@alumnes.montsià.cat"
                value="<?= htmlspecialchars($_POST['correu'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                required
                autocomplete="email"
            >
        </div>
        <div class="form-group">
            <label for="contrasenya">Contrasenya</label>
            <input
                type="password"
                id="contrasenya"
                name="contrasenya"
                placeholder="••••••••"
                required
                autocomplete="current-password"
            >
        </div>
        <button type="submit" class="btn-login">Iniciar sessió</button>
    </form>
    <p class="hint">Curs 2025-2026 &mdash; CFGS ASIX</p>
</div>
</body>
</html>