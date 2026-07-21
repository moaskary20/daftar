<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@200;300;400;500;700;800;900&display=swap" rel="stylesheet">

<style>
    :root {
        --daftar-violet-1: #5458f0;
        --daftar-violet-2: #4238ca;
        --daftar-violet-3: #37309f;
        --daftar-surface: #f4f6fb;
        --daftar-card-border: rgba(148, 163, 184, .22);
        --daftar-shadow: 0 10px 34px rgba(30, 27, 75, .07);
    }

    body, html, .fi-body, .fi-main, .fi-sidebar, .fi-topbar,
    input, textarea, select, button {
        font-family: 'Tajawal', sans-serif !important;
    }

    /* ============ خلفية المحتوى ============ */
    .fi-body {
        background:
            radial-gradient(circle at 10% 6%, rgba(84, 88, 240, .07), transparent 26rem),
            radial-gradient(circle at 90% 20%, rgba(245, 158, 11, .07), transparent 24rem),
            var(--daftar-surface);
        background-attachment: fixed;
    }

    .dark .fi-body {
        background:
            radial-gradient(circle at 10% 6%, rgba(84, 88, 240, .1), transparent 26rem),
            radial-gradient(circle at 90% 20%, rgba(245, 158, 11, .05), transparent 24rem),
            #0b1020;
    }

    /* ============ القائمة الجانبية ============ */
    .fi-sidebar {
        background: linear-gradient(185deg, var(--daftar-violet-1) 0%, var(--daftar-violet-2) 62%, var(--daftar-violet-3) 100%) !important;
        border-inline-end: 0 !important;
        box-shadow: 10px 0 40px rgba(30, 27, 75, .22);
    }

    .dark .fi-sidebar {
        background: linear-gradient(185deg, #322e81 0%, #241f63 62%, #191542 100%) !important;
    }

    .fi-sidebar-header {
        background: transparent !important;
        box-shadow: none !important;
        border-bottom: 1px solid rgba(255, 255, 255, .14);
    }

    .fi-sidebar-header .fi-logo,
    .fi-sidebar-header .fi-icon-btn {
        color: #fff !important;
    }

    .fi-sidebar-header .fi-logo {
        font-weight: 900;
        letter-spacing: .02em;
        text-shadow: 0 2px 12px rgba(0, 0, 0, .25);
    }

    .fi-sidebar-nav {
        padding-inline: .8rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .28) transparent;
    }

    .fi-sidebar-nav::-webkit-scrollbar { width: 5px; }
    .fi-sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .28); border-radius: 999px; }

    .fi-sidebar .fi-sidebar-group-label,
    .fi-sidebar .fi-sidebar-group-btn {
        color: rgba(255, 255, 255, .58) !important;
        font-size: .72rem !important;
        font-weight: 800 !important;
        letter-spacing: .06em;
    }

    .fi-sidebar .fi-sidebar-group-collapse-btn,
    .fi-sidebar .fi-sidebar-group-collapse-btn svg,
    .fi-sidebar .fi-sidebar-group-btn svg {
        color: rgba(255, 255, 255, .58) !important;
    }

    .fi-sidebar .fi-sidebar-group-collapse-btn:hover {
        background: rgba(255, 255, 255, .12) !important;
    }

    .fi-sidebar .fi-sidebar-item-btn {
        border-radius: 999px !important;
        padding-block: .56rem !important;
        padding-inline: .85rem !important;
        color: rgba(255, 255, 255, .86) !important;
        transition: background .18s ease, color .18s ease, transform .18s ease;
    }

    .fi-sidebar .fi-sidebar-item-btn:hover {
        background: rgba(255, 255, 255, .13) !important;
        color: #fff !important;
        transform: translateX(-3px);
    }

    .fi-sidebar .fi-sidebar-item-icon,
    .fi-sidebar .fi-sidebar-item-label {
        color: inherit !important;
        font-weight: 600;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: #fff !important;
        color: var(--daftar-violet-2) !important;
        font-weight: 800;
        box-shadow: 0 10px 24px rgba(9, 9, 45, .28);
    }

    .dark .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        color: #322e81 !important;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-icon {
        color: var(--daftar-violet-1) !important;
    }

    .fi-sidebar .fi-sidebar-item-grouped-border-part,
    .fi-sidebar .fi-sidebar-item-grouped-border-part-not-first,
    .fi-sidebar .fi-sidebar-item-grouped-border-part-not-last {
        border-color: rgba(255, 255, 255, .3) !important;
    }

    .fi-sidebar .fi-sidebar-item-badge-ctn .fi-badge {
        box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
    }

    /* التنقل الفرعي داخل الصفحات (Clusters) */
    .fi-page-sub-navigation-sidebar {
        background: rgba(255, 255, 255, .8);
        border: 1px solid var(--daftar-card-border);
        border-radius: 1rem;
        padding: .6rem;
        box-shadow: var(--daftar-shadow);
        height: fit-content;
    }

    .dark .fi-page-sub-navigation-sidebar {
        background: rgba(20, 26, 48, .8);
        border-color: rgba(71, 85, 105, .4);
    }

    .fi-page-sub-navigation-sidebar .fi-sidebar-item-btn {
        border-radius: .7rem;
    }

    .fi-page-sub-navigation-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: linear-gradient(120deg, var(--daftar-violet-1), var(--daftar-violet-2));
        color: #fff;
        box-shadow: 0 8px 20px rgba(66, 56, 202, .3);
    }

    .fi-page-sub-navigation-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-icon,
    .fi-page-sub-navigation-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-label {
        color: #fff !important;
    }

    .fi-sidebar-footer {
        border-top: 1px solid rgba(255, 255, 255, .14);
        background: transparent !important;
    }

    /* ============ الشريط العلوي ============ */
    .fi-topbar {
        background: rgba(255, 255, 255, .82) !important;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border-bottom: 1px solid rgba(148, 163, 184, .18);
        box-shadow: 0 6px 24px rgba(30, 27, 75, .05);
    }

    .dark .fi-topbar {
        background: rgba(15, 20, 40, .82) !important;
        border-bottom-color: rgba(148, 163, 184, .12);
    }

    .fi-topbar .fi-icon-btn:hover,
    .fi-user-menu-trigger:hover {
        background: rgba(84, 88, 240, .1) !important;
    }

    /* ============ البطاقات والجداول والأقسام ============ */
    .fi-section,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat,
    .fi-fo-tabs,
    .fi-wi-chart {
        border-radius: 1rem !important;
        border: 1px solid var(--daftar-card-border) !important;
        box-shadow: var(--daftar-shadow) !important;
    }

    .dark .fi-section,
    .dark .fi-ta-ctn,
    .dark .fi-wi-stats-overview-stat,
    .dark .fi-fo-tabs,
    .dark .fi-wi-chart {
        border-color: rgba(71, 85, 105, .4) !important;
        box-shadow: 0 10px 34px rgba(0, 0, 0, .3) !important;
    }

    .fi-ta-header-row {
        background: linear-gradient(120deg, rgba(84, 88, 240, .08), rgba(84, 88, 240, .03));
    }

    .dark .fi-ta-header-row {
        background: linear-gradient(120deg, rgba(84, 88, 240, .16), rgba(84, 88, 240, .06));
    }

    .fi-ta-header-cell {
        font-weight: 800 !important;
    }

    .fi-ta-header-cell,
    .fi-ta-header-cell-sort-btn {
        color: var(--daftar-violet-3) !important;
    }

    .dark .fi-ta-header-cell,
    .dark .fi-ta-header-cell-sort-btn {
        color: #a5b4fc !important;
    }

    .fi-ta-row {
        transition: background .15s ease;
    }

    .fi-ta-row:hover {
        background: rgba(84, 88, 240, .05) !important;
    }

    .dark .fi-ta-row:hover {
        background: rgba(84, 88, 240, .12) !important;
    }

    .fi-ta-row:nth-child(even) {
        background: rgba(148, 163, 184, .05);
    }

    /* ============ الشارات والوسوم ============ */
    .fi-badge {
        border-radius: 999px !important;
        font-weight: 700 !important;
    }

    /* ============ بطاقات الإحصائيات ============ */
    .fi-wi-stats-overview-stat {
        position: relative;
    }

    .fi-wi-stats-overview-stat::before {
        content: '';
        position: absolute;
        inset-inline-start: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, var(--daftar-violet-1), var(--daftar-violet-2));
        border-start-start-radius: 1rem;
        border-end-start-radius: 1rem;
    }

    .fi-wi-stats-overview-stat-value {
        font-weight: 900 !important;
        background: linear-gradient(120deg, var(--daftar-violet-2), var(--daftar-violet-1));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .dark .fi-wi-stats-overview-stat-value {
        background: linear-gradient(120deg, #a5b4fc, #c7d2fe);
        -webkit-background-clip: text;
        background-clip: text;
    }

    /* ============ التبويبات والترقيم ============ */
    .fi-tabs-item.fi-active,
    .fi-sc-tabs-tab.fi-active {
        background: linear-gradient(120deg, rgba(84, 88, 240, .12), rgba(84, 88, 240, .06)) !important;
        color: var(--daftar-violet-2) !important;
    }

    .dark .fi-tabs-item.fi-active,
    .dark .fi-sc-tabs-tab.fi-active {
        color: #c7d2fe !important;
    }

    .fi-pagination-item.fi-active,
    .fi-pagination-item.fi-active .fi-pagination-item-btn {
        background: linear-gradient(120deg, var(--daftar-violet-1), var(--daftar-violet-2)) !important;
        color: #fff !important;
        border-radius: .6rem;
    }

    /* ============ الروابط ============ */
    .fi-link {
        font-weight: 700;
    }

    /* ============ الأزرار والحقول ============ */
    .fi-btn {
        border-radius: .7rem !important;
        font-weight: 800 !important;
    }

    .fi-btn.fi-color-primary:not(.fi-outlined) {
        background: linear-gradient(120deg, var(--daftar-violet-1), var(--daftar-violet-2)) !important;
        box-shadow: 0 8px 20px rgba(66, 56, 202, .28) !important;
    }

    .fi-btn.fi-color-primary:not(.fi-outlined):hover {
        filter: brightness(1.08);
    }

    .fi-btn.fi-color-success:not(.fi-outlined) {
        box-shadow: 0 8px 20px rgba(16, 185, 129, .24) !important;
    }

    .fi-btn.fi-color-danger:not(.fi-outlined) {
        box-shadow: 0 8px 20px rgba(244, 63, 94, .24) !important;
    }

    .fi-input-wrp,
    .fi-select-input,
    .fi-fo-file-upload .filepond--panel-root {
        border-radius: .7rem !important;
    }

    .fi-input-wrp:focus-within {
        box-shadow: 0 0 0 3px rgba(84, 88, 240, .18) !important;
    }

    /* ============ ترويسة الصفحة ============ */
    .fi-header-heading {
        font-weight: 900 !important;
        letter-spacing: -.01em;
        background: linear-gradient(120deg, #1e1b4b, var(--daftar-violet-2));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        width: fit-content;
    }

    .dark .fi-header-heading {
        background: linear-gradient(120deg, #e0e7ff, #a5b4fc);
        -webkit-background-clip: text;
        background-clip: text;
    }

    .fi-breadcrumbs-item-label {
        font-weight: 600;
    }

    /* ============ القوائم المنسدلة والإشعارات ============ */
    .fi-dropdown-panel {
        border-radius: .9rem !important;
        border: 1px solid var(--daftar-card-border) !important;
        box-shadow: 0 20px 50px rgba(30, 27, 75, .16) !important;
    }

    .fi-dropdown-list-item:hover {
        background: rgba(84, 88, 240, .08) !important;
    }

    .fi-no-notification {
        border-radius: .9rem !important;
        box-shadow: 0 16px 40px rgba(30, 27, 75, .18) !important;
    }

    /* ============ أشرطة التمرير ============ */
    .fi-main::-webkit-scrollbar,
    .fi-ta-content::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .fi-main::-webkit-scrollbar-thumb,
    .fi-ta-content::-webkit-scrollbar-thumb {
        background: rgba(84, 88, 240, .3);
        border-radius: 999px;
    }

    /* ============ النوافذ المنبثقة ============ */
    .fi-modal-window {
        border-radius: 1.1rem !important;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .3) !important;
    }

    /* ============ صفحة الدخول ============ */
    .fi-simple-main {
        border-radius: 1.25rem !important;
        border: 1px solid var(--daftar-card-border) !important;
        box-shadow: 0 30px 80px rgba(30, 27, 75, .14) !important;
    }

    .fi-simple-layout {
        background:
            radial-gradient(circle at 15% 15%, rgba(84, 88, 240, .14), transparent 30rem),
            radial-gradient(circle at 85% 80%, rgba(245, 158, 11, .12), transparent 28rem),
            var(--daftar-surface);
    }

    .dark .fi-simple-layout {
        background:
            radial-gradient(circle at 15% 15%, rgba(84, 88, 240, .16), transparent 30rem),
            radial-gradient(circle at 85% 80%, rgba(245, 158, 11, .07), transparent 28rem),
            #0b1020;
    }

    /* ============ حركات ============ */
    .fi-sidebar-item-button,
    .fi-btn,
    .fi-input-wrp,
    .fi-section,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat {
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease, background .18s ease;
    }

    .fi-section:hover,
    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 48px rgba(30, 27, 75, .12) !important;
    }

    .fi-btn:hover {
        transform: translateY(-1px);
    }

    .fi-main > * {
        animation: daftar-reveal .42s ease both;
    }

    .fi-wi-stats-overview-stat {
        overflow: hidden;
        background: linear-gradient(150deg, rgba(255, 255, 255, .99), rgba(244, 246, 251, .9));
    }

    .dark .fi-wi-stats-overview-stat {
        background: linear-gradient(150deg, rgba(20, 26, 48, .98), rgba(15, 20, 40, .92));
    }

    @keyframes daftar-reveal {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            scroll-behavior: auto !important;
            animation-duration: .01ms !important;
            transition-duration: .01ms !important;
        }
    }
</style>
