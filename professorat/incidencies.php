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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inc_id'], $_POST['accio'])) {
    $incId = (int)$_POST['inc_id'];
    $accio = $_POST['accio'];

    try {
        if ($accio === 'tancar') {
            // Tancar incidència: marcar data i posar estat "Tancada" (id=3)
            $db->prepare(
                "UPDATE Incidencies SET dataTancada = CURDATE(), idEstat = 3 WHERE id = ? AND dataTancada IS NULL"
            )->execute([$incId]);
            setMissatge('Incidència tancada correctament.', 'success');

        } elseif ($accio === 'canviar_estat' && isset($_POST['nou_estat'])) {
            // Canviar estat
            $nouEstat = (int)$_POST['nou_estat'];
            $stmtEstat = $db->prepare("SELECT estat FROM Estats WHERE id = ?");
            $stmtEstat->execute([$nouEstat]);
            $nomEstat = $stmtEstat->fetchColumn();
            if ($nomEstat !== false) {
                $db->prepare(
                    "UPDATE Incidencies SET idEstat = ? WHERE id = ? AND dataTancada IS NULL"
                )->execute([$nouEstat, $incId]);
                setMissatge("Estat actualitzat a «{$nomEstat}».", 'success');
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

// Carrega els estats reals de la BD
$estats = $db->query("SELECT id, estat FROM Estats WHERE LOWER(estat) NOT LIKE '%tanca%' ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);

capçalera('Incidències Obertes');
mostrarMissatge();
?>

<div class="card">
    <p style="color:#666; font-size:0.9rem;">
        Total d'incidències obertes: <strong><?= count($incidencies) ?></strong>
    </p>
</div>

<style>
.inc-row {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    overflow: hidden;
    background: #fff;
    transition: box-shadow 0.15s ease;
}
.inc-row:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.inc-summary {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    cursor: default;
}
.inc-id {
    font-size: 0.78rem;
    color: #999;
    min-width: 2rem;
    font-weight: 600;
}
.inc-tipus {
    font-weight: 600;
    color: #1a4f8a;
    min-width: 90px;
    font-size: 0.9rem;
}
.inc-inventari {
    font-size: 0.85rem;
    color: #555;
    min-width: 90px;
    font-family: monospace;
}
.inc-alumne {
    font-size: 0.85rem;
    color: #333;
    flex: 1;
    min-width: 120px;
}
.inc-alumne a {
    color: #1a4f8a;
    text-decoration: none;
}
.inc-alumne a:hover { text-decoration: underline; }
.inc-data {
    font-size: 0.8rem;
    color: #888;
    min-width: 90px;
    white-space: nowrap;
}
.inc-estat-badge {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    white-space: nowrap;
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffc107;
}
.inc-toggle-btn {
    margin-left: auto;
    background: none;
    border: 1px solid #cbd5e0;
    border-radius: 6px;
    padding: 4px 10px;
    cursor: pointer;
    font-size: 0.8rem;
    color: #555;
    display: flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    transition: background 0.12s, color 0.12s;
    flex-shrink: 0;
}
.inc-toggle-btn:hover {
    background: #eef3ff;
    color: #1a4f8a;
    border-color: #1a4f8a;
}
.inc-toggle-btn .arrow {
    display: inline-block;
    transition: transform 0.2s ease;
    font-style: normal;
}
.inc-toggle-btn.open .arrow {
    transform: rotate(180deg);
}
.inc-details {
    display: none;
    padding: 0 1rem 1rem 1rem;
    border-top: 1px solid #eef3ff;
    background: #f8faff;
}
.inc-details.open {
    display: block;
}
.inc-desc {
    font-size: 0.85rem;
    color: #333;
    line-height: 1.6;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #fff;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    margin-top: 0.75rem;
}
.inc-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}
.inc-meta {
    display: flex;
    gap: 1.5rem;
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
}
.inc-meta span strong { color: #333; }
</style>

<div class="card">
    <?php if (empty($incidencies)): ?>
        <p style="text-align:center; color:#27ae60; font-weight:600; padding:1rem 0;">
            No hi ha incidències obertes.
        </p>
    <?php else: ?>
        <?php foreach ($incidencies as $inc): ?>
        <div class="inc-row">

            <!-- FILA RESUM (sempre visible) -->
            <div class="inc-summary">
                <span class="inc-id">#<?= (int)$inc['id'] ?></span>
                <span class="inc-tipus"><?= h($inc['tipus']) ?></span>
                <span class="inc-inventari"><?= h($inc['idInventari'] ?? '—') ?></span>
                <span class="inc-alumne">
                    <?php if ($inc['idAlumne']): ?>
                        <a href="gestionar_alumne.php?id=<?= (int)$inc['idAlumne'] ?>">
                            <?= h($inc['alumne']) ?>
                        </a>
                    <?php else: ?>
                        <span style="color:#aaa;">Sense alumne</span>
                    <?php endif; ?>
                </span>
                <span class="inc-data"><?= h($inc['dataOberta']) ?></span>
                <span class="inc-estat-badge"><?= h($inc['estat'] ?? 'Sense estat') ?></span>
                <button class="inc-toggle-btn" onclick="toggleDetalls(this)" type="button">
                    Detalls <i class="arrow">▾</i>
                </button>
            </div>

            <!-- PANELL DESPLEGABLE -->
            <div class="inc-details">
                <div class="inc-meta">
                    <span>Grup: <strong><?= h($inc['grupClasse'] ?? '—') ?></strong></span>
                    <span>Núm. Sèrie: <strong><?= h($inc['numSerie'] ?? '—') ?></strong></span>
                    <span>Data obertura: <strong><?= h($inc['dataOberta']) ?></strong></span>
                </div>

                <div class="inc-desc"><?= h($inc['informacio']) ?></div>

                <div class="inc-actions">

                    <!-- Canvi d'estat -->
                    <form method="POST" style="display:flex;gap:0.3rem;align-items:center;">
                        <input type="hidden" name="inc_id" value="<?= (int)$inc['id'] ?>">
                        <input type="hidden" name="accio" value="canviar_estat">
                        <select name="nou_estat"
                            style="font-size:0.8rem;padding:4px 8px;border:1px solid #ccc;border-radius:5px;cursor:pointer;">
                            <?php foreach ($estats as $idE => $nomE): ?>
                            <option value="<?= (int)$idE ?>" <?= ($inc['idEstat'] ?? 0) == $idE ? 'selected' : '' ?>>
                                <?= h($nomE) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary" style="padding:4px 12px;">
                            Aplicar estat
                        </button>
                    </form>

                    <!-- Tancar incidència -->
                    <form method="POST" onsubmit="return confirm('Tancar aquesta incidència?');" style="display:inline;">
                        <input type="hidden" name="inc_id" value="<?= (int)$inc['id'] ?>">
                        <input type="hidden" name="accio" value="tancar">
                        <button type="submit" class="btn btn-sm btn-success"
                            style="padding:4px 12px;background:#27ae60;color:white;border:none;border-radius:5px;cursor:pointer;">
                            ✓ Tancar incidència
                        </button>
                    </form>

                    <!-- Anar al dispositiu -->
                    <a href="gestionar_dispositiu.php?id=<?= (int)$inc['idDispositiu'] ?>"
                       class="btn btn-sm"
                       style="padding:4px 12px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;font-size:0.8rem;">
                        ⚙ Gestionar dispositiu
                    </a>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDetalls(btn) {
    const row = btn.closest('.inc-row');
    const detalls = row.querySelector('.inc-details');
    const isOpen = detalls.classList.contains('open');
    detalls.classList.toggle('open', !isOpen);
    btn.classList.toggle('open', !isOpen);
    btn.innerHTML = !isOpen
        ? 'Tancar <i class="arrow" style="display:inline-block;transform:rotate(180deg);">▾</i>'
        : 'Detalls <i class="arrow">▾</i>';
}
</script>

<?php peu(); ?>