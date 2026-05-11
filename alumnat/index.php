<?php
/**
 * alumnat/index.php
 * Panell principal de l'alumnat.
 * Mostra l'estat dels dispositius assignats a l'alumne autenticat.
 *
 * @package GestioMaterial
 */

require_once __DIR__ . '/../includes/layout.php';

requerirAutenticacio();

// Si és professor, redirigeix al seu panell
if (esProfessor()) {
    header('Location: ' . BASE_URL . 'professorat/index.php');
    exit;
}

$db       = getDB();
$idAlumne = (int)$_SESSION['usuari_id'];

/**
 * Obté les dades personals de l'alumne autenticat.
 *
 * @param PDO $db       Connexió a la base de dades.
 * @param int $idAlumne ID de l'alumne.
 * @return array|false Dades de l'alumne o false si no existeix.
 */
function obtenirDadesAlumne(PDO $db, int $idAlumne): array|false {
    $stmt = $db->prepare(
        "SELECT id, nom, cognom1, cognom2, correu, grupClasse
         FROM Alumnes
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->execute([$idAlumne]);
    return $stmt->fetch();
}

/**
 * Obté tots els dispositius assignats a l'alumne,
 * incloent les incidències obertes de cada dispositiu.
 *
 * @param PDO $db       Connexió a la base de dades.
 * @param int $idAlumne ID de l'alumne.
 * @return array Llista de dispositius amb l'estat actual.
 */
function obtenirDispositiusAlumne(PDO $db, int $idAlumne): array {
    $stmt = $db->prepare(
        "SELECT
            m.id            AS idMaterial,
            tm.tipus,
            tm.model,
            m.idInventari,
            m.numSerie,
            m.etiquetaDepInf,
            m.macEthernet,
            m.macWifi,
            u.nom           AS aula,
            ass.id          AS idAssignacio,
            ass.dataInici,
            ass.dataFinal,
            -- Incidència oberta més recent del dispositiu
            inc.id          AS idIncidencia,
            inc.informacio  AS descIncidencia,
            inc.dataOberta  AS dataIncidencia,
            e.estat         AS estatIncidencia
         FROM Assignacions ass
         JOIN Material m ON m.id = ass.idMaterial
         JOIN TipusMaterial tm ON m.idTipus = tm.id
         LEFT JOIN Ubicacions u ON m.idUbicacio = u.id
         LEFT JOIN Incidencies inc
            ON inc.idDispositiu = m.id
            AND inc.dataTancada IS NULL
         LEFT JOIN Estats e ON e.id = inc.idEstat
         WHERE ass.idAlumne = ?
         ORDER BY ass.dataFinal IS NULL DESC, ass.dataInici DESC"
    );
    $stmt->execute([$idAlumne]);
    return $stmt->fetchAll();
}

/**
 * Obté l'historial d'incidències tancades de l'alumne.
 *
 * @param PDO $db       Connexió a la base de dades.
 * @param int $idAlumne ID de l'alumne.
 * @return array Historial d'incidències.
 */
function obtenirHistorialIncidencies(PDO $db, int $idAlumne): array {
    $stmt = $db->prepare(
        "SELECT
            inc.id,
            inc.informacio,
            inc.dataOberta,
            inc.dataTancada,
            tm.tipus,
            m.idInventari,
            e.estat
         FROM Incidencies inc
         JOIN Material m ON m.id = inc.idDispositiu
         JOIN TipusMaterial tm ON m.idTipus = tm.id
         LEFT JOIN Estats e ON e.id = inc.idEstat
         WHERE inc.idAlumne = ?
           AND inc.dataTancada IS NOT NULL
         ORDER BY inc.dataTancada DESC
         LIMIT 10"
    );
    $stmt->execute([$idAlumne]);
    return $stmt->fetchAll();
}

$alumne              = obtenirDadesAlumne($db, $idAlumne);
$dispositius         = obtenirDispositiusAlumne($db, $idAlumne);
$historialIncidencies = obtenirHistorialIncidencies($db, $idAlumne);

// Comptadors ràpids
$totalDispositius  = count($dispositius);
$ambIncidencia     = array_filter($dispositius, fn($d) => $d['idIncidencia'] !== null);
$actius            = array_filter($dispositius, fn($d) => $d['dataFinal'] === null);

capçalera('Els meus dispositius');
mostrarMissatge();
?>

<!-- Targeta de benvinguda -->
<div class="card" style="background: linear-gradient(135deg,#1a4f8a,#15407a); color:white; border-radius:12px;">
    <div style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
        <div>
            <h2 style="font-size:1.4rem; margin-bottom:0.3rem;">
                Benvingut/da, <?= h($alumne['nom'] . ' ' . $alumne['cognom1']) ?>!
            </h2>
            <p style="opacity:0.85; font-size:0.9rem;">
                Grup: <strong><?= h($alumne['grupClasse']) ?></strong>
                &mdash; <?= h($alumne['correu']) ?>
            </p>
        </div>
    </div>
</div>

<!-- Resum ràpid -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="card" style="text-align:center; padding:1.2rem;">
        <div style="font-size:2rem; font-weight:700; color:#1a4f8a;"><?= $totalDispositius ?></div>
        <div style="color:#666; font-size:0.85rem;">Total dispositius</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
        <div style="font-size:2rem; font-weight:700; color:#27ae60;"><?= count($actius) ?></div>
        <div style="color:#666; font-size:0.85rem;">Assignacions actives</div>
    </div>
    <div class="card" style="text-align:center; padding:1.2rem;">
        <div style="font-size:2rem; font-weight:700; color:<?= count($ambIncidencia) > 0 ? '#e74c3c' : '#27ae60' ?>;">
            <?= count($ambIncidencia) ?>
        </div>
        <div style="color:#666; font-size:0.85rem;">Amb incidència</div>
    </div>
</div>

<!-- Dispositius assignats -->
<div class="card">
    <h3 style="color:#1a4f8a; margin-bottom:1.2rem; font-size:1.1rem;">Els meus dispositius</h3>

    <?php if (empty($dispositius)): ?>
        <div style="text-align:center; padding:2rem; color:#999;">
            <p>No tens cap dispositiu assignat en aquest moment.</p>
            <p style="font-size:0.85rem; margin-top:0.5rem;">Si creus que és un error, contacta amb el departament d'informàtica.</p>
        </div>
    <?php else: ?>
        <!-- Vista targetes (una per dispositiu) -->
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap:1rem;">
            <?php foreach ($dispositius as $d):
                $actiu      = ($d['dataFinal'] === null || $d['dataFinal'] >= date('Y-m-d'));
                $incidencia = $d['idIncidencia'] !== null;
            ?>
            <div style="
                border: 2px solid <?= $incidencia ? '#e74c3c' : ($actiu ? '#27ae60' : '#ccc') ?>;
                border-radius: 10px;
                padding: 1.2rem;
                background: <?= $incidencia ? '#fff5f5' : ($actiu ? '#f0fff4' : '#fafafa') ?>;
                position: relative;
            ">
                <!-- Indicador d'estat -->
                <div style="position:absolute; top:1rem; right:1rem;">
                    <?php if ($incidencia): ?>
                        <span class="badge-estat badge-inc">Incidència</span>
                    <?php elseif ($actiu): ?>
                        <span class="badge-estat badge-ok">Actiu</span>
                    <?php else: ?>
                        <span class="badge-estat" style="background:#eee; color:#666;">Retornat</span>
                    <?php endif; ?>
                </div>

                <!-- Icona i nom del dispositiu -->
                <div style="display:flex; align-items:center; gap:0.8rem; margin-bottom:1rem;">
                    <div>

                        <div style="font-weight:700; color:#1a4f8a;"><?= h($d['tipus']) ?></div>
                        <div style="font-size:0.82rem; color:#666;"><?= h($d['model'] ?? '—') ?></div>
                    </div>
                </div>

                <!-- Detalls -->
                <table style="font-size:0.83rem; width:100%; border:none;">
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none; width:40%;">Inventari</td>
                        <td style="font-weight:600; border:none;"><?= h($d['idInventari'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none;">Núm. Sèrie</td>
                        <td style="font-weight:600; border:none;"><?= h($d['numSerie'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none;">Etiqueta</td>
                        <td style="font-weight:600; border:none;"><?= h($d['etiquetaDepInf'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none;">Aula</td>
                        <td style="font-weight:600; border:none;"><?= h($d['aula'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none;">Assignat des de</td>
                        <td style="font-weight:600; border:none;"><?= h($d['dataInici'] ?? '—') ?></td>
                    </tr>
                    <?php if ($d['dataFinal']): ?>
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none;">Retornat el</td>
                        <td style="font-weight:600; border:none; color:#666;"><?= h($d['dataFinal']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($d['macEthernet'] || $d['macWifi']): ?>
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none;">MAC Ethernet</td>
                        <td style="font-weight:600; border:none; font-size:0.78rem;"><?= h($d['macEthernet'] ?? '—') ?></td>
                    </tr>
                    <tr>
                        <td style="color:#888; padding:2px 0; border:none;">MAC WiFi</td>
                        <td style="font-weight:600; border:none; font-size:0.78rem;"><?= h($d['macWifi'] ?? '—') ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <!-- Detall d'incidència si n'hi ha -->
                <?php if ($incidencia): ?>
                <div style="
                    margin-top:0.8rem;
                    padding:0.7rem;
                    background:#fdecea;
                    border-radius:6px;
                    border-left:3px solid #e74c3c;
                    font-size:0.82rem;
                ">
                    <strong style="color:#c0392b;">Incidència oberta</strong>
                    <p style="color:#555; margin-top:0.3rem;"><?= h(mb_substr($d['descIncidencia'] ?? '', 0, 120)) ?>
                        <?= strlen($d['descIncidencia'] ?? '') > 120 ? '...' : '' ?>
                    </p>
                    <p style="color:#888; margin-top:0.2rem; font-size:0.78rem;">
                        Oberta el: <?= h($d['dataIncidencia'] ?? '') ?>
                        <?php if ($d['estatIncidencia']): ?>
                            — Estat: <strong><?= h($d['estatIncidencia']) ?></strong>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Historial d'incidències tancades -->
<?php if (!empty($historialIncidencies)): ?>
<div class="card">
    <h3 style="color:#1a4f8a; margin-bottom:1rem; font-size:1.1rem;">Historial d'incidències resoltes</h3>
    <table>
        <thead>
            <tr>
                <th>Tipus</th>
                <th>Inventari</th>
                <th>Descripció</th>
                <th>Estat</th>
                <th>Data obertura</th>
                <th>Data tancament</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($historialIncidencies as $h_inc): ?>
            <tr>
                <td><?= h($h_inc['tipus']) ?></td>
                <td><?= h($h_inc['idInventari'] ?? '—') ?></td>
                <td style="font-size:0.83rem; max-width:200px;">
                    <?= h(mb_substr($h_inc['informacio'], 0, 80)) ?>...
                </td>
                <td><span class="badge-estat badge-ok"><?= h($h_inc['estat'] ?? 'Resolta') ?></span></td>
                <td><?= h($h_inc['dataOberta']) ?></td>
                <td><?= h($h_inc['dataTancada']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Avís informatiu -->
<div class="card" style="background:#fff8e1; border-left:4px solid #f0a500;">
    <h4 style="color:#e67e22; margin-bottom:0.5rem;">Informació important</h4>
    <ul style="font-size:0.88rem; color:#555; line-height:1.7; padding-left:1.2rem;">
        <li>Ets responsable del material que tens assignat. Tracta'l amb cura.</li>
        <li>Si detectes qualsevol problema o avaria, comunica-ho immediatament al professorat.</li>
        <li>No modifiquis la configuració del sistema ni instal·lis programari no autoritzat.</li>
        <li>En cas de pèrdua o robatori, notifica-ho urgent al departament d'informàtica.</li>
    </ul>
</div>

<?php peu(); ?>
