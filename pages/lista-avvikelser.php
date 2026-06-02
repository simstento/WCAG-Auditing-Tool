<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
$page_title = "Lista över avvikelser";
require __DIR__ . '/../includes/header.php';


$rapportId = isset($_GET['rapport_id']) ? (int)$_GET['rapport_id'] : 1;

/**
 * Hämta rapportens metadata
 */
$rapportStmt = $pdo->prepare("
    SELECT ID, title, client, siteName, reviewDate, status
    FROM rapport
    WHERE ID = :rapport_ID
");
$rapportStmt->execute([
    ':rapport_ID' => $rapportId
]);

$rapport = $rapportStmt->fetch();

/**
 * Om rapporten inte finns
 */
if (!$rapport) {
    http_response_code(404);
    echo "<h1>Rapporten kunde inte hittas</h1>";
    exit;
}

/**
 * Hämta avvikelser för rapporten
 */
$sql = "
    SELECT
        a.idAvvikelse,
        a.title,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.priority,
        a.rawObservation,
        a.deviationDescription,
        a.is_global,
        a.global_section,
        GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS sida_namn
    FROM Avvikelse a
    LEFT JOIN sida_has_Avvikelse sha
        ON a.idAvvikelse = sha.Avvikelse_idAvvikelse
    LEFT JOIN sida s
        ON sha.sida_ID = s.ID
    WHERE a.rapport_ID = :rapport_ID
    GROUP BY
        a.idAvvikelse,
        a.title,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.priority,
        a.rawObservation,
        a.deviationDescription
    ORDER BY
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.title
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':rapport_ID' => $rapportId
]);

$avvikelser = $stmt->fetchAll();
?>

    <div class="report-detail-page">
    <div class="report-detail-layout">
        <aside class="report-sidebar">
            <div class="report-sidebar-card">
                <h1><?= htmlspecialchars($rapport['title']) ?></h1>

                <div class="rapport-meta">
                    <p><strong>Kund:</strong> <?= htmlspecialchars($rapport['client']) ?></p>
                    <p><strong>Webbplats/tjänst:</strong> <?= htmlspecialchars($rapport['siteName']) ?></p>
                    <p><strong>Granskningsdatum:</strong> <?= htmlspecialchars($rapport['reviewDate']) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($rapport['status']) ?></p>
                </div>

                <div class="report-sidebar-actions">
                    <a class="button" href="create-avvikelse.php?rapport_id=<?= (int)$rapport['ID'] ?>">
                        Skapa ny avvikelse
                    </a>
                </div>
            </div>
        </aside>

        <section class="report-content">
            <?php if (empty($avvikelser)): ?>
                <div class="empty-state">
                    <p>Det finns inga avvikelser registrerade för denna rapport ännu.</p>
                </div>
            <?php else: ?>
                <div class="avvikelse-lista">
                    <?php foreach ($avvikelser as $avvikelse): ?>
                        <article class="avvikelse-kort">
                            <h2><?= htmlspecialchars($avvikelse['title']) ?></h2>

                            <p class="meta">
                                <strong>Kapitel:</strong>
                                <?= htmlspecialchars($avvikelse['chapter_1']) ?>
                                → <?= htmlspecialchars($avvikelse['chapter_2']) ?>
                                → <?= htmlspecialchars($avvikelse['chapter_3']) ?>
                            </p>

                            <p class="meta">
                                <strong>Prioritet:</strong>
                                <?= htmlspecialchars($avvikelse['priority']) ?>
                            </p>

                            <p class="meta">
                            <strong><?= (int)$avvikelse['is_global'] === 1 ? 'Sida:' : 'Sidor:' ?></strong>
                                <?= (int)$avvikelse['is_global'] === 1
                                    ? htmlspecialchars($avvikelse['global_section'] ?? '')
                                    : htmlspecialchars($avvikelse['sida_namn'] ?? 'Inga kopplade sidor') ?>
                            </p>

                            <p class="section-label">Raw observation</p>
                            <p><?= nl2br(htmlspecialchars($avvikelse['rawObservation'])) ?></p>

                            <p class="section-label">Avvikelsebeskrivning</p>
                            <p><?= nl2br(htmlspecialchars($avvikelse['deviationDescription'])) ?></p>

                            <div class="card-actions">
                                <a href="edit-avvikelse.php?id=<?= (int)$avvikelse['idAvvikelse'] ?>">Redigera</a>
                                <a href="delete-avvikelse.php?id=<?= (int)$avvikelse['idAvvikelse'] ?>">Ta bort</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>