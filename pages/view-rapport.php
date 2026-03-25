<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
$page_title = "Visa rapport";
require __DIR__ . '/../includes/header.php';

$rapportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($rapportId <= 0) {
    http_response_code(400);
    echo "Ogiltigt rapport-ID";
    exit;
}

// Hämta rapport
$stmt = $pdo->prepare("
    SELECT ID, title, client, siteName, reviewDate, status
    FROM rapport
    WHERE ID = :id
");
$stmt->execute([':id' => $rapportId]);

$rapport = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rapport) {
    http_response_code(404);
    echo "Rapport hittades inte";
    exit;
}

// Hämta antal avvikelser
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM Avvikelse a
    INNER JOIN sida_has_Avvikelse sha 
        ON a.idAvvikelse = sha.Avvikelse_idAvvikelse
    INNER JOIN sida s 
        ON sha.sida_ID = s.ID
    WHERE s.rapport_ID = :rapport_ID
");
$countStmt->execute([':rapport_ID' => $rapportId]);

$totalAvvikelser = (int)$countStmt->fetchColumn();

?>

<div class="report-overview">
    <div class="report-overview-layout">
        <section class="report-main-card">
            <h1><?= htmlspecialchars($rapport['title']) ?></h1>

            <div class="rapport-meta">
                <p><strong>Kund:</strong> <?= htmlspecialchars($rapport['client']) ?></p>
                <p><strong>Webbplats:</strong> <?= htmlspecialchars($rapport['siteName']) ?></p>
                <p><strong>Granskningsdatum:</strong> <?= htmlspecialchars($rapport['reviewDate']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($rapport['status']) ?></p>
            </div>
        </section>

        <aside class="report-side-card">
            <div class="report-stat">
                <p><strong>Antal avvikelser:</strong> <?= $totalAvvikelser ?></p>
            </div>

            <div class="report-actions">
                <a class="button" href="create-avvikelse.php?rapport_id=<?= $rapportId ?>">
                    + Skapa avvikelse
                </a>

                <a class="button secondary" href="lista-avvikelser.php?rapport_id=<?= $rapportId ?>">
                    Visa avvikelser
                </a>

                <a class="button secondary" href="generate-report.php?rapport_id=<?= $rapportId ?>">
                    Generera rapport
                </a>
            </div>

            <p class="back-link">
                <a href="list-rapporter.php">← Tillbaka till rapporter</a>
            </p>
        </aside>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>