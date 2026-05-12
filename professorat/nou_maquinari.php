<?php
// EL FITXER HA DE COMENÇAR AQUÍ DALT SENSE CAP ESPAI NI LLETRA
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

// Iniciem la sessió només si no està ja activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguretat per a professors
if (function_exists('comprovarRol')) {
    comprovarRol(ROL_PROFESSOR);
}

$db = getDB();

// Lògica per guardar les dades
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etiqueta = isset($_POST['etiqueta']) ? trim($_POST['etiqueta']) : '';
    $numSerie = isset($_POST['numSerie']) ? trim($_POST['numSerie']) : '';
    $macEth   = isset($_POST['macEthernet']) ? trim($_POST['macEthernet']) : '';
    $macWifi  = isset($_POST['macWifi']) ? trim($_POST['macWifi']) : '';
    $sace     = isset($_POST['sace']) ? trim($_POST['sace']) : '';
    $dataAdq  = !empty($_POST['dataAdquisicio']) ? $_POST['dataAdquisicio'] : null;
    
    // IDs de prova (els que hem creat al phpMyAdmin)
    $idTipus = 1; 
    $idUbicacio = 1;

    try {
        $sql = "INSERT INTO Material (idTipus, etiquetaDepInf, numSerie, macEthernet, macWifi, SACE, dataAdquisicio, idUbicacio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipus, $etiqueta, $numSerie, $macEth, $macWifi, $sace, $dataAdq, $idUbicacio]);
        
        setMissatge("Equip guardat correctament a la base de dades", 'success');
    } catch (PDOException $e) {
        setMissatge("Error al guardar: " . $e->getMessage(), 'error');
    }
}

capçalera('Afegir Nou Maquinari');
mostrarMissatge();
?>

<div class="card">
    <form method="POST" action="nou_maquinari.php">
        <div class="form-group">
            <label>Model / Nom de l'equip:</label>
            <input type="text" name="etiqueta" required>
        </div>
        <div class="form-group">
            <label>Numero de Serie:</label>
            <input type="text" name="numSerie" required>
        </div>
        <div class="form-group">
            <label>MAC Ethernet (Cable):</label>
            <input type="text" name="macEthernet">
        </div>
        <div class="form-group :">
            <label>MAC WIFI (Sense fils):</label>
            <input type="text" name="macWifi">
        </div>
        <div class="form-group">
            <label>Codi SACE:</label>
            <input type="text" name="sace">
        </div>
        <div class="form-group">
            <label>Data d'Adquisicio:</label>
            <input type="date" name="dataAdquisicio">
        </div>
        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Guardar Equip</button>
            <a href="index.php" class="btn" style="background:#ccc; color:black; text-decoration:none; padding:8px; border-radius:4px;">Tornar</a>
        </div>
    </form>
</div>

<?php peu(); ?>
