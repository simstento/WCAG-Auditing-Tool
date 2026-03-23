<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../functions/avvikelse.php';

$rapportId = 1; // exempel, senare hämtar du dynamiskt

// Hämta sidor för vald rapport
$stmt = $pdo->prepare("SELECT id, name FROM sida WHERE rapport_ID = :rapport_ID ORDER BY name");
$stmt->execute([':rapport_ID' => $rapportId]);
$sidor = $stmt->fetchAll();

$errors = [];

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
        $errors[] = 'Rå observation måste anges.';
    }

    if ($deviationDescription === '') {
        $errors[] = 'Avvikelsebeskrivning måste anges.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();

        try {
            $avvikelseId = createAvvikelse($pdo, [
                'kapitel_1' => $kapitel1,
                'kapitel_2' => $kapitel2,
                'kapitel_3' => $kapitel3,
                'title' => $title,
                'rawObservation' => $rawObservation,
                'deviationDescription' => $deviationDescription,
                'rapport_ID' => $rapportId,
                'priority' => $priority,
            ]);

            connectAvvikelseToSidor($pdo, $avvikelseId, $selectedSidor);

            $pdo->commit();

            header('Location: list-avvikelser.php?rapport_id=' . $rapportId);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Något gick fel: ' . $e->getMessage();
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

    <form method="post">
        <label for="title">Titel</label>
        <input type="text" id="title" name="title">

        <label for="kapitel_1">Kapitel 1</label>
        <input type="text" id="kapitel_1" name="kapitel_1">

        <label for="kapitel_2">Kapitel 2</label>
        <input type="text" id="kapitel_2" name="kapitel_2">

        <label for="kapitel_3">Kapitel 3</label>
        <input type="text" id="kapitel_3" name="kapitel_3">

        <label for="rawObservation">Rå observation</label>
        <textarea id="rawObservation" name="rawObservation"></textarea>

        <label for="deviationDescription">Avvikelsebeskrivning</label>
        <textarea id="deviationDescription" name="deviationDescription"></textarea>

        <label for="priority">Prioritet</label>
        <select id="priority" name="priority">
            <option value="Måste">Måste</option>
            <option value="Bör">Bör</option>
            <option value="Kan">Kan</option>
        </select>

        <fieldset>
            <legend>Koppla till sidor</legend>
            <?php foreach ($sidor as $sida): ?>
                <label>
                    <input type="checkbox" name="sidor[]" value="<?= (int)$sida['id'] ?>">
                    <?= htmlspecialchars($sida['name']) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <button type="submit">Spara avvikelse</button>
    </form>
</body>
</html>