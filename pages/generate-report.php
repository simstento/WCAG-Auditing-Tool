<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
/*$page_title = "Skapa rapport";*/
require __DIR__ . '/../includes/header.php';

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

$globalSql = "
    SELECT
        a.idAvvikelse,
        a.title,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.rawObservation,
        a.deviationDescription,
        a.priority,
        a.atgarda_text,
        a.global_section,

        GROUP_CONCAT(
            DISTINCT CONCAT(w.code, ' (', w.level, ')')
            ORDER BY w.code
            SEPARATOR ', '
        ) AS wcag_list

    FROM Avvikelse a
    LEFT JOIN Avvikelse_has_WCAG ahw
        ON a.idAvvikelse = ahw.Avvikelse_idAvvikelse
    LEFT JOIN WCAG w
        ON ahw.WCAG_id = w.id
    WHERE a.rapport_ID = :rapport_ID
      AND a.is_global = 1
    GROUP BY
        a.idAvvikelse,
        a.title,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.rawObservation,
        a.deviationDescription,
        a.priority,
        a.atgarda_text,
        a.global_section
    ORDER BY
        a.global_section,
        a.chapter_1,
        a.chapter_2,
        a.chapter_3,
        a.title
";

$globalStmt = $pdo->prepare($globalSql);
$globalStmt->execute([
    ':rapport_ID' => $rapportId
]);

$globalFindings = $globalStmt->fetchAll(PDO::FETCH_ASSOC);

$groupedGlobalFindings = [
    'Ramverk' => [],
    'Navigering' => []
];

foreach ($globalFindings as $finding) {
    $section = $finding['global_section'] ?? '';

    if (!isset($groupedGlobalFindings[$section])) {
        $groupedGlobalFindings[$section] = [];
    }

    $chapter1 = $finding['chapter_1'] ?: 'Okategoriserat';
    $chapter2 = $finding['chapter_2'] ?: 'Okategoriserat';
    $chapter3 = $finding['chapter_3'] ?: 'Okategoriserat';

    $groupedGlobalFindings[$section][$chapter1][$chapter2][$chapter3][] = $finding;
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
    AND a.is_global = 0
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
function renderFinding(array $finding): void
{
    ?>
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
    <?php
}
/**
 * Render nested grouped findings with proper heading hierarchy
 * 
 * @param array $data Nested array structure of findings
 * @param int $depthLevel Current heading depth (3-5 for h3-h5)
 * @param string $hideLabel Optional level name to skip rendering heading (e.g., 'Okategoriserat')
 */
function renderGroupedFindings($data, $depthLevel = 3, $hideLabel = null): void {
    foreach ($data as $label => $items) {
        if (empty($items)) {
            continue;
        }

        // Check if this is the final level (array of findings with idAvvikelse)
        $isFindingLevel = isset($items[0]) && is_array($items[0]) && isset($items[0]['idAvvikelse']);

        if ($isFindingLevel) {
            // Render finding items
            foreach ($items as $finding) {
                renderFinding($finding);
            }
        } else {
            // Render heading (unless it should be hidden)
            if ($hideLabel !== $label) {
                $headingTag = "h{$depthLevel}";
                $classAttr = ($depthLevel === 6) ? " class=\"chapter-3-heading\"" : "";
                echo "<{$headingTag}{$classAttr}>" . htmlspecialchars($label) . "</{$headingTag}>";
            }

            // Recurse into next level
            renderGroupedFindings($items, $depthLevel + 1, $hideLabel);
        }
    }
}

$hasGlobalFindings = false;
    foreach ($groupedGlobalFindings as $sectionGroups) {
        if (!empty($sectionGroups)) {
            $hasGlobalFindings = true;
            break;
        }
    }

$hasPageFindings = !empty($groupedByPage);
$hasAnyFindings = $hasGlobalFindings || $hasPageFindings;

?>
<div class="generate-report-page">
    <div class="generate-report-header">
    <h1><?= htmlspecialchars($rapport['title']) ?></h1>
    <div class="rapport-meta">
            <p><strong>Kund:</strong> <?= htmlspecialchars($rapport['client']) ?></p>
            <p><strong>Webbplats/tjänst:</strong> <?= htmlspecialchars($rapport['siteName']) ?></p>
            <p><strong>Granskningsdatum:</strong> <?= htmlspecialchars($rapport['reviewDate']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($rapport['status']) ?></p>
        </div>
    </div>

        <?php if (!$hasAnyFindings): ?>
            <div class="empty">
                <p>Det finns inga avvikelser registrerade för denna rapport.</p>
            </div>
        <?php else: ?>
       
        <?php if ($hasGlobalFindings): ?>
            <section class="page-section">
                <h2>Avvikelser som förekommer på alla de testade sidorna</h2>
                <?php renderGroupedFindings($groupedGlobalFindings, 3, 'Okategoriserat'); ?>
            </section>
        <?php endif; ?>

        <?php foreach ($groupedByPage as $sidaNamn => $pageData): ?>
            <section class="page-section">
                <h2><?= htmlspecialchars($sidaNamn) ?></h2>

                <?php if (!empty($pageData['meta']['sida_url'])): ?>
                    <p class="page-meta">
                        <strong>URL:</strong>
                        <?= htmlspecialchars($pageData['meta']['sida_url']) ?>
                    </p>
                <?php endif; ?>

                <?php renderGroupedFindings($pageData['chapters'], 3); ?>
            </section>
            <?php endforeach; ?>

        <?php endif; ?>
        
        <div class="actions">
            <a class="button" href="lista-avvikelser.php?rapport_id=<?= (int)$rapport['ID'] ?>">Till avvikelselista</a>
            <button class="button" onclick="window.print()">Skriv ut / Spara som PDF</button>
        </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>