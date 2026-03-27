<?php
declare(strict_types=1);
$page_title = "Skapa avvikelse";
require __DIR__ . '/../includes/header.php';

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../includes/chapters.php';
require __DIR__ . '/../functions/functions.php';
require __DIR__ . '/../includes/chapters-wcag-map.php';



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
$isGlobal = '0';
$globalSection = '';

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
    $globalSection = trim($_POST['global_section'] ?? '');
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

    if ($isGlobal === '1') {
    if ($globalSection === '') {
        $errors[] = 'Välj om den globala avvikelsen ska placeras under Ramverk eller Navigering.';
    }

        if (!in_array($globalSection, ['Ramverk', 'Navigering'], true)) {
            $errors[] = 'Ogiltig global sektion.';
        }
    } else {
            if (empty($selectedSidor)) {
                $errors[] = 'Minst en sida måste väljas.';
            }
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
                atgarda_text,
                is_global,
                global_section
            ) VALUES (
                :chapter_1,
                :chapter_2,
                :chapter_3,
                :title,
                :rawObservation,
                :deviationDescription,
                :rapport_ID,
                :priority,
                :atgarda_text,
                :is_global,
                :global_section
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
                ':atgarda_text' => $atgardaText,
                ':is_global' => (int)$isGlobal,
                ':global_section' => $isGlobal === '1' ? $globalSection : null
            ]);

            $avvikelseId = (int)$pdo->lastInsertId();

            $insertKoppling = $pdo->prepare("
                INSERT INTO sida_has_Avvikelse (sida_ID, Avvikelse_idAvvikelse)
                VALUES (:sida_ID, :avvikelse_ID)
            ");

            if($isGlobal!=='1') {
                foreach ($selectedSidor as $sidaId) {
                    $insertKoppling->execute([
                        ':sida_ID' => (int)$sidaId,
                        ':avvikelse_ID' => $avvikelseId
                    ]);
                }
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
            $isGlobal = '0';
            $globalSection = '';

        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Fel vid sparning: ' . $e->getMessage();
        }
    }
}

?>
   <div class="form-wrapper">
        <div class="card">

            <h1>Skapa avvikelse</h1>

            <form method="post">

                <div class="form-group">
                    <label for="title">Titel</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>">
                </div>

                <?php
                renderSelect('kapitel_1', getChapterOptions($chapters), $kapitel1);
                renderSelect('kapitel_2', getChapterOptions($chapters, $kapitel1), $kapitel2);
                renderSelect('kapitel_3', getChapterOptions($chapters, $kapitel1, $kapitel2), $kapitel3);
                $chapterWcagMapIds = buildChapterWcagMapIds($chapterWcagMap, $wcagList);
                ?>

                <div class="form-group">
                    <label for="rawObservation">Raw observation</label>
                    <textarea id="rawObservation" name="rawObservation"><?= htmlspecialchars($rawObservation) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="deviationDescription">Avvikelsebeskrivning</label>
                    <textarea id="deviationDescription" name="deviationDescription"><?= htmlspecialchars($deviationDescription) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="atgarda_text">Åtgärdsförslag</label>
                    <textarea id="atgarda_text" name="atgarda_text"><?= htmlspecialchars($atgardaText) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="priority">Prioritet</label>
                    <select id="priority" name="priority">
                        <option value="">Välj prioritet</option>
                        <option value="Måste" <?= $priority === 'Måste' ? 'selected' : '' ?>>Måste</option>
                        <option value="Bör" <?= $priority === 'Bör' ? 'selected' : '' ?>>Bör</option>
                        <option value="Kan" <?= $priority === 'Kan' ? 'selected' : '' ?>>Kan</option>
                    </select>
                </div>

                <fieldset>
                    <legend>WCAG-kriterier</legend>
                    <div class="wcag-toolbar">
                        <button type="button" class="button secondary small" id="show-recommended-wcag">
                            Visa bara rekommenderade
                        </button>

                        <button type="button" class="button secondary small" id="show-all-wcag">
                            Visa alla WCAG
                        </button>
                    </div>
                <p id="wcag-empty-message" class="helper-text" hidden>
                    Det finns inga rekommenderade WCAG-kriterier för det valda kapitlet.
                </p>
                    <div class="checkbox-grid">
                        <?php foreach ($wcagList as $wcag): ?>
                        <label class="checkbox-item wcag-item" data-wcag-id="<?= (int)$wcag['id'] ?>">
                            <input
                                type="checkbox"
                                name="wcag[]"
                                value="<?= (int)$wcag['id'] ?>"
                                <?= in_array((string)$wcag['id'], $selectedWcag, true) ? 'checked' : '' ?>>
                            <span>
                                <?= htmlspecialchars($wcag['code']) ?> –
                                <?= htmlspecialchars($wcag['title']) ?>
                                (<?= htmlspecialchars($wcag['level']) ?>)
                            </span>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Placering i rapport</legend>

                    <div class="form-group">
                        <label for="is_global">Typ av avvikelse</label>
                        <select id="is_global" name="is_global">
                            <option value="0" <?= $isGlobal === '0' ? 'selected' : '' ?>>Sidspecifik</option>
                            <option value="1" <?= $isGlobal === '1' ? 'selected' : '' ?>>Global</option>
                        </select>
                    </div>

                    <div class="form-group" id="global-section-wrapper">
                        <label for="global_section">Global sektion</label>
                        <select id="global_section" name="global_section">
                            <option value="">Välj sektion</option>
                            <option value="Ramverk" <?= $globalSection === 'Ramverk' ? 'selected' : '' ?>>Ramverk</option>
                            <option value="Navigering" <?= $globalSection === 'Navigering' ? 'selected' : '' ?>>Navigering</option>
                        </select>
                    </div>
                </fieldset>

              <fieldset id="page-selection-wrapper">
                    <legend>Koppla till sida/sidor</legend>

                    <?php if (!$sidor): ?>
                        <p>Det finns inga sidor kopplade till rapporten ännu.</p>
                    <?php else: ?>
                        <div class="checkbox-grid">
                            <?php foreach ($sidor as $sida): ?>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="sidor[]" value="<?= (int)$sida['ID'] ?>">
                                    <?= htmlspecialchars($sida['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" name="save_avvikelse" value="1">
                        Spara avvikelse
                    </button>
                </div>

                <a class="back-link" href="lista-avvikelser.php?rapport_id=<?= (int)$rapportId ?>">
                    Till avvikelselistan
                </a>

            </form>
        </div>
    </div>
    <script id="chapters-config" type="application/json">
    <?= json_encode([
    'chapters' => $chapters,
    'selectedKapitel1' => $kapitel1,
    'selectedKapitel2' => $kapitel2,
    'selectedKapitel3' => $kapitel3,
    'chapterWcagMap' => $chapterWcagMapIds
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
<script src="../assets/js/chapters.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>