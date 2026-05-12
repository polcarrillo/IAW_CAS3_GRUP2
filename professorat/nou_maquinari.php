k<?php
// 1. CARREGAR EL SISTEMA
// Fitxers del projecte per a que tot funcioni correctament
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

// Iniciem la sessio de l'usuari si no ho esta
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SEGURETAT
// Verifiquem que qui entra es un professor
if (function_exists('comprovarRol')) {
    comprovarRol(ROL_PROFESSOR);
}

// Connectem a la base de dades
$db = getDB();

// 3. RECOLLIR DADES I GUARDAR
// Si l'usuari envia el formulari, guardem la info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Agafem les dades netejant espais buits
    $etiqueta = isset($_POST['etiqueta']) ? trim($_POST['etiqueta']) : '';
    $numSerie = isset($_POST['numSerie']) ? trim($_POST['numSerie']) : '';
    $macEth   = isset($_POST['macEthernet']) ? trim($_POST['macEthernet']) : '';
    $macWifi  = isset($_POST['macWifi']) ? trim($_POST['macWifi']) : '';
    $sace     = isset($_POST['sace']) ? trim($_POST['sace']) : '';
    $dataAdq  = isset($_POST['dataAdquisicio']) ? $_POST['dataAdquisicio'] : null;
    
    // IDs de prova (creats manualment al phpMyAdmin)
    $idTipus = 1; 
    $idUbicacio = 1;

    try {
        // Ordre SQL per insertar a la taula Material
        $sql = "INSERT INTO Material (idTipus, etiquetaDepInf, numSerie, macEthernet, macWifi, SACE, dataAdquisicio, idUbicacio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        // Enviem les dades en l'ordre de la taula
        $stmt->execute([$idTipus, $etiqueta, $numSerie, $macEth, $macWifi, $sace, $dataAdq, $idUbicacio]);
        
        setMissatge("Equip guardat correctament a la base de dades", 'success');
    } catch (PDOException $e) {
        setMissatge("Error al guardar: " . $e->getMessage(), 'error');
    }
}

// 4. DISSENY DE LA PAGINA
// Fem servir la capçalera
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

        <div class="form-group">
            <label>MAC WiFi (Sense fils):</label>
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

<?php 
// Peu de pagina del projecte
peu(); 
?>
