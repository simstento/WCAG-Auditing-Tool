<?php
declare(strict_types=1);

function createAvvikelse(PDO $pdo, array $data): intdiv{
    $sql = "INSERT INTO avvikelse (
            kapitel_1,
            kapitel_2,
            kapitel_3,
            title,
            rawObservation,
            deviationDescription,
            rapport_ID,
            priority,
            atgarda_text
        ) VALUES (
            :kapitel_1,
            :kapitel_2,
            :kapitel_3,
            :title,
            :rawObservation,
            :deviationDescription,
            :rapport_ID,
            :priority,
            :atgarda_text
        )
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':kapitel_1' => $data['kapitel_1'],
        ':kapitel_2' => $data['kapitel_2'],
        ':kapitel_3' => $data['kapitel_3'],
        ':title' => $data['title'],
        ':rawObservation' => $data['rawObservation'],
        ':deviationDescription' => $data['deviationDescription'],
        ':rapport_ID' => $data['rapport_ID'],
        ':priority' => $data['priority'],
        ':atgarda_text' => $data['atgarda_text']
    ]);
    return (int)$pdo->lastInsertId();
    
}

function connectAvvikelseToSidor(PDO $pdo, int $avvikelseId, array $sidaIds): void
{
    $sql = "
        INSERT INTO sida_has_Avvikelse (sida_ID, Avvikelse_idAvvikelse)
        VALUES (:sida_ID, :avvikelse_ID)
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($sidaIds as $sidaId) {
        $stmt->execute([
            ':sida_ID' => (int)$sidaId,
            ':avvikelse_ID' => $avvikelseId,
        ]);
    }
}