<?php
// 1. CARREGAR FITXERS EXTERNS
// Incloem la configuració, la base de dades, la seguretat i el disseny
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

// Iniciem la sessió per saber qui és l'usuari
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SEGURETAT
// Verifiquem que l'usuari sigui un professor
if (function_exists('comprovarRol')) {
    comprovarRol(ROL_PROFESSOR);
}

// Connectem a la base de dades
$db = getDB();

// 3. RECULLIR DADES DEL FORMULARI
// Aquest codi s'executa quan l'usuari clica el botó de guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $etiqueta   = $_POST['etiqueta'] ?? '';
    $idTipus    = $_POST['idTipus'] ?? '';
    $numSerie   = $_POST['numSerie'] ?? '';
    $idUbicacio = $_POST['idUbicacio'] ?? '';

    try {
        // Preparem l'ordre per insertar les dades a la taula Material
        // No enviem l'ID perquè la base de dades el posarà sol (Auto-increment)
        $sql = "INSERT INTO Material (idTipus, etiquetaDepInf, numSerie, idUbicacio) 
                VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipus, $etiqueta, $numSerie, $idUbicacio]);
        
        // Missatge de confirmació
        if (function_exists('setMissatge')) {
            setMissatge("Maquinari registrat correctament: $etiqueta", 'success');
        }
    } catch (PDOException $e) {
        // Missatge si hi ha un error
        if (function_exists('setMissatge')) {
            setMissatge("Error al guardar les dades: " . $e->getMessage(), 'error');
        }
    }
}

// 4. MOSTRAR LA PÀGINA (DISSENY)
// Fem servir les funcions del Pol per la capçalera i el peu
capçalera('Afegir Nou Maquinari');

// Mostrem el missatge d'èxit o error si n'hi ha un
if (function_exists('mostrarMissatge')) {
    mostrarMissatge();
}
?>

<div class="card">
    <form method="POST" action="nou_maquinari.php">
        
        <div class="form-group">
            <label>Model o Etiqueta:</label>
            <input type="text" name="etiqueta" required>
        </div>

        <div class="form-group">
            <label>Tipus de Material:</label>
            <select name="idTipus" required>
                <option value="1">Portatil</option>
                <option value="2">Sobretaula</option>
                <option value="3">Projector</option>
            </select>
        </div>

        <div class="form-group">
            <label>Numero de Serie:</label>
            <input type="text" name="numSerie" required>
        </div>

        <div class="form-group">
            <label>ID de l'Aula (Ubicacio):</label>
            <input type="number" name="idUbicacio" required>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Guardar Maquinari</button>
            <a href="index.php" class="btn" style="background:#ccc; color:black; text-decoration:none;">Tornar</a>
        </div>

    </form>
</div>

<?php 
// Tancem el disseny de la pàgina
peu(); 
?>
