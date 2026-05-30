import './bootstrap.js';
import './styles/app.css';

function handleToggle() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
}

function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;
    // Retire le listener précédent avant d'en ajouter un nouveau (Turbo recharge le DOM)
    toggle.removeEventListener('click', handleToggle);
    toggle.addEventListener('click', handleToggle);
}

// Chargement initial
document.addEventListener('DOMContentLoaded', initThemeToggle);
// Navigation Turbo (changement de langue, etc.)
document.addEventListener('turbo:load', initThemeToggle);
