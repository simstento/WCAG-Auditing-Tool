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

function renderSelect(string $name, array $options, string $selectedValue = '', bool $autoSubmit = false)
{
    $onChange = $autoSubmit ? 'onchange="this.form.submit()"' : '';

    echo '<select name="' . htmlspecialchars($name) . '" ' . $onChange . '>';
    echo '<option value="">Välj</option>';

    foreach ($options as $option) {
        $selected = ($selectedValue === $option) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>'
            . htmlspecialchars($option)
            . '</option>';
    }

    echo '</select>';
}