<?php
/**
 * Estilos compartidos para el sistema de asistencia
 * Basado en el estilo del marketplace
 */
?>
<style>
/* Variables de Color - Igual que marketplace */
:root {
    --navy: #003366;
    --gold: #fdb714;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    --green-50: #f0fdf4;
    --green-100: #dcfce7;
    --green-500: #22c55e;
    --green-600: #16a34a;
    --green-700: #15803d;
    --red-50: #fef2f2;
    --red-100: #fee2e2;
    --red-500: #ef4444;
    --red-600: #dc2626;
    --red-700: #b91c1c;
    --blue-50: #eff6ff;
    --blue-100: #dbeafe;
    --blue-500: #3b82f6;
    --blue-600: #2563eb;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    color: var(--gray-900);
}

/* Navigation - Estilo marketplace */
.navbar {
    background-color: var(--navy);
    padding: 1rem 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.nav-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-brand {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.logo {
    background-color: var(--gold);
    color: var(--navy);
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 1.25rem;
    text-decoration: none;
    transition: transform 0.2s;
}

.logo:hover {
    transform: scale(1.05);
}

.nav-title h1 {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0;
}

.nav-title p {
    font-size: 0.75rem;
    color: var(--gold);
    margin-top: 2px;
}

.nav-links {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 8px;
    transition: background-color 0.2s;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-links a:hover,
.nav-links a.active {
    background-color: rgba(255, 255, 255, 0.1);
}

.nav-links a.active {
    background-color: rgba(253, 183, 20, 0.2);
}

/* Dropdowns */
.admin-dropdown,
.user-dropdown {
    position: relative;
    display: inline-block;
}

.admin-dropdown-btn,
.user-dropdown-btn {
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    transition: background-color 0.2s;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
}

.admin-dropdown-btn:hover,
.user-dropdown-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.admin-dropdown-content,
.user-dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    background-color: white;
    min-width: 200px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    z-index: 1000;
    overflow: hidden;
    border: 1px solid var(--gray-100);
}

.admin-dropdown-content a,
.user-dropdown-content a {
    color: var(--gray-700);
    padding: 12px 16px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    transition: background-color 0.2s;
}

.admin-dropdown-content a:hover,
.user-dropdown-content a:hover {
    background-color: var(--gray-50);
    color: var(--navy);
}

/* Better dropdown hover handling */
.admin-dropdown:hover .admin-dropdown-content,
.user-dropdown:hover .user-dropdown-content {
    display: block;
}

/* Add padding to create hover bridge */
.admin-dropdown-content {
    margin-top: -2px;
    padding-top: 2px;
}

.user-dropdown-content {
    margin-top: -2px;
    padding-top: 2px;
}

/* Ensure dropdowns stay visible when hovering */
.admin-dropdown-content:hover,
.user-dropdown-content:hover {
    display: block;
}

.user-dropdown-content hr {
    margin: 0.5rem 0;
    border: none;
    border-top: 1px solid var(--gray-200);
}

.logout-link {
    color: var(--red-600) !important;
}

.logout-link:hover {
    background-color: var(--red-50) !important;
}

.avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--gold);
}

.btn-login {
    background-color: var(--gold) !important;
    color: var(--navy) !important;
    font-weight: 600 !important;
}

.btn-login:hover {
    background-color: #ffc942 !important;
    transform: translateY(-1px);
}

/* Mobile Menu */
.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 8px;
}

.mobile-nav-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.mobile-nav-overlay.active {
    display: block;
}

.mobile-nav-menu {
    position: fixed;
    top: 0;
    right: -300px;
    width: 280px;
    height: 100%;
    background-color: white;
    box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    transition: right 0.3s ease;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.mobile-nav-menu.active {
    right: 0;
}

.mobile-nav-header {
    background-color: var(--navy);
    color: white;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-nav-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 4px;
}

.mobile-user-info {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 1rem;
    background-color: var(--gray-50);
}

.mobile-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--gold);
}

.mobile-user-name {
    font-weight: 600;
    color: var(--navy);
}

.mobile-user-role {
    font-size: 0.875rem;
    color: var(--gray-600);
}

.mobile-nav-links {
    padding: 1rem 0;
    flex: 1;
}

.mobile-nav-links a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 12px 20px;
    color: var(--gray-700);
    text-decoration: none;
    font-weight: 500;
    border-bottom: 1px solid var(--gray-100);
    transition: background-color 0.2s;
}

.mobile-nav-links a:hover {
    background-color: var(--gray-50);
    color: var(--navy);
}

.mobile-admin-section {
    border-top: 2px solid var(--gray-200);
    padding-top: 1rem;
}

.mobile-admin-header {
    padding: 12px 20px;
    font-weight: 600;
    color: var(--navy);
    font-size: 0.875rem;
    text-transform: uppercase;
}

.mobile-admin-links a {
    padding-left: 40px;
    font-size: 0.875rem;
}

.mobile-nav-footer {
    border-top: 2px solid var(--gray-200);
    padding: 1rem;
    background-color: var(--gray-50);
}

.mobile-footer-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--gray-700);
    text-decoration: none;
    font-weight: 500;
    border-radius: 8px;
    transition: background-color 0.2s;
    margin-bottom: 0.5rem;
}

.mobile-footer-link:hover {
    background-color: white;
}

.mobile-footer-link.logout {
    color: var(--red-600);
    background-color: var(--red-50);
}

/* Content Container */
.container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Cards - Estilo marketplace */
.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    padding: 1.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-left: 4px solid var(--gold);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
}

.stat-label {
    color: var(--gray-600);
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.stat-change {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-change.positive {
    color: var(--green-600);
}

.stat-change.negative {
    color: var(--red-600);
}

/* Buttons */
.btn {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background-color: var(--navy);
    color: white;
}

.btn-primary:hover {
    background-color: #002244;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: white;
    color: var(--navy);
    border: 2px solid var(--navy);
}

.btn-secondary:hover {
    background-color: var(--gray-50);
}

.btn-accent {
    background-color: var(--gold);
    color: var(--navy);
    font-weight: 600;
}

.btn-accent:hover {
    background-color: #ffc942;
    transform: translateY(-1px);
}

/* Forms */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gray-700);
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--gray-300);
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1);
}

/* Tables */
.table {
    width: 100%;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table thead {
    background-color: var(--gray-50);
}

.table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--gray-700);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.table td {
    padding: 1rem;
    border-top: 1px solid var(--gray-100);
    color: var(--gray-700);
    font-size: 0.875rem;
}

.table tbody tr:hover {
    background-color: var(--gray-50);
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success {
    background-color: var(--green-100);
    color: var(--green-700);
}

.badge-warning {
    background-color: #fef3c7;
    color: #92400e;
}

.badge-danger {
    background-color: var(--red-100);
    color: var(--red-700);
}

.badge-info {
    background-color: var(--blue-100);
    color: var(--blue-700);
}

/* Alerts */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-success {
    background-color: var(--green-50);
    color: var(--green-700);
    border: 1px solid var(--green-200);
}

.alert-error {
    background-color: var(--red-50);
    color: var(--red-700);
    border: 1px solid var(--red-200);
}

.alert-warning {
    background-color: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.alert-info {
    background-color: var(--blue-50);
    color: var(--blue-700);
    border: 1px solid var(--blue-200);
}

/* Loading Spinner */
.spinner {
    border: 3px solid var(--gray-200);
    border-top-color: var(--navy);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Grid System */
.grid {
    display: grid;
    gap: 1.5rem;
}

.grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }

/* Flex utilities */
.flex { display: flex; }
.gap-2 { gap: 0.5rem; }
.justify-center { justify-content: center; }
.items-center { align-items: center; }
.gap-3 { gap: 0.75rem; }

/* Responsive */
@media (max-width: 768px) {
    .nav-links {
        display: none;
    }

    .mobile-menu-btn {
        display: block;
    }

    .nav-title h1 {
        font-size: 1rem;
    }

    .nav-title p {
        display: none;
    }

    .container {
        padding: 1rem;
    }

    .grid-cols-2,
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }

    .stat-value {
        font-size: 1.5rem;
    }
}

@media (min-width: 768px) and (max-width: 1024px) {
    .grid-cols-3,
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>