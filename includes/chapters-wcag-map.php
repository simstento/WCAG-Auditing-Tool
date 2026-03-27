<?php

$chapterWcagMap = [
    'Störande|Placeholdertext|Ljudkontroll' => ['1.4.2'],
    'Störande|Placeholdertext|Rörligt innehåll' => ['2.2.2'],
    'Störande|Placeholdertext|Flimmer' => ['2.3.1'],
    'Innehåll|Placeholdertext|Sidtitel' => ['2.4.2'],
    'Innehåll|Placeholdertext|Sidans språk' => ['3.1.2'],
    'Innehåll|Placeholdertext|Automatisk omladdning' => ['2.2.1'],
    'Innehåll|Semantik|Landmärken' => ['2.4.1', '1.3.1'],
    'Innehåll|Semantik|Rubriker' => ['1.3.1'],
    'Innehåll|Semantik|Listor' => ['1.3.1'],
    'Innehåll|Semantik|Tabeller' => ['1.3.1'],
    'Innehåll|Länkar|Syfte för länkar' => ['2.4.4'],
    'Innehåll|Länkar|Färganvändning för länkar' => ['1.4.1'],
    'Innehåll|Länkar|Ikon-/symbollänkar' => ['1.3.3'],
    'Innehåll|Text|Språkändringar' => ['3.1.2'],
    'Innehåll|Text|Rubriker' => ['2.4.6'],
    'Innehåll|Text|Kontrast för text' => ['1.4.3'],
    'Innehåll|Text|Sensoriska hänvisningar' => ['1.3.3'],
    'Tangentbord|Fokus|Fokusordning' => ['2.4.3'],
    'Tangentbord|Fokus|Synlig fokusmarkering' => ['2.4.7'],
    'Tangentbord|Fokus|Kontrast för fokusmarkering' => ['1.4.11'],
    'Tangentbord|Fokus|Kontextförändring vid fokus' => ['3.2.1'],
    'Tangentbord|Fokus|Uppdykande innehåll vid fokus' => ['3.2.2'],
    'Tangentbord|Hanterbart|Skipplänkar' => ['2.4.1'],
    'Tangentbord|Hanterbart|Hanterbart med tangentbord' => ['2.1.1'],
    'Tangentbord|Hanterbart|Ingen tangentbordsfälla' => ['2.1.2'],
    'Layout|Flexibel layout' => ['1.4.10'],
    'Layout|Utöka textavstånd' => ['1.4.12'],
    'Layout|Förstoring' => ['1.4.4'],
    'Layout|Användning av färg' => ['1.4.1'],
    'Generella krav|Statusmeddelanden' => ['4.1.3'],
    'Generella krav|Tidsgränser' => ['2.2.1'],
    'Generella krav|Innehållets ordning' => ['1.3.2'],
    'Generella krav| Konsekvent navigering' => ['3.2.3'],
    'Generella krav|Konsekvent benämning' => ['3.2.4'],
    'Generella krav|Flera sätt att hitta sidan' => ['2.4.5'],
    'Generella krav|Avbryta klick' => ['2.5.2'],
    'Generella krav|Användarinställningar' => ['11.7'],
    'Avvikande sidor|Förhindra allvarliga konsekvenser' => ['3.3.4'],
    'Avvikande sidor|En-knapps snabbtangenter' => ['2.1.4'],
    'Avvikande sidor|Aktivering av tillgänglighetsfunktioner' => ['5.2'],
    'Avvikande sidor|Biometri' => ['5.3'],
    'Avvikande sidor|Bevara tillgänglighet vid omvandling' => ['5.4'],
    'Bilder|Placeholdertext|Kontrast i grafik' => ['1.4.1'],
    'Bilder|Placeholdertext|Bilder av text' => ['1.4.5'],
    'Bilder|Placeholdertext|Kontrast för bilder av text' => ['1.4.3'],
    'Bilder|Textalternativ' => ['1.1.1'],
    'Bilder|Textalternativ|Textalternativ för bilder' => ['1.1.1'],
    'Bilder|Textalternativ|Kontrast för textalternativ' => ['1.4.3'],
    'Bilder|Textalternativ|Språk för textalternativ' => ['3.1.2'],
    'Användargränssnitt|Ledtexter|Ledtexter, instruktioner' => ['3.3.2'],
    'Användargränssnitt|Ledtexter|Kopplade ledtexter' => ['1.3.1'],
    'Användargränssnitt|Ledtexter|Begrippsliga ledtexter' => ['2.4.6'],
    'Användargränssnitt|Interaktiva komponenter|Kopplade fält' => ['1.3.1'],
    'Användargränssnitt|Interaktiva komponenter|Namn, roll, värde' => ['4.1.2'],
    'Användargränssnitt|Interaktiva komponenter|Etiketter i namn' => ['2.5.3'],
    'Användargränssnitt|Interaktiva komponenter|Ikon-/symbolknappar' => ['1.3.3'],
    'Användargränssnitt|Interaktiva komponenter|Kontrast för komponenter' => ['1.4.11'],
    'Användargränssnitt|Beteende|Uppdykande innehåll vid hovring' => ['1.4.13'],
    'Användargränssnitt|Beteende|Kontextförändring vid inmatning' => ['3.2.2'],
    'Användargränssnitt|Felmeddelanden|Felmeddelanden' => ['3.3.1'],
    'Användargränssnitt|Felmeddelanden|Färganvändning för felmeddelanden' => ['1.4.1'],
    'Användargränssnitt|Felmeddelanden|Korrigeringsförslag' => ['3.3.3'],
    'Ljud och video|Placeholdertext|Textalternativ för tidsberoende medier' => ['1.1.1'],
    'Ljud och video|Placeholdertext|Alternativ för ljudklipp' => ['1.2.1'],
    'Ljud och video|Placeholdertext|Alternativ för animeringar och filmer' => ['1.2.1'],
    'Ljud och video|Placeholdertext|Reglage' => ['7.3'],
    'Ljud och video|Undertexter|Undertexter' => ['1.2.2'],
    'Ljud och video|Syntolkning|Syntolkning' => ['1.2.5'],
    'Ljud och video|Syntolkning|Syntolkning eller transkription' => ['1.2.3'],
];

function buildChapterWcagMapIds(array $chapterWcagMap, array $wcagList): array
{
    $wcagCodeToId = array_column($wcagList, 'id', 'code');

    $result = [];

    foreach ($chapterWcagMap as $chapterKey => $wcagCodes) {
        $result[$chapterKey] = array_values(
            array_filter(
                array_map(fn($code) => $wcagCodeToId[$code] ?? null, $wcagCodes)
            )
        );
    }

    return $result;
}