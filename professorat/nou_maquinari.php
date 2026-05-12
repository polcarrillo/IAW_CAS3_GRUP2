<?php
// Carreguem configuració i disseny
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguretat per a professors
if (function_exists('comprovarRol')) {
    comprovarRol(ROL_PROFESSOR);
}

$db = getDB();

// Si l'usuari envia el formulari
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recollim totes les dades del formulari
    $etiqueta = $_POST['etiqueta'] ?? '';
    $numSerie = $_POST['numSerie'] ?? '';
    $macEth   = $_POST['macEthernet'] ?? null;
    $macWifi  = $_POST['macWifi'] ?? null;
    $sace     = $_POST['sace'] ?? null;
    
    // Fem servir els IDs 1 que hem creat abans
    $idTipus = 1; 
    $idUbicacio = 1;

    try {
        // Inserim les dades incloent els nous camps de xarxa i SACE
        $sql = "INSERT INTO Material (idTipus, etiquetaDepInf, numSerie, macEthernet, macWifi, SACE, idUbicacio) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipus, $etiqueta, $numSerie, $macEth, $macWifi, $sace, $idUbicacio]);
        
        setMissatge("Equip registrat amb tots els detalls", 'success');
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
            <label>Model / Etiqueta:</label>
            <input type="text" name="etiqueta" required>
        </div>

        <div class="form-group">
            <label>Numero de Serie:</label>
            <input type="text" name="numSerie" required>
        </div>

        <div class="form-group">
            <label>MAC Ethernet (Cable):</label>
            <input type="text" name="macEthernet" placeholder="00:00:00:00:00:00">
        </div>

        <div class="form-group">
            <label>MAC WiFi (Sense fils):</label>
            <input type="text" name="macWifi" placeholder="00:00:00:00:00:00">
        </div>

        <div class="form-group">
            <label>Codi SACE:</label>
            <input type="text" name="sace" placeholder="Codi d'inventari de la Generalitat">
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Guardar Equip Complet</button>
            <a href="index.php" class="btn" style="background:#ccc; color:black; text-decoration:none; padding:8px; border-radius:4px;">Tornar</a>
        </div>

    </form>
</div>

<?php peu(); ?>
