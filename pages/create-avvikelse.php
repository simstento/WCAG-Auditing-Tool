<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../includes/chapters.php';
require __DIR__ . '/../functions/functions.php';

$stmtWcag = $pdo->query("SELECT id, code, title, level FROM WCAG ORDER BY code");
$wcagList = $stmtWcag->fetchAll();

$rapportId = isset($_GET['rapport_id']) ? (int)$_GET['rapport_id'] : 0;

if ($rapportId <= 0) {
    die('Ingen rapport vald.');
}

// Hämta sidor som tillhör rapporten
$stmt = $pdo->prepare("SELECT ID, name FROM sida WHERE rapport_ID = :rapport_ID ORDER BY name");
$stmt->execute([':rapport_ID' => $rapportId]);
$sidor = $stmt->fetchAll();

$selectedWcag = [];

$errors = [];
$success = '';

$title = '';
$kapitel1 = '';
$kapitel2 = '';
$kapitel3 = '';
$rawObservation = '';
$deviationDescription = '';
$priority = '';
$atgardaText = '';
$selectedSidor = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $atgardaText = trim($_POST['atgarda_text'] ?? '');
    $kapitel1 = trim($_POST['kapitel_1'] ?? '');
    $kapitel2 = trim($_POST['kapitel_2'] ?? '');
    $kapitel3 = trim($_POST['kapitel_3'] ?? '');
    $rawObservation = trim($_POST['rawObservation'] ?? '');
    $deviationDescription = trim($_POST['deviationDescription'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $selectedSidor = $_POST['sidor'] ?? [];
    $selectedWcag = $_POST['wcag'] ?? [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_avvikelse'])) {
    if ($title === '') {
        $errors[] = 'Titel måste anges.';
    }

    if ($rawObservation === '') {
        $errors[] = 'Raw observation måste anges.';
    }

    if ($deviationDescription === '') {
        $errors[] = 'Avvikelsebeskrivning måste anges.';
    }

    if ($priority === '') {
        $errors[] = 'Prioritet måste väljas.';
    }

    if (empty($selectedSidor)) {
        $errors[] = 'Minst en sida måste väljas.';
    }

    if ($atgardaText === '') {
        $errors[] = 'Åtgärda måste anges.';
    }

    if (empty($selectedWcag)) {
        $errors[] = 'Minst ett WCAG-kriterium måste väljas.';
    }

    if ($kapitel1 !== '' && !chapterHasLevel3($chapters, $kapitel1)) {
    $kapitel3 = '';
}

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $insertAvvikelse = $pdo->prepare("
                INSERT INTO Avvikelse (
                    chapter_1,
                    chapter_2,
                    chapter_3,
                    title,
                    rawObservation,
                    deviationDescription,
                    rapport_ID,
                    priority,
                    atgarda_text
                ) VALUES (
                    :chapter_1,
                    :chapter_2,
                    :chapter_3,
                    :title,
                    :rawObservation,
                    :deviationDescription,
                    :rapport_ID,
                    :priority,
                    :atgarda_text
                )
            ");

            $insertAvvikelse->execute([
                ':chapter_1' => $kapitel1,
                ':chapter_2' => $kapitel2,
                ':chapter_3' => $kapitel3,
                ':title' => $title,
                ':rawObservation' => $rawObservation,
                ':deviationDescription' => $deviationDescription,
                ':rapport_ID' => $rapportId,
                ':priority' => $priority,
                ':atgarda_text' => $atgardaText
            ]);

            $avvikelseId = (int)$pdo->lastInsertId();

            $insertKoppling = $pdo->prepare("
                INSERT INTO sida_has_Avvikelse (sida_ID, Avvikelse_idAvvikelse)
                VALUES (:sida_ID, :avvikelse_ID)
            ");

            foreach ($selectedSidor as $sidaId) {
                $insertKoppling->execute([
                    ':sida_ID' => (int)$sidaId,
                    ':avvikelse_ID' => $avvikelseId
                ]);
            }

            $insertWcag = $pdo->prepare("
                INSERT INTO Avvikelse_has_WCAG (Avvikelse_idAvvikelse, WCAG_id)
                VALUES (:avvikelse_ID, :wcag_ID)
            ");

            foreach ($selectedWcag as $wcagId) {
                $insertWcag->execute([
                    ':avvikelse_ID' => $avvikelseId,
                    ':wcag_ID' => (int)$wcagId
                ]);
            }

            $pdo->commit();

            $success = 'Avvikelsen sparades korrekt.';

            $title = '';
            $kapitel1 = '';
            $kapitel2 = '';
            $kapitel3 = '';
            $rawObservation = '';
            $deviationDescription = '';
            $priority = '';
            $atgardaText = '';
            $selectedSidor = [];
            $selectedWcag = [];

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Fel vid sparning: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Skapa avvikelse</title>
</head>
<body>
    <h1>Skapa avvikelse</h1>

    <?php if ($errors): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <p><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="title">Titel</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>">

    <?php
    renderSelect('kapitel_1', getChapterOptions($chapters), $kapitel1);
    renderSelect('kapitel_2', getChapterOptions($chapters, $kapitel1), $kapitel2);
    renderSelect('kapitel_3', getChapterOptions($chapters, $kapitel1, $kapitel2), $kapitel3);
    ?>
        <label for="rawObservation">Raw observation</label>
        <textarea id="rawObservation" name="rawObservation"><?= htmlspecialchars($rawObservation) ?></textarea>

        <label for="deviationDescription">Avvikelsebeskrivning</label>
        <textarea id="deviationDescription" name="deviationDescription"><?= htmlspecialchars($deviationDescription) ?></textarea>

        <label for="atgarda_text">Åtgärdsförslag:</label>
        <textarea id="atgarda_text" name="atgarda_text"><?= htmlspecialchars($atgardaText) ?></textarea>

        <label for="priority">Prioritet</label>
        <select id="priority" name="priority">
            <option value="">Välj prioritet</option>
            <option value="Måste" <?= $priority === 'Måste' ? 'selected' : '' ?>>Måste</option>
            <option value="Bör" <?= $priority === 'Bör' ? 'selected' : '' ?>>Bör</option>
            <option value="Kan" <?= $priority === 'Kan' ? 'selected' : '' ?>>Kan</option>
        </select>

        <fieldset>
    <legend>WCAG-kriterier</legend>

    <?php foreach ($wcagList as $wcag): ?>
        <label>
            <input
                type="checkbox"
                name="wcag[]"
                value="<?= (int)$wcag['id'] ?>"
                <?= in_array((string)$wcag['id'], $selectedWcag, true) ? 'checked' : '' ?>
            >
            <?= htmlspecialchars($wcag['code']) ?> – 
            <?= htmlspecialchars($wcag['title']) ?> 
            (<?= htmlspecialchars($wcag['level']) ?>)
        </label>
    <?php endforeach; ?>
</fieldset>

        <fieldset>
            <legend>Koppla till sida/sidor</legend>

            <?php if (!$sidor): ?>
                <p>Det finns inga sidor kopplade till rapporten ännu.</p>
            <?php else: ?>
                <?php foreach ($sidor as $sida): ?>
                    <label>
                        <input
                            type="checkbox"
                            name="sidor[]"
                            value="<?= (int)$sida['ID'] ?>"
                            <?= in_array((string)$sida['ID'], $selectedSidor, true) ? 'checked' : '' ?>
                        >
                        <?= htmlspecialchars($sida['name']) ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </fieldset>

        <button type="submit" name="save_avvikelse" value="1">Spara avvikelse</button>
        <p>
        <a href="generate-report.php">Till avvikelselistan</a>
    </p>
    </form>
    <script id="chapters-config" type="application/json">
    <?= json_encode([
    'chapters' => $chapters,
    'selectedKapitel1' => $kapitel1,
    'selectedKapitel2' => $kapitel2,
    'selectedKapitel3' => $kapitel3,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<script src="../assets/js/chapters.js"></script>
</body>
</html>