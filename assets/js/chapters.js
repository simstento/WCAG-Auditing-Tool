document.addEventListener('DOMContentLoaded', () => {
    console.log('chapters.js laddad');

    const configElement = document.getElementById('chapters-config');

    if (!configElement) {
        console.error('chapters-config saknas');
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
        console.error('Ett eller flera kapitel-fält saknas i DOM');
        return;
    }

    function clearSelect(selectElement) {
        selectElement.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Välj';
        selectElement.appendChild(defaultOption);
    }

    function populateSelect(selectElement, options, selectedValue = '') {
        clearSelect(selectElement);

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

    function isAssocObject(value) {
        return value && typeof value === 'object' && !Array.isArray(value);
    }

    function updateKapitel2(selectedValue = '') {
        const kapitel1 = kapitel1Select.value;

        clearSelect(kapitel2Select);
        clearSelect(kapitel3Select);

        if (!kapitel1 || !chapters[kapitel1]) {
            kapitel3Select.disabled = true;
            return;
        }

        const level2 = chapters[kapitel1];

        if (Array.isArray(level2)) {
            populateSelect(kapitel2Select, level2, selectedValue);
            kapitel3Select.disabled = true;
            return;
        }

        if (isAssocObject(level2)) {
            populateSelect(kapitel2Select, Object.keys(level2), selectedValue);
            kapitel3Select.disabled = false;
        }
    }

    function updateKapitel3(selectedValue = '') {
        const kapitel1 = kapitel1Select.value;
        const kapitel2 = kapitel2Select.value;

        clearSelect(kapitel3Select);

        if (!kapitel1 || !kapitel2 || !chapters[kapitel1]) {
            kapitel3Select.disabled = true;
            return;
        }

        const level2 = chapters[kapitel1];

        if (Array.isArray(level2)) {
            kapitel3Select.disabled = true;
            return;
        }

        if (level2[kapitel2] && Array.isArray(level2[kapitel2])) {
            populateSelect(kapitel3Select, level2[kapitel2], selectedValue);
            kapitel3Select.disabled = false;
        } else {
            kapitel3Select.disabled = true;
        }
    }

    kapitel1Select.addEventListener('change', () => {
        updateKapitel2();
        updateKapitel3();
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