<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
$page_title = "Lista över rapporter";
require __DIR__ . '/../includes/header.php';

$stmt = $pdo->query("
    SELECT ID, title, client, siteName, reviewDate, status
    FROM rapport
    ORDER BY ID DESC
");

$rapporter = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="reports-page">
    <div class="topbar">
        <h1>Rapporter</h1>
    </div>
    <div class="page-actions">
        <a class="button" href="create-rapport.php">Skapa rapport</a>
    </div>

    <?php if (empty($rapporter)): ?>
        <div class="empty-state">
            <p>Det finns inga rapporter ännu.</p>
        </div>
    <?php else: ?>
        <div class="table-card">
        <table class="report-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titel</th>
                    <th>Kund</th>
                    <th>Webbplats</th>
                    <th>Granskningsdatum</th>
                    <th>Status</th>
                    <th>Åtgärder</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rapporter as $rapport): ?>
                    <tr>
                        <td><?= (int)$rapport['ID'] ?></td>
                        <td><?= htmlspecialchars($rapport['title']) ?></td>
                        <td><?= htmlspecialchars($rapport['client']) ?></td>
                        <td><?= htmlspecialchars($rapport['siteName']) ?></td>
                        <td><?= htmlspecialchars($rapport['reviewDate']) ?></td>
                        <td><?= htmlspecialchars($rapport['status']) ?></td>
                        <td class="actions">
                            <a href="view-rapport.php?id=<?= (int)$rapport['ID'] ?>">Öppna</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>