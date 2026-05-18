<?php
/**
 * professorat/index.php
 * Panell principal del professorat.
 * Mostra un resum general de l'estat del material.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/../includes/layout.php';

requerirProfessor();

$db = getDB();

// Recompte total de materials per tipus
$stmtTipus = $db->query(
    "SELECT tm.tipus, COUNT(m.id) AS total
     FROM TipusMaterial tm
     LEFT JOIN Material m ON m.idTipus = tm.id
     GROUP BY tm.id, tm.tipus
     ORDER BY tm.tipus"
);
$recomptesTipus = $stmtTipus->fetchAll();

// Total d'incidències obertes
$stmtInc = $db->query(
    "SELECT COUNT(*) AS total FROM Incidencies WHERE dataTancada IS NULL"
);
$totalIncidencies = $stmtInc->fetchColumn();

// Total d'alumnes
$stmtAlumnes = $db->query("SELECT COUNT(*) AS total FROM Alumnes");
$totalAlumnes = $stmtAlumnes->fetchColumn();

// Total d'assignacions actives
$stmtAssig = $db->query(
    "SELECT COUNT(*) AS total FROM Assignacions WHERE dataFinal IS NULL OR dataFinal >= CURDATE()"
);
$totalAssig = $stmtAssig->fetchColumn();

// Dispositius per aula
$stmtAula = $db->query(
    "SELECT
        u.nom AS aula,
        tm.tipus,
        tm.model,
        COUNT(m.id) AS total
     FROM Ubicacions u
     LEFT JOIN Material m ON m.idUbicacio = u.id
     LEFT JOIN TipusMaterial tm ON m.idTipus = tm.id
     GROUP BY u.id, u.nom, tm.id, tm.tipus, tm.model
     ORDER BY u.nom, tm.tipus"
);
$perAula = [];
foreach ($stmtAula->fetchAll() as $fila) {
    $perAula[$fila['aula']][] = $fila;
}

capçalera('Panell de Control');
mostrarMissatge();
?>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:1rem; margin-bottom:2rem;">
    <div class="card" style="text-align:center;">
        <div style="font-size:2rem; font-weight:700; color:#1a4f8a;"><?= (int)$totalAlumnes ?></div>
        <div style="color:#666;">Alumnes registrats</div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:2rem; font-weight:700; color:#1a4f8a;"><?= (int)$totalAssig ?></div>
        <div style="color:#666;">Assignacions actives</div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:2rem; font-weight:700; color:#e74c3c;"><?= (int)$totalIncidencies ?></div>
        <div style="color:#666;">Incidències obertes</div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem; color:#1a4f8a;">Material per tipus</h3>
    <table>
        <thead>
            <tr>
                <th>Tipus de material</th>
                <th>Total unitats</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recomptesTipus as $fila): ?>
            <tr>
                <td><?= h($fila['tipus']) ?></td>
                <td><?= (int)$fila['total'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recomptesTipus)): ?>
            <tr><td colspan="2" style="text-align:center; color:#999;">No hi ha material registrat.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

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

<?php peu(); ?>