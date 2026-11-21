// js/app.js
// Shared client-side JavaScript helper for VoteSecure

// API client utility wrapper
const API = {
    // Perform a GET request to PHP APIs
    get: async (url) => {
        try {
            const response = await fetch(url);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('GET request failed:', error);
            return { success: false, message: 'Network error or server unavailable.' };
        }
    },

    // Perform a POST request to PHP APIs with form data
    post: async (url, formData) => {
        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('POST request failed:', error);
            return { success: false, message: 'Network error or server unavailable.' };
        }
    }
};

// Global notification Toast manager
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // Choose icon based on notification type
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '❌';
    if (type === 'warning') icon = '⚠️';

    toast.innerHTML = `<span class="toast-icon">${icon}</span><span class="toast-message">${message}</span>`;
    container.appendChild(toast);

    // Trigger transition animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 50);

    // Auto dismiss toast after 4.5 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 4500);
}

// Redirect voter if not logged in, or redirect admin if needed
async function checkAuth(requiredRole = null) {
    const data = await API.get('api/auth/profile.php');
    
    if (!data.success) {
        // Not logged in
        if (window.location.pathname.indexOf('login.html') === -1 && 
            window.location.pathname.indexOf('register.html') === -1 &&
            window.location.pathname.indexOf('home.html') !== -1 &&
            window.location.pathname !== '/' &&
            window.location.pathname.slice(-1) !== '/') {
            
            // Allow home page, redirect others to login
            window.location.href = 'login.html';
        }
        return null;
    }

    const user = data.user;
    
    // Save user info in sessionStorage for fast client check
    sessionStorage.setItem('user', JSON.stringify(user));

    // Role redirection logic
    if (requiredRole && user.role !== requiredRole) {
        if (user.role === 'admin') {
            window.location.href = 'admin.html';
        } else {
            window.location.href = 'dashboard.html';
        }
    }

    // If on login/register and already logged in, redirect to respective dashboard
    if (window.location.pathname.indexOf('login.html') !== -1 || 
        window.location.pathname.indexOf('register.html') !== -1) {
        if (user.role === 'admin') {
            window.location.href = 'admin.html';
        } else {
            window.location.href = 'dashboard.html';
        }
    }

    return user;
}

// Perform logout action
async function performLogout() {
    const data = await API.get('api/auth/logout.php');
    if (data.success) {
        sessionStorage.clear();
        window.location.href = 'login.html';
    } else {
        showToast('Logout failed: ' + data.message, 'error');
    }
}

// Helper to format dates elegantly (Kolkata timezone friendly format)
function formatDateTimeStr(dateStr, timeStr) {
    if (!dateStr) return '';
    const dateObj = new Date(dateStr + 'T' + (timeStr || '00:00:00'));
    return dateObj.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

// Generate background glowing orbs dynamically
document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('.orb')) {
        const orb1 = document.createElement('div');
        orb1.className = 'orb orb-1';
        const orb2 = document.createElement('div');
        orb2.className = 'orb orb-2';
        document.body.appendChild(orb1);
        document.body.appendChild(orb2);
    }
});
