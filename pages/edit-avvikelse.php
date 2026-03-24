<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

$avvikelseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($avvikelseId <= 0) {
    http_response_code(400);
    echo "<h1>Ogiltigt avvikelse-ID</h1>";
    exit;
}

/**
 * Hämta själva avvikelsen
 */
$avvikelseStmt = $pdo->prepare("
    SELECT *
    FROM Avvikelse
    WHERE idAvvikelse = :id
");
$avvikelseStmt->execute([
    ':id' => $avvikelseId
]);

$avvikelse = $avvikelseStmt->fetch();

if (!$avvikelse) {
    http_response_code(404);
    echo "<h1>Avvikelsen kunde inte hittas</h1>";
    exit;
}

$rapportId = (int)$avvikelse['rapport_ID'];

/**
 * Hämta sidor för rapporten
 */
$sidorStmt = $pdo->prepare("
    SELECT ID, name
    FROM sida
    WHERE rapport_ID = :rapport_ID
    ORDER BY name
");
$sidorStmt->execute([
    ':rapport_ID' => $rapportId
]);
$sidor = $sidorStmt->fetchAll();

/**
 * Hämta alla WCAG
 */
$wcagStmt = $pdo->query("
    SELECT id, code, title, level
    FROM WCAG
    ORDER BY code
");
$wcagList = $wcagStmt->fetchAll();

/**
 * Hämta kopplade sidor
 */
$koppladeSidorStmt = $pdo->prepare("
    SELECT sida_ID
    FROM sida_has_Avvikelse
    WHERE Avvikelse_idAvvikelse = :id
");
$koppladeSidorStmt->execute([
    ':id' => $avvikelseId
]);
$selectedSidor = array_map(
    static fn(array $row): string => (string)$row['sida_ID'],
    $koppladeSidorStmt->fetchAll()
);

/**
 * Hämta kopplade WCAG
 */
$koppladeWcagStmt = $pdo->prepare("
    SELECT WCAG_id
    FROM Avvikelse_has_WCAG
    WHERE Avvikelse_idAvvikelse = :id
");
$koppladeWcagStmt->execute([
    ':id' => $avvikelseId
]);
$selectedWcag = array_map(
    static fn(array $row): string => (string)$row['WCAG_id'],
    $koppladeWcagStmt->fetchAll()
);

/**
 * Förifyll formulärdata
 */
$title = $avvikelse['title'] ?? '';
$kapitel1 = $avvikelse['chapter_1'] ?? '';
$kapitel2 = $avvikelse['chapter_2'] ?? '';
$kapitel3 = $avvikelse['chapter_3'] ?? '';
$rawObservation = $avvikelse['rawObservation'] ?? '';
$deviationDescription = $avvikelse['deviationDescription'] ?? '';
$priority = $avvikelse['priority'] ?? '';
$atgardaText = $avvikelse['atgarda_text'] ?? '';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $kapitel1 = trim($_POST['kapitel_1'] ?? '');
    $kapitel2 = trim($_POST['kapitel_2'] ?? '');
    $kapitel3 = trim($_POST['kapitel_3'] ?? '');
    $rawObservation = trim($_POST['rawObservation'] ?? '');
    $deviationDescription = trim($_POST['deviationDescription'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $atgardaText = trim($_POST['atgarda_text'] ?? '');
    $selectedSidor = $_POST['sidor'] ?? [];
    $selectedWcag = $_POST['wcag'] ?? [];

    if ($title === '') {
        $errors[] = 'Titel måste anges.';
    }

    if ($rawObservation === '') {
        $errors[] = 'Raw observation måste anges.';
    }

    if ($deviationDescription === '') {
        $errors[] = 'Avvikelsebeskrivning måste anges.';
    }

    if ($atgardaText === '') {
        $errors[] = 'Åtgärda måste anges.';
    }

    if ($priority === '') {
        $errors[] = 'Prioritet måste väljas.';
    }

    if (empty($selectedSidor)) {
        $errors[] = 'Minst en sida måste väljas.';
    }

    if (empty($selectedWcag)) {
        $errors[] = 'Minst ett WCAG-kriterium måste väljas.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            /**
             * 1. Uppdatera Avvikelse
             */
            $updateStmt = $pdo->prepare("
                UPDATE Avvikelse
                SET
                    chapter_1 = :chapter_1,
                    chapter_2 = :chapter_2,
                    chapter_3 = :chapter_3,
                    title = :title,
                    rawObservation = :rawObservation,
                    deviationDescription = :deviationDescription,
                    priority = :priority,
                    atgarda_text = :atgarda_text
                WHERE idAvvikelse = :id
            ");

            $updateStmt->execute([
                ':chapter_1' => $kapitel1,
                ':chapter_2' => $kapitel2,
                ':chapter_3' => $kapitel3,
                ':title' => $title,
                ':rawObservation' => $rawObservation,
                ':deviationDescription' => $deviationDescription,
                ':priority' => $priority,
                ':atgarda_text' => $atgardaText,
                ':id' => $avvikelseId
            ]);

            /**
             * 2. Rensa och lägg in nya sidkopplingar
             */
            $deleteSidorStmt = $pdo->prepare("
                DELETE FROM sida_has_Avvikelse
                WHERE Avvikelse_idAvvikelse = :id
            ");
            $deleteSidorStmt->execute([
                ':id' => $avvikelseId
            ]);

            $insertSidorStmt = $pdo->prepare("
                INSERT INTO sida_has_Avvikelse (sida_ID, Avvikelse_idAvvikelse)
                VALUES (:sida_ID, :avvikelse_ID)
            ");

            foreach ($selectedSidor as $sidaId) {
                $insertSidorStmt->execute([
                    ':sida_ID' => (int)$sidaId,
                    ':avvikelse_ID' => $avvikelseId
                ]);
            }

            /**
             * 3. Rensa och lägg in nya WCAG-kopplingar
             */
            $deleteWcagStmt = $pdo->prepare("
                DELETE FROM Avvikelse_has_WCAG
                WHERE Avvikelse_idAvvikelse = :id
            ");
            $deleteWcagStmt->execute([
                ':id' => $avvikelseId
            ]);

            $insertWcagStmt = $pdo->prepare("
                INSERT INTO Avvikelse_has_WCAG (Avvikelse_idAvvikelse, WCAG_id)
                VALUES (:avvikelse_ID, :wcag_ID)
            ");

            foreach ($selectedWcag as $wcagId) {
                $insertWcagStmt->execute([
                    ':avvikelse_ID' => $avvikelseId,
                    ':wcag_ID' => (int)$wcagId
                ]);
            }

            $pdo->commit();
            $success = 'Avvikelsen uppdaterades korrekt.';

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Fel vid uppdatering: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Redigera avvikelse</title>
</head>
<body>
    <h1>Redigera avvikelse</h1>

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

        <label for="kapitel_1">Kapitel 1</label>
        <input type="text" id="kapitel_1" name="kapitel_1" value="<?= htmlspecialchars($kapitel1) ?>">

        <label for="kapitel_2">Kapitel 2</label>
        <input type="text" id="kapitel_2" name="kapitel_2" value="<?= htmlspecialchars($kapitel2) ?>">

        <label for="kapitel_3">Kapitel 3</label>
        <input type="text" id="kapitel_3" name="kapitel_3" value="<?= htmlspecialchars($kapitel3) ?>">

        <label for="rawObservation">Raw observation</label>
        <textarea id="rawObservation" name="rawObservation"><?= htmlspecialchars($rawObservation) ?></textarea>

        <label for="deviationDescription">Avvikelsebeskrivning</label>
        <textarea id="deviationDescription" name="deviationDescription"><?= htmlspecialchars($deviationDescription) ?></textarea>

        <label for="atgarda_text">Åtgärda</label>
        <textarea id="atgarda_text" name="atgarda_text"><?= htmlspecialchars($atgardaText) ?></textarea>

        <label for="priority">Prioritet</label>
        <select id="priority" name="priority">
            <option value="">Välj prioritet</option>
            <option value="Måste" <?= $priority === 'Måste' ? 'selected' : '' ?>>Måste</option>
            <option value="Bör" <?= $priority === 'Bör' ? 'selected' : '' ?>>Bör</option>
            <option value="Kan" <?= $priority === 'Kan' ? 'selected' : '' ?>>Kan</option>
        </select>

        <fieldset>
            <legend>Koppla till sida/sidor</legend>
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
        </fieldset>

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

        <button type="submit">Spara ändringar</button>
    </form>

    <p>
        <a href="lista-avvikelser.php">Tillbaka till avvikelselistan</a>
    </p>
</body>
</html>