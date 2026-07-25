// assets/js/harvest.js
let rowIdx = 1;

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('user-search');
    const suggestionsList = document.getElementById('suggestions-list');
    const hiddenIdInput = document.getElementById('selected-user-id');
    const selectionDisplay = document.getElementById('selection-display');
    const confirmedName = document.getElementById('confirmed-name');
    const quickCreateBtn = document.getElementById('btn-quick-create');
    const printForm = document.getElementById('print-form');

    // Modal buttons
    const openModalBtn = document.getElementById('btn-open-modal');
    const closeModalBtn = document.getElementById('btn-close-modal');
    const selectEvent = document.getElementById('current_event_select');

    // Rows management buttons
    const addRowBtn = document.getElementById('btn-add-row');
    const productsContainer = document.getElementById('products_container');

    // --- 1. MODAL CONTROLS ---
    if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            document.getElementById('eventModal').style.display = 'flex';
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            document.getElementById('eventModal').style.display = 'none';
        });
    }

    if (selectEvent && selectEvent.options.length <= 1) {
        document.getElementById('eventModal').style.display = 'flex';
    }

    // --- 2. DYNAMIC ROWS OPERATIONS ---
    if (addRowBtn) {
        addRowBtn.addEventListener('click', function () {
            const div = document.createElement('div');
            div.className = 'product-row';
            div.innerHTML = `
                <input type="text" name="products[${rowIdx}][name]" placeholder="Anaran'ny vokatra" required>
                <input type="number" name="products[${rowIdx}][qty]" class="qty-input" placeholder="Isa" min="1" required>
                <input type="number" step="0.01" name="products[${rowIdx}][price]" class="price-input" placeholder="Vidiny tsirairay" required>
                <button type="button" class="btn-remove-row" style="background: #dc3545;">X</button>
            `;
            productsContainer.appendChild(div);
            rowIdx++;
        });
    }

    // Event delegation to catch clicks on dynamically created delete buttons
    if (productsContainer) {
        productsContainer.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('btn-remove-row')) {
                const rows = productsContainer.querySelectorAll('.product-row');
                if (rows.length > 1) {
                    e.target.parentElement.remove();
                }
            }
        });
    }

    // --- 3. PRINT POPUP HOOK ---
    if (printForm) {
        printForm.addEventListener('submit', function () {
            const width = window.screen.width - (window.screen.width / 4);
            const height = window.screen.height - (window.screen.height / 4);
            const left = (window.screen.width / 2) - (width / 2);
            const top = (window.screen.height / 2) - (height / 2);

            window.open('', 'print_popup', `width=${width},height=${height},top=${top},left=${left},status=no,toolbar=no,menubar=no,scrollbars=yes`);
        });
    }

    // --- 4. USER LOOKUP ENGINE ---
    function selectUser(id, name) {
        searchInput.value = name;
        hiddenIdInput.value = id;

        confirmedName.textContent = `${name} (ID: ${id})`;
        selectionDisplay.style.display = 'block';
        suggestionsList.style.display = 'none';
        quickCreateBtn.style.display = 'none';
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();

            if (query.length < 2) {
                suggestionsList.style.display = 'none';
                quickCreateBtn.style.display = 'none';
                return;
            }

            quickCreateBtn.style.display = 'block';

            fetch(`mpivavaka/search?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    suggestionsList.innerHTML = '';

                    if (data.length === 0) {
                        suggestionsList.innerHTML = '<div class="suggestion-empty">Aucun résultat trouvé. Cliquez sur Créer.</div>';
                        suggestionsList.style.display = 'block';
                        return;
                    }

                    data.forEach(user => {
                        const row = document.createElement('div');
                        row.className = 'suggestion-item';
                        row.textContent = user.name;

                        if (user.address) {
                            row.textContent += ' - ' + user.address;
                        }

                        row.addEventListener('click', function () {
                            selectUser(user.id, user.name);
                        });

                        suggestionsList.appendChild(row);
                    });

                    suggestionsList.style.display = 'block';
                })
                .catch(error => {
                    console.error('An error occurred during dataset mapping retrieval operations:', error);
                });
        });
    }

    // --- 5. DONOR CREATION EVENT ---
    if (quickCreateBtn) {
        quickCreateBtn.addEventListener('click', function () {
            const nameToCreate = searchInput.value.trim();
            if (!nameToCreate) return;

            const formData = new FormData();
            formData.append('name', nameToCreate);

            fetch('/mpivavaka/create', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        selectUser(result.id, result.name);
                        appendDonorToSidebar(result.id, result.name);
                        showNotification(`Mpivavaka créé avec succès ! ID : ${result.id}`, 'success');
                    } else {
                        showNotification(`Erreur lors de la création : ${result.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('An error occurred during user creation process:', error);
                });
        });
    }

    document.addEventListener('click', function (e) {
        if (e.target !== searchInput && e.target !== suggestionsList && e.target !== quickCreateBtn) {
            suggestionsList.style.display = 'none';
        }
    });
});

// --- 6. SIDEBAR UPDATES & LIVE CHANNELS ---
function appendDonorToSidebar(id, name) {
    const listContainer = document.getElementById('new-subscribers-list');
    if (!listContainer) return;

    const emptyPlaceholder = listContainer.querySelector('li[style*="italic"]');
    if (emptyPlaceholder) {
        emptyPlaceholder.remove();
    }

    const donorHtml = `
        <li style="padding: 10px 0; border-bottom: 1px solid #eee; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; font-weight: bold;">
                <span style="font-family: monospace; color: #3498db;">${id}</span>
            </div>
            <div style="color: #7f8c8d; font-size: 12px; margin-top: 2px; text-transform: uppercase;">
                ${name}
            </div>
        </li>
    `;

    listContainer.insertAdjacentHTML('afterbegin', donorHtml);

    while (listContainer.children.length > 5) {
        listContainer.lastElementChild.remove();
    }
}

const harvestChannel = new BroadcastChannel('harvest_printing_channel');

harvestChannel.onmessage = function (event) {
    const printedProducts = event.data.products;
    const printForm = document.getElementById('print-form');
    const selectionDisplay = document.getElementById('selection-display');

    printedProducts.forEach(product => {
        appendHarvestToSidebar(
            product.product_code,
            product.name,
            product.qty,
            product.price
        );
    });

    if (printForm) printForm.reset();
    if (selectionDisplay) selectionDisplay.style.display = 'none';
};

function appendHarvestToSidebar(productCode, name, qty, price) {
    const listContainer = document.getElementById('latest-scans-list');
    if (!listContainer) return;

    const emptyPlaceholder = listContainer.querySelector('li[style*="italic"]');
    if (emptyPlaceholder) {
        emptyPlaceholder.remove();
    }

    const totalPrice = parseFloat(price) * parseInt(qty);
    const formattedPrice = new Intl.NumberFormat('fr-FR').format(totalPrice) + ' MGA';

    const harvestHtml = `
        <li style="padding: 10px 0; border-bottom: 1px solid #eee; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; font-weight: bold;">
                <span style="font-family: monospace; color: #e67e22;">${productCode}</span>
                <span style="color: #2ecc71;">${formattedPrice}</span>
            </div>
            <div style="color: #7f8c8d; font-size: 12px; margin-top: 2px; text-transform: uppercase;">
                ${name}
            </div>
        </li>
    `;

    listContainer.insertAdjacentHTML('afterbegin', harvestHtml);

    while (listContainer.children.length > 5) {
        listContainer.lastElementChild.remove();
    }
}

// --- 7. TOAST NOTIFICATIONS ---
function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container');
    if (!container) return;

    const toast = document.createElement('div');

    toast.style.padding = '12px 20px';
    toast.style.borderRadius = '4px';
    toast.style.color = '#ffffff';
    toast.style.fontFamily = "'Calibri', sans-serif";
    toast.style.fontSize = '14px';
    toast.style.fontWeight = 'bold';
    toast.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    toast.style.transition = 'all 0.4s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';

    if (type === 'success') {
        toast.style.backgroundColor = '#2ecc71';
    } else {
        toast.style.backgroundColor = '#e74c3c';
    }

    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 5000);
}