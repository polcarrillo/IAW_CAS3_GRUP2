<?php
// 1. INCLOURE FITXERS DEL POL I L'ABDU
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

// Només els professors poden entrar aquí (seguretat del Pol)
comprovarRol(ROL_PROFESSOR);

$db = getDB(); // Connectem a la base de dades estil PDO (estil Pol)

// 2. LÒGICA PER GUARDAR LES DADES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = $_POST['nom'] ?? '';
    $tipus     = $_POST['tipus'] ?? '';
    $num_serie = $_POST['num_serie'] ?? '';
    $id_aula   = $_POST['id_aula'] ?? '';

    try {
        // Preparem la consulta per evitar atacs (SQL Injection)
        $stmt = $db->prepare("INSERT INTO Material (nom, tipus, num_serie, id_aula) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nom, $tipus, $num_serie, $id_aula]);
        
        // Si tot va bé, fem servir el sistema de missatges del Pol
        setMissatge("Maquinari registrat correctament!", 'success');
    } catch (Exception $e) {
        setMissatge("Error al guardar: " . $e->getMessage(), 'error');
    }
}

// 3. DIBUIXAR LA PÀGINA (HARMÒNIA VISUAL)
capçalera('Afegir Nou Maquinari'); // Crida la capçalera del Pol
mostrarMissatge(); // Mostra el globus de "Tot correcte" o "Error"
?>

<div class="card">
    <form method="POST" action="nou_maquinari.php">
        
        <div class="form-group">
            <label>Nom del dispositiu:</label>
            <input type="text" name="nom" required placeholder="Ex: Portàtil HP ProBook">
        </div>

        <div class="form-group">
            <label>Tipus de material:</label>
            <select name="tipus" required>
                <option value="">-- Selecciona un tipus --</option>
                <option value="Portàtil">Portàtil</option>
                <option value="Sobretaula">Sobretaula</option>
                <option value="Projector">Projector</option>
                <option value="Monitor">Monitor</option>
                <option value="Altres">Altres</option>
            </select>
        </div>

        <div class="form-group">
            <label>Número de Sèrie:</label>
            <input type="text" name="num_serie" required placeholder="Ex: SN123456789">
        </div>

        <div class="form-group">
            <label>ID de l'Aula:</label>
            <input type="number" name="id_aula" required placeholder="Ex: 1">
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Guardar Maquinari</button>
            <a href="index.php" class="btn btn-danger" style="background:#999;">Cancel·lar</a>
        </div>

    </form>
</div>

<?php
peu(); // Crida el peu de pàgina del Pol
?>
