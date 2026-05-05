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

// Acció: tancar incidència
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tancar_id'])) {
    $tancarId = (int)$_POST['tancar_id'];
    try {
        $stmt = $db->prepare(
            "UPDATE Incidencies SET dataTancada = CURDATE() WHERE id = ? AND dataTancada IS NULL"
        );
        $stmt->execute([$tancarId]);
        setMissatge('Incidència tancada correctament.', 'success');
    } catch (PDOException $e) {
        error_log('Error tancant incidència: ' . $e->getMessage());
        setMissatge('Error en tancar la incidència.', 'error');
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
                    <form method="POST" onsubmit="return confirm('Tancar aquesta incidència?');">
                        <input type="hidden" name="tancar_id" value="<?= (int)$inc['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success">Tancar</button>
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