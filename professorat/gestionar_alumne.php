<?php
/**
 * gestionar_alumne.php
 * Permet al professor gestionar les dades d'un alumne existent
 * i modificar l'estat dels seus dispositius assignats.
 *
 * @package GestioMaterial
 * @author  El teu nom
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/db.php';

requerirProfessor();

$db       = getDB();
$idAlumne = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error    = '';

// Comprova que l'alumne existeix
if ($idAlumne <= 0) {
    header('Location: ' . BASE_URL . 'professorat/alumne_dispositius.php');
    exit;
}

$stmtAlumne = $db->prepare(
    "SELECT id, nom, cognom1, COALESCE(cognom2,'') AS cognom2,
            correu, COALESCE(grupClasse,'') AS grupClasse
     FROM Alumnes WHERE id = ? LIMIT 1"
);
$stmtAlumne->execute([$idAlumne]);
$alumne = $stmtAlumne->fetch();

if (!$alumne) {
    setMissatge('Alumne no trobat.', 'error');
    header('Location: ' . BASE_URL . 'professorat/alumne_dispositius.php');
    exit;
}

// Processa el formulari de modificació de dades (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accio'])) {

    // --- Modificar dades de l'alumne ---
    if ($_POST['accio'] === 'modificar_alumne') {
        $nom        = trim($_POST['nom']        ?? '');
        $cognom1    = trim($_POST['cognom1']    ?? '');
        $cognom2    = trim($_POST['cognom2']    ?? '');
        $correu     = trim($_POST['correu']     ?? '');
        $grupClasse = trim($_POST['grupClasse'] ?? '');

        if (empty($nom) || empty($cognom1) || empty($correu)) {
            $error = 'Els camps nom, cognom i correu són obligatoris.';
        } elseif (!filter_var($correu, FILTER_VALIDATE_EMAIL)) {
            $error = 'El format del correu electrònic no és vàlid.';
        } else {
            try {
                // Actualitza la taula Alumnes
                $stmtUpd = $db->prepare(
                    "UPDATE Alumnes SET nom=?, cognom1=?, cognom2=?, correu=?, grupClasse=?
                     WHERE id=?"
                );
                $stmtUpd->execute([$nom, $cognom1, $cognom2, $correu, $grupClasse, $idAlumne]);

                // Actualitza també la taula Usuaris si existeix el correu
                $stmtUpdUsr = $db->prepare(
                    "UPDATE Usuaris SET nom=?, cognom1=?, cognom2=?, correu=?, grupClasse=?
                     WHERE correu=? AND rol='alumne'"
                );
                $stmtUpdUsr->execute([$nom, $cognom1, $cognom2, $correu, $grupClasse, $alumne['correu']]);

                setMissatge('Dades de l\'alumne actualitzades correctament.', 'success');
                header('Location: gestionar_alumne.php?id=' . $idAlumne);
                exit;
            } catch (PDOException $e) {
                $error = 'Error en actualitzar les dades: ' . $e->getMessage();
            }
        }
    }

    // --- Modificar estat d'un dispositiu (tancar/obrir incidència) ---
    if ($_POST['accio'] === 'tancar_incidencia') {
        $idIncidencia = (int)($_POST['idIncidencia'] ?? 0);
        if ($idIncidencia > 0) {
            try {
                $stmt = $db->prepare(
                    "UPDATE Incidencies SET dataTancada = CURDATE() WHERE id = ? AND dataTancada IS NULL"
                );
                $stmt->execute([$idIncidencia]);
                setMissatge('Incidència tancada correctament.', 'success');
            } catch (PDOException $e) {
                setMissatge('Error en tancar la incidència.', 'error');
            }
        }
        header('Location: gestionar_alumne.php?id=' . $idAlumne);
        exit;
    }
}

// Recarrega les dades actualitzades de l'alumne
$stmtAlumne->execute([$idAlumne]);
$alumne = $stmtAlumne->fetch();

// Obté els dispositius de l'alumne amb l'estat de les incidències
$stmtDev = $db->prepare(
    "SELECT
        m.id AS idMaterial,
        tm.tipus,
        COALESCE(tm.model,'') AS model,
        COALESCE(m.idInventari,'') AS idInventari,
        COALESCE(m.numSerie,'') AS numSerie,
        COALESCE(m.etiquetaDepInf,'') AS etiquetaDepInf,
        COALESCE(u.nom,'') AS aula,
        ass.id AS idAssignacio,
        ass.dataInici,
        ass.dataFinal,
        inc.id AS idIncidencia,
        COALESCE(inc.informacio,'') AS descIncidencia,
        inc.dataOberta,
        COALESCE(e.estat,'') AS estatIncidencia
     FROM Assignacions ass
     JOIN Material m ON m.id = ass.idMaterial
     JOIN TipusMaterial tm ON m.idTipus = tm.id
     LEFT JOIN Ubicacions u ON m.idUbicacio = u.id
     LEFT JOIN Incidencies inc ON inc.idDispositiu = m.id AND inc.dataTancada IS NULL
     LEFT JOIN Estats e ON e.id = inc.idEstat
     WHERE ass.idAlumne = ?
     ORDER BY ass.dataFinal IS NULL DESC, ass.dataInici DESC"
);
$stmtDev->execute([$idAlumne]);
$dispositius = $stmtDev->fetchAll();

$nomComplet = trim($alumne['nom'] . ' ' . $alumne['cognom1'] . ' ' . $alumne['cognom2']);

capçalera('Gestionar Alumne');
mostrarMissatge();
?>

<!-- Capçalera de l'alumne -->
<div class="card" style="background:linear-gradient(135deg,#1a4f8a,#15407a); color:white; margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
            <h2 style="font-size:1.3rem; margin-bottom:0.3rem;"><?= h($nomComplet) ?></h2>
            <p style="opacity:0.85; font-size:0.88rem;">
                <?php if ($alumne['grupClasse']): ?>
                    Grup: <strong><?= h($alumne['grupClasse']) ?></strong> &mdash;
                <?php endif; ?>
                <?= h($alumne['correu']) ?>
            </p>
        </div>
        <a href="alumne_dispositius.php" class="btn btn-primary" style="background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.4);">
            ← Tornar
        </a>
    </div>
</div>

<!-- FORMULARI: Modificar dades de l'alumne -->
<div class="card">
    <h3 style="color:#1a4f8a; margin-bottom:1.2rem;">Modificar dades de l'alumne</h3>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="accio" value="modificar_alumne">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <div class="form-group">
                <label for="nom">Nom <span style="color:#e74c3c;">*</span></label>
                <input type="text" id="nom" name="nom"
                       value="<?= h($alumne['nom']) ?>" required maxlength="50">
            </div>
            <div class="form-group">
                <label for="cognom1">Primer cognom <span style="color:#e74c3c;">*</span></label>
                <input type="text" id="cognom1" name="cognom1"
                       value="<?= h($alumne['cognom1']) ?>" required maxlength="50">
            </div>
            <div class="form-group">
                <label for="cognom2">Segon cognom</label>
                <input type="text" id="cognom2" name="cognom2"
                       value="<?= h($alumne['cognom2']) ?>" maxlength="50">
            </div>
            <div class="form-group">
                <label for="grupClasse">Grup / Classe</label>
                <input type="text" id="grupClasse" name="grupClasse"
                       value="<?= h($alumne['grupClasse']) ?>" maxlength="10">
            </div>
            <div class="form-group">
                <label for="correu">Correu electrònic <span style="color:#e74c3c;">*</span></label>
                <input type="email" id="correu" name="correu"
                       value="<?= h($alumne['correu']) ?>" required maxlength="100">
            </div>
        </div>

        <button type="submit" class="btn btn-success">Guardar canvis</button>
    </form>
</div>

<!-- DISPOSITIUS: Estat i incidències -->
<div class="card">
    <h3 style="color:#1a4f8a; margin-bottom:1.2rem;">
        Dispositius assignats
        <span style="font-size:0.85rem; color:#666; font-weight:400;">
            (<?= count($dispositius) ?> dispositiu<?= count($dispositius) !== 1 ? 's' : '' ?>)
        </span>
    </h3>

    <?php if (empty($dispositius)): ?>
        <div style="text-align:center; padding:2rem; color:#999;">
            Aquest alumne no té cap dispositiu assignat.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tipus</th>
                    <th>Model</th>
                    <th>Inventari</th>
                    <th>Núm. Sèrie</th>
                    <th>Aula</th>
                    <th>Assignat des de</th>
                    <th>Estat</th>
                    <th>Acció</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dispositius as $d):
                $teInc = !empty($d['idIncidencia']);
                $actiu = ($d['dataFinal'] === null || $d['dataFinal'] >= date('Y-m-d'));
            ?>
                <tr>
                    <td><?= h($d['tipus']) ?></td>
                    <td><?= h($d['model']) ?: '—' ?></td>
                    <td><?= h($d['idInventari']) ?: '—' ?></td>
                    <td><?= h($d['numSerie']) ?: '—' ?></td>
                    <td><?= h($d['aula']) ?: '—' ?></td>
                    <td><?= h($d['dataInici'] ?? '—') ?></td>
                    <td>
                        <?php if ($teInc): ?>
                            <span class="badge-estat badge-inc">Incidència</span>
                            <div style="font-size:0.78rem; color:#888; margin-top:0.2rem;">
                                <?= h(mb_substr($d['descIncidencia'], 0, 50)) ?>...
                            </div>
                        <?php elseif ($actiu): ?>
                            <span class="badge-estat badge-ok">Actiu</span>
                        <?php else: ?>
                            <span class="badge-estat" style="background:#eee; color:#666;">Retornat</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($teInc): ?>
                            <form method="POST" onsubmit="return confirm('Tancar la incidència d\'aquest dispositiu?');">
                                <input type="hidden" name="accio" value="tancar_incidencia">
                                <input type="hidden" name="idIncidencia" value="<?= (int)$d['idIncidencia'] ?>">
                                <button type="submit" class="btn btn-sm btn-success">Tancar incidència</button>
                            </form>
                        <?php else: ?>
                            <span style="color:#999; font-size:0.82rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php peu(); ?>
