<?php
/**
 * nou_alumne.php
 * Pàgina per crear un nou alumne al sistema.
 * Permet al professor afegir un alumne nou amb les seves dades
 * i crear les credencials d'accés a l'aplicació.
 *
 * @package GestioMaterial
 * @author  El teu nom
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

requerirProfessor();

$error  = '';
$exit   = '';

// Processa el formulari (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom        = trim($_POST['nom']        ?? '');
    $cognom1    = trim($_POST['cognom1']    ?? '');
    $cognom2    = trim($_POST['cognom2']    ?? '');
    $correu     = trim($_POST['correu']     ?? '');
    $grupClasse = trim($_POST['grupClasse'] ?? '');
    $contrasenya = $_POST['contrasenya']   ?? '';

    // Validacions
    if (empty($nom) || empty($cognom1) || empty($correu) || empty($contrasenya)) {
        $error = 'Els camps nom, cognom, correu i contrasenya són obligatoris.';
    } elseif (!filter_var($correu, FILTER_VALIDATE_EMAIL)) {
        $error = 'El format del correu electrònic no és vàlid.';
    } elseif (strlen($contrasenya) < 6) {
        $error = 'La contrasenya ha de tenir almenys 6 caràcters.';
    } else {
        $db = getDB();

        // Comprova si el correu ja existeix
        $check = $db->prepare("SELECT id FROM Usuaris WHERE correu = ? LIMIT 1");
        $check->execute([$correu]);
        if ($check->fetch()) {
            $error = 'Ja existeix un usuari amb aquest correu electrònic.';
        } else {
            try {
                $db->beginTransaction();

                // Insereix a la taula Alumnes
                $stmtAlumne = $db->prepare(
                    "INSERT INTO Alumnes (nom, cognom1, cognom2, correu, grupClasse)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmtAlumne->execute([$nom, $cognom1, $cognom2, $correu, $grupClasse]);

                // Insereix a la taula Usuaris (credencials d'accés)
                $hash = password_hash($contrasenya, PASSWORD_DEFAULT);
                $stmtUsuari = $db->prepare(
                    "INSERT INTO Usuaris (nom, cognom1, cognom2, correu, contrasenya_hash, rol, grupClasse, actiu)
                     VALUES (?, ?, ?, ?, ?, 'alumne', ?, 1)"
                );
                $stmtUsuari->execute([$nom, $cognom1, $cognom2, $correu, $hash, $grupClasse]);

                $db->commit();

                setMissatge("Alumne $nom $cognom1 creat correctament.", 'success');
                header('Location: ' . BASE_URL . 'professorat/index.php');
                exit;

            } catch (PDOException $e) {
                $db->rollBack();
                $error = 'Error en crear l\'alumne: ' . $e->getMessage();
            }
        }
    }
}

capçalera('Nou Alumne');
mostrarMissatge();
?>

<div class="card">
    <h3 style="color:#1a4f8a; margin-bottom:1.5rem;">Dades del nou alumne</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">

        <!-- Dades personals -->
        <h4 style="color:#555; margin-bottom:1rem; font-size:0.95rem; text-transform:uppercase; letter-spacing:0.05em;">
            Dades personals
        </h4>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label for="nom">Nom <span style="color:#e74c3c;">*</span></label>
                <input
                    type="text"
                    id="nom"
                    name="nom"
                    placeholder="Ex: Joan"
                    value="<?= h($_POST['nom'] ?? '') ?>"
                    required
                    maxlength="50"
                >
            </div>

            <div class="form-group">
                <label for="cognom1">Primer cognom <span style="color:#e74c3c;">*</span></label>
                <input
                    type="text"
                    id="cognom1"
                    name="cognom1"
                    placeholder="Ex: García"
                    value="<?= h($_POST['cognom1'] ?? '') ?>"
                    required
                    maxlength="50"
                >
            </div>

            <div class="form-group">
                <label for="cognom2">Segon cognom</label>
                <input
                    type="text"
                    id="cognom2"
                    name="cognom2"
                    placeholder="Ex: López"
                    value="<?= h($_POST['cognom2'] ?? '') ?>"
                    maxlength="50"
                >
            </div>

            <div class="form-group">
                <label for="grupClasse">Grup / Classe</label>
                <input
                    type="text"
                    id="grupClasse"
                    name="grupClasse"
                    placeholder="Ex: ASIX1"
                    value="<?= h($_POST['grupClasse'] ?? '') ?>"
                    maxlength="10"
                >
            </div>
        </div>

        <!-- Credencials d'accés -->
        <h4 style="color:#555; margin-top:1rem; margin-bottom:1rem; font-size:0.95rem; text-transform:uppercase; letter-spacing:0.05em;">
            Credencials d'accés
        </h4>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label for="correu">Correu electrònic <span style="color:#e74c3c;">*</span></label>
                <input
                    type="email"
                    id="correu"
                    name="correu"
                    placeholder="nom@iesmontsia.org"
                    value="<?= h($_POST['correu'] ?? '') ?>"
                    required
                    maxlength="100"
                >
            </div>

            <div class="form-group">
                <label for="contrasenya">Contrasenya <span style="color:#e74c3c;">*</span></label>
                <input
                    type="password"
                    id="contrasenya"
                    name="contrasenya"
                    placeholder="Mínim 6 caràcters"
                    required
                    minlength="6"
                >
            </div>
        </div>

        <div style="background:#fff8e1; border-left:4px solid #f0a500; padding:0.8rem 1rem; border-radius:6px; margin-bottom:1.5rem; font-size:0.85rem; color:#555;">
            <strong style="color:#e67e22;">Nota:</strong> La contrasenya s'emmagatzemarà de forma segura (xifrada). 
            Comunica-la a l'alumne perquè pugui accedir al sistema.
        </div>

        <!-- Botons -->
        <div style="display:flex; gap:1rem;">
            <button type="submit" class="btn btn-success">Crear alumne</button>
            <a href="<?= BASE_URL ?>professorat/index.php" class="btn btn-primary">Cancel·lar</a>
        </div>

    </form>
</div>

<?php peu(); ?>
