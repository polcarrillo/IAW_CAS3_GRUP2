<?php
/**
 * professorat/gestionar_dispositiu.php
 * Gestió completa d'un dispositiu: dades, assignació i incidències.
 * S'accedeix des de dispositius_tipus.php passant ?id=idMaterial
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/../includes/layout.php';

requerirProfessor();

$db         = getDB();
$idMaterial = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idMaterial <= 0) {
    setMissatge('ID de dispositiu no vàlid.', 'error');
    header('Location: dispositius_tipus.php');
    exit;
}

// ── Carrega el dispositiu ────────────────────────────────────────────────────
$stmtDev = $db->prepare(
    "SELECT m.*, tm.tipus, COALESCE(tm.model,'') AS model, COALESCE(u.nom,'') AS nomAula
     FROM Material m
     JOIN TipusMaterial tm ON tm.id = m.idTipus
     LEFT JOIN Ubicacions u ON u.id = m.idUbicacio
     WHERE m.id = ? LIMIT 1"
);
$stmtDev->execute([$idMaterial]);
$dispositiu = $stmtDev->fetch();

if (!$dispositiu) {
    setMissatge('Dispositiu no trobat.', 'error');
    header('Location: dispositius_tipus.php');
    exit;
}

// ── Llistes auxiliars ────────────────────────────────────────────────────────
$tipusList  = $db->query("SELECT id, tipus, model FROM TipusMaterial ORDER BY tipus")->fetchAll();
$ubicacions = $db->query("SELECT id, nom FROM Ubicacions ORDER BY nom")->fetchAll();
$estats     = $db->query("SELECT id, estat FROM Estats ORDER BY estat")->fetchAll();
$alumnes    = $db->query(
    "SELECT id, CONCAT(nom,' ',cognom1,' ',COALESCE(cognom2,'')) AS nomComplet, grupClasse
     FROM Alumnes ORDER BY cognom1, nom"
)->fetchAll();

// ── Assignació activa ────────────────────────────────────────────────────────
$stmtAss = $db->prepare(
    "SELECT ass.id AS idAssignacio, ass.dataInici, ass.dataFinal,
            al.id AS idAlumne, CONCAT(al.nom,' ',al.cognom1,' ',COALESCE(al.cognom2,'')) AS nomAlumne,
            al.correu, al.grupClasse
     FROM Assignacions ass
     JOIN Alumnes al ON al.id = ass.idAlumne
     WHERE ass.idMaterial = ? AND (ass.dataFinal IS NULL OR ass.dataFinal >= CURDATE())
     ORDER BY ass.dataInici DESC LIMIT 1"
);
$stmtAss->execute([$idMaterial]);
$assignacioActiva = $stmtAss->fetch();

// ── Incidències ──────────────────────────────────────────────────────────────
$stmtInc = $db->prepare(
    "SELECT inc.id, inc.informacio, inc.dataOberta, inc.dataTancada,
            e.estat, COALESCE(CONCAT(al.nom,' ',al.cognom1),'—') AS nomAlumne
     FROM Incidencies inc
     LEFT JOIN Estats e ON e.id = inc.idEstat
     LEFT JOIN Alumnes al ON al.id = inc.idAlumne
     WHERE inc.idDispositiu = ?
     ORDER BY inc.dataOberta DESC"
);
$stmtInc->execute([$idMaterial]);
$incidencies = $stmtInc->fetchAll();
$incOberta   = array_filter($incidencies, fn($i) => $i['dataTancada'] === null);

// ════════════════════════════════════════════════════════════════════════════
// PROCESSAR ACCIONS POST
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio = $_POST['accio'] ?? '';

    // ── 1. Editar dades del dispositiu ──────────────────────────────────────
    if ($accio === 'editar_dispositiu') {
        $idTipus        = (int)($_POST['idTipus'] ?? 0);
        $idUbicacio     = (int)($_POST['idUbicacio'] ?? 0);
        $idInventari    = trim($_POST['idInventari'] ?? '');
        $etiqueta       = trim($_POST['etiquetaDepInf'] ?? '');
        $numSerie       = trim($_POST['numSerie'] ?? '');
        $macEthernet    = trim($_POST['macEthernet'] ?? '');
        $macWifi        = trim($_POST['macWifi'] ?? '');
        $sace           = trim($_POST['SACE'] ?? '');
        $dataAdquisicio = $_POST['dataAdquisicio'] ?: null;

        if ($idTipus <= 0 || empty($idInventari)) {
            setMissatge('Tipus i número d\'inventari són obligatoris.', 'error');
        } else {
            // Comprova inventari duplicat (excloent el mateix dispositiu)
            $stmtChk = $db->prepare("SELECT id FROM Material WHERE idInventari = ? AND id != ?");
            $stmtChk->execute([$idInventari, $idMaterial]);
            if ($stmtChk->fetch()) {
                setMissatge("Ja existeix un altre dispositiu amb l'inventari «{$idInventari}».", 'error');
            } else {
                try {
                    $db->prepare(
                        "UPDATE Material SET idTipus=?, idInventari=?, etiquetaDepInf=?,
                         numSerie=?, macEthernet=?, macWifi=?, SACE=?,
                         dataAdquisicio=?, idUbicacio=? WHERE id=?"
                    )->execute([
                        $idTipus, $idInventari, $etiqueta ?: null,
                        $numSerie ?: null, $macEthernet ?: null, $macWifi ?: null,
                        $sace ?: null, $dataAdquisicio, $idUbicacio ?: null,
                        $idMaterial
                    ]);
                    setMissatge('Dispositiu actualitzat correctament.', 'success');
                } catch (PDOException $e) {
                    error_log($e->getMessage());
                    setMissatge('Error en actualitzar el dispositiu.', 'error');
                }
            }
        }
        header("Location: gestionar_dispositiu.php?id={$idMaterial}");
        exit;
    }

    // ── 2. Assignar a un alumne ──────────────────────────────────────────────
    if ($accio === 'assignar') {
        $idAlumne  = (int)($_POST['idAlumne'] ?? 0);
        $dataInici = $_POST['dataInici'] ?: date('Y-m-d');

        if ($idAlumne <= 0) {
            setMissatge('Has de seleccionar un alumne.', 'error');
        } elseif ($assignacioActiva) {
            setMissatge('El dispositiu ja té una assignació activa. Retorna\'l primer.', 'error');
        } else {
            try {
                $db->prepare(
                    "INSERT INTO Assignacions (idMaterial, idAlumne, dataInici) VALUES (?, ?, ?)"
                )->execute([$idMaterial, $idAlumne, $dataInici]);
                setMissatge('Dispositiu assignat correctament.', 'success');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                setMissatge('Error en assignar el dispositiu.', 'error');
            }
        }
        header("Location: gestionar_dispositiu.php?id={$idMaterial}");
        exit;
    }

    // ── 3. Retornar material (tancar assignació) ─────────────────────────────
    if ($accio === 'retornar') {
        $idAssignacio = (int)($_POST['idAssignacio'] ?? 0);
        if ($idAssignacio > 0) {
            try {
                $db->prepare(
                    "UPDATE Assignacions SET dataFinal = CURDATE() WHERE id = ? AND idMaterial = ?"
                )->execute([$idAssignacio, $idMaterial]);
                setMissatge('Material marcat com a retornat.', 'success');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                setMissatge('Error en processar el retorn.', 'error');
            }
        }
        header("Location: gestionar_dispositiu.php?id={$idMaterial}");
        exit;
    }

    // ── 4. Nova incidència ───────────────────────────────────────────────────
    if ($accio === 'nova_incidencia') {
        $informacio = trim($_POST['informacio'] ?? '');
        $idEstat    = (int)($_POST['idEstat'] ?? 0);

        // Alumne: preferència a l'assignació activa; si no, el seleccionat manualment
        if ($assignacioActiva) {
            $idAlumneInc = (int)$assignacioActiva['idAlumne'];
        } else {
            $idAlumneInc = ($_POST['idAlumne'] ?? '') !== '' ? (int)$_POST['idAlumne'] : null;
        }

        if (empty($informacio)) {
            setMissatge('La descripció de la incidència és obligatòria.', 'error');
        } elseif ($idEstat <= 0) {
            setMissatge('Has de seleccionar un estat per a la incidència.', 'error');
        } else {
            try {
                $db->prepare(
                    "INSERT INTO Incidencies (informacio, dataOberta, idAlumne, idDispositiu, idEstat)
                     VALUES (?, CURDATE(), ?, ?, ?)"
                )->execute([$informacio, $idAlumneInc, $idMaterial, $idEstat]);
                setMissatge('Incidència registrada correctament.', 'success');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                setMissatge('Error en registrar la incidència.', 'error');
            }
        }
        header("Location: gestionar_dispositiu.php?id={$idMaterial}");
        exit;
    }

    // ── 5. Tancar incidència ─────────────────────────────────────────────────
    if ($accio === 'tancar_incidencia') {
        $idInc = (int)($_POST['idIncidencia'] ?? 0);
        if ($idInc > 0) {
            try {
                $db->prepare(
                    "UPDATE Incidencies SET dataTancada = CURDATE() WHERE id = ? AND idDispositiu = ?"
                )->execute([$idInc, $idMaterial]);
                setMissatge('Incidència tancada correctament.', 'success');
            } catch (PDOException $e) {
                error_log($e->getMessage());
                setMissatge('Error en tancar la incidència.', 'error');
            }
        }
        header("Location: gestionar_dispositiu.php?id={$idMaterial}");
        exit;
    }
}

// ── Recarrega el dispositiu per si s'ha editat ──────────────────────────────
$stmtDev->execute([$idMaterial]);
$dispositiu = $stmtDev->fetch();

$titol = h($dispositiu['tipus']) . ' — ' . h($dispositiu['idInventari'] ?? 'sense inventari');
capçalera('Dispositiu: ' . $titol);
mostrarMissatge();
?>

<!-- Navegació enrere -->
<div style="margin-bottom:1.2rem;">
    <a href="dispositius_tipus.php" style="color:#1a4f8a;font-size:0.9rem;text-decoration:none;">
        &larr; Tornar a la llista de dispositius
    </a>
</div>

<!-- Capçalera del dispositiu -->
<div class="card" style="background:linear-gradient(135deg,#1a4f8a,#15407a);color:white;margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="font-size:1.4rem;margin-bottom:0.3rem;"><?= $titol ?></h2>
            <p style="opacity:0.8;font-size:0.88rem;">
                Núm. Sèrie: <?= h($dispositiu['numSerie'] ?? '—') ?>
                &nbsp;&mdash;&nbsp;
                Aula: <?= h($dispositiu['nomAula'] ?: '—') ?>
            </p>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <?php if (!empty($incOberta)): ?>
                <span style="background:#e74c3c;padding:4px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;">
                    Incidència oberta
                </span>
            <?php else: ?>
                <span style="background:#27ae60;padding:4px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;">
                    Sense incidències
                </span>
            <?php endif; ?>
            <?php if ($assignacioActiva): ?>
                <span style="background:#f0a500;padding:4px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;">
                    Assignat
                </span>
            <?php else: ?>
                <span style="background:rgba(255,255,255,0.25);padding:4px 14px;border-radius:20px;font-size:0.82rem;font-weight:600;">
                    Disponible
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

    <!-- COL ESQUERRA: Dades + Assignació -->
    <div>

        <!-- Formulari edició dades -->
        <div class="card">
            <h3 style="color:#1a4f8a;margin-bottom:1.2rem;font-size:1rem;border-bottom:2px solid #eef3ff;padding-bottom:0.6rem;">
                Dades del dispositiu
            </h3>
            <form method="POST">
                <input type="hidden" name="accio" value="editar_dispositiu">

                <div class="form-group">
                    <label>Tipus de material</label>
                    <select name="idTipus" required>
                        <?php foreach ($tipusList as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"
                                <?= (int)$dispositiu['idTipus'] === (int)$t['id'] ? 'selected' : '' ?>>
                                <?= h($t['tipus']) ?> <?= $t['model'] ? '(' . h($t['model']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Ubicació / Aula</label>
                    <select name="idUbicacio">
                        <option value="">Sense ubicació</option>
                        <?php foreach ($ubicacions as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"
                                <?= (int)$dispositiu['idUbicacio'] === (int)$u['id'] ? 'selected' : '' ?>>
                                <?= h($u['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;">
                    <div class="form-group">
                        <label>Núm. Inventari</label>
                        <input type="text" name="idInventari"
                               value="<?= h($dispositiu['idInventari'] ?? '') ?>"
                               required maxlength="10">
                    </div>
                    <div class="form-group">
                        <label>Etiqueta Dep. Inf.</label>
                        <input type="text" name="etiquetaDepInf"
                               value="<?= h($dispositiu['etiquetaDepInf'] ?? '') ?>"
                               maxlength="50">
                    </div>
                </div>

                <div class="form-group">
                    <label>Número de sèrie</label>
                    <input type="text" name="numSerie"
                           value="<?= h($dispositiu['numSerie'] ?? '') ?>"
                           maxlength="50">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;">
                    <div class="form-group">
                        <label>MAC Ethernet</label>
                        <input type="text" name="macEthernet"
                               value="<?= h($dispositiu['macEthernet'] ?? '') ?>"
                               maxlength="50" placeholder="AA:BB:CC:DD:EE:FF">
                    </div>
                    <div class="form-group">
                        <label>MAC WiFi</label>
                        <input type="text" name="macWifi"
                               value="<?= h($dispositiu['macWifi'] ?? '') ?>"
                               maxlength="50" placeholder="AA:BB:CC:DD:EE:FF">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.8rem;">
                    <div class="form-group">
                        <label>SACE</label>
                        <input type="text" name="SACE"
                               value="<?= h($dispositiu['SACE'] ?? '') ?>"
                               maxlength="50">
                    </div>
                    <div class="form-group">
                        <label>Data adquisició</label>
                        <input type="date" name="dataAdquisicio"
                               value="<?= h($dispositiu['dataAdquisicio'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Desar canvis
                </button>
            </form>
        </div>

        <!-- Assignació activa -->
        <div class="card">
            <h3 style="color:#1a4f8a;margin-bottom:1.2rem;font-size:1rem;border-bottom:2px solid #eef3ff;padding-bottom:0.6rem;">
                Assignació actual
            </h3>

            <?php if ($assignacioActiva): ?>
                <div style="background:#fff8e1;border-left:4px solid #f0a500;padding:1rem;border-radius:6px;margin-bottom:1rem;">
                    <p style="font-weight:700;color:#333;margin-bottom:0.4rem;">
                        <?= h(trim($assignacioActiva['nomAlumne'])) ?>
                    </p>
                    <p style="font-size:0.85rem;color:#666;">
                        Grup: <?= h($assignacioActiva['grupClasse'] ?? '—') ?><br>
                        Correu: <?= h($assignacioActiva['correu'] ?? '—') ?><br>
                        Des de: <?= h($assignacioActiva['dataInici']) ?>
                    </p>
                    <div style="margin-top:0.8rem;display:flex;gap:0.5rem;">
                        <a class="btn btn-sm btn-warning"
                           href="gestionar_alumne.php?id=<?= (int)$assignacioActiva['idAlumne'] ?>">
                            Veure alumne
                        </a>
                        <form method="POST" onsubmit="return confirm('Marcar com a retornat?');" style="display:inline;">
                            <input type="hidden" name="accio" value="retornar">
                            <input type="hidden" name="idAssignacio" value="<?= (int)$assignacioActiva['idAssignacio'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Retornar material</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <p style="color:#27ae60;font-size:0.9rem;margin-bottom:1rem;font-weight:600;">
                    Dispositiu disponible — sense assignar
                </p>
                <form method="POST">
                    <input type="hidden" name="accio" value="assignar">
                    <div class="form-group">
                        <label>Assignar a l'alumne</label>
                        <select name="idAlumne" required>
                            <option value="">Selecciona un alumne...</option>
                            <?php foreach ($alumnes as $a): ?>
                                <option value="<?= (int)$a['id'] ?>">
                                    <?= h(trim($a['nomComplet'])) ?>
                                    <?= $a['grupClasse'] ? '(' . h($a['grupClasse']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Data d'inici</label>
                        <input type="date" name="dataInici" value="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="btn btn-success" style="width:100%;">
                        Assignar dispositiu
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>

    <!-- COL DRETA: Incidències -->
    <div>

        <!-- Nova incidència -->
        <div class="card">
            <h3 style="color:#e74c3c;margin-bottom:1.2rem;font-size:1rem;border-bottom:2px solid #fdecea;padding-bottom:0.6rem;">
                Registrar incidència
            </h3>
            <form method="POST">
                <input type="hidden" name="accio" value="nova_incidencia">

                <!-- informacio (VARCHAR 5000) -->
                <div class="form-group">
                    <label>Descripció <span style="color:#e74c3c;">*</span></label>
                    <textarea name="informacio" rows="4" required maxlength="5000"
                        placeholder="Descriu la incidència detalladament..."></textarea>
                </div>

                <!-- idEstat (FK → Estats) -->
                <div class="form-group">
                    <label>Estat <span style="color:#e74c3c;">*</span></label>
                    <select name="idEstat" required>
                        <option value="">Selecciona un estat...</option>
                        <?php foreach ($estats as $e): ?>
                            <option value="<?= (int)$e['id'] ?>"><?= h($e['estat']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- idAlumne (FK → Alumnes, nullable) -->
                <?php if ($assignacioActiva): ?>
                    <p style="font-size:0.82rem;color:#888;margin-bottom:0.8rem;">
                        S'associarà a: <strong><?= h(trim($assignacioActiva['nomAlumne'])) ?></strong>
                    </p>
                <?php else: ?>
                    <div class="form-group">
                        <label>Alumne relacionat <span style="font-size:0.8rem;color:#999;">(opcional)</span></label>
                        <select name="idAlumne">
                            <option value="">Cap alumne associat</option>
                            <?php foreach ($alumnes as $a): ?>
                                <option value="<?= (int)$a['id'] ?>">
                                    <?= h(trim($a['nomComplet'])) ?>
                                    <?= $a['grupClasse'] ? '(' . h($a['grupClasse']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-danger" style="width:100%;">
                    Registrar incidència
                </button>
            </form>
        </div>

        <!-- Llista d'incidències -->
        <div class="card">
            <h3 style="color:#1a4f8a;margin-bottom:1.2rem;font-size:1rem;border-bottom:2px solid #eef3ff;padding-bottom:0.6rem;">
                Historial d'incidències
                <span style="font-size:0.8rem;font-weight:400;color:#888;margin-left:0.5rem;">
                    (<?= count($incidencies) ?> total)
                </span>
            </h3>

            <?php if (empty($incidencies)): ?>
                <p style="color:#999;text-align:center;padding:1.5rem 0;">
                    Aquest dispositiu no té incidències registrades.
                </p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:0.8rem;max-height:500px;overflow-y:auto;">
                <?php foreach ($incidencies as $inc):
                    $oberta = $inc['dataTancada'] === null;
                ?>
                    <div style="
                        border:1px solid <?= $oberta ? '#f5c6cb' : '#c3e6cb' ?>;
                        background:<?= $oberta ? '#fff5f5' : '#f8fff8' ?>;
                        border-radius:8px;
                        padding:0.9rem;
                    ">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;margin-bottom:0.5rem;">
                            <span style="
                                font-size:0.78rem;font-weight:700;padding:2px 10px;border-radius:10px;
                                background:<?= $oberta ? '#f8d7da' : '#d4edda' ?>;
                                color:<?= $oberta ? '#721c24' : '#155724' ?>;
                            ">
                                <?= $oberta ? 'Oberta' : 'Tancada' ?>
                            </span>
                            <span style="font-size:0.78rem;color:#888;"><?= h($inc['estat'] ?? '—') ?></span>
                        </div>

                        <p style="font-size:0.85rem;color:#333;margin-bottom:0.5rem;line-height:1.5;">
                            <?= h(mb_substr($inc['informacio'], 0, 180)) ?>
                            <?= mb_strlen($inc['informacio']) > 180 ? '...' : '' ?>
                        </p>

                        <p style="font-size:0.78rem;color:#888;margin-bottom:<?= $oberta ? '0.6rem' : '0' ?>;">
                            Alumne: <?= h($inc['nomAlumne']) ?>
                            &nbsp;&bull;&nbsp;
                            Oberta: <?= h($inc['dataOberta']) ?>
                            <?php if (!$oberta): ?>
                                &nbsp;&bull;&nbsp; Tancada: <?= h($inc['dataTancada']) ?>
                            <?php endif; ?>
                        </p>

                        <?php if ($oberta): ?>
                            <form method="POST" onsubmit="return confirm('Tancar aquesta incidència?');">
                                <input type="hidden" name="accio" value="tancar_incidencia">
                                <input type="hidden" name="idIncidencia" value="<?= (int)$inc['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">
                                    Tancar incidència
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php peu(); ?>