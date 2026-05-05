<?php
/**
 * professorat/dispositius_tipus.php
 * Llista els dispositius per tipus i a qui estan assignats.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/../includes/layout.php';

requerirProfessor();

$db = getDB();

// Filtre opcional per tipus
$tipusFiltrat = isset($_GET['tipus']) ? (int)$_GET['tipus'] : 0;

// Carrega tots els tipus de material per al desplegable
$stmtTipus = $db->query("SELECT id, tipus FROM TipusMaterial ORDER BY tipus");
$tipusList = $stmtTipus->fetchAll();

/**
 * Obté els dispositius amb les seves assignacions actuals.
 *
 * @param PDO $db          Connexió a la base de dades.
 * @param int $idTipus     ID del tipus de material (0 = tots).
 * @return array Llista de dispositius amb alumne assignat (o null).
 */
function obtenirDispositiusAssignats(PDO $db, int $idTipus): array {
    $where = $idTipus > 0 ? 'WHERE m.idTipus = :idTipus' : '';
    $sql = "
        SELECT
            tm.tipus,
            tm.model,
            m.idInventari,
            m.numSerie,
            m.etiquetaDepInf,
            u.nom AS aula,
            CONCAT(al.nom, ' ', al.cognom1, ' ', COALESCE(al.cognom2,'')) AS alumne,
            al.id AS idAlumne,
            al.grupClasse,
            ass.dataInici,
            ass.dataFinal
        FROM Material m
        JOIN TipusMaterial tm ON m.idTipus = tm.id
        LEFT JOIN Ubicacions u ON m.idUbicacio = u.id
        LEFT JOIN Assignacions ass ON ass.idMaterial = m.id
            AND (ass.dataFinal IS NULL OR ass.dataFinal >= CURDATE())
        LEFT JOIN Alumnes al ON ass.idAlumne = al.id
        $where
        ORDER BY tm.tipus, m.idInventari
    ";
    $stmt = $idTipus > 0 ? $db->prepare($sql) : $db->prepare($sql);
    if ($idTipus > 0) {
        $stmt->bindValue(':idTipus', $idTipus, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

$dispositius = obtenirDispositiusAssignats($db, $tipusFiltrat);

capçalera('Dispositius per Tipus i Assignació');
?>

<div class="card">
    <form method="GET" style="display:flex; gap:1rem; align-items:flex-end;">
        <div class="form-group" style="margin:0; flex:1;">
            <label for="tipus">Filtrar per tipus:</label>
            <select id="tipus" name="tipus">
                <option value="0">Tots els tipus</option>
                <?php foreach ($tipusList as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= $tipusFiltrat === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= h($t['tipus']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Tipus</th>
                <th>Model</th>
                <th>Inventari</th>
                <th>Núm. Sèrie</th>
                <th>Aula</th>
                <th>Alumne assignat</th>
                <th>Grup</th>
                <th>Data inici</th>
                <th>Acció</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dispositius as $d): ?>
            <tr>
                <td><?= h($d['tipus']) ?></td>
                <td><?= h($d['model'] ?? '—') ?></td>
                <td><?= h($d['idInventari'] ?? '—') ?></td>
                <td><?= h($d['numSerie'] ?? '—') ?></td>
                <td><?= h($d['aula'] ?? '—') ?></td>
                <td>
                    <?php if ($d['alumne'] && $d['idAlumne']): ?>
                        <a href="gestionar_alumne.php?id=<?= (int)$d['idAlumne'] ?>">
                            <?= h(trim($d['alumne'])) ?>
                        </a>
                    <?php else: ?>
                        <span style="color:#999;">Sense assignar</span>
                    <?php endif; ?>
                </td>
                <td><?= h($d['grupClasse'] ?? '—') ?></td>
                <td><?= $d['dataInici'] ? h($d['dataInici']) : '—' ?></td>
                <td>
                    <?php if ($d['idAlumne']): ?>
                        <a class="btn btn-sm btn-primary" href="gestionar_alumne.php?id=<?= (int)$d['idAlumne'] ?>">Gestionar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($dispositius)): ?>
            <tr><td colspan="9" style="text-align:center; color:#999;">No s'han trobat dispositius.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php peu(); ?>