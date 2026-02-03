// State management
let currentLanguage = 'de';
let products = [];
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Constants
const API_URL = 'backend/api/get_products.php';

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // Load saved language or default to 'de'
    currentLanguage = localStorage.getItem('lang') || 'de';

    checkProtocol();
    setLanguage(currentLanguage); // This will load products and update UI
    updateCartUI();
    setupNavigation();
    setupFilters();
});

function checkProtocol() {
    if (window.location.protocol === 'file:') {
        const grid = document.getElementById('product-grid');
        if (grid) {
            grid.innerHTML = `
                <div class="alert alert-warning text-center w-100 p-4" style="grid-column: 1 / -1; border-radius: 12px;">
                    <h4 class="fw-bold">⚠️ Wichtiger Hinweis: Lokale Dateiansicht</h4>
                    <p class="mb-0">Sie haben die Seite direkt als Datei geöffnet. Da die Produkte von einem Server geladen werden müssen, öffnen Sie die Seite bitte über Ihren XAMPP-Server unter:<br>
                    <code class="d-block mt-2 bg-white p-2 border rounded">http://localhost/Matin_Food/index.html</code></p>
                </div>
            `;
        }
    }
}

// Navigation logic
function setupNavigation() {
    // Basic navigation is now handled by goToSection
}

function setupFilters() {
    const priceFilter = document.getElementById('price-filter');
    if (priceFilter) {
        priceFilter.addEventListener('input', (e) => {
            renderProducts(products.filter(p => p.price <= e.target.value));
        });
    }
}

function goToSection(sectionId) {
    document.querySelectorAll('.page-section').forEach(section => {
        section.classList.remove('active');
    });
    const target = document.getElementById(sectionId);
    if (target) target.classList.add('active');

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Search logic
function handleSearch(event) {
    event.preventDefault();
    const query = document.getElementById('main-search').value.toLowerCase();
    const filtered = products.filter(p => {
        const matchesQuery = p.name_de.toLowerCase().includes(query) ||
            p.category.toLowerCase().includes(query);
        const matchesCategory = selectedSearchCategory === 'all' || p.category === selectedSearchCategory;
        return matchesQuery && matchesCategory;
    });
    renderProducts(filtered);
    goToSection('home-section');
}

// Load products from API
async function loadProducts() {
    try {
        const response = await fetch(`${API_URL}?lang=${currentLanguage}`);
        products = await response.json();
        renderProducts();
        renderCategories();
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

// Render product grid
function renderProducts(productsToRender = products) {
    const grid = document.getElementById('product-grid');
    if (!grid) return;

    if (productsToRender.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5" style="grid-column: 1 / -1;">
                <p class="text-muted fs-5">Keine Produkte in dieser Kategorie gefunden.</p>
                <button class="btn btn-outline-primary mt-2" onclick="filterByCategory('all')">Alle Produkte anzeigen</button>
            </div>`;
        return;
    }

    grid.innerHTML = productsToRender.map(product => {
        const discount = Math.floor(Math.random() * 20) + 15;
        const weightPrice = (product.price / 0.75).toFixed(2); // Mocked weight price

        return `
        <div class="product-card">
            <div class="product-image-container">
                <div class="badge-container">
                    <span class="badge-discount">-${discount}%</span>
                    ${product.id % 4 === 0 ? '<span class="badge-neu-pill">Neu</span>' : ''}
                </div>
                <button class="wishlist-btn-ref" title="Auf den Merkzettel">
                    ♡
                </button>
                <img src="${product.img}" alt="${product.name}" class="product-img" onclick="openProductModal(${product.id})">
            </div>
            <div class="product-info-ref text-center">
                <div class="product-price-ref">${parseFloat(product.price).toFixed(2)} €</div>
                <div class="product-tax-info">Inkl. MwSt. zzgl. Versandkosten</div>
                <h3 class="product-name-ref" onclick="openProductModal(${product.id})">${product.name}</h3>
                <div class="product-weight-price">${weightPrice} €/kg</div>
                
                <div class="mt-3">
                    <button class="add-to-cart-pill" onclick="addToCart(${product.id})">
                        In den 🛒
                    </button>
                </div>
            </div>
        </div>
    `}).join('');
}

// i18n Dictionary
const translations = {
    'de': {
        'help': 'Hilfe',
        'about_us': 'Über uns',
        'contact': 'Kontakt',
        'all_categories': 'Alle Kategorien',
        'account': 'Konto',
        'wishlist': 'Merkzettel',
        'cart': 'Warenkorb',
        'home': 'Startseite',
        'cat_grains': 'Getreide & Körner',
        'cat_canned': 'Konserven',
        'cat_syrups': 'Sirupe & Saucen',
        'offers': 'ANGEBOTE %',
        'recommended': 'Empfohlen',
        'categories': 'Kategorien',
        'welcome_title': 'Willkommen bei MATIN FOOD',
        'welcome_text': 'Entdecken Sie authentische orientalische Spezialitäten und frische Lebensmittel in bester Qualität.',
        'all_products': 'Alle Produkte',
        'popular': 'Beliebt',
        'new_in_shop': 'Neu im Shop',
        'bestseller': 'Bestseller',
        'sold_out_soon': 'Bald ausverkauft!',
        'grains': 'Getreide & Körner',
        'canned': 'Konserven',
        'syrups': 'Sirupe & Saucen',
        'bakery': 'Bäckerei',
        'fresh': 'Frische Ware',
        'sweets': 'Süßigkeiten'
    },
    'en': {
        'help': 'Help',
        'about_us': 'About Us',
        'contact': 'Contact',
        'all_categories': 'All Categories',
        'account': 'Account',
        'wishlist': 'Wishlist',
        'cart': 'Cart',
        'home': 'Home',
        'cat_grains': 'Grains & Seeds',
        'cat_canned': 'Canned Food',
        'cat_syrups': 'Syrups & Sauces',
        'offers': 'OFFERS %',
        'recommended': 'Recommended',
        'categories': 'Categories',
        'welcome_title': 'Welcome to MATIN FOOD',
        'welcome_text': 'Discover authentic oriental specialties and fresh food in top quality.',
        'all_products': 'All Products',
        'popular': 'Popular',
        'new_in_shop': 'New in Shop',
        'bestseller': 'Bestseller',
        'sold_out_soon': 'Sold out soon!',
        'grains': 'Grains & Seeds',
        'canned': 'Canned Food',
        'syrups': 'Syrups & Sauces',
        'bakery': 'Bakery',
        'fresh': 'Fresh Goods',
        'sweets': 'Sweets'
    },
    'ar': {
        'help': 'مساعدة',
        'about_us': 'من نحن',
        'contact': 'اتصل بنا',
        'all_categories': 'جميع الفئات',
        'account': 'حسابي',
        'wishlist': 'قائمة الأمنيات',
        'cart': 'عربة التسوق',
        'home': 'الرئيسية',
        'cat_grains': 'حبوب وبقوليات',
        'cat_canned': 'معلبات',
        'cat_syrups': 'شراب وصوص',
        'offers': 'عروض %',
        'recommended': 'مقترح لك',
        'categories': 'الفئات',
        'welcome_title': 'مرحباً بكم في MATIN FOOD',
        'welcome_text': 'اكتشف التخصصات الشرقية الأصيلة والأغذية الطازجة بجودة عالية.',
        'all_products': 'جميع المنتجات',
        'popular': 'الأكثر شعبية',
        'new_in_shop': 'جديد في المتجر',
        'bestseller': 'الأكثر مبيعاً',
        'sold_out_soon': 'قاربت على النفاد!',
        'grains': 'حبوب وبقوليات',
        'canned': 'معلبات',
        'syrups': 'شراب وصوص',
        'bakery': 'مخبوزات',
        'fresh': 'مواد طازجة',
        'sweets': 'حلويات'
    }
};

// Category mapping for localized display (legacy support)
const categoryMap = translations['de'];

// Render categories (search dropdown and sidebar)
function renderCategories() {
    const searchCatList = document.getElementById('search-cat-list');
    const sidebarCatList = document.getElementById('sidebar-categories');

    const categories = [...new Set(products.map(p => p.category))];
    const dict = translations[currentLanguage];

    if (searchCatList) {
        searchCatList.innerHTML = `
            <li><a class="dropdown-item" href="#" onclick="setSearchCategory('all')">${dict['all_categories']}</a></li>
            ${categories.map(cat => `
                <li><a class="dropdown-item" href="#" onclick="setSearchCategory('${cat}')">${dict[cat] || cat}</a></li>
            `).join('')}
        `;
    }

    if (sidebarCatList) {
        sidebarCatList.innerHTML = `
            <li class="category-item-ref" onclick="filterByCategory('all')">${dict['all_products']}</li>
            ${categories.map(cat => `
                <li class="category-item-ref" onclick="filterByCategory('${cat}')">${dict[cat] || cat}</li>
            `).join('')}
        `;
    }
}

let selectedSearchCategory = 'all';
function setSearchCategory(category) {
    selectedSearchCategory = category;
    const btn = document.getElementById('searchCatBtn');
    const dict = translations[currentLanguage];
    if (btn) btn.innerText = category === 'all' ? dict['all_categories'] : (dict[category] || category);
}

function filterByCategory(category) {
    let filtered = products;

    if (category === 'all') {
        filtered = products;
    } else if (category === 'neu') {
        filtered = products.filter(p => p.id % 4 === 0);
    } else if (category === 'bestseller') {
        filtered = products.filter(p => p.price > 4);
    } else if (category === 'ausverkauft') {
        filtered = products.filter(p => p.id % 7 === 0);
    } else {
        filtered = products.filter(p => p.category.toLowerCase() === category.toLowerCase());
    }

    renderProducts(filtered);
    goToSection('home-section');
}

// Cart management
function addToCart(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id == productId);
    if (existingItem) {
        existingItem.qty++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            img: product.img,
            qty: 1
        });
    }

    saveCart();
    updateCartUI();
    showToast(`${product.name} hinzugefügt`);
}

function toggleCart() {
    const panel = document.getElementById('cart-panel');
    panel.classList.toggle('active');
}

function updateCartUI() {
    const itemsContainer = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total');
    const countEl = document.getElementById('cart-count');

    if (itemsContainer) {
        itemsContainer.innerHTML = cart.map(item => `
            <div class="d-flex align-items-center mb-4 gap-3">
                <img src="${item.img}" alt="${item.name}" style="width: 70px; height: 70px; object-fit: contain; border-radius: 8px; background: #fdfdfd; border: 1px solid #eee;">
                <div class="flex-grow-1">
                    <div class="fw-bold d-block text-truncate" style="max-width: 180px;">${item.name}</div>
                    <div class="text-danger fw-bold">${parseFloat(item.price).toFixed(2)} €</div>
                    <div class="small text-muted">Menge: ${item.qty}</div>
                </div>
                <button class="btn btn-sm btn-outline-light text-dark border-0" onclick="removeFromCart(${item.id})">🗑️</button>
            </div>
        `).join('');
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    if (totalEl) totalEl.innerText = `${total.toFixed(2)} €`;
    if (countEl) countEl.innerText = cart.reduce((sum, item) => sum + item.qty, 0);

    updateCheckoutSummary();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    saveCart();
    updateCartUI();
}

function clearCart() {
    if (confirm('Möchten Sie den Warenkorb wirklich leeren?')) {
        cart = [];
        saveCart();
        updateCartUI();
    }
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Order handling
function openCheckout() {
    if (cart.length === 0) {
        alert('Ihr Warenkorb ist leer!');
        return;
    }
    const panel = document.getElementById('cart-panel');
    panel.classList.remove('active');
    goToSection('checkout-section');
}

function updateCheckoutSummary() {
    const summaryItems = document.getElementById('order-summary-items');
    const summaryTotal = document.getElementById('order-total-value');

    if (!summaryItems) return;

    summaryItems.innerHTML = cart.map(item => `
        <div class="d-flex justify-content-between mb-2">
            <span>${item.name} <span class="text-muted">x${item.qty}</span></span>
            <span class="fw-medium">${(item.price * item.qty).toFixed(2)} €</span>
        </div>
    `).join('');

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    summaryTotal.innerText = `${total.toFixed(2)} €`;
}

async function submitOrder(event) {
    event.preventDefault();

    const orderData = {
        name: document.getElementById('cust-name').value,
        address: document.getElementById('cust-address').value,
        city: document.getElementById('cust-city').value,
        phone: document.getElementById('cust-phone').value,
        payment_method: document.getElementById('payment-method-select').value,
        total_amount: cart.reduce((sum, item) => sum + (item.price * item.qty), 0),
        items: cart.map(item => ({
            product_id: item.id,
            qty: item.qty,
            price: item.price
        }))
    };

    try {
        const response = await fetch('backend/api/place_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        const result = await response.json();

        if (result.success) {
            cart = [];
            saveCart();
            updateCartUI();
            window.location.href = `invoice.php?order_id=${result.order_id}`;
        } else {
            alert('Fehler bei der Bestellung: ' + result.error);
        }
    } catch (error) {
        console.error('Order error:', error);
        alert('Ein technischer Fehler ist aufgetreten.');
    }
}

// Language management
function setLanguage(lang) {
    currentLanguage = lang;
    localStorage.setItem('lang', lang);

    // Update HTML attributes
    document.documentElement.lang = lang;
    document.documentElement.dir = (lang === 'ar') ? 'rtl' : 'ltr';

    // Update UI components
    updateStaticText();
    updateLanguageSwitcherUI();
    loadProducts();
}

function updateStaticText() {
    const dict = translations[currentLanguage];
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict[key]) {
            el.innerText = dict[key];
        }
    });
}

function updateLanguageSwitcherUI() {
    const currentLangText = document.getElementById('current-lang-text');
    if (currentLangText) {
        const langMap = { 'de': 'Deutsch', 'en': 'English', 'ar': 'العربية' };
        currentLangText.innerText = langMap[currentLanguage];
    }
}

// UI Helpers
function showToast(message) {
    console.log('Toast:', message);
}

function openProductModal(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    document.getElementById('modal-img').src = product.img;
    document.getElementById('modal-title').innerHTML = `
        ${product.name} <br>
        <small class="text-muted" style="font-family: Arial, sans-serif;">${product.name_ar}</small>
    `;
    document.getElementById('modal-price').innerText = `${parseFloat(product.price).toFixed(2)} €`;
    document.getElementById('modal-details-content').innerHTML = `
        <strong>Kategorie:</strong> ${categoryMap[product.category] || product.category}<br>
        <strong>Haltbarkeit:</strong> ${product.expiry}<br>
        <strong>Beschreibung:</strong> Authentische Qualität für Ihre Küche. Premium-Import.
    `;
    document.getElementById('modal-btn').onclick = () => {
        addToCart(product.id);
        const modal = bootstrap.Modal.getInstance(document.getElementById('product-modal'));
        if (modal) modal.hide();
    };

    const productModal = new bootstrap.Modal(document.getElementById('product-modal'));
    productModal.show();
}

function closeModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('product-modal'));
    if (modal) modal.hide();
}

// Account view switching
function switchAccountView(view) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginTab = document.getElementById('login-tab');
    const registerTab = document.getElementById('register-tab');

    if (view === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        loginTab.classList.remove('active');
        registerTab.classList.add('active');
    }
}
