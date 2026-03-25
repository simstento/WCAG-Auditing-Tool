<?php
declare(strict_types=1);

require __DIR__ . '/../src/db.php';

$errors = [];

$title = '';
$client = '';
$siteName = '';
$reviewDate = '';
$status = 'utkast';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $client = trim($_POST['client'] ?? '');
    $siteName = trim($_POST['siteName'] ?? '');
    $reviewDate = trim($_POST['reviewDate'] ?? '');
    $status = trim($_POST['status'] ?? 'utkast');

    if ($title === '') {
        $errors[] = 'Titel måste anges.';
    }

    if ($client === '') {
        $errors[] = 'Kund måste anges.';
    }

    if ($siteName === '') {
        $errors[] = 'Webbplatsnamn måste anges.';
    }

    if ($reviewDate === '') {
        $errors[] = 'Granskningsdatum måste anges.';
    }

    $allowedStatuses = ['utkast', 'pågående', 'klar'];

    if (!in_array($status, $allowedStatuses, true)) {
        $errors[] = 'Ogiltig status.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO rapport (title, client, siteName, reviewDate, status)
                VALUES (:title, :client, :siteName, :reviewDate, :status)
            ");

            $stmt->execute([
                ':title' => $title,
                ':client' => $client,
                ':siteName' => $siteName,
                ':reviewDate' => $reviewDate,
                ':status' => $status
            ]);

            $rapportId = (int)$pdo->lastInsertId();

            header('Location: view-rapport.php?id=' . $rapportId);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Det gick inte att spara rapporten: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Skapa rapport</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        form {
            display: grid;
            gap: 16px;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
        }

        input,
        select {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            font-size: 16px;
        }

        .errors {
            background: #ffeaea;
            border: 1px solid #cc0000;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        button {
            padding: 10px 16px;
            font-size: 16px;
            cursor: pointer;
        }

        a {
            text-decoration: none;
        }
    </style>
</head>
<body>

    <h1>Skapa rapport</h1>

    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <div>
            <label for="title">Titel</label>
            <input
                type="text"
                id="title"
                name="title"
                value="<?= htmlspecialchars($title) ?>"
                required
            >
        </div>

        <div>
            <label for="client">Kund</label>
            <input type="text" id="client" name="client" value="<?= htmlspecialchars($client) ?>" required>
        </div>

        <div>
            <label for="siteName">Webbplatsnamn</label>
            <input type="text" id="siteName" name="siteName" value="<?= htmlspecialchars($siteName) ?>" required>
        </div>

        <div>
            <label for="reviewDate">Granskningsdatum</label>
            <input type="date" id="reviewDate" name="reviewDate" value="<?= htmlspecialchars($reviewDate) ?>" required>
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="utkast" <?= $status === 'utkast' ? 'selected' : '' ?>>utkast</option>
                <option value="pågående" <?= $status === 'pågående' ? 'selected' : '' ?>>pågående</option>
                <option value="klar" <?= $status === 'klar' ? 'selected' : '' ?>>klar</option>
            </select>
        </div>

        <div class="actions">
            <button type="submit">Spara rapport</button>
            <a href="list-rapporter.php">Avbryt</a>
        </div>
    </form>

</body>
</html>