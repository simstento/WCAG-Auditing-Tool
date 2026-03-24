<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

$rapportId = isset($_GET['rapport_id']) ? (int)$_GET['rapport_id'] : 1;

/**
 * Hämta rapportmetadata
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

if (!$rapport) {
    http_response_code(404);
    echo "<h1>Rapporten kunde inte hittas</h1>";
    exit;
}

/**
 * Hämta data sida-för-sida.
 * Viktigt:
 * - en rad per sida + avvikelse
 * - WCAG slås ihop med GROUP_CONCAT
 */
$sql = "
    SELECT
        s.ID AS sida_id,
        s.name AS sida_namn,
        s.url AS sida_url,

        a.idAvvikelse,
        a.title,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.rawObservation,
        a.deviationDescription,
        a.priority,
        a.atgarda_text,

        GROUP_CONCAT(
            DISTINCT CONCAT(w.code, ' (', w.level, ')')
            ORDER BY w.code
            SEPARATOR ', '
        ) AS wcag_list

    FROM sida s
    INNER JOIN sida_has_Avvikelse sha
        ON s.ID = sha.sida_ID
    INNER JOIN Avvikelse a
        ON sha.Avvikelse_idAvvikelse = a.idAvvikelse
    LEFT JOIN Avvikelse_has_WCAG ahw
        ON a.idAvvikelse = ahw.Avvikelse_idAvvikelse
    LEFT JOIN WCAG w
        ON ahw.WCAG_id = w.id
    WHERE s.rapport_ID = :rapport_ID
    GROUP BY
        s.ID,
        s.name,
        s.url,
        a.idAvvikelse,
        a.title,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.rawObservation,
        a.deviationDescription,
        a.priority,
        a.atgarda_text
    ORDER BY
        s.name,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.title
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':rapport_ID' => $rapportId
]);

$findings = $stmt->fetchAll();

/**
 * Gruppera så här:
 * Sida -> Kapitel 1 -> Kapitel 2 -> Kapitel 3 -> avvikelser[]
 */
$groupedByPage = [];

foreach ($findings as $finding) {
    $sidaNamn = $finding['sida_namn'] ?: 'Namnlös sida';
    $chapter1 = $finding['chapter_1'] ?: 'Okategoriserat';
    $chapter2 = $finding['chapter_2'] ?: 'Okategoriserat';
    $chapter3 = $finding['chapter_3'] ?: 'Okategoriserat';

    $groupedByPage[$sidaNamn]['meta'] = [
        'sida_id' => $finding['sida_id'],
        'sida_url' => $finding['sida_url']
    ];

    $groupedByPage[$sidaNamn]['chapters'][$chapter1][$chapter2][$chapter3][] = $finding;
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($rapport['title']) ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 2rem auto;
            max-width: 1100px;
            line-height: 1.55;
            color: #111;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        h2 {
            font-size: 1.6rem;
            margin-top: 2.2rem;
            margin-bottom: 0.75rem;
            border-bottom: 2px solid #ccc;
            padding-bottom: 0.2rem;
        }

        h3 {
            font-size: 1.3rem;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }

        h4 {
            font-size: 1.1rem;
            margin-top: 1.2rem;
            margin-bottom: 0.5rem;
        }

        h5 {
            font-size: 1rem;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }

        .rapport-meta {
            margin-bottom: 2rem;
            color: #444;
        }

        .actions {
            margin-bottom: 1.5rem;
        }

        .button {
            display: inline-block;
            padding: 0.6rem 1rem;
            background: #2c6800;
            color: #fff;
            text-decoration: none;
            border-radius: 0.35rem;
            margin-right: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .button:hover {
            background: #245500;
        }

        .page-section {
            margin-bottom: 2rem;
        }

        .page-meta {
            color: #444;
            margin-bottom: 1rem;
        }

        .finding {
            border: 1px solid #ddd;
            border-radius: 0.4rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fafafa;
        }

        .finding-title {
            font-size: 1.05rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .label {
            font-weight: bold;
            margin-top: 0.75rem;
            display: block;
        }

        .meta {
            color: #444;
            margin-bottom: 0.35rem;
        }

        .empty {
            padding: 1rem;
            border: 1px solid #ccc;
            background: #fff8e1;
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

    <?php if (empty($groupedByPage)): ?>
        <div class="empty">
            <p>Det finns inga avvikelser registrerade för denna rapport.</p>
        </div>
    <?php else: ?>

        <?php foreach ($groupedByPage as $sidaNamn => $pageData): ?>
            <section class="page-section">
                <h2><?= htmlspecialchars($sidaNamn) ?></h2>

                <?php if (!empty($pageData['meta']['sida_url'])): ?>
                    <p class="page-meta">
                        <strong>URL:</strong>
                        <?= htmlspecialchars($pageData['meta']['sida_url']) ?>
                    </p>
                <?php endif; ?>

                <?php foreach ($pageData['chapters'] as $chapter1 => $chapter2Groups): ?>
                    <h3><?= htmlspecialchars($chapter1) ?></h3>

                    <?php foreach ($chapter2Groups as $chapter2 => $chapter3Groups): ?>
                        <h4><?= htmlspecialchars($chapter2) ?></h4>

                        <?php foreach ($chapter3Groups as $chapter3 => $chapterFindings): ?>
                            <h5><?= htmlspecialchars($chapter3) ?></h5>

                            <?php foreach ($chapterFindings as $finding): ?>
                                <article class="finding">
                                    <div class="finding-title">
                                        <?= htmlspecialchars($finding['title']) ?>
                                    </div>

                                    <p class="meta">
                                        <strong>Prioritet:</strong>
                                        <?= htmlspecialchars($finding['priority']) ?>
                                    </p>

                                    <span class="label">Observation</span>
                                    <p><?= nl2br(htmlspecialchars($finding['rawObservation'])) ?></p>

                                    <span class="label">Avvikelsebeskrivning</span>
                                    <p><?= nl2br(htmlspecialchars($finding['deviationDescription'])) ?></p>

                                    <span class="label">Åtgärda</span>
                                    <p><?= nl2br(htmlspecialchars($finding['atgarda_text'] ?? '')) ?></p>

                                    <span class="label">WCAG</span>
                                    <p><?= htmlspecialchars($finding['wcag_list'] ?? 'Ej angivet') ?></p>
                                </article>
                            <?php endforeach; ?>

                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

    <?php endif; ?>
    
    <div class="actions">
        <a class="button" href="lista-avvikelser.php?rapport_id=<?= (int)$rapport['ID'] ?>">Till avvikelselista</a>
        <button class="button" onclick="window.print()">Skriv ut / Spara som PDF</button>
    </div>
</body>
</html>