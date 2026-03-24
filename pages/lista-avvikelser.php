<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

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

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($rapport['title']) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 2rem;
            line-height: 1.5;
        }

        h1 {
            margin-bottom: 0.5rem;
        }

        .rapport-meta {
            margin-bottom: 1.5rem;
            color: #444;
        }

        .actions {
            margin-bottom: 1.5rem;
        }

        .avvikelse-lista {
            display: grid;
            gap: 1rem;
        }

        .avvikelse-kort {
            border: 1px solid #ccc;
            padding: 1rem;
            border-radius: 0.5rem;
            background: #fafafa;
        }

        .avvikelse-kort h2 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .meta {
            margin: 0.25rem 0;
            color: #444;
        }

        .section-label {
            font-weight: bold;
            margin-top: 0.75rem;
        }

        .empty-state {
            padding: 1rem;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            background: #fff8e1;
        }

        a.button {
            display: inline-block;
            padding: 0.6rem 1rem;
            background: #2c6800;
            color: #fff;
            text-decoration: none;
            border-radius: 0.35rem;
        }

        a.button:hover {
            background: #245500;
        }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($rapport['title']) ?></h1>

    <div class="rapport-meta">
        <p><strong>Kund:</strong> <?= htmlspecialchars($rapport['client']) ?></p>
        <p><strong>Webbplats/tjänst:</strong> <?= htmlspecialchars($rapport['siteName']) ?></p>
        <p><strong>Granskningsdatum:</strong> <?= htmlspecialchars($rapport['reviewDate']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($rapport['status']) ?></p>
    </div>

    <div class="actions">
        <a class="button" href="create-avvikelse.php?rapport_id=<?= (int)$rapport['ID'] ?>">Skapa ny avvikelse</a>
    </div>

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
                        <strong>Sidor:</strong>
                        <?= htmlspecialchars($avvikelse['sida_namn'] ?? 'Inga kopplade sidor') ?>
                    </p>

                    <p class="section-label">Raw observation</p>
                    <p><?= nl2br(htmlspecialchars($avvikelse['rawObservation'])) ?></p>

                    <p class="section-label">Avvikelsebeskrivning</p>
                    <p><?= nl2br(htmlspecialchars($avvikelse['deviationDescription'])) ?></p>

                    <p>
                        <a href="edit-avvikelse.php?id=<?= (int)$avvikelse['idAvvikelse'] ?>">Redigera</a>
                        |
                        <a href="delete-avvikelse.php?id=<?= (int)$avvikelse['idAvvikelse'] ?>">Ta bort</a>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>