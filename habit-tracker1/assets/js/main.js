// Initialize Saved Theme on Load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeLabel(savedTheme);
});

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeLabel(newTheme);
}

function updateThemeLabel(theme) {
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');
    
    if (themeIcon && themeText) {
        if (theme === 'dark') {
            themeIcon.className = 'fa-solid fa-sun';
            themeText.textContent = 'Light Mode';
        } else {
            themeIcon.className = 'fa-solid fa-moon';
            themeText.textContent = 'Dark Mode';
        }
    }
}

function openModal(id) {
    document.getElementById(id).style.display = 'flex';
    document.getElementById('userDropdown').style.display = 'none';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function toggleDropdown() {
    const menu = document.getElementById('userDropdown');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Close dropdown and modal when clicking outside
window.onclick = function(event) {
    const routineModal = document.getElementById('routineModal');
    const profileModal = document.getElementById('profileModal');
    const dropdown = document.getElementById('userDropdown');
    const userBtn = document.querySelector('.user-btn');

    if (event.target === routineModal) closeModal('routineModal');
    if (event.target === profileModal) closeModal('profileModal');
    
    if (dropdown && !dropdown.contains(event.target) && !userBtn.contains(event.target)) {
        dropdown.style.display = 'none';
    }
};