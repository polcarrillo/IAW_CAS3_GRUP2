<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (function_exists('comprovarRol')) {
    comprovarRol(ROL_PROFESSOR);
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Adaptem els noms de les variables al que demana la teva BD
    $etiqueta   = $_POST['etiqueta'] ?? '';
    $idTipus    = $_POST['idTipus'] ?? '';
    $numSerie   = $_POST['numSerie'] ?? '';
    $idUbicacio = $_POST['idUbicacio'] ?? '';

    try {
        // LA SQL AMB ELS NOMS REALS DE LA TEVA CAPTURA
        $sql = "INSERT INTO Material (idTipus, etiquetaDepInf, numSerie, idUbicacio) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipus, $etiqueta, $numSerie, $idUbicacio]);
        
        setMissatge("Maquinari registrat: $etiqueta", 'success');
    } catch (PDOException $e) {
        setMissatge("Error de base de dades: " . $e->getMessage(), 'error');
    }
}

capçalera('Registrar Nou Maquinari');
mostrarMissatge();
?>

<div class="card">
    <form method="POST" action="nou_maquinari.php">
        
        <div class="form-group">
            <label>Etiqueta Departament (Nom):</label>
            <input type="text" name="etiqueta" required placeholder="Ex: PC-SISTEMES-01">
        </div>

        <div class="form-group">
            <label>Tipus de Material:</label>
            <select name="idTipus" required>
                <option value="1">Portàtil</option>
                <option value="2">Sobretaula</option>
                <option value="3">Projector</option>
            </select>
        </div>

        <div class="form-group">
            <label>Número de Sèrie:</label>
            <input type="text" name="numSerie" required placeholder="Ex: SN12345">
        </div>

        <div class="form-group">
            <label>ID Ubicació (Aula):</label>
            <input type="number" name="idUbicacio" required placeholder="Ex: 1">
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Guardar a la Base de Dades</button>
            <a href="index.php" class="btn" style="background:#ccc; color:black;">Tornar</a>
        </div>

    </form>
</div>

<?php peu(); ?>
