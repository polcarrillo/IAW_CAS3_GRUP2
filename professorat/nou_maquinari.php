<?php
// EL FITXER HA DE COMENÇAR AQUÍ DALT SENSE CAP ESPAI NI LLETRA
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

// Genera el pròxim idInventari en format INV-0001
function generarIdInventari($db) {
    $stmt = $db->query("SELECT idInventari FROM Material WHERE idInventari LIKE 'INV-%' ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/INV-(\d+)$/', $last, $m)) {
        $num = (int)$m[1] + 1;
    } else {
        $num = 1;
    }
    return 'INV-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

$tipusOpcions = ['Portàtil', 'Pantalla', 'Tauleta', 'Projector', 'Torre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipusSeleccionat = trim($_POST['tipus']         ?? '');
    $model            = trim($_POST['model']          ?? '');
    $etiqueta         = trim($_POST['etiquetaDepInf'] ?? '');
    $numSerie         = trim($_POST['numSerie']        ?? '');
    $macEth           = trim($_POST['macEthernet']    ?? '') ?: null;
    $macWifi          = trim($_POST['macWifi']         ?? '') ?: null;
    $sace             = trim($_POST['sace']            ?? '');
    $dataAdq          = !empty($_POST['dataAdquisicio']) ? $_POST['dataAdquisicio'] : null;
    $idInventari      = generarIdInventari($db);
    $idUbicacio       = 1; // Valor per defecte

    try {
        // Busca o crea l'entrada a TipusMaterial per aquest tipus + model
        $stmtFind = $db->prepare("SELECT id FROM TipusMaterial WHERE tipus = ? AND model = ?");
        $stmtFind->execute([$tipusSeleccionat, $model]);
        $idTipus = $stmtFind->fetchColumn();

        if (!$idTipus) {
            $stmtTipus = $db->prepare("INSERT INTO TipusMaterial (tipus, model) VALUES (?, ?)");
            $stmtTipus->execute([$tipusSeleccionat, $model]);
            $idTipus = $db->lastInsertId();
        }

        $sql = "INSERT INTO Material 
                    (idTipus, idInventari, etiquetaDepInf, numSerie, macEthernet, macWifi, SACE, dataAdquisicio, idUbicacio)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipus, $idInventari, $etiqueta, $numSerie, $macEth, $macWifi, $sace, $dataAdq, $idUbicacio]);

        setMissatge("Equip {$idInventari} guardat correctament.", 'success');
        header('Location: nou_maquinari.php');
        exit;

    } catch (PDOException $e) {
        setMissatge("Error al guardar: " . $e->getMessage(), 'error');
    }
}

$nextInventari = generarIdInventari($db);

capçalera('Afegir Nou Maquinari');
mostrarMissatge();
?>

<div class="card">
    <h3 style="margin-bottom:1.5rem; color:#1a4f8a;">Registrar nou equip</h3>
    <form method="POST" action="nou_maquinari.php">

        <!-- Tipus -->
        <div class="form-group">
            <label for="tipus">Tipus d'equip: <span style="color:red;">*</span></label>
            <select name="tipus" id="tipus" required>
                <option value="">-- Selecciona un tipus --</option>
                <?php foreach ($tipusOpcions as $t): ?>
                    <option value="<?= h($t) ?>" <?= (($_POST['tipus'] ?? '') === $t ? 'selected' : '') ?>>
                        <?= h($t) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Model -->
        <div class="form-group">
            <label for="model">Model: <span style="color:red;">*</span></label>
            <input type="text" id="model" name="model" placeholder="p.ex. HP ProBook 450 G9"
                   value="<?= h($_POST['model'] ?? '') ?>" required>
        </div>

        <!-- ID Inventari (auto) -->
        <div class="form-group">
            <label for="idInventari">ID d'inventari:</label>
            <input type="text" id="idInventari" name="idInventari"
                   value="<?= h($nextInventari) ?>"
                   readonly
                   style="background:#f0f4f8; color:#555; cursor:not-allowed; font-weight:600; letter-spacing:1px;">
            <small style="color:#888;">Assignat automàticament.</small>
        </div>

        <!-- Etiqueta Dep. Informàtica -->
        <div class="form-group">
            <label for="etiquetaDepInf">Etiqueta Dep. Informàtica: <span style="color:red;">*</span></label>
            <input type="text" id="etiquetaDepInf" name="etiquetaDepInf"
                   placeholder="p.ex. AULA3-PC05"
                   value="<?= h($_POST['etiquetaDepInf'] ?? '') ?>" required>
        </div>

        <!-- Número de Sèrie -->
        <div class="form-group">
            <label for="numSerie">Número de sèrie: <span style="color:red;">*</span></label>
            <input type="text" id="numSerie" name="numSerie"
                   placeholder="p.ex. 5CD1234XYZ"
                   value="<?= h($_POST['numSerie'] ?? '') ?>" required>
        </div>

        <!-- MAC Ethernet (opcional) -->
        <div class="form-group">
            <label for="macEthernet">MAC Ethernet (cable) <span style="color:#888; font-weight:400;">— opcional</span>:</label>
            <input type="text" id="macEthernet" name="macEthernet"
                   placeholder="p.ex. AA:BB:CC:DD:EE:FF"
                   value="<?= h($_POST['macEthernet'] ?? '') ?>">
        </div>

        <!-- MAC WiFi (opcional) -->
        <div class="form-group">
            <label for="macWifi">MAC WiFi (sense fils) <span style="color:#888; font-weight:400;">— opcional</span>:</label>
            <input type="text" id="macWifi" name="macWifi"
                   placeholder="p.ex. 11:22:33:44:55:66"
                   value="<?= h($_POST['macWifi'] ?? '') ?>">
        </div>

        <!-- SACE -->
        <div class="form-group">
            <label for="sace">Codi SACE: <span style="color:red;">*</span></label>
            <input type="text" id="sace" name="sace"
                   placeholder="p.ex. SACE-00123"
                   value="<?= h($_POST['sace'] ?? '') ?>" required>
        </div>

        <!-- Data d'adquisició -->
        <div class="form-group">
            <label for="dataAdquisicio">Data d'adquisició: <span style="color:red;">*</span></label>
            <input type="date" id="dataAdquisicio" name="dataAdquisicio"
                   value="<?= h($_POST['dataAdquisicio'] ?? '') ?>" required>
        </div>

        <!-- Botons -->
        <div style="margin-top:1.5rem; display:flex; gap:1rem;">
            <button type="submit" class="btn btn-primary">Guardar equip</button>
            <a href="index.php" class="btn" style="background:#ccc; color:#333; text-decoration:none; padding:8px 16px; border-radius:4px;">
                Tornar
            </a>
        </div>

    </form>
</div>

<?php peu(); ?>