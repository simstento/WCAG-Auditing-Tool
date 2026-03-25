<?php 
declare(strict_types=1);

function getChapterOptions(array $chapters, ?string $kapitel1 = null, ?string $kapitel2 = null): array
{
    // Nivå 1
    if ($kapitel1 === null) {
        return array_keys($chapters);
    }

    if (!isset($chapters[$kapitel1])) {
        return [];
    }

    $level2 = $chapters[$kapitel1];

    // Om vi bara vill hämta nivå 2
    if ($kapitel2 === null) {
        // Tvånivåstruktur: ['Ljudkontroll', 'Rörligt innehåll']
        if (array_is_list($level2)) {
            return $level2;
        }

        // Trenivåstruktur: ['Semantik' => [...], 'Länkar' => [...]]
        return array_keys($level2);
    }

    // Om vi vill hämta nivå 3
    if (!isset($level2[$kapitel2])) {
        return [];
    }

    return is_array($level2[$kapitel2]) ? $level2[$kapitel2] : [];
}

function chapterHasLevel3(array $chapters, string $kapitel1): bool
{
    if (!isset($chapters[$kapitel1])) {
        return false;
    }

    return !array_is_list($chapters[$kapitel1]);
}

function renderSelect(string $name, array $options, string $selectedValue = ''): void
{
    $label = match ($name) {
        'kapitel_1' => 'Kapitel 1',
        'kapitel_2' => 'Kapitel 2',
        'kapitel_3' => 'Kapitel 3',
        default => ucwords(str_replace('_', ' ', $name)),
    };

    echo '<div class="form-group">';
    echo '<label for="' . htmlspecialchars($name) . '">' . htmlspecialchars($label) . '</label>';
    echo '<select id="' . htmlspecialchars($name) . '" name="' . htmlspecialchars($name) . '">';
    echo '<option value="">Välj</option>';

    foreach ($options as $option) {
        $selected = ($selectedValue === $option) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>'
            . htmlspecialchars($option)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';
}