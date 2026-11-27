/**
 * Módulo de Autenticación - JavaScript
 * Sistema de Soporte TI - TAMA
 */

const Auth = {
    apiUrl: '/soporte/src/api/auth.php',
    csrfToken: function() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    },
    
    /**
     * Iniciar sesión
     */
    login: async function(username, password) {
        try {
            const response = await fetch(this.apiUrl + '?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken()
                },
                body: JSON.stringify({ username, password, _csrf: this.csrfToken() })
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error en login:', error);
            return { success: false, message: 'Error de conexión' };
        }
    },
    
    /**
     * Cerrar sesión
     */
    logout: async function() {
        try {
            const response = await fetch(this.apiUrl + '?action=logout');
            const data = await response.json();
            
            if (data.success && data.data.redirect) {
                window.location.href = data.data.redirect;
            }
            return data;
        } catch (error) {
            console.error('Error en logout:', error);
            return { success: false, message: 'Error de conexión' };
        }
    },
    
    /**
     * Verificar sesión
     */
    checkSession: async function() {
        try {
            const response = await fetch(this.apiUrl + '?action=check');
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error verificando sesión:', error);
            return { success: false, message: 'Error de conexión' };
        }
    }
};

/**
 * Inicializar formulario de login
 */
function initLoginForm() {
    const form = document.getElementById('loginForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const btnSubmit = form.querySelector('button[type="submit"]');
        const alertDiv = document.getElementById('loginAlert');
        
        // Validación básica
        if (!username || !password) {
            showLoginAlert('Por favor ingrese usuario y contraseña', 'danger');
            return;
        }
        
        // Deshabilitar botón
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Ingresando...';
        
        // Ocultar alerta anterior
        if (alertDiv) alertDiv.style.display = 'none';
        
        // Llamar API
        const result = await Auth.login(username, password);
        
        if (result.success) {
            showLoginAlert('¡Bienvenido! Redirigiendo...', 'success');
            
            // Redireccionar
            setTimeout(() => {
                window.location.href = result.data.redirect || '/soporte/home.php';
            }, 500);
        } else {
            showLoginAlert(result.message || 'Error al iniciar sesión', 'danger');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-sign-in-alt"></i> Ingresar';
        }
    });
}

/**
 * Mostrar alerta en login
 */
function showLoginAlert(message, type) {
    const alertDiv = document.getElementById('loginAlert');
    if (alertDiv) {
        alertDiv.className = 'alert alert-' + type;
        alertDiv.textContent = message;
        alertDiv.style.display = 'block';
    }
}

// Inicializar cuando cargue el DOM
document.addEventListener('DOMContentLoaded', function() {
    initLoginForm();
});
