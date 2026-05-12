<?php
/**
 * nou_maquinari.php
 * Formulari per afegir nous dispositius al sistema.
 */

// 1. CARREGAR DEPENDÈNCIES (Fitxers del Pol i l'Abdu)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

// Iniciem la sessió per poder llegir qui és l'usuari
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SEGURETAT: Només professors
// Si la funció del Pol existeix la usem, si no, fem un control manual ràpid
if (function_exists('comprovarRol')) {
    comprovarRol(ROL_PROFESSOR);
} else {
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'professor') {
        die("Error: No tens permisos de professor per veure aquesta pàgina.");
    }
}

// Connectem a la base de dades (Estil PDO del Pol)
$db = getDB();

// 3. LÒGICA DE GUARDAR (S'executa quan prems el botó)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = $_POST['nom'] ?? '';
    $tipus     = $_POST['tipus'] ?? '';
    $num_serie = $_POST['num_serie'] ?? '';
    $id_aula   = $_POST['id_aula'] ?? '';

    try {
        // Preparem la consulta per seguretat (evita que ens hackegin la BD)
        $sql = "INSERT INTO Material (nom, tipus, num_serie, id_aula) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$nom, $tipus, $num_serie, $id_aula]);
        
        // Usem el sistema de missatges verds/vermells del Pol
        if (function_exists('setMissatge')) {
            setMissatge("S'ha guardat el material: $nom", 'success');
        }
    } catch (PDOException $e) {
        if (function_exists('setMissatge')) {
            setMissatge("Error de base de dades: " . $e->getMessage(), 'error');
        }
    }
}

// 4. DIBUIXAR LA PÀGINA
// capçalera() posa el logo de l'Institut, el menú blau i el CSS
capçalera('Registrar Nou Maquinari');

// mostrarMissatge() treu el globus de text si s'ha guardat correctament
if (function_exists('mostrarMissatge')) {
    mostrarMissatge();
}
?>

<div class="card">
    <p style="margin-bottom: 1rem; color: #666;">Omple les dades per afegir un nou equip a l'inventari del centre.</p>

    <form method="POST" action="nou_maquinari.php">
        
        <div class="form-group">
            <label for="nom">Nom del dispositiu</label>
            <input type="text" id="nom" name="nom" required placeholder="Ex: Portàtil HP 250 G8">
        </div>

        <div class="form-group">
            <label for="tipus">Tipus de maquinari</label>
            <select id="tipus" name="tipus" required>
                <option value="">-- Selecciona --</option>
                <option value="Portàtil">Portàtil</option>
                <option value="Sobretaula">Sobretaula</option>
                <option value="Projector">Projector</option>
                <option value="Monitor">Monitor</option>
                <option value="Tauleta">Tauleta</option>
            </select>
        </div>

        <div class="form-group">
            <label for="num_serie">Número de Sèrie</label>
            <input type="text" id="num_serie" name="num_serie" required placeholder="SN-XXXXXXX">
        </div>

        <div class="form-group">
            <label for="id_aula">ID Aula (Destí)</label>
            <input type="number" id="id_aula" name="id_aula" required placeholder="Ex: 1">
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Guardar a l'Inventari</button>
            <a href="index.php" class="btn" style="background: #e0e0e0; color: #333;">Tornar</a>
        </div>

    </form>
</div>

<?php
// peu() tanca el main i posa el copyright del curs
peu();
?>
