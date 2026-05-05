<?php
/**
 * professorat/alumne_dispositius.php
 * Mostra els dispositius assignats a un alumne en concret.
 * Permet cercar per nom, cognom o grup.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/../includes/layout.php';

requerirProfessor();

$db = getDB();

$cerca    = trim($_GET['cerca'] ?? '');
$alumnes  = [];
$idAlumne = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dispositius = [];
$alumneSelec = null;

// Cerca alumnes per nom/cognom/grup
if ($cerca !== '') {
    $stmt = $db->prepare(
        "SELECT id, nom, cognom1, cognom2, correu, grupClasse
         FROM Alumnes
         WHERE nom LIKE :cerca OR cognom1 LIKE :cerca OR cognom2 LIKE :cerca OR grupClasse LIKE :cerca
         ORDER BY cognom1, nom
         LIMIT 50"
    );
    $stmt->execute([':cerca' => '%' . $cerca . '%']);
    $alumnes = $stmt->fetchAll();
}

// Si s'ha seleccionat un alumne, carrega els seus dispositius
if ($idAlumne > 0) {
    $stmtAlumne = $db->prepare(
        "SELECT id, nom, cognom1, cognom2, correu, grupClasse FROM Alumnes WHERE id = ?"
    );
    $stmtAlumne->execute([$idAlumne]);
    $alumneSelec = $stmtAlumne->fetch();

    if ($alumneSelec) {
        $stmtDev = $db->prepare(
            "SELECT
                tm.tipus,
                m.idInventari,
                m.numSerie,
                m.etiquetaDepInf,
                u.nom AS aula,
                ass.dataInici,
                ass.dataFinal,
                e.estat AS estatAssig
             FROM Assignacions ass
             JOIN Material m ON m.id = ass.idMaterial
             JOIN TipusMaterial tm ON m.idTipus = tm.id
             LEFT JOIN Ubicacions u ON m.idUbicacio = u.id
             LEFT JOIN Estats e ON e.id = (
                 SELECT inc.idEstat FROM Incidencies inc
                 WHERE inc.idDispositiu = m.id AND inc.dataTancada IS NULL
                 LIMIT 1
             )
             WHERE ass.idAlumne = ?
             ORDER BY ass.dataInici DESC"
        );
        $stmtDev->execute([$idAlumne]);
        $dispositius = $stmtDev->fetchAll();
    }
}

capçalera('Dispositius d\'un Alumne');
?>

<div class="card">
    <form method="GET" style="display:flex; gap:1rem; align-items:flex-end;">
        <div class="form-group" style="margin:0; flex:1;">
            <label for="cerca">Cercar alumne (nom, cognom o grup):</label>
            <input type="text" id="cerca" name="cerca" value="<?= h($cerca) ?>" placeholder="Ex: Garcia, DAW1...">
        </div>
        <button type="submit" class="btn btn-primary">Cercar</button>
    </form>
</div>

<?php if ($cerca !== '' && !empty($alumnes)): ?>
<div class="card">
    <h3 style="color:#1a4f8a; margin-bottom:1rem;">Resultats de la cerca</h3>
    <table>
        <thead>
            <tr><th>Nom</th><th>Grup</th><th>Correu</th><th>Acció</th></tr>
        </thead>
        <tbody>
        <?php foreach ($alumnes as $a): ?>
            <tr>
                <td><?= h($a['nom'] . ' ' . $a['cognom1'] . ' ' . ($a['cognom2'] ?? '')) ?></td>
                <td><?= h($a['grupClasse']) ?></td>
                <td><?= h($a['correu']) ?></td>
                <td>
                    <a class="btn btn-sm btn-primary"
                       href="?id=<?= (int)$a['id'] ?>&cerca=<?= urlencode($cerca) ?>">
                        Veure dispositius
                    </a>
                    <a class="btn btn-sm btn-warning" href="gestionar_alumne.php?id=<?= (int)$a['id'] ?>">Gestionar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($cerca !== '' && empty($alumnes)): ?>
    <div class="alert alert-error">No s'ha trobat cap alumne amb la cerca "<?= h($cerca) ?>".</div>
<?php endif; ?>

<?php if ($alumneSelec): ?>
<div class="card">
    <h3 style="color:#1a4f8a; margin-bottom:0.3rem;">
        Dispositius de: <?= h($alumneSelec['nom'] . ' ' . $alumneSelec['cognom1'] . ' ' . ($alumneSelec['cognom2'] ?? '')) ?>
    </h3>
    <p style="color:#666; font-size:0.88rem; margin-bottom:1rem;">
        Grup: <?= h($alumneSelec['grupClasse']) ?> &mdash; <?= h($alumneSelec['correu']) ?>
    </p>
    <table>
        <thead>
            <tr>
                <th>Tipus</th>
                <th>Inventari</th>
                <th>Núm. Sèrie</th>
                <th>Etiqueta</th>
                <th>Aula</th>
                <th>Data Inici</th>
                <th>Data Fi</th>
                <th>Estat</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dispositius as $d): ?>
            <tr>
                <td><?= h($d['tipus']) ?></td>
                <td><?= h($d['idInventari'] ?? '—') ?></td>
                <td><?= h($d['numSerie'] ?? '—') ?></td>
                <td><?= h($d['etiquetaDepInf'] ?? '—') ?></td>
                <td><?= h($d['aula'] ?? '—') ?></td>
                <td><?= h($d['dataInici'] ?? '—') ?></td>
                <td><?= $d['dataFinal'] ? h($d['dataFinal']) : '<span style="color:green">Activa</span>' ?></td>
                <td>
                    <?php if ($d['estatAssig']): ?>
                        <span class="badge-estat badge-inc"><?= h($d['estatAssig']) ?></span>
                    <?php else: ?>
                        <span class="badge-estat badge-ok">OK</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($dispositius)): ?>
            <tr><td colspan="8" style="text-align:center; color:#999;">Aquest alumne no té dispositius assignats.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php peu(); ?>