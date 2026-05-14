<?php
/**
 * professorat/incidencies.php
 * Llista tots els dispositius que estan en incidència (obertes).
 * Permet tancar incidències.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/../includes/layout.php';

requerirProfessor();

$db = getDB();

// Acció: canviar estat d'incidència
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inc_id'], $_POST['nou_estat'])) {
    $incId   = (int)$_POST['inc_id'];
    $nouEstat = $_POST['nou_estat'];

    try {
        if ($nouEstat === 'tancar') {
            $db->prepare(
                "UPDATE Incidencies SET dataTancada = CURDATE() WHERE id = ? AND dataTancada IS NULL"
            )->execute([$incId]);
            setMissatge('Incidència tancada correctament.', 'success');
        } else {
            $idEstatMap = ['1' => 'Oberta', '2' => 'En procés', '3' => 'Pendent de peça'];
            $nouEstatInt = (string)(int)$nouEstat;
            if (isset($idEstatMap[$nouEstatInt])) {
                $db->prepare(
                    "UPDATE Incidencies SET idEstat = ? WHERE id = ? AND dataTancada IS NULL"
                )->execute([$nouEstatInt, $incId]);
                setMissatge("Estat actualitzat a «{$idEstatMap[$nouEstatInt]}».", 'success');
            } else {
                setMissatge('Estat no reconegut.', 'error');
            }
        }
    } catch (PDOException $e) {
        error_log('Error actualitzant incidència: ' . $e->getMessage());
        setMissatge('Error en actualitzar la incidència.', 'error');
    }
    header('Location: incidencies.php');
    exit;
}

/**
 * Obté totes les incidències obertes amb informació del material i l'alumne.
 *
 * @param PDO $db Connexió a la base de dades.
 * @return array Llista d'incidències obertes.
 */
function obtenirIncidenciesObertes(PDO $db): array {
    $stmt = $db->query(
        "SELECT
            inc.id,
            inc.informacio,
            inc.dataOberta,
            inc.idDispositiu,
            inc.idEstat,
            e.estat,
            m.idInventari,
            m.numSerie,
            tm.tipus,
            CONCAT(al.nom, ' ', al.cognom1) AS alumne,
            al.id AS idAlumne,
            al.grupClasse
         FROM Incidencies inc
         JOIN Material m ON m.id = inc.idDispositiu
         JOIN TipusMaterial tm ON m.idTipus = tm.id
         LEFT JOIN Alumnes al ON inc.idAlumne = al.id
         LEFT JOIN Estats e ON e.id = inc.idEstat
         WHERE inc.dataTancada IS NULL
         ORDER BY inc.dataOberta DESC"
    );
    return $stmt->fetchAll();
}

$incidencies = obtenirIncidenciesObertes($db);

capçalera('Incidències Obertes');
mostrarMissatge();
?>

<div class="card">
    <p style="color:#666; font-size:0.9rem;">
        Total d'incidències obertes: <strong><?= count($incidencies) ?></strong>
    </p>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tipus / Dispositiu</th>
                <th>Inventari</th>
                <th>Alumne</th>
                <th>Grup</th>
                <th>Estat</th>
                <th>Data Obertura</th>
                <th>Descripció</th>
                <th>Acció</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($incidencies as $inc): ?>
            <tr>
                <td><?= (int)$inc['id'] ?></td>
                <td><?= h($inc['tipus']) ?></td>
                <td><?= h($inc['idInventari'] ?? '—') ?></td>
                <td>
                    <?php if ($inc['idAlumne']): ?>
                        <a href="gestionar_alumne.php?id=<?= (int)$inc['idAlumne'] ?>">
                            <?= h($inc['alumne']) ?>
                        </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= h($inc['grupClasse'] ?? '—') ?></td>
                <td><span class="badge-estat badge-inc"><?= h($inc['estat'] ?? 'Sense estat') ?></span></td>
                <td><?= h($inc['dataOberta']) ?></td>
                <td style="max-width:250px; font-size:0.83rem;"><?= h(mb_substr($inc['informacio'], 0, 100)) ?>...</td>
                <td>
                    <form method="POST" style="display:flex;gap:0.4rem;align-items:center;flex-wrap:wrap;">
                        <input type="hidden" name="inc_id" value="<?= (int)$inc['id'] ?>">
                        <select name="nou_estat"
                            style="font-size:0.8rem;padding:3px 6px;border:1px solid #ccc;border-radius:5px;cursor:pointer;">
                            <option value="1" <?= ($inc['idEstat'] ?? 0) == 1 ? 'selected' : '' ?>>Oberta</option>
                            <option value="2" <?= ($inc['idEstat'] ?? 0) == 2 ? 'selected' : '' ?>>En procés</option>
                            <option value="3" <?= ($inc['idEstat'] ?? 0) == 3 ? 'selected' : '' ?>>Pendent de peça</option>
                            <option value="tancar" style="color:#27ae60;font-weight:600;">✓ Tancar</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary"
                            style="padding:3px 10px;"
                            onclick="return this.form.nou_estat.value === 'tancar' ? confirm('Tancar aquesta incidència?') : true;">
                            Aplicar
                        </button>
                        <a href="gestionar_dispositiu.php?id=<?= (int)$inc['idDispositiu'] ?>"
                           class="btn btn-sm" style="padding:3px 8px;background:#6c757d;color:white;">⚙</a>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($incidencies)): ?>
            <tr><td colspan="9" style="text-align:center; color:#27ae60; font-weight:600;">✅ No hi ha incidències obertes.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php peu(); ?>