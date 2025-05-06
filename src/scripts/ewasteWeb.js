// Back to top button functionality
const upButton = document.getElementById("upButton");

// Show button when scrolling down
window.onscroll = function () {
    if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
        upButton.style.display = "block";
    } else {
        upButton.style.display = "none";
    }
};

// Scroll to top when button is clicked
upButton.onclick = function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
};

// Login/Signup Form Toggle
document.addEventListener("DOMContentLoaded", function () {
    const toggleFormLink = document.getElementById("toggleForm");
    const loginForm = document.getElementById("loginForm");
    const signupForm = document.getElementById("signupForm");
    const formTitle = document.getElementById("formTitle");
    const formToggleText = document.getElementById("formToggleText");
    const loginBackground = document.querySelector('.login-background');
    const signupBackground = document.querySelector('.signup-background');

    function toggleForms() {
        if (signupForm.classList.contains("hidden")) {
            signupForm.classList.remove("hidden");
            loginForm.classList.add("hidden");
            formTitle.textContent = "Sign Up";
            formToggleText.innerHTML = 'Already have an account? <a href="#" id="toggleForm">Log in</a>';
            // Toggle background if elements exist
            if (loginBackground && signupBackground) {
                loginBackground.classList.add("hidden");
                signupBackground.classList.remove("hidden");
            }
        } else {
            loginForm.classList.remove("hidden");
            signupForm.classList.add("hidden");
            formTitle.textContent = "Log in";
            formToggleText.innerHTML = 'New to site? <a href="#" id="toggleForm">Sign up</a>';
            // Toggle background if elements exist
            if (loginBackground && signupBackground) {
                loginBackground.classList.remove("hidden");
                signupBackground.classList.add("hidden");
            }
        }
    }

    document.body.addEventListener("click", function (event) {
        if (event.target && event.target.id === "toggleForm") {
            event.preventDefault();
            toggleForms();
        }
    });
});

// Category Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
    const categoryFilter = document.getElementById('category-filter');
    const products = document.querySelectorAll('.product-card');
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            const selectedCategory = categoryFilter.value; 
            
            products.forEach(function(product) {
                const productCategory = product.getAttribute('value');  
                
                if (selectedCategory === 'all' || selectedCategory === productCategory) {
                    product.style.display = 'block'; 
                } else {
                    product.style.display = 'none';  
                }
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = this.getAttribute('data-product-price');
            const productImage = this.getAttribute('data-product-image');
            const productStock = parseInt(this.getAttribute('data-product-stock') || '10');
            
            // Add to cart logic
            addToCart(productId, productName, productPrice, productImage, productStock);
            
            // Show confirmation notification
            showNotification(`${productName} added to cart!`);
            
            // Open cart after adding item (optional)
            const cartElement = document.querySelector('.cart');
            if (cartElement) {
                cartElement.classList.add('active');
            }
            
            // Prevent default action if it's an anchor tag
            if (this.tagName === 'A') {
                event.preventDefault();
            }
        });
    });
    
    // Cart toggle functionality 
    const cartIcon = document.querySelector('.iconCart');
    const cartElement = document.querySelector('.cart');
    const closeCartButton = document.querySelector('.close-btn');
    const checkoutAllButton = document.querySelector('.checkout-all-btn');

    if (cartIcon) {
        cartIcon.addEventListener('click', function() {
            if (cartElement) {
                cartElement.classList.add('active');
            }
        });
    }

    if (closeCartButton) {
        closeCartButton.addEventListener('click', function() {
            if (cartElement) {
                cartElement.classList.remove('active');
            }
        });
    }

    if (checkoutAllButton) {
        checkoutAllButton.addEventListener('click', function() {
            // Checkout all items functionality
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            if (cart.length > 0) {
                showNotification('Processing checkout for all items');
            } else {
                showNotification('Your cart is empty');
            }
        });
    }
    
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const cartItemCheckboxes = document.querySelectorAll('.cart-item-checkbox:not([disabled])');
            cartItemCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedTotal();
        });
    }
    
    // Delete selected and Checkout selected buttons
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const checkoutSelectedBtn = document.getElementById('checkoutSelectedBtn');
    
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function() {
            removeSelectedItems();
        });
    }
    
    if (checkoutSelectedBtn) {
        checkoutSelectedBtn.addEventListener('click', function() {
            checkoutSelectedItems();
        });
    }
    
    function addToCart(productId, productName, productPrice, productImage, productStock) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            // Increment quantity if already in cart
            existingItem.quantity += 1;
        } else {
            // Add new item to cart
            cart.push({
                id: productId,
                name: productName,
                price: parseFloat(productPrice),
                image: productImage || '/path/to/default-image.jpg',
                quantity: 1,
                stock: productStock
            });
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount(cart.reduce((total, item) => total + item.quantity, 0));
        renderCart();
    }
    
    function updateCartCount(count) {
        const totalQuantity = document.querySelector('.totalQuantity');
        if (totalQuantity) {
            totalQuantity.textContent = count;
        }
    }
    
    function renderCart() {
        const cartContainer = document.querySelector('.listCart');
        if (!cartContainer) return;
        
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (cart.length === 0) {
            cartContainer.innerHTML = '<p style="color: white; text-align: center; padding: 20px;">Your cart is empty</p>';
            return;
        }
        
        let cartHTML = '';
        
        cart.forEach((item, index) => {
            // Add checkbox for item selection
            const isSelectable = item.stock > 0 && item.quantity <= item.stock;
            
            cartHTML += `
            <div class="item" data-product-id="${item.id}">
                <input type="checkbox" class="cart-item-checkbox" ${!isSelectable ? 'disabled' : ''}>
                <img src="${item.image}" alt="${item.name}">
                <div class="content">
                    <div class="name">${item.name}</div>
                    <div class="price">P ${item.price.toFixed(2)} / 1 product</div>
                    <div class="stock-info">Stock: ${item.stock}</div>
                    ${item.stock <= 0 ? '<div class="stock-warning">Out of stock!</div>' : 
                    item.quantity > item.stock ? `<div class="stock-warning">Only ${item.stock} available!</div>` : ''}
                </div>
                <div class="quantity">
                    <button class="decrease" data-index="${index}">-</button>
                    <span class="value">${item.quantity}</span>
                    <button class="increase" data-index="${index}" ${item.quantity >= item.stock ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>+</button>
                </div>
            </div>
            `;
        });
        
        cartContainer.innerHTML = cartHTML;
        
        document.querySelectorAll('.decrease').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                decreaseQuantity(index);
            });
        });
        
        document.querySelectorAll('.increase').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                increaseQuantity(index);
            });
        });
        
        document.querySelectorAll('.cart-item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedTotal();
            });
        });
        
        updateSelectedTotal();
    }
    
    function decreaseQuantity(index) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart[index].quantity > 1) {
            cart[index].quantity -= 1;
        } else {
            cart.splice(index, 1);
        }
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount(cart.reduce((total, item) => total + item.quantity, 0));
        renderCart();
    }
    
    function increaseQuantity(index) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart[index].quantity < cart[index].stock) {
            cart[index].quantity += 1;
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount(cart.reduce((total, item) => total + item.quantity, 0));
            renderCart();
        }
    }
    
    function updateSelectedTotal() {
        const selectedTotalElement = document.querySelector('.selected-total-price');
        if (!selectedTotalElement) return;
        
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const checkboxes = document.querySelectorAll('.cart-item-checkbox');
        
        let selectedTotal = 0;
        let hasSelectedItems = false;
        
        checkboxes.forEach((checkbox, index) => {
            if (checkbox.checked) {
                hasSelectedItems = true;
                selectedTotal += cart[index].price * cart[index].quantity;
            }
        });
        
        selectedTotalElement.textContent = `P ${selectedTotal.toFixed(2)}`;
        
        // Update buttons state
        if (deleteSelectedBtn) {
            if (hasSelectedItems) {
                deleteSelectedBtn.classList.remove('disabled');
            } else {
                deleteSelectedBtn.classList.add('disabled');
            }
        }
        
        if (checkoutSelectedBtn) {
            if (hasSelectedItems) {
                checkoutSelectedBtn.classList.remove('disabled');
            } else {
                checkoutSelectedBtn.classList.add('disabled');
            }
        }
        
        // Update select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox && checkboxes.length > 0) {
            selectAllCheckbox.checked = Array.from(checkboxes).every(checkbox => checkbox.disabled || checkbox.checked);
            selectAllCheckbox.indeterminate = !selectAllCheckbox.checked && Array.from(checkboxes).some(checkbox => checkbox.checked);
        }
    }
    
    function removeSelectedItems() {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const checkboxes = document.querySelectorAll('.cart-item-checkbox');
        
        // Create a new cart with only unselected items
        const newCart = cart.filter((_, index) => {
            const checkbox = checkboxes[index];
            return !checkbox || !checkbox.checked;
        });
        
        localStorage.setItem('cart', JSON.stringify(newCart));
        updateCartCount(newCart.reduce((total, item) => total + item.quantity, 0));
        renderCart();
        showNotification('Selected items removed from cart');
    }
    
    function checkoutSelectedItems() {
        showNotification('Proceeding to checkout with selected items');
    }
    
    function showNotification(message) {
        // Check if notification container exists
        let notificationContainer = document.querySelector('.notification-container');
        
        // Create notification container if it doesn't exist
        if (!notificationContainer) {
            notificationContainer = document.createElement('div');
            notificationContainer.className = 'notification-container';
            document.body.appendChild(notificationContainer);
            
            // Style the container
            notificationContainer.style.position = 'fixed';
            notificationContainer.style.top = '20px';
            notificationContainer.style.right = '20px';
            notificationContainer.style.zIndex = '1000';
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        
        // Style the notification
        notification.style.background = '#4CAF50';
        notification.style.color = 'white';
        notification.style.padding = '12px 24px';
        notification.style.marginBottom = '10px';
        notification.style.borderRadius = '4px';
        notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        notification.style.transition = 'opacity 0.5s';
        
        // Add to container
        notificationContainer.appendChild(notification);
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 3000);
    }
    
    // cart on page load
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    updateCartCount(cart.reduce((total, item) => total + item.quantity, 0));
    renderCart();
    
    const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    updateWishlistCount(wishlist.length);
});

// Image Carousel Functionality
document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.carousel');
    
    carousels.forEach(function(carousel) {
        const slides = carousel.querySelectorAll('.carousel-slide');
        const nextButton = carousel.querySelector('.carousel-next');
        const prevButton = carousel.querySelector('.carousel-prev');
        let currentSlide = 0;
        
        for (let i = 1; i < slides.length; i++) {
            slides[i].style.display = 'none';
        }

        if (nextButton) {
            nextButton.addEventListener('click', function() {
                slides[currentSlide].style.display = 'none';
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].style.display = 'block';
            });
        }
        
        if (prevButton) {
            prevButton.addEventListener('click', function() {
                slides[currentSlide].style.display = 'none';
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                slides[currentSlide].style.display = 'block';
            });
        }
        
        // Auto advance slides every 5 seconds
        setInterval(function() {
            if (nextButton) {
                nextButton.click();
            } else {
                slides[currentSlide].style.display = 'none';
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].style.display = 'block';
            }
        }, 5000);
    });
});

// Form Validation for Login/Signup forms
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const email = loginForm.querySelector('input[type="email"]');
            const password = loginForm.querySelector('input[type="password"]');
            
            if (email && !validateEmail(email.value)) {
                event.preventDefault();
                showValidationError(email, 'Please enter a valid email address');
            }
            
            if (password && password.value.length < 6) {
                event.preventDefault();
                showValidationError(password, 'Password must be at least 6 characters');
            }
        });
    }
    
    if (signupForm) {
        signupForm.addEventListener('submit', function(event) {
            const email = signupForm.querySelector('input[type="email"]');
            const password = signupForm.querySelector('input[type="password"]');
            const confirmPassword = signupForm.querySelector('input[name="confirm-password"]');
            
            if (email && !validateEmail(email.value)) {
                event.preventDefault();
                showValidationError(email, 'Please enter a valid email address');
            }
            
            if (password && password.value.length < 6) {
                event.preventDefault();
                showValidationError(password, 'Password must be at least 6 characters');
            }
            
            if (confirmPassword && password && confirmPassword.value !== password.value) {
                event.preventDefault();
                showValidationError(confirmPassword, 'Passwords do not match');
            }
        });
    }
    
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function showValidationError(inputElement, message) {
        const parent = inputElement.parentElement;
        const existingError = parent.querySelector('.error-message');
        
        if (existingError) {
            existingError.remove();
        }
        
        // Create and add error message
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        errorElement.style.color = 'red';
        errorElement.style.fontSize = '12px';
        errorElement.style.marginTop = '5px';
        inputElement.insertAdjacentElement('afterend', errorElement);
        inputElement.style.borderColor = 'red';
        inputElement.addEventListener('input', function() {
            errorElement.remove();
            inputElement.style.borderColor = '';
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    loadWishlistState();
    loadCartState();
});

function loadWishlistState() {
    const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    const wishlistButtons = document.querySelectorAll('.wishlist-btn');
    
    wishlistButtons.forEach(function(button) {
        const productId = button.getAttribute('data-product-id');
        
        if (wishlist.includes(productId)) {
            button.classList.add('active');
        }
    });
}

function loadCartState() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartItemsList = document.querySelector('.cart-items');
    
    if (cartItemsList) {
        if (cart.length === 0) {
            cartItemsList.innerHTML = '<p>Your cart is empty</p>';
        } else {
            let cartHTML = '';
            let totalPrice = 0;
            
            cart.forEach(function(item) {
                const itemTotal = parseFloat(item.price) * item.quantity;
                totalPrice += itemTotal;
                
                cartHTML += `
                <div class="cart-item" data-product-id="${item.id}">
                    <div class="cart-item-details">
                        <h3>${item.name}</h3>
                        <p>Price: $${item.price}</p>
                        <div class="quantity-controls">
                            <button class="decrease-quantity">-</button>
                            <span class="item-quantity">${item.quantity}</span>
                            <button class="increase-quantity">+</button>
                        </div>
                    </div>
                    <div class="cart-item-price">
                        $${itemTotal.toFixed(2)}
                    </div>
                    <button class="remove-from-cart">×</button>
                </div>
                `;
            });
            
            cartItemsList.innerHTML = cartHTML;
            const decreaseButtons = document.querySelectorAll('.decrease-quantity');
            const increaseButtons = document.querySelectorAll('.increase-quantity');
            const removeButtons = document.querySelectorAll('.remove-from-cart');
            
            decreaseButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    const productId = cartItem.getAttribute('data-product-id');
                    updateCartItemQuantity(productId, -1);
                });
            });
            
            increaseButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    const productId = cartItem.getAttribute('data-product-id');
                    updateCartItemQuantity(productId, 1);
                });
            });
            
            removeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const cartItem = this.closest('.cart-item');
                    const productId = cartItem.getAttribute('data-product-id');
                    removeCartItem(productId);
                });
            });
            
            const totalElement = document.querySelector('.cart-total');
            if (totalElement) {
                totalElement.textContent = `$${totalPrice.toFixed(2)}`;
            }
        }
    }
}

function updateCartItemQuantity(productId, change) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const item = cart.find(item => item.id === productId);
    
    if (item) {
        item.quantity += change;
        
        if (item.quantity <= 0) {
            removeCartItem(productId);
            return;
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        loadCartState();
        updateCartCount(cart.reduce((total, item) => total + item.quantity, 0));
    }
}

function removeCartItem(productId) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.id !== productId);
    
    localStorage.setItem('cart', JSON.stringify(cart));
    
    loadCartState();
    updateCartCount(cart.reduce((total, item) => total + item.quantity, 0));
    
    showNotification('Item removed from cart');
}

function updateCartCount(count) {
    const cartCounter = document.querySelector('.cart-count');
    if (cartCounter) {
        cartCounter.textContent = count;
    }
}

function showNotification(message) {
    let notificationContainer = document.querySelector('.notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
        
        notificationContainer.style.position = 'fixed';
        notificationContainer.style.top = '20px';
        notificationContainer.style.right = '20px';
        notificationContainer.style.zIndex = '1000';
    }
    
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    

    notification.style.background = '#4CAF50';
    notification.style.color = 'white';
    notification.style.padding = '12px 24px';
    notification.style.marginBottom = '10px';
    notification.style.borderRadius = '4px';
    notification.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
    notification.style.transition = 'opacity 0.5s';
    
    notificationContainer.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 3000);

    function getSelectedItems() {
        const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
        const selectedItems = [];
        
        checkboxes.forEach(checkbox => {
            const item = checkbox.closest('.item');
            if (item) {
                const productId = item.getAttribute('data-product-id');
                if (productId) {
                    selectedItems.push(productId);
                }
            }
        });
        
        return selectedItems;
    }
}