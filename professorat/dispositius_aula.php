<?php
/**
 * professorat/dispositius_aula.php
 * Llista els dispositius per tipus que hi ha per cada aula,
 * amb el recompte total per tipus.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/../includes/layout.php';

requerirProfessor();

$db = getDB();

/**
 * Obté els dispositius agrupats per ubicació i tipus de material.
 *
 * @param PDO $db Connexió a la base de dades.
 * @return array Llista de dispositius agrupats.
 */
function obtenirDispositiusPerAula(PDO $db): array {
    $stmt = $db->query(
        "SELECT
            u.nom AS aula,
            tm.tipus,
            COUNT(m.id) AS total,
            tm.model
         FROM Ubicacions u
         LEFT JOIN Material m ON m.idUbicacio = u.id
         LEFT JOIN TipusMaterial tm ON m.idTipus = tm.id
         GROUP BY u.id, u.nom, tm.id, tm.tipus, tm.model
         ORDER BY u.nom, tm.tipus"
    );
    return $stmt->fetchAll();
}

$dispositius  = obtenirDispositiusPerAula($db);

// Agrupa per aula per facilitar la visualització
$perAula = [];
foreach ($dispositius as $fila) {
    $perAula[$fila['aula']][] = $fila;
}

capçalera('Dispositius per Aula');
?>

<?php if (empty($perAula)): ?>
    <div class="alert alert-error">No s'han trobat dispositius registrats.</div>
<?php else: ?>
    <?php foreach ($perAula as $nomAula => $materials): ?>
    <div class="card">
        <h3 style="color:#1a4f8a; margin-bottom:1rem;">📍 Aula: <?= h($nomAula) ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Tipus</th>
                    <th>Model</th>
                    <th>Total unitats</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $totalAula = 0;
            foreach ($materials as $m):
                if ($m['tipus'] === null) continue;
                $totalAula += (int)$m['total'];
            ?>
                <tr>
                    <td><?= h($m['tipus']) ?></td>
                    <td><?= h($m['model'] ?? '—') ?></td>
                    <td><?= (int)$m['total'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700; background:#eef3ff;">
                    <td colspan="2">Total aula</td>
                    <td><?= $totalAula ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php peu(); ?>