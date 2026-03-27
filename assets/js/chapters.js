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

        if (highlightSuggestWcagGlobally) {
        highlightSuggestWcagGlobally();
        }
    });

    kapitel2Select.addEventListener('change', () => {
        updateKapitel3();
        if (highlightSuggestWcagGlobally) {
        highlightSuggestWcagGlobally();
        }
    });

    if (selectedKapitel1) {
        kapitel1Select.value = selectedKapitel1;
    }

    updateKapitel2(selectedKapitel2);
    updateKapitel3(selectedKapitel3);

    if (highlightSuggestWcagGlobally) {
        highlightSuggestWcagGlobally();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const rowsContainer = document.getElementById('page-rows');
    const addRowButton = document.getElementById('add-page-row');

    if (!rowsContainer || !addRowButton) {
        return;
    }

    function addPageRow() {
        const currentRows = rowsContainer.querySelectorAll('.page-row-card');
        const index = currentRows.length;

        const row = document.createElement('div');
        row.className = 'page-row-card';

        row.innerHTML = `
            <h2>Rad ${index + 1}</h2>

            <div class="form-group">
                <label for="page_name_${index}">Sidnamn</label>
                <input
                    type="text"
                    id="page_name_${index}"
                    name="page_name[]"
                >
            </div>

            <div class="form-group">
                <label for="page_url_${index}">URL</label>
                <input
                    type="text"
                    id="page_url_${index}"
                    name="page_url[]"
                    placeholder="www.exempelsida.se/sida"
                >
            </div>
        `;

        rowsContainer.appendChild(row);
    }

    addRowButton.addEventListener('click', addPageRow);
});

let highlightSuggestWcagGlobally = null;

function initWcagHighlighting() {
    const configEl = document.getElementById('chapters-config');
    if (!configEl) return;

    const config = JSON.parse(configEl.textContent);
    const kapitel1El = document.getElementById('kapitel_1');
    const kapitel2El = document.getElementById('kapitel_2');
    const kapitel3El = document.getElementById('kapitel_3');
    const chapterWcagMap = config.chapterWcagMap || {};

    const showRecommendedButton = document.getElementById('show-recommended-wcag');
    const showAllButton = document.getElementById('show-all-wcag');
    const emptyMessage = document.getElementById('wcag-empty-message');

    let currentSuggestedIds = [];

    function buildChapterKey() {
        const kap1 = kapitel1El?.value?.trim() || '';
        const kap2 = kapitel2El?.value?.trim() || '';
        const kap3 = kapitel3El?.value?.trim() || '';

        if (!kap1 || !kap2) {
            return '';
        }

        return kap3 ? `${kap1}|${kap2}|${kap3}` : `${kap1}|${kap2}`;
    }

    function getAllWcagItems() {
        return document.querySelectorAll('.wcag-item');
    }

    function clearSuggestedWcag() {
    document.querySelectorAll('.wcag-item').forEach(item => {
        item.classList.remove('is-suggested');
        item.classList.remove('is-hidden');
    });
}

    function applyRecommendedView() {
        const allItems = getAllWcagItems();

        allItems.forEach(item => {
            const wcagId = Number(item.dataset.wcagId);
            const isSuggested = currentSuggestedIds.includes(wcagId);

            item.classList.toggle('is-suggested', isSuggested);
            item.classList.toggle('is-hidden', !isSuggested);
        });

        if (emptyMessage) {
            emptyMessage.hidden = currentSuggestedIds.length > 0;
        }
    }

    function applyAllView() {
        const allItems = getAllWcagItems();

        allItems.forEach(item => {
            const wcagId = Number(item.dataset.wcagId);
            const isSuggested = currentSuggestedIds.includes(wcagId);

            item.classList.remove('is-hidden');
            item.classList.toggle('is-suggested', isSuggested);
        });
    }
    
    function highlightSuggestedWcag() {
        clearSuggestedWcag();

        const chapterKey = buildChapterKey();

        const placeholder = document.getElementById('wcag-placeholder');
        const allItems = getAllWcagItems();

        // Inget kapitel är valt
        if (!chapterKey) {
            currentSuggestedIds = [];

            // Dölj alla WCAG-objekt och visa placeholder
            allItems.forEach(item => {
                item.classList.add('is-hidden');
                item.classList.remove('is-suggested');
            });

            if (placeholder) {
                placeholder.hidden = false;
            }

            // Kapitel är valt, dölj placeholder
            currentSuggestedIds = chapterWcagMap[chapterKey] || [];

            if (placeholder) {
                placeholder.hidden = true;
            }
            applyRecommendedView();
            return;
        }
        currentSuggestedIds = chapterWcagMap[chapterKey] || [];
        applyRecommendedView();
    }

    highlightSuggestWcagGlobally = highlightSuggestedWcag;

    function attachEventListeners() {
        [kapitel1El, kapitel2El, kapitel3El].forEach(element => {
            if (element) {
                element.addEventListener('change', highlightSuggestedWcag);
            }
        });
    }

    attachEventListeners();
    highlightSuggestedWcag();
}
document.addEventListener('DOMContentLoaded', initWcagHighlighting);


function initGlobalFieldToggle() {
    const isGlobalSelect = document.getElementById('is_global');
    const globalSectionWrapper = document.getElementById('global-section-wrapper');
    const pageSelectionWrapper = document.getElementById('page-selection-wrapper');

    if (!isGlobalSelect) return;

    function toggleGlobalFields() {
        const isGlobal = isGlobalSelect.value === '1';

        if (globalSectionWrapper) {
            globalSectionWrapper.style.display = isGlobal ? 'block' : 'none';
        }

        if (pageSelectionWrapper) {
            pageSelectionWrapper.style.display = isGlobal ? 'none' : 'block';
        }
    }

    isGlobalSelect.addEventListener('change', toggleGlobalFields);
    toggleGlobalFields();
}

document.addEventListener('DOMContentLoaded', initGlobalFieldToggle);
