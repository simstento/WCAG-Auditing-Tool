<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

$rapportId = 1; // tillfälligt hårdkodat för test

// Hämta sidor som tillhör rapporten
$stmt = $pdo->prepare("SELECT ID, name FROM sida WHERE rapport_ID = :rapport_ID ORDER BY name");
$stmt->execute([':rapport_ID' => $rapportId]);
$sidor = $stmt->fetchAll();

$errors = [];
$success = '';

$title = '';
$kapitel1 = '';
$kapitel2 = '';
$kapitel3 = '';
$rawObservation = '';
$deviationDescription = '';
$priority = '';
$selectedSidor = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $kapitel1 = trim($_POST['kapitel_1'] ?? '');
    $kapitel2 = trim($_POST['kapitel_2'] ?? '');
    $kapitel3 = trim($_POST['kapitel_3'] ?? '');
    $rawObservation = trim($_POST['rawObservation'] ?? '');
    $deviationDescription = trim($_POST['deviationDescription'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $selectedSidor = $_POST['sidor'] ?? [];

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

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Spara avvikelsen
            $insertAvvikelse = $pdo->prepare("
                INSERT INTO Avvikelse (
                    chapter_1,
                    chapter_2,
                    chapter_3,
                    title,
                    rawObservation,
                    deviationDescription,
                    rapport_ID,
                    priority
                ) VALUES (
                    :chapter_1,
                    :chapter_2,
                    :chapter_3,
                    :title,
                    :rawObservation,
                    :deviationDescription,
                    :rapport_ID,
                    :priority
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
                ':priority' => $priority
            ]);

            $avvikelseId = (int)$pdo->lastInsertId();

            // 2. Koppla avvikelsen till valda sidor
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

            $pdo->commit();

            $success = 'Avvikelsen sparades korrekt.';

            // töm formuläret efter lyckad sparning
            $title = '';
            $kapitel1 = '';
            $kapitel2 = '';
            $kapitel3 = '';
            $rawObservation = '';
            $deviationDescription = '';
            $priority = '';
            $selectedSidor = [];

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

        <label for="priority">Prioritet</label>
        <select id="priority" name="priority">
            <option value="">Välj prioritet</option>
            <option value="Måste" <?= $priority === 'Måste' ? 'selected' : '' ?>>Måste</option>
            <option value="Bör" <?= $priority === 'Bör' ? 'selected' : '' ?>>Bör</option>
            <option value="Kan" <?= $priority === 'Kan' ? 'selected' : '' ?>>Kan</option>
        </select>

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

        <button type="submit">Spara avvikelse</button>
    </form>
</body>
</html>