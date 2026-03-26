<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

function normalizeUrl(string $url): string
{
    $url = trim($url);

    if ($url === '') {
        return '';
    }

    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }

    return $url;
}

$rapportId = isset($_GET['rapport_id']) ? (int)$_GET['rapport_id'] : 0;

if ($rapportId <= 0) {
    http_response_code(400);
    exit('Ingen rapport vald.');
}

$rapportStmt = $pdo->prepare("
    SELECT ID, title
    FROM rapport
    WHERE ID = :rapport_ID
");
$rapportStmt->execute([
    ':rapport_ID' => $rapportId
]);

$rapport = $rapportStmt->fetch(PDO::FETCH_ASSOC);

if (!$rapport) {
    http_response_code(404);
    exit('Rapporten kunde inte hittas.');
}

$errors = [];
$pageRows = [
    ['name' => '', 'url' => ''],
    ['name' => '', 'url' => ''],
    ['name' => '', 'url' => ''],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $names = $_POST['page_name'] ?? [];
    $urls = $_POST['page_url'] ?? [];

    $pageRows = [];
    $rowsToInsert = [];

    $rowCount = max(count($names), count($urls));

    for ($i = 0; $i < $rowCount; $i++) {
        $name = trim($names[$i] ?? '');
        $rawUrl = trim($urls[$i] ?? '');
        $url = normalizeUrl($rawUrl);

        $pageRows[] = [
            'name' => $name,
            'url' => $rawUrl
        ];

        if ($name === '' && $rawUrl === '') {
            continue;
        }

        if ($name === '') {
            $errors[] = 'Sidnamn saknas på rad ' . ($i + 1) . '.';
            continue;
        }

        if ($rawUrl === '') {
            $errors[] = 'URL saknas på rad ' . ($i + 1) . '.';
            continue;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Ogiltig URL på rad ' . ($i + 1) . '.';
            continue;
        }

        $rowsToInsert[] = [
            'name' => $name,
            'url' => $url
        ];
    }

    if (empty($rowsToInsert) && empty($errors)) {
        $errors[] = 'Fyll i minst en sida.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sida (rapport_ID, name, url)
                VALUES (:rapport_ID, :name, :url)
            ");

            foreach ($rowsToInsert as $row) {
                $stmt->execute([
                    ':rapport_ID' => $rapportId,
                    ':name' => $row['name'],
                    ':url' => $row['url']
                ]);
            }

            header('Location: view-rapport.php?id=' . $rapportId);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Det gick inte att spara sidorna: ' . $e->getMessage();
        }
    }

    while (count($pageRows) < 3) {
        $pageRows[] = ['name' => '', 'url' => ''];
    }
}

$title = 'Lägg till sidor';
require __DIR__ . '/../includes/header.php';
?>

<div class="form-wrapper">
    <div class="card">
        <h1>Lägg till sidor</h1>

        <p><strong>Rapport:</strong> <?= htmlspecialchars($rapport['title']) ?></p>
        <p class="helper-text">Du kan ange URL utan https://, till exempel www.exempelsida.se.</p>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div id="page-rows" class="multi-page-list">
                <?php foreach ($pageRows as $index => $row): ?>
                    <div class="page-row-card">
                        <h2>Rad <?= $index + 1 ?></h2>

                        <div class="form-group">
                            <label for="page_name_<?= $index ?>">Sidnamn</label>
                            <input
                                type="text"
                                id="page_name_<?= $index ?>"
                                name="page_name[]"
                                value="<?= htmlspecialchars($row['name']) ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="page_url_<?= $index ?>">URL</label>
                            <input
                                type="text"
                                id="page_url_<?= $index ?>"
                                name="page_url[]"
                                value="<?= htmlspecialchars($row['url']) ?>"
                                placeholder="www.exempelsida.se/sida"
                            >
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-actions">
                <button type="button" class="button secondary" id="add-page-row">
                    + Lägg till rad</button>

                <button type="submit" class="button">
                    Spara sidor
                </button>

                <a class="button secondary" href="view-rapport.php?id=<?= (int)$rapportId ?>">
                    Avbryt
                </a>
            </div>
        </form>
    </div>
</div>
<script src="../assets/js/chapters.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>