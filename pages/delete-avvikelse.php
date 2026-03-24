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
 * Hämta rapport_ID först så vi kan skicka användaren tillbaka rätt
 */
$stmt = $pdo->prepare("
    SELECT rapport_ID, title
    FROM Avvikelse
    WHERE idAvvikelse = :id
");
$stmt->execute([
    ':id' => $avvikelseId
]);

$avvikelse = $stmt->fetch();

if (!$avvikelse) {
    http_response_code(404);
    echo "<h1>Avvikelsen kunde inte hittas</h1>";
    exit;
}

$rapportId = (int)$avvikelse['rapport_ID'];
$title = $avvikelse['title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';

    if ($confirm === 'yes') {
        $deleteStmt = $pdo->prepare("
            DELETE FROM Avvikelse
            WHERE idAvvikelse = :id
        ");
        $deleteStmt->execute([
            ':id' => $avvikelseId
        ]);

        header('Location: lista-avvikelser.php?rapport_id=' . $rapportId);
        exit;
    }

    if ($confirm === 'no') {
        header('Location: lista-avvikelser.php?rapport_id=' . $rapportId);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Ta bort avvikelse</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 2rem;
            line-height: 1.5;
        }

        .box {
            max-width: 700px;
            padding: 1.5rem;
            border: 1px solid #ccc;
            border-radius: 0.5rem;
            background: #fafafa;
        }

        h1 {
            margin-top: 0;
        }

        .button-row {
            margin-top: 1.5rem;
            display: flex;
            gap: 0.75rem;
        }

        button {
            padding: 0.7rem 1rem;
            border: none;
            border-radius: 0.35rem;
            cursor: pointer;
            font-size: 1rem;
        }

        .danger {
            background: #b00020;
            color: white;
        }

        .danger:hover {
            background: #8c0019;
        }

        .secondary {
            background: #ddd;
            color: #111;
        }

        .secondary:hover {
            background: #c8c8c8;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Ta bort avvikelse</h1>

        <p>Är du säker på att du vill ta bort denna avvikelse?</p>

        <p><strong><?= htmlspecialchars($title) ?></strong></p>

        <p>Detta går inte att ångra.</p>

        <form method="post">
            <div class="button-row">
                <button class="danger" type="submit" name="confirm" value="yes">Ja, ta bort</button>
                <button class="secondary" type="submit" name="confirm" value="no">Nej, avbryt</button>
            </div>
        </form>
    </div>
</body>
</html>