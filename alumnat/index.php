<?php
require_once __DIR__ . '/../includes/layout.php';

requerirAutenticacio();

if (esProfessor()) {
    header('Location: ' . BASE_URL . 'professorat/index.php');
    exit;
}

$db       = getDB();
$idAlumne = (int)$_SESSION['usuari_id'];

// Obté dades de l'alumne, amb fallback al correu de sessió
$stmt = $db->prepare(
    "SELECT id, nom, cognom1,
            COALESCE(cognom2,'') AS cognom2,
            correu,
            COALESCE(grupClasse,'') AS grupClasse
     FROM Alumnes WHERE id = ? LIMIT 1"
);
$stmt->execute([$idAlumne]);
$alumne = $stmt->fetch();

if (!$alumne && !empty($_SESSION['correu'])) {
    $stmt2 = $db->prepare(
        "SELECT id, nom, cognom1,
                COALESCE(cognom2,'') AS cognom2,
                correu,
                COALESCE(grupClasse,'') AS grupClasse
         FROM Alumnes WHERE correu = ? LIMIT 1"
    );
    $stmt2->execute([$_SESSION['correu']]);
    $alumne = $stmt2->fetch();
    if ($alumne) {
        $_SESSION['usuari_id'] = (int)$alumne['id'];
        $idAlumne = (int)$alumne['id'];
    }
}

if (!$alumne) {
    $alumne = [
        'id'         => $idAlumne,
        'nom'        => $_SESSION['nom'] ?? 'Usuari',
        'cognom1'    => '',
        'cognom2'    => '',
        'correu'     => $_SESSION['correu'] ?? '',
        'grupClasse' => '',
    ];
}

// Dispositius de l'alumne
$stmtDev = $db->prepare(
    "SELECT
        m.id AS idMaterial,
        tm.tipus,
        COALESCE(tm.model,'') AS model,
        COALESCE(m.idInventari,'') AS idInventari,
        COALESCE(m.numSerie,'') AS numSerie,
        COALESCE(m.etiquetaDepInf,'') AS etiquetaDepInf,
        COALESCE(m.macEthernet,'') AS macEthernet,
        COALESCE(m.macWifi,'') AS macWifi,
        COALESCE(u.nom,'') AS aula,
        ass.id AS idAssignacio,
        ass.dataInici,
        ass.dataFinal,
        inc.id AS idIncidencia,
        COALESCE(inc.informacio,'') AS descIncidencia,
        inc.dataOberta AS dataIncidencia,
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

// Historial incidències tancades
$stmtHist = $db->prepare(
    "SELECT
        inc.informacio,
        inc.dataOberta,
        inc.dataTancada,
        tm.tipus,
        COALESCE(m.idInventari,'') AS idInventari,
        COALESCE(e.estat,'Resolta') AS estat
     FROM Incidencies inc
     JOIN Material m ON m.id = inc.idDispositiu
     JOIN TipusMaterial tm ON m.idTipus = tm.id
     LEFT JOIN Estats e ON e.id = inc.idEstat
     WHERE inc.idAlumne = ? AND inc.dataTancada IS NOT NULL
     ORDER BY inc.dataTancada DESC LIMIT 10"
);
$stmtHist->execute([$idAlumne]);
$historial = $stmtHist->fetchAll();

$totalDev  = count($dispositius);
$ambInc    = count(array_filter($dispositius, fn($d) => $d['idIncidencia'] !== null));
$actius    = count(array_filter($dispositius, fn($d) => $d['dataFinal'] === null));

$nomMostrat = trim(($alumne['nom'] ?? '') . ' ' . ($alumne['cognom1'] ?? ''));

capçalera('Els meus dispositius');
mostrarMissatge();
?>

<!-- Benvinguda -->
<div class="card" style="background:linear-gradient(135deg,#1a4f8a,#15407a);color:white;">
    <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
        <div style="font-size:3.5rem;">👨‍💻</div>
        <div>
            <h2 style="font-size:1.4rem;margin-bottom:0.3rem;">Benvingut/da, <?= htmlspecialchars($nomMostrat, ENT_QUOTES, 'UTF-8') ?>!</h2>
            <p style="opacity:0.85;font-size:0.9rem;">
                <?php if ($alumne['grupClasse']): ?>
                    Grup: <strong><?= htmlspecialchars($alumne['grupClasse'], ENT_QUOTES, 'UTF-8') ?></strong> &mdash;
                <?php endif; ?>
                <?= htmlspecialchars($alumne['correu'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
    </div>
</div>

<!-- Resum -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem;">
    <div class="card" style="text-align:center;padding:1.2rem;">
        <div style="font-size:2rem;">🖥️</div>
        <div style="font-size:2rem;font-weight:700;color:#1a4f8a;"><?= $totalDev ?></div>
        <div style="color:#666;font-size:0.85rem;">Total dispositius</div>
    </div>
    <div class="card" style="text-align:center;padding:1.2rem;">
        <div style="font-size:2rem;">✅</div>
        <div style="font-size:2rem;font-weight:700;color:#27ae60;"><?= $actius ?></div>
        <div style="color:#666;font-size:0.85rem;">Actius</div>
    </div>
    <div class="card" style="text-align:center;padding:1.2rem;">
        <div style="font-size:2rem;">⚠️</div>
        <div style="font-size:2rem;font-weight:700;color:<?= $ambInc > 0 ? '#e74c3c' : '#27ae60' ?>;"><?= $ambInc ?></div>
        <div style="color:#666;font-size:0.85rem;">Amb incidència</div>
    </div>
</div>

<!-- Dispositius -->
<div class="card">
    <h3 style="color:#1a4f8a;margin-bottom:1.2rem;">📋 Els meus dispositius</h3>

    <?php if (empty($dispositius)): ?>
        <div style="text-align:center;padding:2rem;color:#999;">
            <div style="font-size:3rem;">📭</div>
            <p style="margin-top:0.5rem;">No tens cap dispositiu assignat.</p>
            <p style="font-size:0.85rem;margin-top:0.3rem;">Si creus que és un error, contacta amb el departament d'informàtica.</p>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
        <?php foreach ($dispositius as $d):
            $actiu     = ($d['dataFinal'] === null || $d['dataFinal'] >= date('Y-m-d'));
            $teInc     = !empty($d['idIncidencia']);
            $color     = $teInc ? '#e74c3c' : ($actiu ? '#27ae60' : '#ccc');
            $bg        = $teInc ? '#fff5f5' : ($actiu ? '#f0fff4' : '#fafafa');
            $icona     = match(true) {
                str_contains(strtolower($d['tipus']), 'port') => '💻',
                str_contains(strtolower($d['tipus']), 'tecl') => '⌨️',
                str_contains(strtolower($d['tipus']), 'rat')  => '🖱️',
                str_contains(strtolower($d['tipus']), 'mon')  => '🖥️',
                str_contains(strtolower($d['tipus']), 'tab')  => '📱',
                default => '🔧'
            };
        ?>
            <div style="border:2px solid <?= $color ?>;border-radius:10px;padding:1.2rem;background:<?= $bg ?>;position:relative;">

                <!-- Badge estat -->
                <div style="position:absolute;top:0.8rem;right:0.8rem;">
                    <?php if ($teInc): ?>
                        <span class="badge-estat badge-inc">⚠️ Incidència</span>
                    <?php elseif ($actiu): ?>
                        <span class="badge-estat badge-ok">✅ Actiu</span>
                    <?php else: ?>
                        <span class="badge-estat" style="background:#eee;color:#666;">Retornat</span>
                    <?php endif; ?>
                </div>

                <!-- Capçalera -->
                <div style="display:flex;align-items:center;gap:0.8rem;margin-bottom:1rem;padding-right:5rem;">
                    <span style="font-size:2rem;"><?= $icona ?></span>
                    <div>
                        <div style="font-weight:700;color:#1a4f8a;"><?= htmlspecialchars($d['tipus'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($d['model']): ?>
                            <div style="font-size:0.82rem;color:#666;"><?= htmlspecialchars($d['model'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dades -->
                <table style="font-size:0.83rem;width:100%;">
                    <?php
                    $camps = [
                        'Inventari'       => $d['idInventari'],
                        'Núm. Sèrie'      => $d['numSerie'],
                        'Etiqueta'        => $d['etiquetaDepInf'],
                        'Aula'            => $d['aula'],
                        'Assignat des de' => $d['dataInici'],
                    ];
                    foreach ($camps as $etiq => $val):
                        if (empty($val)) continue;
                    ?>
                        <tr>
                            <td style="color:#888;padding:2px 0;border:none;width:45%;"><?= $etiq ?></td>
                            <td style="font-weight:600;border:none;"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($d['dataFinal']): ?>
                        <tr>
                            <td style="color:#888;padding:2px 0;border:none;">Retornat el</td>
                            <td style="font-weight:600;border:none;color:#888;"><?= htmlspecialchars($d['dataFinal'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endif; ?>
                </table>

                <!-- Incidència oberta -->
                <?php if ($teInc): ?>
                    <div style="margin-top:0.8rem;padding:0.7rem;background:#fdecea;border-radius:6px;border-left:3px solid #e74c3c;font-size:0.82rem;">
                        <strong style="color:#c0392b;">Incidència oberta</strong>
                        <p style="color:#555;margin-top:0.3rem;">
                            <?= htmlspecialchars(mb_substr($d['descIncidencia'], 0, 120), ENT_QUOTES, 'UTF-8') ?>
                            <?= mb_strlen($d['descIncidencia']) > 120 ? '...' : '' ?>
                        </p>
                        <?php if ($d['dataIncidencia']): ?>
                            <p style="color:#888;margin-top:0.3rem;font-size:0.78rem;">
                                Data: <?= htmlspecialchars($d['dataIncidencia'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($d['estatIncidencia']): ?>
                                    — <strong><?= htmlspecialchars($d['estatIncidencia'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Historial -->
<?php if (!empty($historial)): ?>
<div class="card">
    <h3 style="color:#1a4f8a;margin-bottom:1rem;">📁 Historial d'incidències resoltes</h3>
    <table>
        <thead>
            <tr>
                <th>Tipus</th>
                <th>Inventari</th>
                <th>Descripció</th>
                <th>Estat</th>
                <th>Oberta</th>
                <th>Tancada</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($historial as $hi): ?>
            <tr>
                <td><?= htmlspecialchars($hi['tipus'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($hi['idInventari'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="font-size:0.83rem;">
                    <?= htmlspecialchars(mb_substr($hi['informacio'], 0, 80), ENT_QUOTES, 'UTF-8') ?>...
                </td>
                <td><span class="badge-estat badge-ok"><?= htmlspecialchars($hi['estat'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars($hi['dataOberta'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($hi['dataTancada'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Avís -->
<div class="card" style="background:#fff8e1;border-left:4px solid #f0a500;">
    <h4 style="color:#e67e22;margin-bottom:0.5rem;">ℹ️ Informació important</h4>
    <ul style="font-size:0.88rem;color:#555;line-height:1.8;padding-left:1.2rem;">
        <li>Ets responsable del material que tens assignat. Tracta'l amb cura.</li>
        <li>Si detectes qualsevol problema, comunica-ho immediatament al professorat.</li>
        <li>No instal·lis programari no autoritzat ni modifiquis la configuració del sistema.</li>
        <li>En cas de pèrdua o robatori, notifica-ho urgentment al departament d'informàtica.</li>
    </ul>
</div>

<?php peu(); ?>