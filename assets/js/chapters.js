document.addEventListener('DOMContentLoaded', () => {
    const configElement = document.getElementById('chapters-config');

    if (!configElement) {
        return;
    }

    const config = JSON.parse(configElement.textContent);

    const chapters = config.chapters || {};
    const selectedKapitel1 = config.selectedKapitel1 || '';
    const selectedKapitel2 = config.selectedKapitel2 || '';
    const selectedKapitel3 = config.selectedKapitel3 || '';

    const kapitel1Select = document.getElementById('kapitel_1');
    const kapitel2Select = document.getElementById('kapitel_2');
    const kapitel3Select = document.getElementById('kapitel_3');

    if (!kapitel1Select || !kapitel2Select || !kapitel3Select) {
        return;
    }

    function clearSelect(selectElement, placeholder = 'Välj') {
        selectElement.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = placeholder;
        selectElement.appendChild(defaultOption);
    }

    function populateSelect(selectElement, options, selectedValue = '', placeholder = 'Välj') {
        clearSelect(selectElement, placeholder);

        options.forEach((optionValue) => {
            const option = document.createElement('option');
            option.value = optionValue;
            option.textContent = optionValue;

            if (optionValue === selectedValue) {
                option.selected = true;
            }

            selectElement.appendChild(option);
        });
    }

    function getChapter2Options(kapitel1) {
        if (!kapitel1 || !chapters[kapitel1]) {
            return [];
        }

        return Object.keys(chapters[kapitel1]);
    }

    function getChapter3Options(kapitel1, kapitel2) {
        if (!kapitel1 || !kapitel2) {
            return [];
        }

        if (!chapters[kapitel1] || !chapters[kapitel1][kapitel2]) {
            return [];
        }

        return chapters[kapitel1][kapitel2];
    }

    function updateKapitel2(selectedValue = '') {
        const kapitel1 = kapitel1Select.value;
        const kapitel2Options = getChapter2Options(kapitel1);

        populateSelect(kapitel2Select, kapitel2Options, selectedValue);
    }

    function updateKapitel3(selectedValue = '') {
        const kapitel1 = kapitel1Select.value;
        const kapitel2 = kapitel2Select.value;
        const kapitel3Options = getChapter3Options(kapitel1, kapitel2);

        populateSelect(kapitel3Select, kapitel3Options, selectedValue);
    }

    function resetKapitel3() {
        clearSelect(kapitel3Select);
    }

    kapitel1Select.addEventListener('change', () => {
        updateKapitel2();
        resetKapitel3();
    });

    kapitel2Select.addEventListener('change', () => {
        updateKapitel3();
    });

    if (selectedKapitel1) {
        kapitel1Select.value = selectedKapitel1;
    }

    updateKapitel2(selectedKapitel2);
    updateKapitel3(selectedKapitel3);
});