/**
 * POS System JavaScript - MedXtore Pharmacy
 * Author: VOVANHUNG-DEV
 * Description: Real-time POS functionality with cart management, payment processing, and stock checking
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        searchDebounceTime: 300,
        apiEndpoints: {
            products: '/api/pos/products',
            cart: '/api/pos/cart',
            discount: '/api/pos/discount',
            customer: '/api/pos/customer',
            payment: '/api/pos/payment',
            heldBills: '/api/pos/held-bills'
        },
        localStorageKey: 'pos_cart_backup',
        minTouchSize: '44px'
    };

    // Global state
    let cart = {
        items: [],
        subtotal: 0,
        discount_amount: 0,
        discount: {},
        total: 0,
        customer: null
    };

    let searchTimeout = null;
    let heldBills = [];

    // ==================== INITIALIZATION ====================

    /**
     * Initialize POS system when DOM is ready
     */
    function init() {
        console.log('🚀 POS System initializing...');

        // Load cart from server session first
        console.log('📥 Loading cart from server...');
        updateCartFromServer();

        // Load initial products
        loadProducts();

        // Load held bills
        loadHeldBills();

        // Setup event listeners
        setupEventListeners();

        console.log('✅ POS System ready');
    }

    /**
     * Load initial product list
     * @param {string} search - Search keyword
     * @param {string} categoryId - Category ID filter
     * @param {number} limit - Number of products to load
     * @param {number} offset - Offset for pagination
     */
    function loadProducts(search = '', categoryId = '', limit = 50, offset = 0) {
        console.log('Loading products...', {search, categoryId, limit, offset});

        // Build query parameters
        const params = new URLSearchParams({
            action: 'get_products',
            search: search,
            loai_id: categoryId,
            limit: limit,
            offset: offset
        });

        // Show loading state
        const resultsContainer = document.getElementById('productsGrid') || document.getElementById('search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '<div class="loading-spinner"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Đang tải...</span></div></div>';
        }

        // Fetch products
        fetch(CONFIG.apiEndpoints.products + '?' + params.toString())
            .then(response => response.json())
            .then(data => {
                console.log('Products loaded:', data);

                if (data.success && data.data) {
                    displaySearchResults(data.data);
                } else {
                    showNotification(data.message || 'Không thể tải sản phẩm', 'error');
                    if (resultsContainer) {
                        resultsContainer.innerHTML = '<div class="error-message">Không thể tải sản phẩm. Vui lòng thử lại.</div>';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading products:', error);
                showNotification('Lỗi kết nối. Vui lòng kiểm tra lại.', 'error');
                if (resultsContainer) {
                    resultsContainer.innerHTML = '<div class="error-message">Lỗi kết nối. Vui lòng thử lại.</div>';
                }
            });
    }

    /**
     * Setup all event listeners
     */
    function setupEventListeners() {
        // Search functionality
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const keyword = e.target.value.trim();
                if (keyword.length >= 2) {
                    searchProducts(keyword);
                } else if (keyword.length === 0) {
                    clearSearchResults();
                }
            });
        }

        // Cart clear button
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', function() {
                if (confirm('Xóa toàn bộ giỏ hàng?')) {
                    clearCart();
                }
            });
        }

        // Hold bill button
        const holdBillBtn = document.getElementById('hold-bill-btn');
        if (holdBillBtn) {
            holdBillBtn.addEventListener('click', function() {
                const billName = prompt('Nhập tên hóa đơn tạm giữ (hoặc để trống):');
                if (billName !== null) {
                    holdBill(billName || 'Hóa đơn ' + new Date().toLocaleTimeString());
                }
            });
        }

        // Payment method tabs
        const paymentTabs = document.querySelectorAll('.payment-method-tab');
        paymentTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                switchPaymentMethod(this.dataset.method);
            });
        });

        // Cash payment - quick amount buttons
        const quickAmountBtns = document.querySelectorAll('.quick-amount-btn');
        quickAmountBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const amount = parseInt(this.dataset.amount);
                const cashReceivedInput = document.getElementById('cash-received');
                if (cashReceivedInput) {
                    cashReceivedInput.value = amount;
                    calculateChange(amount);
                }
            });
        });

        // Cash received input
        const cashReceivedInput = document.getElementById('cash-received');
        if (cashReceivedInput) {
            cashReceivedInput.addEventListener('input', function() {
                const amount = parseFloat(this.value) || 0;
                calculateChange(amount);
            });
        }

        // Apply voucher button
        const applyVoucherBtn = document.getElementById('apply-voucher-btn');
        if (applyVoucherBtn) {
            applyVoucherBtn.addEventListener('click', function() {
                const voucherCode = document.getElementById('voucher-code').value.trim();
                if (voucherCode) {
                    applyVoucher(voucherCode);
                } else {
                    showNotification('Vui lòng nhập mã voucher', 'warning');
                }
            });
        }

        // Apply discount button
        const applyDiscountBtn = document.getElementById('apply-discount-btn');
        if (applyDiscountBtn) {
            applyDiscountBtn.addEventListener('click', function() {
                showDiscountModal();
            });
        }

        // Process payment button
        const processPaymentBtn = document.getElementById('process-payment-btn');
        if (processPaymentBtn) {
            processPaymentBtn.addEventListener('click', function() {
                processPayment();
            });
        }
    }

    // ==================== PRODUCT SEARCH ====================

    /**
     * Search products with debounce
     * @param {string} keyword - Search keyword
     */
    function searchProducts(keyword) {
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Show loading state
        showSearchLoading();

        // Debounce search
        searchTimeout = setTimeout(function() {
            // Make AJAX request
            const params = new URLSearchParams({
                action: 'get_products',
                search: keyword,
                loai_id: '',
                limit: 50,
                offset: 0
            });

            fetch(CONFIG.apiEndpoints.products + '?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySearchResults(data.data || data.products || []);
                    } else {
                        showNotification(data.message || 'Lỗi tìm kiếm', 'error');
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    showNotification('Lỗi kết nối', 'error');
                })
                .finally(() => {
                    hideSearchLoading();
                });
        }, CONFIG.searchDebounceTime);
    }

    /**
     * Display search results
     * @param {Array} products - Array of product objects
     */
    function displaySearchResults(products) {
        const resultsContainer = document.getElementById('productsGrid') || document.getElementById('search-results');
        if (!resultsContainer) {
            console.error('Products container not found (tried productsGrid and search-results)');
            return;
        }

        console.log('Displaying', products.length, 'products');

        if (products.length === 0) {
            resultsContainer.innerHTML = '<div class="no-results">Không tìm thấy sản phẩm</div>';
            return;
        }

        // Normalize product data to match expected format
        const normalizedProducts = products.map(product => ({
            id: product.thuoc_id || product.id,
            tenthuoc: product.ten_thuoc || product.tenthuoc,
            mathuoc: product.thuoc_id || product.id, // Use ID as code if not provided
            hinhanh: product.hinhanh,
            tonkho: product.soluong_tonkho || product.tonkho || 0,
            donvi: product.donvi || [{
                id: product.donvi_id,
                tendonvi: product.ten_donvi,
                giaban: product.gia
            }],
            gia: product.gia,
            donvi_id: product.donvi_id,
            ten_donvi: product.ten_donvi
        }));

        let html = '<div class="product-grid">';
        normalizedProducts.forEach(product => {
            html += createProductCard(product);
        });
        html += '</div>';

        resultsContainer.innerHTML = html;

        // Add click handlers to product cards
        attachProductCardHandlers();
    }

    /**
     * Create product card HTML
     * @param {Object} product - Product object
     * @returns {string} HTML string
     */
    function createProductCard(product) {
        const stockStatus = getStockStatus(product.tonkho);
        const stockClass = stockStatus.class;
        const stockText = stockStatus.text;

        return `
            <div class="product-card" data-product-id="${product.id}">
                <div class="product-image">
                    <img src="${product.hinhanh || '/images/no-image.png'}" alt="${product.tenthuoc}">
                    <span class="stock-badge ${stockClass}">${stockText}</span>
                </div>
                <div class="product-info">
                    <h4 class="product-name">${product.tenthuoc}</h4>
                    <p class="product-code">Mã: ${product.mathuoc}</p>
                    <div class="product-units">
                        ${createUnitButtons(product.donvi)}
                    </div>
                    <div class="product-actions">
                        <button class="btn btn-primary add-to-cart-btn"
                                data-product-id="${product.id}"
                                ${product.tonkho <= 0 ? 'disabled' : ''}>
                            <i class="icon-cart"></i> Thêm vào giỏ
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Create unit selection buttons
     * @param {Array} units - Array of unit objects
     * @returns {string} HTML string
     */
    function createUnitButtons(units) {
        if (!units || units.length === 0) return '';

        let html = '<div class="unit-buttons">';
        units.forEach((unit, index) => {
            html += `
                <button class="unit-btn ${index === 0 ? 'active' : ''}"
                        data-unit-id="${unit.id}"
                        data-unit-name="${unit.tendonvi}"
                        data-price="${unit.giaban}">
                    ${unit.tendonvi}: ${formatCurrency(unit.giaban)}
                </button>
            `;
        });
        html += '</div>';

        return html;
    }

    /**
     * Get stock status information
     * @param {number} stock - Stock quantity
     * @returns {Object} Status object with class and text
     */
    function getStockStatus(stock) {
        if (stock <= 0) {
            return { class: 'out-of-stock', text: 'Hết hàng' };
        } else if (stock < 10) {
            return { class: 'low-stock', text: 'Còn ' + stock };
        } else {
            return { class: 'in-stock', text: 'Còn hàng' };
        }
    }

    /**
     * Attach event handlers to product cards
     */
    function attachProductCardHandlers() {
        // Unit selection buttons
        const unitButtons = document.querySelectorAll('.unit-btn');
        unitButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active from siblings
                this.parentElement.querySelectorAll('.unit-btn').forEach(b => {
                    b.classList.remove('active');
                });
                // Add active to this button
                this.classList.add('active');
            });
        });

        // Add to cart buttons
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
        addToCartButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productCard = this.closest('.product-card');
                const activeUnitBtn = productCard.querySelector('.unit-btn.active');

                if (!activeUnitBtn) {
                    showNotification('Vui lòng chọn đơn vị tính', 'warning');
                    return;
                }

                const unitId = activeUnitBtn.dataset.unitId;
                const unitName = activeUnitBtn.dataset.unitName;
                const price = parseFloat(activeUnitBtn.dataset.price) || 0;
                const productName = productCard.querySelector('.product-name')?.textContent || 'Unknown';
                const productImage = productCard.querySelector('.product-image img')?.src || '';

                console.log('Adding to cart:', {productId, unitId, unitName, price, productName});

                addToCart(productId, unitId, 1, price, productName, unitName, productImage);
            });
        });
    }

    /**
     * Clear search results
     */
    function clearSearchResults() {
        const resultsContainer = document.getElementById('search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '<div class="search-placeholder">Nhập tên hoặc mã sản phẩm để tìm kiếm</div>';
        }
    }

    /**
     * Show search loading state
     */
    function showSearchLoading() {
        const resultsContainer = document.getElementById('search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '<div class="loading-spinner">Đang tìm kiếm...</div>';
        }
    }

    /**
     * Hide search loading state
     */
    function hideSearchLoading() {
        // Loading is replaced by results or no-results message
    }

    // ==================== CART MANAGEMENT ====================

    /**
     * Add product to cart
     * @param {number} productId - Product ID
     * @param {number} unitId - Unit ID
     * @param {number} quantity - Quantity to add
     * @param {number} price - Product price
     * @param {string} productName - Product name
     * @param {string} unitName - Unit name
     * @param {string} image - Product image
     */
    function addToCart(productId, unitId, quantity, price, productName, unitName, image) {
        console.log('addToCart called with:', {productId, unitId, quantity, price, productName, unitName});

        // Make AJAX request to add to cart
        fetch(CONFIG.apiEndpoints.cart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'add',
                thuoc_id: productId,
                donvi_id: unitId,
                soluong: quantity,
                gia: price,
                ten_thuoc: productName,
                ten_donvi: unitName,
                hinhanh: image
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Add to cart response:', data);
            if (data.success) {
                // Update local cart
                updateCartFromServer();
                showNotification('✅ Đã thêm vào giỏ hàng', 'success');
            } else {
                // Show error notification
                const message = data.message || 'Lỗi thêm sản phẩm';
                showNotification(' ' + message, 'error');
                console.error('Add to cart failed:', message);
            }
        })
        .catch(error => {
            console.error('Add to cart error:', error);
            showNotification(' Lỗi kết nối: ' + error.message, 'error');
        });
    }

    /**
     * Update cart item quantity
     * @param {string} key - Cart item key
     * @param {number} quantity - New quantity
     */
    function updateCartQuantity(key, change) {
        // Make AJAX request
        fetch(CONFIG.apiEndpoints.cart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_quantity',
                key: key,
                change: change
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartFromServer();
            } else {
                showNotification(data.message || 'Lỗi cập nhật giỏ hàng', 'error');
            }
        })
        .catch(error => {
            console.error('Update cart error:', error);
            showNotification('Lỗi kết nối', 'error');
        });
    }

    /**
     * Remove item from cart
     * @param {string} key - Cart item key
     */
    function removeFromCart(key) {
        if (!confirm('Xóa sản phẩm khỏi giỏ hàng?')) {
            return;
        }

        fetch(CONFIG.apiEndpoints.cart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'remove',
                key: key
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartFromServer();
                showNotification('Đã xóa sản phẩm', 'success');
            } else {
                showNotification(data.message || 'Lỗi xóa sản phẩm', 'error');
            }
        })
        .catch(error => {
            console.error('Remove from cart error:', error);
            showNotification('Lỗi kết nối', 'error');
        });
    }

    /**
     * Clear entire cart
     */
    function clearCart() {
        fetch(CONFIG.apiEndpoints.cart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'clear'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cart = {
                    items: [],
                    subtotal: 0,
                    discount_amount: 0,
                    discount: {},
                    total: 0,
                    customer: null
                };
                updateCartUI();
                clearCartBackup();
                showNotification('✅ Đã xóa toàn bộ giỏ hàng', 'success');
            } else {
                showNotification(' ' + (data.message || 'Lỗi xóa giỏ hàng'), 'error');
            }
        })
        .catch(error => {
            console.error('Clear cart error:', error);
            showNotification('Lỗi kết nối', 'error');
        });
    }

    /**
     * Fetch and update cart from server
     */
    function updateCartFromServer() {
        fetch(CONFIG.apiEndpoints.cart)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    cart.items = data.data.items || [];
                    cart.subtotal = data.data.subtotal || 0;
                    cart.discount_amount = data.data.discount_amount || 0;
                    cart.discount = data.data.discount || {};
                    cart.total = data.data.total || 0;
                    updateCartUI();
                    saveCartBackup();
                } else {
                    showNotification(data.message || 'Không thể tải giỏ hàng', 'error');
                }
            })
            .catch(error => {
                console.error('Error fetching cart:', error);
                showNotification('Lỗi tải giỏ hàng: ' + error.message, 'error');
            });
    }

    /**
     * Update cart UI
     */
    function updateCartUI() {
        // Calculate cart totals first
        calculateCartTotals();

        // Update cart items list
        const cartItemsContainer = document.getElementById('cartItems');
        if (cartItemsContainer) {
            // Handle both array and object cart.items
            const items = Array.isArray(cart.items) ? cart.items : Object.entries(cart.items || {}).map(([key, item]) => ({...item, key}));

            if (items.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="empty-cart">
                        <i class="bi bi-cart-x"></i>
                        <p>Giỏ hàng trống</p>
                        <small>Thêm sản phẩm để bắt đầu</small>
                    </div>
                `;
            } else {
                let html = '';
                items.forEach((item, index) => {
                    const key = item.key || `${item.thuoc_id}_${item.donvi_id}`;
                    html += createCartItemHTML(item, key);
                });
                cartItemsContainer.innerHTML = html;

                // Attach event handlers to cart items
                attachCartItemHandlers();
            }
        }

        // Update totals display
        updateCartTotals();

        // Update cart count badge
        const cartCountBadge = document.getElementById('cart-count');
        if (cartCountBadge) {
            const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});
            const totalItems = items.reduce((sum, item) => sum + (item.quantity || item.soluong || 0), 0);
            cartCountBadge.textContent = totalItems;
            cartCountBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
        }
    }

    /**
     * Calculate cart totals
     */
    function calculateCartTotals() {
        // Handle both array and object cart.items
        const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});

        // Calculate subtotal
        cart.subtotal = items.reduce((sum, item) => {
            const price = item.price || item.gia || 0;
            const quantity = item.quantity || item.soluong || 1;
            return sum + (price * quantity);
        }, 0);

        // Calculate total (subtotal - discount_amount)
        cart.total = cart.subtotal - (cart.discount_amount || 0);

        // Make sure total is not negative
        if (cart.total < 0) cart.total = 0;
    }

    /**
     * Create cart item HTML
     * @param {Object} item - Cart item object
     * @param {number} index - Item index
     * @returns {string} HTML string
     */
    function createCartItemHTML(item, key) {
        // Generate key if not present (thuoc_id_donvi_id)
        const itemKey = key || item.key || `${item.thuoc_id}_${item.donvi_id}`;
        const price = item.price || item.gia || 0;
        const quantity = item.quantity || item.soluong || 1;
        const subtotal = price * quantity;

        return `
            <div class="cart-item" data-key="${itemKey}">
                <div class="cart-item-info">
                    <h5 class="cart-item-name">${item.name || item.ten_thuoc || 'N/A'}</h5>
                    <p class="cart-item-unit">
                        <i class="bi bi-box"></i> ${item.unit_name || item.ten_donvi || 'N/A'}
                    </p>
                    <p class="cart-item-price">
                        <i class="bi bi-tag-fill"></i> ${formatCurrency(price)}
                    </p>
                </div>
                <div class="cart-item-quantity">
                    <button class="btn-quantity btn-decrease" data-key="${itemKey}" title="Giảm số lượng">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <input type="number" class="quantity-input" value="${quantity}"
                           min="1" max="999" data-key="${itemKey}" data-current-qty="${quantity}">
                    <button class="btn-quantity btn-increase" data-key="${itemKey}" title="Tăng số lượng">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="cart-item-total">
                    <p class="item-total">${formatCurrency(subtotal)}</p>
                    <button class="btn-remove" data-key="${itemKey}" title="Xóa sản phẩm">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Attach event handlers to cart items
     */
    function attachCartItemHandlers() {
        // Decrease quantity buttons
        document.querySelectorAll('.btn-decrease').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.dataset.key;
                updateCartQuantity(key, -1);
            });
        });

        // Increase quantity buttons
        document.querySelectorAll('.btn-increase').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.dataset.key;
                updateCartQuantity(key, 1);
            });
        });

        // Quantity input change
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const key = this.dataset.key;
                const currentQty = parseInt(this.dataset.currentQty || 1);
                const newQty = parseInt(this.value) || 1;
                const change = newQty - currentQty;

                if (change !== 0) {
                    updateCartQuantity(key, change);
                }
            });
        });

        // Remove buttons
        document.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.dataset.key;
                removeFromCart(key);
            });
        });
    }

    /**
     * Update cart totals display
     */
    function updateCartTotals() {
        const subtotalEl = document.getElementById('cartSubtotal');
        const discountEl = document.getElementById('cartDiscount');
        const totalEl = document.getElementById('cartTotal');

        if (subtotalEl) {
            subtotalEl.textContent = formatCurrency(cart.subtotal || 0);
        }

        if (discountEl) {
            const discountAmount = cart.discount_amount || 0;

            if (discountAmount > 0) {
                // Show discount with clear button
                discountEl.innerHTML = `
                    <span>-${formatCurrency(discountAmount)}</span>
                    <button class="btn-clear-discount" onclick="clearDiscount()" title="Xóa mã giảm giá">
                        <i class="bi bi-x-circle"></i>
                    </button>
                `;
            } else {
                discountEl.textContent = '-' + formatCurrency(0);
            }

            // Show/hide discount row
            const discountRow = discountEl.closest('.summary-row');
            if (discountRow) {
                discountRow.style.display = discountAmount > 0 ? 'flex' : 'none';
            }
        }

        if (totalEl) {
            totalEl.textContent = formatCurrency(cart.total || 0);
        }

        // Enable/disable checkout button
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});
            checkoutBtn.disabled = items.length === 0;
        }
    }

    // ==================== STOCK CHECKING ====================

    /**
     * Check stock availability
     * @param {number} productId - Product ID
     * @param {number} unitId - Unit ID
     * @param {number} quantity - Quantity to check
     * @returns {Promise<boolean>} Promise resolving to stock availability
     */
    function checkStock(productId, unitId, quantity) {
        return fetch(CONFIG.apiEndpoints.checkStock, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            },
            body: JSON.stringify({
                product_id: productId,
                unit_id: unitId,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                return data.available;
            } else {
                showNotification(data.message || 'Không thể kiểm tra tồn kho', 'error');
                return false;
            }
        })
        .catch(error => {
            console.error('Stock check error:', error);
            showNotification('Lỗi kiểm tra tồn kho: ' + error.message, 'error');
            return false;
        });
    }

    // ==================== CUSTOMER MANAGEMENT ====================

    /**
     * Search customer by phone or name
     */
    function searchCustomer() {
        console.log('🔍 searchCustomer() called');

        const searchInput = document.getElementById('customerSearch');
        console.log('Search input element:', searchInput);

        const keyword = searchInput ? searchInput.value.trim() : '';
        console.log('Search keyword:', keyword);

        if (!keyword) {
            console.warn('⚠️ No keyword entered');
            showNotification('Vui lòng nhập SĐT hoặc tên khách hàng', 'warning');
            return;
        }

        const customerInfo = document.getElementById('customerInfo');
        if (customerInfo) {
            customerInfo.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Đang tìm...</div>';
        }

        const url = CONFIG.apiEndpoints.customer + '?action=search&keyword=' + encodeURIComponent(keyword);
        console.log('🌐 Fetching:', url);

        fetch(url)
            .then(response => {
                console.log('📡 Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📦 Customer search response:', data);

                if (data.success && data.data && data.data.length > 0) {
                    console.log('✅ Found customers:', data.data.length);
                    displayCustomerResults(data.data);
                } else {
                    console.log(' No customers found');
                    if (customerInfo) {
                        customerInfo.innerHTML = `
                            <div class="customer-not-found">
                                <i class="bi bi-exclamation-circle"></i>
                                <p>Không tìm thấy khách hàng</p>
                                <button class="btn btn-sm btn-primary" onclick="showAddCustomerModal('${keyword}')">
                                    <i class="bi bi-plus-circle"></i> Thêm mới
                                </button>
                            </div>
                        `;
                    }
                    showNotification('Không tìm thấy khách hàng', 'warning');
                }
            })
            .catch(error => {
                console.error(' Search customer error:', error);
                showNotification('Lỗi tìm kiếm khách hàng: ' + error.message, 'error');
                if (customerInfo) {
                    customerInfo.innerHTML = `
                        <div class="customer-default">
                            <i class="bi bi-person"></i>
                            <span>Khách vãng lai</span>
                        </div>
                    `;
                }
            });
    }

    /**
     * Display customer search results
     */
    function displayCustomerResults(customers) {
        const customerInfo = document.getElementById('customerInfo');
        if (!customerInfo) return;

        if (customers.length === 1) {
            // Auto-select if only one result
            selectCustomer(customers[0]);
        } else {
            // Show list to choose
            let html = '<div class="customer-results">';
            customers.forEach(customer => {
                html += `
                    <div class="customer-result-item" onclick="selectCustomer(${JSON.stringify(customer).replace(/"/g, '&quot;')})">
                        <div class="customer-result-info">
                            <strong>${customer.ten || customer.name}</strong>
                            <small>${customer.sdt || customer.phone}</small>
                        </div>
                        <i class="bi bi-chevron-right"></i>
                    </div>
                `;
            });
            html += '</div>';
            customerInfo.innerHTML = html;
        }
    }

    /**
     * Select a customer
     */
    function selectCustomer(customer) {
        cart.customer = customer;

        const customerInfo = document.getElementById('customerInfo');
        if (customerInfo) {
            customerInfo.innerHTML = `
                <div class="customer-selected">
                    <div class="customer-details">
                        <i class="bi bi-person-check-fill text-success"></i>
                        <div>
                            <strong>${customer.ten || customer.name}</strong>
                            <small>${customer.sdt || customer.phone}</small>
                            ${customer.email ? `<small>${customer.email}</small>` : ''}
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearCustomer()">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
        }

        showNotification('Đã chọn khách hàng: ' + (customer.ten || customer.name), 'success');
    }

    /**
     * Clear selected customer
     */
    function clearCustomer() {
        cart.customer = null;

        const customerInfo = document.getElementById('customerInfo');
        if (customerInfo) {
            customerInfo.innerHTML = `
                <div class="customer-default">
                    <i class="bi bi-person"></i>
                    <span>Khách vãng lai</span>
                </div>
            `;
        }

        const searchInput = document.getElementById('customerSearch');
        if (searchInput) {
            searchInput.value = '';
        }
    }

    // ==================== VOUCHER & DISCOUNT ====================

    /**
     * Apply voucher code (reads from input field)
     */
    function applyVoucher() {
        console.log('🏷️ applyVoucher() called');

        const voucherInput = document.getElementById('voucherCode');
        const code = voucherInput ? voucherInput.value.trim() : '';

        console.log('Voucher code:', code);

        if (!code) {
            console.warn('⚠️ No voucher code entered');
            showNotification('⚠️ Vui lòng nhập mã voucher', 'warning');
            return;
        }

        console.log('🌐 Applying voucher:', code);

        fetch(CONFIG.apiEndpoints.discount, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'apply_promotion',
                promotion_code: code
            })
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Apply voucher response:', data);

            if (data.success) {
                console.log('✅ Voucher applied successfully');
                updateCartFromServer();
                showNotification('✅ Đã áp dụng mã khuyến mãi: ' + code, 'success');

                // Clear input after successful application
                voucherInput.value = '';
            } else {
                console.error(' Failed to apply voucher:', data.message);
                showNotification(' ' + (data.message || 'Mã khuyến mãi không hợp lệ'), 'error');
            }
        })
        .catch(error => {
            console.error(' Apply voucher error:', error);
            showNotification(' Lỗi kết nối: ' + error.message, 'error');
        });
    }

    /**
     * Fill promotion code into input field (when clicking promotion tag)
     * @param {string} code - Promotion code to fill
     */
    function applyPromotionCode(code) {
        console.log('📝 applyPromotionCode() called with:', code);

        if (!code) {
            console.warn('⚠️ No promotion code provided');
            return;
        }

        const voucherInput = document.getElementById('voucherCode');
        if (voucherInput) {
            voucherInput.value = code;
            voucherInput.focus();
            console.log('✅ Filled promotion code into input:', code);
            showNotification('📝 Đã điền mã: ' + code + '. Nhấn "Áp dụng" để sử dụng.', 'info');
        } else {
            console.error(' Voucher input not found');
        }
    }

    /**
     * Clear/Remove applied discount
     */
    function clearDiscount() {
        console.log('🗑️ clearDiscount() called');
        console.log('Current discount_amount:', cart.discount_amount);

        if (!cart.discount_amount || cart.discount_amount === 0) {
            console.log('ℹ️ No discount to clear');
            showNotification('ℹ️ Chưa có mã giảm giá nào được áp dụng', 'info');
            return;
        }

        if (!confirm('Bạn có chắc muốn xóa mã giảm giá đang áp dụng?')) {
            console.log(' User cancelled clear discount');
            return;
        }

        console.log('🌐 Clearing discount...');

        fetch(CONFIG.apiEndpoints.discount, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'remove_discount'
            })
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Clear discount response:', data);

            if (data.success) {
                console.log('✅ Discount cleared successfully');
                updateCartFromServer();
                showNotification('✅ Đã xóa mã giảm giá', 'success');

                // Clear voucher input
                const voucherInput = document.getElementById('voucherCode');
                if (voucherInput) {
                    voucherInput.value = '';
                }
            } else {
                console.error(' Failed to clear discount:', data.message);
                showNotification(' ' + (data.message || 'Lỗi xóa mã giảm giá'), 'error');
            }
        })
        .catch(error => {
            console.error(' Clear discount error:', error);
            showNotification(' Lỗi kết nối: ' + error.message, 'error');
        });
    }

    /**
     * Apply manual discount (called from modal button)
     */
    function applyManualDiscount() {
        console.log('Applying manual discount...');

        const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});

        if (items.length === 0) {
            showNotification('Giỏ hàng trống', 'warning');
            return;
        }

        const discountType = document.getElementById('discountType').value;
        const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
        const discountReason = document.getElementById('discountReason').value.trim();

        if (discountValue <= 0) {
            showNotification('Vui lòng nhập giá trị giảm giá', 'warning');
            return;
        }

        if (discountType === 'percentage' && (discountValue < 0 || discountValue > 100)) {
            showNotification('Giảm giá phải từ 0% đến 100%', 'error');
            return;
        }

        if (discountType === 'fixed' && discountValue > cart.subtotal) {
            showNotification('Số tiền giảm không được lớn hơn tổng tiền hàng', 'error');
            return;
        }

        const applyBtn = document.getElementById('applyDiscountBtn');
        if (applyBtn) {
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang áp dụng...';
        }

        fetch(CONFIG.apiEndpoints.discount, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'apply_manual',
                type: discountType,
                value: discountValue,
                reason: discountReason || 'Giảm giá trực tiếp'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartFromServer();

                const modal = bootstrap.Modal.getInstance(document.getElementById('manualDiscountModal'));
                if (modal) {
                    modal.hide();
                }

                document.getElementById('discountValue').value = '';
                document.getElementById('discountReason').value = '';

                const discountText = discountType === 'percentage'
                    ? `${discountValue}%`
                    : `${formatCurrency(discountValue)}`;
                showNotification(`Đã áp dụng giảm giá ${discountText}`, 'success');
            } else {
                showNotification(data.message || 'Lỗi áp dụng giảm giá', 'error');
            }
        })
        .catch(error => {
            console.error('Apply discount error:', error);
            showNotification('Lỗi kết nối: ' + error.message, 'error');
        })
        .finally(() => {
            if (applyBtn) {
                applyBtn.disabled = false;
                applyBtn.innerHTML = '<i class="bi bi-check-circle"></i> Áp dụng';
            }
        });
    }

    /**
     * Update discount preview
     */
    function updateDiscountPreview() {
        const discountType = document.getElementById('discountType').value;
        const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
        const previewDiv = document.getElementById('discountPreview');
        const previewText = document.getElementById('discountPreviewText');

        if (discountValue <= 0 || cart.subtotal <= 0) {
            previewDiv.style.display = 'none';
            return;
        }

        let discountAmount = 0;
        let previewMessage = '';

        if (discountType === 'percentage') {
            if (discountValue > 100) {
                previewDiv.className = 'alert alert-danger small';
                previewText.textContent = 'Giảm giá không được vượt quá 100%';
                previewDiv.style.display = 'block';
                return;
            }
            discountAmount = (cart.subtotal * discountValue) / 100;
            previewMessage = `Giảm ${discountValue}% = ${formatCurrency(discountAmount)}`;
        } else {
            if (discountValue > cart.subtotal) {
                previewDiv.className = 'alert alert-danger small';
                previewText.textContent = 'Số tiền giảm không được lớn hơn tổng tiền hàng';
                previewDiv.style.display = 'block';
                return;
            }
            discountAmount = discountValue;
            previewMessage = `Giảm ${formatCurrency(discountAmount)}`;
        }

        const finalTotal = cart.subtotal - discountAmount;
        previewMessage += ` | Còn lại: ${formatCurrency(finalTotal)}`;

        previewDiv.className = 'alert alert-info small';
        previewText.textContent = previewMessage;
        previewDiv.style.display = 'block';
    }

    // ==================== HOLD BILLS ====================

    /**
     * Hold current bill
     * @param {string} billName - Name for the held bill
     */
    /**
     * Open hold bill modal (called by button)
     */
    function holdBill() {
        console.log('⏸️ holdBill() called - opening modal');
        console.log('Cart items:', cart.items);

        const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});

        if (items.length === 0) {
            console.warn('⚠️ Cart is empty');
            showNotification('⚠️ Giỏ hàng trống', 'warning');
            return;
        }

        // Open modal
        console.log('✅ Opening hold bill modal');
        const modal = new bootstrap.Modal(document.getElementById('holdBillModal'));
        modal.show();
    }

    /**
     * Confirm hold bill (called by modal button)
     */
    function confirmHoldBill() {
        console.log('⏸️ confirmHoldBill() called');

        // Get bill name from input
        const billNameInput = document.getElementById('holdBillName');
        const billName = billNameInput ? billNameInput.value.trim() : '';

        console.log('Bill name:', billName || '(no name)');

        const url = CONFIG.apiEndpoints.heldBills;
        console.log('🌐 Fetching:', url);

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'hold',
                bill_name: billName || null
            })
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Hold bill response:', data);

            if (data.success) {
                console.log('✅ Bill held successfully');

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('holdBillModal'));
                if (modal) {
                    modal.hide();
                }

                // Clear input
                if (billNameInput) {
                    billNameInput.value = '';
                }

                // Reload held bills list
                loadHeldBills();

                // Clear current cart
                clearCart();

                showNotification('✅ Đã tạm giữ hóa đơn', 'success');
            } else {
                console.error(' Failed to hold bill:', data.message);
                showNotification(' ' + (data.message || 'Lỗi tạm giữ hóa đơn'), 'error');
            }
        })
        .catch(error => {
            console.error(' Hold bill error:', error);
            showNotification(' Lỗi kết nối: ' + error.message, 'error');
        });
    }

    /**
     * Retrieve held bill
     * @param {number} heldBillId - Held bill ID
     */
    function retrieveHeldBill(heldBillId) {
        if (cart.items.length > 0) {
            if (!confirm('Giỏ hàng hiện tại sẽ bị xóa. Tiếp tục?')) {
                return;
            }
        }

        fetch(CONFIG.apiEndpoints.heldBills, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'retrieve',
                bill_id: heldBillId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartFromServer();
                loadHeldBills();
                showNotification('Đã khôi phục hóa đơn', 'success');
            } else {
                showNotification(data.message || 'Lỗi khôi phục hóa đơn', 'error');
            }
        })
        .catch(error => {
            console.error('Retrieve bill error:', error);
            showNotification('Lỗi kết nối', 'error');
        });
    }

    /**
     * Load held bills from server
     */
    function loadHeldBills() {
        console.log('📋 loadHeldBills() called');

        const url = CONFIG.apiEndpoints.heldBills + '?action=list';
        console.log('🌐 Fetching:', url);

        fetch(url)
            .then(response => {
                console.log('📡 Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📦 Load held bills response:', data);

                if (data.success) {
                    heldBills = data.data || data.heldBills || [];
                    console.log('✅ Loaded held bills:', heldBills.length);
                    updateHeldBillsUI();
                } else {
                    console.error(' Failed to load held bills:', data.message);
                    showNotification(data.message || 'Không thể tải hóa đơn tạm giữ', 'error');
                }
            })
            .catch(error => {
                console.error(' Load held bills error:', error);
                showNotification('Lỗi tải hóa đơn tạm giữ: ' + error.message, 'error');
            });
    }

    /**
     * Update held bills UI
     */
    function updateHeldBillsUI() {
        console.log('🔄 updateHeldBillsUI() called');
        console.log('Held bills count:', heldBills.length);

        const heldBillsContainer = document.getElementById('heldBillsList');
        const heldBillsCount = document.getElementById('heldBillsCount');

        if (!heldBillsContainer) {
            console.error(' heldBillsList container not found');
            return;
        }

        // Update count badge
        if (heldBillsCount) {
            heldBillsCount.textContent = heldBills.length;
        }

        if (heldBills.length === 0) {
            console.log('ℹ️ No held bills to display');
            heldBillsContainer.innerHTML = '<div class="text-center text-muted small">Không có hóa đơn tạm giữ</div>';
            return;
        }

        console.log('✅ Rendering', heldBills.length, 'held bills');

        let html = '';
        heldBills.forEach(bill => {
            const billName = bill.bill_name || bill.name || 'Hóa đơn #' + bill.id;
            const itemsCount = bill.items_count || bill.item_count || 0;
            const total = bill.total || 0;
            const createdAt = bill.created_at || '';

            html += `
                <div class="held-bill-item" onclick="retrieveHeldBill(${bill.id})">
                    <div class="held-bill-info">
                        <strong>${billName}</strong>
                        <small>${createdAt}</small>
                    </div>
                    <div class="held-bill-total">
                        ${formatCurrency(total)}
                    </div>
                </div>
            `;
        });

        heldBillsContainer.innerHTML = html;
        console.log('✅ Held bills UI updated');
    }

    // ==================== PAYMENT PROCESSING ====================

    let selectedPaymentMethod = null;

    /**
     * Select payment method
     */
    function selectPaymentMethod(method) {
        console.log('💳 selectPaymentMethod() called with:', method);

        selectedPaymentMethod = method;

        // Update UI - highlight selected method
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.classList.remove('active');
        });

        const selectedCard = document.querySelector(`.payment-method-card[data-method="${method}"]`);
        if (selectedCard) {
            selectedCard.classList.add('active');
        }

        // Show payment details based on method
        const paymentDetails = document.getElementById('paymentDetails');
        if (!paymentDetails) {
            console.error(' paymentDetails container not found');
            return;
        }

        switch (method) {
            case 'cash':
                showCashPaymentForm();
                break;
            case 'banking':
                showBankingPaymentForm();
                break;
            case 'momo':
                showMomoPaymentForm();
                break;
            case 'split':
                showSplitPaymentForm();
                break;
            default:
                paymentDetails.innerHTML = '<p class="text-muted">Chọn phương thức thanh toán</p>';
        }

        // Enable confirm button
        const confirmBtn = document.getElementById('confirmPaymentBtn');
        if (confirmBtn) {
            confirmBtn.disabled = false;
        }

        console.log('✅ Payment method selected:', method);
    }

    /**
     * Show cash payment form
     */
    function showCashPaymentForm() {
        console.log('💵 showCashPaymentForm() called');

        const total = cart.total || 0;
        const paymentDetails = document.getElementById('paymentDetails');

        paymentDetails.innerHTML = `
            <div class="cash-payment-form">
                <h5>💵 Thanh toán tiền mặt</h5>

                <div class="mb-3">
                    <label class="form-label">Tổng tiền phải trả:</label>
                    <div class="total-display">${formatCurrency(total)}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tiền khách đưa:</label>
                    <input type="number"
                           class="form-control form-control-lg"
                           id="cashReceived"
                           placeholder="Nhập số tiền..."
                           min="${total}"
                           value="${total}"
                           oninput="calculateChange()">
                </div>

                <div class="quick-amount-buttons mb-3">
                    <button class="btn btn-outline-primary" onclick="setCashAmount(${Math.ceil(total / 1000) * 1000})">
                        ${formatCurrency(Math.ceil(total / 1000) * 1000)}
                    </button>
                    <button class="btn btn-outline-primary" onclick="setCashAmount(${Math.ceil(total / 5000) * 5000})">
                        ${formatCurrency(Math.ceil(total / 5000) * 5000)}
                    </button>
                    <button class="btn btn-outline-primary" onclick="setCashAmount(${Math.ceil(total / 10000) * 10000})">
                        ${formatCurrency(Math.ceil(total / 10000) * 10000)}
                    </button>
                    <button class="btn btn-outline-primary" onclick="setCashAmount(${Math.ceil(total / 50000) * 50000})">
                        ${formatCurrency(Math.ceil(total / 50000) * 50000)}
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tiền thối:</label>
                    <div class="change-display" id="changeAmount">0 ₫</div>
                </div>
            </div>
        `;

        // Auto calculate change
        calculateChange();
    }

    /**
     * Set cash amount (quick buttons)
     */
    function setCashAmount(amount) {
        console.log('💵 setCashAmount:', amount);
        const cashInput = document.getElementById('cashReceived');
        if (cashInput) {
            cashInput.value = amount;
            calculateChange();
        }
    }

    /**
     * Calculate change
     */
    function calculateChange() {
        console.log('🧮 calculateChange() called');

        const total = cart.total || 0;
        const cashInput = document.getElementById('cashReceived');
        const changeDisplay = document.getElementById('changeAmount');

        if (!cashInput || !changeDisplay) return;

        const received = parseFloat(cashInput.value) || 0;
        const change = received - total;

        console.log('Total:', total, 'Received:', received, 'Change:', change);

        if (change >= 0) {
            changeDisplay.textContent = formatCurrency(change);
            changeDisplay.style.color = '#10b981';
        } else {
            changeDisplay.textContent = 'Chưa đủ tiền';
            changeDisplay.style.color = '#ef4444';
        }
    }

    /**
     * Show banking payment form
     */
    function showBankingPaymentForm() {
        const paymentDetails = document.getElementById('paymentDetails');
        paymentDetails.innerHTML = `
            <div class="banking-payment-form">
                <h5>🏦 Chuyển khoản ngân hàng</h5>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Tính năng đang phát triển
                </div>
            </div>
        `;
    }

    /**
     * Show MoMo payment form
     */
    function showMomoPaymentForm() {
        const paymentDetails = document.getElementById('paymentDetails');
        paymentDetails.innerHTML = `
            <div class="momo-payment-form">
                <h5>📱 Thanh toán MoMo</h5>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Tính năng đang phát triển
                </div>
            </div>
        `;
    }

    /**
     * Show split payment form
     */
    function showSplitPaymentForm() {
        const paymentDetails = document.getElementById('paymentDetails');
        paymentDetails.innerHTML = `
            <div class="split-payment-form">
                <h5>💳 Thanh toán kết hợp</h5>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    Tính năng đang phát triển
                </div>
            </div>
        `;
    }

    /**
     * Confirm payment
     */
    function confirmPayment() {
        console.log('✅ confirmPayment() called');
        console.log('Payment method:', selectedPaymentMethod);

        if (!selectedPaymentMethod) {
            showNotification('⚠️ Vui lòng chọn phương thức thanh toán', 'warning');
            return;
        }

        const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});

        if (items.length === 0) {
            showNotification('⚠️ Giỏ hàng trống', 'warning');
            return;
        }

        // Validate cash payment
        if (selectedPaymentMethod === 'cash') {
            const total = cart.total || 0;
            const cashInput = document.getElementById('cashReceived');
            const received = cashInput ? parseFloat(cashInput.value) || 0 : 0;

            if (received < total) {
                showNotification('⚠️ Số tiền khách đưa chưa đủ', 'warning');
                return;
            }
        }

        // Process payment
        console.log('🌐 Processing payment...');

        const paymentData = {
            action: 'process',  // Required by API
            payment_method: selectedPaymentMethod,
            customer_id: cart.customer ? cart.customer.id : null
        };

        // Add cash-specific data
        if (selectedPaymentMethod === 'cash') {
            const cashInput = document.getElementById('cashReceived');
            paymentData.cash_received = cashInput ? parseFloat(cashInput.value) || 0 : 0;
            paymentData.change_given = paymentData.cash_received - cart.total;
        }

        console.log('📤 Payment data:', paymentData);

        fetch(CONFIG.apiEndpoints.payment, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(paymentData)
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Payment response:', data);

            if (data.success) {
                console.log('✅ Payment successful');

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                if (modal) {
                    modal.hide();
                }

                // Clear cart
                clearCart();

                // Reset payment method
                selectedPaymentMethod = null;

                showNotification('✅ Thanh toán thành công! Mã đơn: ' + (data.order_id || ''), 'success');

                // TODO: Show invoice / print receipt
            } else {
                console.error(' Payment failed:', data.message);
                showNotification(' ' + (data.message || 'Lỗi thanh toán'), 'error');
            }
        })
        .catch(error => {
            console.error(' Payment error:', error);
            showNotification(' Lỗi kết nối: ' + error.message, 'error');
        });
    }

    /**
     * Switch payment method
     * @param {string} method - Payment method ('cash', 'banking', 'momo', 'split')
     */
    function switchPaymentMethod(method) {
        // Update active tab
        document.querySelectorAll('.payment-method-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.payment-method-tab[data-method="${method}"]`).classList.add('active');

        // Show corresponding payment panel
        document.querySelectorAll('.payment-panel').forEach(panel => {
            panel.style.display = 'none';
        });
        document.getElementById(`payment-${method}`).style.display = 'block';
    }

    /**
     * Calculate change for cash payment
     * @param {number} cashReceived - Amount of cash received
     */
    function calculateChange(cashReceived) {
        const change = cashReceived - cart.total;
        const changeDisplay = document.getElementById('change-amount');

        if (changeDisplay) {
            if (change >= 0) {
                changeDisplay.textContent = formatCurrency(change);
                changeDisplay.classList.remove('text-danger');
                changeDisplay.classList.add('text-success');
            } else {
                changeDisplay.textContent = formatCurrency(Math.abs(change)) + ' (Thiếu)';
                changeDisplay.classList.remove('text-success');
                changeDisplay.classList.add('text-danger');
            }
        }

        // Enable/disable payment button
        const processPaymentBtn = document.getElementById('process-payment-btn');
        if (processPaymentBtn) {
            processPaymentBtn.disabled = change < 0;
        }
    }

    /**
     * Process payment
     */
    function processPayment() {
        if (cart.items.length === 0) {
            showNotification('Giỏ hàng trống', 'warning');
            return;
        }

        // Get active payment method
        const activeTab = document.querySelector('.payment-method-tab.active');
        const paymentMethod = activeTab ? activeTab.dataset.method : 'cash';

        // Prepare payment data
        const paymentData = {
            method: paymentMethod,
            cart: cart,
            customer: cart.customer
        };

        // Add method-specific data
        if (paymentMethod === 'cash') {
            const cashReceived = parseFloat(document.getElementById('cash-received').value) || 0;
            if (cashReceived < cart.total) {
                showNotification('Số tiền nhận chưa đủ', 'error');
                return;
            }
            paymentData.cash_received = cashReceived;
            paymentData.change = cashReceived - cart.total;
        }

        // Show loading
        const processPaymentBtn = document.getElementById('process-payment-btn');
        if (processPaymentBtn) {
            processPaymentBtn.disabled = true;
            processPaymentBtn.innerHTML = '<span class="spinner"></span> Đang xử lý...';
        }

        // Make payment request
        fetch(CONFIG.apiEndpoints.payment, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'process',
                payment_method: paymentMethod,
                customer_id: cart.customer ? cart.customer.id : null,
                cash_received: paymentData.cash_received || 0,
                change_given: paymentData.change || 0,
                transaction_ref: paymentData.transaction_ref || null
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Thanh toán thành công!', 'success');

                // Redirect to invoice page
                if (data.invoice_url) {
                    window.location.href = data.invoice_url;
                } else if (data.order_id) {
                    // Or print directly
                    printInvoice(data.order_id);
                }

                // Clear cart
                clearCart();
            } else {
                showNotification(data.message || 'Lỗi thanh toán', 'error');
            }
        })
        .catch(error => {
            console.error('Payment error:', error);
            showNotification('Lỗi kết nối', 'error');
        })
        .finally(() => {
            if (processPaymentBtn) {
                processPaymentBtn.disabled = false;
                processPaymentBtn.innerHTML = 'Xác nhận thanh toán';
            }
        });
    }

    /**
     * Print invoice
     * @param {number} orderId - Order ID
     */
    function printInvoice(orderId) {
        // Open print window
        const printWindow = window.open('/pos/invoice/' + orderId, '_blank');
        if (printWindow) {
            printWindow.addEventListener('load', function() {
                printWindow.print();
            });
        }
    }

    // ==================== LOCAL STORAGE BACKUP ====================

    /**
     * Save cart to localStorage as backup
     */
    function saveCartBackup() {
        try {
            localStorage.setItem(CONFIG.localStorageKey, JSON.stringify(cart));
        } catch (e) {
            console.error('Error saving cart backup:', e);
        }
    }

    /**
     * Load cart from localStorage backup
     */
    function loadCartBackup() {
        try {
            const backup = localStorage.getItem(CONFIG.localStorageKey);
            if (backup) {
                const savedCart = JSON.parse(backup);
                // Only restore if cart is not empty
                if (savedCart.items && savedCart.items.length > 0) {
                    cart = savedCart;
                }
            }
        } catch (e) {
            console.error('Error loading cart backup:', e);
        }
    }

    /**
     * Clear cart backup from localStorage
     */
    function clearCartBackup() {
        try {
            localStorage.removeItem(CONFIG.localStorageKey);
        } catch (e) {
            console.error('Error clearing cart backup:', e);
        }
    }

    // ==================== UTILITY FUNCTIONS ====================

    /**
     * Format number as currency
     * @param {number} amount - Amount to format
     * @returns {string} Formatted currency string
     */
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    /**
     * Get CSRF token
     * @returns {string} CSRF token
     */
    function getCSRFToken() {
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return tokenMeta ? tokenMeta.content : '';
    }

    /**
     * Show notification
     * @param {string} message - Notification message
     * @param {string} type - Notification type ('success', 'error', 'warning', 'info')
     */
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon"></span>
                <span class="notification-message">${message}</span>
            </div>
        `;

        // Add to body
        document.body.appendChild(notification);

        // Show with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto hide after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // ==================== MISSING FUNCTIONS ====================

    /**
     * Proceed to payment - Open payment modal
     */
    function proceedToPayment() {
        console.log('💳 proceedToPayment() called');
        console.log('Cart items:', cart.items);
        console.log('Cart total:', cart.total);

        const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});

        if (items.length === 0) {
            console.warn('⚠️ Cart is empty');
            showNotification('⚠️ Giỏ hàng trống', 'warning');
            return;
        }

        console.log('✅ Opening payment modal');
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        paymentModal.show();

        // Auto-select cash payment and show form
        setTimeout(() => {
            selectedPaymentMethod = 'cash';
            showCashPaymentForm();
            document.getElementById('confirmPaymentBtn').disabled = false;
        }, 100);

        showNotification('💵 Nhập tiền khách đưa', 'info');
    }

    /**
     * Cancel order - Clear cart and reset
     */
    function cancelOrder() {
        console.log(' cancelOrder() called');

        const items = Array.isArray(cart.items) ? cart.items : Object.values(cart.items || {});

        if (items.length === 0) {
            console.log('ℹ️ Cart already empty');
            showNotification('ℹ️ Giỏ hàng đã trống', 'info');
            return;
        }

        if (confirm('⚠️ Bạn có chắc muốn hủy đơn hàng này?')) {
            console.log('✅ User confirmed cancellation');
            clearCart();
            clearCustomer();
            showNotification('✅ Đã hủy đơn hàng', 'success');
        } else {
            console.log(' User cancelled cancellation');
        }
    }

    /**
     * Show add customer modal
     */
    function showAddCustomerModal(keyword = '') {
        console.log('👤 showAddCustomerModal() called with keyword:', keyword);

        const modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));

        // Pre-fill phone if keyword looks like a phone number
        if (keyword && /^[0-9]+$/.test(keyword)) {
            const phoneInput = document.querySelector('#addCustomerForm input[name="sdt"]');
            if (phoneInput) {
                phoneInput.value = keyword;
            }
        }

        modal.show();
    }

    /**
     * Save new customer
     */
    function saveCustomer() {
        console.log('💾 saveCustomer() called');

        const form = document.getElementById('addCustomerForm');
        const formData = new FormData(form);

        const customerData = {
            ten: formData.get('ten'),
            sdt: formData.get('sdt'),
            email: formData.get('email'),
            diachi: formData.get('diachi')
        };

        console.log('📤 Customer data:', customerData);

        if (!customerData.ten || !customerData.sdt) {
            console.warn('⚠️ Missing required fields');
            showNotification('⚠️ Vui lòng nhập tên và số điện thoại', 'warning');
            return;
        }

        fetch(CONFIG.apiEndpoints.customer, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(customerData)
        })
        .then(response => {
            console.log('📡 Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Save customer response:', data);

            if (data.success) {
                console.log('✅ Customer saved successfully');
                showNotification('✅ Đã thêm khách hàng: ' + customerData.ten, 'success');

                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                modal.hide();

                // Clear form
                form.reset();

                // Auto-select the new customer
                if (data.customer) {
                    selectCustomer(data.customer);
                }
            } else {
                console.error(' Failed to save customer:', data.message);
                showNotification(' ' + (data.message || 'Lỗi thêm khách hàng'), 'error');
            }
        })
        .catch(error => {
            console.error(' Save customer error:', error);
            showNotification(' Lỗi kết nối: ' + error.message, 'error');
        });
    }

    // ==================== INITIALIZATION ====================

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export functions for global access if needed
    window.POSSystem = {
        searchProducts,
        loadProducts,
        addToCart,
        updateCartQuantity,
        removeFromCart,
        clearCart,
        checkStock,
        applyVoucher,
        applyDiscount,
        applyPromotionCode,
        holdBill,
        retrieveHeldBill,
        loadHeldBills,
        calculateChange,
        processPayment,
        updateCartUI,
        updateCartFromServer,
        showNotification
    };

    // Expose key functions globally for inline event handlers
    window.loadProducts = loadProducts;
    window.applyPromotionCode = applyPromotionCode;
    window.searchProducts = searchProducts;
    window.addToCart = addToCart;
    window.updateCartQuantity = updateCartQuantity;
    window.removeFromCart = removeFromCart;
    window.clearCart = clearCart;
    window.updateCartUI = updateCartUI;
    window.updateCartFromServer = updateCartFromServer;
    window.holdBill = holdBill;
    window.confirmHoldBill = confirmHoldBill;
    window.retrieveHeldBill = retrieveHeldBill;
    window.processPayment = processPayment;
    window.proceedToPayment = proceedToPayment;
    window.cancelOrder = cancelOrder;
    window.showNotification = showNotification;
    window.searchCustomer = searchCustomer;
    window.selectCustomer = selectCustomer;
    window.clearCustomer = clearCustomer;
    window.showAddCustomerModal = showAddCustomerModal;
    window.saveCustomer = saveCustomer;
    window.applyVoucher = applyVoucher;
    window.clearDiscount = clearDiscount;
    window.applyManualDiscount = applyManualDiscount;
    window.updateDiscountPreview = updateDiscountPreview;
    window.selectPaymentMethod = selectPaymentMethod;
    window.setCashAmount = setCashAmount;
    window.calculateChange = calculateChange;
    window.confirmPayment = confirmPayment;

})();
