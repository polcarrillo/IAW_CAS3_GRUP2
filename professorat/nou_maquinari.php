<?php
// Carreguem els fitxers de configuració i disseny
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

// Iniciem la sessió
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifiquem que l'usuari sigui professor
if (function_exists('comprovarRol')) {
    comprovarRol(ROL_PROFESSOR);
}

// Connectem a la base de dades
$db = getDB();

// Si l'usuari envia el formulari
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etiqueta = $_POST['etiqueta'] ?? '';
    $numSerie = $_POST['numSerie'] ?? '';
    
    // Fem servir els IDs que acabem de crear manualment
    $idTipus = 1; 
    $idUbicacio = 1;

    try {
        // Inserim les dades a la taula Material
        $sql = "INSERT INTO Material (idTipus, etiquetaDepInf, numSerie, idUbicacio) 
                VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipus, $etiqueta, $numSerie, $idUbicacio]);
        
        setMissatge("Dades guardades correctament", 'success');
    } catch (PDOException $e) {
        setMissatge("Error al guardar: " . $e->getMessage(), 'error');
    }
}

// Dibuixem la capçalera de la pàgina
capçalera('Afegir Nou Maquinari');
mostrarMissatge();
?>

<div class="card">
    <form method="POST" action="nou_maquinari.php">
        
        <div class="form-group">
            <label>Nom o Etiqueta del material:</label>
            <input type="text" name="etiqueta" required>
        </div>

        <div class="form-group">
            <label>Numero de Serie:</label>
            <input type="text" name="numSerie" required>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Guardar a la Base de Dades</button>
            <a href="index.php" class="btn" style="background:#ccc; color:black; text-decoration:none; padding:8px; border-radius:4px;">Tornar</a>
        </div>

    </form>
</div>

<?php 
// Dibuixem el peu de pàgina
peu(); 
?>
