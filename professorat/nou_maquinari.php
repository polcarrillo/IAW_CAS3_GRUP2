<?php
// 1. Incloure la connexió i l'autenticació
include_once("../includes/db.php");
include_once("../includes/auth.php"); // Per assegurar que només entren profes
include_once("../includes/layout.php"); // Per mantenir l'estètica del Pol

// 2. Lògica d'inserció quan enviem el formulari
$missatge = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $tipus = $_POST['tipus'];
    $num_serie = $_POST['num_serie'];
    $id_aula = $_POST['id_aula'];

    $sql = "INSERT INTO Material (nom, tipus, num_serie, id_aula) VALUES ('$nom', '$tipus', '$num_serie', '$id_aula')";
    
    if (mysqli_query($conn, $sql)) {
        $missatge = "<div class='alert alert-success'>Maquinari registrat correctament!</div>";
    } else {
        $missatge = "<div class='alert alert-danger'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// 3. Mostrar la capçalera (definida al layout.php del Pol)
mostrar_header("Afegir Nou Maquinari");
?>

<div class="container">
    <h2>Registrar Nou Maquinari</h2>
    <?php echo $missatge; ?>

    <form method="POST" action="nou_maquinari.php" class="form-style">
        <label>Nom del dispositiu:</label>
        <input type="text" name="nom" required placeholder="Ex: Portàtil HP">

        <label>Tipus:</label>
        <select name="tipus">
            <option value="Portàtil">Portàtil</option>
            <option value="Sobretaula">Sobretaula</option>
            <option value="Projector">Projector</option>
            <option value="Altres">Altres</option>
        </select>

        <label>Número de Sèrie:</label>
        <input type="text" name="num_serie" required>

        <label>Aula (ID):</label>
        <input type="number" name="id_aula" required>

        <button type="submit" class="btn-save">Guardar Maquinari</button>
    </form>
</div>

<?php
// 4. Mostrar el peu de pàgina
mostrar_footer();
?>
