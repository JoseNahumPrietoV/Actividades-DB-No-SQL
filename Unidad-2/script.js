// Elementos del DOM
const loginBtn = document.getElementById('loginBtn');
const registerBtn = document.getElementById('registerBtn');
const accountBtn = document.getElementById('accountBtn');
const cartBtn = document.getElementById('cartBtn');
const logoutBtn = document.getElementById('logoutBtn');
const loginModal = document.getElementById('loginModal');
const registerModal = document.getElementById('registerModal');
const cartModal = document.getElementById('cartModal');
const closeModalButtons = document.querySelectorAll('.close-modal');
const switchToRegister = document.getElementById('switchToRegister');
const switchToLogin = document.getElementById('switchToLogin');
const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const searchInput = document.getElementById('searchInput');
const popularGamesContainer = document.getElementById('popularGames');
const saleGamesContainer = document.getElementById('saleGames');
const cartItemsContainer = document.getElementById('cartItems');
const cartTotalElement = document.getElementById('cartTotal');
const cartCountElement = document.getElementById('cartCount');
const checkoutBtn = document.getElementById('checkoutBtn');
const categoryLinks = document.querySelectorAll('nav ul li a');
const notification = document.getElementById('notification');

// Estado de la aplicación
let currentUser = null;
let cart = [];
let cartItemCount = 0;

// Inicializar la aplicación
document.addEventListener('DOMContentLoaded', () => {
    checkAuthStatus();
    loadGames();
    loadCart();
    
    // Agregar event listeners para botones de edición
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            handleEditGame(this);
        });
    });
    
    // Agregar event listeners para botones de detalles
    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            handleShowGameDetails(this);
        });
    });
    
    // Agregar event listeners para botones de eliminación
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            handleDeleteGame(this);
        });
    });
    
    // Configurar event listeners
    setupEventListeners();
});

// Verificar estado de autenticación
function checkAuthStatus() {
    if (window.userSession && window.userSession.isLoggedIn) {
        currentUser = {
            id: window.userSession.userId,
            name: window.userSession.userName,
            role: window.userSession.userRole
        };
    }
    updateUserInterface();
}

// Función para inicializar el carrusel (si aún no existe)
function initHeroSlider() {
    if (!document.getElementById('heroSlider')) return;
    
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const slidesContainer = document.getElementById('slidesContainer');
    const dotsContainer = document.getElementById('sliderDots');
    
    // Crear dots de navegación
    function createDots() {
        dotsContainer.innerHTML = '';
        slides.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.className = 'dot';
            if (index === currentSlide) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(index));
            dotsContainer.appendChild(dot);
        });
    }
    
    function goToSlide(index) {
        currentSlide = index;
        updateSlider();
    }
    
    function updateSlider() {
        if (slidesContainer) {
            slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
        }
        
        // Actualizar dots
        document.querySelectorAll('.dot').forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });
    }
    
    // Event listeners para botones de navegación
    document.getElementById('nextSlide')?.addEventListener('click', () => {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlider();
    });
    
    document.getElementById('prevSlide')?.addEventListener('click', () => {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        updateSlider();
    });
    
    // Auto-play
    let sliderInterval = setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlider();
    }, 5000);
    
    // Pausar auto-play al interactuar
    const heroSlider = document.getElementById('heroSlider');
    if (heroSlider) {
        heroSlider.addEventListener('mouseenter', () => clearInterval(sliderInterval));
        heroSlider.addEventListener('mouseleave', () => {
            sliderInterval = setInterval(() => {
                currentSlide = (currentSlide + 1) % slides.length;
                updateSlider();
            }, 5000);
        });
    }
    
    // Inicializar
    createDots();
    
    // Click en slide para ver detalles
    document.querySelectorAll('.slide').forEach(slide => {
        slide.addEventListener('click', function() {
            const gameId = this.getAttribute('data-game-id');
            if (gameId) {
                window.location.href = `game_detail.php?id=${gameId}`;
            }
        });
    });
}

// Llamar a initHeroSlider cuando se cargue el DOM
document.addEventListener('DOMContentLoaded', () => {
    initHeroSlider();
    // ... resto del código existente
});
// Configurar event listeners
function setupEventListeners() {
    // Mostrar modales
    if (loginBtn) loginBtn.addEventListener('click', () => showModal(loginModal));
    if (registerBtn) registerBtn.addEventListener('click', () => showModal(registerModal));
    if (cartBtn) cartBtn.addEventListener('click', () => showModal(cartModal));
    if (logoutBtn) logoutBtn.addEventListener('click', logout);
    const distribuirBtn = document.getElementById('distribuirBtn');
    const devRegisterModal = document.getElementById('devRegisterModal');
    
    // En setupEventListeners() en script.js
const exploreBtn = document.getElementById('exploreBtn');
if (exploreBtn) exploreBtn.addEventListener('click', () => {
    window.location.href = 'explore.php';
});

if (distribuirBtn) {
    distribuirBtn.addEventListener('click', () => showModal(devRegisterModal));
}

    
    const wishlistBtn = document.getElementById('wishlistBtn');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', () => {
            window.location.href = 'wishlist.php';
        });
    }
    
    // Cerrar modales
    closeModalButtons.forEach(button => {
        button.addEventListener('click', closeModals);
    });
    
    // Cambiar entre modales
    if (switchToRegister) switchToRegister.addEventListener('click', () => {
        loginModal.style.display = 'none';
        registerModal.style.display = 'flex';
    });
    
    if (switchToLogin) switchToLogin.addEventListener('click', () => {
        registerModal.style.display = 'none';
        loginModal.style.display = 'flex';
    });
    
    // Manejar envío de formularios
    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);
    
    // Búsqueda
    if (searchInput) searchInput.addEventListener('input', debounce(handleSearch, 300));
    
    // Categorías
    categoryLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const category = link.getAttribute('data-category');
            loadGamesByCategory(category);
            
            // Actualizar clase activa
            categoryLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
    
    // Checkout
    if (checkoutBtn) checkoutBtn.addEventListener('click', handleCheckout);
    
    // Cerrar modal al hacer clic fuera del contenido
    window.addEventListener('click', (event) => {
        if (event.target === loginModal) loginModal.style.display = 'none';
        if (event.target === registerModal) registerModal.style.display = 'none';
        if (event.target === cartModal) cartModal.style.display = 'none';
    });
    
    window.addEventListener('click', (event) => {
    // ... otros modales existentes ...
    if (event.target === devRegisterModal) {
        devRegisterModal.style.display = 'none';
    }
});
}

// Cargar juegos
function loadGames() {
    // Cargar juegos populares
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_games&limit=4'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayGames(data.games, popularGamesContainer);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al cargar los juegos', 'error');
    });
    
    // Cargar juegos en oferta
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_games'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const saleGames = data.games.filter(game => game.descuento > 0);
            displayGames(saleGames, saleGamesContainer);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al cargar las ofertas', 'error');
    });
}

// Cargar juegos por categoría
function loadGamesByCategory(category) {
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_games&category=${category}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Limpiar contenedores y mostrar solo juegos de la categoría seleccionada
            popularGamesContainer.innerHTML = '';
            saleGamesContainer.innerHTML = '';
            displayGames(data.games, popularGamesContainer);
            
            // Actualizar título
            document.querySelector('.section-title').textContent = 
                category === 'all' ? 'Todos los Juegos' : `Juegos de ${category}`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al cargar la categoría', 'error');
    });
}

// Mostrar juegos en el contenedor
function displayGames(games, container) {
    container.innerHTML = '';
    
    if (games.length === 0) {
        container.innerHTML = '<p>No se encontraron juegos</p>';
        return;
    }
    
    games.forEach(game => {
        const gameCard = createGameCard(game);
        container.appendChild(gameCard);
    });
}

// Función para editar juego usando data attributes
function handleEditGame(button) {
    const gameId = button.getAttribute('data-game-id');
    const titulo = button.getAttribute('data-game-title');
    const descripcion = button.getAttribute('data-game-description');
    const precio = button.getAttribute('data-game-price');
    const descuento = button.getAttribute('data-game-discount');
    const categoria = button.getAttribute('data-game-category');
    const imagen_url = button.getAttribute('data-game-image');
    const requisitos = button.getAttribute('data-game-requirements');
    
    editGame(gameId, titulo, descripcion, precio, descuento, categoria, imagen_url, requisitos);
}

// Función para mostrar detalles usando data attributes
function handleShowGameDetails(button) {
    const gameId = button.getAttribute('data-game-id');
    const titulo = button.getAttribute('data-game-title');
    const descripcion = button.getAttribute('data-game-description');
    const precio = button.getAttribute('data-game-price');
    const descuento = button.getAttribute('data-game-discount');
    const categoria = button.getAttribute('data-game-category');
    const imagen_url = button.getAttribute('data-game-image');
    const requisitos = button.getAttribute('data-game-requirements');
    const estado = button.getAttribute('data-game-status');
    const fecha = button.getAttribute('data-game-date');
    
    showGameDetails(gameId, titulo, descripcion, precio, descuento, categoria, imagen_url, requisitos, estado, fecha);
}

// Función para manejar eliminación
function handleDeleteGame(button) {
    const gameId = button.getAttribute('data-game-id');
    const gameName = button.getAttribute('data-game-title');
    showDeleteConfirm(gameId, gameName);
}

function createGameCard(game) {
    const gameCard = document.createElement('div');
    gameCard.classList.add('game-card');
    
    // Convertir precio a número
    const precio = parseFloat(game.precio) || 0;
    const descuento = parseInt(game.descuento) || 0;
    
    const finalPrice = descuento > 0 ? 
        (precio * (1 - descuento / 100)).toFixed(2) : 
        precio.toFixed(2);
    
    gameCard.innerHTML = `
        <div class="game-image" style="background-image: url('${game.imagen_url || 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'}')">
            ${descuento > 0 ? `<div class="discount-badge">-${descuento}%</div>` : ''}
            
            <!-- Botón de wishlist -->
            <div class="wishlist-icon" data-game-id="${game.id}">
                <i class="far fa-heart"></i>
            </div>
            
            <div class="game-overlay">
                <button class="add-to-cart-btn" data-game-id="${game.id}" title="Agregar al carrito">
                    <i class="fas fa-shopping-cart"></i>
                </button>
                <button class="view-details-btn" data-game-id="${game.id}" title="Ver detalles">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>
        </div>
        <div class="game-info-simple">
            <div class="game-title">${game.titulo || 'Juego sin nombre'}</div>
            <div class="game-price-simple">
                ${descuento > 0 ? 
                    `<div class="price-with-discount">
                        <span class="original-price">$${precio.toFixed(2)}</span>
                        <span class="current-price">$${finalPrice}</span>
                    </div>` : 
                    `<span class="current-price">${precio > 0 ? '$' + finalPrice : 'Gratis'}</span>`
                }
            </div>
        </div>
    `;

    // Agregar evento al botón de wishlist
    const wishlistBtn = gameCard.querySelector('.wishlist-icon');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleWishlist(game.id, wishlistBtn);
        });
    }
    
    // Verificar estado inicial de wishlist
    if (window.userSession && window.userSession.isLoggedIn) {
    checkWishlistStatus(game.id, function(inWishlist) {
        if (inWishlist && wishlistBtn) {
            wishlistBtn.classList.add('active');
            wishlistBtn.innerHTML = '<i class="fas fa-heart"></i>';
        }
    });
}
    
    // Agregar evento al botón de carrito
    const addToCartBtn = gameCard.querySelector('.add-to-cart-btn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            addToCart(game.id);
        });
    }
    
    // Agregar evento al botón de detalles
    const viewDetailsBtn = gameCard.querySelector('.view-details-btn');
    if (viewDetailsBtn) {
        viewDetailsBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            window.location.href = `game_detail.php?id=${game.id}`;
        });
    }
    
    // Hacer click en toda la tarjeta redirige a detalles
    gameCard.addEventListener('click', () => {
        window.location.href = `game_detail.php?id=${game.id}`;
    });
    
    return gameCard;
}

// Función para verificar estado de wishlist
function checkWishlistStatus(gameId, button) {
    console.log('Verificando estado de wishlist para gameId:', gameId);
    
    if (!window.userSession || !window.userSession.isLoggedIn) {
        return false;
    }
    
    fetch('process_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=check_wishlist_status&game_id=${gameId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta de check_wishlist_status:', data);
        if (data.success && data.in_wishlist && button) {
            button.classList.add('active');
            button.innerHTML = '<i class="fas fa-heart"></i>';
        }
    })
    .catch(error => {
        console.error('Error al verificar wishlist:', error);
    });
}

// Manejar búsqueda
function handleSearch() {
    const searchTerm = searchInput.value.trim();
    
    if (searchTerm.length === 0) {
        loadGames();
        return;
    }
    
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=search_games&search_term=${encodeURIComponent(searchTerm)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar resultados de búsqueda
            popularGamesContainer.innerHTML = '';
            saleGamesContainer.innerHTML = '';
            displayGames(data.games, popularGamesContainer);
            
            // Actualizar título
            document.querySelector('.section-title').textContent = `Resultados de búsqueda: "${searchTerm}"`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al buscar juegos', 'error');
    });
}

// Agregar al carrito
function addToCart(gameId) {
    if (!currentUser) {
        showModal(loginModal);
        showNotification('Debes iniciar sesión para agregar juegos al carrito', 'error');
        return;
    }
    
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_cart&game_id=${gameId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar carrito
            loadCart();
            
            // Mostrar mensaje de éxito
            showNotification('¡Juego agregado al carrito!');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al agregar al carrito', 'error');
    });
}

// Cargar carrito
function loadCart() {
    if (!currentUser) return;
    
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = data.cart_items;
            cartItemCount = cart.reduce((total, item) => total + item.quantity, 0);
            
            // Actualizar interfaz
            updateCartUI();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al cargar el carrito', 'error');
    });
}

// Actualizar interfaz del carrito
function updateCartUI() {
    // Actualizar contador del carrito
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = cartItemCount;
    }
    
    // Actualizar items del carrito
    if (cartItemsContainer) {
        cartItemsContainer.innerHTML = '';
        
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p class="empty-cart">Tu carrito está vacío</p>';
            if (cartTotalElement) {
                cartTotalElement.textContent = '0.00';
            }
            return;
        }
        
        cart.forEach(item => {
            // Asegurarse de que los valores sean números
            const finalPrice = parseFloat(item.final_price);
            const quantity = 1; // Siempre 1
            const itemTotal = finalPrice * quantity;
            
            const cartItem = document.createElement('div');
            cartItem.classList.add('cart-item');
            cartItem.innerHTML = `
                <img src="${item.image}" alt="${item.title}">
                <div class="cart-item-details">
                    <h4>${item.title}</h4>
                    <p>$${finalPrice.toFixed(2)}</p>
                </div>
                <div class="cart-item-total">
                    $${itemTotal.toFixed(2)}
                </div>
                <button class="remove-from-cart" data-cart-id="${item.id}">&times;</button>
            `;
            
            // Agregar evento para eliminar
            const removeBtn = cartItem.querySelector('.remove-from-cart');
            removeBtn.addEventListener('click', () => {
                removeFromCart(item.id);
            });
            
            cartItemsContainer.appendChild(cartItem);
        });
        
        // Actualizar total
        if (cartTotalElement) {
            const total = cart.reduce((sum, item) => {
                const finalPrice = parseFloat(item.final_price);
                const quantity = 1; // Siempre 1
                return sum + (finalPrice * quantity);
            }, 0);
            cartTotalElement.textContent = total.toFixed(2);
        }
    }
}

// Eliminar del carrito
function removeFromCart(cartId) {
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_from_cart&cart_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar carrito
            loadCart();
            showNotification('Producto eliminado del carrito');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al eliminar del carrito', 'error');
    });
}

// Manejar login
function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(loginForm);
    
    fetch('process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar la página para actualizar la interfaz con PHP
            window.location.reload();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Manejar registro
function handleRegister(e) {
    e.preventDefault();
    
    const formData = new FormData(registerForm);
    
    fetch('process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar la página para actualizar la interfaz con PHP
            window.location.reload();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

// Cerrar sesión
function logout() {
    fetch('process.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=logout'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar página para limpiar estado
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al cerrar sesión', 'error');
    });
}

// Manejar checkout
function handleCheckout() {
    if (cart.length === 0) {
        showNotification('Tu carrito está vacío', 'error');
        return;
    }
    
    // Procesar cada item del carrito
    const purchasePromises = cart.map(item => {
        return fetch('process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=purchase_game&game_id=${item.game_id}`
        }).then(response => response.json());
    });
    
    Promise.all(purchasePromises)
    .then(results => {
        const allSuccessful = results.every(result => result.success);
        
        if (allSuccessful) {
            showNotification('¡Compra realizada con éxito! Los juegos han sido añadidos a tu biblioteca.');
            // Vaciar carrito
            cart = [];
            cartItemCount = 0;
            updateCartUI();
            
            // Redirigir a biblioteca después de 2 segundos
            setTimeout(() => {
                window.location.href = 'library.php';
            }, 2000);
        } else {
            showNotification('Error al procesar algunos items del carrito', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al procesar la compra', 'error');
    });
}

// Actualizar interfaz de usuario según estado de autenticación
function updateUserInterface() {
    // Esta función se maneja principalmente con PHP en este ejemplo
}

// Mostrar modal
function showModal(modal) {
    closeModals();
    modal.style.display = 'flex';
}

// Cerrar modales
function closeModals() {
    if (loginModal) loginModal.style.display = 'none';
    if (registerModal) registerModal.style.display = 'none';
    if (cartModal) cartModal.style.display = 'none';
}

// Mostrar notificación
function showNotification(message, type = 'success') {
    if (!notification) return;
    
    notification.textContent = message;
    notification.className = 'notification';
    notification.classList.add(type);
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// Debounce para búsqueda
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function editGame(gameId, titulo, descripcion, precio, descuento, categoria, imagen_url, requisitos) {
    try {
        console.log('=== EDITANDO JUEGO ===');
        console.log('ID:', gameId);
        console.log('Título:', titulo);
        console.log('Precio:', precio);
        console.log('Descuento:', descuento);
        
        // VERIFICAR QUE gameId NO SEA undefined O VACÍO
        if (!gameId || gameId === 'undefined' || gameId === '0') {
            console.error('ERROR: gameId no válido:', gameId);
            alert('Error: ID del juego no válido');
            return;
        }
        
        // Llenar formulario con datos del juego
        const editGameIdField = document.getElementById('editGameId');
        if (!editGameIdField) {
            console.error('ERROR: No se encontró el campo editGameId');
            return;
        }
        
        editGameIdField.value = gameId;
        console.log('Campo editGameId establecido a:', editGameIdField.value);
        
        document.getElementById('titulo').value = titulo || '';
        document.getElementById('descripcion').value = descripcion || '';
        document.getElementById('precio').value = precio || 0;
        document.getElementById('descuento').value = descuento || 0;
        document.getElementById('categoria').value = categoria || '';
        document.getElementById('imagen_url').value = imagen_url || '';
        document.getElementById('requisitos').value = requisitos || '';
        
        // Actualizar display del descuento
        updateDiscountValue(descuento);
        
        // Cambiar texto del botón
        document.getElementById('submitButtonText').textContent = 'Actualizar Juego';
        
        // Mostrar botón cancelar
        document.getElementById('cancelEdit').style.display = 'block';
        
        // Scroll al formulario
        document.getElementById('newGameForm').scrollIntoView({ behavior: 'smooth' });
        
        // Cambiar título del formulario
        const formTitle = document.querySelector('#newGameForm h2');
        if (formTitle) {
            formTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Juego';
        }
        
        console.log('Formulario listo para edición');
        
    } catch (error) {
        console.error('Error en editGame:', error);
        alert('Error al cargar el formulario de edición: ' + error.message);
    }
}

// Función para mostrar detalles del juego
function showGameDetails(gameId, titulo, descripcion, precio, descuento, categoria, imagen_url, requisitos, estado, fecha) {
    try {
        console.log('Mostrando detalles ID:', gameId);
        
        document.getElementById('detailGameTitle').textContent = titulo;
        
        const detailBody = document.getElementById('detailGameBody');
        const precioFinal = descuento > 0 ? 
            precio * (1 - descuento / 100) : 
            precio;
        
        detailBody.innerHTML = `
            <div class="detail-section">
                <h4><i class="fas fa-info-circle"></i> Información General</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                    <div><strong>Categoría:</strong> ${categoria}</div>
                    <div><strong>Precio:</strong> 
                        ${descuento > 0 ? 
                            `<s>$${parseFloat(precio).toFixed(2)}</s> $${precioFinal.toFixed(2)} 
                            <span style="color: #FF4C4C; font-weight: bold;">(-${descuento}%)</span>` : 
                            `$${parseFloat(precio).toFixed(2)}`
                        }
                    </div>
                    <div><strong>Estado:</strong> <span class="status-badge status-${estado}">${estado}</span></div>
                    <div><strong>Fecha:</strong> ${new Date(fecha).toLocaleDateString()}</div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-align-left"></i> Descripción</h4>
                <p>${descripcion.replace(/\n/g, '<br>')}</p>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-desktop"></i> Requisitos del Sistema</h4>
                <p>${requisitos.replace(/\n/g, '<br>')}</p>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-image"></i> URL de Imagen</h4>
                <p><a href="${imagen_url}" target="_blank">${imagen_url}</a></p>
            </div>
        `;
        
        document.getElementById('gameDetailsModal').style.display = 'flex';
    } catch (error) {
        console.error('Error en showGameDetails:', error);
        alert('Error al mostrar detalles del juego');
    }
}

// Función para cerrar detalles del juego
function closeGameDetails() {
    document.getElementById('gameDetailsModal').style.display = 'none';
}

// Función para mostrar confirmación de eliminación
function showDeleteConfirm(gameId, gameName) {
    try {
        window.gameToDelete = gameId;
        document.getElementById('deleteGameName').textContent = gameName;
        document.getElementById('deleteConfirmModal').style.display = 'flex';
    } catch (error) {
        console.error('Error en showDeleteConfirm:', error);
    }
}

// Función para cerrar modal de eliminación
function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    window.gameToDelete = null;
}

// Función para eliminar juego
function deleteGame() {
    if (!window.gameToDelete) return;
    
    // Redirigir a página de eliminación
    window.location.href = 'delete_game.php?id=' + window.gameToDelete;
}

// Función para resetear formulario
function resetGameForm() {
    try {
        document.getElementById('gameForm').reset();
        document.getElementById('editGameId').value = '';
        document.getElementById('submitButtonText').textContent = 'Enviar para Revisión';
        document.getElementById('cancelEdit').style.display = 'none';
        
        // Restaurar título del formulario
        const formTitle = document.querySelector('#newGameForm h2');
        if (formTitle) {
            formTitle.innerHTML = '<i class="fas fa-upload"></i> Enviar Nuevo Juego';
        }
    } catch (error) {
        console.error('Error en resetGameForm:', error);
    }
}

// ===== FUNCIONES DE WISHLIST =====

// Función para manejar wishlist
function toggleWishlist(gameId, button) {
    console.log('toggleWishlist llamado para gameId:', gameId);
    
    if (!window.userSession || !window.userSession.isLoggedIn) {
        console.log('Usuario no autenticado, mostrando modal de login');
        showModal(loginModal);
        showNotification('Debes iniciar sesión para usar la lista de deseos', 'error');
        return;
    }
    
    // Verificar si ya está en la wishlist
    checkWishlistStatus(gameId, function(inWishlist) {
        if (inWishlist) {
            removeFromWishlist(gameId, button);
        } else {
            addToWishlist(gameId, button);
        }
    });
}

// Función para verificar estado de wishlist
function checkWishlistStatus(gameId, callback) {
    console.log('Verificando estado de wishlist para gameId:', gameId);
    
    if (!window.userSession || !window.userSession.isLoggedIn) {
        callback(false);
        return;
    }
    
    fetch('process_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=check_wishlist_status&game_id=${gameId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta de check_wishlist_status:', data);
        if (data.success) {
            callback(data.in_wishlist);
        } else {
            callback(false);
        }
    })
    .catch(error => {
        console.error('Error al verificar wishlist:', error);
        callback(false);
    });
}

// Función para agregar a wishlist
function addToWishlist(gameId, button) {
    console.log('Agregando a wishlist gameId:', gameId);
    
    fetch('process_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_wishlist&game_id=${gameId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta add_to_wishlist:', data);
        if (data.success) {
            // Animar el botón
            if (button) {
                button.classList.add('active', 'added');
                button.innerHTML = '<i class="fas fa-heart"></i>';
                setTimeout(() => {
                    button.classList.remove('added');
                }, 500);
            }
            showNotification(data.message);
            updateWishlistCount();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error al agregar a wishlist:', error);
        showNotification('Error al agregar a lista de deseos', 'error');
    });
}

// Función para eliminar de wishlist
function removeFromWishlist(gameId, button) {
    console.log('Eliminando de wishlist gameId:', gameId);
    
    if (confirm('¿Estás seguro de que deseas eliminar este juego de tu lista de deseos?')) {
        fetch('process_wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=remove_from_wishlist&game_id=${gameId}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta remove_from_wishlist:', data);
            if (data.success) {
                if (button) {
                    button.classList.remove('active');
                    button.innerHTML = '<i class="far fa-heart"></i>';
                }
                showNotification(data.message);
                updateWishlistCount();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error al eliminar de wishlist:', error);
            showNotification('Error al eliminar de lista de deseos', 'error');
        });
    }
}

// Función para actualizar contador de wishlist
function updateWishlistCount() {
    console.log('Actualizando contador de wishlist...');
    
    if (!window.userSession || !window.userSession.isLoggedIn) {
        console.log('Usuario no autenticado, no se actualiza contador');
        return;
    }
    
    fetch('process_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_wishlist'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Respuesta get_wishlist:', data);
        if (data.success) {
            const wishlistCount = document.getElementById('wishlistCount');
            if (wishlistCount) {
                const count = data.wishlist ? data.wishlist.length : 0;
                wishlistCount.textContent = count;
                // Animar el contador
                wishlistCount.style.animation = 'none';
                setTimeout(() => {
                    wishlistCount.style.animation = 'pulse 0.5s ease-in-out';
                }, 10);
            }
        }
    })
    .catch(error => {
        console.error('Error al actualizar contador wishlist:', error);
    });
}
