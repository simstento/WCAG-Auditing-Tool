<?php 
declare(strict_types=1);

function getChapterOptions(array $chapters, ?string $kapitel1 = null, ?string $kapitel2 = null): array
{
    // nivå 1
    if ($kapitel1 === null) {
        return array_keys($chapters);
    }

    // nivå 2
    if ($kapitel2 === null) {
        return isset($chapters[$kapitel1])
            ? array_keys($chapters[$kapitel1])
            : [];
    }

    // nivå 3
    return $chapters[$kapitel1][$kapitel2] ?? [];
}

?>