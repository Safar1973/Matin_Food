// State management
let currentLanguage = 'de';
let products = [];
let cart = JSON.parse(localStorage.getItem('cart')) || [];

// Constants
const API_URL = 'backend/api/get_products.php';

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    updateCartUI();
    setupNavigation();
});

// Navigation logic
function setupNavigation() {
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = link.getAttribute('data-section');
            goToSection(sectionId);
        });
    });
}

function goToSection(sectionId) {
    document.querySelectorAll('.page-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionId).classList.add('active');
    
    // Smooth scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
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
function renderProducts() {
    const grid = document.getElementById('product-grid');
    if (!grid) return;
    
    grid.innerHTML = products.map(product => `
        <div class="product-card shadow-sm border p-3 rounded bg-white">
            <img src="${product.img}" alt="${product.name}" class="product-img mb-3" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;" onclick="openProductModal(${product.id})">
            <div class="product-info">
                <h5 class="mb-1">${product.name}</h5>
                <p class="text-primary fw-bold mb-2">${parseFloat(product.price).toFixed(2)} €</p>
                <button class="btn btn-outline-success btn-sm w-100" onclick="addToCart(${product.id})">Hinzufügen</button>
            </div>
        </div>
    `).join('');
}

// Render categories sidebar
function renderCategories() {
    const list = document.getElementById('category-list');
    if (!list) return;
    
    const categories = [...new Set(products.map(p => p.category))];
    list.innerHTML = `
        <li class="category-item border-bottom py-2" onclick="filterByCategory('all')" style="cursor: pointer;">Alle Produkte</li>
        ${categories.map(cat => `
            <li class="category-item border-bottom py-2 text-capitalize" onclick="filterByCategory('${cat}')" style="cursor: pointer;">${cat}</li>
        `).join('')}
    `;
}

function filterByCategory(category) {
    if (category === 'all') {
        renderProducts();
    } else {
        const filtered = products.filter(p => p.category === category);
        const grid = document.getElementById('product-grid');
        grid.innerHTML = filtered.map(product => `
            <div class="product-card shadow-sm border p-3 rounded bg-white">
                <img src="${product.img}" alt="${product.name}" class="product-img mb-3" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px;">
                <div class="product-info">
                    <h5 class="mb-1">${product.name}</h5>
                    <p class="text-primary fw-bold mb-2">${parseFloat(product.price).toFixed(2)} €</p>
                    <button class="btn btn-outline-success btn-sm w-100" onclick="addToCart(${product.id})">Hinzufügen</button>
                </div>
            </div>
        `).join('');
    }
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
    
    // Optional: Visual feedback
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
            <div class="cart-item d-flex align-items-center mb-3 p-2 border-bottom">
                <img src="${item.img}" alt="${item.name}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;" class="me-3">
                <div class="flex-grow-1">
                    <div class="fw-bold fs-6">${item.name}</div>
                    <div class="text-muted small">${parseFloat(item.price).toFixed(2)} € x ${item.qty}</div>
                </div>
                <button class="btn btn-sm text-danger" onclick="removeFromCart(${item.id})">×</button>
            </div>
        `).join('');
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    if (totalEl) totalEl.innerText = `${total.toFixed(2)} €`;
    if (countEl) countEl.innerText = cart.reduce((sum, item) => sum + item.qty, 0);

    // Update checkout summary if section is active
    updateCheckoutSummary();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    saveCart();
    updateCartUI();
}

function clearCart() {
    cart = [];
    saveCart();
    updateCartUI();
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Order handling
function openCheckout() {
    if (cart.length === 0) {
        alert('Ihre Schecker ist leer!');
        return;
    }
    toggleCart();
    goToSection('checkout-section');
}

function updateCheckoutSummary() {
    const summaryItems = document.getElementById('order-summary-items');
    const summaryTotal = document.getElementById('order-total-value');
    
    if (!summaryItems) return;

    summaryItems.innerHTML = cart.map(item => `
        <div class="checkout-item d-flex justify-content-between mb-2">
            <span>${item.name} (x${item.qty})</span>
            <span>${(item.price * item.qty).toFixed(2)} €</span>
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
            alert('Vielen Dank für Ihre Bestellung!');
            clearCart();
            goToSection('home-section');
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
    loadProducts();
    // Here you would also update static UI text based on a dictionary
}

// UI Helpers
function showToast(message) {
    // Basic alert for now, could be a pretty toast later
    console.log('Toast:', message);
}

function openProductModal(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    document.getElementById('modal-img').src = product.img;
    document.getElementById('modal-title').innerText = product.name;
    document.getElementById('modal-price').innerText = `${parseFloat(product.price).toFixed(2)} €`;
    document.getElementById('modal-details-content').innerText = `Kategorie: ${product.category}`;
    document.getElementById('modal-btn').onclick = () => {
        addToCart(product.id);
        closeModal();
    };
    
    document.getElementById('product-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('product-modal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('product-modal');
    if (event.target == modal) {
        closeModal();
    }
}
