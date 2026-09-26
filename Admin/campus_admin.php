<?php
session_start();

// 1. PHP GUARD: Secure the page — must be Admin with a campus assigned (Campus Admin)
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin' || empty($_SESSION['user']['campus'])) {
    header('Location: ../login.php');
    exit;
}
    
// 2. DATA PREPARATION: Pass PHP Session to JS safely
$sessionUser = $_SESSION['user'];
$adminName   = $sessionUser['full_name'] ?? 'System Administrator';
$adminCampus = trim($sessionUser['campus']);          // always set for campus admin
// Campus Admin is never a super admin
$sessionUser['is_super_admin'] = false;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FLEXAM - Campus Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/announcements.js"></script>    
    <link rel="icon" type="image/svg+xml" href="../Image/OLFU.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif !important; }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #1e293b;
        }

        button, .sidebar-link span { font-weight: 400 !important; }

        .sidebar-link {
            transition: all 0.2s ease;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            line-height: 1.25;
        }
        
        .sidebar-link:hover { background-color: #f1f5f9; color: #1e293b; }
        .sidebar-link.active { background-color: #ecfdf5; color: #047857; }
        .sidebar-link.active span { font-weight: 500 !important; }
        
        .logo-box {
            background: #047857;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(4, 120, 87, 0.2);
        }

        .top-header {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1rem;
            height: auto;
            min-height: 64px;
        }
        @media (min-width: 768px) {
            .top-header { padding: 1rem 1.5rem; min-height: 80px; }
        }
        /* Campus tab strip scrollable on mobile */
        .campus-tab-strip { overflow-x: auto; -webkit-overflow-scrolling: touch; flex-wrap: nowrap !important; }
        /* Filter bar wraps nicely on small screens */
        @media (max-width: 640px) {
            .filter-select { font-size: 0.75rem; padding: 0.35rem 0.6rem; }
            .btn-action-large { padding: 0.5rem 1rem; font-size: 0.8rem; }
        }

        /* Analytics Specific */
        .analytics-card {
            background: white;
            padding: 1.25rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 110px;
        }

        .stat-card-small {
            background: white;
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-action-large {
            padding: 0.6rem 1.5rem;
            font-size: 0.9rem;
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        /* Calendar Grid Styling */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border-top: 1px solid #e2e8f0;
            border-left: 1px solid #e2e8f0;
        }
        .calendar-cell {
            height: 100px;
            padding: 0.5rem;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            background: white;
            position: relative;
        }
        .event-bar {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            color: white;
            margin-top: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .event-bar:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }

        .quick-icon-card { transition: all 0.3s ease; }
        .quick-icon-card:hover { transform: translateY(-4px); }

        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* ── Modal Styles ───────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 50;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: white;
            border-radius: 1rem;
            width: 100%;
            max-width: 640px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            max-height: 90vh;
            overflow-y: auto;
        }
        .badge-admin {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-head {
            background: #dbeafe;
            color: #1e40af;
        }
        .toast {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(1rem); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        /* Campus filter badge */
        .campus-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Filter select styling */
        .filter-select {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            font-size: 0.875rem;
            color: #374151;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2364748b%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C/polyline%3E%3C/svg%3E');
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1em;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .filter-select:focus { outline: none; border-color: #047857; }
        /* ── Campus Tab Strip ─────────────────────────────────────────── */
        .campus-tab-strip {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
            padding: 0.625rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .campus-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid transparent;
            transition: all 0.18s ease;
            color: #64748b;
            background: white;
            border-color: #e2e8f0;
            white-space: nowrap;
            user-select: none;
        }
        .campus-tab:hover {
            border-color: #a7f3d0;
            color: #047857;
            background: #f0fdf4;
        }
        .campus-tab.active {
            background: #047857;
            border-color: #047857;
            color: white;
            box-shadow: 0 2px 6px rgba(4,120,87,0.25);
        }
        .campus-tab .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.1rem;
            height: 1.1rem;
            padding: 0 0.2rem;
            border-radius: 9999px;
            font-size: 0.6rem;
            font-weight: 700;
            background: rgba(255,255,255,0.25);
            color: inherit;
        }
        .campus-tab.active .tab-count {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .prog-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .prog-badges-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-height: 52px;        /* ~2 lines of badges */
            overflow: hidden;
            position: relative;
            transition: max-height 0.3s ease;
        }

        .prog-badges-wrap.expanded {
            max-height: 500px;       /* tall enough for any amount */
        }

        /* fade-out gradient at the bottom when collapsed */
        .prog-badges-wrap:not(.expanded)::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 22px;
            background: linear-gradient(to bottom, transparent, white);
            pointer-events: none;
        }

        .prog-toggle-btn {
            background: none;
            border: none;
            padding: 0;
            font-size: 11px;
            font-weight: 600;
            color: #3b82f6;
            cursor: pointer;
            text-align: left;
            width: fit-content;
        }
        .prog-toggle-btn:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        /* ── Programs Cell (Courses Table) ── */
        .prog-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .prog-badges-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-height: 52px;
            overflow: hidden;
            position: relative;
            transition: max-height 0.3s ease;
        }

        .prog-badges-wrap.expanded {
            max-height: 500px;
        }

        .prog-badges-wrap:not(.expanded)::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 22px;
            background: linear-gradient(to bottom, transparent, white);
            pointer-events: none;
        }

        .prog-toggle-btn {
            background: none;
            border: none;
            padding: 0;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            text-align: left;
            width: fit-content;
        }

        .prog-toggle-btn:hover {
            color: #1e293b;
            text-decoration: underline;
        }

        /* ── Programs Modal ── */
        .prog-modal-overlay {
            position: fixed; inset: 0; z-index: 1000;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .prog-modal-overlay.open {
            opacity: 1; pointer-events: all;
        }
        .prog-modal-box {
            background: white; border-radius: 20px;
            width: 480px; max-width: 92vw;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            transform: translateY(16px) scale(0.97);
            transition: transform 0.25s cubic-bezier(0.4,0,0.2,1);
            overflow: hidden;
        }
        .prog-modal-overlay.open .prog-modal-box {
            transform: translateY(0) scale(1);
        }
        .prog-modal-header {
            background: linear-gradient(135deg, #047857, #059669);
            padding: 20px 22px 16px;
            display: flex; align-items: flex-start; justify-content: space-between;
        }
        .prog-modal-title { color: white; font-size: 15px; font-weight: 700; }
        .prog-modal-subtitle { color: rgba(255,255,255,0.7); font-size: 11px; margin-top: 2px; }
        .prog-modal-close {
            width: 28px; height: 28px; border-radius: 8px;
            background: rgba(255,255,255,0.2); border: none; cursor: pointer;
            color: white; font-size: 16px; display: flex;
            align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .prog-modal-close:hover { background: rgba(255,255,255,0.35); }
        .prog-modal-body { padding: 20px 22px 24px; }
        .prog-modal-search {
            width: 100%; box-sizing: border-box;
            padding: 8px 14px 8px 36px;
            border: 1.5px solid #e2e8f0; border-radius: 10px;
            font-size: 13px; color: #334155; outline: none;
            transition: border-color 0.15s;
            margin-bottom: 16px;
            background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E") no-repeat 12px center;
        }
        .prog-modal-search:focus { border-color: #047857; background-color: white; }
        .prog-modal-pills { display: flex; flex-wrap: wrap; gap: 8px; min-height: 40px; }
        .prog-modal-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px 6px 10px; border-radius: 10px;
            font-size: 12px; font-weight: 700;
            background: #f1f5f9; color: #334155;
            border: 1.5px solid #e2e8f0;
            transition: all 0.15s; cursor: default;
        }
        .prog-modal-pill:hover {
            background: #e2e8f0; border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        .prog-modal-pill.hidden { display: none; }
        .prog-modal-empty {
            font-size: 12px; color: #94a3b8;
            font-style: italic; display: none; padding: 8px 0;
        }
        .prog-modal-footer {
            padding: 12px 22px; background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
        }
        .prog-modal-count { font-size: 11px; color: #64748b; font-weight: 600; }
        .prog-modal-done {
            padding: 7px 18px; border-radius: 8px;
            background: #047857; color: white; border: none;
            font-size: 12px; font-weight: 700; cursor: pointer;
            transition: background 0.15s;
        }
        .prog-modal-done:hover { background: #065f46; }
        .see-more-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 9999px;
            font-size: 11px; font-weight: 700;
            background: #f8fafc; color: #475569;
            border: 1.5px dashed #cbd5e1;
            cursor: pointer; transition: all 0.15s;
        }
        .see-more-btn:hover {
            background: #047857; color: white;
            border-color: #047857; border-style: solid;
        }

        /* ═══════════════════════════════════════════════════════
           RESPONSIVE IMPROVEMENTS
           ═══════════════════════════════════════════════════════ */

        /* Ensure all tables scroll horizontally on small screens */
        .overflow-x-auto { -webkit-overflow-scrolling: touch; }
        .overflow-x-auto > table { min-width: 600px; }

        /* Schedule table needs extra room */
        #schedMgmtBody, #schedMgmtBody tr { min-width: 900px; }
        table:has(#schedMgmtBody) { min-width: 900px; }

        /* Calendar needs min-width to scroll */
        .calendar-grid { min-width: 560px; }
        .calendar-cell { min-height: 70px; height: auto; }

        /* Notification dropdown: cap width on mobile */
        @media (max-width: 767px) {
            #adminNotifDropdown {
                width: min(340px, calc(100vw - 2rem));
                right: -1rem;
            }
            #analyticsCards {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            #proctorDatePills {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                flex-wrap: nowrap;
            }
        }

        @media (max-width: 639px) {
            /* Header */
            .top-header { padding: 0.625rem 0.875rem; min-height: 56px; }

            /* Main content area */
            main.flex-1 { padding: 0.875rem !important; }

            /* Stat cards: smaller on mobile */
            .stat-card-small { padding: 0.875rem 1rem; }
            .stat-card-small h3 { font-size: 1.4rem; }

            /* Analytics card */
            .analytics-card { min-height: 90px; padding: 1rem; }

            /* Buttons */
            .btn-action-large { padding: 0.5rem 0.875rem; font-size: 0.8rem; }

            /* Toast: span full width */
            .toast { left: 0.75rem; right: 0.75rem; bottom: 0.75rem; text-align: center; }

            /* Table cells: tighter padding */
            table td { padding-left: 0.625rem !important; padding-right: 0.625rem !important; }
            table th { padding-left: 0.625rem !important; padding-right: 0.625rem !important; }

            /* Campus tab strip: always horizontal scroll */
            .campus-tab-strip { flex-wrap: nowrap !important; overflow-x: auto; -webkit-overflow-scrolling: touch; }

            /* Date filter pills: horizontal scroll */
            #analyticsDatePills, #proctorDatePills {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                flex-wrap: nowrap;
            }

            /* Filter selects: auto width */
            .filter-select { min-width: 0; }

            /* Dashboard 5-col grid → 2-col */
            .md\\:grid-cols-5 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        }
    </style>
</head>
<body class="h-full bg-slate-50">
    <div id="app" class="h-full w-full"></div>

   <script>
        // ─────────────────────────────────────────────────────────────────────────
        // CONSTANTS
        // ─────────────────────────────────────────────────────────────────────────
        let CAMPUSES = (() => {
            try {
                const saved = JSON.parse(localStorage.getItem('customCampuses'));
                return (Array.isArray(saved) && saved.length) ? saved : null;
            } catch(e) { return null; }
        })() || ['Quezon City', 'Valenzuela', 'Antipolo', 'Pampanga', 'Laguna', 'Nueva Ecija'];

        // ─── YEAR LEVELS (admin-configurable) ─────────────────────────────────
        let YEAR_LEVELS = (() => {
            try { return JSON.parse(localStorage.getItem('customYearLevels')) || null; } catch(e) { return null; }
        })() || ['1st Year','2nd Year','3rd Year','4th Year','5th Year','6th Year'];

        const SEMESTERS = ['1st Semester', '2nd Semester'];

        function yearLevelOptions(selected = '') {
            return `<option value="" disabled ${!selected ? 'selected' : ''}>Select Year Level</option>` +
                YEAR_LEVELS.map(y => `<option ${y === selected ? 'selected' : ''}>${y}</option>`).join('');
        }
        function saveYearLevels() {
            localStorage.setItem('customYearLevels', JSON.stringify(YEAR_LEVELS));
        }
        

        function campusOptions(selected = '') {
            // Campus admin: only show their own campus
            return `<option value="${currentUser.campus}" selected>${currentUser.campus}</option>`;
        }

        function campusFilterSelect(id, onchange = '') {
            // Campus admin: no filter UI needed — campus is always locked
            return `<input type="hidden" id="${id}" value="${currentUser.campus}">`;
        }

        function renderCampusTabs(view, filterFn, items) {
            // Campus admin: no tab strip — data is already filtered to their campus
            return '';
        }

    function feedbacksFilter() {
    const campus   = campusFilters['feedbacks'] || '';
    const college  = (document.getElementById('fbCollegeFilter')?.value  || '').toLowerCase();
    const program  = (document.getElementById('fbProgramFilter')?.value  || '').toLowerCase();
    const status   = (document.getElementById('fbStatusFilter')?.value   || '');
    const category = (document.getElementById('fbCategoryFilter')?.value || '').toLowerCase();
    const search   = (document.getElementById('fbSearchInput')?.value    || '').toLowerCase();

    let visible = 0;
    document.querySelectorAll('#feedbacksBody [data-campus-row]').forEach(card => {
        const rowCampus   = card.getAttribute('data-campus-row')  || '';
        const rowCollege  = (card.getAttribute('data-fb-college') || '').toLowerCase();
        const rowProgram  = (card.getAttribute('data-fb-program') || '').toLowerCase();
        const rowStatus   = card.getAttribute('data-fb-status')   || '';
        const rowCategory = (card.getAttribute('data-fb-category')|| '').toLowerCase();
        const rowSearch   = (card.getAttribute('data-fb-search')  || '').toLowerCase();

        const show =
            (!campus   || rowCampus  === campus)   &&
            (!college  || rowCollege === college)   &&
            (!program  || rowProgram === program)   &&
            (!status   || rowStatus  === status)    &&
            (!category || rowCategory === category) &&
            (!search   || rowSearch.includes(search));

        card.setAttribute('data-filtered', show ? 'false' : 'true');
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noResults = document.getElementById('fbNoResults');
    const body = document.getElementById('feedbacksBody');
    if (noResults) noResults.classList.toggle('hidden', visible > 0 || !body);

    pageState['feedbacks'] = 1;
    applyPagination('feedbacks');
}

window.updateFbProgramOptions = function() {
    const selectedCollege = (document.getElementById('fbCollegeFilter')?.value || '').toLowerCase();
    const programSel = document.getElementById('fbProgramFilter');
    if (!programSel) return;
    const feedbacks = allData.filter(d => d.type === 'feedback');
    const programs = [...new Set(
        feedbacks
            .filter(f => !selectedCollege || (f.college||'').toLowerCase() === selectedCollege)
            .map(f => (f.program||'').trim())
            .filter(Boolean)
    )].sort();
    programSel.innerHTML = '<option value="">All Programs</option>' +
        programs.map(p => `<option value="${p}">${p}</option>`).join('');
};

window.clearFbFilters = function() {
    ['fbCollegeFilter','fbProgramFilter','fbStatusFilter','fbCategoryFilter'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const si = document.getElementById('fbSearchInput');
    if (si) si.value = '';
    feedbacksFilter();
};

window.toggleProgWrap = function(id) {
    const wrap = document.getElementById(`prog-wrap-${id}`);
    const btn  = document.getElementById(`prog-btn-${id}`);
    if (!wrap) return;

    const isExpanded = wrap.classList.toggle('expanded');
    if (btn) btn.textContent = isExpanded ? 'show less' : `show all`;
};

window.openProgramsModal = function(courseId, programs, courseCode) {
    if (document.getElementById('programsModal')) document.getElementById('programsModal').remove();

    const buildBadgeHtml = (prog) => {
        const colRec = allData.find(d =>
            d.type === 'college' &&
            Array.isArray(d.programs) &&
            d.programs.some(p => {
                const a = p.includes('=') ? p.split('=')[0].trim() : p.trim();
                return a.toLowerCase() === prog.toLowerCase();
            })
        );
        const fullName = colRec
            ? ((colRec.programs.find(p => {
                const a = p.includes('=') ? p.split('=')[0].trim() : p.trim();
                return a.toLowerCase() === prog.toLowerCase();
              }) || '').split('=')[1]?.trim() || '')
            : '';
        return `<span class="prog-modal-pill" data-name="${(prog + ' ' + fullName).toLowerCase()}">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold shrink-0">
                ${esc(prog.charAt(0))}
            </span>
            <span class="font-bold text-slate-700">${esc(prog)}</span>
            ${fullName ? `<span class="text-slate-400">— ${esc(fullName)}</span>` : ''}
        </span>`;
    };

    const modal = document.createElement('div');
    modal.id = 'programsModal';
    modal.className = 'prog-modal-overlay';
    modal.innerHTML = `
    <div class="prog-modal-box">
        <div class="prog-modal-header">
            <div>
                <div class="prog-modal-title">
                    <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/>
                    </svg>
                    ${esc(courseCode)} — Programs
                </div>
                <div class="prog-modal-subtitle">${programs.length} program${programs.length !== 1 ? 's' : ''} enrolled in this course</div>
            </div>
            <button class="prog-modal-close" onclick="document.getElementById('programsModal').remove()">✕</button>
        </div>
        <div class="prog-modal-body">
            <input
                type="text"
                class="prog-modal-search"
                placeholder="Search programs..."
                oninput="filterProgramPills(this.value)"
            />
            <div class="prog-modal-pills" id="programPillsContainer">
                ${programs.map(buildBadgeHtml).join('')}
            </div>
            <div class="prog-modal-empty" id="programsEmptyMsg">No programs match your search.</div>
        </div>
        <div class="prog-modal-footer">
            <span class="prog-modal-count" id="programsModalCount">${programs.length} program${programs.length !== 1 ? 's' : ''}</span>
            <button class="prog-modal-done" onclick="document.getElementById('programsModal').remove()">Done</button>
        </div>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
    requestAnimationFrame(() => modal.classList.add('open'));
    modal.addEventListener('click', e => { if (e.target === modal) { modal.classList.remove('open'); setTimeout(() => modal.remove(), 200); } });
};

window.filterProgramPills = function(query) {
    const q = query.toLowerCase();
    const pills = document.querySelectorAll('#programPillsContainer .prog-modal-pill');
    let visible = 0;
    pills.forEach(pill => {
        const name = pill.getAttribute('data-name') || '';
        const show = !q || name.includes(q);
        pill.classList.toggle('hidden', !show);
        if (show) visible++;
    });
    const empty = document.getElementById('programsEmptyMsg');
    const count = document.getElementById('programsModalCount');
    if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
    if (count) count.textContent = `${visible} program${visible !== 1 ? 's' : ''}`;
};

window.markAllFeedbacksRead = async function() {
    const res = await window.flexamApi.feedbacks.markRead(0);
    if (res.success) { showToast('All feedbacks marked as read'); await refreshAllData(); }
    else showToast(res.message || 'Error', 'error');
};

window.deleteFeedback = async function(id) {
    if (!confirm('Delete this feedback?')) return;
    const res = await window.flexamApi.feedbacks.delete(id);
    if (res.success) { showToast('Feedback deleted'); await refreshAllData(); }
    else showToast(res.message || 'Error', 'error');
};

        // ─────────────────────────────────────────────────────────────────────────
        // 1. SESSION INITIALIZATION
        // ─────────────────────────────────────────────────────────────────────────
        let currentUser = <?php echo json_encode($sessionUser); ?>;
        sessionStorage.setItem('currentUser', JSON.stringify(currentUser));

        // ─────────────────────────────────────────────────────────────────────────
        // 2. GLOBAL STATE
        // ─────────────────────────────────────────────────────────────────────────
        let currentView = localStorage.getItem('flexam_currentView') || 'dashboard'; 
        let allData = [];
        let adminReadNotifs = new Set(JSON.parse(localStorage.getItem('adminReadNotifs') || '[]'));
        let calendarMonth = new Date().getMonth();
        let calendarYear = new Date().getFullYear();
        let sidebarScrollPosition = 0;
        let lastImportLog = (() => { try { const s = sessionStorage.getItem('flexam_importLog'); return s ? JSON.parse(s) : null; } catch(e) { return null; } })();
        // ── Restore pending import conflicts so the Auto-Resolved widget persists across reloads ──
        window._importResolvableRows = (() => { try { const s = localStorage.getItem('flexam_pendingImportConflicts'); return s ? JSON.parse(s) : []; } catch(e) { return []; } })();

        // ─────────────────────────────────────────────────────────────────────────
        // ACTIVITY LOG ENGINE
        // ─────────────────────────────────────────────────────────────────────────
        const FLEXAM_LOG_KEY = 'flexam_activityLog';
        const LOG_MAX = 500; // keep last 500 entries

        function activityLog(action, details = '', targetUser = null) {
            try {
                const existing = JSON.parse(localStorage.getItem(FLEXAM_LOG_KEY) || '[]');
                const entry = {
                    id:         Date.now() + Math.random().toString(36).slice(2, 6),
                    timestamp:  new Date().toISOString(),
                    actor:      currentUser.full_name || currentUser.username || 'Unknown',
                    actorId:    currentUser.id || null,
                    actorRole:  currentUser.role || 'Admin',
                    actorCampus: currentUser.campus || '',
                    action,
                    details,
                    targetUser: targetUser || null,
                };
                existing.unshift(entry);
                if (existing.length > LOG_MAX) existing.length = LOG_MAX;
                localStorage.setItem(FLEXAM_LOG_KEY, JSON.stringify(existing));
            } catch(e) { /* silently fail */ }
        }

        function getAllLogs() {
            try { return JSON.parse(localStorage.getItem(FLEXAM_LOG_KEY) || '[]'); } catch(e) { return []; }
        }

        function clearAllLogs() {
            localStorage.removeItem(FLEXAM_LOG_KEY);
        }

        // Room Utilization pagination state
        let roomUtilPage = 1;
        let analyticsDateFilter = 'all'; // 'all' | 'day' | 'week' | 'month'
        const ROOM_UTIL_PER_PAGE = 10;

        // Per-view campus filter state
        let campusFilters = {
            'schedule-mgmt': currentUser.campus || '',
            'view-schedule': currentUser.campus || '',
            'analytics':     currentUser.campus || '',
            'feedbacks':     currentUser.campus || '',
            'users':         currentUser.campus || '',
            'courses':       currentUser.campus || '',
            'rooms':         currentUser.campus || '',
            'colleges':      currentUser.campus || '',
            'proctors':      currentUser.campus || ''
        };

        // ── Pagination ────────────────────────────────────────────────────────
        const PAGE_SIZE = 15;
        let pageState = { users: 1, courses: 1, rooms: 1, colleges: 1, proctors: 1, feedbacks: 1, 'schedule-mgmt': 1, 'view-schedule': 1 };

        function showGlobalPager(view, total, perPage) {
            const bar = document.getElementById('global-pager');
            const info = document.getElementById('global-pager-info');
            const controls = document.getElementById('global-pager-controls');
            if (!bar || !info || !controls) return;
            const totalPages = Math.ceil(total / perPage);
            if (totalPages <= 1) { bar.classList.add('hidden'); return; }
            bar.classList.remove('hidden');
            // Add bottom padding to main so content isn't hidden behind bar
            const main = document.querySelector('main');
            if (main) main.style.paddingBottom = '56px';
            const cur = pageState[view] || 1;
            info.textContent = 'Page ' + cur + ' of ' + totalPages + '  ·  ' + total + ' records';
            const pages = [];
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= cur - 2 && i <= cur + 2)) pages.push(i);
                else if (pages[pages.length - 1] !== '...') pages.push('...');
            }
            let btns = '<button onclick="goPage(\'' + view + '\',' + (cur-1) + ')" ' + (cur<=1?'disabled':'') + ' class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition">&#8249; Prev</button>';
            pages.forEach(function(p) {
                if (p === '...') { btns += '<span class="px-2 text-slate-400 text-xs">&#8230;</span>'; }
                else { btns += '<button onclick="goPage(\'' + view + '\',' + p + ')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition ' + (p===cur?'bg-emerald-600 text-white shadow-sm':'text-slate-500 hover:bg-slate-100') + '">' + p + '</button>'; }
            });
            btns += '<button onclick="goPage(\'' + view + '\',' + (cur+1) + ')" ' + (cur>=totalPages?'disabled':'') + ' class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition">Next &#8250;</button>';
            controls.innerHTML = btns;
        }

        function hideGlobalPager() {
            const bar = document.getElementById('global-pager');
            if (bar) bar.classList.add('hidden');
            const main = document.querySelector('main');
            if (main) main.style.paddingBottom = '';
        }

        function renderPager(view, total, perPage) {
            showGlobalPager(view, total, perPage);
            // Also render inline pager if element exists (e.g. pager-rooms)
            const inlineEl = document.getElementById('pager-' + view);
            if (!inlineEl) return '';
            const totalPages = Math.ceil(total / perPage);
            const cur = pageState[view] || 1;
            if (totalPages <= 1) { inlineEl.innerHTML = ''; return ''; }
            const pages = [];
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= cur - 2 && i <= cur + 2)) pages.push(i);
                else if (pages[pages.length - 1] !== '...') pages.push('...');
            }
            let btns = '<span class="text-xs text-slate-400">Page ' + cur + ' of ' + totalPages + '</span><div class="flex items-center gap-1">';
            btns += '<button onclick="goPage(\'' + view + '\',' + (cur-1) + ')" ' + (cur<=1?'disabled':'') + ' class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition">&#8249; Prev</button>';
            pages.forEach(function(p) {
                if (p === '...') { btns += '<span class="px-2 text-slate-400 text-xs">&#8230;</span>'; }
                else { btns += '<button onclick="goPage(\'' + view + '\',' + p + ')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition ' + (p===cur?'bg-emerald-600 text-white shadow-sm':'text-slate-500 hover:bg-slate-100') + '">' + p + '</button>'; }
            });
            btns += '<button onclick="goPage(\'' + view + '\',' + (cur+1) + ')" ' + (cur>=totalPages?'disabled':'') + ' class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed transition">Next &#8250;</button></div>';
            inlineEl.innerHTML = btns;
            return '';
        }

        function goPage(view, page) {
            const allRows = Array.from(document.querySelectorAll('[data-page-view="' + view + '"]'));
            if (!allRows.length) return;
            const visibleRows = allRows.filter(r => r.getAttribute('data-filtered') !== 'true');
            if (!visibleRows.length) return;
            const totalPages = Math.ceil(visibleRows.length / PAGE_SIZE);
            page = Math.max(1, Math.min(page, totalPages));
            pageState[view] = page;
            const start = (page - 1) * PAGE_SIZE;
            allRows.forEach(function(row) { row.style.display = 'none'; });
            visibleRows.forEach(function(row, i) { row.style.display = (i >= start && i < start + PAGE_SIZE) ? '' : 'none'; });
            const pagerEl = document.getElementById('pager-' + view);
            if (pagerEl) pagerEl.innerHTML = renderPager(view, visibleRows.length, PAGE_SIZE);
            // After page change, sync checkboxes to cross-page selection state
            if (view === 'schedule-mgmt' && typeof _syncCurrentPageCheckboxes === 'function') {
                _syncCurrentPageCheckboxes();
                window.onSchedRowCbChange();
            }
        }

        function applyPagination(view) {
            pageState[view] = 1;
            goPage(view, 1);
        }

        // Override goPage for users (uses JS-rendered rows, not static DOM)
        const _origGoPage = goPage;
        goPage = function(view, page) {
            if (view === 'users') {
                const users = window._usersFiltered || [];
                if (!users.length) return;
                const totalPages = Math.ceil(users.length / PAGE_SIZE);
                page = Math.max(1, Math.min(page, totalPages));
                pageState['users'] = page;
                const tbody = document.getElementById('usersTableBody');
                const tableFooter = document.getElementById('tableFooter');
                if (!tbody) return;
                const start = (page - 1) * PAGE_SIZE;
                const paged = users.slice(start, start + PAGE_SIZE);
                tbody.innerHTML = paged.map((u, i) => `
                <tr class="hover:bg-slate-50 transition" data-id="${u.id}">
                    <td class="px-5 py-3 text-slate-400 text-xs">${start + i + 1}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 ${u.role === 'Admin' ? 'bg-emerald-600' : 'bg-blue-600'}">${u.full_name.charAt(0).toUpperCase()}</div>
                            <span class="font-semibold text-slate-800">${esc(u.full_name)}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">${esc(u.username)}</td>
                    <td class="px-5 py-3"><span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold ${u.role === 'Admin' ? 'badge-admin' : 'badge-head'}">${esc(u.role)}</span></td>
                    <td class="px-5 py-3 text-slate-600">${esc(u.email || '—')}</td>
                    <td class="px-5 py-3">${u.campus ? `<span class="campus-badge">${esc(u.campus)}</span>` : '<span class="text-slate-400">—</span>'}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <button onclick="openEdit(${u.id})" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                            <button onclick="openDelete(${u.id}, '${esc(u.username)}')" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </td>
                </tr>`).join('');
                if (tableFooter) tableFooter.textContent = `Showing ${start+1}–${Math.min(start+PAGE_SIZE, users.length)} of ${users.length} users`;
                showGlobalPager('users', users.length, PAGE_SIZE);
            } else {
                _origGoPage(view, page);
            }
        };

            async function refreshAllData() {
    try {
        const [colRes, romRes, proRes, couRes, schRes, feeRes, spExRes] = await Promise.all([
            window.flexamApi.colleges.list(),
            window.flexamApi.rooms.list(),
            window.flexamApi.proctors.list(),
            window.flexamApi.courses.list(),
            window.flexamApi.schedules.list(),
            window.flexamApi.feedbacks.list(),   // ← ADD THIS
            window.flexamApi.special_exams.list()
        ]);

        // ── Campus Admin: filter special exams to own campus only ─────────────
        const myCampusCA = (currentUser.campus || '').trim();
        window._specialExams = (spExRes.data || []).filter(e =>
            !myCampusCA || (e.campus || '').trim() === myCampusCA
        );

        allData = [
            ...(colRes.data || []).map(i => ({ ...i, type: 'college' })),
            ...(romRes.data || []).map(i => ({ ...i, type: 'room' })),
            ...(proRes.data || []).map(i => ({ ...i, type: 'proctor' })),
            ...(couRes.data || []).map(i => ({ ...i, type: 'course' })),
            ...(schRes.data || []).map(i => ({ ...i, type: 'schedule' })),
            ...(feeRes.data || []).map(i => ({ ...i, type: 'feedback' }))
        ];

        // ── Campus Admin: restrict allData to own campus only ──────────────────
        if (currentUser.campus) {
            // Assign the admin's campus to colleges that have no campus set
            // (colleges created before campus tracking was added)
            allData = allData.map(d => {
                if (d.type === 'college' && !d.campus) {
                    return { ...d, campus: currentUser.campus };
                }
                return d;
            });
            allData = allData.filter(d => !d.campus || (d.campus || '').trim() === currentUser.campus.trim());
        }
        renderApp();
    } catch (err) {
        console.error("Database Refresh Failed:", err);
    }
}

        document.addEventListener('DOMContentLoaded', refreshAllData);
        document.addEventListener('DOMContentLoaded', function() {
            activityLog('Session Started', `Logged in as ${currentUser.role}`);
        });

        // ─────────────────────────────────────────────────────────────────────────
        // REAL-TIME SYNC — polls schedules every 5 s, re-renders only on change
        // ─────────────────────────────────────────────────────────────────────────
        (function startRealtimeSync() {
            // Per-type fingerprints: count|firstId|lastId
            const _hashes = { schedule: '', college: '', room: '', proctor: '', course: '' };
            let _syncPaused = false;

            // Pause while an import is running so we don't clobber in-progress state
            document.addEventListener('flexam:importStart', () => { _syncPaused = true;  });
            document.addEventListener('flexam:importEnd',   () => { _syncPaused = false; });

            function _fp(items) {
                return items.length + '|' + (items[0]?.id ?? '') + '|' + (items[items.length - 1]?.id ?? '');
            }

            async function _pollAllData() {
                if (_syncPaused) return;
                try {
                    const [colRes, romRes, proRes, couRes, schRes] = await Promise.all([
                        window.flexamApi.colleges.list(),
                        window.flexamApi.rooms.list(),
                        window.flexamApi.proctors.list(),
                        window.flexamApi.courses.list(),
                        window.flexamApi.schedules.list(),
                    ]);

                    const fresh = {
                        college:  (colRes.data  || []),
                        room:     (romRes.data  || []),
                        proctor:  (proRes.data  || []),
                        course:   (couRes.data  || []),
                        schedule: (schRes.data  || []),
                    };

                    let changed = false;
                    for (const [type, items] of Object.entries(fresh)) {
                        const fp = _fp(items);
                        if (fp !== _hashes[type]) { _hashes[type] = fp; changed = true; }
                    }

                    // ── Also detect when import conflicts change (dismissed / resolved / errored) ──
                    const _curImportLen = (window._importResolvableRows || []).filter(r => r.canAutoResolve).length;
                    if (_curImportLen !== (_hashes._importRowCount ?? _curImportLen)) { changed = true; }
                    _hashes._importRowCount = _curImportLen;

                    if (!changed) return; // nothing new — skip re-render

                    allData = [
                        ...fresh.college.map(i  => ({ ...i, type: 'college'  })),
                        ...fresh.room.map(i     => ({ ...i, type: 'room'     })),
                        ...fresh.proctor.map(i  => ({ ...i, type: 'proctor'  })),
                        ...fresh.course.map(i   => ({ ...i, type: 'course'   })),
                        ...fresh.schedule.map(i => ({ ...i, type: 'schedule' })),
                        // keep feedbacks — not polled (only changes on explicit action)
                        ...allData.filter(d => d.type === 'feedback'),
                    ];

                    // ── Campus Admin: restrict to own campus only ──────────────
                    if (currentUser.campus) {
                        // Assign the admin's campus to colleges with no campus set
                        allData = allData.map(d => {
                            if (d.type === 'college' && !d.campus) {
                                return { ...d, campus: currentUser.campus };
                            }
                            return d;
                        });
                        allData = allData.filter(d => !d.campus || (d.campus || '').trim() === currentUser.campus.trim());
                    }
                    renderApp();

                    // Flash the live dot indicator
                    const dot = document.getElementById('_realtimeDot');
                    if (dot) { dot.style.opacity = '1'; setTimeout(() => { dot.style.opacity = '0'; }, 1200); }
                } catch(e) { /* network blip — skip silently */ }
            }

            // Set initial fingerprints after first full load settles
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    for (const type of ['college', 'room', 'proctor', 'course', 'schedule']) {
                        const items = allData.filter(d => d.type === type);
                        _hashes[type] = _fp(items);
                    }
                }, 1500);
            });

            setInterval(_pollAllData, 5000);
        })();

        // ─────────────────────────────────────────────────────────────────────────
        // 3. SIGN OUT
        // ─────────────────────────────────────────────────────────────────────────
        window.handleSignOut = function() {
            // Show confirmation modal
            if (document.getElementById('logoutConfirmModal')) return;
            const modal = document.createElement('div');
            modal.id = 'logoutConfirmModal';
            modal.className = 'fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4';
            modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-fadeIn">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </div>
            <h3 class="text-lg font-bold text-slate-800 mb-1">Sign Out</h3>
            <p class="text-sm text-slate-500 mb-6">Are you sure you want to sign out of FLEXAM?</p>
            <div class="flex gap-3">
                <button onclick="document.getElementById('logoutConfirmModal').remove()"
                    class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-sm">
                    Cancel
                </button>
                <button onclick="document.getElementById('logoutConfirmModal').remove();sessionStorage.clear();localStorage.removeItem('currentUser');window.location.href='../logout.php';"
                    class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-sm">
                    Sign Out
                </button>
            </div>
        </div>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
    // Close on backdrop click
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

        // ─────────────────────────────────────────────────────────────────────────
        // 4. SDK STUB
        // ─────────────────────────────────────────────────────────────────────────
        if (!window.dataSdk) {
            window.dataSdk = {
                init: function(config) {
                    const stored = JSON.parse(localStorage.getItem('flexam_data')) || [];
                    config.onDataChanged(stored);
                }
            };
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 5. CORE RENDER
        // ─────────────────────────────────────────────────────────────────────────
        function renderApp() {
        const app = document.getElementById('app');
        if (!app) return;
        localStorage.setItem('flexam_currentView', currentView);
        app.innerHTML = `
                <div class="h-full flex overflow-hidden">
                    <!-- Mobile sidebar overlay -->
                    <div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" onclick="closeSidebar()"></div>
                    ${renderSidebar()}
                    <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-left">
                        ${renderHeader()}
                        <main class="flex-1 overflow-y-auto p-4 md:p-6 bg-slate-50 relative">
                            ${renderMainContent()}
                        </main>
                    </div>
                </div>
            `;
            attachHandlers();
            if (typeof restoreSidebarScroll === "function") restoreSidebarScroll();
            // Re-populate announcement sections from cache after DOM is rebuilt
            if (typeof window._flexAnnRefresh === 'function') window._flexAnnRefresh();
            // Initialize pagination for the current view after DOM is ready
            hideGlobalPager();
            const paginatedViews = ['rooms', 'courses', 'colleges', 'proctors', 'feedbacks', 'schedule-mgmt', 'view-schedule'];
            if (paginatedViews.includes(currentView)) {
                setTimeout(() => applyPagination(currentView), 0);
            }
            if (currentView === 'users') {
                setTimeout(() => loadUsers(), 0);
            }
            if (currentView === 'logs') {
                setTimeout(() => window.renderLogsView(), 0);
            }
        }

        window.toggleSidebar = function() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (!sidebar) return;
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            sidebar.classList.toggle('-translate-x-full', isOpen);
            if (overlay) overlay.classList.toggle('hidden', isOpen);
        };
        window.closeSidebar = function() {
            const sidebar = document.getElementById('mainSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
        };

        function showDuplicateWarning(wrapperId, message) {
    let el = document.getElementById(wrapperId);
    if (!el) {
        el = document.createElement('div');
        el.id = wrapperId;
        el.className = 'flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-semibold animate-fadeIn';
        el.innerHTML = `
            <svg class="w-4 h-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span id="${wrapperId}-text"></span>`;
        // Insert at top of form, after any heading
        const form = document.querySelector(`#${wrapperId}`);
        if (!form) return;
    }
    document.getElementById(`${wrapperId}-text`).textContent = message;
    el.classList.remove('hidden');
}

    function hideDuplicateWarning(wrapperId) {
    const el = document.getElementById(wrapperId);
    if (el) el.classList.add('hidden');
}

    function checkAndShowDuplicate(formId, bannerId, isDuplicate, message) {
    const form = document.getElementById(formId);
    if (!form) return;

    let banner = document.getElementById(bannerId);
    if (!banner) {
        banner = document.createElement('div');
        banner.id = bannerId;
        banner.className = 'hidden flex items-center gap-3 px-4 py-3 mb-4 rounded-xl bg-red-50 border border-red-300 text-red-700 text-sm font-semibold';
        banner.innerHTML = `
            <div class="w-7 h-7 shrink-0 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <span class="duplicate-banner-text flex-1"></span>`;
        form.insertBefore(banner, form.firstChild);
    }

    if (isDuplicate) {
        banner.querySelector('.duplicate-banner-text').innerHTML = message;
        banner.classList.remove('hidden');
        banner.classList.add('flex');
    } else {
        banner.classList.add('hidden');
        banner.classList.remove('flex');
    }

    // Also block/unblock the submit button
    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = isDuplicate;
        submitBtn.classList.toggle('opacity-50', isDuplicate);
        submitBtn.classList.toggle('cursor-not-allowed', isDuplicate);
    }
}


async function safeFetch(url, opts = {}) {
    try {
        const res  = await fetch(url, opts);
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch {
            // Server returned HTML/PHP error — extract a readable message
            const match = text.match(/<b>(?:Fatal error|Parse error|Warning|Notice)<\/b>:\s*(.+?)(?:\s+in\s+<b>|$)/i)
                       || text.match(/(?:Fatal error|Parse error|Warning|Notice):\s*(.+?)(?:\s+in\s+|$)/i);
            const msg = match
                ? match[1].replace(/<[^>]+>/g, '').trim()
                : (text.length < 300 ? text.replace(/<[^>]+>/g, '').trim() : 'Server error — check PHP logs');
            return { success: false, message: msg };
        }
    } catch (err) {
        return { success: false, message: err.message || 'Network error' };
    }
}
window.reopenImportLog = function() {
    if (!lastImportLog) return;
    showImportResults(
        lastImportLog.success,
        lastImportLog.failed,
        lastImportLog.skippedRows
    );
};

window.flexamApi = {
    colleges: {
        list:   ()     => safeFetch('../api/colleges.php?action=list'),
        create: (data) => safeFetch('../api/colleges.php?action=create', { method: 'POST', body: JSON.stringify(data) }),
        update: (data) => safeFetch('../api/colleges.php?action=update', { method: 'POST', body: JSON.stringify(data) }),
        delete: (id)   => safeFetch('../api/colleges.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) })
    },
    rooms: {
        list:    ()     => safeFetch('../api/rooms.php?action=list'),
        create:  (data) => safeFetch('../api/rooms.php?action=create',  { method: 'POST', body: JSON.stringify(data) }),
        update:  (data) => safeFetch('../api/rooms.php?action=update',  { method: 'POST', body: JSON.stringify(data) }),
        delete:  (id)   => safeFetch('../api/rooms.php?action=delete',  { method: 'POST', body: JSON.stringify({ id }) }),
        block:   (data) => safeFetch('../api/rooms.php?action=block',   { method: 'POST', body: JSON.stringify(data) }),
        unblock: (id)   => safeFetch('../api/rooms.php?action=unblock', { method: 'POST', body: JSON.stringify({ id }) }),
    },
    proctors: {
        list:   ()     => safeFetch('../api/proctors.php?action=list'),
        create: (data) => safeFetch('../api/proctors.php?action=create', { method: 'POST', body: JSON.stringify(data) }),
        update: (data) => safeFetch('../api/proctors.php?action=update', { method: 'POST', body: JSON.stringify(data) }),
        delete: (id)   => safeFetch('../api/proctors.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) })
    },
    courses: {
        list:   ()     => safeFetch('../api/courses.php?action=list'),
        create: (data) => safeFetch('../api/courses.php?action=create', { method: 'POST', body: JSON.stringify(data) }),
        update: (data) => safeFetch('../api/courses.php?action=update', { method: 'POST', body: JSON.stringify(data) }),
        delete: (id)   => safeFetch('../api/courses.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) })
    },
    schedules: {
        list:    ()     => safeFetch('../api/schedules.php?action=list'),
        create:  (data) => safeFetch('../api/schedules.php?action=create',  { method: 'POST', body: JSON.stringify(data) }),
        update:  (data) => safeFetch('../api/schedules.php?action=update',  { method: 'POST', body: JSON.stringify(data) }),
        delete:  (id)   => safeFetch('../api/schedules.php?action=delete',  { method: 'POST', body: JSON.stringify({ id }) }),
        approve: (id)   => safeFetch('../api/schedules.php?action=approve', { method: 'POST', body: JSON.stringify({ id }) }),
        reject:  (id)   => safeFetch('../api/schedules.php?action=reject',  { method: 'POST', body: JSON.stringify({ id }) })
    },
    special_exams: {
        list:   ()     => safeFetch('../api/special_exams.php?action=list'),
        create: (data) => safeFetch('../api/special_exams.php?action=create', { method: 'POST', body: JSON.stringify(data) }),
        update: (data) => safeFetch('../api/special_exams.php?action=update', { method: 'POST', body: JSON.stringify(data) }),
        delete: (id)   => safeFetch('../api/special_exams.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) })
    },
    feedbacks: {
        list:     ()    => safeFetch('../api/feedbacks.php?action=list'),
        markRead: (id)  => safeFetch('../api/feedbacks.php?action=mark_read', { method: 'POST', body: JSON.stringify({ id }) }),
        delete:   (id)  => safeFetch('../api/feedbacks.php?action=delete',    { method: 'POST', body: JSON.stringify({ id }) }),
    },
};

// ─────────────────────────────────────────────────────────────────────────
// DIRECT SCHEDULE ACTIONS (Approve, Reject, Delete)
// ─────────────────────────────────────────────────────────────────────────

window.approveSchedule = async function(id) {
    // ── Overdue guard: block approval if exam date has already passed ──
    const sched = allData.find(d => d.type === 'schedule' && String(d.id) === String(id));
    if (sched) {
        const rawDate = sched.exam_date || sched.date || '';
        if (rawDate) {
            const examDate = new Date(rawDate + 'T00:00:00');
            const today    = new Date();
            today.setHours(0, 0, 0, 0);
            if (examDate < today) {
                showToast('Cannot approve — this schedule is already overdue.', 'error');
                return;
            }
        }

        // ── Pre-approval conflict checks against already-Approved schedules ──
        const approved = allData.filter(d =>
            d.type === 'schedule' &&
            d.status === 'Approved' &&
            String(d.id) !== String(id)
        );
        const rid  = String(sched.room_id || sched.room || '');
        const date = (sched.exam_date || sched.date || '');
        const slot = (sched.time_slot || '');
        const sec  = (sched.section || sched.section_name || '').trim().toLowerCase();
        const pid  = String(sched.proctor_id || '');

        // Room conflict
        if (rid && rid !== '0' && date && slot) {
            const roomClash = approved.find(d =>
                dbId(String(d.room_id || d.room || '')) === dbId(rid) &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === slot
            );
            if (roomClash) {
                showToast(`Cannot approve — room "${sched.room_name || rid}" is already booked by "${roomClash.course_code || roomClash.course_name || 'another exam'}" at ${slot} on ${date}.`, 'error');
                return;
            }
        }
        // Proctor conflict
        if (pid && pid !== '0' && date && slot) {
            const proctorClash = approved.find(d =>
                String(d.proctor_id || '') === pid &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === slot
            );
            if (proctorClash) {
                showToast(`Cannot approve — proctor "${sched.proctor_name || 'assigned proctor'}" is already assigned to "${proctorClash.course_code || 'another exam'}" (Room: ${proctorClash.room_name || '—'}) at ${slot} on ${date}.`, 'error');
                return;
            }
        }
        // Section time overlap
        if (sec && date && slot) {
            const schedCollege = (sched.college || '').trim().toLowerCase();
        const sectionClash = schedCollege ? approved.find(d =>
                (d.section || d.section_name || '').trim().toLowerCase() === sec &&
                (d.college || '').trim().toLowerCase() === schedCollege &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === slot
            ) : null;
            if (sectionClash) {
                showToast(`Cannot approve — section "${sched.section || sched.section_name}" (${sched.college}) is already scheduled at ${slot} on ${date} for "${sectionClash.course_code || 'another exam'}".`, 'error');
                return;
            }
        }

        // ── Duplicate check — block if an identical schedule already exists ──
        const allNonRejected = allData.filter(d =>
            d.type === 'schedule' &&
            (d.status || '') !== 'Rejected' &&
            String(d.id) !== String(id)
        );
        const _courseId = dbId(String(sched.course_id || ''));
        const _examType = sched.exam_type || '';
        const _campus   = sched.campus || '';
        const _section  = sec;
        const _college  = (sched.college || '').trim().toLowerCase();
        const _program  = (sched.program || '').trim().toLowerCase();
        const dupMatch  = allNonRejected.find(o => {
            const oCourseId = dbId(String(o.course_id || ''));
            const oDate     = o.exam_date || o.date || '';
            const oSlot     = o.time_slot || '';
            const oSection  = (o.section || o.section_name || o.class_section || '').trim().toLowerCase();
            const oCollege  = (o.college || '').trim().toLowerCase();
            const oProgram  = (o.program || '').trim().toLowerCase();
            // Different program = never a duplicate (e.g. BSIT vs BSCS)
            if (_program && oProgram && _program !== oProgram) return false;
            if (_campus && _courseId && _examType && date && slot && _section &&
                (o.campus||'') === _campus && oCourseId === _courseId &&
                (o.exam_type||'') === _examType && oDate === date && oSlot === slot &&
                oSection === _section) return true;
            if (_section && _college && _courseId && _examType && date &&
                oSection === _section && oCollege === _college &&
                oCourseId === _courseId && (o.exam_type||'') === _examType && oDate === date) return true;
            if (_section && _college && date && slot &&
                oSection === _section && oCollege === _college &&
                oDate === date && oSlot === slot) return true;
            return false;
        });
        if (dupMatch) {
            showToast(`Cannot approve — duplicate detected. "${sched.course_code || sched.course_name || 'This schedule'}" for section "${sched.section || sched.section_name || '—'}" already exists (ID ${dupMatch.id}, Status: ${dupMatch.status}).`, 'error');
            return;
        }
        // ─────────────────────────────────────────────────────────────────────
    }

    if (!confirm('Approve this schedule?')) return;

    try {
        const result = await window.flexamApi.schedules.approve(id);
        if (result.success) {
            const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id));
            activityLog('Schedule Approved', s ? `${s.course_code || s.course_name || ''} · ${s.exam_type || ''} · ${s.exam_date || ''}` : `ID ${id}`);
            showToast('Schedule Approved!');
            await refreshAllData();   // re-fetches from DB and re-renders the table
        } else {
            showToast(result.message || 'Failed to approve schedule', 'error');
        }
    } catch (err) {
        console.error('Approve Error:', err);
        showToast('System error during approval', 'error');
    }
};

window.rejectSchedule = async function(id) {
    const reason = prompt('Optional: Enter rejection reason:');
    if (reason === null) return;   // user hit Cancel → do nothing

    try {
        const result = await window.flexamApi.schedules.reject(id);
        if (result.success) {
            const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id));
            activityLog('Schedule Rejected', s ? `${s.course_code || s.course_name || ''} · ${s.exam_type || ''}${reason ? ' · Reason: ' + reason : ''}` : `ID ${id}`);
            showToast('Schedule has been rejected');
            await refreshAllData();
        } else {
            showToast(result.message || 'Failed to reject schedule', 'error');
        }
    } catch (err) {
        console.error('Reject Error:', err);
        showToast('System error during rejection', 'error');
    }
};

window.deleteSchedule = async function(id) {
    if (!confirm('Are you sure you want to delete this schedule? This action cannot be undone.')) return;
    
    try {
        const result = await window.flexamApi.schedules.delete(id);
        if (result.success) {
            const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id));
            activityLog('Schedule Deleted', s ? `${s.course_code || s.course_name || ''} · ${s.exam_type || ''} · ${s.exam_date || ''}` : `ID ${id}`);
            showToast('Schedule deleted successfully');
            await refreshAllData();
        } else {
            showToast(result.message || 'Failed to delete schedule', 'error');
        }
    } catch (err) {
        console.error("Delete Error:", err);
        showToast('System error during deletion', 'error');
    }
};

        // ─────────────────────────────────────────────────────────────────────────
        // BULK SCHEDULE SELECTION & ACTIONS
        // ─────────────────────────────────────────────────────────────────────────

        // ── Cross-page selection state ────────────────────────────────────────
        // Stores IDs selected across ALL pages (not just the visible page)
        let _schedAllPagesSelected = false;  // true = "select all pages" mode
        const _schedCrossPageIds = new Set(); // manual cross-page selections

        function getSelectedSchedIds() {
            if (_schedAllPagesSelected) {
                // Return ALL eligible IDs across every page
                return [...document.querySelectorAll('#schedMgmtBody .sched-row-cb')]
                    .map(cb => cb.getAttribute('data-id'));
            }
            // Normal mode: union of DOM checked + cross-page set
            const domChecked = [...document.querySelectorAll('#schedMgmtBody .sched-row-cb:checked')]
                .map(cb => cb.getAttribute('data-id'));
            const all = new Set([...domChecked, ..._schedCrossPageIds]);
            return [...all];
        }

        function _syncCurrentPageCheckboxes() {
            // When navigating pages, reflect cross-page selection state on the new page's checkboxes
            if (_schedAllPagesSelected) {
                document.querySelectorAll('#schedMgmtBody .sched-row-cb').forEach(cb => cb.checked = true);
            } else {
                document.querySelectorAll('#schedMgmtBody .sched-row-cb').forEach(cb => {
                    cb.checked = _schedCrossPageIds.has(cb.getAttribute('data-id'));
                });
            }
        }

        window.onSchedRowCbChange = function(cb) {
            // If a single row checkbox changed, record it in cross-page set
            if (cb) {
                const id = cb.getAttribute('data-id');
                if (cb.checked) { _schedCrossPageIds.add(id); }
                else { _schedCrossPageIds.delete(id); _schedAllPagesSelected = false; }
            }
            const ids = getSelectedSchedIds();
            const bar  = document.getElementById('schedBulkBar');
            const cnt  = document.getElementById('schedBulkCount');
            if (bar)  bar.style.display  = ids.length > 0 ? '' : 'none';
            if (cnt)  cnt.textContent    = `${ids.length} schedule${ids.length !== 1 ? 's' : ''} selected`;

            // Update header checkbox state
            const allVisible = [...document.querySelectorAll('#schedMgmtBody .sched-row-cb')]
                .filter(c => c.closest('tr')?.style.display !== 'none');
            const visibleChecked = allVisible.filter(c => c.checked);
            const selAll = document.getElementById('schedSelectAll');
            if (selAll) {
                selAll.checked       = allVisible.length > 0 && visibleChecked.length === allVisible.length;
                selAll.indeterminate = visibleChecked.length > 0 && visibleChecked.length < allVisible.length;
            }

            // Show/hide "select all pages" banner
            _updateSelectAllPagesBanner(ids.length);
        };

        function _updateSelectAllPagesBanner(selectedCount) {
            const allRows = document.querySelectorAll('#schedMgmtBody tr[data-campus-row]');
            const totalRows = allRows.length;
            const visibleRows = [...allRows].filter(r => r.style.display !== 'none');
            const banner = document.getElementById('schedSelectAllPagesBanner');
            if (!banner) return;

            if (_schedAllPagesSelected) {
                banner.innerHTML = `
                    <span class="text-emerald-700 font-semibold">All <strong>${totalRows}</strong> schedules (across all pages) are selected.</span>
                    <button onclick="window.clearSchedSelection()" class="ml-3 text-emerald-700 underline font-semibold hover:text-emerald-900 text-xs">Clear selection</button>`;
                banner.style.display = '';
            } else if (visibleRows.length > 0 && selectedCount === visibleRows.length && totalRows > visibleRows.length) {
                // All visible rows selected, but there are more pages
                banner.innerHTML = `
                    <span class="text-slate-600">All <strong>${visibleRows.length}</strong> schedules on this page are selected.</span>
                    <button onclick="window.selectAllSchedPages()" class="ml-3 text-emerald-700 underline font-semibold hover:text-emerald-900 text-xs">Select all ${totalRows} schedules across all pages</button>`;
                banner.style.display = '';
            } else {
                banner.style.display = 'none';
            }
        }

        window.selectAllSchedPages = function() {
            _schedAllPagesSelected = true;
            _schedCrossPageIds.clear();
            // Check all checkboxes on current page too
            document.querySelectorAll('#schedMgmtBody .sched-row-cb').forEach(cb => cb.checked = true);
            window.onSchedRowCbChange();
        };

        window.toggleAllSchedRows = function(checked) {
            if (!checked) {
                // Unchecking header → clear everything including cross-page
                _schedAllPagesSelected = false;
                _schedCrossPageIds.clear();
            }
            document.querySelectorAll('#schedMgmtBody .sched-row-cb').forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = checked;
                    if (checked) _schedCrossPageIds.add(cb.getAttribute('data-id'));
                }
            });
            window.onSchedRowCbChange();
        };

        window.clearSchedSelection = function() {
            _schedAllPagesSelected = false;
            _schedCrossPageIds.clear();
            document.querySelectorAll('#schedMgmtBody .sched-row-cb').forEach(cb => cb.checked = false);
            const selAll = document.getElementById('schedSelectAll');
            if (selAll) { selAll.checked = false; selAll.indeterminate = false; }
            window.onSchedRowCbChange();
        };

        window.selectDuplicateSchedules = function() {
            _schedAllPagesSelected = false;
            _schedCrossPageIds.clear();
            document.querySelectorAll('#schedMgmtBody .sched-row-cb').forEach(cb => {
                const row = cb.closest('tr');
                if (!row) return;
                const isDup = row.getAttribute('data-duplicate') === 'true';
                cb.checked = isDup;
                if (isDup) _schedCrossPageIds.add(cb.getAttribute('data-id'));
            });
            window.onSchedRowCbChange();
        };

        window.bulkApproveSchedules = async function() {
            const ids = getSelectedSchedIds();
            if (!ids.length) { showToast('No schedules selected.', 'error'); return; }
            const today = new Date(); today.setHours(0,0,0,0);
            const eligible = ids.filter(id => {
                const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id));
                if (!s || s.status !== 'Pending') return false;
                const rawDate = s.exam_date || s.date || '';
                if (rawDate) { const d = new Date(rawDate + 'T00:00:00'); if (d < today) return false; }
                return true;
            });
            if (!eligible.length) { showToast('No eligible pending (non-overdue) schedules in selection.', 'error'); return; }
            const skipped = ids.length - eligible.length;
            if (!confirm(`Approve ${eligible.length} schedule(s)?${skipped > 0 ? `\n(${skipped} skipped — not pending or overdue.)` : ''}`)) return;
            showToast(`Approving ${eligible.length} schedule(s)…`);
            let approved = 0, failed = 0, dupBlocked = 0;
            const approvedInBatch = new Set();
            const blockedDetails  = [];
            for (const id of eligible) {
                try {
                    const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id));

                    // ── Duplicate guard ───────────────────────────────────────
                    if (s) {
                        const _cid  = dbId(String(s.course_id || ''));
                        const _type = s.exam_type || '';
                        const _camp = s.campus || '';
                        const _date = s.exam_date || s.date || '';
                        const _slot = s.time_slot || '';
                        const _sec  = (s.section || s.section_name || s.class_section || '').trim().toLowerCase();
                        const _col  = (s.college || '').trim().toLowerCase();
                        const _prg  = (s.program || '').trim().toLowerCase();
                        const allNR = allData.filter(d =>
                            d.type === 'schedule' &&
                            (d.status === 'Approved' || approvedInBatch.has(String(d.id))) &&
                            String(d.id) !== String(id)
                        );
                        const dupOf = allNR.find(o => {
                            const oc   = dbId(String(o.course_id||'')), od = o.exam_date||o.date||'', os = o.time_slot||'',
                                  oSec = (o.section||o.section_name||o.class_section||'').trim().toLowerCase(),
                                  oCol = (o.college||'').trim().toLowerCase(),
                                  oPrg = (o.program||'').trim().toLowerCase();
                            // Different program = never a duplicate (e.g. BSIT vs BSCS)
                            if (_prg && oPrg && _prg !== oPrg) return false;
                            if (_camp && _cid && _type && _date && _slot && _sec && _col &&
                                (o.campus||'')===_camp && oc===_cid && (o.exam_type||'')===_type &&
                                od===_date && os===_slot && oSec===_sec && oCol===_col) return true;
                            if (_sec && _col && _cid && _type && _date &&
                                oSec===_sec && oCol===_col && oc===_cid &&
                                (o.exam_type||'')===_type && od===_date) return true;
                            if (_sec && _col && _cid && _date && _slot &&
                                oSec===_sec && oCol===_col && oc===_cid && od===_date && os===_slot) return true;
                            return false;
                        });
                        if (dupOf) {
                            dupBlocked++;
                            // Auto-reject the duplicate instead of just skipping it
                            try { await window.flexamApi.schedules.reject(id); } catch(e) {}
                            blockedDetails.push({
                                course:    s.course_code || s.course_name || '—',
                                section:   s.section || s.section_name || '—',
                                college:   s.college || '—',
                                type:      s.exam_type || '—',
                                date:      s.exam_date || s.date || '—',
                                dupId:     dupOf.id,
                                dupStatus: dupOf.status || '—',
                            });
                            continue;
                        }
                    }
                    // ─────────────────────────────────────────────────────────

                    const result = await window.flexamApi.schedules.approve(id);
                    if (result.success) { if (s) activityLog('Schedule Approved (Bulk)', `${s.course_code||s.course_name||''}`); approvedInBatch.add(String(id)); approved++; }
                    else { failed++; }
                } catch(e) { failed++; }
            }
            await refreshAllData(); window.clearSchedSelection();

            // ── Show result modal if any duplicates were blocked ──────────────
            if (dupBlocked > 0) {
                const existingDupModal = document.getElementById('bulkDupBlockedModal');
                if (existingDupModal) existingDupModal.remove();
                const dupModal = document.createElement('div');
                dupModal.className = 'modal-overlay active';
                dupModal.id = 'bulkDupBlockedModal';
                const rowsHtml = blockedDetails.map(b => `
                    <div class="flex items-start gap-3 px-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="mt-0.5 w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-800">${esc(b.course)}</p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                ${esc(b.type)} &nbsp;·&nbsp; Section <strong>${esc(b.section)}</strong> &nbsp;·&nbsp; ${esc(b.college)}
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5">${esc(b.date)} &nbsp;·&nbsp; Duplicate of ID ${esc(String(b.dupId))} <span class="font-semibold">(${esc(b.dupStatus)})</span></p>
                        </div>
                    </div>`).join('');
                dupModal.innerHTML = `
                <div class="modal-box" style="max-width:560px;">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Bulk Approval Summary</h3>
                            <p class="text-xs text-slate-500 mt-0.5">${approved} approved &nbsp;·&nbsp; <span class="text-amber-600 font-semibold">${dupBlocked} duplicate${dupBlocked !== 1 ? 's' : ''} auto-rejected</span>${failed > 0 ? ` &nbsp;·&nbsp; ${failed} failed` : ''}</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 bg-amber-50 border-b border-amber-100">
                        <p class="text-xs text-amber-700 font-medium">The following schedules were <strong>automatically rejected</strong> because an identical schedule already exists.</p>
                    </div>
                    <div class="overflow-y-auto" style="max-height:340px;">
                        ${rowsHtml}
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-3">
                        <button onclick="document.getElementById('bulkDupBlockedModal').remove()"
                            class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition">Close</button>
                    </div>
                </div>`;
                document.body.appendChild(dupModal);
                dupModal.addEventListener('click', e => { if (e.target === dupModal) dupModal.remove(); });
            } else {
                showToast(`✅ Approved ${approved} schedule(s)${failed > 0 ? ` · ${failed} failed` : ''}.`);
            }
        };

        window.bulkRejectSchedules = async function() {
            const ids = getSelectedSchedIds();
            if (!ids.length) { showToast('No schedules selected.', 'error'); return; }
            const eligible = ids.filter(id => { const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id)); return s && s.status === 'Pending'; });
            if (!eligible.length) { showToast('No pending schedules in selection to reject.', 'error'); return; }
            const skipped = ids.length - eligible.length;
            const reason = prompt(`Reject ${eligible.length} schedule(s)?${skipped > 0 ? `\n(${skipped} non-pending will be skipped.)` : ''}\n\nOptional: Enter rejection reason:`);
            if (reason === null) return;
            showToast(`Rejecting ${eligible.length} schedule(s)…`);
            let rejected = 0, failed = 0;
            for (const id of eligible) {
                try {
                    const result = await window.flexamApi.schedules.reject(id);
                    if (result.success) { const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id)); activityLog('Schedule Rejected (Bulk)', s ? `${s.course_code||s.course_name||''}${reason?' · Reason: '+reason:''}` : `ID ${id}`); rejected++; }
                    else { failed++; }
                } catch(e) { failed++; }
            }
            await refreshAllData(); window.clearSchedSelection();
            showToast(`Schedule${rejected !== 1 ? 's' : ''} rejected: ${rejected}${failed > 0 ? ` · ${failed} failed` : ''}.`);
        };

        window.bulkDeleteSchedules = async function() {
            const ids = getSelectedSchedIds();
            if (!ids.length) { showToast('No schedules selected.', 'error'); return; }
            if (!confirm(`Delete ${ids.length} schedule(s)? This cannot be undone.`)) return;
            showToast(`Deleting ${ids.length} schedule(s)…`);
            let deleted = 0, failed = 0;
            for (const id of ids) {
                try {
                    const result = await window.flexamApi.schedules.delete(id);
                    if (result.success) { const s = allData.find(d => d.type === 'schedule' && String(d.id) === String(id)); activityLog('Schedule Deleted (Bulk)', s ? `${s.course_code||s.course_name||''}` : `ID ${id}`); deleted++; }
                    else { failed++; }
                } catch(e) { failed++; }
            }
            await refreshAllData(); window.clearSchedSelection();
            showToast(`🗑️ Deleted ${deleted} schedule(s)${failed > 0 ? ` · ${failed} failed` : ''}.`);
        };

        // ─────────────────────────────────────────────────────────────────────────
        // AUTO-RESOLVE CONFLICTS
        // ─────────────────────────────────────────────────────────────────────────

        // ── Shared helpers (used by both detect + resolve) ───────────────────────
        function _arParseSlot(slot) {
            if (!slot) return null;
            const parts = slot.split(' - ');
            if (parts.length < 2) return null;
            function toMin(str) {
                const m = str.trim().match(/(\d+):(\d+)\s*(AM|PM)/i);
                if (!m) return null;
                let h = parseInt(m[1]), mn = parseInt(m[2]);
                if (m[3].toUpperCase() === 'PM' && h !== 12) h += 12;
                if (m[3].toUpperCase() === 'AM' && h === 12) h = 0;
                return h * 60 + mn;
            }
            return { start: toMin(parts[0]), end: toMin(parts[1]) };
        }
        function _arOverlap(a, b) {
            const pa = _arParseSlot(a), pb = _arParseSlot(b);
            if (!pa || !pb) return false;
            return pa.start < pb.end && pb.start < pa.end;
        }
        function _arSlotsForDur(durStr) {
            const dur = Math.round((parseFloat(durStr) || 1) * 60);
            const starts = [420,480,540,600,660,780,840,900,960,1020,1080,1140,1200];
            const LUNCH_START=720, LUNCH_END=780, DAY_END=1260;
            function toAMPM(m) {
                const h=Math.floor(m/60), min=m%60, ap=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
                return String(h12).padStart(2,'0')+':'+String(min).padStart(2,'0')+' '+ap;
            }
            return starts.filter(s => {
                const e = s + dur;
                return e <= DAY_END;
            }).map(s => toAMPM(s) + ' - ' + toAMPM(s + dur));
        }

        // ── Find a free room+slot for a schedule ─────
        // For existing schedules: tries same slot, different room only.
        // For import auto-fix (no slot): tries duration-aware slots across all free rooms.
        function _arFindFix(sched, liveSchedules) {
            const origDate = sched.exam_date || sched.date || '';
            const origSlot = sched.time_slot || '';
            const campus   = (sched.campus || '').toLowerCase().trim();

            // Case-insensitive campus match so "Quezon City" !== "quezon city" doesn't fail
            const rooms = allData.filter(d =>
                d.type === 'room' &&
                !d.locked &&
                (!campus || (d.campus || '').toLowerCase().trim() === campus)
            );

            // Generate slots matching the exam duration; fall back to 1-hour slots
            const durSlots = sched.duration ? _arSlotsForDur(sched.duration) : [];
            const ALL_SLOTS = [
                '07:00 AM - 08:00 AM','08:00 AM - 09:00 AM','09:00 AM - 10:00 AM','10:00 AM - 11:00 AM','11:00 AM - 12:00 PM',
                '12:00 PM - 01:00 PM','01:00 PM - 02:00 PM','02:00 PM - 03:00 PM','03:00 PM - 04:00 PM','04:00 PM - 05:00 PM',
                '05:00 PM - 06:00 PM','06:00 PM - 07:00 PM','07:00 PM - 08:00 PM','08:00 PM - 09:00 PM'
            ];

            // Slots to try:
            // - With a specific origSlot: try that slot first (different room), then duration slots
            // - Without slot (import auto-fix): try duration-aware slots, then all standard slots
            let slotsToTry;
            if (origSlot) {
                const extras = durSlots.length ? durSlots.filter(s => s !== origSlot) : ALL_SLOTS.filter(s => s !== origSlot);
                slotsToTry = [origSlot, ...extras];
            } else {
                slotsToTry = durSlots.length ? durSlots : ALL_SLOTS;
            }

            for (const slot of slotsToTry) {
                for (const room of rooms) {
                    const rid = dbId(String(room.id));
                    // Skip originally-assigned room only when trying the same slot
                    if (slot === origSlot && origSlot && rid === dbId(String(sched.room_id || sched.room || ''))) continue;
                    const clash = liveSchedules.find(other =>
                        dbId(String(other.id)) !== dbId(String(sched.id)) &&
                        (other.exam_date || other.date) === origDate &&
                        dbId(String(other.room_id || other.room || '')) === rid &&
                        _arOverlap(other.time_slot, slot)
                    );
                    if (!clash) return { room, rid: parseInt(rid, 10) || rid, slot, date: origDate, sameDay: slot === origSlot };
                }
            }
            return null;
        }

        window.autoResolveConflicts = function() {
            const schedules = allData.filter(d => d.type === 'schedule');

            // ── 1. Detect conflicts ──────────────────────────────────────────────
            // Build a stable room map so lookups are O(1) and order-independent
            const _roomLookup = {};
            allData.forEach(d => { if (d.type === 'room') _roomLookup[dbId(String(d.id))] = d; });

            const conflicts = [];
            schedules.forEach(s => {
                // Online exams don't need a room — skip all room-based conflict checks
                if (s.is_online) return;

                const issues = [];
                const roomId = dbId(String(s.room_id || s.room || ''));
                // No room assigned yet — not a conflict, just unscheduled
                if (!roomId) return;
                const room   = roomId ? _roomLookup[roomId] : undefined;

                if (!room || room.locked)
                    issues.push(room && room.locked ? 'Room is blocked' : 'Room no longer exists');

                if (room && !room.locked && s.exam_date && s.time_slot) {
                    const clash = schedules.find(other =>
                        dbId(String(other.id)) !== dbId(String(s.id)) &&
                        dbId(String(other.room_id || other.room || '')) === roomId &&
                        (other.exam_date || other.date) === (s.exam_date || s.date) &&
                        _arOverlap(other.time_slot, s.time_slot)
                    );
                    if (clash) issues.push(`Double-booked with "${clash.course_name || clash.course_code || 'another exam'}"`);
                }

                if (issues.length > 0) conflicts.push({ sched: s, issues });
            });

            // ── 2. No conflicts ──────────────────────────────────────────────────
            if (conflicts.length === 0) {
                // Check for pending fixable import rows that were skipped and never saved
                const pendingFix = (window._importResolvableRows || []).filter(r => r.canAutoResolve && r.importRow);
                const modal = document.createElement('div');
                modal.className = 'modal-overlay active';
                modal.id = 'autoResolveModal';
                if (pendingFix.length === 0) {
                    modal.innerHTML = `
                    <div class="modal-box p-8 text-center">
                        <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-2">No Conflicts Found</h3>
                        <p class="text-sm text-slate-500 mb-6">All schedules look good — no room conflicts or blocked rooms detected.</p>
                        <button onclick="document.getElementById('autoResolveModal').remove()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">Done</button>
                    </div>`;
                } else {
                    modal.innerHTML = `
                    <div class="modal-box p-0 overflow-hidden" style="max-width:560px;width:100%">
                        <div class="px-6 py-5 border-b border-slate-100 flex items-center gap-3 bg-amber-50">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">Saved schedules are conflict-free</h3>
                                <p class="text-xs text-slate-500 mt-0.5">${pendingFix.length} import conflict${pendingFix.length !== 1 ? 's' : ''} still need${pendingFix.length === 1 ? 's' : ''} to be placed</p>
                            </div>
                        </div>
                        <div class="px-6 py-4 overflow-y-auto" style="max-height:300px">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Unplaced import rows (skipped during import):</p>
                            <div class="space-y-2">
                                ${pendingFix.map((r, i) => `
                                <div class="flex items-start gap-3 p-3 rounded-xl bg-purple-50 border border-purple-100">
                                    <div class="w-5 h-5 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center shrink-0 mt-0.5 text-[10px] font-bold">${i + 1}</div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 truncate">${r.label || ('Row ' + (i + 1))}</p>
                                        <p class="text-xs text-purple-700 mt-0.5">${r.reason || ''}</p>
                                        <p class="text-[10px] text-purple-500 mt-1 font-medium">&#9889; A free room &amp; time slot will be assigned automatically</p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 bg-purple-100 text-purple-700">&#9889; Fixable</span>
                                </div>`).join('')}
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                            <p class="text-xs text-purple-600 font-semibold">&#9889; These were skipped at import — they are not yet in the system</p>
                            <div class="flex gap-2 shrink-0">
                                <button onclick="document.getElementById('autoResolveModal').remove()" class="px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-xl transition text-sm">Dismiss</button>
                                <button onclick="document.getElementById('autoResolveModal').remove(); autoResolveImportConflicts(window._importResolvableRows);"
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg>
                                    Auto-Fix ${pendingFix.length} Conflict${pendingFix.length !== 1 ? 's' : ''}
                                </button>
                            </div>
                        </div>
                    </div>`;
                }
                document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
                return;
            }

            // ── 3. Pre-compute suggestions using a live booking snapshot ─────────
            const liveSnap = allData.filter(d => d.type === 'schedule').map(s => Object.assign({}, s));
            const enriched = conflicts.map(({ sched, issues }) => {
                const fix = _arFindFix(sched, liveSnap);
                if (fix) {
                    // Mark this slot as taken in the snapshot so the next conflict sees it
                    const snapEntry = liveSnap.find(l => dbId(String(l.id)) === dbId(String(sched.id)));
                    if (snapEntry) { snapEntry.room_id = fix.rid; snapEntry.time_slot = fix.slot; }
                }
                return { sched, issues, fix };
            });

            // ── 4. Build rows with suggestions ───────────────────────────────────
            const rows = enriched.map(({ sched, issues, fix }, idx) => {
                const name     = sched.course_name || sched.course_code || '—';
                const date     = sched.exam_date || sched.date || '—';
                const curSlot  = sched.time_slot || '—';
                const curRoom  = (() => {
                    const rid = dbId(String(sched.room_id || sched.room || ''));
                    const r   = rid ? _roomLookup[rid] : undefined;
                    return r ? r.name : '—';
                })();

                const issueHtml = issues.map(i =>
                    `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        ${esc(i)}
                    </span>`
                ).join('');

                const suggestHtml = fix
                    ? `<div class="mt-2 flex items-start gap-1.5 p-2 rounded-lg bg-purple-50 border border-purple-100">
                            <svg class="w-3 h-3 text-purple-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4"/></svg>
                            <div class="text-[10px] text-purple-700 leading-relaxed">
                                <span class="font-bold">Suggested fix (same day &amp; time, different room):</span>
                                <span class="font-semibold">${fix.room.building ? esc(fix.room.building) + ', ' : ''}${esc(fix.room.name)}</span>
                                &nbsp;·&nbsp; <span class="font-semibold">${esc(fix.slot)}</span>
                            </div>
                       </div>`
                    : `<div class="mt-2 flex items-center gap-1.5 p-2 rounded-lg bg-amber-50 border border-amber-100">
                            <svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="text-[10px] text-amber-700 font-semibold">No free room on same day &amp; time — manual fix needed</span>
                       </div>`;

                return `<tr class="border-b border-slate-100 last:border-0 align-top">
                    <td class="py-3.5 px-4">
                        <p class="text-sm font-semibold text-slate-800 leading-tight">${esc(name)}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">${esc(sched.exam_type || '')} · ${esc(sched.section || '')}</p>
                    </td>
                    <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                        <p class="font-medium">${esc(date)}</p>
                        <p class="text-slate-400">${esc(curSlot)}</p>
                        <p class="text-slate-400 text-[10px] mt-0.5">Room: ${esc(curRoom)}</p>
                    </td>
                    <td class="py-3.5 px-4">
                        <div class="flex flex-col gap-1">
                            ${issueHtml}
                        </div>
                        ${suggestHtml}
                    </td>
                </tr>`;
            }).join('');

            const resolvableCount = enriched.filter(e => e.fix).length;
            const unresolvableCount = enriched.length - resolvableCount;

            // ── 5. Build modal ────────────────────────────────────────────────────
            const modal = document.createElement('div');
            modal.className = 'modal-overlay active';
            modal.id = 'autoResolveModal';
            modal.innerHTML = `
            <div class="modal-box" style="max-width:720px">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Auto-Resolve Conflicts</h3>
                            <p class="text-xs text-slate-500">
                                ${conflicts.length} conflict${conflicts.length > 1 ? 's' : ''} detected
                                &nbsp;·&nbsp;
                                <span class="text-purple-600 font-semibold">${resolvableCount} can be auto-fixed</span>
                                ${unresolvableCount > 0 ? `&nbsp;·&nbsp;<span class="text-amber-600 font-semibold">${unresolvableCount} need manual fix</span>` : ''}
                            </p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('autoResolveModal').remove()" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="overflow-y-auto" style="max-height:380px">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 sticky top-0 z-10">
                            <tr>
                                <th class="py-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Course</th>
                                <th class="py-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Schedule</th>
                                <th class="py-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Issue &amp; Suggestion</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
                    <p class="text-xs text-slate-400">Suggested fixes reassign to a different room on the <strong>same day &amp; same time</strong>. Manual-fix items are skipped.</p>
                    <div class="flex gap-2 shrink-0">
                        <button onclick="document.getElementById('autoResolveModal').remove()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 border border-slate-200 rounded-lg bg-white transition">Cancel</button>
                        ${resolvableCount > 0 ? `
                        <button id="confirmAutoResolveBtn" onclick="runAutoResolve()" class="px-5 py-2 text-sm font-semibold bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg>
                            Apply ${resolvableCount} Fix${resolvableCount > 1 ? 'es' : ''}
                        </button>` : ''}
                    </div>
                </div>
            </div>`;
            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);

            // ── Store enriched list for runAutoResolve ───────────────────────────
            window._pendingConflicts = enriched;
        };

        window.runAutoResolve = async function() {
            const enriched = (window._pendingConflicts || []).filter(e => e.fix);
            if (!enriched.length) return;

            const btn = document.getElementById('confirmAutoResolveBtn');
            if (btn) { btn.disabled = true; btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Applying…'; }

            let fixed = 0, failed = 0;

            for (const { sched, fix } of enriched) {
                try {
                    const result = await window.flexamApi.schedules.update({
                        id:            dbId(String(sched.id)),
                        course_id:     sched.course_id,
                        college:       sched.college,
                        exam_type:     sched.exam_type || sched.examType,
                        year_level:    sched.year_level,
                        section:       sched.section || '',
                        section_name:  sched.section || '',
                        class_section: sched.section || '',
                        exam_date:     fix.date || sched.exam_date || sched.date || '',
                        time_slot:     fix.slot,
                        duration:      sched.duration || '1 Hour',
                        room_id:       fix.rid,
                        room_name:     fix.room.building ? fix.room.building + ', ' + fix.room.name : fix.room.name,
                        proctor_id:    sched.proctor_id || null,
                        campus:        sched.campus || '',
                        status:        sched.status || 'Pending'
                    });
                    if (result && result.success) fixed++;
                    else failed++;
                } catch(e) { failed++; }
            }

            document.getElementById('autoResolveModal')?.remove();
            await refreshAllData();

            if (failed === 0) {
                showToast(`✓ Applied ${fixed} fix${fixed !== 1 ? 'es' : ''} successfully!`, 'success');
            } else {
                showToast(`Applied ${fixed}, ${failed} failed to save`, fixed > 0 ? 'success' : 'error');
            }
        };

        // ─────────────────────────────────────────────────────────────────────────
        // 7. UI HELPERS
        // ─────────────────────────────────────────────────────────────────────────
        function getCollegeOptions(selectedCode = '') {
            // Deduplicate by code — one college can have multiple DB rows (one per program group)
            const seen = new Set();
            const colleges = allData.filter(d => {
                if (d.type !== 'college' || !d.code) return false;
                if (seen.has(d.code)) return false;
                seen.add(d.code);
                return true;
            }).sort((a, b) => (a.code).localeCompare(b.code));
            return colleges.map(c => `<option value="${c.code}" ${selectedCode === c.code ? 'selected' : ''}>${c.code}${c.name ? ' — ' + c.name : ''}</option>`).join('');
        }
        function getRoomOptions(selectedId = '', filterCampus = '') {
    const availableRooms = allData.filter(d => {
        if (d.type !== 'room') return false;
        if (d.locked) return false;                                      // skip blocked rooms
        if (filterCampus && d.campus !== filterCampus) return false;    // campus filter
        return true;
    });

    if (availableRooms.length === 0) return '';

    return availableRooms.map(r => {
        const rid      = dbId(String(r.id));
        const selected = String(rid) === String(selectedId) ? 'selected' : '';
        return `<option value="${rid}" ${selected} data-campus="${r.campus || ''}">
            ${r.name} — ${r.building}${r.campus ? ' (' + r.campus + ')' : ''} (Cap: ${r.capacity})
        </option>`;
    }).join('');
}

        function saveSidebarScroll() {
            const sidebarNav = document.querySelector('aside nav');
            if (sidebarNav) sidebarScrollPosition = sidebarNav.scrollTop;
        }

        function restoreSidebarScroll() {
            const sidebarNav = document.querySelector('aside nav');
            if (sidebarNav && sidebarScrollPosition) sidebarNav.scrollTop = sidebarScrollPosition;
        }

        function dbId(id) {
            if (!id) return id;
            return String(id).replace(/^[a-z]+_/, '');
        }

        function findById(type, rawId) {
    const strId = String(rawId);
    return allData.find(d =>
        d.type === type &&
        (String(d.id) === strId || dbId(String(d.id)) === strId || String(d.id) === dbId(strId))
    );
}

    function resolveScheduleDisplay(sched) {
    // Course — display code only
    let courseName = sched.course_code || sched.course_name || sched.course || '';
    let semester = sched.semester || '';
    let resolvedProgram = sched.program || sched.college_program || '';
    if (sched.course_id) {
        const c = findById('course', sched.course_id);
        if (c) {
            if (!courseName) courseName = c.course_code || c.course_name || '';
            if (!semester)   semester   = c.semester || '';
            if (!resolvedProgram) resolvedProgram = c.program || '';
        }
    }
    if (!courseName && !sched.course_id) courseName = sched.course_name || '';

    // Room — always show "Building, Room Name" by reconstructing from room lookup
    let roomName = '';
    if (sched.is_online) {
        roomName = '🌐 Online';
    } else {
        const _roomLookup = findById('room', sched.room_id);
        if (_roomLookup) {
            roomName = _roomLookup.building ? `${_roomLookup.building}, ${_roomLookup.name}` : _roomLookup.name;
        } else if (sched.room_name) {
            roomName = sched.room_name;
        }
        if (!roomName) roomName = sched.room || '';
    }

                let proctorName = '';
// Prefer the enriched multi-proctor arrays returned by the API
if (Array.isArray(sched.proctor_names) && sched.proctor_names.length) {
    proctorName = sched.proctor_names.join(', ');
} else if (Array.isArray(sched.proctor_ids) && sched.proctor_ids.length) {
    // IDs present but names not — look them up from allData
    proctorName = sched.proctor_ids
        .map(pid => findById('proctor', pid)?.name || '')
        .filter(Boolean).join(', ');
} else if (sched.proctor_id) {
    // Legacy single-proctor fallback
    const p = findById('proctor', sched.proctor_id);
    proctorName = p ? p.name || '' : '';
}
if (!proctorName) proctorName = sched.proctor_name || sched.proctorName || sched.proctor || '';

    // Date — try all known field name variants (matches head.php import pattern)
    let examDate = '';
    const rawDateVal = sched.exam_date || sched.exam_dat || sched.date || sched.exam_date_display || '';
    if (rawDateVal && rawDateVal !== '0000-00-00' && rawDateVal !== '0000-00-00 00:00:00' && rawDateVal !== 'null') {
        try {
            // Strip time portion if present (e.g. "2026-03-10 00:00:00" → "2026-03-10")
            const datePart = String(rawDateVal).trim().substring(0, 10);
            const d = new Date(datePart + 'T00:00:00');
            if (!isNaN(d.getTime()) && d.getFullYear() > 1970) {
                examDate = d.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
            }
        } catch(e) {}
    }

    // Campus
    let campus = sched.campus || sched.campus_name || '';
    if (!campus && sched.room_id) {
        const r = findById('room', sched.room_id);
        if (r) campus = r.campus || '';
    }

    return { courseName, semester, roomName, proctorName, examDate, campus, resolvedProgram };

}

function resolveToCollegeCode(value) {
    if (!value || !value.trim()) return '';
    const v = value.trim().toLowerCase();

    // Direct match against college code (e.g. "CCS")
    const byCode = allData.find(d =>
        d.type === 'college' && (d.code || '').toLowerCase() === v
    );
    if (byCode) return byCode.code;

    // Match against program acronyms — handles "BSIT=BS in IT" and plain "BSIT"
    const byProgram = allData.find(d =>
        d.type === 'college' &&
        Array.isArray(d.programs) &&
        d.programs.some(p => {
            const acronym = (p.includes('=') ? p.split('=')[0] : p).trim().toLowerCase();
            return acronym === v;
        })
    );
    if (byProgram) return byProgram.code;

    return value.trim(); // Return as-is if no match
}

// ── Main function called by all college cells in all tables ────────────────
function resolveCollegeAcronym(sched) {
    // Helper: check if a value is a real college code (not empty/unknown)
    function isValid(v) {
        return v && v.trim() !== '' && v.trim().toUpperCase() !== 'UNKNOWN';
    }

    // 1. Use schedule.college if valid
    if (isValid(sched.college)) {
        return resolveToCollegeCode(sched.college.trim()) || sched.college.trim();
    }

    // 2. Find linked course by course_id
    const schedCourseId = sched.course_id ? dbId(String(sched.course_id)) : null;
    let course = null;

    if (schedCourseId) {
        course = allData.find(d =>
            d.type === 'course' &&
            (dbId(String(d.id)) === schedCourseId || String(d.id) === schedCourseId)
        );
    }

    // 3. Try matching by course_code if no match by ID
    if (!course) {
        const code = (sched.course_code || sched.course_name || '').trim().toLowerCase();
        if (code) {
            course = allData.find(d =>
                d.type === 'course' &&
                (d.course_code || '').toLowerCase() === code
            );
        }
    }

    // 4. From course record — try college then program
    if (course) {
        if (isValid(course.college)) {
            return resolveToCollegeCode(course.college.trim()) || course.college.trim();
        }
        if (isValid(course.program)) {
            return resolveToCollegeCode(course.program.trim()) || course.program.trim();
        }
    }

    // 5. From schedule record — try program directly
    if (isValid(sched.program)) {
        return resolveToCollegeCode(sched.program.trim()) || sched.program.trim();
    }

    return '—';
}

                // Campus filter helper — reads the current DOM select value and saves to state
                function onCampusFilterChange(view, selectId) {
                    const el = document.getElementById(selectId);
                    if (el) campusFilters[view] = el.value;
                    applyViewFilters(view);
                }
                window.toggleCampusDropdown = function(e, id) {
            e.stopPropagation();
            // Close any other open dropdowns
            document.querySelectorAll('[id^="cdrop-"]').forEach(el => {
                if (el.id !== id) el.classList.add('hidden');
            });
            const dd = document.getElementById(id);
            if (dd) dd.classList.toggle('hidden');
            // Close on outside click
            setTimeout(() => {
                document.addEventListener('click', function closeDrop(ev) {
                    const dd = document.getElementById(id);
                    if (dd && !dd.contains(ev.target)) {
                        dd.classList.add('hidden');
                        document.removeEventListener('click', closeDrop);
                    }
                });
            }, 0);
        };
        

        // Apply campus filter to visible table rows (data-campus attribute approach)
        function applyViewFilters(view) {
            const campus = campusFilters[view] || '';
            // Re-render is lightweight — just hide/show rows via data-campus
            const rows = document.querySelectorAll('[data-campus-row]');
            rows.forEach(row => {
                const rowCampus = row.getAttribute('data-campus-row') || '';
                row.style.display = (!campus || rowCampus === campus) ? '' : 'none';
            });
            updateRowCountBadge(view);
        }

        function updateRowCountBadge(view) {
            const badge = document.getElementById('rowCountBadge');
            if (!badge) return;
            const visible = document.querySelectorAll('[data-campus-row]:not([style*="none"])').length;
            const total   = document.querySelectorAll('[data-campus-row]').length;
            badge.textContent = visible === total ? `${total} records` : `${visible} of ${total} records`;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 8. SIDEBAR
        // ─────────────────────────────────────────────────────────────────────────
        function renderSidebar() {
            const menu = [
                { id: 'dashboard', label: 'Dashboard', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
                { id: 'schedule-mgmt', label: 'Schedule', labelSecond: 'Management', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', multiline: true },
                { id: 'view-schedule', label: 'View Schedule', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z M15 15l3 3m-3-3a3 3 0 10-6 0 3 3 0 006 0z' },
                { id: 'analytics', label: 'Analytics', icon: 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z' },
                { id: 'feedbacks', label: 'Feedbacks', icon: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z' },
                { id: 'users', label: 'Users', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'},
                { id: 'logs', label: 'Activity Logs', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
                { id: 'calendar', label: 'Calendar', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
                { id: 'courses', label: 'Courses', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
                { id: 'rooms', label: 'Rooms', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
                { id: 'colleges', label: 'Colleges', icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z' },
                { id: 'proctors', label: 'Proctors', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
                { id: 'semester-archive', label: 'Semester Archive', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' }
            ];
            
            return `
            <aside id="mainSidebar" class="fixed lg:relative inset-y-0 left-0 w-64 bg-white border-r border-slate-200 flex flex-col z-50 shrink-0 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out">
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="shrink-0">
                            <img src="../Image/OLFU.png" alt="OLFU Logo" class="w-10 h-10 object-contain">
                        </div>
                        <div class="text-left">
                            <h1 class="font-bold text-slate-800 text-lg leading-tight">FLEXAM</h1>
                            <p class="text-[11px] text-slate-500 font-medium">Exam Scheduler</p>
                        </div>
                    </div>
                </div>
                <nav class="flex-1 px-4 space-y-1 overflow-y-auto mt-2 text-left min-h-0">
                    ${menu.map(m => `
                        <button data-view="${m.id}" onclick="if(window.innerWidth < 1024) closeSidebar()" class="sidebar-link w-full ${currentView === m.id ? 'active' : ''}">
                            <svg class="w-5 h-5 shrink-0 ${currentView === m.id ? 'text-emerald-600' : 'text-slate-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${m.icon}"/>
                            </svg>
                            ${m.multiline ? `
                                <span class="flex flex-col text-left">
                                    <span>${m.label}</span>
                                    <span>${m.labelSecond}</span>
                                </span>
                            ` : `<span>${m.label}</span>`}
                        </button>
                    `).join('')}
                </nav>
                <div class="p-4 mt-auto">
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 ${currentUser.is_super_admin ? 'bg-amber-500' : 'bg-emerald-600'} rounded-full flex items-center justify-center text-white text-sm font-bold shadow-sm shrink-0">
                                ${currentUser.full_name ? currentUser.full_name.charAt(0) : 'S'}
                            </div>
                            <div class="flex-1 min-w-0 text-left">
                                <p class="text-sm font-semibold text-slate-700 truncate">${currentUser.full_name || 'Admin'}</p>
                                <span class="inline-block px-2 py-0.5 text-[10px] uppercase font-bold tracking-wide rounded-full ${currentUser.is_super_admin ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}">
                                    ${currentUser.is_super_admin ? 'Super Admin' : currentUser.campus ? 'Campus Admin' : 'Admin'}
                                </span>
                                ${currentUser.campus ? `<span class="inline-block ml-1 px-2 py-0.5 text-[10px] font-bold tracking-wide rounded-full bg-blue-100 text-blue-700 truncate max-w-full">${currentUser.campus}</span>` : ''}
                            </div>
                        </div>
                        <button class="w-full text-left text-xs font-medium text-red-500 hover:text-red-600 transition flex items-center gap-2 pl-1" onclick="handleSignOut()">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            Sign Out
                        </button>
                    </div>
                </div>
            </aside>`;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 9. HEADER
        // ─────────────────────────────────────────────────────────────────────────
        function renderHeader() {
            const titles = { 
                'dashboard': 'Dashboard', 
                'schedule-mgmt': 'Schedule Management',
                'view-schedule': 'View Schedule',
                'analytics': 'Analytics Dashboard', 
                'feedbacks': 'Student Feedbacks',
                'users': 'User Management',
                'logs': 'Activity Logs',
                'calendar': 'Exam Calendar',
                'courses': 'Courses',
                'rooms': 'Room Management',
                'colleges': 'College Management',
                'proctors': 'Proctor Management',
                'announcements': 'Announcements'
            };
            const todayStr = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            return `
            <header class="top-header flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="text-left">
                        <h2 class="text-lg md:text-xl font-bold text-slate-900 capitalize">${titles[currentView] || 'Portal'}</h2>
                        <p class="text-xs md:text-sm text-slate-400 mt-0.5 hidden sm:block">
                            Our Lady of Fatima University
                            ${currentUser.campus ? `<span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                ${esc(currentUser.campus)} Campus
                            </span>` : ''}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-0">
                    <span class="text-sm font-medium text-slate-500 hidden md:block">${todayStr}</span>
                    <span id="_realtimeDot" title="Live sync active" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#047857;opacity:0;transition:opacity 0.4s;"><span style="width:8px;height:8px;background:#22c55e;border-radius:50%;display:inline-block;animation:_pulse 1s ease-in-out infinite;"></span>Live</span>
                    <style>@keyframes _pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
                    <div class="relative" id="notifWrapper">
                            <button id="notifBtn" onclick="toggleAdminNotif()" class="p-2 text-slate-500 hover:bg-slate-100 rounded-full transition relative">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                ${(() => { const n = allData.filter(d => d.type==='schedule' && (d.status==='Pending'||!d.status) && !adminReadNotifs.has(String(d.id))).length; return n > 0 ? `<span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none shadow">${n > 99 ? '99+' : n}</span>` : ''; })()}
                            </button>
                            <div id="adminNotifDropdown" class="hidden absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden">
                                ${(() => {
                                   const pending = allData.filter(d => d.type==='schedule' && (d.status==='Pending'||!d.status) && !adminReadNotifs.has(String(d.id)));
                                    const n = pending.length;
                                   const listHtml = n === 0
                                    ? `<div class="px-4 py-10 text-center">
                                           <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                               <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                           </div>
                                           <p class="text-sm font-semibold text-slate-500">All caught up!</p>
                                           <p class="text-xs text-slate-400 mt-1">No pending schedules</p>
                                       </div>`
                                    : `<div class="max-h-72 overflow-y-auto divide-y divide-slate-100">
                                           ${pending.map(s => {
                                               const rawDate = s.exam_date || s.date || '';
                                               let dateDisplay = 'No date';
                                               if (rawDate) {
                                                   const d = new Date(rawDate + 'T00:00:00');
                                                   dateDisplay = d.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
                                               }
                                               return `<div class="px-4 py-3 hover:bg-slate-50 transition">
                                                   <div class="flex items-start gap-3">
                                                       <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center shrink-0 mt-0.5">
                                                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                       </div>
                                                       <div class="flex-1 min-w-0">
                                                           <p class="text-sm font-semibold text-slate-800 truncate">${s.course_name || s.course_code || '—'}</p>
                                                           <p class="text-xs text-slate-400 mt-0.5">${s.exam_type || 'Exam'} · ${dateDisplay}${s.campus ? ' · ' + s.campus : ''}</p>
                                                       </div>
                                                       <div class="flex gap-1 shrink-0">
                                                           ${(() => { const _od = rawDate && (() => { const _d = new Date(rawDate + 'T00:00:00'); const _t = new Date(); _t.setHours(0,0,0,0); return _d < _t; })(); return _od ? `<button disabled title="Overdue — cannot approve" class="px-2 py-1 text-[11px] font-bold bg-slate-100 text-slate-400 rounded-lg cursor-not-allowed opacity-60" style="pointer-events:none;">✓</button>` : `<button onclick="approveSchedule('${s.id}');toggleAdminNotif()" class="px-2 py-1 text-[11px] font-bold bg-emerald-100 text-emerald-700 hover:bg-emerald-200 rounded-lg transition">✓</button>`; })()}
                                                           <button onclick="rejectSchedule('${s.id}');toggleAdminNotif()" class="px-2 py-1 text-[11px] font-bold bg-red-100 text-red-600 hover:bg-red-200 rounded-lg transition">✕</button>
                                                       </div>
                                                   </div>
                                               </div>`;
                                           }).join('')}
                                       </div>`;
                                return `<div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                                            <div><h3 class="text-sm font-bold text-slate-800">Pending Approvals</h3>
                                            <p class="text-xs text-slate-400">${n} schedule${n!==1?'s':''} awaiting review</p></div>
                                            <div class="flex items-center gap-2">${n>0?`<button onclick="markAdminNotifsRead()" class="text-xs font-semibold text-slate-400 hover:text-slate-600">Mark read</button><button onclick="currentView='schedule-mgmt';renderApp();toggleAdminNotif()" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">View all →</button>`:''}</div>
                                        </div>${listHtml}`;
                                })()}
                            </div>
                        </div>
                </div>
            </header>`;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 10. MAIN CONTENT ROUTER
        // ─────────────────────────────────────────────────────────────────────────
        function renderMainContent() {
            // ── Date range helper (used by analytics) ──────────────────────
            const _now   = new Date();
            const _today = new Date(_now.getFullYear(), _now.getMonth(), _now.getDate());
            function _inDateRange(s) {
                if (analyticsDateFilter === 'all') return true;
                const raw = (s.exam_date || s.date || '').trim();
                if (!raw || raw === '0000-00-00') return false;
                const d = new Date(raw.substring(0, 10) + 'T00:00:00');
                if (isNaN(d.getTime())) return false;
                if (analyticsDateFilter === 'day')   return d >= _today && d < new Date(_today.getTime() + 86400000);
                if (analyticsDateFilter === 'week') {
                    const ws = new Date(_today); ws.setDate(_today.getDate() - _today.getDay());
                    return d >= ws && d < new Date(ws.getTime() + 7 * 86400000);
                }
                if (analyticsDateFilter === 'month') return d.getFullYear() === _now.getFullYear() && d.getMonth() === _now.getMonth();
                return true;
            }

            const schedules = allData.filter(d => d.type === 'schedule');
            // For analytics view, apply date filter to stat totals shown on cards
            const analyticsSchedules = currentView === 'analytics'
                ? schedules.filter(s => _inDateRange(s))
                : schedules;
            const totalSchedules    = analyticsSchedules.length;
            const pendingSchedules  = analyticsSchedules.filter(s => s.status === 'Pending' || !s.status).length;
            const approvedSchedules = analyticsSchedules.filter(s => s.status === 'Approved').length;
            const scheduledPercent  = totalSchedules > 0 ? Math.round((approvedSchedules / totalSchedules) * 100) : 0;

           // ── DASHBOARD ──────────────────────────────────────────────────────
            if (currentView === 'dashboard') {
                const recentScheds = allData.filter(d => d.type === 'schedule').slice(-5).reverse();
                return `
                <div class="fade-in space-y-6 max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        ${renderDashboardStat('Total Courses', allData.filter(d => d.type === 'course').length, 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'emerald')}
                        ${renderDashboardStat('Total Rooms', allData.filter(d => d.type === 'room').length, 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'cyan')}
                        ${renderDashboardStat('Total Colleges', allData.filter(d => d.type === 'college').length, 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z', 'emerald')}
                        ${renderDashboardStat('Total Proctors', allData.filter(d => d.type === 'proctor').length, 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'lime')}
                        ${(() => {
                    const blocked = allData.filter(d => d.type === 'room' && d.locked);
                    return `<div class="bg-white p-6 rounded-xl border ${blocked.length > 0 ? 'border-red-200' : 'border-slate-200'} shadow-sm flex justify-between items-center text-left cursor-pointer hover:shadow-md transition" onclick="currentView='rooms';renderApp()">
                        <div><p class="text-[10px] font-bold ${blocked.length > 0 ? 'text-red-400' : 'text-slate-400'} uppercase tracking-widest mb-1">Blocked Rooms</p><h3 class="text-3xl font-bold ${blocked.length > 0 ? 'text-red-600' : 'text-slate-800'}">${blocked.length}</h3></div>
                        <div class="w-12 h-12 ${blocked.length > 0 ? 'bg-red-50 text-red-500' : 'bg-slate-100 text-slate-400'} rounded-lg flex items-center justify-center"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                </div>`;
            })()}
        </div>
                   <!-- ── Campus Announcements Panel ── -->
                   <div id="flexAnnCAPanel" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                       <div class="flex items-center justify-between px-5 py-3 bg-slate-50 border-b border-slate-200 flex-wrap gap-2">
                           <div class="flex items-center gap-2.5">
                               <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                   <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.952 9.168-4.951"/>
                                   </svg>
                               </div>
                               <div class="flex items-center gap-2 flex-wrap">
                                   <span class="text-sm font-bold text-slate-800">Announcements</span>
                                   <span id="flexAnnCAMyBadge" class="text-[10px] font-bold px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full"></span>
                                   <span class="text-[11px] font-semibold px-2 py-1 bg-white border border-slate-200 text-slate-600 rounded-lg">${currentUser.campus || 'My Campus'}</span>
                               </div>
                           </div>
                           <div class="flex items-center gap-1.5">
                               <button onclick="var b=document.getElementById('flexAnnCABody');var i=document.getElementById('flexAnnCAColIcon');var h=b.style.display==='none';b.style.display=h?'':'none';i.style.transform=h?'':'rotate(180deg)';"
                                       title="Collapse / Expand"
                                       class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
                                   <svg id="flexAnnCAColIcon" class="w-3.5 h-3.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                   </svg>
                               </button>
                               <button onclick="openNewCampusAnnModal()"
                                       class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold transition shadow-sm">
                                   <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                       <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                                   </svg>
                                   New Announcement
                               </button>
                           </div>
                       </div>
                       <div id="flexAnnCABody" class="px-5 py-4">
                           <div id="flexAnnCAUnifiedGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                               <p class="text-sm text-slate-400 py-4 text-center col-span-full">Loading announcements…</p>
                           </div>
                       </div>
                   </div>
                   <!-- ── End Campus Announcements Panel ── -->
                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                        <div class="lg:col-span-3 space-y-6">
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 min-h-[400px] flex flex-col">
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-lg font-bold text-slate-800">Recent Schedules</h3>
                                    <button onclick="currentView='schedule-mgmt';renderApp()" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition">View all →</button>
                                </div>
                                ${recentScheds.length === 0 ? `
                                <div class="flex-1 flex items-center justify-center text-center">
                                    <div>
                                        <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <p class="text-slate-400 text-sm font-medium">No schedules yet</p>
                                        <button onclick="currentView='schedule-mgmt';renderApp()" class="mt-3 text-xs text-emerald-600 font-semibold hover:underline">Add your first schedule →</button>
                                    </div>
                                </div>` : `
                                <div class="flex-1 space-y-2 overflow-y-auto">
                                    ${recentScheds.map(s => {
                                        const status = s.status || 'Pending';
                                        const rawDate = s.exam_date || s.date || '';
                                        let dateDisplay = '—';
                                        if (rawDate) {
                                            const d = new Date(rawDate + 'T00:00:00');
                                            dateDisplay = d.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
                                        }
                                        const statusColor = status === 'Approved'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : status === 'Rejected'
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-orange-100 text-orange-700';
                                        return `
                                        <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100">
                                            <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 truncate">${s.course_name || s.course_code || '—'}</p>
                                                <p class="text-xs text-slate-400 mt-0.5">${s.exam_type || '—'} · ${dateDisplay}${s.campus ? ' · ' + s.campus : ''}</p>
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold shrink-0 ${statusColor}">${status}</span>
                                        </div>`;
                                    }).join('')}
                                </div>`}
                            </div>
                            ${(() => {
                                const blockedRooms = allData.filter(d => d.type === 'room' && d.locked);
                                if (blockedRooms.length === 0) return '';
                                return `
                                <div class="bg-white rounded-xl border border-red-200 shadow-sm p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 bg-red-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </div>
                                            <h3 class="text-sm font-bold text-slate-800">Blocked Rooms <span class="ml-1 px-2 py-0.5 bg-red-100 text-red-600 text-xs font-bold rounded-full">${blockedRooms.length}</span></h3>
                                        </div>
                                        <button onclick="currentView='rooms';renderApp()" class="text-xs font-semibold text-red-500 hover:text-red-700 transition">Manage →</button>
                                    </div>
                                    <div class="space-y-2">
                                        ${blockedRooms.map(r => {
                                            const fromDate = r.blocked_from ? new Date(r.blocked_from + 'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric'}) : '';
                                            const toDate   = r.blocked_to   ? new Date(r.blocked_to   + 'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric'}) : '';
                                            const dateRange = fromDate && toDate ? `${fromDate} – ${toDate}` : fromDate ? `From ${fromDate}` : toDate ? `Until ${toDate}` : 'Indefinite';
                                            return `
                                            <div class="flex items-center gap-3 p-3 rounded-lg bg-red-50 border border-red-100">
                                                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-semibold text-slate-800 truncate">${r.name}${r.campus ? ' · ' + r.campus : ''}</p>
                                                    <p class="text-xs text-slate-400 truncate">${r.block_reason || 'No reason given'} · ${dateRange}</p>
                                                </div>
                                                <button onclick="window.unblockRoom('${dbId(String(r.id))}')" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 whitespace-nowrap transition">Unblock</button>
                                            </div>`;
                                        }).join('')}
                                    </div>
                                </div>`;
                            })()}
                        </div>
                        <div class="lg:col-span-2 space-y-4">
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                                <h3 class="font-bold text-slate-800 text-lg mb-5 text-left">Quick Actions</h3>
                                <div class="grid grid-cols-3 gap-0">
                                    ${renderIconAction('Add Course', 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'emerald', 'openAddCourseModal()')}
                                    ${renderIconAction('Add Schedule', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'blue', 'openAddScheduleModal()')}
                                    ${renderIconAction('Add Rooms', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'cyan', 'window.openAddRoomModal()')}
                                    ${renderIconAction('Add Proctors', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'purple', 'openAddProctorModal()')}
                                    ${renderIconAction('Analytics', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'amber', "currentView='analytics';renderApp()")}
                                    ${renderIconAction('Export', 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'green', 'openDashboardExportModal()')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            }

            // ── ANNOUNCEMENTS ──────────────────────────────────────────────────
            if (currentView === 'announcements') {
                return `
                <div class="fade-in space-y-6 max-w-4xl mx-auto">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.952 9.168-4.951"/></svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-bold text-slate-800">Manage Announcements</p>
                                    <p class="text-xs text-slate-400">Scoped to <span class="font-semibold text-emerald-600">${esc(currentUser.campus || 'your campus')}</span> — visible to students and heads at this campus</p>
                                </div>
                            </div>
                            <button onclick="openCampusAnnEditor()" class="flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Manage Announcements
                            </button>
                        </div>
                        <div class="px-5 py-4">
                            <div id="campusAnnListFull" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"></div>
                        </div>
                    </div>
                </div>`;
            }


            // ── SCHEDULE MANAGEMENT ────────────────────────────────────────────
            if (currentView === 'schedule-mgmt') {
                if (!window._schedTab) window._schedTab = 'schedules';
                const activeTab = window._schedTab;
                return `
                <div class="fade-in space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-left">
                        <div class="stat-card-small">
                            <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Total Schedules</p><h3 class="text-2xl font-bold text-slate-800">${totalSchedules}</h3></div>
                            <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg></div>
                        </div>
                        <div class="stat-card-small">
                            <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Pending Approval</p><h3 class="text-2xl font-bold text-orange-500">${pendingSchedules}</h3></div>
                            <div class="w-9 h-9 bg-orange-50 text-orange-500 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/></svg></div>
                        </div>
                        <div class="stat-card-small">
                            <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Approved</p><h3 class="text-2xl font-bold text-emerald-500">${approvedSchedules}</h3></div>
                            <div class="w-9 h-9 bg-emerald-50 text-emerald-500 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2"/></svg></div>
                        </div>
                        ${(() => {
                            const { autoResolvedPct, autoResolvedLabel, conflictedCount } = computeAnalyticsExtras(schedules, '');
                            const arColor = conflictedCount === 0 ? 'text-emerald-600' : autoResolvedPct >= 80 ? 'text-purple-600' : 'text-red-500';
                            const iconBg  = conflictedCount === 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-purple-50 text-purple-600';
                            return `<div id="autoResolvedCard" class="stat-card-small cursor-pointer hover:border-purple-300 hover:shadow-md transition group" onclick="autoResolveConflicts()" title="Click to check / auto-resolve scheduling conflicts">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Auto-Resolved</p>
                                    <span class="relative flex h-1.5 w-1.5" title="Live — updates every 5 seconds">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full ${conflictedCount === 0 ? 'bg-emerald-400' : 'bg-purple-400'} opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 ${conflictedCount === 0 ? 'bg-emerald-500' : 'bg-purple-500'}"></span>
                                    </span>
                                </div>
                                <h3 id="autoResolvedPct" class="text-2xl font-bold ${arColor}">${autoResolvedPct}%</h3>
                                <p id="autoResolvedLabel" class="text-[10px] ${conflictedCount === 0 ? 'text-emerald-400' : 'text-purple-400'} mt-0.5 group-hover:text-purple-600 transition">${conflictedCount === 0 ? 'No conflicts detected' : autoResolvedLabel}</p>
                            </div>
                            <div class="w-9 h-9 ${iconBg} rounded-md flex items-center justify-center shrink-0 ml-4 group-hover:bg-purple-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg></div>
                        </div>`;
                        })()}
                        <div class="stat-card-small cursor-pointer hover:border-purple-300 hover:shadow-md transition group" onclick="document.getElementById('spExamSection').scrollIntoView({behavior:'smooth'})" title="View Special Exam Registrations" style="border-color:#e9d5ff;">
                            <div><p class="text-[10px] font-bold text-purple-400 uppercase mb-0.5">Special Exams</p><h3 class="text-2xl font-bold text-purple-700">${(window._specialExams||[]).length}</h3><p class="text-[10px] text-purple-400 mt-0.5 group-hover:text-purple-600 transition">Click to view registrations</p></div>
                            <div class="w-9 h-9 bg-purple-100 text-purple-600 rounded-md flex items-center justify-center shrink-0 ml-4 group-hover:bg-purple-200 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        </div>
                    </div>

                    <!-- TAB STRIP -->
                    <div class="flex border-b border-slate-200 bg-white rounded-t-xl overflow-hidden shadow-sm">
                        <button onclick="window._schedTab='schedules';renderApp()" class="flex items-center gap-2 px-6 py-3.5 text-sm font-semibold border-b-2 transition whitespace-nowrap ${activeTab==='schedules' ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg>
                            Schedule Management
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold ${activeTab==='schedules' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">${schedules.length}</span>
                        </button>
                        <button onclick="window._schedTab='special';renderApp()" class="flex items-center gap-2 px-6 py-3.5 text-sm font-semibold border-b-2 transition whitespace-nowrap ${activeTab==='special' ? 'border-purple-500 text-purple-700 bg-purple-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Special Exam Registrations
                            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold ${activeTab==='special' ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-500'}">${(window._specialExams||[]).length}</span>
                        </button>
                    </div>

                    <!-- TAB CONTENT: SCHEDULES -->
                    <div style="display:${activeTab==='schedules'?'block':'none'}">
                    <div class="bg-white rounded-b-xl border border-slate-200 shadow-sm overflow-hidden -mt-px">
                        <div class="p-4 flex flex-col md:flex-row gap-3 items-center border-b border-slate-100">
                            <div class="relative flex-1 w-full">
                                <input id="schedMgmtSearch" type="text" placeholder="Search schedules..." oninput="schedMgmtFilter()" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-slate-300">
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                               <select id="schedMgmtCollege" onchange="schedMgmtFilter()" class="filter-select">
                                <option value="">All Colleges</option>
                                ${allData.filter(d => d.type === 'college' && d.code)
                                    .reduce((acc, c) => {
                                        if (!acc.find(x => x.code === c.code)) acc.push({ code: c.code, name: c.name || '' });
                                        return acc;
                                    }, [])
                                    .sort((a, b) => a.code.localeCompare(b.code))
                                    .map(c => `<option value="${c.code}" title="${c.code}${c.name ? ' — ' + c.name : ''}">${c.code}</option>`).join('')}
                            </select>
                                <select id="schedMgmtType" onchange="schedMgmtFilter()" class="filter-select"><option value="">All Exam Types</option><option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option></select>
                                <button onclick="schedMgmtClearFilters()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800 whitespace-nowrap">Clear</button>
                                <button onclick="openAddScheduleModal()" class="flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 shadow-sm transition whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5"/></svg>
                                    Add Schedule
                                </button>
                            </div>
                        </div>
                       <!-- Campus Tab Strip -->
                        ${renderCampusTabs('schedule-mgmt', 'schedMgmtFilter()', schedules)}
                        <!-- Record count + data buttons -->
                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                        ${renderDataButtons('schedule-mgmt', schedules.length)}
                        </div>

                        <!-- ── Bulk Action Toolbar (hidden until rows are selected) ── -->
                        <div id="schedBulkBar" style="display:none;" class="px-4 py-2.5 bg-emerald-50 border-b border-emerald-200 flex flex-wrap items-center gap-3">
                            <span id="schedBulkCount" class="text-xs font-bold text-emerald-700"></span>
                            <div class="flex items-center gap-2 ml-auto flex-wrap">
                                <button onclick="bulkApproveSchedules()"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Approve Selected
                                </button>
                                <button onclick="bulkRejectSchedules()"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-lg transition shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Reject Selected
                                </button>
                                <button onclick="bulkDeleteSchedules()"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Delete Selected
                                </button>
                                <button onclick="clearSchedSelection()"
                                    class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 border border-slate-300 bg-white rounded-lg transition">
                                    Clear Selection
                                </button>
                            </div>
                        </div>
                        <!-- ── Duplicate selector bar (always visible if duplicates exist) ── -->
                        ${(() => {
                            const dupCount = schedules.filter(s => {
                                if ((s.status || '') === 'Rejected') return false;
                                const sid      = dbId(String(s.id));
                                const thisCampus  = s.campus || '';
                                const thisCourseId= dbId(String(s.course_id || ''));
                                const thisType    = s.exam_type || '';
                                const thisDate    = s.exam_date || s.date || '';
                                const thisSlot    = s.time_slot || '';
                                const thisSec     = (s.section || s.section_name || s.class_section || '').trim().toLowerCase();
                                const thisCol     = (s.college || '').trim().toLowerCase();
                                const thisProg    = (s.program || '').trim().toLowerCase();
                                return schedules.some(o => {
                                    if (dbId(String(o.id)) === sid) return false;
                                    if ((o.status || '') === 'Rejected') return false;
                                    const oCourseId = dbId(String(o.course_id || ''));
                                    const oDate = o.exam_date || o.date || '';
                                    const oSlot = o.time_slot || '';
                                    const oSec  = (o.section || o.section_name || o.class_section || '').trim().toLowerCase();
                                    const oCol  = (o.college || '').trim().toLowerCase();
                                    const oProg = (o.program || '').trim().toLowerCase();
                                    // True duplicate: same college + program + course + type + date + slot + section
                                    if (thisSec && thisCol && thisProg && thisCourseId && thisType && thisDate && thisSlot &&
                                        oSec === thisSec && oCol === thisCol && oProg === thisProg && oCourseId === thisCourseId &&
                                        (o.exam_type||'') === thisType && oDate === thisDate && oSlot === thisSlot) return true;
                                    return false;
                                });
                            }).length;
                            return dupCount > 0
                                ? `<div class="px-4 py-2 bg-amber-50 border-b border-amber-200 flex items-center gap-3">
                                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                    <span class="text-xs font-semibold text-amber-700">${dupCount} duplicate schedule${dupCount !== 1 ? 's' : ''} detected</span>
                                    <button onclick="selectDuplicateSchedules()"
                                        class="ml-auto flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        Select Duplicates
                                    </button>
                                </div>`
                                : '';
                        })()}

                        <div id="schedSelectAllPagesBanner" style="display:none;"
                             class="flex items-center px-5 py-2.5 bg-emerald-50 border-b border-emerald-200 text-sm">
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="pl-4 pr-2 py-3 text-center w-10">
                                            <input type="checkbox" id="schedSelectAll" title="Select all schedules on this page"
                                                onchange="toggleAllSchedRows(this.checked)"
                                                class="w-4 h-4 rounded border-slate-300 text-emerald-600 accent-emerald-600 cursor-pointer">
                                        </th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Course</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">College</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Program</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Semester</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Exam Type</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Year Level</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Section</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Duration</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Room</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Proctor</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Campus</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                        <th class="px-6 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="schedMgmtBody" class="divide-y divide-slate-100">
                                    ${schedules.length === 0 ? `<tr><td colspan="15" class="py-24 text-center"><p class="text-slate-400 text-sm">No schedules found</p></td></tr>` : schedules.map(sched => {
                                        const status = sched.status || 'Pending';
                                        const campus = sched.campus || '';
                                        const statusBadge = status === 'Approved'
                                            ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">✓ Approved</span>`
                                            : status === 'Rejected'
                                            ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">✕ Rejected</span>`
                                            : `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">⏳ Pending</span>`;
                                        const safeId = dbId(sched.id);
                                        const { courseName, semester, roomName, proctorName, examDate, campus: resolvedCampus, resolvedProgram } = resolveScheduleDisplay(sched);
                                        const displayCampus = sched.campus || sched.campus_name || resolvedCampus || '';
                                        const dateDisplay = examDate || '—';
                                        const resolvedCollege = resolveCollegeAcronym(sched);
                                        // ── Duplicate detection (mirrors add/edit form logic) ──
                                        const _isDup = status !== 'Rejected' && schedules.some(o => {
                                            if (dbId(String(o.id)) === safeId) return false;
                                            if ((o.status || '') === 'Rejected') return false;
                                            const oCourseId  = dbId(String(o.course_id || ''));
                                            const thisCourseId = dbId(String(sched.course_id || ''));
                                            const thisDate   = sched.exam_date || sched.date || '';
                                            const thisSlot   = sched.time_slot || '';
                                            const thisSec    = (sched.section || sched.section_name || sched.class_section || '').trim().toLowerCase();
                                            const thisCol    = (sched.college || '').trim().toLowerCase();
                                            const thisProg   = (sched.program || '').trim().toLowerCase();
                                            const oDate      = o.exam_date || o.date || '';
                                            const oSlot      = o.time_slot || '';
                                            const oSec       = (o.section || o.section_name || o.class_section || '').trim().toLowerCase();
                                            const oCol       = (o.college || '').trim().toLowerCase();
                                            const oProg      = (o.program || '').trim().toLowerCase();
                                            // True duplicate: same college + program + course + type + date + slot + section
                                            if (thisSec && thisCol && thisProg && thisCourseId && (sched.exam_type||'') && thisDate && thisSlot &&
                                                oSec === thisSec && oCol === thisCol && oProg === thisProg && oCourseId === thisCourseId &&
                                                (o.exam_type||'') === (sched.exam_type||'') && oDate === thisDate && oSlot === thisSlot) return true;
                                            return false;
                                        });
                                        return `
                            <tr class="transition sched-mgmt-row ${_isDup ? 'bg-amber-50 hover:bg-amber-100/70' : 'hover:bg-slate-50/50'}"
                                data-campus-row="${displayCampus}"
                                data-college-row="${resolvedCollege}"
                                data-search="${(courseName||sched.course_name||sched.course_code||'').toLowerCase()} ${resolvedCollege.toLowerCase()} ${(sched.exam_type||'').toLowerCase()} ${(sched.section||'').toLowerCase()} ${displayCampus.toLowerCase()}"
                                data-type-row="${(sched.exam_type||'').toLowerCase()}"
                                data-status-row="${status}"
                                data-duplicate="${_isDup ? 'true' : 'false'}"
                                data-page-view="schedule-mgmt"
                                data-filtered="false"
                                data-id="${safeId}">
                            <td class="pl-4 pr-2 py-4 text-center w-10">
                                <input type="checkbox" class="sched-row-cb w-4 h-4 rounded border-slate-300 text-emerald-600 accent-emerald-600 cursor-pointer"
                                    data-id="${safeId}" onchange="onSchedRowCbChange(this)">
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                ${_isDup ? '<span title="Duplicate schedule detected" class="inline-flex items-center mr-1 text-amber-500"><svg class=\'w-3.5 h-3.5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z\'/></svg></span>' : ''}${courseName || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${resolveCollegeAcronym(sched)}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${(resolvedProgram||sched.program) ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-violet-50 text-violet-700 border border-violet-100">${esc(resolvedProgram||sched.program)}</span>` : '<span class="text-slate-300">—</span>'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${semester ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">${esc(semester)}</span>` : '<span class="text-slate-300">—</span>'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${sched.exam_type || sched.examType || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${sched.year_level || sched.yearLevel || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${sched.section || sched.section_name || sched.class_section || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${dateDisplay}${sched.time_slot ? '<br><span class="text-xs text-slate-400">' + sched.time_slot + '</span>' : ''}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">${sched.duration || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">
    ${sched.is_online
        ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sky-100 text-sky-700 border border-sky-200">🌐 Online</span>'
        : (roomName || '—')}
</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${proctorName || '—'}</td>
                            <td class="px-6 py-4">${displayCampus ? `<span class="campus-badge">${displayCampus}</span>` : '<span class="text-slate-400 text-xs">—</span>'}</td>
                                            <td class="px-6 py-4">${statusBadge}</td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end items-center gap-1">
                                                    <button onclick="editSchedule('${safeId}')" title="Edit" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </button>
                                                    ${status === 'Pending' ? (() => {
                                                        const _rawDate = sched.exam_date || sched.date || '';
                                                        const _isOverdue = _rawDate && (() => { const d = new Date(_rawDate + 'T00:00:00'); const t = new Date(); t.setHours(0,0,0,0); return d < t; })();
                                                        return _isOverdue
                                                            ? `<button disabled title="Overdue — cannot approve" class="p-1.5 text-slate-300 bg-slate-50 rounded-lg cursor-not-allowed opacity-60" style="pointer-events:none;">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                               </button>`
                                                            : `<button onclick="approveSchedule('${safeId}')" title="Approve" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                               </button>`;
                                                    })() : ''}
                                                    ${status === 'Pending' ? `
                                                    <button onclick="rejectSchedule('${safeId}')" title="Reject" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </button>` : ''}
                                                    <button onclick="deleteSchedule('${safeId}')" title="Delete" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>`;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div id="pager-schedule-mgmt" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2"></div>
                    </div>
                    </div><!-- end schedules tab -->

                    <!-- TAB CONTENT: SPECIAL EXAM REGISTRATIONS -->
                    <div style="display:${activeTab==='special'?'block':'none'}">
                    <div class="bg-white rounded-b-xl border border-purple-200 shadow-sm overflow-hidden -mt-px">
                        <div class="px-5 py-4 border-b border-purple-100 flex flex-wrap items-center gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Special Exam Registrations</h3>
                                <p class="text-[11px] text-slate-400">Students registered to take special / removal examinations</p>
                            </div>
                            <span id="spExamBadge" class="ml-auto text-xs font-semibold text-purple-600 bg-purple-50 border border-purple-100 px-2.5 py-1 rounded-full">
                                ${(window._specialExams||[]).length} registered
                            </span>
                        </div>
                        <div class="p-4 flex flex-col md:flex-row gap-3 items-center border-b border-slate-100">
                            <div class="relative flex-1 w-full">
                                <input id="spExamSearch" type="text" placeholder="Search by student name, no., college..." oninput="filterSpecialExams()" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-purple-300">
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                                <select id="spExamSYFilter" onchange="filterSpecialExams()" class="filter-select">
                                    <option value="">All S.Y.</option>
                                    ${(() => {
                                        const cy = new Date().getFullYear();
                                        const sySet = new Set((window._specialExams||[]).map(e => e.school_year).filter(Boolean));
                                        sySet.add(`${cy}-${cy+1}`);
                                        sySet.add(`${cy-1}-${cy}`);
                                        return [...sySet].sort().reverse().map(sy => `<option value="${sy}">${sy}</option>`).join('');
                                    })()}
                                </select>
                                <select id="spExamSemFilter" onchange="filterSpecialExams()" class="filter-select">
                                    <option value="">All Semesters</option>
                                    <option>1st Semester</option><option>2nd Semester</option><option>Summer</option>
                                </select>
                                <select id="spExamTypeFilter" onchange="filterSpecialExams()" class="filter-select">
                                    <option value="">All Types</option>
                                    <option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option><option>Others</option>
                                </select>
                                <button onclick="spExamClearFilters()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800 whitespace-nowrap">Clear</button>
                                <button onclick="exportSpecialExamPDF()" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-slate-600 hover:bg-slate-700 rounded-lg transition shadow-sm whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Export PDF
                                </button>
                                <button onclick="openSpecialExamModal()" class="flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 shadow-sm transition whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5"/></svg>
                                    Add Registration
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-purple-50 border-b border-purple-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Student No.</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Last Name</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">First Name</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">College / Program</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">S.Y.</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Semester</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Exam Type</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Receipt No.</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">No. of Exams</th>
                                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Reason</th>
                                        <th class="px-4 py-3 text-right text-[11px] font-bold text-purple-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="specialExamBody" class="divide-y divide-slate-100">
                                    ${(() => {
                                        const rows = window._specialExams || [];
                                        if (rows.length === 0) return `<tr><td colspan="11" class="py-16 text-center"><div class="flex flex-col items-center gap-3"><div class="w-12 h-12 bg-purple-50 rounded-full flex items-center justify-center"><svg class="w-6 h-6 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg></div><p class="text-slate-400 text-sm font-medium">No special exam registrations yet</p><p class="text-slate-300 text-xs">Click "Add Registration" to register a student</p></div></td></tr>`;
                                        return rows.map(e => {
                                            const courses = (() => { try { return JSON.parse(e.exam_courses||'[]'); } catch(_){return[];} })();
                                            return `
                                        <tr class="hover:bg-purple-50/30 transition sp-exam-row"
                                            data-search="${(e.student_no||'').toLowerCase()} ${(e.last_name||'').toLowerCase()} ${(e.first_name||'').toLowerCase()} ${(e.college||'').toLowerCase()} ${(e.program||'').toLowerCase()}"
                                            data-sy="${e.school_year||''}"
                                            data-sem="${e.semester||''}"
                                            data-type="${(e.exam_type||'').toLowerCase()}">
                                            <td class="px-4 py-3 text-sm font-mono text-slate-700">${esc(e.student_no||'—')}</td>
                                            <td class="px-4 py-3 text-sm font-bold text-slate-800">${esc(e.last_name||'—')}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">${esc(e.first_name||'—')}</td>
                                            <td class="px-4 py-3 text-sm"><span class="font-semibold text-slate-800">${esc(e.college||'—')}</span>${e.program ? `<br><span class="text-xs text-slate-400">${esc(e.program)}</span>` : ''}</td>
                                            <td class="px-4 py-3 text-sm text-slate-600">${esc(e.school_year||'—')}</td>
                                            <td class="px-4 py-3 text-sm text-slate-600">${esc(e.semester||'—')}</td>
                                            <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">${esc(e.exam_type||'—')}</span></td>
                                            <td class="px-4 py-3 text-sm font-mono text-slate-600">${esc(e.receipt_no||'—')}</td>
                                            <td class="px-4 py-3 text-sm text-center font-bold text-purple-700">${esc(String(e.num_exams||'0'))}</td>
                                            <td class="px-4 py-3 text-sm text-slate-500 max-w-[160px]"><span class="truncate block" title="${esc(e.reason||'')}">${esc(e.reason||'—')}</span></td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <button onclick="openViewSpecialExam(${e.id})" class="p-1.5 text-purple-400 hover:text-purple-700 hover:bg-purple-50 rounded-lg transition" title="View">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                                <button onclick="openSpecialExamModal(${e.id})" class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button onclick="deleteSpecialExam(${e.id})" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>`;
                                        }).join('');
                                    })()}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div><!-- end special tab -->

                </div>`;
            }

           if (currentView === 'view-schedule') {
            const schedules = allData.filter(d => d.type === 'schedule');
            return `
            <div class="fade-in space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-left">
            <div class="stat-card-small">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Total Schedules</p><h3 class="text-2xl font-bold text-slate-800">${totalSchedules}</h3></div>
                <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg></div>
            </div>
            <div class="stat-card-small">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Approved</p><h3 class="text-2xl font-bold text-emerald-500">${approvedSchedules}</h3></div>
                <div class="w-9 h-9 bg-emerald-50 text-emerald-500 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2"/></svg></div>
            </div>
            <div class="stat-card-small">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Pending</p><h3 class="text-2xl font-bold text-orange-500">${pendingSchedules}</h3></div>
                <div class="w-9 h-9 bg-orange-50 text-orange-500 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/></svg></div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 pt-4 pb-3 flex flex-wrap items-center gap-2 border-b border-slate-100">
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input id="viewSchedSearch" type="text" placeholder="Search code/room..." oninput="viewSchedFilter()"
                           class="pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-emerald-400 w-36">
                </div>
                <select id="viewSchedCollege" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[130px]">
                    <option value="">All Colleges</option>
                    ${allData.filter(d => d.type === 'college' && d.code)
                        .reduce((acc, c) => {
                            if (!acc.find(x => x.code === c.code)) acc.push({ code: c.code, name: c.name || '' });
                            return acc;
                        }, [])
                        .sort((a, b) => a.code.localeCompare(b.code))
                        .map(c => `<option value="${c.code}">${c.code}${c.name ? ' — ' + c.name : ''}</option>`).join('')}
                </select>
                <select id="viewSchedSemester" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[130px]">
                    <option value="">All Semesters</option>
                    ${SEMESTERS.map(s => `<option value="${s}">${s}</option>`).join('')}
                </select>
                <select id="viewSchedCourse" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[110px]">
                    <option value="">All Courses</option>
                    ${[...new Set(allData.filter(d => d.type === 'course' && d.course_code).map(c => c.course_code))].sort().map(c => `<option value="${c}">${c}</option>`).join('')}
                </select>
                <select id="viewSchedType" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[110px]">
                    <option value="">All Exams</option>
                    <option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option>
                </select>
                <select id="viewSchedYearLevel" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[100px]">
                    <option value="">All Levels</option>
                    ${YEAR_LEVELS.map(y => `<option value="${y}">${y}</option>`).join('')}
                </select>
                <select id="viewSchedStatus" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[110px]">
                    <option value="">All Statuses</option>
                    <option value="Approved">Approved</option>
                    <option value="Pending">Pending</option>
                    <option value="Rejected">Rejected</option>
                </select>
                <button onclick="viewSchedClearFilters()" class="ml-auto text-xs text-slate-400 hover:text-red-500 font-medium transition flex items-center gap-1 shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear All Filters
                </button>
            </div>

                <!-- Campus Tab Strip -->
                ${renderCampusTabs('view-schedule', 'viewSchedFilter()', schedules)}
                    <!-- Amber hint + Export PDF row -->
                    <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-start gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 max-w-xl">
                            <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>To export <strong>only specific schedule</strong>, please apply filters first (e.g. College, Semester, Year Level, Course) before clicking <strong>Export PDF</strong>. Exporting without filters will include all schedules.</span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span id="viewSchedCount" class="text-xs text-slate-400"></span>
                            <button onclick="exportSchedulePDF()"
                                class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition shadow-sm whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export PDF
                            </button>
                        </div>
                    </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Course</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">College</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Program</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Semester</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Exam Type</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Year Level</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Section</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Duration</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Room</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Proctor</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Campus</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody id="viewSchedBody" class="divide-y divide-slate-100">
                        ${schedules.length === 0
                            ? `<tr><td colspan="13" class="py-24 text-center">
                                   <div class="flex flex-col items-center gap-3">
                                       <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center">
                                           <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                       </div>
                                       <p class="text-slate-400 text-sm font-medium">No schedules found</p>
                                   </div>
                               </td></tr>`
                            : schedules.map(sched => {
                                const status = sched.status || 'Pending';
                                const campus = sched.campus || '';
                                const rawDate = sched.exam_date || sched.exam_dat || sched.date || '';
                                let dateDisplay = '—';
                                if (rawDate && rawDate !== '0000-00-00' && rawDate !== '0000-00-00 00:00:00' && rawDate !== 'null') {
                                    const d = new Date(String(rawDate).trim().substring(0,10) + 'T00:00:00');
                                    if (!isNaN(d.getTime()) && d.getFullYear() > 1970) dateDisplay = d.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
                                }
                                const statusBadge = status === 'Approved'
                                    ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">✓ Approved</span>`
                                    : status === 'Rejected'
                                    ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">✕ Rejected</span>`
                                    : `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">⏳ Pending</span>`;
                               const college = resolveCollegeAcronym(sched);
                            const { courseName, semester, roomName, proctorName, resolvedProgram } = resolveScheduleDisplay(sched);
                            return `
                                <tr class="hover:bg-slate-50/50 transition"
                    data-campus-row="${campus}"
                    data-college-row="${college}"
                    data-search="${(courseName||sched.course_name||sched.course_code||'').toLowerCase()} ${college.toLowerCase()} ${(sched.exam_type||'').toLowerCase()} ${(sched.section||'').toLowerCase()} ${campus.toLowerCase()}"
                    data-type-row="${(sched.exam_type||'').toLowerCase()}"
                    data-status-row="${status}"
                    data-semester-row="${(semester||sched.semester||sched.semester_name||'').toLowerCase()}"
                    data-course-row="${(sched.course_code||'').toLowerCase()}"
                    data-year-row="${(sched.year_level||sched.yearLevel||'').toLowerCase()}">
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">${sched.course_code || courseName || '—'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${college}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${(resolvedProgram||sched.program) ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-violet-50 text-violet-700 border border-violet-100">${esc(resolvedProgram||sched.program)}</span>` : '<span class="text-slate-300">—</span>'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${semester ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">${esc(semester)}</span>` : '<span class="text-slate-300">—</span>'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${sched.exam_type || '—'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${sched.year_level || sched.yearLevel || '—'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${sched.section || '—'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${dateDisplay}${sched.time_slot ? '<br><span class="text-xs text-slate-400">' + sched.time_slot + '</span>' : ''}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${sched.duration || '—'}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">
    ${sched.is_online
        ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-sky-100 text-sky-700 border border-sky-200">🌐 Online</span>'
        : (roomName || '—')}
</td>
                    <td class="px-6 py-4 text-sm text-slate-600">${proctorName || '—'}</td>
                    <td class="px-6 py-4">${campus ? '<span class="campus-badge">' + campus + '</span>' : '<span class="text-slate-400 text-xs">—</span>'}</td>
                    <td class="px-6 py-4">${statusBadge}</td>
                </tr>`;
                            }).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    </div>`;
}

            // ── ROOMS ──────────────────────────────────────────────────────────
           // ── ROOMS ──────────────────────────────────────────────────────────
if (currentView === 'rooms') {
    const rooms          = allData.filter(d => d.type === 'room');
    const blockedCount   = rooms.filter(r => r.locked).length;
    const ALL_SLOTS_CHECK = ['07:00 AM - 08:00 AM','08:00 AM - 09:00 AM','09:00 AM - 10:00 AM','10:00 AM - 11:00 AM','11:00 AM - 12:00 PM','12:00 PM - 01:00 PM','01:00 PM - 02:00 PM','02:00 PM - 03:00 PM','03:00 PM - 04:00 PM','04:00 PM - 05:00 PM','05:00 PM - 06:00 PM','06:00 PM - 07:00 PM','07:00 PM - 08:00 PM','08:00 PM - 09:00 PM'];
    const todayStr = new Date().toISOString().split('T')[0];
    const unavailableCount = rooms.filter(r => {
        if (r.locked) return false;
        const rid = dbId(String(r.id));
        const byDate = {};
        allData.filter(d => d.type==='schedule' && dbId(String(d.room_id||d.room||''))===rid && (d.exam_date||d.date||'')>=todayStr)
               .forEach(s => { const d=s.exam_date||s.date||''; if(!byDate[d]) byDate[d]=new Set(); byDate[d].add(s.time_slot||''); });
        return Object.values(byDate).some(slots => ALL_SLOTS_CHECK.every(sl => slots.has(sl)));
    }).length;
    const availableCount = rooms.length - blockedCount - unavailableCount;
    return `
    <div class="fade-in space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="stat-card-small">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Total Rooms</p><h3 class="text-2xl font-bold text-slate-800">${rooms.length}</h3></div>
                <div class="w-9 h-9 bg-slate-100 text-slate-500 rounded-md flex items-center justify-center shrink-0 ml-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="stat-card-small">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Available</p><h3 class="text-2xl font-bold text-emerald-600">${availableCount}</h3></div>
                <div class="w-9 h-9 bg-emerald-50 text-emerald-500 rounded-md flex items-center justify-center shrink-0 ml-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="stat-card-small">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Unavailable</p><h3 class="text-2xl font-bold text-orange-500">${unavailableCount}</h3></div>
                <div class="w-9 h-9 bg-orange-50 text-orange-400 rounded-md flex items-center justify-center shrink-0 ml-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="stat-card-small">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Blocked</p><h3 class="text-2xl font-bold text-red-500">${blockedCount}</h3></div>
                <div class="w-9 h-9 bg-red-50 text-red-400 rounded-md flex items-center justify-center shrink-0 ml-4">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4 text-left">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg></span>
                    <input id="roomsSearch" type="text" oninput="roomsFilter()" placeholder="Search rooms..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                ${campusFilterSelect('roomsCampus', "campusFilters['rooms']=this.value;document.querySelectorAll('#campus-tab-strip-rooms .campus-tab').forEach(b=>{b.classList.toggle('active',b.textContent.trim().startsWith(this.value||'All'))});roomsFilter()")}
                <select id="roomsStatusFilter" onchange="roomsFilter()" class="filter-select">
                    <option value="">All Status</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                    <option value="blocked">Blocked</option>
                </select>
                <button onclick="roomsClearFilters()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800">Clear</button>
            </div>
            <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-100 items-center">
                            ${renderDataButtons('rooms', rooms.length)}
                            <button onclick="window.openAddRoomModal()" class="btn-action-large bg-emerald-600 text-white hover:bg-emerald-700 ml-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Room
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100">${renderCampusTabs('rooms', 'roomsFilter()', rooms)}</div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Building</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Name</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Capacity</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Floor</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Campus</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Block Reason</th>
                            <th class="px-6 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="roomsBody" class="divide-y divide-slate-100">
                        ${rooms.length > 0 ? rooms.map(room => {
                            const rid      = String(room.id);
                            const campus = room.campus || room.campus_name || room.location || '';
                            const isLocked = !!room.locked;
                            const reason   = room.block_reason || '';
                            const fromDate = room.blocked_from ? new Date(room.blocked_from + 'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '';
                            const toDate   = room.blocked_to   ? new Date(room.blocked_to   + 'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : '';
                            const dateRange = (fromDate && toDate) ? `${fromDate} – ${toDate}` : fromDate ? `From ${fromDate}` : toDate ? `Until ${toDate}` : '';

                            // Check if fully booked: all 8 slots taken for today or any upcoming date
                            const ALL_SLOTS = ['07:00 AM - 08:00 AM','08:00 AM - 09:00 AM','09:00 AM - 10:00 AM','10:00 AM - 11:00 AM','11:00 AM - 12:00 PM','12:00 PM - 01:00 PM','01:00 PM - 02:00 PM','02:00 PM - 03:00 PM','03:00 PM - 04:00 PM','04:00 PM - 05:00 PM','05:00 PM - 06:00 PM','06:00 PM - 07:00 PM','07:00 PM - 08:00 PM','08:00 PM - 09:00 PM'];
                            const today = new Date().toISOString().split('T')[0];
                            const roomSchedules = allData.filter(d => d.type === 'schedule' && dbId(String(d.room_id||d.room||'')) === rid && (d.exam_date||d.date||'') >= today);
                            const bookedByDate = {};
                            roomSchedules.forEach(s => {
                                const d = s.exam_date||s.date||'';
                                if (!bookedByDate[d]) bookedByDate[d] = new Set();
                                bookedByDate[d].add(s.time_slot||'');
                            });
                            const isFullyBooked = !isLocked && Object.values(bookedByDate).some(slots => ALL_SLOTS.every(sl => slots.has(sl)));
                            const bookedDates = Object.entries(bookedByDate).filter(([,slots]) => ALL_SLOTS.every(sl => slots.has(sl))).map(([d]) => new Date(d+'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric'}));
                            
                            return `
                            <tr class="hover:bg-slate-50 transition"
                                data-campus-row="${campus}"
                                data-page-view="rooms"
                                data-filtered="false"
                                data-status-row="${isLocked ? 'blocked' : isFullyBooked ? 'unavailable' : 'available'}"
                                data-search="${(room.name||'').toLowerCase()} ${(room.building||'').toLowerCase()} ${campus.toLowerCase()}">
                                
                                <td class="px-6 py-4 text-sm text-slate-700">${esc(room.building)||'—'}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-900">${esc(room.name)||'—'}</td>
                                
                                <td class="px-6 py-4 text-sm text-slate-700">${room.capacity||'—'}</td>
                               <td class="px-6 py-4 text-sm text-slate-700">${(() => {
                            const f = (room.floor || '').toString().trim();
                            if (f && /[a-zA-Z]/.test(f)) return esc(f);
                            let n = f ? parseInt(f) : NaN;
                            if (isNaN(n) || !f) {
                                const nameMatch = (room.name || '').match(/(\d)/);
                                if (nameMatch) n = parseInt(nameMatch[1]);
                            }
                            if (isNaN(n)) return '—';
                            const suffix = n === 1 ? 'st' : n === 2 ? 'nd' : n === 3 ? 'rd' : 'th';
                            return `${n}${suffix} Floor`;
                        })()}</td>
                                <td class="px-6 py-4">${campus ? `<span class="campus-badge">${campus}</span>` : '<span class="text-slate-400 text-xs">—</span>'}</td>
                                <td class="px-6 py-4">
                                    ${isLocked
                                        ? `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                               <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>Blocked</span>`
                                        : isFullyBooked
                                        ? `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                                               <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Unavailable</span>`
                                        : `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                               <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Available</span>`}
                                </td>
                                <td class="px-6 py-4 max-w-[180px]">
                                    ${isLocked && (reason || dateRange)
                                        ? `<div>${reason ? `<p class="text-xs text-slate-600 truncate" title="${esc(reason)}">${esc(reason)}</p>` : ''}${dateRange ? `<p class="text-[11px] text-slate-400 mt-0.5">${dateRange}</p>` : ''}</div>`
                                        : isFullyBooked && bookedDates.length
                                        ? `<p class="text-xs text-orange-600">All slots full on: ${bookedDates.slice(0,3).join(', ')}${bookedDates.length > 3 ? ` +${bookedDates.length-3} more` : ''}</p>`
                                        : `<span class="text-slate-300 text-xs">—</span>`}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button onclick="window.editRoom('${rid}')" title="Edit" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        ${isLocked
                                            ? `<button onclick="window.unblockRoom('${rid}')" title="Unblock" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                               </button>`
                                            : `<button onclick="window.openBlockRoomModal('${rid}')" title="Block" class="p-1.5 text-slate-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition">
                                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/></svg>
                                               </button>`}
                                        <button onclick="window.deleteRoom('${rid}')" title="Delete" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>`;
                        }).join('') : `<tr><td colspan="8" class="px-6 py-16 text-center text-sm text-slate-500">No rooms found. Click "Add Room" to create your first room.</td></tr>`}
                    </tbody>
                </table>
            </div>
            <div id="pager-rooms" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2"></div>
        </div>
    </div>`;
}

            // ── ANALYTICS ─────────────────────────────────────────────────────
            if (currentView === 'analytics') {
                const rejectedSchedules = analyticsSchedules.filter(s => s.status === 'Rejected').length;
                return `
                <div class="fade-in space-y-6 text-left">
                    <!-- Stat cards — same 5-col grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="stat-card-small">
                            <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Total Schedules</p><h3 class="text-2xl font-bold text-slate-800">${totalSchedules}</h3></div>
                            <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg></div>
                        </div>
                        <div class="stat-card-small">
                            <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Approved</p><h3 class="text-2xl font-bold text-emerald-500">${approvedSchedules}</h3></div>
                            <div class="w-9 h-9 bg-emerald-50 text-emerald-500 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2"/></svg></div>
                        </div>
                        <div class="stat-card-small">
                            <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Pending Approval</p><h3 class="text-2xl font-bold text-orange-500">${pendingSchedules}</h3></div>
                            <div class="w-9 h-9 bg-orange-50 text-orange-500 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/></svg></div>
                        </div>
                        <div class="stat-card-small" style="border-color:${rejectedSchedules>0?'#fecaca':''}">
                            <div><p class="text-[10px] font-bold text-red-400 uppercase mb-0.5">Rejected</p><h3 class="text-2xl font-bold text-red-600">${rejectedSchedules}</h3><p class="text-[10px] text-slate-400 mt-0.5">See details below</p></div>
                            <div class="w-9 h-9 bg-red-50 text-red-500 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" stroke-width="2"/></svg></div>
                        </div>
                        <div class="stat-card-small">
                            <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Scheduled</p><h3 class="text-2xl font-bold text-blue-600">${scheduledPercent}%</h3><p class="text-[10px] text-slate-400 mt-0.5">of total approved</p></div>
                            <div class="w-9 h-9 bg-blue-50 text-blue-600 rounded-md flex items-center justify-center shrink-0 ml-4"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14" stroke-width="2"/></svg></div>
                        </div>
                    </div>
                    <!-- White card: campus tabs + data buttons + metric cards (mirrors schedule-mgmt white card) -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <!-- Filter bar -->
                        <div class="p-4 flex flex-col md:flex-row gap-3 items-center border-b border-slate-100">
                            <span class="text-sm font-semibold text-slate-700 flex-1">Analytics Overview</span>
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                                <!-- Date range pills -->
                                <div id="analyticsDatePills" class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                                    <button onclick="analyticsDateFilter='all';analyticsFilter()" data-pill="all" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">All Time</button>
                                    <button onclick="analyticsDateFilter='day';analyticsFilter()" data-pill="day" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">Today</button>
                                    <button onclick="analyticsDateFilter='week';analyticsFilter()" data-pill="week" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Week</button>
                                    <button onclick="analyticsDateFilter='month';analyticsFilter()" data-pill="month" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Month</button>
                                </div>
                                ${renderDataButtons('analytics')}
                            </div>
                        </div>
                        <!-- Campus Tab Strip -->
                        ${renderCampusTabs('analytics', 'analyticsFilter()', schedules)}
                        <!-- Record count bar -->
                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                            <span class="text-xs text-slate-400 font-medium">${totalSchedules} schedule${totalSchedules !== 1 ? 's' : ''} total</span>
                        </div>
                        <!-- Metric cards inside the white card -->
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 xl:grid-cols-7 gap-4" id="analyticsCards">
                                ${renderAnalyticsCards(schedules, scheduledPercent, approvedSchedules, pendingSchedules, totalSchedules, '')}
                            </div>
                        </div>
                    </div>
                    <!-- Donut Charts Row -->
                    <div class="grid grid-cols-1 gap-6">
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm min-h-[250px]">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-2 h-5 bg-emerald-500 rounded-full"></div>
                                <h3 class="font-bold text-slate-800 text-sm">Schedules by Department/Program</h3>
                            </div>
                            <div id="byDeptChart"></div>
                        </div>
                    </div>
                    <!-- Room Utilization -->
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex justify-between items-center mb-5">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-5 bg-blue-500 rounded-full"></div>
                                <h3 class="font-bold text-slate-800 text-sm">Room Utilization & Availability</h3>
                            </div>
                            <span class="text-xs text-slate-400" id="roomUtilInfo"></span>
                        </div>
                        <div id="roomUtilChart" class="space-y-1.5"></div>
                        <div id="roomUtilPager" class="mt-4 flex items-center justify-between hidden">
                            <span class="text-xs text-slate-400" id="roomUtilPagerInfo"></span>
                            <div class="flex items-center gap-1" id="roomUtilPagerControls"></div>
                        </div>
                    </div>
                    <!-- Most Utilized Buildings & Rooms (Donut) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="p-2 bg-amber-50 rounded-lg shrink-0">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Utilized Buildings</h3>
                                    <p class="text-[10px] text-slate-400">Ranked by total exam bookings</p>
                                </div>
                            </div>
                            <div id="topBuildingsChart"></div>
                        </div>
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="p-2 bg-blue-50 rounded-lg shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M14 3v18"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Utilized Rooms</h3>
                                    <p class="text-[10px] text-slate-400">Top rooms by booking frequency</p>
                                </div>
                            </div>
                            <div id="topRoomsChart"></div>
                        </div>
                    </div>
                    <!-- Proctor Analytics -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <!-- Header -->
                        <div class="p-4 flex flex-col md:flex-row gap-3 items-center border-b border-slate-100">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="p-2 bg-purple-50 rounded-lg shrink-0">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Proctor Analytics</h3>
                                    <p class="text-[10px] text-slate-400">Exam assignments per proctor filtered by period</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                                <div id="proctorDatePills" class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                                    <button onclick="proctorAnalyticsFilter('all')" data-ppill="all" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">All Time</button>
                                    <button onclick="proctorAnalyticsFilter('day')"   data-ppill="day"   class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">Today</button>
                                    <button onclick="proctorAnalyticsFilter('week')"  data-ppill="week"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Week</button>
                                    <button onclick="proctorAnalyticsFilter('month')" data-ppill="month" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Month</button>
                                    <button onclick="proctorAnalyticsFilter('year')"  data-ppill="year"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Year</button>
                                </div>
                                <button onclick="exportProctorAnalyticsPDF()"
                                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition shadow-sm whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download PDF
                                </button>
                            </div>
                        </div>
                        <!-- Summary stat bar -->
                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex items-center gap-4 flex-wrap">
                            <span id="proctorAnalyticsSummary" class="text-xs text-slate-400 font-medium"></span>
                        </div>
                        <!-- Chart area -->
                        <div class="p-4">
                            <div id="proctorAnalyticsChart" class="space-y-3"></div>
                        </div>
                        <!-- Table -->
                        <div class="overflow-x-auto border-t border-slate-100">
                            <table class="w-full text-left" id="proctorAnalyticsTable">
                                <thead class="bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">#</th>
                                        <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Proctor Name</th>
                                        <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Campus</th>
                                        <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Exams as Proctor</th>
                                        <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Approved</th>
                                        <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pending</th>
                                        <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">% Share</th>
                                    </tr>
                                </thead>
                                <tbody id="proctorAnalyticsBody" class="divide-y divide-slate-100"></tbody>
                            </table>
                        </div>
                        <!-- Proctor Analytics Pager -->
                        <div id="proctorAnalyticsPager" class="flex items-center justify-between px-4 py-3 border-t border-slate-100 flex-wrap gap-2"></div>
                    </div>
                    <!-- Scheduling Conflicts -->
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-red-50 rounded-lg shrink-0">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm">Scheduling Conflicts</h3>
                                    <p class="text-[10px] text-slate-400">Double-bookings, locked rooms &amp; unassigned rooms</p>
                                </div>
                            </div>
                            <div id="conflictSummaryBadge"></div>
                        </div>
                        <!-- Conflicts type-filter pills -->
                        <div id="conflictTypePills" class="flex flex-wrap gap-1.5 mb-4">
                            <button onclick="conflictsTypeFilter('all')" data-cpill="all" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap bg-white text-slate-700 shadow-sm border border-slate-200">All Types</button>
                            <button onclick="conflictsTypeFilter('locked')"  data-cpill="locked"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">🔒 Locked Room</button>
                            <button onclick="conflictsTypeFilter('double')"  data-cpill="double"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">⚠️ Double-Booking</button>
                            <button onclick="conflictsTypeFilter('noroom')"  data-cpill="noroom"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">📋 No Room</button>
                            <button onclick="conflictsTypeFilter('proctor')" data-cpill="proctor" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">👤 Proctor Conflict</button>
                            <button onclick="conflictsTypeFilter('section')" data-cpill="section" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">🎓 Section Overlap</button>
                        </div>
                        <div id="conflictsTable"></div>
                        <!-- Conflicts Pager -->
                        <div id="conflictsPager" class="flex items-center justify-between px-1 pt-3 border-t border-slate-100 flex-wrap gap-2 mt-3"></div>
                    </div>
                    <!-- ── Rejected Schedules (Campus-scoped) ─────────────────── -->
                    ${(() => {
                        const myC = currentUser.campus || '';
                        const rejScopes = allData.filter(d =>
                            d.type === 'schedule' &&
                            d.status === 'Rejected' &&
                            (!myC || (d.campus || d.room_campus || '') === myC)
                        );
                        const scopeLabel = myC || 'Your Campus';
                        const esc = v => String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

                        // Store for pagination
                        window._rejScopes = rejScopes;
                        window._rejShowCampusCol = false;

                        return `
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="p-5 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-red-50 rounded-lg shrink-0">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-sm">Rejected Schedules</h3>
                                        <p class="text-[10px] text-slate-400">${esc(scopeLabel)} · ${rejScopes.length} rejected schedule${rejScopes.length!==1?'s':''}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                                    ${rejScopes.length} Rejected
                                </span>
                            </div>
                            ${rejScopes.length === 0 ? `
                            <div class="py-14 flex flex-col items-center justify-center text-center gap-2">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center mb-1">
                                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">No rejected schedules</p>
                                <p class="text-xs text-slate-400">All schedules for ${esc(scopeLabel)} are in good standing.</p>
                            </div>` : `
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-50 border-b border-slate-100">
                                        <tr>
                                            <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Course</th>
                                            <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">College</th>
                                            <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Exam Type</th>
                                            <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
                                            <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Room</th>
                                            <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rejSchedBody" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100 flex-wrap gap-2" id="rejSchedPager"></div>`}
                        </div>`;
                    })()}
                </div>`;
            }

            // ── FEEDBACKS ─────────────────────────────────────────────────────
           if (currentView === 'feedbacks') {
            const feedbacks = allData.filter(d => d.type === 'feedback');
            const unread = feedbacks.filter(f => !f.is_read).length;

            // ── Build college & program options from feedback data ──────────────
            const fbColleges = [...new Set(feedbacks.map(f => (f.college||'').trim()).filter(Boolean))].sort();
            const fbPrograms = [...new Set(feedbacks.map(f => (f.program||'').trim()).filter(Boolean))].sort();

            return `
            <div class="fade-in space-y-6">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-wrap justify-between items-center gap-4 text-left">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Student Feedbacks</h3>
                <p class="text-sm text-slate-400">${unread} unread feedback${unread !== 1 ? 's' : ''}</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button onclick="markAllFeedbacksRead()" class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition">Mark all as read</button>
                ${renderDataButtons('feedbacks', feedbacks.length)}
            </div>
        </div>

        <!-- Campus Tab Strip -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-3 flex flex-wrap items-center gap-2">
            ${renderCampusTabs('feedbacks', 'feedbacksFilter()', feedbacks)}
            <span class="ml-auto text-xs text-slate-400 font-medium">${feedbacks.length} feedback${feedbacks.length !== 1 ? 's' : ''}</span>
        </div>

        <!-- College / Program / Status / Search Filters -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[180px]">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input id="fbSearchInput" type="text" placeholder="Search by name, subject, message…"
                    oninput="feedbacksFilter()"
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-emerald-400">
            </div>
            <select id="fbCollegeFilter" onchange="feedbacksFilter();updateFbProgramOptions()" class="filter-select">
                <option value="">All Colleges</option>
                ${fbColleges.map(c => `<option value="${esc(c)}">${esc(c)}</option>`).join('')}
            </select>
            <select id="fbProgramFilter" onchange="feedbacksFilter()" class="filter-select">
                <option value="">All Programs</option>
                ${fbPrograms.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('')}
            </select>
            <select id="fbStatusFilter" onchange="feedbacksFilter()" class="filter-select">
                <option value="">All Status</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
            </select>
            <select id="fbCategoryFilter" onchange="feedbacksFilter()" class="filter-select">
                <option value="">All Categories</option>
                ${[...new Set(feedbacks.map(f=>(f.category||'').trim()).filter(Boolean))].sort().map(c=>`<option value="${esc(c)}">${esc(c)}</option>`).join('')}
            </select>
            <button onclick="clearFbFilters()" class="text-xs text-slate-400 hover:text-slate-700 font-medium px-2 whitespace-nowrap transition">Clear filters</button>
        </div>

        ${feedbacks.length === 0 ? `
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm min-h-[400px] flex flex-col items-center justify-center p-10 text-center text-slate-400">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-6 text-slate-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-slate-600 font-bold text-lg">No feedbacks yet</h3>
                <p class="text-sm mt-1">Student feedback will appear here once submitted</p>
            </div>
        ` : `
            <div id="feedbacksBody" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${feedbacks.map(f => {
                    const campus = f.campus || '';
                    const isUnread = !f.is_read;
                    const date = f.created_at ? new Date(f.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
                   return `
                    <div class="bg-white p-5 rounded-xl border ${isUnread ? 'border-emerald-200 shadow-md' : 'border-slate-200 shadow-sm'} transition cursor-pointer hover:shadow-lg hover:border-emerald-300 hover:-translate-y-0.5 transform"
                         data-page-view="feedbacks"
                         data-campus-row="${campus}"
                         data-fb-college="${esc(f.college||'')}"
                         data-fb-program="${esc(f.program||'')}"
                         data-fb-status="${isUnread ? 'unread' : 'read'}"
                         data-fb-category="${esc(f.category||'')}"
                         data-fb-search="${esc((f.student_name||'') + ' ' + (f.subject||'') + ' ' + (f.message||'')).toLowerCase()}"
                         onclick="openAdminFeedbackDetail(${JSON.stringify(f).replace(/"/g,'&quot;')})">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-sm shrink-0">
                                    ${(f.student_name || 'A').charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800">${esc(f.student_name || 'Anonymous')}</span>
                                    ${isUnread ? '<span class="ml-2 inline-block w-2 h-2 bg-emerald-500 rounded-full align-middle"></span>' : ''}
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs text-slate-400">${date}</span>
                                <button onclick="event.stopPropagation();deleteFeedback('${f.id}')" title="Delete" class="p-1 text-slate-300 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            ${f.college ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">${esc(f.college)}</span>` : ''}
                            ${f.program ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-100">${esc(f.program)}</span>` : ''}
                            ${f.category ? `<span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">${esc(f.category)}</span>` : ''}
                            ${f.exam_difficulty ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${f.exam_difficulty.startsWith('30') ? 'bg-amber-50 text-amber-700 border border-amber-200' : f.exam_difficulty.startsWith('50') ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'bg-red-50 text-red-700 border border-red-200'}">${f.exam_difficulty.startsWith('30') ? '😐' : f.exam_difficulty.startsWith('50') ? '😓' : '😱'} ${esc(f.exam_difficulty)}</span>` : ''}
                        </div>
                        ${f.subject ? `<p class="text-sm font-semibold text-slate-700 mb-1">${esc(f.subject)}</p>` : ''}
                        <p class="text-sm text-slate-600 italic line-clamp-2">"${esc(f.message)}"</p>
                        ${f.student_number ? `<p class="text-xs text-slate-400 mt-2">Student #: ${esc(f.student_number)}</p>` : ''}
                        <p class="text-[10px] text-emerald-600 font-semibold mt-2">Click to view full details →</p>
                    </div>`;
                }).join('')}
            </div>
            <div id="fbNoResults" class="hidden bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <p class="font-semibold text-slate-500">No feedbacks match your filters</p>
                <button onclick="clearFbFilters()" class="mt-3 text-xs text-emerald-600 hover:text-emerald-700 font-semibold underline">Clear all filters</button>
            </div>
        `}
    </div>`;
}

            // ── USERS ──────────────────────────────────────────────────────────
            if (currentView === 'users') {
                return `
                <div class="fade-in space-y-5 max-w-7xl mx-auto">

                    <!-- ADD USER MODAL -->
                    <div id="addUserModal" class="modal-overlay">
                        <div class="modal-box">
                            <div class="flex justify-between items-center px-8 py-5 border-b border-slate-200">
                                <h2 class="text-xl font-bold text-slate-800">Add User</h2>
                                <button onclick="closeModal('addUserModal')" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <form id="addUserForm" class="px-8 py-6" onsubmit="submitAddUser(event)">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Full Name <span class="text-red-500">*</span></label>
                                        <input name="full_name" type="text" placeholder="e.g. Juan Dela Cruz" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm" required oninput="checkAddUserDuplicate()">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Username <span class="text-red-500">*</span></label>
                                        <input name="username" type="text" placeholder="e.g. jdelacruz" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm" required>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Password <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input name="password" id="add-pass" type="password" placeholder="Min. 8 chars + special character" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm pr-10" required minlength="8" oninput="checkAddPassStrength(this.value)">
                                            <button type="button" onclick="togglePass('add-pass')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </div>
                                        <div id="add-pass-requirements" class="hidden mt-1.5 space-y-1">
                                            <div id="add-req-length" class="flex items-center gap-1.5 text-xs text-slate-400">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/></svg>
                                                <span>At least 8 characters</span>
                                            </div>
                                            <div id="add-req-special" class="flex items-center gap-1.5 text-xs text-slate-400">
                                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/></svg>
                                                <span>At least 1 special character (!@#$%^&amp;*...)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Role <span class="text-red-500">*</span></label>
                                        <select name="role" id="add-role" onchange="toggleAssignment('add')" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white" required>
                                            <option value="">— Select Role —</option>
                                            <option value="Admin">👨‍💼 Admin</option>
                                            <option value="Program Head">👩‍🏫 Program Head</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Email</label>
                                        <input name="email" type="email" placeholder="email@fatima.edu.ph" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm" oninput="checkAddUserDuplicate()">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Campus <span class="text-red-500">*</span></label>
                                        <select name="campus" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100" required disabled>
                                            <option value="${esc(currentUser.campus)}" selected>${esc(currentUser.campus)}</option>
                                        </select>
                                        <input type="hidden" name="campus" value="${esc(currentUser.campus)}">
                                    </div>
                                </div>
                                <div id="add-assignment" class="hidden mt-5 p-4 rounded-xl bg-blue-50 border border-blue-200 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-bold text-blue-800">👩‍🏫 Program Head Assignment</p>
                                        <span id="add-campus-badge" class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-200 text-blue-800 hidden"></span>
                                    </div>
                                    <p class="text-xs text-blue-600">This head will only see feedbacks and data from their assigned college &amp; program.</p>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">College <span class="text-red-500">*</span></label>
                                            <select name="college" id="add-college-sel"
                                                onchange="syncAddPrograms()"
                                                class="w-full px-3 py-2 border border-blue-200 rounded-xl text-sm bg-white disabled:opacity-40 disabled:cursor-not-allowed">
                                                <option value="">— Select campus first —</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Program <span class="text-red-500">*</span></label>
                                            <select name="program" id="add-program-sel"
                                                class="w-full px-3 py-2 border border-blue-200 rounded-xl text-sm bg-white disabled:opacity-40 disabled:cursor-not-allowed"
                                                disabled>
                                                <option value="">— Select college first —</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="add-duplicate-warning" class="hidden mt-4 flex items-start gap-3 px-4 py-3.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-800 text-sm font-semibold"><div class="w-7 h-7 shrink-0 rounded-full bg-amber-100 flex items-center justify-center mt-0.5"><svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg></div><span id="add-duplicate-warning-text" class="flex-1 leading-snug"></span></div><div id="add-error" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-4 py-2 rounded-lg"></div>
                                <div class="flex gap-3 mt-6">
                                    <button type="submit" id="add-submit-btn" class="flex-1 bg-emerald-700 hover:bg-emerald-800 text-white font-bold py-3 rounded-xl transition">Create User</button>
                                    <button type="button" onclick="closeModal('addUserModal')" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl transition">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- EDIT USER MODAL -->
                    <div id="editUserModal" class="modal-overlay">
                        <div class="modal-box">
                            <div class="flex justify-between items-center px-8 py-5 border-b border-slate-200">
                                <h2 class="text-xl font-bold text-slate-800">Edit User</h2>
                                <button onclick="closeModal('editUserModal')" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <form id="editUserForm" class="px-8 py-6" onsubmit="submitEditUser(event)">
                                <input type="hidden" name="id" id="edit-id">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Full Name <span class="text-red-500">*</span></label>
                                        <input name="full_name" id="edit-fullname" type="text" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm" required>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Username <span class="text-red-500">*</span></label>
                                        <input name="username" id="edit-username" type="text" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm" required>
                                    </div>
                                    <div class="space-y-1 md:col-span-2">
                                        <label class="text-sm font-semibold text-slate-700">New Password <span class="text-slate-400 font-normal text-xs">(leave blank to keep current)</span></label>
                                        <div class="relative">
                                            <input name="password" id="edit-pass" type="password" placeholder="Min. 8 characters" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm pr-10" minlength="8">
                                            <button type="button" onclick="togglePass('edit-pass')" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Role <span class="text-red-500">*</span></label>
                                        <select name="role" id="edit-role" onchange="toggleAssignment('edit')" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-white" required>
                                            <option value="Admin">👨‍💼 Admin</option>
                                            <option value="Program Head">👩‍🏫 Program Head</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Email</label>
                                        <input name="email" id="edit-email" type="email" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-sm font-semibold text-slate-700">Campus <span class="text-red-500">*</span></label>
                                        <select name="campus" id="edit-campus" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100" required disabled>
                                            <option value="${esc(currentUser.campus)}" selected>${esc(currentUser.campus)}</option>
                                        </select>
                                        <input type="hidden" name="campus" value="${esc(currentUser.campus)}">
                                    </div>
                                </div>
                                <div id="edit-assignment" class="hidden mt-5 p-4 rounded-xl bg-blue-50 border border-blue-200 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-bold text-blue-800">👩‍🏫 Program Head Assignment</p>
                                        <span id="edit-campus-badge" class="text-xs font-bold px-2.5 py-1 rounded-full bg-blue-200 text-blue-800 hidden"></span>
                                    </div>
                                    <p class="text-xs text-blue-600">This head will only see feedbacks and data from their assigned college &amp; program.</p>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">College <span class="text-red-500">*</span></label>
                                            <select name="college" id="edit-college"
                                                onchange="syncEditPrograms()"
                                                class="w-full px-3 py-2 border border-blue-200 rounded-xl text-sm bg-white disabled:opacity-40 disabled:cursor-not-allowed">
                                                <option value="">— Select campus first —</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Program <span class="text-red-500">*</span></label>
                                            <select name="program" id="edit-program"
                                                class="w-full px-3 py-2 border border-blue-200 rounded-xl text-sm bg-white disabled:opacity-40 disabled:cursor-not-allowed"
                                                disabled>
                                                <option value="">— Select college first —</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="edit-error" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-4 py-2 rounded-lg"></div>
                                <div class="flex gap-3 mt-6">
                                    <button type="submit" id="edit-submit-btn" class="flex-1 bg-emerald-700 hover:bg-emerald-800 text-white font-bold py-3 rounded-xl transition">Save Changes</button>
                                    <button type="button" onclick="closeModal('editUserModal')" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl transition">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- DELETE CONFIRM MODAL -->
                    <div id="deleteUserModal" class="modal-overlay">
                        <div class="modal-box" style="max-width:420px">
                            <div class="p-8 text-center">
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-slate-800 mb-2">Delete User</h3>
                                <p class="text-slate-500 text-sm mb-6">Are you sure you want to delete <strong id="delete-username-display"></strong>? This cannot be undone.</p>
                                <div class="flex gap-3">
                                    <button onclick="closeModal('deleteUserModal')" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition">Cancel</button>
                                    <button id="confirm-delete-btn" onclick="confirmDelete()" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STATS ROW — stat-card-small, same as schedule-mgmt -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="statsRow"></div>

                    <!-- TABLE CARD: toolbar + campus tabs + data buttons + table (mirrors schedule-mgmt white card) -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <!-- Filter bar -->
                        <div class="p-4 flex flex-col md:flex-row gap-3 items-center border-b border-slate-100">
                            <div class="relative flex-1 w-full">
                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input id="searchInput" type="text" placeholder="Search by name, username, email…"
                                       class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-slate-300"
                                       oninput="filterTable()">
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                                <select id="roleFilter" onchange="filterTable()" class="filter-select">
                                    <option value="">All Roles</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Program Head">Program Head</option>
                                </select>
                                <button onclick="openModal('addUserModal')"
                                        class="flex items-center gap-2 bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 shadow-sm transition whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5"/></svg>
                                    Add User
                                </button>
                            </div>
                        </div>
                        <!-- Campus Tab Strip -->
                        <div id="usersCampusTabStrip" class="border-b border-slate-100">${renderCampusTabs('users', 'usersTabFilter()', allUsers)}</div>
                        <div class="overflow-x-auto">
                            <table class="w-full" id="usersTable">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">#</th>
                                        <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Full Name</th>
                                        <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Username</th>
                                        <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Role</th>
                                        <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Email</th>
                                        <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Campus</th>
                                        <th class="px-5 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody" class="divide-y divide-slate-100 text-sm">
                                    <tr><td colspan="7" class="py-16 text-center text-slate-400">Loading users…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 text-xs text-slate-500" id="tableFooter"></div>
                    </div>
                </div>`;
            }

            // ── LOGS ───────────────────────────────────────────────────────────
            if (currentView === 'logs') {
                return `<div class="fade-in space-y-5 max-w-7xl mx-auto" id="logsViewRoot">
                    <!-- Stats row — stat-card-small, same as schedule-mgmt -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="logsStatsRow"></div>
                    <!-- White card: toolbar + log entries (mirrors schedule-mgmt white card) -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <!-- Filter bar -->
                        <div class="p-4 flex flex-col md:flex-row gap-3 items-center border-b border-slate-100">
                            <div class="relative flex-1 w-full">
                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <input id="logsSearch" type="text" placeholder="Search by user, action, or details…"
                                       class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-slate-300"
                                       oninput="renderLogsView()">
                            </div>
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                                <select id="logsRoleFilter" onchange="renderLogsView()" class="filter-select">
                                    <option value="">All Roles</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Program Head">Program Head</option>
                                </select>
                                <select id="logsCampusFilter" onchange="renderLogsView()" class="filter-select">
                                    <option value="">All Campuses</option>
                                    ${CAMPUSES.map(c => `<option value="${c}">${c}</option>`).join('')}
                                </select>
                                <select id="logsActionFilter" onchange="renderLogsView()" class="filter-select">
                                    <option value="">All Actions</option>
                                    <option value="Schedule">Schedules</option>
                                    <option value="User">Users</option>
                                    <option value="Room">Rooms</option>
                                    <option value="Import">Imports</option>
                                </select>
                                <button onclick="if(confirm('Clear all activity logs? This cannot be undone.')) { clearAllLogs(); renderLogsView(); showToast('Logs cleared'); }"
                                    class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Clear Logs
                                </button>
                            </div>
                        </div>
                        <!-- Record count bar -->
                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recent Activity</p>
                            <span id="logsCount" class="text-xs text-slate-400 font-medium italic"></span>
                        </div>
                        <!-- Log entries -->
                        <div id="logsTableBody" class="divide-y divide-slate-100"></div>
                    </div>
                </div>`;
            }
            if (currentView === 'calendar') {
                return `
                <div class="fade-in space-y-6">
                    <div id="calendar-container">${renderCalendar()}</div>
                </div>`;
            }

            // ── COURSES ────────────────────────────────────────────────────────
            if (currentView === 'courses') {
                const courses = allData.filter(d => d.type === 'course');
                return `
                <div class="fade-in space-y-6">
                    <!-- Course Detail Side Panel Overlay -->
                    <div id="coursePanelOverlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.4);backdrop-filter:blur(2px);z-index:998;" onclick="closeCoursePanel()"></div>
                    <!-- Course Detail Slide-in Side Panel -->
                    <div id="courseDetailSidePanel" style="position:fixed;top:0;right:-460px;height:100vh;width:440px;background:white;z-index:999;box-shadow:-8px 0 40px rgba(0,0,0,0.14);display:flex;flex-direction:column;transition:right 0.35s cubic-bezier(0.4,0,0.2,1);">
                        <!-- Panel Header -->
                        <div style="padding:24px 24px 0;border-bottom:1px solid #f1f5f9;flex-shrink:0;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div id="coursePanelIcon" style="width:44px;height:44px;border-radius:10px;background:#ecfdf5;color:#047857;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:800;letter-spacing:-0.5px;flex-shrink:0;"></div>
                                    <div>
                                        <h2 style="font-size:0.95rem;font-weight:700;color:#0f172a;line-height:1.3;" id="coursePanelTitle"></h2>
                                        <p style="font-size:0.72rem;color:#64748b;margin-top:2px;" id="coursePanelSubtitle"></p>
                                    </div>
                                </div>
                                <button onclick="closeCoursePanel()" style="width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;transition:all 0.15s;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='white';this.style.color='#64748b'">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <!-- Stats bar -->
                            <div id="coursePanelStats" style="display:flex;gap:16px;padding-bottom:16px;"></div>
                        </div>
                        <!-- Panel Body -->
                        <div style="flex:1;overflow-y:auto;padding:20px 24px;" id="coursePanelBody"></div>
                        <!-- Panel Footer -->
                        <div style="padding:16px 24px;border-top:1px solid #f1f5f9;flex-shrink:0;">
                            <button onclick="closeCoursePanel()" style="width:100%;padding:10px;border-radius:8px;background:#f1f5f9;color:#475569;font-size:0.82rem;font-weight:600;border:none;cursor:pointer;transition:background 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Close</button>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative flex-1 min-w-[200px]">
                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg>
                                </span>
                                <input id="coursesSearch" type="text" oninput="coursesFilter()" placeholder="Search courses..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500">
                            </div>
                            ${campusFilterSelect('coursesCampus', "campusFilters['courses']=this.value;coursesFilter()")}
                            <button onclick="coursesClearFilters()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800">Clear</button>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-4 flex-wrap gap-2">
                            ${renderDataButtons('courses', courses.length)}
                            <div class="flex items-center gap-2">
                                <button onclick="openYearLevelManager()" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-indigo-500 hover:bg-indigo-600 rounded-lg transition shadow-sm whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Year Levels
                                </button>
                                <button onclick="openAddCourseModal()" class="bg-emerald-600 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-emerald-700 transition shadow-sm">+ Add Course</button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <!-- Campus Tab Strip -->
                        <div class="px-5 py-3 border-b border-slate-100">${renderCampusTabs('courses', 'coursesFilter()', courses)}</div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr class="text-[11px] font-bold text-slate-500 uppercase tracking-widest">
                                        <th class="px-6 py-4">Course</th>
                                        <th class="px-6 py-4">Programs</th>
                                        <th class="px-6 py-4">Year Level</th>
                                        <th class="px-6 py-4">Semester</th>
                                        <th class="px-6 py-4">Campus</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                    </thead>
                    <tbody id="coursesBody" class="divide-y divide-slate-100">
                        ${(() => {
                            // Group courses by course_code ONLY (one row per code, all campuses merged)
                            const courseGroups = {};
                            courses.forEach(c => {
                                const key = (c.course_code || '').toLowerCase();
                                if (!courseGroups[key]) {
                                    courseGroups[key] = { ...c, programs: [], campuses: [], colleges: [], programCollegeMap: {}, progDetailsMap: {}, _ids: [], _normProgs: [] };
                                }
                                // Collect all IDs
                                courseGroups[key]._ids.push(c.id);

                                // Collect unique programs — normalize to avoid duplicates like "BSPSYCH" vs "BS PSYCH"
                                const prog = (c.program || '').trim();
                                const normProg = prog.replace(/\s+/g, '').toUpperCase();
                                if (normProg && !courseGroups[key]._normProgs.includes(normProg)) {
                                    courseGroups[key]._normProgs.push(normProg);
                                    courseGroups[key].programs.push(prog);
                                }

                                // Map program → college
                                const col = (c.college || '').trim();
                                if (normProg && col) courseGroups[key].programCollegeMap[normProg] = col;

                                // Map program → full details (year_level, semester, college, campus)
                                // Use normProg as key so lookups are consistent
                                if (normProg) {
                                    courseGroups[key].progDetailsMap[normProg] = {
                                        year_level: (c.year_level || '').trim(),
                                        semester:   (c.semester   || '').trim(),
                                        college:    col,
                                        campus:     (c.campus     || '').trim(),
                                    };
                                }

                                // Collect unique colleges
                                if (col && !courseGroups[key].colleges.includes(col)) {
                                    courseGroups[key].colleges.push(col);
                                }

                                // Collect unique campuses
                                const camp = (c.campus || '').trim();
                                if (camp && !courseGroups[key].campuses.includes(camp)) {
                                    courseGroups[key].campuses.push(camp);
                                }

                                // Keep the best course_name (not equal to code)
                                const currentName = (courseGroups[key].course_name || '').trim();
                                const newName = (c.course_name || '').trim();
                                const code = (c.course_code || '').toLowerCase();
                                if (newName && newName.toLowerCase() !== code && currentName.toLowerCase() === code) {
                                    courseGroups[key].course_name = newName;
                                }
                            });

                            const groupedCourses = Object.values(courseGroups);

                            // Enrich programs from schedule records that share the same course_code or course_id
                            groupedCourses.forEach(g => {
                                const code = (g.course_code || '').trim().toLowerCase();
                                const gIds = new Set((g._ids || []).map(id => String(id)));
                                allData
                                    .filter(d => {
                                        if (d.type !== 'schedule') return false;
                                        // Match by course_code directly on schedule
                                        if (code && (d.course_code || '').trim().toLowerCase() === code) return true;
                                        // Match by course_id being one of the grouped course IDs
                                        if (d.course_id && gIds.has(String(d.course_id))) return true;
                                        return false;
                                    })
                                    .forEach(s => {
                                        // Program directly on schedule
                                        const prog = (s.program || s.college_program || '').trim();
                                        const normProg = prog.replace(/\s+/g, '').toUpperCase();
                                        if (normProg && !g._normProgs.includes(normProg)) {
                                            g._normProgs.push(normProg);
                                            g.programs.push(prog);
                                        }
                                        // Program from the linked course record
                                        if (s.course_id) {
                                            const cr = allData.find(d => d.type === 'course' && String(d.id) === String(s.course_id));
                                            if (cr) {
                                                const cp = (cr.program || '').trim();
                                                const normCp = cp.replace(/\s+/g, '').toUpperCase();
                                                if (normCp && !g._normProgs.includes(normCp)) {
                                                    g._normProgs.push(normCp);
                                                    g.programs.push(cp);
                                                }
                                            }
                                        }
                                    });
                            });

                            if (groupedCourses.length === 0) {
                                return `<tr><td colspan="6" class="py-20 text-center text-slate-400">No courses found.</td></tr>`;
                            }

                            return groupedCourses.map(c => {
                                const safeId = dbId(String(c.id));
                                const campus = c.campus || '';
                                const courseName = (c.course_name || '').trim();
                                const displayName = (courseName && courseName.toLowerCase() !== (c.course_code||'').toLowerCase())
                                    ? courseName : '';
                                const MAX_VISIBLE = 4;
        const visibleProgs = c.programs.slice(0, MAX_VISIBLE);
        const hiddenCount = c.programs.length - MAX_VISIBLE;

        const programsEncoded     = btoa(unescape(encodeURIComponent(JSON.stringify(c.programs))));
        const campusesEncoded     = btoa(unescape(encodeURIComponent(JSON.stringify(c.campuses || []))));
        const collegesEncoded     = btoa(unescape(encodeURIComponent(JSON.stringify(c.colleges || []))));
        const progColMapEncoded   = btoa(unescape(encodeURIComponent(JSON.stringify(c.programCollegeMap || {}))));
        const progDetailsEncoded  = btoa(unescape(encodeURIComponent(JSON.stringify(c.progDetailsMap || {}))));

        const programCell = c.programs.length === 0
            ? '<span class="text-slate-300 text-xs">—</span>'
            : `<button onclick="showCoursePanel('${safeId}')" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100 rounded-full hover:bg-blue-100 transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                ${c.programs.length} program${c.programs.length !== 1 ? 's' : ''}
            </button>`;

                                return `
<tr class="hover:bg-slate-50/50 transition"
    data-campus-row="${(c.campuses||[]).join('|')}"
    data-page-view="courses"
    data-filtered="false"
    data-campuses="${(c.campuses||[]).join('|')}"
    data-search="${(c.course_code||'').toLowerCase()} ${(c.course_name||'').toLowerCase()} ${(c.campuses||[]).join(' ').toLowerCase()}"
    data-course-id="${safeId}"
    data-course-code="${esc(c.course_code || '')}"
    data-course-name="${esc(c.course_name || '')}"
    data-course-year="${esc(c.year_level || '')}"
    data-course-semester="${esc(c.semester || '')}"
    data-course-programs="${programsEncoded}"
    data-course-campuses="${campusesEncoded}"
    data-course-colleges="${collegesEncoded}"
    data-course-prog-col-map="${progColMapEncoded}"
    data-course-prog-details="${progDetailsEncoded}">

    <td class="px-6 py-4">
        <div class="flex flex-col">
            <span class="text-sm font-bold text-slate-800">${esc(c.course_code)}</span>
            ${displayName
                ? `<span class="text-xs text-slate-400 mt-0.5">${esc(displayName)}</span>`
                : `<span class="text-xs text-slate-300 mt-0.5">—</span>`}
        </div>
    </td>

    <td class="px-6 py-4">
        <div class="flex flex-wrap gap-1">${programCell}</div>
    </td>

    <td class="px-6 py-4">
        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full
            ${c.year_level === '1st Year' ? 'bg-purple-50 text-purple-700 border border-purple-100' :
              c.year_level === '2nd Year' ? 'bg-blue-50 text-blue-700 border border-blue-100' :
              c.year_level === '3rd Year' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' :
              c.year_level === '4th Year' ? 'bg-orange-50 text-orange-700 border border-orange-100' :
              'bg-slate-50 text-slate-600 border border-slate-200'}">
            ${esc(c.year_level || '—')}
        </span>
    </td>

    <td class="px-6 py-4 text-sm text-slate-500">${esc(c.semester || '—')}</td>

    <td class="px-6 py-4" data-campus-cell>
        ${c.campuses && c.campuses.length > 0
            ? `<span class="campus-badge">${c.campuses[0]}</span>`
            : '<span class="text-slate-400 text-xs">—</span>'}
    </td>

    <td class="px-6 py-4 text-right">
        <div class="flex justify-end gap-2">
            <button onclick="editCourse('${safeId}')" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2"/></svg>
            </button>
            <button onclick="deleteCourse('${safeId}')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2"/></svg>
            </button>
        </div>
    </td>
</tr>`;
                            }).join('');
                        })()}
                    </tbody>
                </table>
            </div>
            <div id="pager-courses" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2"></div>
        </div>
    </div>`;
}

            // ── COLLEGES ───────────────────────────────────────────────────────
            if (currentView === 'colleges') {
                const colleges = allData.filter(d => d.type === 'college');
                return `
                <div class="fade-in space-y-6">
                    <!-- Programs Side Panel Overlay -->
                    <div id="programsPanelOverlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.4);backdrop-filter:blur(2px);z-index:998;" onclick="closeProgramsPanel()"></div>
                    <!-- Programs Slide-in Side Panel -->
                    <div id="programsSidePanel" style="position:fixed;top:0;right:-460px;height:100vh;width:440px;background:white;z-index:999;box-shadow:-8px 0 40px rgba(0,0,0,0.14);display:flex;flex-direction:column;transition:right 0.35s cubic-bezier(0.4,0,0.2,1);">
                        <!-- Panel Header -->
                        <div style="padding:24px 24px 0;border-bottom:1px solid #f1f5f9;flex-shrink:0;">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div id="programsPanelIcon" style="width:44px;height:44px;border-radius:10px;background:#ecfdf5;color:#047857;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:800;letter-spacing:-0.5px;flex-shrink:0;"></div>
                                    <div>
                                        <h2 style="font-size:0.95rem;font-weight:700;color:#0f172a;line-height:1.3;" id="programsPanelTitle"></h2>
                                        <p style="font-size:0.72rem;color:#64748b;margin-top:2px;" id="programsPanelSubtitle"></p>
                                    </div>
                                </div>
                                <button onclick="closeProgramsPanel()" style="width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;transition:all 0.15s;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='white';this.style.color='#64748b'">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <!-- Stats bar -->
                            <div id="programsPanelStats" style="display:flex;gap:16px;padding-bottom:16px;"></div>
                        </div>
                        <!-- Panel Body -->
                        <div style="flex:1;overflow-y:auto;padding:20px 24px;" id="programsPanelBody"></div>
                        <!-- Panel Footer -->
                        <div style="padding:16px 24px;border-top:1px solid #f1f5f9;flex-shrink:0;">
                            <button onclick="closeProgramsPanel()" style="width:100%;padding:10px;border-radius:8px;background:#f1f5f9;color:#475569;font-size:0.82rem;font-weight:600;border:none;cursor:pointer;transition:background 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Close</button>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4 text-left">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative flex-1 min-w-[200px]">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg></span>
                                <input id="collegesSearch" type="text" oninput="collegesFilter()" placeholder="Search colleges..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            </div>
                            ${campusFilterSelect('collegesCampus', "campusFilters['colleges']=this.value;collegesFilter()")}
                            <button onclick="collegesClearFilters()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800">Clear</button>
                        </div>
                        <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-100 items-center">
                            ${renderDataButtons('colleges', colleges.length)}
                           <button onclick="openAddCollegeModal()" class="btn-action-large bg-emerald-600 text-white hover:bg-emerald-700 ml-auto"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add College</button>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <!-- Campus Tab Strip -->
                        <div class="px-5 py-3 border-b border-slate-100">${renderCampusTabs('colleges', 'collegesFilter()', colleges)}</div>
                        <div class="overflow-x-auto">
                            <table class="w-full table-fixed">
                                <colgroup>
                                    <col style="width:38%">
                                    <col style="width:10%">
                                    <col style="width:26%">
                                    <col style="width:14%">
                                    <col style="width:12%">
                                </colgroup>
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">College / Department</th>
                                        <th class="px-3 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Code</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Programs</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Campus</th>
                                        <th class="px-6 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="collegesBody" class="divide-y divide-slate-100">
                                    ${colleges.length > 0 ? colleges.map(college => {
    const safeId = dbId(String(college.id));
    // For campus admin: colleges with no campus value belong to the admin's campus
    const campus = college.campus || currentUser.campus || '';
    const programs = Array.isArray(college.programs) && college.programs.length > 0
        ? college.programs
        : (college.code ? [college.code] : []);
    const programBadges = programs.length > 0
        ? programs.map(p => {
            const eqIndex = p.indexOf('=');
            const acronym  = eqIndex !== -1 ? p.slice(0, eqIndex).trim() : p.trim();
            const fullName = eqIndex !== -1 ? p.slice(eqIndex + 1).trim() : '';
            return `<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100 rounded-full"
                title="${fullName ? esc(fullName) : ''}"
                style="cursor:${fullName ? 'help' : 'default'}">
                <span class="font-bold">${esc(acronym)}</span>
                ${fullName ? `<span class="text-blue-400 font-normal">— ${esc(fullName)}</span>` : ''}
            </span>`;
        }).join(' ')
        : '<span class="text-slate-400 text-xs">No programs</span>';
    return `
    <tr class="hover:bg-slate-50/50 transition"
        data-campus-row="${campus}"
        data-page-view="colleges"
        data-filtered="false"
        data-search="${(college.name||'').toLowerCase()} ${campus.toLowerCase()}"
        data-college-id="${safeId}"
        data-college-name="${esc(college.name || college.code || '')}"
        data-college-campus="${esc(campus)}"
        data-college-programs="${btoa(unescape(encodeURIComponent(JSON.stringify(programs))))}">
        <td class="px-6 py-3 text-sm font-semibold text-slate-900">${esc(college.name || college.code || '—')}</td>
        <td class="px-3 py-3"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">${esc(college.code || '—')}</span></td>
        <td class="px-6 py-3">${programs.length > 0
            ? `<button onclick="showProgramsPanel('${safeId}')" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100 rounded-full hover:bg-blue-100 transition"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>${programs.length} program${programs.length !== 1 ? 's' : ''}</button>`
            : '<span class="text-slate-400 text-xs italic">No programs</span>'}</td>
        <td class="px-6 py-3">${campus ? `<span class="campus-badge">${campus}</span>` : '<span class="text-slate-400 text-xs">—</span>'}</td>
        <td class="px-6 py-3">
            <div class="flex items-center justify-end gap-2">
                <button onclick="editCollege('${safeId}')" title="Edit" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button onclick="deleteCollege('${safeId}')" title="Delete" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </td>
    </tr>`;
}).join('') : `<tr><td colspan="5" class="px-6 py-16 text-center text-sm text-slate-500">No colleges found. Click "Add College" to create your first college.</td></tr>`}
                                </tbody>
                            </table>
                        </div>
                        <div id="pager-colleges" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2"></div>
                    </div>
                </div>`;
            }

            // ── PROCTORS ───────────────────────────────────────────────────────
            if (currentView === 'proctors') {
                const proctors = allData.filter(d => d.type === 'proctor');
                return `
                <div class="fade-in space-y-6">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4 text-left">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="relative flex-1 min-w-[200px]">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg></span>
                                <input id="proctorsSearch" type="text" oninput="proctorsFilter()" placeholder="Search proctors..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            </div>
                            ${campusFilterSelect('proctorsCampus', "campusFilters['proctors']=this.value;proctorsFilter()")}
                            <button onclick="proctorsClearFilters()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800">Clear</button>
                        </div>
                        <div class="flex flex-wrap gap-3 pt-4 border-t border-slate-100 items-center">
                            ${renderDataButtons('proctors', proctors.length)}
                            <button onclick="openAddProctorModal()" class="btn-action-large bg-emerald-600 text-white hover:bg-emerald-700 ml-auto"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Proctor</button>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <!-- Campus Tab Strip -->
                        <div class="px-5 py-3 border-b border-slate-100">${renderCampusTabs('proctors', 'proctorsFilter()', proctors)}</div>
                        <div class="overflow-x-auto">
                            <table class="w-full table-fixed">
                                <colgroup>
                                    <col style="width:20%">
                                    <col style="width:14%">
                                    <col style="width:27%">
                                    <col style="width:16%">
                                    <col style="width:15%">
                                    <col style="width:8%">
                                </colgroup>
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Name</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">College/Program</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Email</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Phone</th>
                                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Campus</th>
                                        <th class="px-6 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="proctorsBody" class="divide-y divide-slate-100">
                                    ${proctors.length > 0 ? proctors.map(proctor => {
                                        const safeId = dbId(String(proctor.id));
                                        const campus = proctor.campus || '';
                                        return `
                                        <tr class="hover:bg-slate-50 transition" data-campus-row="${campus}" data-page-view="proctors" data-filtered="false" data-search="${(proctor.name||'').toLowerCase()} ${campus.toLowerCase()}">
                                            <td class="px-6 py-3 text-sm font-medium text-slate-900 truncate">${proctor.name || 'N/A'}</td>
                                            <td class="px-6 py-3 text-sm text-slate-700 truncate">${proctor.collegeProgram || proctor.college_program || 'N/A'}</td>
                                            <td class="px-6 py-3 text-sm text-slate-700 truncate">${proctor.email || 'N/A'}</td>
                                            <td class="px-6 py-3 text-sm text-slate-700">${proctor.phone || 'N/A'}</td>
                                            <td class="px-6 py-3">${campus ? `<span class="campus-badge">${campus}</span>` : '<span class="text-slate-400 text-xs">—</span>'}</td>
                                            <td class="px-6 py-3"><div class="flex items-center justify-end gap-2">
                                                <button onclick="editProctor('${safeId}')" title="Edit" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button onclick="deleteProctor('${safeId}')" title="Delete" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div></td>
                                        </tr>`;
                                    }).join('') : `<tr><td colspan="6" class="px-6 py-16 text-center"><p class="text-sm text-slate-500">No proctors found.</p></td></tr>`}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>`;
            }

            if (currentView === 'semester-archive') {
                return renderSemesterArchiveView();
            }

            return `<div class="p-20 text-center text-slate-400">View under construction</div>`;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 11. ANALYTICS CARDS HELPER
        // ─────────────────────────────────────────────────────────────────────────
        function computeAnalyticsExtras(schedules, campus) {
            // ── Efficiency: % of available (unlocked) rooms that have ≥1 booking ──
            const availableRooms = allData.filter(d =>
                d.type === 'room' && !d.locked && (!campus || (d.campus || '') === campus)
            );
            const bookedRoomIds = new Set(
                schedules.map(s => dbId(String(s.room_id || s.room || ''))).filter(Boolean)
            );
            const usedRooms = availableRooms.filter(r => bookedRoomIds.has(dbId(String(r.id)))).length;
            const efficiencyPct = availableRooms.length > 0
                ? Math.round((usedRooms / availableRooms.length) * 100) : 0;
            const efficiencyLabel = `${usedRooms} of ${availableRooms.length} rooms used`;

            // ── Auto-Resolved: % of schedules that have NO active conflicts ───────
            // A schedule is "conflicted" if its room is blocked/missing OR it has a
            // room double-booking overlap with another schedule on the same date.
            // Build a stable room map from allData so lookups are consistent regardless
            // of array order or campus filtering applied at a higher level.
            const _roomMap = {};
            allData.forEach(d => { if (d.type === 'room') _roomMap[dbId(String(d.id))] = d; });

            let conflictedCount = 0;
            schedules.forEach(s => {
                // Online exams don't need a room — never count them as conflicted
                if (s.is_online) return;
                const roomId = dbId(String(s.room_id || s.room || ''));
                // No room assigned yet — not a conflict, just unscheduled; skip
                if (!roomId) return;
                const room = _roomMap[roomId];
                // Room was assigned but is now missing from data or is locked — that's a real conflict
                if (!room || room.locked) { conflictedCount++; return; }
                // Check for double-booking: same room, same date, overlapping time
                if (s.exam_date && s.time_slot) {
                    const hasClash = schedules.some(other =>
                        dbId(String(other.id)) !== dbId(String(s.id)) &&
                        dbId(String(other.room_id || other.room || '')) === roomId &&
                        (other.exam_date || other.date) === (s.exam_date || s.date) &&
                        _arOverlap(other.time_slot, s.time_slot)
                    );
                    if (hasClash) conflictedCount++;
                }
            });
            // ── Also count pending fixable import rows (skipped at import, never saved) ──
            // Filter by current campus so super-admin imports don't pollute campus admin stats
            const _myCampus = (currentUser.campus || '').trim();
            const pendingImportConflicts = (window._importResolvableRows || []).filter(r =>
                r.canAutoResolve && r.importRow &&
                (!_myCampus || !r.importRow.campus || (r.importRow.campus || '').trim() === _myCampus)
            ).length;
            conflictedCount += pendingImportConflicts;

            const totalDenominator  = schedules.length + pendingImportConflicts;
            const liveConflicts     = conflictedCount - pendingImportConflicts;
            const cleanCount        = schedules.length - liveConflicts;
            // If there are no schedules at all, show 100% (nothing to conflict).
            // If there are schedules, show the true ratio of conflict-free ones.
            const autoResolvedPct   = totalDenominator > 0
                ? Math.round((cleanCount / totalDenominator) * 100)
                : 100;
            const autoResolvedLabel = conflictedCount === 0
                ? 'No conflicts detected'
                : `${conflictedCount} conflict${conflictedCount > 1 ? 's' : ''} remaining`;

            return { efficiencyPct, efficiencyLabel, autoResolvedPct, autoResolvedLabel, conflictedCount };
        }

        function _computeRoomsBuildings(schedules, campus) {
            const bookedRoomIds  = new Set(schedules.map(s => dbId(String(s.room_id || s.room || ''))).filter(Boolean));
            const totalRooms     = allData.filter(d => d.type === 'room' && (!campus || (d.campus||'') === campus)).length;
            const totalRoomsUsed = allData.filter(d => d.type === 'room' && !d.locked && (!campus || (d.campus||'') === campus) && bookedRoomIds.has(dbId(String(d.id)))).length;
            const roomsUsedPct   = totalRooms > 0 ? Math.round((totalRoomsUsed / totalRooms) * 100) : 0;
            const roomsUsedLabel = `${totalRoomsUsed} of ${totalRooms} rooms`;

            // Per-building breakdown using original casing
            const buildingDetails = {};
            allData.filter(d => d.type === 'room' && d.building && (!campus || (d.campus||'') === campus)).forEach(d => {
                const key = (d.building || '').trim();
                if (!key) return;
                if (!buildingDetails[key]) buildingDetails[key] = { total: 0, used: 0 };
                buildingDetails[key].total++;
                if (!d.locked && bookedRoomIds.has(dbId(String(d.id)))) buildingDetails[key].used++;
            });

            const allBuildings  = Object.keys(buildingDetails);
            const usedBuildings = allBuildings.filter(b => buildingDetails[b].used > 0);
            const buildingsUsedPct   = allBuildings.length > 0 ? Math.round((usedBuildings.length / allBuildings.length) * 100) : 0;
            const buildingsUsedLabel = `${usedBuildings.length} of ${allBuildings.length} buildings`;

            return { roomsUsedPct, roomsUsedLabel, buildingsUsedPct, buildingsUsedLabel, buildingDetails, allBuildings, usedBuildings };
        }

        function renderAnalyticsCards(schedules, scheduledPercent, approvedSchedules, pendingSchedules, totalSchedules, campus) {
            const { efficiencyPct, efficiencyLabel, autoResolvedPct, autoResolvedLabel, conflictedCount } = computeAnalyticsExtras(schedules, campus);
            const { roomsUsedPct, roomsUsedLabel, buildingsUsedPct, buildingsUsedLabel, buildingDetails, allBuildings, usedBuildings } = _computeRoomsBuildings(schedules, campus);
            const effColor = efficiencyPct >= 70 ? 'text-emerald-600' : efficiencyPct >= 40 ? 'text-blue-600' : 'text-orange-500';
            const effBar   = efficiencyPct >= 70 ? 'bg-emerald-500'   : efficiencyPct >= 40 ? 'bg-blue-500'   : 'bg-orange-400';
            const arColor  = conflictedCount === 0 ? 'text-emerald-600' : autoResolvedPct >= 80 ? 'text-purple-600' : 'text-red-500';
            const arBar    = conflictedCount === 0 ? 'bg-emerald-500'   : autoResolvedPct >= 80 ? 'bg-purple-500'   : 'bg-red-400';
            const ruColor  = roomsUsedPct >= 70 ? 'text-teal-600'     : roomsUsedPct >= 40 ? 'text-cyan-600'    : 'text-orange-500';
            const ruBar    = roomsUsedPct >= 70 ? 'bg-teal-500'       : roomsUsedPct >= 40 ? 'bg-cyan-500'      : 'bg-orange-400';
            const buColor  = buildingsUsedPct >= 70 ? 'text-indigo-600' : buildingsUsedPct >= 40 ? 'text-violet-600' : buildingsUsedPct > 0 ? 'text-orange-500' : 'text-slate-400';

            // Per-building rows for the expanded card
            const buildingRows = allBuildings.sort().map(b => {
                const { total, used } = buildingDetails[b];
                const pct  = total > 0 ? Math.round((used / total) * 100) : 0;
                const bar  = pct >= 70 ? 'bg-indigo-500' : pct >= 40 ? 'bg-violet-400' : pct > 0 ? 'bg-orange-400' : 'bg-slate-300';
                const dot  = used > 0  ? 'bg-indigo-500' : 'bg-slate-300';
                return `<div class="flex items-center gap-2 min-w-0">
                    <span class="w-2 h-2 rounded-full shrink-0 ${dot}"></span>
                    <span class="text-[10px] text-slate-600 font-medium truncate flex-1" title="${b}">${b}</span>
                    <span class="text-[10px] text-slate-400 shrink-0">${used}/${total}</span>
                    <div class="w-12 h-1 bg-slate-100 rounded-full shrink-0"><div class="h-full ${bar} rounded-full" style="width:${pct}%"></div></div>
                </div>`;
            }).join('');

            return `
            <div class="analytics-card"><div class="flex justify-between items-start"><div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Schedules</p><h3 class="text-2xl font-bold text-slate-800">${totalSchedules}</h3><p class="text-[10px] text-slate-400 mt-1">${campus || 'All campuses'}</p></div><div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg></div></div><div class="w-full h-1.5 bg-slate-100 rounded-full mt-auto"><div class="h-full bg-emerald-600 rounded-full" style="width: ${totalSchedules > 0 ? '100%' : '0%'}"></div></div></div>
            <div class="analytics-card"><div class="flex justify-between items-start"><div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Scheduled</p><h3 class="text-2xl font-bold text-emerald-600">${approvedSchedules}</h3><p class="text-[10px] text-slate-400 mt-1">${scheduledPercent}% of total</p></div><div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2"/></svg></div></div><div class="w-full h-1.5 bg-slate-100 rounded-full mt-auto"><div class="h-full bg-emerald-600 rounded-full" style="width: ${scheduledPercent}%"></div></div></div>
            <div class="analytics-card"><div class="flex justify-between items-start"><div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Pending</p><h3 class="text-2xl font-bold text-orange-500">${pendingSchedules}</h3><p class="text-[10px] text-slate-400 mt-1">Awaiting approval</p></div><div class="p-2 bg-orange-50 text-orange-500 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"/></svg></div></div></div>
            <div class="analytics-card"><div class="flex justify-between items-start"><div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Efficiency</p><h3 class="text-2xl font-bold ${effColor}">${efficiencyPct}%</h3><p class="text-[10px] text-slate-400 mt-1">${efficiencyLabel}</p></div><div class="p-2 bg-blue-50 text-blue-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14" stroke-width="2"/></svg></div></div><div class="w-full h-1.5 bg-slate-100 rounded-full mt-auto"><div class="h-full ${effBar} rounded-full transition-all duration-500" style="width:${efficiencyPct}%"></div></div></div>
            <div class="analytics-card"><div class="flex justify-between items-start"><div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Auto-Resolved</p><h3 class="text-2xl font-bold ${arColor}">${autoResolvedPct}%</h3><p class="text-[10px] text-slate-400 mt-1">${autoResolvedLabel}</p></div><div class="p-2 bg-purple-50 text-purple-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg></div></div><div class="w-full h-1.5 bg-slate-100 rounded-full mt-auto"><div class="h-full ${arBar} rounded-full transition-all duration-500" style="width:${autoResolvedPct}%"></div></div></div>
            <div class="analytics-card"><div class="flex justify-between items-start"><div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Rooms Used</p><h3 class="text-2xl font-bold ${ruColor}">${roomsUsedPct}%</h3><p class="text-[10px] text-slate-400 mt-1">${roomsUsedLabel}</p></div><div class="p-2 bg-teal-50 text-teal-600 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div></div><div class="w-full h-1.5 bg-slate-100 rounded-full mt-auto"><div class="h-full ${ruBar} rounded-full transition-all duration-500" style="width:${roomsUsedPct}%"></div></div></div>
            <div class="analytics-card md:col-span-2 xl:col-span-1 cursor-pointer hover:shadow-md hover:border-indigo-200 transition-all group"
                 style="min-height:110px"
                 onclick="openBuildingsModal()"
                 title="Click to view all buildings">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Buildings Used</p>
                        <h3 class="text-2xl font-bold ${buColor}">${buildingsUsedPct}%</h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">${buildingsUsedLabel}</p>
                    </div>
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg shrink-0 group-hover:bg-indigo-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
                ${(() => { window._buildingDetails = buildingDetails; window._buildingCampus = campus || 'All Campuses'; return ''; })()}
                ${allBuildings.length > 0
                    ? `<div class="space-y-1 mt-1 overflow-y-auto max-h-[80px]">${buildingRows}</div>`
                    : `<p class="text-[10px] text-slate-300 mt-auto">No buildings assigned to rooms yet.</p>`
                }
                <div class="flex items-center justify-between mt-2 gap-2">
                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full"><div class="h-full ${buildingsUsedPct >= 70 ? 'bg-indigo-500' : buildingsUsedPct >= 40 ? 'bg-violet-400' : buildingsUsedPct > 0 ? 'bg-orange-400' : 'bg-slate-300'} rounded-full transition-all duration-500" style="width:${buildingsUsedPct}%"></div></div>
                    <span class="text-[9px] font-semibold text-indigo-400 group-hover:text-indigo-600 transition whitespace-nowrap">View all →</span>
                </div>
            </div>`;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 12. PER-VIEW FILTER FUNCTIONS (search + campus combined)
        // ─────────────────────────────────────────────────────────────────────────
        function genericFilter(bodyId, searchId, campusFilterKey) {
            const search  = (document.getElementById(searchId)?.value || '').toLowerCase();
            const campus  = campusFilters[campusFilterKey] || '';
            const rows    = document.querySelectorAll(`#${bodyId} [data-campus-row]`);
            rows.forEach(row => {
                const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
                const rowCampus = row.getAttribute('data-campus-row') || '';
                const show = (!search || rowSearch.includes(search)) && (!campus || rowCampus === campus);
                row.setAttribute('data-filtered', show ? 'false' : 'true');
            });
            pageState[campusFilterKey] = 1;
            applyPagination(campusFilterKey);
        }

        function coursesFilter() {
        const search = (document.getElementById('coursesSearch')?.value || '').toLowerCase();
        const campus = campusFilters['courses'] || '';
        const rows   = document.querySelectorAll('#coursesBody [data-campus-row]');
        let visible  = 0;
    rows.forEach(row => {
        const rowSearch   = (row.getAttribute('data-search') || '').toLowerCase();
        const rowCampuses = (row.getAttribute('data-campuses') || '').split('|').filter(Boolean);
        const show = (!search || rowSearch.includes(search))
                  && (!campus || rowCampuses.includes(campus));
        row.setAttribute('data-filtered', show ? 'false' : 'true');
        if (show) {
            visible++;
            const cell = row.querySelector('[data-campus-cell]');
            if (cell) {
                const displayCampus = campus || rowCampuses[0] || '';
                cell.innerHTML = displayCampus
                    ? `<span class="campus-badge">${displayCampus}</span>`
                    : '<span class="text-slate-400 text-xs">—</span>';
            }
        }
    });
    const badge = document.getElementById('rowCountBadge');
    if (badge) badge.textContent = visible === rows.length ? `${rows.length} records` : `${visible} of ${rows.length} records`;
    pageState['courses'] = 1;
    applyPagination('courses');
}
        function collegesFilter()     { genericFilter('collegesBody',   'collegesSearch', 'colleges'); }

        function showProgramsPanel(collegeId) {
            const row = document.querySelector(`tr[data-college-id="${collegeId}"]`);
            if (!row) return;
            const name   = row.dataset.collegeName   || '';
            const campus = row.dataset.collegeCampus || '';
            let programs = [];
            try { programs = JSON.parse(decodeURIComponent(escape(atob(row.dataset.collegePrograms || 'W10=')))); } catch(e) { programs = []; }

            // Set icon abbreviation (first letters of each word, max 4 chars)
            const iconText = name.split(' ').filter(w => /^[A-Z]/i.test(w)).map(w => w[0]).join('').substring(0,4).toUpperCase() || '?';
            document.getElementById('programsPanelIcon').textContent     = iconText;
            document.getElementById('programsPanelTitle').textContent    = name || 'Programs';
            document.getElementById('programsPanelSubtitle').textContent = (campus ? campus + ' Campus' : '') + (programs.length ? ' · ' + programs.length + ' program' + (programs.length !== 1 ? 's' : '') : '');

            // Stats bar
            document.getElementById('programsPanelStats').innerHTML = `
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;display:flex;flex-direction:column;gap:1px;">
                    <span style="font-size:1.1rem;font-weight:800;color:#047857;">${programs.length}</span>
                    <span style="font-size:0.68rem;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Programs</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;display:flex;flex-direction:column;gap:1px;">
                    <span style="font-size:1.1rem;font-weight:800;color:#3b82f6;">${campus || '—'}</span>
                    <span style="font-size:0.68rem;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Campus</span>
                </div>`;

            // Body
            const body = document.getElementById('programsPanelBody');
            if (programs.length === 0) {
                body.innerHTML = `<div style="text-align:center;padding:48px 0;color:#94a3b8;">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;opacity:0.4"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p style="font-size:0.85rem;">No programs listed.</p></div>`;
            } else {
                // Parse programs and detect groups by acronym
                const parsed = programs.map((p, i) => {
                    const eqIndex  = p.indexOf('=');
                    const acronym  = eqIndex !== -1 ? p.slice(0, eqIndex).trim() : p.trim();
                    const fullName = eqIndex !== -1 ? p.slice(eqIndex + 1).trim() : '';
                    return { acronym, fullName, index: i };
                });

                // Group by acronym (e.g. "SHS PLUS", "SHS REGULAR", "BSIT")
                const groupMap = {};
                for (const prog of parsed) {
                    const key = prog.acronym;
                    if (!groupMap[key]) groupMap[key] = [];
                    groupMap[key].push(prog);
                }
                const groups = Object.keys(groupMap);
                const hasMultipleGroups = groups.length > 1 && programs.length >= 5;

                const colors = [
                    ['#ecfdf5','#047857'],['#eff6ff','#3b82f6'],['#fdf4ff','#9333ea'],
                    ['#fff7ed','#ea580c'],['#fefce8','#ca8a04'],['#f0fdf4','#16a34a']
                ];

                function renderProgramList(list) {
                    return list.map((prog, i) => {
                        const [bg, fg] = colors[prog.index % colors.length];
                        return `<div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #f1f5f9;margin-bottom:8px;background:#fafafa;transition:all 0.15s;cursor:default;"
                            onmouseover="this.style.borderColor='#a7f3d0';this.style.background='#f0fdf4'"
                            onmouseout="this.style.borderColor='#f1f5f9';this.style.background='#fafafa'">
                            <div style="width:40px;height:40px;border-radius:9px;background:${bg};color:${fg};display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:800;letter-spacing:-0.5px;flex-shrink:0;">${prog.acronym.substring(0,4)}</div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:0.82rem;font-weight:600;color:#1e293b;">${prog.fullName || prog.acronym}</div>
                                <div style="font-size:0.7rem;color:#94a3b8;margin-top:1px;">${prog.acronym}</div>
                            </div>
                            <span style="font-size:0.65rem;font-weight:700;color:#94a3b8;background:#f1f5f9;padding:2px 7px;border-radius:99px;">#${prog.index+1}</span>
                        </div>`;
                    }).join('');
                }

                if (!hasMultipleGroups) {
                    // No tabs needed — render all
                    body.innerHTML = `<p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:12px;">All Programs</p>`
                        + renderProgramList(parsed);
                } else {
                    // Build tab bar + tab content
                    const tabColors = ['#047857','#3b82f6','#9333ea','#ea580c','#ca8a04','#16a34a','#0891b2','#db2777'];
                    const allTab = 'ALL';
                    const allGroups = [allTab, ...groups];

                    // Map each group acronym to the same color as its icon avatar
                    const groupColorMap = {};
                    groups.forEach((g, gi) => {
                        groupColorMap[g] = colors[groupMap[g][0].index % colors.length][1];
                    });

                    // Display label: if a group acronym matches the college code exactly, call it "[acronym] Regular"
                    const collegeCodeUpper = (name || '').toUpperCase().split(' ').map(w=>w[0]).join('').substring(0,4);
                    function groupLabel(g) {
                        if (g === allTab) return 'All';
                        // If there are other groups that START with this acronym (e.g. "SHS PLUS" alongside "SHS"), label it as "SHS Regular"
                        const hasLongerSibling = groups.some(other => other !== g && other.startsWith(g + ' '));
                        return hasLongerSibling ? g + ' Regular' : g;
                    }

                    function buildTabs(activeTab) {
                        return allGroups.map((g, gi) => {
                            const isActive = g === activeTab;
                            const count = g === allTab ? programs.length : groupMap[g].length;
                            const color = g === allTab ? '#475569' : groupColorMap[g];
                            const label = groupLabel(g);
                            // All tabs always show solid color; active tab gets full opacity, inactive gets lighter
                            const bgColor  = isActive ? color : color + '22';
                            const txtColor = isActive ? 'white' : color;
                            const bdColor  = isActive ? color : color + '55';
                            return `<button onclick="switchProgramTab('${g.replace(/'/g,"\\'")}', this)"
                                style="padding:6px 12px;border-radius:8px;border:1px solid ${bdColor};
                                background:${bgColor};color:${txtColor};
                                font-size:0.72rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all 0.15s;display:inline-flex;align-items:center;gap:5px;"
                                onmouseover="if(this.dataset.active!=='1'){this.style.background='${color}33';this.style.borderColor='${color}88';}"
                                onmouseout="if(this.dataset.active!=='1'){this.style.background='${bgColor}';this.style.borderColor='${bdColor}';}"
                                data-active="${isActive ? '1' : '0'}" data-tab="${g}" data-color="${color}">
                                ${label}
                                <span style="background:${isActive ? 'rgba(255,255,255,0.25)' : color + '33'};color:${isActive ? 'white' : color};padding:1px 6px;border-radius:99px;font-size:0.65rem;font-weight:700;">${count}</span>
                            </button>`;
                        }).join('');
                    }

                    // Store groupMap on window for tab switching
                    window._progGroupMap = groupMap;
                    window._progAllParsed = parsed;
                    window._progRenderList = renderProgramList;

                    window.switchProgramTab = function(tab, btn) {
                        const list = tab === 'ALL' ? window._progAllParsed : (window._progGroupMap[tab] || []);
                        document.getElementById('programsTabContent').innerHTML = window._progRenderList(list);
                        const allBtns = btn.parentElement.querySelectorAll('button[data-tab]');
                        allBtns.forEach(b => {
                            const isNowActive = b.dataset.tab === tab;
                            const tc = b.dataset.color || '#475569';
                            b.style.background    = isNowActive ? tc : tc + '22';
                            b.style.borderColor   = isNowActive ? tc : tc + '55';
                            b.style.color         = isNowActive ? 'white' : tc;
                            b.dataset.active      = isNowActive ? '1' : '0';
                            const badge = b.querySelector('span');
                            if (badge) {
                                badge.style.background = isNowActive ? 'rgba(255,255,255,0.25)' : tc + '33';
                                badge.style.color      = isNowActive ? 'white' : tc;
                            }
                        });
                    };

                    body.innerHTML = `
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #f1f5f9;">
                            ${buildTabs(allTab)}
                        </div>
                        <div id="programsTabContent">
                            ${renderProgramList(parsed)}
                        </div>`;
                }
            }

            // Open panel
            document.getElementById('programsPanelOverlay').style.display = 'block';
            document.getElementById('programsSidePanel').style.right = '0';
        }

        function closeProgramsPanel() {
            document.getElementById('programsSidePanel').style.right = '-460px';
            document.getElementById('programsPanelOverlay').style.display = 'none';
        }

        function showCoursePanel(courseId) {
            const row = document.querySelector(`tr[data-course-id="${courseId}"]`);
            if (!row) return;
            const code     = row.dataset.courseCode     || '';
            const name     = row.dataset.courseName     || '';
            const year     = row.dataset.courseYear     || '';
            const semester = row.dataset.courseSemester || '';
            let programs = [], campuses = [];
            try { programs = JSON.parse(decodeURIComponent(escape(atob(row.dataset.coursePrograms || 'W10=')))); } catch(e) { programs = []; }
            try { campuses = JSON.parse(decodeURIComponent(escape(atob(row.dataset.courseCampuses || 'W10=')))); } catch(e) { campuses = []; }

            let colleges = [], progColMap = {}, progDetails = {};
            try { colleges    = JSON.parse(decodeURIComponent(escape(atob(row.dataset.courseColleges    || 'W10=')))); } catch(e) { colleges = []; }
            try { progColMap  = JSON.parse(decodeURIComponent(escape(atob(row.dataset.courseProgColMap  || 'e30=')))); } catch(e) { progColMap = {}; }
            try { progDetails = JSON.parse(decodeURIComponent(escape(atob(row.dataset.courseProgDetails || 'e30=')))); } catch(e) { progDetails = {}; }

            const displayName = (name && name.toLowerCase() !== code.toLowerCase()) ? name : '';
            const iconText = code.substring(0, 4).toUpperCase() || '?';

            document.getElementById('coursePanelIcon').textContent  = iconText;
            document.getElementById('coursePanelTitle').textContent = code || 'Course';
            document.getElementById('coursePanelSubtitle').textContent = displayName || (campuses.length ? campuses.join(', ') : '');

            // Stats bar
            // Unique year levels and semesters across all programs
            const allYears = [...new Set(Object.values(progDetails).map(d => d.year_level).filter(Boolean))];
            const allSems  = [...new Set(Object.values(progDetails).map(d => d.semester).filter(Boolean))];
            document.getElementById('coursePanelStats').innerHTML = `
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;display:flex;flex-direction:column;gap:1px;">
                    <span style="font-size:1.1rem;font-weight:800;color:#047857;">${programs.length}</span>
                    <span style="font-size:0.68rem;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Programs</span>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;display:flex;flex-direction:column;gap:1px;min-width:0;max-width:160px;">
                    <span style="font-size:0.82rem;font-weight:800;color:#3b82f6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${campuses.length > 0 ? campuses.join(', ') : '—'}</span>
                    <span style="font-size:0.68rem;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Campus</span>
                </div>
                ${allYears.length > 0 ? `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;display:flex;flex-direction:column;gap:1px;">
                    <span style="font-size:0.82rem;font-weight:800;color:#7c3aed;">${allYears.join(', ')}</span>
                    <span style="font-size:0.68rem;color:#94a3b8;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;">Year</span>
                </div>` : ''}`;

            // Body
            const body = document.getElementById('coursePanelBody');
            let html = '';

            // Course info section
            html += `<p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:12px;">Course Details</p>`;
            html += `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:20px;">`;
            const details = [
                { label: 'Course Code', value: code },
                { label: 'Semester',    value: allSems.join(', ')  || '—' },
                { label: 'Year Level',  value: allYears.join(', ') || '—' },
                { label: 'Campus',      value: campuses.join(', ') || '—' },
                ...(colleges.length > 0 ? [{ label: 'College', value: colleges.join(', ') }] : []),
            ];
            details.forEach(d => {
                html += `<div style="background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:10px 12px;">
                    <div style="font-size:0.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">${d.label}</div>
                    <div style="font-size:0.82rem;font-weight:600;color:#1e293b;">${esc(d.value)}</div>
                </div>`;
            });
            html += `</div>`;

            // Programs section
            if (programs.length === 0) {
                html += `<div style="text-align:center;padding:32px 0;color:#94a3b8;">
                    <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block;opacity:0.4"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p style="font-size:0.82rem;">No programs listed.</p></div>`;
            } else {
                html += `<p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:12px;">All Programs</p>`;
                const colors = [
                    ['#ecfdf5','#047857'],['#eff6ff','#3b82f6'],['#fdf4ff','#9333ea'],
                    ['#fff7ed','#ea580c'],['#fefce8','#ca8a04'],['#f0fdf4','#16a34a']
                ];
                // Resolve full names from colleges data
                html += programs.map((prog, i) => {
                    // Per-program details come directly from the course record — no guessing
                    const normKey = prog.replace(/\s+/g, '').toUpperCase();
                    const pd = progDetails[normKey] || progDetails[prog] || {};

                    // College name: use pd.college (stored on the course record itself).
                    // Only look up in colleges collection to get the full college name from the code.
                    const collegeRec = pd.college
                        ? allData.find(d =>
                            d.type === 'college' &&
                            (d.code || '').toLowerCase() === pd.college.toLowerCase()
                          ) || allData.find(d =>
                            d.type === 'college' &&
                            (d.name || '').toLowerCase() === pd.college.toLowerCase()
                          )
                        : null;
                    const linkedCollegeName = collegeRec
                        ? (collegeRec.name || collegeRec.code || pd.college || '')
                        : (pd.college || '');

                    // Full program name: look up inside the MATCHED college record only
                    const fullName = collegeRec
                        ? ((collegeRec.programs || []).find(p => {
                            const a = p.includes('=') ? p.split('=')[0].trim() : p.trim();
                            return a.toLowerCase() === prog.toLowerCase();
                          }) || '').split('=')[1]?.trim() || ''
                        : '';
                    const [bg, fg] = colors[i % colors.length];
                    return `<div style="display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-radius:10px;border:1px solid #f1f5f9;margin-bottom:8px;background:#fafafa;transition:all 0.15s;cursor:default;"
                        onmouseover="this.style.borderColor='#a7f3d0';this.style.background='#f0fdf4'"
                        onmouseout="this.style.borderColor='#f1f5f9';this.style.background='#fafafa'">
                        <div style="width:40px;height:40px;border-radius:9px;background:${bg};color:${fg};display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:800;letter-spacing:-0.5px;flex-shrink:0;margin-top:2px;">${prog.substring(0,4).toUpperCase()}</div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.82rem;font-weight:600;color:#1e293b;">${fullName ? esc(fullName) : esc(prog)}</div>
                            <div style="font-size:0.7rem;color:#94a3b8;margin-top:1px;">${esc(prog)}</div>
                            <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:5px;">
                                ${pd.year_level ? `<span style="font-size:0.65rem;font-weight:600;color:#7c3aed;background:#f5f3ff;border:1px solid #ede9fe;padding:1px 6px;border-radius:99px;">${esc(pd.year_level)}</span>` : ''}
                                ${pd.semester   ? `<span style="font-size:0.65rem;font-weight:600;color:#0369a1;background:#f0f9ff;border:1px solid #e0f2fe;padding:1px 6px;border-radius:99px;">${esc(pd.semester)}</span>` : ''}
                                ${linkedCollegeName ? `<span style="font-size:0.65rem;font-weight:600;color:#047857;background:#f0fdf4;border:1px solid #d1fae5;padding:1px 6px;border-radius:99px;">${esc(linkedCollegeName)}</span>` : ''}
                                ${pd.campus     ? `<span style="font-size:0.65rem;font-weight:600;color:#b45309;background:#fffbeb;border:1px solid #fde68a;padding:1px 6px;border-radius:99px;">${esc(pd.campus)}</span>` : ''}
                            </div>
                        </div>
                        <span style="font-size:0.65rem;font-weight:700;color:#94a3b8;background:#f1f5f9;padding:2px 7px;border-radius:99px;flex-shrink:0;">#${i+1}</span>
                    </div>`;
                }).join('');
            }

            body.innerHTML = html;

            // Open panel
            document.getElementById('coursePanelOverlay').style.display = 'block';
            document.getElementById('courseDetailSidePanel').style.right = '0';
        }

        function closeCoursePanel() {
            document.getElementById('courseDetailSidePanel').style.right = '-460px';
            document.getElementById('coursePanelOverlay').style.display = 'none';
        }
        function proctorsFilter()     { genericFilter('proctorsBody',   'proctorsSearch', 'proctors'); }

        function coursesClearFilters() {
    const s = document.getElementById('coursesSearch');
    const c = document.getElementById('coursesCampus');
    if (s) s.value = '';
    if (c) c.value = '';
    campusFilters['courses'] = currentUser.campus || '';
    coursesFilter();
}

function proctorsClearFilters() {
    const s = document.getElementById('proctorsSearch');
    const c = document.getElementById('proctorsCampus');
    if (s) s.value = '';
    if (c) c.value = '';
    campusFilters['proctors'] = currentUser.campus || '';
    proctorsFilter();
}

function collegesClearFilters() {
    const s = document.getElementById('collegesSearch');
    const c = document.getElementById('collegesCampus');
    if (s) s.value = '';
    if (c) c.value = '';
    campusFilters['colleges'] = currentUser.campus || '';
    collegesFilter();
}

        function schedMgmtClearFilters() {
    ['schedMgmtSearch','schedMgmtCollege','schedMgmtType'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    campusFilters['schedule-mgmt'] = currentUser.campus || '';
    renderApp();
}

        // Paste here
function schedMgmtFilter() {
    const search  = (document.getElementById('schedMgmtSearch')?.value || '').toLowerCase();
    const campus  = campusFilters['schedule-mgmt'] || '';
    const college = (document.getElementById('schedMgmtCollege')?.value || '').toLowerCase();
    const type    = (document.getElementById('schedMgmtType')?.value || '').toLowerCase();

    const rows = document.querySelectorAll('#schedMgmtBody tr[data-campus-row]');
    let visible = 0;
    rows.forEach(row => {
        const rowSearch  = (row.getAttribute('data-search')      || '').toLowerCase();
        const rowCampus  =  row.getAttribute('data-campus-row')  || '';
        const rowCollege = (row.getAttribute('data-college-row') || '').toLowerCase();
        const rowType    = (row.getAttribute('data-type-row')    || '').toLowerCase();

        const matches = (!search  || rowSearch.includes(search))
                     && (!campus  || rowCampus  === campus)
                     && (!college || rowCollege === college)
                     && (!type    || rowType    === type);

        row.setAttribute('data-filtered', matches ? 'false' : 'true');
        if (matches) visible++;
    });

    const badge = document.getElementById('rowCountBadge');
    const total = document.querySelectorAll('#schedMgmtBody tr[data-campus-row]').length;
    if (badge) badge.textContent = visible === total
        ? `${total} records`
        : `${visible} of ${total} records`;

    // Update campus tab active states
    document.querySelectorAll('.campus-tab').forEach(btn => {
        const onclick = btn.getAttribute('onclick') || '';
        const match = onclick.match(/'schedule-mgmt'\]='([^']*)'/);
        if (match) btn.classList.toggle('active', match[1] === campus);
    });

    // Re-apply pagination with filtered rows
    if (typeof pageState !== 'undefined') pageState['schedule-mgmt'] = 1;
    if (typeof applyPagination === 'function') applyPagination('schedule-mgmt');

    // Sync checkbox / bulk bar state after filter changes
    window.onSchedRowCbChange && window.onSchedRowCbChange();
}

    function viewSchedClearFilters() {
    ['viewSchedSearch','viewSchedCollege','viewSchedSemester','viewSchedCourse','viewSchedType','viewSchedYearLevel','viewSchedStatus'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    campusFilters['view-schedule'] = currentUser.campus || '';
    renderApp();
}
    function viewSchedFilter() {
    const search    = (document.getElementById('viewSchedSearch')?.value || '').toLowerCase();
    const campus    = campusFilters['view-schedule'] || '';
    const college   = document.getElementById('viewSchedCollege')?.value || '';
    const semester  = (document.getElementById('viewSchedSemester')?.value || '').toLowerCase();
    const course    = (document.getElementById('viewSchedCourse')?.value || '').toLowerCase();
    const type      = document.getElementById('viewSchedType')?.value || '';
    const yearLevel = (document.getElementById('viewSchedYearLevel')?.value || '').toLowerCase();
    const status    = document.getElementById('viewSchedStatus')?.value || '';

    const rows = document.querySelectorAll('#viewSchedBody tr');
    let visible = 0;
    rows.forEach(row => {
        const rowSearch   = (row.getAttribute('data-search')       || '').toLowerCase();
        const rowCampus   =  row.getAttribute('data-campus-row')   || '';
        const rowCollege  =  row.getAttribute('data-college-row')  || '';
        const rowStatus   =  row.getAttribute('data-status-row')   || '';
        const rowType     =  row.getAttribute('data-type-row')     || '';
        const rowSemester = (row.getAttribute('data-semester-row') || '').toLowerCase();
        const rowCourse   = (row.getAttribute('data-course-row')   || '').toLowerCase();
        const rowYear     = (row.getAttribute('data-year-row')     || '').toLowerCase();

        const show = (!search    || rowSearch.includes(search))
                  && (!campus    || rowCampus   === campus)
                  && (!college   || rowCollege  === college)
                  && (!semester  || rowSemester === semester)
                  && (!course    || rowCourse   === course)
                  && (!type      || rowType     === type.toLowerCase())
                  && (!yearLevel || rowYear     === yearLevel)
                  && (!status    || rowStatus   === status);

        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const badge = document.getElementById('viewSchedCount');
    if (badge) badge.textContent = visible === rows.length
        ? `${rows.length} records`
        : `${visible} of ${rows.length} records`;
}

       function analyticsFilter() {
            roomUtilPage = 1; // reset pagination on filter change
            // Update active pill styling
            document.querySelectorAll('#analyticsDatePills [data-pill]').forEach(btn => {
                const isActive = btn.dataset.pill === analyticsDateFilter;
                btn.className = 'px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap ' +
                    (isActive ? 'bg-white text-emerald-700 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700');
            });
            const campus = campusFilters['analytics'] || '';

            // ── Date range filter ───────────────────────────────────────────
            const now   = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

            function inDateRange(s) {
                const raw = (s.exam_date || s.date || '').trim();
                if (!raw || raw === '0000-00-00') return analyticsDateFilter === 'all';
                const d = new Date(raw.substring(0, 10) + 'T00:00:00');
                if (isNaN(d.getTime())) return analyticsDateFilter === 'all';
                if (analyticsDateFilter === 'day')   return d >= today && d < new Date(today.getTime() + 86400000);
                if (analyticsDateFilter === 'week') {
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay()); // Sunday
                    const weekEnd   = new Date(weekStart.getTime() + 7 * 86400000);
                    return d >= weekStart && d < weekEnd;
                }
                if (analyticsDateFilter === 'month') return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
                return true; // 'all'
            }

            const schedules = allData.filter(d => d.type === 'schedule' && (!campus || d.campus === campus) && inDateRange(d));
            const total     = schedules.length;
            const approved  = schedules.filter(s => s.status === 'Approved').length;
            const pending   = schedules.filter(s => s.status === 'Pending' || !s.status).length;
            const pct       = total > 0 ? Math.round((approved / total) * 100) : 0;
            const cards     = document.getElementById('analyticsCards');
            if (cards) cards.innerHTML = renderAnalyticsCards(schedules, pct, approved, pending, total, campus);
            const label = document.getElementById('analyticsCampusLabel');
            if (label) label.textContent = campus ? `Showing data for: ${campus}` : '';
            renderAnalyticsCharts(schedules, campus);
            if (document.getElementById('proctorAnalyticsChart')) {
                proctorAnalyticsFilter(proctorDateFilter);
            }
            // Init rejected schedules pagination
            if (typeof initRejSchedPager === 'function') initRejSchedPager();
        }

        // ─── Donut/Pie chart renderer ─────────────────────────────────────────
        function renderPieChart(el, entries, colorList, emptyMsg) {
            if (!el) return;
            if (!entries || entries.length === 0) {
                el.innerHTML = `<p class="text-slate-400 text-sm text-center py-8">${emptyMsg || 'No data available'}</p>`;
                return;
            }
            const total = entries.reduce((s, e) => s + e[1], 0);
            if (total === 0) {
                el.innerHTML = `<p class="text-slate-400 text-sm text-center py-8">${emptyMsg || 'No data available'}</p>`;
                return;
            }
            const cx = 90, cy = 90, r = 78, holeR = 44;
            let startAngle = -Math.PI / 2;
            const slices = entries.map(([label, count], i) => {
                const angle = (count / total) * 2 * Math.PI;
                const end   = startAngle + angle;
                const x1 = cx + r * Math.cos(startAngle), y1 = cy + r * Math.sin(startAngle);
                const x2 = cx + r * Math.cos(end),         y2 = cy + r * Math.sin(end);
                const ix1 = cx + holeR * Math.cos(startAngle), iy1 = cy + holeR * Math.sin(startAngle);
                const ix2 = cx + holeR * Math.cos(end),         iy2 = cy + holeR * Math.sin(end);
                const large = angle > Math.PI ? 1 : 0;
                const pct   = Math.round((count / total) * 100);
                const path  = angle < 0.02 ? '' :
                    `M ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} L ${ix2} ${iy2} A ${holeR} ${holeR} 0 ${large} 0 ${ix1} ${iy1} Z`;
                startAngle = end;
                return { label, count, pct, color: colorList[i % colorList.length], path };
            });
            const svgPaths = slices.map(s => s.path ?
                `<path d="${s.path}" fill="${s.color}" stroke="white" stroke-width="1.5" opacity="0.92">
                    <title>${s.label}: ${s.count} (${s.pct}%)</title>
                 </path>` : '').join('');
            const legend = entries.map(([label, count], i) => {
                const pct = Math.round((count / total) * 100);
                return `<div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${colorList[i % colorList.length]}"></span>
                    <span class="text-[11px] text-slate-600 truncate font-medium" title="${label}">${label}</span>
                    <span class="text-[11px] text-slate-400 ml-auto shrink-0 font-semibold">${count} <span class="text-slate-300">(${pct}%)</span></span>
                </div>`;
            }).join('');
            el.innerHTML = `
                <div class="flex flex-col sm:flex-row items-center gap-4">
                    <svg viewBox="0 0 180 180" width="180" height="180" class="shrink-0">
                        ${svgPaths}
                        <circle cx="${cx}" cy="${cy}" r="${holeR - 2}" fill="white"/>
                        <text x="${cx}" y="${cy - 6}" text-anchor="middle" font-size="18" font-weight="bold" fill="#1e293b">${total}</text>
                        <text x="${cx}" y="${cy + 12}" text-anchor="middle" font-size="8" fill="#94a3b8">total</text>
                    </svg>
                    <div class="flex-1 w-full space-y-1.5 min-w-0">${legend}</div>
                </div>`;
        }

        const PIE_COLORS          = ['#10b981','#3b82f6','#8b5cf6','#f97316','#ec4899','#06b6d4','#84cc16','#f59e0b','#14b8a6','#6366f1','#f43f5e','#a855f7'];
        const CAMPUS_PIE_COLORS   = ['#14b8a6','#6366f1','#f43f5e','#eab308','#0ea5e9','#d946ef','#22c55e','#fb923c'];
        const BUILDING_PIE_COLORS = ['#f59e0b','#f97316','#eab308','#fb923c','#fbbf24','#fcd34d','#fde68a','#fef3c7'];
        const ROOM_PIE_COLORS     = ['#3b82f6','#2563eb','#60a5fa','#0ea5e9','#38bdf8','#06b6d4','#22d3ee','#93c5fd'];
        const PROCTOR_PIE_COLORS  = ['#8b5cf6','#6366f1','#3b82f6','#14b8a6','#10b981','#f59e0b','#f97316','#ec4899','#06b6d4','#84cc16','#a855f7','#f43f5e'];

        function renderAnalyticsCharts(schedules, campus) {
            // If called with no args (e.g. from pagination), rebuild from current filter state
            if (!schedules) {
                campus = campusFilters['analytics'] || '';
                schedules = allData.filter(d => d.type === 'schedule' && (!campus || d.campus === campus));
            }

            // ── Schedules by Department/Program — Donut ──────────────────────
            const deptEl = document.getElementById('byDeptChart');
            if (deptEl) {
                const deptMap = {};
                schedules.forEach(s => {
                    const key = s.college || s.program || 'Unknown';
                    deptMap[key] = (deptMap[key] || 0) + 1;
                });
                const deptEntries = Object.entries(deptMap).sort((a,b) => b[1]-a[1]);
                renderPieChart(deptEl, deptEntries, PIE_COLORS, 'No department/program data available');
            }

            // ── Room Utilization & Availability ──────────────────────────────
            const roomEl = document.getElementById('roomUtilChart');
            if (roomEl) {
                const rooms = allData.filter(d => d.type === 'room' && (!campus || d.campus === campus));
                const totalRoomCount = rooms.length;

                // Build all room rows data
                const roomRows = rooms.map(room => {
                    const roomId   = dbId(String(room.id));
                    const bookings = schedules.filter(s => dbId(String(s.room_id || s.room || '')) === roomId).length;
                    const locked   = !!room.locked;
                    const utilPct  = bookings > 0 ? Math.min(100, Math.round((bookings / 8) * 100)) : 0;
                    const barColor = locked ? 'bg-red-400' : utilPct >= 75 ? 'bg-orange-500' : utilPct >= 40 ? 'bg-blue-500' : 'bg-emerald-500';
                    const statusLabel = locked
                        ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-600">Locked</span>`
                        : `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Available</span>`;
                    return `
                    <div class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:border-slate-200 transition bg-slate-50">
                        <div class="w-28 shrink-0">
                            <p class="text-sm font-semibold text-slate-800 truncate" title="${room.name}">${room.name}</p>
                            <p class="text-[10px] text-slate-400 truncate">${room.building || ''}${room.campus ? ' · ' + room.campus : ''}</p>
                        </div>
                        <div class="flex-1 h-5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full ${barColor} rounded-full transition-all duration-500"
                                 style="width:${Math.max(utilPct > 0 ? 4 : 0, utilPct)}%"></div>
                        </div>
                        <div class="w-28 shrink-0 flex items-center justify-end gap-2">
                            <span class="text-xs text-slate-500">${bookings} booking${bookings!==1?'s':''}</span>
                            ${statusLabel}
                        </div>
                    </div>`;
                });

                // Update header info
                const infoEl = document.getElementById('roomUtilInfo');
                if (infoEl) infoEl.textContent = `${totalRoomCount} total room${totalRoomCount !== 1 ? 's' : ''}`;

                if (totalRoomCount === 0) {
                    roomEl.innerHTML = '<p class="text-slate-400 text-sm text-center py-8">No rooms found for selected campus.</p>';
                    const pager = document.getElementById('roomUtilPager');
                    if (pager) pager.classList.add('hidden');
                } else {
                    const totalPages = Math.ceil(totalRoomCount / ROOM_UTIL_PER_PAGE);
                    // Clamp page
                    if (roomUtilPage < 1) roomUtilPage = 1;
                    if (roomUtilPage > totalPages) roomUtilPage = totalPages;

                    const start = (roomUtilPage - 1) * ROOM_UTIL_PER_PAGE;
                    const end   = Math.min(start + ROOM_UTIL_PER_PAGE, totalRoomCount);
                    roomEl.innerHTML = roomRows.slice(start, end).join('');

                    // Pager
                    const pager     = document.getElementById('roomUtilPager');
                    const pagerInfo = document.getElementById('roomUtilPagerInfo');
                    const pagerCtrl = document.getElementById('roomUtilPagerControls');
                    if (pager) {
                        if (totalPages <= 1) {
                            pager.classList.add('hidden');
                        } else {
                            pager.classList.remove('hidden');
                            if (pagerInfo) pagerInfo.textContent = `Showing ${start + 1}–${end} of ${totalRoomCount} rooms`;
                            if (pagerCtrl) {
                                const btnBase = 'inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold transition';
                                const btnActive = 'bg-emerald-600 text-white shadow-sm';
                                const btnInactive = 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50';
                                const btnDisabled = 'bg-slate-50 border border-slate-100 text-slate-300 cursor-not-allowed';

                                let html = '';
                                // Prev
                                html += roomUtilPage > 1
                                    ? `<button class="${btnBase} ${btnInactive}" onclick="roomUtilPage--;renderAnalyticsCharts()">‹</button>`
                                    : `<button class="${btnBase} ${btnDisabled}" disabled>‹</button>`;

                                // Page numbers
                                for (let p = 1; p <= totalPages; p++) {
                                    if (totalPages > 7 && p > 2 && p < totalPages - 1 && Math.abs(p - roomUtilPage) > 1) {
                                        if (p === 3 || p === totalPages - 2) html += `<span class="px-1 text-slate-400 text-xs">…</span>`;
                                        continue;
                                    }
                                    html += `<button class="${btnBase} ${p === roomUtilPage ? btnActive : btnInactive}" onclick="roomUtilPage=${p};renderAnalyticsCharts()">${p}</button>`;
                                }

                                // Next
                                html += roomUtilPage < totalPages
                                    ? `<button class="${btnBase} ${btnInactive}" onclick="roomUtilPage++;renderAnalyticsCharts()">›</button>`
                                    : `<button class="${btnBase} ${btnDisabled}" disabled>›</button>`;

                                pagerCtrl.innerHTML = html;
                            }
                        }
                    }
                }
            }

            // ── Most Utilized Buildings — Donut ─────────────────────────────
            const buildingsEl = document.getElementById('topBuildingsChart');
            if (buildingsEl) {
                const buildingMap = {};
                schedules.forEach(s => {
                    const r = allData.find(d => d.type === 'room' && dbId(String(d.id)) === dbId(String(s.room_id || s.room || '')));
                    const key = r && r.building ? r.building.trim() : null;
                    if (key) buildingMap[key] = (buildingMap[key] || 0) + 1;
                });
                const bEntries = Object.entries(buildingMap).sort((a,b) => b[1]-a[1]).slice(0, 8);
                renderPieChart(buildingsEl, bEntries, BUILDING_PIE_COLORS, 'No building data — rooms may not have a building assigned.');
            }

            // ── Most Utilized Rooms — Donut ──────────────────────────────────
            const topRoomsEl = document.getElementById('topRoomsChart');
            if (topRoomsEl) {
                const roomBookMap = {};
                schedules.forEach(s => {
                    const rid = dbId(String(s.room_id || s.room || ''));
                    if (rid) roomBookMap[rid] = (roomBookMap[rid] || 0) + 1;
                });
                const topRooms = Object.entries(roomBookMap)
                    .map(([rid, count]) => {
                        const r = allData.find(d => d.type === 'room' && dbId(String(d.id)) === rid);
                        return [r ? r.name : '(Unknown)', count];
                    })
                    .sort((a,b) => b[1]-a[1]).slice(0, 8);
                renderPieChart(topRoomsEl, topRooms, ROOM_PIE_COLORS, 'No room booking data available.');
            }

            // ── Scheduling Conflicts ─────────────────────────────────────
            const conflictsTableEl = document.getElementById('conflictsTable');
            const conflictBadgeEl  = document.getElementById('conflictSummaryBadge');
            if (conflictsTableEl) {
                const allScheds = campus
                    ? allData.filter(d => d.type === 'schedule' && (d.campus||'') === campus)
                    : allData.filter(d => d.type === 'schedule');
                const conflicts = [];

                // 1. Locked room (online exams need no room — skip)
                allScheds.forEach(s => {
                    if (s.is_online) return;
                    const r = allData.find(d => d.type === 'room' && dbId(String(d.id)) === dbId(String(s.room_id || s.room || '')));
                    if (r && r.locked) conflicts.push({
                        type: 'locked', badge: '🔒 Locked Room', bg: 'bg-red-50', bdg: 'bg-red-100 text-red-700',
                        course: s.course_code || s.course || '—',
                        section: [s.year_level, s.section].filter(Boolean).join(' / ') || '—',
                        college: s.college || '—',
                        issue: `Room <strong>${r.name}${r.building?', '+r.building:''}</strong> is locked/blocked`,
                        date: s.exam_date || s.date || '—',
                        campus: s.campus || '—'
                    });
                });

                // 2. Double-booking: same room + date + time (online exams need no room — skip)
                const seen = {};
                allScheds.forEach(s => {
                    if (s.is_online) return;
                    const rid  = dbId(String(s.room_id || s.room || ''));
                    const date = (s.exam_date || s.date || '').trim();
                    const time = (s.time_slot || s.start_time || '').trim();
                    if (!rid || !date || !time) return;
                    const key = `${rid}|${date}|${time}`;
                    if (seen[key]) {
                        const prev = seen[key];
                        const r = allData.find(d => d.type === 'room' && dbId(String(d.id)) === rid);
                        conflicts.push({
                            type: 'double', badge: '⚠️ Double-Booking', bg: 'bg-orange-50', bdg: 'bg-orange-100 text-orange-700',
                            course: `${s.course_code||'?'} & ${prev.course_code||'?'}`,
                            section: [s.year_level, s.section].filter(Boolean).join(' / ') || '—',
                            college: s.college || '—',
                            issue: `${r ? r.name+(r.building?', '+r.building:'') : 'Room #'+rid} double-booked at ${time}`,
                            date, campus: s.campus || '—'
                        });
                    } else { seen[key] = s; }
                });

                // 3. No room assigned (online exams intentionally have no room — skip)
                allScheds.forEach(s => {
                    if (s.is_online) return;
                    const rid = s.room_id || s.room || '';
                    if (!rid || rid === '0' || rid === 0) conflicts.push({
                        type: 'noroom', badge: '📋 No Room', bg: 'bg-slate-50', bdg: 'bg-slate-200 text-slate-600',
                        course: s.course_code || s.course || '—',
                        section: [s.year_level, s.section].filter(Boolean).join(' / ') || '—',
                        college: s.college || '—',
                        issue: 'No room assigned to this schedule',
                        date: s.exam_date || s.date || '—',
                        campus: s.campus || '—'
                    });
                });

                // 4. Proctor double-assignment: same proctor + date + timeslot
                const seenProctors = {};
                allScheds.forEach(s => {
                    const pid  = String(s.proctor_id || '');
                    const date = (s.exam_date || s.date || '').trim();
                    const time = (s.time_slot || '').trim();
                    if (!pid || pid === '0' || !date || !time) return;
                    const key = `${pid}|${date}|${time}`;
                    if (seenProctors[key]) {
                        const prev = seenProctors[key];
                        conflicts.push({
                            type: 'proctor', badge: '👤 Proctor Conflict', bg: 'bg-purple-50', bdg: 'bg-purple-100 text-purple-700',
                            course: `${s.course_code||'?'} & ${prev.course_code||'?'}`,
                            section: [s.year_level, s.section].filter(Boolean).join(' / ') || '—',
                            college: s.college || '—',
                            issue: `${s.proctor_name || 'Proctor #'+pid} is assigned to two rooms at ${time}`,
                            date, campus: s.campus || '—'
                        });
                    } else { seenProctors[key] = s; }
                });

                // 5. Section time overlap: same section+college scheduled in two rooms at the same time
                const seenSections = {};
                allScheds.forEach(s => {
                    const sec  = (s.section || s.section_name || '').trim().toLowerCase();
                    const col  = (s.college || '').trim().toLowerCase();
                    const date = (s.exam_date || s.date || '').trim();
                    const time = (s.time_slot || '').trim();
                    if (!sec || !col || !date || !time) return;
                    const key = `${sec}|${col}|${date}|${time}`;
                    if (seenSections[key]) {
                        const prev = seenSections[key];
                        conflicts.push({
                            type: 'section', badge: '🎓 Section Overlap', bg: 'bg-yellow-50', bdg: 'bg-yellow-100 text-yellow-700',
                            course: `${s.course_code||'?'} & ${prev.course_code||'?'}`,
                            section: sec.toUpperCase(),
                            college: s.college || '—',
                            issue: `Section scheduled in two rooms simultaneously at ${time}`,
                            date, campus: s.campus || '—'
                        });
                    } else { seenSections[key] = s; }
                });

                // Summary badge
                if (conflictBadgeEl) {
                    const lk = conflicts.filter(c=>c.type==='locked').length;
                    const db = conflicts.filter(c=>c.type==='double').length;
                    const nr = conflicts.filter(c=>c.type==='noroom').length;
                    const pt = conflicts.filter(c=>c.type==='proctor').length;
                    const st = conflicts.filter(c=>c.type==='section').length;
                    if (conflicts.length === 0) {
                        conflictBadgeEl.innerHTML = '<span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>No conflicts detected</span>';
                    } else {
                        conflictBadgeEl.innerHTML =
                            '<div class="flex flex-wrap gap-2">' +
                            (lk ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">🔒 ${lk} Locked Room${lk!==1?'s':''}</span>` : '') +
                            (db ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-orange-100 text-orange-700">⚠️ ${db} Double-Booking${db!==1?'s':''}</span>` : '') +
                            (nr ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-200 text-slate-600">📋 ${nr} No Room${nr!==1?'s':''}</span>` : '') +
                            (pt ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700">👤 ${pt} Proctor Conflict${pt!==1?'s':''}</span>` : '') +
                            (st ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">🎓 ${st} Section Overlap${st!==1?'s':''}</span>` : '') +
                            '</div>';
                    }
                }

                // Store for pagination & filtering
                window._conflictsAll  = conflicts;
                window._conflictsType = 'all';
                _conflictsPage = 1;

                if (conflicts.length === 0) {
                    const pagerEl2 = document.getElementById('conflictsPager');
                    if (pagerEl2) pagerEl2.innerHTML = '';
                    const pillsEl = document.getElementById('conflictTypePills');
                    if (pillsEl) pillsEl.style.display = 'none';
                    conflictsTableEl.innerHTML = `
                        <div class="flex flex-col items-center justify-center py-14 text-center">
                            <div class="w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="font-bold text-slate-700 text-sm">All clear — no scheduling conflicts found</p>
                            <p class="text-xs text-slate-400 mt-1">No double-bookings, locked rooms, or missing room assignments detected.</p>
                        </div>`;
                } else {
                    const pillsEl = document.getElementById('conflictTypePills');
                    if (pillsEl) pillsEl.style.display = '';
                    renderConflictsPage(1);
                }
            }

            // ── Proctor Analytics (also re-render when analytics charts refresh) ──
            if (document.getElementById('proctorAnalyticsChart')) {
                renderProctorAnalytics();
                proctorAnalyticsFilter(proctorDateFilter);
            }
        }

        // ─── Proctor Analytics ────────────────────────────────────────────────────
        let proctorDateFilter = 'all';

        function proctorAnalyticsFilter(period) {
            proctorDateFilter = period || 'all';
            document.querySelectorAll('#proctorDatePills [data-ppill]').forEach(btn => {
                const isActive = btn.dataset.ppill === proctorDateFilter;
                btn.className = 'px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap ' +
                    (isActive ? 'bg-white text-purple-700 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700');
            });
            renderProctorAnalytics();
        }

        function renderProctorAnalytics() {
            const now   = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

            function inProctorDateRange(s) {
                const raw = (s.exam_date || s.date || '').trim();
                if (!raw || raw === '0000-00-00') return proctorDateFilter === 'all';
                const d = new Date(raw.substring(0, 10) + 'T00:00:00');
                if (isNaN(d.getTime())) return proctorDateFilter === 'all';
                if (proctorDateFilter === 'day')   return d >= today && d < new Date(today.getTime() + 86400000);
                if (proctorDateFilter === 'week') {
                    const ws = new Date(today); ws.setDate(today.getDate() - today.getDay());
                    return d >= ws && d < new Date(ws.getTime() + 7 * 86400000);
                }
                if (proctorDateFilter === 'month') return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
                if (proctorDateFilter === 'year')  return d.getFullYear() === now.getFullYear();
                return true;
            }

            const myCampus = currentUser.campus || '';
            const schedules = allData.filter(d => d.type === 'schedule'
                && (!myCampus || d.campus === myCampus)
                && inProctorDateRange(d));
            const proctors  = allData.filter(d => d.type === 'proctor' && (!myCampus || (d.campus || '') === myCampus));
            const totalExams = schedules.length;

            // Build proctor → { total, approved, pending } map
            const proctorMap = {};
            proctors.forEach(p => {
                proctorMap[String(p.id)] = { id: p.id, name: p.name || '—', campus: p.campus || '—', total: 0, approved: 0, pending: 0 };
            });
            schedules.forEach(s => {
                const pids = (s.proctor_ids && s.proctor_ids.length)
                    ? s.proctor_ids.map(String)
                    : (s.proctor_id ? [String(s.proctor_id)] : []);
                pids.forEach(pid => {
                    if (!proctorMap[pid]) proctorMap[pid] = { id: pid, name: s.proctor_name || 'Unknown', campus: s.campus || '—', total: 0, approved: 0, pending: 0 };
                    proctorMap[pid].total++;
                    if (s.status === 'Approved') proctorMap[pid].approved++;
                    if (s.status === 'Pending' || !s.status) proctorMap[pid].pending++;
                });
            });

            const proctorRows = Object.values(proctorMap)
                .filter(p => p.total > 0)
                .sort((a, b) => b.total - a.total);

            const periodLabel = { all:'All Time', day:'Today', week:'This Week', month:'This Month', year:'This Year' }[proctorDateFilter] || 'All Time';

            // Summary bar
            const summaryEl = document.getElementById('proctorAnalyticsSummary');
            if (summaryEl) {
                const active = proctorRows.length;
                summaryEl.textContent = `${active} proctor${active !== 1 ? 's' : ''} assigned · ${totalExams} exam${totalExams !== 1 ? 's' : ''} · Period: ${periodLabel}`;
            }

            // Donut chart
            const chartEl = document.getElementById('proctorAnalyticsChart');
            if (chartEl) {
                const pieEntries = proctorRows.slice(0, 12).map(p => [p.name, p.total]);
                renderPieChart(chartEl, pieEntries, PROCTOR_PIE_COLORS, 'No proctor assignments found for this period.');
            }

            // Table
            const bodyEl = document.getElementById('proctorAnalyticsBody');
            if (bodyEl) {
                if (proctorRows.length === 0) {
                    bodyEl.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-slate-400 text-sm">No proctor assignments for this period.</td></tr>`;
                    const pagerEl = document.getElementById('proctorAnalyticsPager');
                    if (pagerEl) pagerEl.innerHTML = '';
                } else {
                    _proctorAllRows = proctorRows;
                    _proctorPage = 1;
                    renderProctorPage(_proctorPage, totalExams);
                }
            }
        }

        let _proctorPage = 1;
        const PROCTOR_PAGE_SIZE = 10;
        let _proctorAllRows = [];

        function renderProctorPage(page, totalExams) {
            const rows = _proctorAllRows;
            const total = rows.length;
            if (!total) return;
            const totalPages = Math.ceil(total / PROCTOR_PAGE_SIZE);
            _proctorPage = Math.max(1, Math.min(page, totalPages));
            const start = (_proctorPage - 1) * PROCTOR_PAGE_SIZE;
            const slice = rows.slice(start, start + PROCTOR_PAGE_SIZE);
            const te = typeof totalExams !== 'undefined' ? totalExams : rows.reduce((sum, p) => sum + p.total, 0);

            const bodyEl = document.getElementById('proctorAnalyticsBody');
            if (bodyEl) {
                bodyEl.innerHTML = slice.map((p, i) => {
                    const sharePct = te > 0 ? Math.round((p.total / te) * 100) : 0;
                    return `<tr class="hover:bg-purple-50 transition">
                        <td class="py-3 px-4 text-xs text-slate-400 font-medium">${start + i + 1}</td>
                        <td class="py-3 px-4 text-sm font-semibold text-slate-800">${p.name}</td>
                        <td class="py-3 px-4 text-xs text-slate-500">${p.campus}</td>
                        <td class="py-3 px-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">${p.total}</span></td>
                        <td class="py-3 px-4 text-xs text-emerald-600 font-semibold">${p.approved}</td>
                        <td class="py-3 px-4 text-xs text-orange-500 font-semibold">${p.pending}</td>
                        <td class="py-3 px-4 text-xs text-slate-500">${sharePct}%</td>
                    </tr>`;
                }).join('');
            }

            const pagerEl = document.getElementById('proctorAnalyticsPager');
            if (!pagerEl) return;
            const btnBase   = 'px-2.5 py-1 rounded text-[11px] font-medium transition';
            const btnActive = 'bg-purple-600 text-white shadow-sm';
            const btnInact  = 'text-slate-600 hover:bg-slate-100';
            const maxV = 7;
            let ps = Math.max(1, _proctorPage - Math.floor(maxV / 2));
            let pe = Math.min(totalPages, ps + maxV - 1);
            if (pe - ps < maxV - 1) ps = Math.max(1, pe - maxV + 1);
            let pageButtons = '';
            if (ps > 1) pageButtons += `<button onclick="renderProctorPage(1)" class="${btnBase} ${btnInact}">1</button><span class="text-slate-300 text-xs px-1">…</span>`;
            for (let p = ps; p <= pe; p++) {
                pageButtons += `<button onclick="renderProctorPage(${p})" class="${btnBase} ${p === _proctorPage ? btnActive : btnInact}">${p}</button>`;
            }
            if (pe < totalPages) pageButtons += `<span class="text-slate-300 text-xs px-1">…</span><button onclick="renderProctorPage(${totalPages})" class="${btnBase} ${btnInact}">${totalPages}</button>`;

            pagerEl.innerHTML = `
                <p class="text-[10px] text-slate-400">Showing ${start + 1}–${Math.min(start + PROCTOR_PAGE_SIZE, total)} of ${total} proctor${total !== 1 ? 's' : ''}</p>
                <div class="flex items-center gap-1">
                    <button onclick="renderProctorPage(${_proctorPage - 1})" ${_proctorPage === 1 ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg> Prev
                    </button>
                    <div class="flex items-center gap-0.5">${pageButtons}</div>
                    <button onclick="renderProctorPage(${_proctorPage + 1})" ${_proctorPage === totalPages ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                        Next <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>`;
        }
        window.renderProctorPage = renderProctorPage;

        // ─── Rejected Schedules Pagination ───────────────────────────────────────
        let _rejSchedPage = 1;
        const REJ_SCHED_PAGE_SIZE = 10;

        function initRejSchedPager() {
            _rejSchedPage = 1;
            renderRejSchedPage(_rejSchedPage);
        }

        function renderRejSchedPage(page) {
            const data = window._rejScopes || [];
            const showCampusCol = window._rejShowCampusCol || false;
            const total = data.length;
            if (!total) return;
            const totalPages = Math.ceil(total / REJ_SCHED_PAGE_SIZE);
            _rejSchedPage = Math.max(1, Math.min(page, totalPages));
            const start = (_rejSchedPage - 1) * REJ_SCHED_PAGE_SIZE;
            const slice = data.slice(start, start + REJ_SCHED_PAGE_SIZE);

            const esc = v => String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const bodyEl = document.getElementById('rejSchedBody');
            if (bodyEl) {
                bodyEl.innerHTML = slice.map(s => `
                    <tr class="hover:bg-red-50/30 transition">
                        <td class="py-3 px-4">
                            <div class="text-xs font-semibold text-slate-800">${esc(s.course_code)}</div>
                            <div class="text-[11px] text-slate-500 truncate max-w-[180px]">${esc(s.course_name)}</div>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-600">${esc(s.college||'—')}</td>
                        <td class="py-3 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ${s.exam_type==='Midterm'?'bg-blue-100 text-blue-700':s.exam_type==='Final'?'bg-purple-100 text-purple-700':'bg-amber-100 text-amber-700'}">${esc(s.exam_type||'—')}</span></td>
                        <td class="py-3 px-4 text-xs text-slate-600">${esc(s.exam_date||s.date||'—')}</td>
                        <td class="py-3 px-4 text-xs text-slate-600">${esc(s.room_name||'—')}</td>
                        ${showCampusCol ? `<td class="py-3 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">${esc(s.campus||s.room_campus||'—')}</span></td>` : ''}
                        <td class="py-3 px-4 text-[11px] text-slate-500 max-w-[200px] truncate" title="${esc(s.rejection_reason||s.remarks||'')}">
                            ${s.rejection_reason||s.remarks ? `<span class="italic text-red-500">"${esc((s.rejection_reason||s.remarks||'').substring(0,60))}${(s.rejection_reason||s.remarks||'').length>60?'…':''}"</span>` : '<span class="text-slate-300">—</span>'}
                        </td>
                    </tr>`).join('');
            }

            const pagerEl = document.getElementById('rejSchedPager');
            if (!pagerEl) return;
            const btnBase   = 'px-2.5 py-1 rounded text-[11px] font-medium transition';
            const btnActive = 'bg-red-600 text-white shadow-sm';
            const btnInact  = 'text-slate-600 hover:bg-slate-100';
            const maxV = 7;
            let ps = Math.max(1, _rejSchedPage - Math.floor(maxV / 2));
            let pe = Math.min(totalPages, ps + maxV - 1);
            if (pe - ps < maxV - 1) ps = Math.max(1, pe - maxV + 1);
            let pageButtons = '';
            if (ps > 1) pageButtons += `<button onclick="renderRejSchedPage(1)" class="${btnBase} ${btnInact}">1</button><span class="text-slate-300 text-xs px-1">…</span>`;
            for (let p = ps; p <= pe; p++) {
                pageButtons += `<button onclick="renderRejSchedPage(${p})" class="${btnBase} ${p === _rejSchedPage ? btnActive : btnInact}">${p}</button>`;
            }
            if (pe < totalPages) pageButtons += `<span class="text-slate-300 text-xs px-1">…</span><button onclick="renderRejSchedPage(${totalPages})" class="${btnBase} ${btnInact}">${totalPages}</button>`;

            pagerEl.innerHTML = `
                <p class="text-[10px] text-slate-400">Showing ${start + 1}–${Math.min(start + REJ_SCHED_PAGE_SIZE, total)} of ${total} rejected schedule${total !== 1 ? 's' : ''}</p>
                <div class="flex items-center gap-1">
                    <button onclick="renderRejSchedPage(${_rejSchedPage - 1})" ${_rejSchedPage === 1 ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg> Prev
                    </button>
                    <div class="flex items-center gap-0.5">${pageButtons}</div>
                    <button onclick="renderRejSchedPage(${_rejSchedPage + 1})" ${_rejSchedPage === totalPages ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                        Next <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>`;
        }
        window.renderRejSchedPage = renderRejSchedPage;

        // ─── Scheduling Conflicts Pagination ─────────────────────────────────────
        let _conflictsPage = 1;
        const CONFLICTS_PAGE_SIZE = 15;

        function renderConflictsPage(page) {
            const allConflicts = window._conflictsAll || [];
            const typeFilter   = window._conflictsType || 'all';
            const filtered     = typeFilter === 'all' ? allConflicts : allConflicts.filter(c => c.type === typeFilter);
            const total        = filtered.length;
            const conflictsTableEl = document.getElementById('conflictsTable');
            if (!conflictsTableEl) return;

            if (total === 0) {
                conflictsTableEl.innerHTML = `<div class="py-10 text-center text-slate-400 text-sm">No conflicts of this type found.</div>`;
                const pagerEl = document.getElementById('conflictsPager');
                if (pagerEl) pagerEl.innerHTML = '';
                return;
            }

            const totalPages = Math.ceil(total / CONFLICTS_PAGE_SIZE);
            _conflictsPage   = Math.max(1, Math.min(page, totalPages));
            const start      = (_conflictsPage - 1) * CONFLICTS_PAGE_SIZE;
            const slice      = filtered.slice(start, start + CONFLICTS_PAGE_SIZE);

            conflictsTableEl.innerHTML =
                `<div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead><tr class="bg-slate-50 border-b border-slate-200 text-left">
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-36">Type</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Course</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Section / College</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Issue</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Exam Date</th>
                            <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Campus</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            ${slice.map(c => `
                                <tr class="${c.bg} hover:brightness-[0.97] transition">
                                    <td class="px-4 py-3 whitespace-nowrap"><span class="inline-block text-[10px] font-bold px-2 py-1 rounded-full ${c.bdg}">${c.badge}</span></td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">${c.course}</td>
                                    <td class="px-4 py-3 text-slate-600"><span class="font-medium">${c.section}</span><span class="text-slate-400 ml-1">· ${c.college}</span></td>
                                    <td class="px-4 py-3 text-slate-600">${c.issue}</td>
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${c.date}</td>
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${c.campus}</td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>`;

            const pagerEl = document.getElementById('conflictsPager');
            if (!pagerEl) return;
            if (totalPages <= 1) {
                pagerEl.innerHTML = `<p class="text-[10px] text-slate-400">${total} conflict${total!==1?'s':''} found</p>`;
                return;
            }
            const btnBase   = 'px-2.5 py-1 rounded text-[11px] font-medium transition';
            const btnActive = 'bg-red-500 text-white shadow-sm';
            const btnInact  = 'text-slate-600 hover:bg-slate-100';
            const maxV = 7;
            let ps = Math.max(1, _conflictsPage - Math.floor(maxV / 2));
            let pe = Math.min(totalPages, ps + maxV - 1);
            if (pe - ps < maxV - 1) ps = Math.max(1, pe - maxV + 1);
            let pageButtons = '';
            if (ps > 1) pageButtons += `<button onclick="renderConflictsPage(1)" class="${btnBase} ${btnInact}">1</button><span class="text-slate-300 text-xs px-1">…</span>`;
            for (let p = ps; p <= pe; p++) {
                pageButtons += `<button onclick="renderConflictsPage(${p})" class="${btnBase} ${p === _conflictsPage ? btnActive : btnInact}">${p}</button>`;
            }
            if (pe < totalPages) pageButtons += `<span class="text-slate-300 text-xs px-1">…</span><button onclick="renderConflictsPage(${totalPages})" class="${btnBase} ${btnInact}">${totalPages}</button>`;

            pagerEl.innerHTML = `
                <p class="text-[10px] text-slate-400">Showing ${start + 1}–${Math.min(start + CONFLICTS_PAGE_SIZE, total)} of ${total} conflict${total !== 1 ? 's' : ''}</p>
                <div class="flex items-center gap-1">
                    <button onclick="renderConflictsPage(${_conflictsPage - 1})" ${_conflictsPage === 1 ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg> Prev
                    </button>
                    <div class="flex items-center gap-0.5">${pageButtons}</div>
                    <button onclick="renderConflictsPage(${_conflictsPage + 1})" ${_conflictsPage === totalPages ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                        Next <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>`;
        }
        window.renderConflictsPage = renderConflictsPage;

        window.conflictsTypeFilter = function(type) {
            window._conflictsType = type;
            _conflictsPage = 1;
            // Update pill styles
            document.querySelectorAll('#conflictTypePills [data-cpill]').forEach(btn => {
                const isActive = btn.dataset.cpill === type;
                btn.className = 'px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap ' +
                    (isActive ? 'bg-white text-red-600 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700');
            });
            renderConflictsPage(1);
        };

        window.exportProctorAnalyticsPDF = async function() {
            if (!window.jspdf) {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                    s.onload = resolve; s.onerror = reject;
                    document.head.appendChild(s);
                });
            }
            if (!window.jspdf || !window.jspdf.jsPDF) { showToast('PDF library failed to load', 'error'); return; }
            if (typeof window.jspdf.jsPDF.API.autoTable === 'undefined') {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js';
                    s.onload = resolve; s.onerror = reject;
                    document.head.appendChild(s);
                });
            }
            const { jsPDF } = window.jspdf;
            const now   = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            function inPdfDateRange(s) {
                const raw = (s.exam_date || s.date || '').trim();
                if (!raw || raw === '0000-00-00') return proctorDateFilter === 'all';
                const d = new Date(raw.substring(0, 10) + 'T00:00:00');
                if (isNaN(d.getTime())) return proctorDateFilter === 'all';
                if (proctorDateFilter === 'day')   return d >= today && d < new Date(today.getTime() + 86400000);
                if (proctorDateFilter === 'week') { const ws = new Date(today); ws.setDate(today.getDate() - today.getDay()); return d >= ws && d < new Date(ws.getTime() + 7 * 86400000); }
                if (proctorDateFilter === 'month') return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
                if (proctorDateFilter === 'year')  return d.getFullYear() === now.getFullYear();
                return true;
            }
            const myCampus = currentUser.campus || '';
            const schedules = allData.filter(d => d.type === 'schedule' && (!myCampus || d.campus === myCampus) && inPdfDateRange(d));
            const proctors  = allData.filter(d => d.type === 'proctor' && (!myCampus || (d.campus || '') === myCampus));
            const totalExams = schedules.length;
            const periodLabel = { all:'All Time', day:'Today', week:'This Week', month:'This Month', year:'This Year' }[proctorDateFilter] || 'All Time';
            const proctorMap = {};
            proctors.forEach(p => { proctorMap[String(p.id)] = { id: p.id, name: p.name || '—', campus: p.campus || '—', total: 0, approved: 0, pending: 0 }; });
            schedules.forEach(s => {
                const pids = (s.proctor_ids && s.proctor_ids.length) ? s.proctor_ids.map(String) : (s.proctor_id ? [String(s.proctor_id)] : []);
                pids.forEach(pid => {
                    if (!proctorMap[pid]) proctorMap[pid] = { id: pid, name: s.proctor_name || 'Unknown', campus: s.campus || '—', total: 0, approved: 0, pending: 0 };
                    proctorMap[pid].total++;
                    if (s.status === 'Approved') proctorMap[pid].approved++;
                    if (s.status === 'Pending' || !s.status) proctorMap[pid].pending++;
                });
            });
            const rows = Object.values(proctorMap).filter(p => p.total > 0).sort((a, b) => b.total - a.total);
            const generated = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + ', ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFillColor(88, 28, 135);
            doc.rect(0, 0, 297, 38, 'F');
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(17);
            doc.setTextColor(255, 255, 255);
            doc.text('FLEXAM — Proctor Analytics Report', 14, 16);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(220, 200, 255);
            doc.text(`Campus: ${myCampus || 'All'} · Period: ${periodLabel} · ${rows.length} proctors · ${totalExams} total exams`, 14, 26);
            doc.text(`Generated: ${generated}`, 14, 33);
            doc.autoTable({
                startY: 46,
                head: [['#', 'Proctor Name', 'Campus', 'Total Exams', 'Approved', 'Pending', '% Share']],
                body: rows.map((p, i) => [i + 1, p.name, p.campus, p.total, p.approved, p.pending, (totalExams > 0 ? Math.round((p.total / totalExams) * 100) : 0) + '%']),
                styles: { fontSize: 9, cellPadding: 3 },
                headStyles: { fillColor: [88, 28, 135], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [248, 245, 255] },
                columnStyles: { 0: { halign: 'center', cellWidth: 10 }, 3: { halign: 'center' }, 4: { halign: 'center' }, 5: { halign: 'center' }, 6: { halign: 'center' } }
            });
            doc.save(`proctor-analytics-${myCampus ? myCampus.toLowerCase().replace(/\s+/g,'-') + '-' : ''}${proctorDateFilter}.pdf`);
            showToast('PDF downloaded!', 'success');
        };
        
        // ─────────────────────────────────────────────────────────────────────────
        function renderDashboardStat(title, value, path, color) {
            const colors = { emerald: 'bg-emerald-50 text-emerald-600', cyan: 'bg-cyan-50 text-cyan-600', lime: 'bg-lime-50 text-lime-600' };
            return `<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex justify-between items-center text-left">
                <div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">${title}</p><h3 class="text-3xl font-bold text-slate-800">${value}</h3></div>
                <div class="w-12 h-12 ${colors[color]} rounded-lg flex items-center justify-center"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="${path}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            </div>`;
        }

        function renderIconAction(label, iconPath, color, onclickFn) {
            const schemes = { emerald: 'bg-emerald-500 shadow-emerald-200', blue: 'bg-blue-500 shadow-blue-200', cyan: 'bg-cyan-500 shadow-cyan-200', purple: 'bg-purple-500 shadow-purple-200', amber: 'bg-amber-500 shadow-amber-200', green: 'bg-green-500 shadow-green-200' };
            return `<div class="text-center cursor-pointer group p-2 quick-icon-card" onclick="${onclickFn || ''}">
                <div class="${schemes[color]} w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-2 shadow-lg transition-all group-hover:scale-105 group-hover:brightness-110">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="${iconPath}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <p class="text-xs font-semibold text-slate-600 group-hover:text-slate-900 transition-colors truncate text-center">${label}</p>
            </div>`;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 14. MODAL HELPERS
        // ─────────────────────────────────────────────────────────────────────────
        // ── Campus Admin: lock all campus selects in every modal ─────────────────
        function lockCampusSelects() {
            document.querySelectorAll('select[name="campus"]').forEach(sel => {
                if (sel.type === 'hidden') return;
                sel.value = currentUser.campus;
                sel.innerHTML = `<option value="${currentUser.campus}" selected>${currentUser.campus}</option>`;
                sel.disabled  = true;
                sel.style.background = '#f1f5f9';
                sel.style.color      = '#475569';
                sel.style.cursor     = 'not-allowed';
                // Fire change event so dependent dropdowns (rooms, proctors) filter correctly
                sel.dispatchEvent(new Event('change'));
            });
            // Also set any plain <input name="campus"> fields
            document.querySelectorAll('input[name="campus"]:not([type="hidden"])').forEach(inp => {
                inp.value    = currentUser.campus;
                inp.readOnly = true;
                inp.style.background = '#f1f5f9';
            });
            // Pre-filter rooms and proctors in schedule modal if open
            const roomSelect = document.getElementById('roomSelect');
            if (roomSelect && typeof getRoomOptions === 'function') {
                roomSelect.innerHTML = '<option value="" disabled selected>Select Room</option>' +
                                       getRoomOptions('', currentUser.campus);
            }
            const proctorSel = document.querySelector('#addScheduleModal select[name="proctor"]');
            if (proctorSel) {
                const fp = allData.filter(d => d.type === 'proctor' && (!currentUser.campus || (d.campus || '') === currentUser.campus));
                proctorSel.innerHTML = `<option value="">No Proctor</option>` +
                    fp.map(p => `<option value="${dbId(p.id)}">${p.name}${p.campus ? ' (' + p.campus + ')' : ''}</option>`).join('');
            }
        }

        function openModal(id)  {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
            // Lock campus selects immediately after the modal DOM is visible
            setTimeout(lockCampusSelects, 0);
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
            if (id === 'addUserModal') {
                const form = document.getElementById('addUserForm');
                if (form) form.reset();
                // Password strength
                const reqBox = document.getElementById('add-pass-requirements');
                if (reqBox) reqBox.classList.add('hidden');
                // Program Head section
                const assignment = document.getElementById('add-assignment');
                if (assignment) assignment.classList.add('hidden');
                // Campus badge
                const badge = document.getElementById('add-campus-badge');
                if (badge) { badge.textContent = ''; badge.classList.add('hidden'); }
                // Generic error
                const err = document.getElementById('add-error');
                if (err) { err.textContent = ''; err.classList.add('hidden'); }
                // Duplicate warning
                const dupWarn = document.getElementById('add-duplicate-warning');
                if (dupWarn) { dupWarn.classList.add('hidden'); dupWarn.classList.remove('flex'); }
                const dupText = document.getElementById('add-duplicate-warning-text');
                if (dupText) dupText.innerHTML = '';
                // Re-enable submit button
                const submitBtn = document.getElementById('add-submit-btn');
                if (submitBtn) { submitBtn.disabled = false; submitBtn.classList.remove('opacity-50','cursor-not-allowed'); }
                // Cascading dropdowns
                const col  = document.getElementById('add-college-sel');
                const prog = document.getElementById('add-program-sel');
                if (col)  { col.innerHTML  = '<option value="">— Select campus first —</option>'; col.disabled  = false; }
                if (prog) { prog.innerHTML = '<option value="">— Select college first —</option>'; prog.disabled = true; }
                // Wiring flag
                const campusSel = document.querySelector('#addUserForm [name="campus"]');
                if (campusSel) campusSel._badgeWired = false;
            }
        }

        function togglePass(inputId) {
            const inp = document.getElementById(inputId);
            inp.type = inp.type === 'password' ? 'text' : 'password';
        }

        function toggleAssignment(prefix) {
            const role    = document.getElementById(`${prefix}-role`).value;
            const section = document.getElementById(`${prefix}-assignment`);
            if (section) section.classList.toggle('hidden', role !== 'Program Head');

            if (role === 'Program Head') {
                updateCampusBadge(prefix);

                const campusSel = document.querySelector(`#${prefix}UserForm [name="campus"], #${prefix}UserModal [name="campus"]`);
                if (campusSel && !campusSel._badgeWired) {
                    campusSel.addEventListener('change', () => {
                        updateCampusBadge(prefix);
                        if (prefix === 'add') populateAddColleges();
                        if (prefix === 'edit') populateEditColleges();
                    });
                    campusSel._badgeWired = true;
                }
                // Populate colleges immediately when toggled on
                if (prefix === 'add') populateAddColleges();
                if (prefix === 'edit') populateEditColleges();
            }
        }

        // ── Helper: build college options for a campus ────────────────────────
        function getCollegesForCampus(campus) {
            return allData
                .filter(d => d.type === 'college' && (d.campus || '').trim() === campus)
                .sort((a, b) => (a.code || '').localeCompare(b.code || ''));
        }

        function buildCollegeOptions(colleges, selectedCode) {
            if (!colleges.length) return '<option value="">No colleges on record</option>';
            return '<option value="">— Select college —</option>' +
                colleges.map(c => {
                    const sel = selectedCode && c.code === selectedCode ? 'selected' : '';
                    const label = c.name ? `${c.code} — ${c.name}` : c.code;
                    return `<option value="${c.code}" ${sel} data-programs='${JSON.stringify(c.programs || [])}'>${label}</option>`;
                }).join('');
        }

        function buildProgramOptions(programs, selectedCode) {
            if (!programs.length) return '<option value="">No programs on record</option>';
            return '<option value="">— Select program —</option>' +
                programs.map(p => {
                    const parts = p.split('=');
                    const code  = parts[0].trim();
                    const label = parts[1] ? `${code} — ${parts[1].trim()}` : code;
                    const sel   = selectedCode && code === selectedCode ? 'selected' : '';
                    return `<option value="${code}" ${sel}>${label}</option>`;
                }).join('');
        }

        // ── Add User: populate colleges on campus change ──────────────────────
        function populateAddColleges(selectedCollege = '', selectedProgram = '') {
            const campusSel  = document.querySelector('#addUserForm [name="campus"]');
            const collegeSel = document.getElementById('add-college-sel');
            const programSel = document.getElementById('add-program-sel');
            if (!campusSel || !collegeSel || !programSel) return;

            const campus   = campusSel.value;
            const colleges = getCollegesForCampus(campus);
            collegeSel.innerHTML = buildCollegeOptions(colleges, selectedCollege);
            collegeSel.disabled  = !campus || !colleges.length;

            // Restore programs if a college is pre-selected
            programSel.innerHTML = '<option value="">— Select college first —</option>';
            programSel.disabled  = true;
            if (selectedCollege) {
                const col = colleges.find(c => c.code === selectedCollege);
                if (col) {
                    programSel.innerHTML = buildProgramOptions(col.programs || [], selectedProgram);
                    programSel.disabled  = false;
                }
            }
        }

        function syncAddPrograms() {
            const collegeSel = document.getElementById('add-college-sel');
            const programSel = document.getElementById('add-program-sel');
            if (!collegeSel || !programSel) return;
            const opt = collegeSel.options[collegeSel.selectedIndex];
            if (!opt || !opt.value) {
                programSel.innerHTML = '<option value="">— Select college first —</option>';
                programSel.disabled  = true;
                return;
            }
            let progs = [];
            try { progs = JSON.parse(opt.getAttribute('data-programs') || '[]'); } catch(e) {}
            programSel.innerHTML = buildProgramOptions(progs, '');
            programSel.disabled  = !progs.length;
        }

        // ── Edit User: populate colleges on campus change ─────────────────────
        function populateEditColleges(selectedCollege = '', selectedProgram = '') {
            const campusSel  = document.getElementById('edit-campus');
            const collegeSel = document.getElementById('edit-college');
            const programSel = document.getElementById('edit-program');
            if (!campusSel || !collegeSel || !programSel) return;

            const campus   = campusSel.value;
            const colleges = getCollegesForCampus(campus);
            collegeSel.innerHTML = buildCollegeOptions(colleges, selectedCollege);
            collegeSel.disabled  = !campus || !colleges.length;

            programSel.innerHTML = '<option value="">— Select college first —</option>';
            programSel.disabled  = true;
            if (selectedCollege) {
                const col = colleges.find(c => c.code === selectedCollege);
                if (col) {
                    programSel.innerHTML = buildProgramOptions(col.programs || [], selectedProgram);
                    programSel.disabled  = false;
                }
            }
        }

        function syncEditPrograms() {
            const collegeSel = document.getElementById('edit-college');
            const programSel = document.getElementById('edit-program');
            if (!collegeSel || !programSel) return;
            const opt = collegeSel.options[collegeSel.selectedIndex];
            if (!opt || !opt.value) {
                programSel.innerHTML = '<option value="">— Select college first —</option>';
                programSel.disabled  = true;
                return;
            }
            let progs = [];
            try { progs = JSON.parse(opt.getAttribute('data-programs') || '[]'); } catch(e) {}
            programSel.innerHTML = buildProgramOptions(progs, '');
            programSel.disabled  = !progs.length;
        }

        function updateCampusBadge(prefix) {
            const badge     = document.getElementById(`${prefix}-campus-badge`);
            const campusSel = document.querySelector(`#${prefix}UserForm [name="campus"], #${prefix}UserModal [name="campus"]`);
            if (!badge || !campusSel) return;
            const campus = campusSel.value;
            if (campus) {
                badge.textContent = '📍 ' + campus;
                badge.classList.remove('hidden');
            } else {
                badge.textContent = '';
                badge.classList.add('hidden');
            }
        }

        function showError(elementId, msg) {
            const el = document.getElementById(elementId);
            if (!el) return;
            el.textContent = msg;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 5000);
        }

        function showToast(msg, type = 'success', duration = 3500) {
            const existing = document.querySelector('.toast');
            if (existing) existing.remove();
            const t = document.createElement('div');
            t.className = `toast ${type === 'success' ? 'bg-emerald-700' : 'bg-red-600'}`;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), duration);
        }

        function esc(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 15. USERS API & TABLE
        // ─────────────────────────────────────────────────────────────────────────
        let allUsers = [];
        let pendingDeleteId = null;

        async function usersApi(action, body = null) {
            const opts = { method: body ? 'POST' : 'GET', headers: {'Content-Type':'application/json'} };
            if (body) opts.body = JSON.stringify(body);
            const res = await fetch(`../api/users.php?action=${action}`, opts);
            return res.json();
        }

        async function loadUsers() {
            const result = await usersApi('list');
            if (!result.success) { showToast('Failed to load users', 'error'); return; }
            // Campus Admin: only show users from own campus
            allUsers = currentUser.campus
                ? (result.data || []).filter(u => (u.campus || '').trim() === currentUser.campus.trim())
                : (result.data || []);
            renderStats();
            filterTable();
        }

        function renderStats() {
            const statsRow = document.getElementById('statsRow');
            if (!statsRow) return;
            const campus = campusFilters['users'] || '';
            const filtered = campus ? allUsers.filter(u => u.campus === campus) : allUsers;
            const total  = filtered.length;
            const admins = filtered.filter(u => u.role === 'Admin').length;
            const heads  = filtered.filter(u => u.role === 'Program Head').length;
            statsRow.innerHTML = [
                { label: 'Total Users',   val: total,  numCls: 'text-slate-800',   iconCls: 'bg-emerald-50 text-emerald-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>' },
                { label: 'Admins',        val: admins, numCls: 'text-emerald-600', iconCls: 'bg-emerald-50 text-emerald-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' },
                { label: 'Program Heads', val: heads,  numCls: 'text-blue-600',    iconCls: 'bg-blue-50 text-blue-600',       icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>' },
                { label: 'Active',        val: total,  numCls: 'text-slate-800',   iconCls: 'bg-slate-100 text-slate-500',    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' },
            ].map(({ label, val, numCls, iconCls, icon }) => `
                <div class="stat-card-small">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">${label}</p>
                        <h3 class="text-2xl font-bold ${numCls}">${val}</h3>
                    </div>
                    <div class="w-9 h-9 ${iconCls} rounded-md flex items-center justify-center shrink-0 ml-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icon}</svg>
                    </div>
                </div>`
            ).join('');
        }

        function usersTabFilter() {
            pageState['users'] = 1;
            renderStats();
            filterTable();
        }

        function filterTable() {
            const searchEl  = document.getElementById('searchInput');
            const roleEl    = document.getElementById('roleFilter');
            if (!searchEl || !roleEl) return;
            const search = searchEl.value.toLowerCase();
            const role   = roleEl.value;
            const campus = campusFilters['users'] || '';
            const filtered = allUsers.filter(u =>
                (!search || u.full_name.toLowerCase().includes(search) ||
                            u.username.toLowerCase().includes(search) ||
                            (u.email||'').toLowerCase().includes(search)) &&
                (!role   || u.role === role) &&
                (!campus || (u.campus||'') === campus)
            );
            pageState['users'] = 1;
            renderStats();
            renderTable(filtered);
        }

        function renderTable(users) {
            const tbody       = document.getElementById('usersTableBody');
            const tableFooter = document.getElementById('tableFooter');
            if (!tbody) return;
            if (users.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="py-16 text-center text-slate-400">No users found.</td></tr>`;
                if (tableFooter) tableFooter.textContent = 'Showing 0 users';
                const pagerEl = document.getElementById('pager-users');
                if (pagerEl) pagerEl.innerHTML = '';
                return;
            }
            // Store full list for pagination, render first page
            const page = pageState['users'] || 1;
            const start = (page - 1) * PAGE_SIZE;
            const paged = users.slice(start, start + PAGE_SIZE);
            tbody.innerHTML = paged.map((u, i) => `
                <tr class="hover:bg-slate-50 transition" data-id="${u.id}">
                    <td class="px-5 py-3 text-slate-400 text-xs">${i + 1}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 ${u.role === 'Admin' ? 'bg-emerald-600' : 'bg-blue-600'}">
                                ${u.full_name.charAt(0).toUpperCase()}
                            </div>
                            <span class="font-semibold text-slate-800">${esc(u.full_name)}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-slate-600 font-mono text-xs">${esc(u.username)}</td>
                    <td class="px-5 py-3">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold ${u.role === 'Admin' ? 'badge-admin' : 'badge-head'}">
                            ${esc(u.role)}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-slate-600">${esc(u.email || '—')}</td>
                    <td class="px-5 py-3">${u.campus ? `<span class="campus-badge">${esc(u.campus)}</span>` : '<span class="text-slate-400">—</span>'}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            <button onclick="openUserLogs(${u.id})" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="View Logs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                            </button>
                            <button onclick="openEdit(${u.id})" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button onclick="openDelete(${u.id}, '${esc(u.username)}')" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>`
            ).join('');
            if (tableFooter) tableFooter.textContent = `Showing 1–${Math.min(PAGE_SIZE, users.length)} of ${users.length} users`;
            showGlobalPager('users', users.length, PAGE_SIZE);
            // Store for goPage usage
            window._usersFiltered = users;
        }

        function checkAddPassStrength(val) {
            const reqBox = document.getElementById('add-pass-requirements');
            if (!reqBox) return;
            if (val.length === 0) { reqBox.classList.add('hidden'); return; }
            reqBox.classList.remove('hidden');
            const hasLength  = val.length >= 8;
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(val);
            const iconOk  = `<svg class="w-3.5 h-3.5 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
            const iconBad = `<svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/></svg>`;
            document.getElementById('add-req-length').innerHTML  = `${hasLength  ? iconOk : iconBad}<span class="${hasLength  ? 'text-emerald-600' : 'text-slate-400'}">At least 8 characters</span>`;
            document.getElementById('add-req-special').innerHTML = `${hasSpecial ? iconOk : iconBad}<span class="${hasSpecial ? 'text-emerald-600' : 'text-slate-400'}">At least 1 special character (!@#$%^&amp;*...)</span>`;
        }

        function checkAddUserDuplicate() {
            const form      = document.getElementById('addUserForm');
            if (!form) return;
            const name      = (form.full_name?.value || '').trim().toLowerCase();
            const email     = (form.email?.value     || '').trim().toLowerCase();
            const warning   = document.getElementById('add-duplicate-warning');
            const warnText  = document.getElementById('add-duplicate-warning-text');
            const submitBtn = document.getElementById('add-submit-btn');
            if (!warning || !warnText || !submitBtn) return;

            // Need at least a name to check; clear warning if nothing entered yet
            if (!name) {
                warning.classList.add('hidden');
                warning.classList.remove('flex');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                return;
            }

            // Block if same name exists anywhere in the system (any campus)
            // If email is also filled in, BOTH name AND email must match to flag
            const duplicate = allUsers.find(u => {
                const sameName  = (u.full_name || '').trim().toLowerCase() === name;
                if (!sameName) return false;
                if (email) {
                    // Name + email both match → definite duplicate
                    return (u.email || '').trim().toLowerCase() === email;
                }
                // Name alone matches → flag as potential duplicate
                return true;
            });

            if (duplicate) {
                let msg = `An account named <strong>${esc(duplicate.full_name)}</strong>`;
                if (email && (duplicate.email || '').trim().toLowerCase() === email) {
                    msg += ` with email <strong>${esc(duplicate.email)}</strong>`;
                }
                msg += ` already exists`;
                if (duplicate.campus) msg += ` (${esc(duplicate.campus)})`;
                msg += `. Duplicate accounts are not allowed across any campus.`;
                warnText.innerHTML = msg;
                warning.classList.remove('hidden');
                warning.classList.add('flex');
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                warning.classList.add('hidden');
                warning.classList.remove('flex');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        async function submitAddUser(e) {
            e.preventDefault();
            const form = e.target;
            const btn  = document.getElementById('add-submit-btn');

            // Validate special character requirement
            const password = form.password.value;
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(password)) {
                showError('add-error', 'Password must contain at least one special character (e.g. !@#$%^&*).');
                return;
            }

            // Final duplicate guard before hitting the API (campus-agnostic)
            const _newName  = form.full_name.value.trim().toLowerCase();
            const _newEmail = form.email.value.trim().toLowerCase();
            const _dupCheck = allUsers.find(u => {
                const sameName = (u.full_name || '').trim().toLowerCase() === _newName;
                if (!sameName) return false;
                if (_newEmail) return (u.email || '').trim().toLowerCase() === _newEmail;
                return true;
            });
            if (_dupCheck) {
                let dupMsg = `An account named "${_dupCheck.full_name}"`;
                if (_newEmail && (_dupCheck.email||'').trim().toLowerCase() === _newEmail) dupMsg += ` with email "${_dupCheck.email}"`;
                dupMsg += ` already exists`;
                if (_dupCheck.campus) dupMsg += ` (${_dupCheck.campus})`;
                dupMsg += `. Duplicate accounts are not allowed across any campus.`;
                showError('add-error', dupMsg);
                return;
            }

            btn.disabled = true; btn.textContent = 'Creating…';
            const data = {
                full_name : form.full_name.value.trim(),
                username  : form.username.value.trim(),
                password  : form.password.value,
                role      : form.role.value,
                email     : form.email.value.trim(),
                campus    : form.campus.value,
                college   : form.college?.value.trim() || '',
                program   : form.program?.value.trim() || '',
            };
            const result = await usersApi('create', data);
            btn.disabled = false; btn.textContent = 'Create User';
            if (result.success) {
                activityLog('User Created', `${data.full_name} (${data.role}) · ${data.campus}`, data.full_name);
                showToast(result.message);
                closeModal('addUserModal');
                form.reset();
                document.getElementById('add-assignment').classList.add('hidden');
                const addBadge = document.getElementById('add-campus-badge');
                if (addBadge) { addBadge.textContent = ''; addBadge.classList.add('hidden'); }
                // Reset wired flag so badge re-wires next time
                const addCampusSel = document.querySelector('#addUserForm [name="campus"]');
                if (addCampusSel) addCampusSel._badgeWired = false;
                loadUsers();
                // Reset cascaded dropdowns
                const addCol = document.getElementById('add-college-sel');
                const addProg = document.getElementById('add-program-sel');
                if (addCol)  { addCol.innerHTML  = '<option value="">— Select campus first —</option>'; addCol.disabled  = false; }
                if (addProg) { addProg.innerHTML = '<option value="">— Select college first —</option>'; addProg.disabled = true; }
            } else {
                showError('add-error', result.message);
            }
        }

        function openEdit(id) {
            const u = allUsers.find(u => u.id == id);
            if (!u) return;
            document.getElementById('edit-id').value       = u.id;
            document.getElementById('edit-fullname').value = u.full_name;
            document.getElementById('edit-username').value = u.username;
            document.getElementById('edit-pass').value     = '';
            document.getElementById('edit-role').value     = u.role;
            document.getElementById('edit-email').value    = u.email || '';
            document.getElementById('edit-campus').value   = u.campus || '';
            document.getElementById('edit-assignment').classList.toggle('hidden', u.role !== 'Program Head');
            document.getElementById('edit-error').classList.add('hidden');

            if (u.role === 'Program Head') {
                updateCampusBadge('edit');
                const campusSel = document.getElementById('edit-campus');
                if (campusSel && !campusSel._badgeWired) {
                    campusSel.addEventListener('change', () => {
                        updateCampusBadge('edit');
                        populateEditColleges();
                    });
                    campusSel._badgeWired = true;
                }
                // Populate dropdowns with user's existing college & program pre-selected
                populateEditColleges(u.college || '', u.program || '');
            }
            openModal('editUserModal');
        }

        async function submitEditUser(e) {
            e.preventDefault();
            const form = e.target;
            const btn  = document.getElementById('edit-submit-btn');
            btn.disabled = true; btn.textContent = 'Saving…';
            const data = {
                id        : parseInt(form.id.value),
                full_name : form.full_name.value.trim(),
                username  : form.username.value.trim(),
                password  : form.password.value,
                role      : form.role.value,
                email     : form.email.value.trim(),
                campus    : form.campus.value,
                college   : form.college?.value.trim() || '',
                program   : form.program?.value.trim() || '',
            };
            const result = await usersApi('update', data);
            btn.disabled = false; btn.textContent = 'Save Changes';
            if (result.success) {
                activityLog('User Updated', `${data.full_name} (${data.role}) · ${data.campus}`, data.full_name);
                showToast(result.message);
                closeModal('editUserModal');
                loadUsers();
            } else {
                showError('edit-error', result.message);
            }
        }

        function openDelete(id, username) {
            pendingDeleteId = id;
            document.getElementById('delete-username-display').textContent = username;
            openModal('deleteUserModal');
        }

        async function confirmDelete() {
            if (!pendingDeleteId) return;
            const btn = document.getElementById('confirm-delete-btn');
            btn.disabled = true; btn.textContent = 'Deleting…';
            const delUser = allUsers.find(u => u.id === pendingDeleteId);
            const result = await usersApi('delete', { id: pendingDeleteId });
            btn.disabled = false; btn.textContent = 'Delete';
            if (result.success) {
                if (delUser) activityLog('User Deleted', `${delUser.full_name} (${delUser.role}) · ${delUser.campus}`, delUser.full_name);
                showToast(result.message);
                closeModal('deleteUserModal');
                loadUsers();
            } else {
                closeModal('deleteUserModal');
                showToast(result.message, 'error');
            }
            pendingDeleteId = null;
        }

        // ─────────────────────────────────────────────────────────────────────────
        // USER LOGS PANEL
        // ─────────────────────────────────────────────────────────────────────────
        function openUserLogs(userId) {
            const u = allUsers.find(u => u.id == userId);
            if (!u) return;
            const logs = getAllLogs().filter(l =>
                l.actor === u.full_name ||
                (l.targetUser && l.targetUser === u.full_name)
            );
            const existing = document.getElementById('userLogsPanel');
            if (existing) existing.remove();
            const panel = document.createElement('div');
            panel.id = 'userLogsPanel';
            panel.className = 'fixed inset-0 z-[300] flex';
            panel.innerHTML = `
                <div class="flex-1 bg-black/40" onclick="document.getElementById('userLogsPanel').remove()"></div>
                <div class="w-full max-w-md bg-white shadow-2xl flex flex-col h-full overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100 bg-slate-50 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold ${u.role === 'Admin' ? 'bg-emerald-600' : 'bg-blue-600'}">${u.full_name.charAt(0).toUpperCase()}</div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-slate-800 truncate">${esc(u.full_name)}</p>
                            <p class="text-xs text-slate-500">${esc(u.role)} · ${esc(u.campus || '—')}</p>
                        </div>
                        <button onclick="document.getElementById('userLogsPanel').remove()" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-3 border-b border-slate-100 flex items-center justify-between">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">${logs.length} Activity Record${logs.length !== 1 ? 's' : ''}</p>
                    </div>
                    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
                        ${logs.length === 0
                            ? `<div class="py-16 text-center text-slate-400 text-sm">No activity recorded yet.</div>`
                            : logs.map(l => renderLogEntry(l)).join('')
                        }
                    </div>
                </div>`;
            document.body.appendChild(panel);
        }

        function renderLogEntry(l) {
            const { icon, color, bg } = logActionMeta(l.action);
            const dt = new Date(l.timestamp);
            const timeStr = dt.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
            return `<div class="flex gap-3 items-start p-3 rounded-xl border border-slate-100 bg-white hover:bg-slate-50 transition">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${bg}">
                    <svg class="w-4 h-4 ${color}" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icon}</svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-800">${esc(l.action)}</p>
                        <span class="text-[10px] text-slate-400 whitespace-nowrap shrink-0">${timeStr}</span>
                    </div>
                    ${l.details ? `<p class="text-xs text-slate-500 mt-0.5 truncate" title="${esc(l.details)}">${esc(l.details)}</p>` : ''}
                    <p class="text-[10px] text-slate-400 mt-0.5">by <span class="font-medium">${esc(l.actor)}</span> · ${esc(l.actorCampus || 'N/A')}</p>
                </div>
            </div>`;
        }

        window.renderLogsView = function() {
            const search     = (document.getElementById('logsSearch')?.value || '').toLowerCase();
            const roleF      = document.getElementById('logsRoleFilter')?.value || '';
            const campusF    = document.getElementById('logsCampusFilter')?.value || '';
            const actionF    = document.getElementById('logsActionFilter')?.value || '';

            let logs = getAllLogs().filter(l => {
                const matchSearch = !search ||
                    (l.actor || '').toLowerCase().includes(search) ||
                    (l.action || '').toLowerCase().includes(search) ||
                    (l.details || '').toLowerCase().includes(search);
                const matchRole   = !roleF   || l.actorRole === roleF;
                const matchCampus = !campusF || (l.actorCampus || '') === campusF;
                const matchAction = !actionF || (l.action || '').includes(actionF);
                return matchSearch && matchRole && matchCampus && matchAction;
            });

            // Stats
            const statsRow = document.getElementById('logsStatsRow');
            if (statsRow) {
                const all = getAllLogs();
                const byAdmin = all.filter(l => l.actorRole === 'Admin').length;
                const byHead  = all.filter(l => l.actorRole === 'Program Head').length;
                const today   = all.filter(l => new Date(l.timestamp).toDateString() === new Date().toDateString()).length;
                statsRow.innerHTML = [
                    { label: 'Total Events',     val: all.length, numCls: 'text-slate-800',   iconCls: 'bg-slate-100 text-slate-500',   icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>' },
                    { label: 'By Admins',        val: byAdmin,    numCls: 'text-emerald-600', iconCls: 'bg-emerald-50 text-emerald-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>' },
                    { label: 'By Program Heads', val: byHead,     numCls: 'text-blue-600',    iconCls: 'bg-blue-50 text-blue-600',      icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>' },
                    { label: "Today's Events",   val: today,      numCls: 'text-amber-600',   iconCls: 'bg-amber-50 text-amber-600',    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>' },
                ].map(({ label, val, numCls, iconCls, icon }) => `
                    <div class="stat-card-small">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">${label}</p>
                            <h3 class="text-2xl font-bold ${numCls}">${val}</h3>
                        </div>
                        <div class="w-9 h-9 ${iconCls} rounded-md flex items-center justify-center shrink-0 ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icon}</svg>
                        </div>
                    </div>`
                ).join('');
            }

            const countEl = document.getElementById('logsCount');
            if (countEl) countEl.textContent = `${logs.length} record${logs.length !== 1 ? 's' : ''}`;

            const body = document.getElementById('logsTableBody');
            if (!body) return;
            if (logs.length === 0) {
                body.innerHTML = `<div class="py-16 text-center text-slate-400 text-sm">No activity logs match your filters.</div>`;
                return;
            }

            // Group by date
            const grouped = {};
            logs.forEach(l => {
                const day = new Date(l.timestamp).toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                if (!grouped[day]) grouped[day] = [];
                grouped[day].push(l);
            });

            body.innerHTML = Object.entries(grouped).map(([day, entries]) => `
                <div>
                    <div class="px-5 py-2 bg-slate-50 border-b border-slate-100">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">${day}</p>
                    </div>
                    <div class="divide-y divide-slate-50">
                        ${entries.map(l => {
                            const { icon, color, bg } = logActionMeta(l.action);
                            const time = new Date(l.timestamp).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', hour12: true });
                            const roleBadge = l.actorRole === 'Admin'
                                ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Admin</span>'
                                : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">Program Head</span>';
                            return `<div class="flex items-start gap-4 px-5 py-3.5 hover:bg-slate-50 transition">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5 ${bg}">
                                    <svg class="w-4 h-4 ${color}" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icon}</svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-800">${esc(l.action)}</p>
                                        ${roleBadge}
                                        ${l.actorCampus ? `<span class="campus-badge text-[10px]">${esc(l.actorCampus)}</span>` : ''}
                                    </div>
                                    ${l.details ? `<p class="text-xs text-slate-500 mt-0.5" title="${esc(l.details)}">${esc(l.details)}</p>` : ''}
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        <span class="font-semibold text-slate-600">${esc(l.actor)}</span>
                                        <span class="mx-1">·</span>
                                        <span>${time}</span>
                                    </p>
                                </div>
                            </div>`;
                        }).join('')}
                    </div>
                </div>
            `).join('');
        };

        function logActionMeta(action) {
            const a = (action || '').toLowerCase();
            if (a.includes('approv'))  return { bg: 'bg-emerald-50', color: 'text-emerald-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' };
            if (a.includes('reject'))  return { bg: 'bg-red-50',     color: 'text-red-500',     icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' };
            if (a.includes('delet'))   return { bg: 'bg-red-50',     color: 'text-red-500',     icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>' };
            if (a.includes('creat'))   return { bg: 'bg-blue-50',    color: 'text-blue-600',    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>' };
            if (a.includes('updat') || a.includes('edit')) return { bg: 'bg-amber-50', color: 'text-amber-600', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>' };
            if (a.includes('block'))   return { bg: 'bg-orange-50',  color: 'text-orange-600',  icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>' };
            if (a.includes('unblock')) return { bg: 'bg-teal-50',    color: 'text-teal-600',    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>' };
            if (a.includes('import'))  return { bg: 'bg-purple-50',  color: 'text-purple-600',  icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>' };
            if (a.includes('login'))   return { bg: 'bg-slate-100',  color: 'text-slate-600',   icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>' };
            return { bg: 'bg-slate-100', color: 'text-slate-500', icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>' };
        }
        // ─────────────────────────────────────────────────────────────────────────
        function campusSelectHtml(name, selected = '') {
            // Campus admin: always locked to their campus
            return `<select name="${name}" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm bg-slate-100" required disabled>
                <option value="${currentUser.campus}" selected>${currentUser.campus}</option>
            </select>
            <input type="hidden" name="${name}" value="${currentUser.campus}">`;
        }

        function openAddCollegeModal() {
        if (document.getElementById('addCollegeModal')) return;
        const modal = document.createElement('div');
        modal.id = 'addCollegeModal';
        modal.className = 'fixed inset-0 bg-black/50 z-50 overflow-y-auto';
        modal.innerHTML = `
    <div class="min-h-screen px-4 py-8 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="flex justify-between items-center px-8 py-6 border-b border-slate-200">
                <h2 class="text-2xl font-bold text-[#1e293b]">Add College</h2>
                <button type="button" onclick="closeAddCollegeModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="addCollegeForm" class="px-8 py-6 space-y-5">

                <!-- College Name + Code -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5 text-left">
                        <label class="block text-sm font-medium text-slate-700">College Name <span class="text-red-500">*</span></label>
                        <input type="text" name="collegeName" placeholder="e.g., College of Computer Studies"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                    </div>
                    <div class="space-y-1.5 text-left">
                        <label class="block text-sm font-medium text-slate-700">College Code <span class="text-red-500">*</span></label>
                        <input type="text" name="collegeCode" placeholder="e.g., CCS"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm uppercase" required>
                    </div>
                </div>

                <!-- Programs -->
                <div class="space-y-1.5 text-left">
                    <label class="block text-sm font-medium text-slate-700">Programs <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" id="progAcronym" placeholder="Acronym (e.g. BSIT)"
                            class="w-1/3 px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <input type="text" id="progFullName" placeholder="Full Name (e.g. BS in Criminology)"
                            class="flex-1 px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                    <div id="programsList" class="space-y-1.5 min-h-[32px]"></div>
                    <input type="hidden" name="programs" id="programsHidden">
                </div>

                <!-- Campus -->
                <div class="space-y-1.5 text-left">
                    <label class="block text-sm font-medium text-slate-700">Campus <span class="text-red-500">*</span></label>
                    ${campusSelectHtml('campus')}
                </div>

                <!-- Error banner -->
                <div id="addCollegeErr" class="hidden text-red-600 text-sm bg-red-50 border border-red-100 px-3 py-2 rounded-lg"></div>

                <!-- Buttons -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" id="addCollegeBtn"
                        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                        Create College
                    </button>
                    <button type="button" onclick="closeAddCollegeModal()"
                        class="px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200 text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);

    // Wire up Enter key on program inputs
    ['progAcronym','progFullName'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); addProgramEntry(); }
        });
    });



        document.getElementById('addCollegeForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd  = new FormData(e.target);
        const btn = document.getElementById('addCollegeBtn');
        const err = document.getElementById('addCollegeErr');
        btn.disabled = true; btn.textContent = 'Creating...';
        err.classList.add('hidden');

        // Auto-add any program typed in the fields but not yet confirmed
        if ((document.getElementById('progAcronym')?.value || '').trim()) addProgramEntry();

        const collegeName    = fd.get('collegeName').trim();
        const collegeCode    = fd.get('collegeCode').trim().toUpperCase();
        const selectedCampus = fd.get('campus');
        // Read programs directly from the DOM list (not fd, which was captured before auto-add)
        const programsArr    = [...document.querySelectorAll('#programsList [data-value]')]
                                .map(el => el.getAttribute('data-value').trim())
                                .filter(p => p);

        if (!collegeCode) {
            err.textContent = 'Please enter a college code (e.g. CCS).';
            err.classList.remove('hidden');
            btn.disabled = false; btn.textContent = 'Create College';
            return;
        }

        if (programsArr.length === 0) {
            err.textContent = 'Please add at least one program.';
            err.classList.remove('hidden');
            btn.disabled = false; btn.textContent = 'Create College';
            return;
        }

        const result = await window.flexamApi.colleges.create({
            name: collegeName, code: collegeCode,
            programs: programsArr, campus: selectedCampus
        });
        btn.disabled = false; btn.textContent = 'Create College';
        if (result.success) {
            closeAddCollegeModal();
            showToast(`Added ${collegeName} to ${selectedCampus}`);
            await refreshAllData();
        } else {
            err.textContent = result.message || 'Error saving data';
            err.classList.remove('hidden');
        }
    };
}

        function closeAddCollegeModal() { const m = document.getElementById('addCollegeModal'); if (m) m.remove(); }


        function editCollege(collegeId) {
    const college = findById('college', collegeId);
    if (!college) { showToast('College not found', 'error'); return; }
    if (document.getElementById('editCollegeModal')) return;
    const modal = document.createElement('div');
    modal.id = 'editCollegeModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';

    // Pre-build linked courses HTML (avoids nested template literal issues)
    const _editCollegeCode = (college.code || '').toUpperCase();
    const _editCollegeName = (college.name || '').toLowerCase();
    const _editLinkedCourses = allData.filter(d =>
        d.type === 'course' &&
        ((d.college || '').toUpperCase() === _editCollegeCode ||
         (d.college || '').toLowerCase() === _editCollegeName)
    );
    const _editCollegeLinkedIds = _editLinkedCourses.map(c => c.id).join(',');
    const _editCollegeCoursesHtml = _editLinkedCourses.length > 0
        ? _editLinkedCourses.map(c =>
            '<div class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-100 rounded-lg group"'
            + ' data-course-id="' + esc(String(c.id)) + '"'
            + ' data-course-code="' + esc(c.course_code || '') + '"'
            + ' data-course-name="' + esc(c.course_name || '') + '">'
            + '<div class="flex items-center gap-2 flex-1">'
            + '<span class="font-bold text-sm text-blue-700">' + esc(c.course_code || '') + '</span>'
            + (c.course_name ? '<span class="text-slate-500 text-sm"> &mdash; ' + esc(c.course_name) + '</span>' : '')
            + (c.program ? '<span class="text-[10px] px-1.5 py-0.5 bg-violet-100 text-violet-700 rounded-full font-semibold">' + esc(c.program) + '</span>' : '')
            + '</div>'
            + '<button type="button" onclick="removeCourseFromCollege(this)" class="p-1 text-slate-300 hover:text-red-500 transition">'
            + '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
            + '</button>'
            + '</div>'
          ).join('')
        : '<p class="text-xs text-slate-400 italic px-1">No courses linked yet. Search and add courses above.</p>';

    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl flex flex-col" style="max-height:90vh;">
        <div class="flex justify-between items-center px-8 py-6 border-b border-slate-200 shrink-0">
            <h2 class="text-2xl font-bold text-[#1e293b]">Edit College</h2>
            <button type="button" onclick="closeEditCollegeModal()" class="text-slate-400 hover:text-slate-600"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="editCollegeForm" class="px-8 py-6 overflow-y-auto flex-1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                <div class="space-y-2 text-left">
                    <label class="block text-sm font-medium text-slate-700">College Name *</label>
                    <input type="text" name="collegeName" value="${esc(college.name || '')}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" required>
                </div>
                <div class="space-y-2 text-left">
                    <label class="block text-sm font-medium text-slate-700">College Code *</label>
                    <input type="text" name="code" value="${esc(college.code || '')}" placeholder="e.g. CCS" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none uppercase" required>
                </div>
                <div class="space-y-2 text-left md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Programs *</label>
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="progAcronym" placeholder="Acronym (e.g. BSIT)" class="w-1/3 px-4 py-3 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <input type="text" id="progFullName" placeholder="Full Name (e.g. BS in Information Technology)" class="flex-1 px-4 py-3 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <button type="button" onclick="addProgramEntry()" class="px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition whitespace-nowrap">+ Add</button>
                    </div>
                    <div id="programsList" class="space-y-1.5 min-h-[40px]">
                        ${Array.isArray(college.programs) ? college.programs.map(p => {
                            const eqIndex  = p.indexOf('=');
                            const acronym  = eqIndex !== -1 ? p.slice(0, eqIndex).trim() : p.trim();
                            const fullName = eqIndex !== -1 ? p.slice(eqIndex + 1).trim() : '';
                            return `<div class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg group" data-value="${esc(p)}">
                                <div class="flex items-center gap-2 flex-1 program-display">
                                    <span class="font-bold text-sm text-slate-700">${esc(acronym)}</span>
                                    ${fullName ? `<span class="text-slate-400 text-sm">— ${esc(fullName)}</span>` : ''}
                                </div>
                                <div class="hidden items-center gap-2 flex-1 program-edit-row">
                                    <input type="text" class="prog-edit-acronym w-24 px-2 py-1 border border-slate-300 rounded-lg text-sm font-bold focus:ring-2 focus:ring-emerald-500 outline-none uppercase" value="${esc(acronym)}" placeholder="Acronym">
                                    <input type="text" class="prog-edit-fullname flex-1 px-2 py-1 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none" value="${esc(fullName)}" placeholder="Full Name (optional)">
                                    <button type="button" onclick="saveProgramEdit(this)" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">Save</button>
                                    <button type="button" onclick="cancelProgramEdit(this)" class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold rounded-lg transition border border-slate-200 whitespace-nowrap">Cancel</button>
                                </div>
                                <button type="button" onclick="editProgramEntry(this)" class="p-1 text-slate-400 hover:text-emerald-500 transition program-edit-btn" title="Edit program">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/></svg>
                                </button>
                                <button type="button" onclick="removeProgramEntry(this)" class="p-1 text-slate-300 hover:text-red-500 transition program-delete-btn">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>`;
                        }).join('') : ''}
                    </div>
                    <input type="hidden" name="programs" id="programsHidden" value="${Array.isArray(college.programs) ? college.programs.join('\n') : ''}">
                    <p class="text-xs text-slate-400 mt-1">Full name is optional. Format: <code class="bg-slate-100 px-1 rounded">ACRONYM=Full Name</code></p>
                </div>

                <div class="space-y-2 text-left md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none resize-none">${college.description && college.description !== 'N/A' ? esc(college.description) : ''}</textarea>
                </div>
                <div class="space-y-2 text-left md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Campus *</label>
                    ${campusSelectHtml('campus', college.campus || '')}
                </div>
            </div>
            <div id="editCollegeErr" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
        </form>
        <div class="flex gap-3 px-8 py-5 border-t border-slate-200 shrink-0 bg-white">
            <button type="submit" form="editCollegeForm" id="editCollegeBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg transition">Update College</button>
            <button type="button" onclick="closeEditCollegeModal()" class="px-8 py-3 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200">Cancel</button>
        </div>
    </div>`;
        document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            document.getElementById('progAcronym')?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); addProgramEntry(); } });
            document.getElementById('progFullName')?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); addProgramEntry(); } });
            // Close course dropdown when clicking outside
            document.addEventListener('click', function _closeCourseDropdown(e) {
                const container = document.getElementById('courseDropdownContainer');
                const input     = document.getElementById('courseSearchInput');
                if (container && input && !container.contains(e.target) && e.target !== input) {
                    container.classList.add('hidden');
                }
                if (!document.getElementById('editCollegeModal')) {
                    document.removeEventListener('click', _closeCourseDropdown);
                }
            });
            document.getElementById('editCollegeForm').onsubmit = async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const btn = document.getElementById('editCollegeBtn');
                btn.disabled = true; btn.textContent = 'Updating...';
                const newCode = (fd.get('code') || '').trim().toUpperCase();
                const result = await window.flexamApi.colleges.update({
                    id: collegeId, name: fd.get('collegeName'), code: newCode,
                    description: fd.get('description') || '',
                    programs: (fd.get('programs') || '').split('\n').map(p => p.trim()).filter(p => p),
                    campus: fd.get('campus')
                });
                if (result.success) {
                    // Sync linked courses: update each course's college to the new college code
                    const linkedIds = (fd.get('linkedCourseIds') || '').split(',').map(s => s.trim()).filter(Boolean);
                    // Also detect removed courses: any course previously linked to this college but NOT in new list → clear their college
                    const oldCode  = (college.code || '').toUpperCase();
                    const oldName  = (college.name || '').toLowerCase();
                    const prevLinked = allData.filter(d =>
                        d.type === 'course' &&
                        ((d.college || '').toUpperCase() === oldCode || (d.college || '').toLowerCase() === oldName)
                    );
                    const addedIds   = new Set(linkedIds);
                    const removedCourses = prevLinked.filter(c => !addedIds.has(String(c.id)));

                    // Update added courses college
                    for (const cid of linkedIds) {
                        const c = allData.find(d => d.type === 'course' && String(d.id) === cid);
                        if (c && (c.college || '').toUpperCase() !== newCode) {
                            await window.flexamApi.courses.update({ ...c, college: newCode });
                        }
                    }
                    // Clear removed courses' college
                    for (const c of removedCourses) {
                        await window.flexamApi.courses.update({ ...c, college: '' });
                    }

                    closeEditCollegeModal(); showToast('College updated!'); await refreshAllData();
                } else {
                    btn.disabled = false; btn.textContent = 'Update College';
                    const err = document.getElementById('editCollegeErr'); err.textContent = result.message || 'Error'; err.classList.remove('hidden');
                }
            };
        }
        function closeEditCollegeModal() { const m = document.getElementById('editCollegeModal'); if (m) m.remove(); }

        // ── College ↔ Course linking helpers ─────────────────────────────────────
        window.filterCourseDropdown = function() {
            const query = (document.getElementById('courseSearchInput')?.value || '').trim().toLowerCase();
            const container = document.getElementById('courseDropdownContainer');
            const listEl    = document.getElementById('courseDropdownList');
            if (!query || query.length < 1) { container.classList.add('hidden'); return; }

            const linked = new Set(
                [...(document.getElementById('collegeCoursesLinked')?.querySelectorAll('[data-course-id]') || [])]
                    .map(el => String(el.getAttribute('data-course-id')))
            );

            const matches = allData.filter(d =>
                d.type === 'course' &&
                !linked.has(String(d.id)) &&
                ((d.course_code || '').toLowerCase().includes(query) ||
                 (d.course_name || '').toLowerCase().includes(query))
            ).slice(0, 12);

            if (!matches.length) {
                listEl.innerHTML = '<div class="px-4 py-3 text-sm text-slate-400 italic">No courses found.</div>';
            } else {
                listEl.innerHTML = matches.map(c => {
                    const safeId   = String(c.id).replace(/'/g, '');
                    const safeCode = (c.course_code||'').replace(/'/g,"&#39;").replace(/\\/g,'');
                    const safeName = (c.course_name||'').replace(/'/g,"&#39;").replace(/\\/g,'');
                    const safeProg = (c.program||'').replace(/'/g,"&#39;");
                    return '<div class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer flex items-center gap-2 border-b border-slate-100 last:border-0 transition"'
                         + ' onclick="selectCourseFromDropdown(\'' + safeId + '\',\'' + safeCode + '\',\'' + safeName + '\',\'' + safeProg + '\')">'
                         + '<span class="font-bold text-sm text-blue-700">' + (c.course_code||'') + '</span>'
                         + '<span class="text-slate-500 text-sm truncate">— ' + (c.course_name||'') + '</span>'
                         + (c.program ? '<span class="ml-auto text-[10px] px-1.5 py-0.5 bg-violet-100 text-violet-700 rounded-full font-semibold shrink-0">' + c.program + '</span>' : '')
                         + '</div>';
                }).join('');
            }
            container.classList.remove('hidden');
        };

        window.selectCourseFromDropdown = function(id, code, name, program) {
            const listEl = document.getElementById('collegeCoursesLinked');
            if (!listEl) return;
            const emptyMsg = listEl.querySelector('p.text-slate-400');
            if (emptyMsg) emptyMsg.remove();

            const item = document.createElement('div');
            item.className = 'flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-100 rounded-lg group';
            item.setAttribute('data-course-id', id);
            item.setAttribute('data-course-code', code);
            item.setAttribute('data-course-name', name);
            item.innerHTML = '<div class="flex items-center gap-2 flex-1">'
                + '<span class="font-bold text-sm text-blue-700">' + code + '</span>'
                + (name ? '<span class="text-slate-500 text-sm">\u2014 ' + name + '</span>' : '')
                + (program ? '<span class="text-[10px] px-1.5 py-0.5 bg-violet-100 text-violet-700 rounded-full font-semibold">' + program + '</span>' : '')
                + '</div>'
                + '<button type="button" onclick="removeCourseFromCollege(this)" class="p-1 text-slate-300 hover:text-red-500 transition">'
                + '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'
                + '</button>';
            listEl.appendChild(item);
            updateCollegeCoursesHidden();

            const inp = document.getElementById('courseSearchInput');
            if (inp) inp.value = '';
            document.getElementById('courseDropdownContainer')?.classList.add('hidden');
        };

        window.addCourseToCollege = function() {
            const inp = document.getElementById('courseSearchInput');
            if (inp) { inp.focus(); filterCourseDropdown(); }
        };

        window.removeCourseFromCollege = function(btn) {
            btn.closest('[data-course-id]').remove();
            updateCollegeCoursesHidden();
            const listEl = document.getElementById('collegeCoursesLinked');
            if (listEl && !listEl.querySelector('[data-course-id]')) {
                listEl.innerHTML = '<p class="text-xs text-slate-400 italic px-1">No courses linked yet. Search and add courses above.</p>';
            }
        };

        function updateCollegeCoursesHidden() {
            const listEl   = document.getElementById('collegeCoursesLinked');
            const hiddenEl = document.getElementById('collegeCoursesHidden');
            if (!listEl || !hiddenEl) return;
            const ids = [...listEl.querySelectorAll('[data-course-id]')].map(el => el.getAttribute('data-course-id'));
            hiddenEl.value = ids.join(',');
        }


        function deleteCollege(collegeId) {
            const college = findById('college', collegeId);
            const collegeName = college ? (college.name || college.code || 'this college') : 'this college';
            const campusLabel = college ? (college.campus || '') : '';

            const existing = document.getElementById('deleteCollegeModal');
            if (existing) existing.remove();

            const modal = document.createElement('div');
            modal.id = 'deleteCollegeModal';
            modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-[999] p-4';
            modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden" style="animation:slideUp .2s ease">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Delete College</h3>
                    <p class="text-slate-500 text-sm mb-1">Are you sure you want to delete</p>
                    <p class="font-semibold text-slate-800 text-sm mb-1">${esc(collegeName)}</p>
                    ${campusLabel ? `<p class="text-xs text-slate-400 mb-4">${esc(campusLabel)}</p>` : '<div class="mb-4"></div>'}
                    <p class="text-xs text-red-500 bg-red-50 rounded-lg px-3 py-2 mb-6">This will also remove all associated programs. This cannot be undone.</p>
                    <div class="flex gap-3">
                        <button onclick="document.getElementById('deleteCollegeModal').remove()" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-sm">Cancel</button>
                        <button id="confirmDeleteCollegeBtn" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-sm">Delete</button>
                    </div>
                </div>
            </div>`;

            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

            document.getElementById('confirmDeleteCollegeBtn').onclick = async () => {
                const btn = document.getElementById('confirmDeleteCollegeBtn');
                btn.disabled = true; btn.textContent = 'Deleting...';
                const result = await window.flexamApi.colleges.delete(collegeId);
                modal.remove();
                if (result.success) { showToast('College deleted.'); await refreshAllData(); }
                else showToast(result.message || 'Error deleting college', 'error');
            };
        }

window.addProgramEntry = function() {
    const acronymEl  = document.getElementById('progAcronym');
    const fullNameEl = document.getElementById('progFullName');
    const listEl     = document.getElementById('programsList');
    if (!acronymEl || !listEl) return;
    const acronym  = acronymEl.value.trim().toUpperCase();
    const fullName = fullNameEl ? fullNameEl.value.trim() : '';
    if (!acronym) { acronymEl.focus(); return; }
    const value = fullName ? `${acronym}=${fullName}` : acronym;
    // Prevent exact duplicates (same acronym AND same full name)
    for (const el of listEl.querySelectorAll('[data-value]')) {
        const v = el.getAttribute('data-value') || '';
        const existingAcronym  = v.includes('=') ? v.split('=')[0].trim() : v.trim();
        const existingFullName = v.includes('=') ? v.split('=').slice(1).join('=').trim() : '';
        if (existingAcronym === acronym && existingFullName.toLowerCase() === fullName.toLowerCase()) {
            acronymEl.style.borderColor = '#ef4444';
            setTimeout(() => acronymEl.style.borderColor = '', 1500);
            return;
        }
    }
    const item = document.createElement('div');
    item.className = 'flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg group';
    item.setAttribute('data-value', value);
    item.innerHTML = `
        <div class="flex items-center gap-2 flex-1 program-display">
            <span class="font-bold text-sm text-slate-700">${esc(acronym)}</span>
            ${fullName ? `<span class="text-slate-400 text-sm">— ${esc(fullName)}</span>` : ''}
        </div>
        <div class="hidden items-center gap-2 flex-1 program-edit-row">
            <input type="text" class="prog-edit-acronym w-24 px-2 py-1 border border-slate-300 rounded-lg text-sm font-bold focus:ring-2 focus:ring-emerald-500 outline-none uppercase" value="${esc(acronym)}" placeholder="Acronym">
            <input type="text" class="prog-edit-fullname flex-1 px-2 py-1 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none" value="${esc(fullName)}" placeholder="Full Name (optional)">
            <button type="button" onclick="saveProgramEdit(this)" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap">Save</button>
            <button type="button" onclick="cancelProgramEdit(this)" class="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold rounded-lg transition border border-slate-200 whitespace-nowrap">Cancel</button>
        </div>
        <button type="button" onclick="editProgramEntry(this)" class="p-1 text-slate-400 hover:text-emerald-500 transition program-edit-btn" title="Edit program">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/></svg>
        </button>
        <button type="button" onclick="removeProgramEntry(this)" class="p-1 text-slate-300 hover:text-red-500 transition program-delete-btn">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>`;
    listEl.appendChild(item);
    updateProgramsHidden();
    acronymEl.value = '';
    if (fullNameEl) fullNameEl.value = '';
    acronymEl.focus();
};

window.removeProgramEntry = function(btn) {
    btn.closest('[data-value]').remove();
    updateProgramsHidden();
};

function updateProgramsHidden() {
    const listEl   = document.getElementById('programsList');
    const hiddenEl = document.getElementById('programsHidden');
    if (!listEl || !hiddenEl) return;
    const values = [...listEl.querySelectorAll('[data-value]')].map(el => el.getAttribute('data-value'));
    hiddenEl.value = values.join('\n');
}

window.editProgramEntry = function(btn) {
    const row = btn.closest('[data-value]');
    const displayDiv = row.querySelector('.program-display');
    const editDiv    = row.querySelector('.program-edit-row');
    const deleteBtn  = row.querySelector('.program-delete-btn');
    const editBtn    = row.querySelector('.program-edit-btn');
    displayDiv.classList.add('hidden');
    editDiv.classList.remove('hidden');
    editDiv.classList.add('flex');
    if (deleteBtn) deleteBtn.classList.add('hidden');
    if (editBtn)   editBtn.classList.add('hidden');
    editDiv.querySelector('.prog-edit-acronym')?.focus();
};

window.cancelProgramEdit = function(btn) {
    const row = btn.closest('[data-value]');
    const displayDiv = row.querySelector('.program-display');
    const editDiv    = row.querySelector('.program-edit-row');
    const deleteBtn  = row.querySelector('.program-delete-btn');
    const editBtn    = row.querySelector('.program-edit-btn');
    editDiv.classList.add('hidden');
    editDiv.classList.remove('flex');
    displayDiv.classList.remove('hidden');
    if (deleteBtn) deleteBtn.classList.remove('hidden');
    if (editBtn)   editBtn.classList.remove('hidden');
};

window.saveProgramEdit = function(btn) {
    const row        = btn.closest('[data-value]');
    const displayDiv = row.querySelector('.program-display');
    const editDiv    = row.querySelector('.program-edit-row');
    const deleteBtn  = row.querySelector('.program-delete-btn');
    const editBtn    = row.querySelector('.program-edit-btn');
    const acronymEl  = editDiv.querySelector('.prog-edit-acronym');
    const fullNameEl = editDiv.querySelector('.prog-edit-fullname');
    const acronym    = (acronymEl?.value || '').trim().toUpperCase();
    const fullName   = (fullNameEl?.value || '').trim();
    if (!acronym) { acronymEl?.focus(); acronymEl.style.borderColor='#ef4444'; setTimeout(()=>acronymEl.style.borderColor='',1500); return; }
    // Check for exact duplicate (same acronym AND same full name, ignore self)
    const listEl = document.getElementById('programsList');
    for (const el of listEl.querySelectorAll('[data-value]')) {
        if (el === row) continue;
        const v = el.getAttribute('data-value') || '';
        const existingAcronym  = v.includes('=') ? v.split('=')[0].trim() : v.trim();
        const existingFullName = v.includes('=') ? v.split('=').slice(1).join('=').trim() : '';
        if (existingAcronym === acronym && existingFullName.toLowerCase() === fullName.toLowerCase()) {
            acronymEl.style.borderColor='#ef4444'; setTimeout(()=>acronymEl.style.borderColor='',1500); return;
        }
    }
    const newValue = fullName ? `${acronym}=${fullName}` : acronym;
    row.setAttribute('data-value', newValue);
    // Update display
    displayDiv.innerHTML = `
        <span class="font-bold text-sm text-slate-700">${esc(acronym)}</span>
        ${fullName ? `<span class="text-slate-400 text-sm">— ${esc(fullName)}</span>` : ''}`;
    editDiv.classList.add('hidden');
    editDiv.classList.remove('flex');
    displayDiv.classList.remove('hidden');
    if (deleteBtn) deleteBtn.classList.remove('hidden');
    if (editBtn)   editBtn.classList.remove('hidden');
    updateProgramsHidden();
};

        // ─────────────────────────────────────────────────────────────────────────
        // 17. COURSES CRUD (campus-aware)
        // ─────────────────────────────────────────────────────────────────────────
        function openAddCourseModal() {
            if (document.getElementById('addCourseModal')) return;
            const modal = document.createElement('div');
            modal.id = 'addCourseModal';
            modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
            modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="flex justify-between items-center px-8 py-6 border-b border-slate-200">
                    <h2 class="text-2xl font-bold text-[#1e293b]">Add Course</h2>
                    <button type="button" onclick="closeAddCourseModal()" class="text-slate-400 hover:text-slate-600"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <form id="addCourseForm" class="px-8 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Course Code <span class="text-red-500">*</span></label><input type="text" name="courseCode" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="e.g., CS101" required></div>
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Course Name <span class="text-red-500">*</span></label><input type="text" name="courseName" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="e.g., Introduction to Computing" required></div>
                        <div class="space-y-2 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">College <span class="text-red-500">*</span></label><select id="addCourseCollegeSelect" name="college" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required><option value="" disabled selected>Select College</option>${getCollegeOptions()}</select></div>
                        <div class="space-y-2 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">Program <span class="text-red-500">*</span></label><select id="addCourseProgramSelect" name="program" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required><option value="" disabled selected>Select College first</option></select></div>
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Year Level <span class="text-red-500">*</span></label><select name="yearLevel" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required>${yearLevelOptions('')}</select></div>
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Semester <span class="text-red-500">*</span></label><select name="semester" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required><option value="" disabled selected>Select Semester</option><option>1st Semester</option><option>2nd Semester</option><option>Summer</option></select></div>
                        <div class="space-y-2 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">Campus <span class="text-red-500">*</span></label>${campusSelectHtml('campus')}</div>
                    </div>
                    <div id="addCourseErr" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
                    <div class="flex gap-3 mt-8"><button type="submit" id="addCourseBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3.5 rounded-lg transition">Create Course</button><button type="button" onclick="closeAddCourseModal()" class="px-8 py-3.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200">Cancel</button></div>
                </form>
            </div>`;
            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            // Populate program dropdown when college is selected
            document.getElementById('addCourseCollegeSelect')?.addEventListener('change', function() {
                const selectedCode = this.value.toLowerCase();
                const progSel = document.getElementById('addCourseProgramSelect');
                if (!progSel) return;
                const collegeRec = allData.find(d =>
                    d.type === 'college' &&
                    ((d.code || '').toLowerCase() === selectedCode ||
                     (d.name || '').toLowerCase() === selectedCode)
                );
                const progs = (collegeRec && Array.isArray(collegeRec.programs)) ? collegeRec.programs : [];
                if (progs.length === 0) {
                    progSel.innerHTML = '<option value="" disabled selected>No programs found for this college</option>';
                } else {
                    progSel.innerHTML = '<option value="" disabled selected>Select Program</option>' + progs.map(p => {
                        const eq = p.indexOf('=');
                        const acronym  = eq !== -1 ? p.slice(0, eq).trim() : p.trim();
                        const fullName = eq !== -1 ? p.slice(eq + 1).trim() : '';
                        const label = fullName ? acronym + ' — ' + fullName : acronym;
                        return '<option value="' + acronym + '">' + label + '</option>';
                    }).join('');
                }
            });
           document.getElementById('addCourseForm').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const btn = document.getElementById('addCourseBtn');
    const errEl = document.getElementById('addCourseErr');
    btn.disabled = true; btn.textContent = 'Creating...';
    errEl.classList.add('hidden');

    const newCode    = fd.get('courseCode').trim().toLowerCase();
    const newCampus  = fd.get('campus').trim();
    const newCollege = (fd.get('college') || '').trim().toLowerCase();
    const newProgram = (fd.get('program') || '').trim().toLowerCase();

    // ── Duplicate check: same course_code + college + program + campus ──
    const duplicate = allData.find(d =>
        d.type === 'course' &&
        (d.course_code || '').toLowerCase() === newCode &&
        (d.campus || '').trim() === newCampus &&
        (d.college || '').toLowerCase() === newCollege &&
        (d.program || '').toLowerCase() === newProgram
    );

    if (duplicate) {
        errEl.textContent = `Course "${fd.get('courseCode').trim()}" already exists in the ${newCampus} campus under the same college and program.`;
        errEl.classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'Create Course';
        return;
    }

    const result = await window.flexamApi.courses.create({
        course_code: fd.get('courseCode'), course_name: fd.get('courseName'),
        college: fd.get('college'), program: fd.get('program'),
        year_level: fd.get('yearLevel'), semester: fd.get('semester'),
        campus: newCampus
    });
    btn.disabled = false; btn.textContent = 'Create Course';
    if (result.success) { closeAddCourseModal(); showToast('Course created!'); await refreshAllData(); }
    else { errEl.textContent = result.message || 'Error'; errEl.classList.remove('hidden'); }
};
        }
        function closeAddCourseModal() { const m = document.getElementById('addCourseModal'); if (m) m.remove(); }

        function editCourse(courseId) {
            const course = findById('course', courseId);
            if (!course) { showToast('Course not found', 'error'); return; }
            if (document.getElementById('editCourseModal')) return;
            const modal = document.createElement('div');
            modal.id = 'editCourseModal';
            modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
            modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl flex flex-col" style="max-height:90vh;">
                <div class="flex justify-between items-center px-8 py-6 border-b border-slate-200 shrink-0">
                    <h2 class="text-2xl font-bold text-[#1e293b]">Edit Course</h2>
                    <button type="button" onclick="closeEditCourseModal()" class="text-slate-400 hover:text-slate-600"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <form id="editCourseForm" class="px-8 py-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Course Code *</label><input type="text" name="courseCode" value="${esc(course.course_code || '')}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" required></div>
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Course Name *</label><input type="text" name="courseName" value="${esc(course.course_name || '')}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none" required></div>
                        <div class="space-y-2 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">College *</label><select name="college" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required><option value="" disabled>Select College</option>${getCollegeOptions(course.college || '')}</select></div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700">Program *</label>
                            <select name="program" id="editCourseProgramSelect" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required>
                                <option value="">Select Program</option>
                                ${(() => {
                                    const selCollege = (course.college || '').toLowerCase();
                                    const currentProg = (course.program || '').trim();
                                    const collegeRec = allData.find(d =>
                                        d.type === 'college' &&
                                        ((d.code || '').toLowerCase() === selCollege ||
                                         (d.name || '').toLowerCase() === selCollege)
                                    );
                                    const progs = (collegeRec && Array.isArray(collegeRec.programs))
                                        ? collegeRec.programs
                                        : (currentProg ? [currentProg] : []);
                                    return progs.map(p => {
                                        const eqIndex = p.indexOf('=');
                                        const acronym  = eqIndex !== -1 ? p.slice(0, eqIndex).trim() : p.trim();
                                        const fullName = eqIndex !== -1 ? p.slice(eqIndex + 1).trim() : '';
                                        const label = fullName ? acronym + ' — ' + fullName : acronym;
                                        const isSelected = acronym.toLowerCase() === currentProg.toLowerCase() || p.toLowerCase() === currentProg.toLowerCase();
                                        return '<option value="' + esc(acronym) + '"' + (isSelected ? ' selected' : '') + '>' + esc(label) + '<\/option>';
                                    }).join('');
                                })()}
                            </select>
                            <p class="text-xs text-slate-400 mt-1">Programs are sourced from the selected college. Change the college above to refresh the list.</p>
                        </div>
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Year Level *</label><select name="yearLevel" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required>${yearLevelOptions(course.year_level)}</select></div>
                        <div class="space-y-2 text-left"><label class="block text-sm font-medium text-slate-700">Semester *</label><select name="semester" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none" required><option value="" disabled>Select Semester</option><option ${course.semester==='1st Semester'?'selected':''}>1st Semester</option><option ${course.semester==='2nd Semester'?'selected':''}>2nd Semester</option><option ${course.semester==='Summer'?'selected':''}>Summer</option></select></div>
                        <div class="space-y-2 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">Campus *</label>${campusSelectHtml('campus', course.campus || '')}</div>
                    </div>
                    <div id="editCourseErr" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
                </form>
                <div class="flex gap-3 px-8 py-5 border-t border-slate-200 shrink-0 bg-white">
                    <button type="submit" form="editCourseForm" id="editCourseBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg transition">Update Course</button>
                    <button type="button" onclick="closeEditCourseModal()" class="px-8 py-3 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200">Cancel</button>
                </div>
            </div>`;
            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            // Live duplicate check for Add Course
    function checkAddCourseDuplicate() {
    const code   = (document.querySelector('#addCourseForm input[name="courseCode"]')?.value || '').trim().toLowerCase();
    const campus = (document.querySelector('#addCourseForm select[name="campus"]')?.value || '').trim();
    if (!code || !campus) { checkAndShowDuplicate('addCourseForm', 'addCourseDupBanner', false); return; }
    const college = (document.querySelector('#addCourseForm select[name="college"]')?.value || '').trim().toLowerCase();
    const program = (document.querySelector('#addCourseForm input[name="program"]')?.value || '').trim().toLowerCase();
    const dup = allData.find(d =>
    d.type === 'course' &&
    (d.course_code || '').toLowerCase() === code &&
    (d.campus || '') === campus &&
    (d.college || '').toLowerCase() === college &&
    (d.program || '').toLowerCase() === program
    );
    checkAndShowDuplicate('addCourseForm', 'addCourseDupBanner', !!dup,
        `A course with code <strong>${document.querySelector('#addCourseForm input[name="courseCode"]').value.trim()}</strong> already exists in <strong>${campus}</strong> under the same college and program.`
    );
    }
    document.querySelector('#addCourseForm input[name="courseCode"]')?.addEventListener('input', checkAddCourseDuplicate);
    document.querySelector('#addCourseForm select[name="campus"]')?.addEventListener('change', checkAddCourseDuplicate);
    document.querySelector('#addCourseForm select[name="college"]')?.addEventListener('change', checkAddCourseDuplicate);
    document.querySelector('#addCourseForm input[name="program"]')?.addEventListener('input', checkAddCourseDuplicate);
            setTimeout(() => { const sel = document.querySelector('#editCourseForm select[name="college"]'); if (sel && course.college) sel.value = course.college; }, 10);
            // Refresh program dropdown when college changes
            document.querySelector('#editCourseForm select[name="college"]')?.addEventListener('change', function() {
                const selectedCode = this.value.toLowerCase();
                const progSel = document.getElementById('editCourseProgramSelect');
                if (!progSel) return;
                const collegeRec = allData.find(d =>
                    d.type === 'college' &&
                    ((d.code || '').toLowerCase() === selectedCode ||
                     (d.name || '').toLowerCase() === selectedCode)
                );
                const progs = (collegeRec && Array.isArray(collegeRec.programs)) ? collegeRec.programs : [];
                progSel.innerHTML = '<option value="">Select Program</option>' + progs.map(p => {
                    const eq = p.indexOf('=');
                    const acronym  = eq !== -1 ? p.slice(0, eq).trim() : p.trim();
                    const fullName = eq !== -1 ? p.slice(eq + 1).trim() : '';
                    const label = fullName ? acronym + ' — ' + fullName : acronym;
                    return '<option value="' + acronym + '">' + label + '</option>';
                }).join('');
            });
            document.getElementById('editCourseForm').onsubmit = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const btn = document.getElementById('editCourseBtn');
    const errEl = document.getElementById('editCourseErr');
    btn.disabled = true; btn.textContent = 'Updating...';
    errEl.classList.add('hidden');

    const programVal = (fd.get('program') || '').trim();
    if (!programVal) {
        errEl.textContent = '❌ Please select a program.';
        errEl.classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'Update Course';
        return;
    }

    const newCode   = fd.get('courseCode').trim().toLowerCase();
    const newCampus = fd.get('campus').trim();

    // ── Duplicate check: same course_code + campus, excluding self ──
    const duplicate = allData.find(d =>
        d.type === 'course' &&
        dbId(String(d.id)) !== courseId &&
        (d.course_code || '').toLowerCase() === newCode &&
        (d.campus || '') === newCampus &&
        (d.college || '').toLowerCase() === (fd.get('college') || '').trim().toLowerCase() &&
        (d.program || '').toLowerCase() === programVal.toLowerCase()
    );

    if (duplicate) {
        errEl.textContent = `❌ Course "${fd.get('courseCode').trim()}" already exists in the ${newCampus} campus under the same college and program.`;
        errEl.classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'Update Course';
        return;
    }

    const result = await window.flexamApi.courses.update({
        id: courseId, course_code: fd.get('courseCode'), course_name: fd.get('courseName'),
        college: fd.get('college'), program: programVal,
        year_level: fd.get('yearLevel'), semester: fd.get('semester'),
        campus: newCampus
    });
    btn.disabled = false; btn.textContent = 'Update Course';
    if (result.success) { closeEditCourseModal(); showToast('Course updated!'); await refreshAllData(); }
    else { errEl.textContent = result.message || 'Error'; errEl.classList.remove('hidden'); }
};
        }

        function closeEditCourseModal() { const m = document.getElementById('editCourseModal'); if (m) m.remove(); }

        async function deleteCourse(courseId) {
            if (!confirm('Delete this course?')) return;
            const result = await window.flexamApi.courses.delete(courseId);
            if (result.success) { showToast('Course deleted.'); await refreshAllData(); }
            else showToast(result.message || 'Error', 'error');
        }

        window.openAdminFeedbackDetail = function(f) {
        if (document.getElementById('adminFeedbackDetailModal')) document.getElementById('adminFeedbackDetailModal').remove();
        const date = f.created_at ? new Date(f.created_at).toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) : 'Unknown date';
        const modal = document.createElement('div');
        modal.id = 'adminFeedbackDetailModal';
        modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
        modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-5 flex justify-between items-start">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-lg">
                    ${(f.student_name || 'A').charAt(0).toUpperCase()}
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">${esc(f.student_name || 'Anonymous')}</h2>
                    ${f.student_number ? `<p class="text-emerald-100 text-xs">Student #: ${esc(f.student_number)}</p>` : ''}
                </div>
            </div>
            <button onclick="document.getElementById('adminFeedbackDetailModal').remove()" class="text-white/70 hover:text-white transition p-1 rounded-lg hover:bg-white/10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <!-- Meta info -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex flex-wrap gap-3">
            ${f.category ? `<span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-emerald-100 text-emerald-700">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                ${esc(f.category)}
            </span>` : ''}
            ${f.college ? `<span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-blue-100 text-blue-700">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                ${esc(f.college)}
            </span>` : ''}
            ${f.campus ? `<span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-purple-100 text-purple-700">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                ${esc(f.campus)}
            </span>` : ''}
            <span class="inline-flex items-center gap-1 text-xs text-slate-500 ml-auto">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                ${date}
            </span>
        </div>
        <!-- Body -->
        <div class="px-6 py-5 space-y-4">
            ${f.subject ? `
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Subject</p>
                <p class="text-base font-semibold text-slate-800">${esc(f.subject)}</p>
            </div>` : ''}
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Message</p>
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                    <p class="text-sm text-slate-700 leading-relaxed italic">"${esc(f.message)}"</p>
                </div>
            </div>
            ${f.exam_difficulty ? `
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Exam Difficulty</p>
                <div class="flex items-center gap-3 p-3 rounded-xl border ${f.exam_difficulty.startsWith('30') ? 'bg-amber-50 border-amber-200' : f.exam_difficulty.startsWith('50') ? 'bg-orange-50 border-orange-200' : 'bg-red-50 border-red-200'}">
                    <span class="text-2xl">${f.exam_difficulty.startsWith('30') ? '😐' : f.exam_difficulty.startsWith('50') ? '😓' : '😱'}</span>
                    <div>
                        <p class="text-sm font-bold ${f.exam_difficulty.startsWith('30') ? 'text-amber-700' : f.exam_difficulty.startsWith('50') ? 'text-orange-700' : 'text-red-700'}">${esc(f.exam_difficulty)}</p>
                        <p class="text-xs ${f.exam_difficulty.startsWith('30') ? 'text-amber-500' : f.exam_difficulty.startsWith('50') ? 'text-orange-500' : 'text-red-500'}">Student-reported difficulty level</p>
                    </div>
                </div>
            </div>` : ''}
            <div class="flex items-center justify-between">
                ${!f.is_read ? `
                <div class="flex items-center gap-2 px-3 py-2 bg-emerald-50 border border-emerald-100 rounded-xl">
                    <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full"></span>
                    <p class="text-xs text-emerald-700 font-medium">Unread</p>
                </div>` : `
                <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-100 rounded-xl">
                    <span class="inline-block w-2 h-2 bg-slate-400 rounded-full"></span>
                    <p class="text-xs text-slate-500 font-medium">Read</p>
                </div>`}
                <span class="text-xs text-slate-400">ID: ${f.id}</span>
            </div>
        </div>
        <!-- Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex gap-3 justify-end">
            <button onclick="event.stopPropagation();deleteFeedback('${f.id}');document.getElementById('adminFeedbackDetailModal').remove();"
                class="px-4 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 font-semibold rounded-xl transition text-sm border border-red-200">
                Delete
            </button>
            <button onclick="document.getElementById('adminFeedbackDetailModal').remove()" 
                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition text-sm">
                Close
            </button>
        </div>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

        // ─────────────────────────────────────────────────────────────────────────
        // 18. ROOMS CRUD (campus-aware)
       // ── Filter ────────────────────────────────────────────────────────────────

       window.toggleAdminNotif = function() {
    const dd = document.getElementById('adminNotifDropdown');
    if (!dd) return;
    const isHidden = dd.classList.contains('hidden');
    dd.classList.toggle('hidden', !isHidden);
    if (isHidden) {
        setTimeout(() => {
            document.addEventListener('click', function closeNotif(e) {
                const wrapper = document.getElementById('notifWrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    dd.classList.add('hidden');
                    document.removeEventListener('click', closeNotif);
                }
            });
        }, 0);
    }
};
window.roomsFilter = function() {
    const search  = (document.getElementById('roomsSearch')?.value || '').toLowerCase();
    const campus  = campusFilters['rooms'] || '';
    const status  = (document.getElementById('roomsStatusFilter')?.value || '');
    // sync dropdown with active tab
    const campusDrop = document.getElementById('roomsCampus');
    if (campusDrop && campusDrop.value !== campus) campusDrop.value = campus;

    const allRows = Array.from(document.querySelectorAll('#roomsBody tr[data-campus-row]'));
    allRows.forEach(row => {
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();
        const rowCampus = row.getAttribute('data-campus-row') || '';
        const rowStatus = row.getAttribute('data-status-row') || '';
        const show = (!search || rowSearch.includes(search))
                  && (!campus || rowCampus === campus)
                  && (!status || rowStatus === status);
        row.setAttribute('data-filtered', show ? 'false' : 'true');
        row.style.display = show ? '' : 'none';
    });

    const visibleRows = allRows.filter(r => r.getAttribute('data-filtered') !== 'true');
    const PAGE = typeof PAGE_SIZE !== 'undefined' ? PAGE_SIZE : 20;
    visibleRows.forEach((row, i) => { row.style.display = i < PAGE ? '' : 'none'; });
    pageState['rooms'] = 1;
    const pagerEl = document.getElementById('pager-rooms');
    if (pagerEl && typeof renderPager === 'function') pagerEl.innerHTML = renderPager('rooms', visibleRows.length, PAGE);
};

window.roomsClearFilters = function() {
    ['roomsSearch','roomsCampus','roomsStatusFilter'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    campusFilters['rooms'] = currentUser.campus || '';
    window.roomsFilter();
};

// ── Helper: find room in allData by raw id string ─────────────────────────
window._findRoom = function(rawId) {
    const s = String(rawId);
    return allData.find(d => d.type === 'room' && (String(d.id) === s || dbId(String(d.id)) === s || s === dbId(String(d.id))));
};

// ── Add Room ──────────────────────────────────────────────────────────────
window.openAddRoomModal = function() {
    if (document.getElementById('addRoomModal')) return;
    const today = new Date().toISOString().split('T')[0];
    const modal = document.createElement('div');
    modal.id = 'addRoomModal';
    modal.className = 'fixed inset-0 bg-black/50 z-50 overflow-y-auto';
    modal.innerHTML = `
    <div class="min-h-screen px-4 py-8 flex items-start justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
        <div class="flex justify-between items-center px-8 py-5 border-b border-slate-200">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add Room</h2>
                <p class="text-sm text-slate-400 mt-0.5">Fill in the details to create a new room</p>
            </div>
            <button type="button" id="closeAddRoomBtn" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="addRoomForm" class="px-8 py-6 space-y-6">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Room Details</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="space-y-1.5 text-left">
                        <label class="block text-sm font-medium text-slate-700">Room Name <span class="text-red-500">*</span></label>
                        <input type="text" name="roomName" placeholder="e.g., CL101" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                    </div>
                    <div class="space-y-1.5 text-left">
                        <label class="block text-sm font-medium text-slate-700">Building <span class="text-red-500">*</span></label>
                        <input type="text" name="building" placeholder="e.g., CL Building" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                    </div>
                    <div class="space-y-1.5 text-left">
                        <label class="block text-sm font-medium text-slate-700">Capacity <span class="text-red-500">*</span></label>
                        <input type="number" name="capacity" placeholder="e.g., 40" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                    </div>
                    <div class="space-y-1.5 text-left">
                        <label class="block text-sm font-medium text-slate-700">Floor</label>
                        <input type="text" name="floor" placeholder="e.g., 1st Floor" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm">
                    </div>
                    <div class="space-y-1.5 text-left md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Campus <span class="text-red-500">*</span></label>
                        ${campusSelectHtml('campus')}
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Room Availability</p>
                        <p class="text-sm text-slate-500 mt-0.5">Mark this room as blocked upon creation</p>
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <span class="text-sm font-medium text-slate-600">Block this room</span>
                        <div class="relative">
                            <input type="checkbox" id="addBlockToggle" class="sr-only">
                            <div id="addToggleTrack" class="w-11 h-6 rounded-full bg-slate-200 transition-colors duration-200"></div>
                            <div id="addToggleThumb" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"></div>
                        </div>
                    </label>
                </div>
                <div id="addBlockDetailsPanel" class="hidden space-y-4 p-4 rounded-xl bg-red-50 border border-red-200">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        <p class="text-xs text-red-700">This room will be <strong>unavailable for scheduling</strong> immediately after creation.</p>
                    </div>
                    <div class="space-y-1.5 text-left">
                        <label class="block text-sm font-semibold text-slate-700">Reason <span class="text-red-500">*</span></label>
                        <select id="addBlockReasonSelect" class="w-full px-3 py-2.5 border border-red-200 rounded-lg bg-white focus:ring-2 focus:ring-red-400 outline-none text-sm">
                            <option value="">— Select a reason —</option>
                            <option value="Maintenance / Repair">Maintenance / Repair</option>
                            <option value="Renovation">Renovation</option>
                            <option value="Reserved for Special Event">Reserved for Special Event</option>
                            <option value="Pest Control">Pest Control</option>
                            <option value="Electrical / Plumbing Work">Electrical / Plumbing Work</option>
                            <option value="Safety Inspection">Safety Inspection</option>
                            <option value="Other">Other (specify below)</option>
                        </select>
                    </div>
                    <div id="addCustomReasonWrapper" class="hidden space-y-1.5 text-left">
                        <label class="block text-sm font-semibold text-slate-700">Custom Reason</label>
                        <input type="text" id="addCustomReasonInput" placeholder="Describe the reason..." class="w-full px-3 py-2.5 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm bg-white">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5 text-left">
                            <label class="block text-sm font-semibold text-slate-700">Blocked From</label>
                            <input type="date" id="addBlockedFrom" min="${today}" class="w-full px-3 py-2.5 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm bg-white">
                            <p class="text-[11px] text-slate-400">Leave blank for indefinite</p>
                        </div>
                        <div class="space-y-1.5 text-left">
                            <label class="block text-sm font-semibold text-slate-700">Blocked Until</label>
                            <input type="date" id="addBlockedTo" min="${today}" class="w-full px-3 py-2.5 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm bg-white">
                            <p class="text-[11px] text-slate-400">Leave blank for indefinite</p>
                        </div>
                    </div>
                </div>
            </div>
            <div id="addRoomErr" class="hidden text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3 pt-2 border-t border-slate-100">
                <button type="button" id="cancelAddRoomBtn" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition">Cancel</button>
                <button type="submit" id="addRoomBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition">Create Room</button>
            </div>
        </form>
    </div>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
    function checkAddRoomDuplicate() {
    const name     = (document.querySelector('#addRoomForm input[name="roomName"]')?.value || '').trim().toLowerCase();
    const building = (document.querySelector('#addRoomForm input[name="building"]')?.value || '').trim().toLowerCase();
    const campus   = (document.querySelector('#addRoomForm select[name="campus"]')?.value || '').trim();
    if (!name || !building || !campus) { checkAndShowDuplicate('addRoomForm', 'addRoomDupBanner', false); return; }
    const dup = allData.find(d =>
        d.type === 'room' &&
        (d.name || '').toLowerCase() === name &&
        (d.building || '').toLowerCase() === building &&
        (d.campus || '') === campus
    );
    checkAndShowDuplicate('addRoomForm', 'addRoomDupBanner', !!dup,
        `Room <strong>${document.querySelector('#addRoomForm input[name="roomName"]').value.trim()}</strong> in <strong>${document.querySelector('#addRoomForm input[name="building"]').value.trim()}</strong> already exists in <strong>${campus}</strong>.`
    );
}
['input[name="roomName"]','input[name="building"]'].forEach(sel =>
    document.querySelector(`#addRoomForm ${sel}`)?.addEventListener('input', checkAddRoomDuplicate)
);
document.querySelector('#addRoomForm select[name="campus"]')?.addEventListener('change', checkAddRoomDuplicate);

    window.closeAddRoomModal = function() { const m = document.getElementById('addRoomModal'); if (m) m.remove(); };

    document.getElementById('closeAddRoomBtn').onclick  = window.closeAddRoomModal;
    document.getElementById('cancelAddRoomBtn').onclick = window.closeAddRoomModal;
    modal.addEventListener('click', function(e) { if (e.target === modal) window.closeAddRoomModal(); });

    document.getElementById('addBlockToggle').addEventListener('change', function() {
        const on = this.checked;
        document.getElementById('addBlockDetailsPanel').classList.toggle('hidden', !on);
        document.getElementById('addToggleTrack').style.backgroundColor = on ? '#ef4444' : '#e2e8f0';
        document.getElementById('addToggleThumb').style.transform       = on ? 'translateX(20px)' : 'translateX(0)';
    });
    document.getElementById('addBlockReasonSelect').addEventListener('change', function() {
        document.getElementById('addCustomReasonWrapper').classList.toggle('hidden', this.value !== 'Other');
    });

    document.getElementById('addRoomForm').onsubmit = async function(e) {
    e.preventDefault();
    const fd        = new FormData(e.target);
    const btn       = document.getElementById('addRoomBtn');
    const errEl     = document.getElementById('addRoomErr');
    const wantBlock = document.getElementById('addBlockToggle').checked;
    errEl.classList.add('hidden');

    // ── Duplicate check: same room name + building + campus ──
    const newName     = fd.get('roomName').trim().toLowerCase();
    const newBuilding = fd.get('building').trim().toLowerCase();
    const newCampus   = fd.get('campus').trim();

    const duplicate = allData.find(d =>
        d.type === 'room' &&
        (d.name || '').toLowerCase() === newName &&
        (d.building || '').toLowerCase() === newBuilding &&
        (d.campus || '') === newCampus
    );

    if (duplicate) {
        errEl.textContent = `Room "${fd.get('roomName').trim()}" in "${fd.get('building').trim()}" already exists in the ${newCampus} campus.`;
        errEl.classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'Create Room';
        return;
    }

    if (wantBlock) {
        const rs = document.getElementById('addBlockReasonSelect').value;
        if (!rs) { errEl.textContent = 'Please select a reason for blocking.'; errEl.classList.remove('hidden'); return; }
        const f = document.getElementById('addBlockedFrom').value;
        const t = document.getElementById('addBlockedTo').value;
        if (f && t && t < f) { errEl.textContent = '"Blocked Until" must be after "Blocked From".'; errEl.classList.remove('hidden'); return; }
    }

    btn.disabled = true; btn.textContent = 'Creating...';

    const createResult = await window.flexamApi.rooms.create({
        name: fd.get('roomName'), building: fd.get('building'),
        capacity: parseInt(fd.get('capacity')), floor: fd.get('floor') || '',
        campus: newCampus, locked: wantBlock ? 1 : 0
    });

    if (!createResult.success) {
        btn.disabled = false; btn.textContent = 'Create Room';
        errEl.textContent = createResult.message || 'Failed to create room.';
        errEl.classList.remove('hidden');
        return;
    }

    if (wantBlock && createResult.data && createResult.data.id) {
        const rs  = document.getElementById('addBlockReasonSelect').value;
        const cr  = document.getElementById('addCustomReasonInput').value.trim();
        await window.flexamApi.rooms.block({
            id: createResult.data.id,
            block_reason: rs === 'Other' ? (cr || 'Other') : rs,
            blocked_from: document.getElementById('addBlockedFrom').value || null,
            blocked_to:   document.getElementById('addBlockedTo').value   || null
        });
    }

            btn.disabled = false; btn.textContent = 'Create Room';
            window.closeAddRoomModal();
            showToast(wantBlock ? 'Room created and blocked!' : 'Room created!');
            await refreshAllData();
        };
        };

// ── Edit Room ─────────────────────────────────────────────────────────────
window.editRoom = function(rawId) {
    const room = window._findRoom(rawId);
    if (!room) { showToast('Room not found', 'error'); return; }
    if (document.getElementById('editRoomModal')) return;

    const isLocked = !!room.locked;
    const today    = new Date().toISOString().split('T')[0];
    const reason   = room.block_reason || '';
    const knownReasons = ['Maintenance / Repair','Renovation','Reserved for Special Event','Pest Control','Electrical / Plumbing Work','Safety Inspection'];
    const isCustom = reason && !knownReasons.includes(reason);

    const modal = document.createElement('div');
    modal.id = 'editRoomModal';
    modal.className = 'fixed inset-0 bg-black/50 z-50 overflow-y-auto';
    modal.innerHTML = `
    <div class="min-h-screen px-4 py-8 flex items-start justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
        <div class="flex justify-between items-center px-8 py-5 border-b border-slate-200">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit Room</h2>
                <p class="text-sm text-slate-400 mt-0.5">${esc(room.name)} · ${esc(room.building||'')}</p>
            </div>
            <button type="button" id="closeEditRoomBtn" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editRoomForm" class="px-8 py-6 space-y-6">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Room Details</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Room Name <span class="text-red-500">*</span></label><input type="text" name="roomName" value="${esc(room.name||'')}" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                    <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Building <span class="text-red-500">*</span></label><input type="text" name="building" value="${esc(room.building||'')}" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                    <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Capacity <span class="text-red-500">*</span></label><input type="number" name="capacity" value="${room.capacity||''}" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                    <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Floor</label><input type="text" name="floor" value="${esc(room.floor||'')}" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm"></div>
                    <div class="space-y-1.5 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">Campus <span class="text-red-500">*</span></label>${campusSelectHtml('campus', room.campus||'')}</div>
                </div>
            </div>
            <div class="border-t border-slate-100 pt-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Room Availability</p>
                        <p class="text-sm text-slate-500 mt-0.5">Current status: ${isLocked ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 ml-1">Blocked</span>' : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 ml-1">Available</span>'}</p>
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <span class="text-sm font-medium text-slate-600">Block this room</span>
                        <div class="relative">
                            <input type="checkbox" id="editBlockToggle" class="sr-only" ${isLocked?'checked':''}>
                            <div id="editToggleTrack" class="w-11 h-6 rounded-full transition-colors duration-200" style="background-color:${isLocked?'#ef4444':'#e2e8f0'}"></div>
                            <div id="editToggleThumb" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200" style="transform:${isLocked?'translateX(20px)':'translateX(0)'}"></div>
                        </div>
                    </label>
                </div>
                <div id="editBlockDetailsPanel" class="${isLocked?'':'hidden'} space-y-4 p-4 rounded-xl bg-red-50 border border-red-200">
                    <div class="flex items-start gap-2"><svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg><p class="text-xs text-red-700">Blocked rooms are <strong>excluded from scheduling</strong>.</p></div>
                    <div class="space-y-1.5 text-left"><label class="block text-sm font-semibold text-slate-700">Reason <span class="text-red-500">*</span></label>
                        <select id="editBlockReasonSelect" class="w-full px-3 py-2.5 border border-red-200 rounded-lg bg-white focus:ring-2 focus:ring-red-400 outline-none text-sm">
                            <option value="">— Select a reason —</option>
                            <option value="Maintenance / Repair" ${reason==='Maintenance / Repair'?'selected':''}>Maintenance / Repair</option>
                            <option value="Renovation" ${reason==='Renovation'?'selected':''}>Renovation</option>
                            <option value="Reserved for Special Event" ${reason==='Reserved for Special Event'?'selected':''}>Reserved for Special Event</option>
                            <option value="Pest Control" ${reason==='Pest Control'?'selected':''}>Pest Control</option>
                            <option value="Electrical / Plumbing Work" ${reason==='Electrical / Plumbing Work'?'selected':''}>Electrical / Plumbing Work</option>
                            <option value="Safety Inspection" ${reason==='Safety Inspection'?'selected':''}>Safety Inspection</option>
                            <option value="Other" ${isCustom?'selected':''}>Other (specify below)</option>
                        </select>
                    </div>
                    <div id="editCustomReasonWrapper" class="${isCustom?'':'hidden'} space-y-1.5 text-left"><label class="block text-sm font-semibold text-slate-700">Custom Reason</label><input type="text" id="editCustomReasonInput" value="${isCustom?esc(reason):''}" placeholder="Describe the reason..." class="w-full px-3 py-2.5 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm bg-white"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-semibold text-slate-700">Blocked From</label><input type="date" id="editBlockedFrom" value="${room.blocked_from||''}" min="${today}" class="w-full px-3 py-2.5 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm bg-white"><p class="text-[11px] text-slate-400">Leave blank for indefinite</p></div>
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-semibold text-slate-700">Blocked Until</label><input type="date" id="editBlockedTo" value="${room.blocked_to||''}" min="${today}" class="w-full px-3 py-2.5 border border-red-200 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm bg-white"><p class="text-[11px] text-slate-400">Leave blank for indefinite</p></div>
                    </div>
                </div>
            </div>
            <div id="editRoomErr" class="hidden text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3 pt-2 border-t border-slate-100">
                <button type="button" id="cancelEditRoomBtn" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition">Cancel</button>
                <button type="submit" id="editRoomBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition">Save Changes</button>
            </div>
        </form>
    </div>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);

    document.getElementById('closeEditRoomBtn').onclick  = () => { const m = document.getElementById('editRoomModal'); if (m) m.remove(); };
    document.getElementById('cancelEditRoomBtn').onclick = () => { const m = document.getElementById('editRoomModal'); if (m) m.remove(); };

    document.getElementById('editBlockToggle').addEventListener('change', function() {
        const on = this.checked;
        document.getElementById('editBlockDetailsPanel').classList.toggle('hidden', !on);
        document.getElementById('editToggleTrack').style.backgroundColor = on ? '#ef4444' : '#e2e8f0';
        document.getElementById('editToggleThumb').style.transform       = on ? 'translateX(20px)' : 'translateX(0)';
    });
    document.getElementById('editBlockReasonSelect').addEventListener('change', function() {
        document.getElementById('editCustomReasonWrapper').classList.toggle('hidden', this.value !== 'Other');
    });

    document.getElementById('editRoomForm').onsubmit = async function(e) {
    e.preventDefault();
    const fd        = new FormData(e.target);
    const btn       = document.getElementById('editRoomBtn');
    const errEl     = document.getElementById('editRoomErr');
    const wantBlock = document.getElementById('editBlockToggle').checked;
    errEl.classList.add('hidden');

    // ── Duplicate check: same name + building + campus, excluding self ──
    const newName     = fd.get('roomName').trim().toLowerCase();
    const newBuilding = fd.get('building').trim().toLowerCase();
    const newCampus   = fd.get('campus').trim();

    const duplicate = allData.find(d =>
        d.type === 'room' &&
        dbId(String(d.id)) !== rawId &&
        (d.name || '').toLowerCase() === newName &&
        (d.building || '').toLowerCase() === newBuilding &&
        (d.campus || '') === newCampus
    );

    if (duplicate) {
        errEl.textContent = `Room "${fd.get('roomName').trim()}" in "${fd.get('building').trim()}" already exists in the ${newCampus} campus.`;
        errEl.classList.remove('hidden');
        btn.disabled = false; btn.textContent = 'Save Changes';
        return;
    }

    if (wantBlock) {
        const rs = document.getElementById('editBlockReasonSelect').value;
        if (!rs) { errEl.textContent = 'Please select a reason for blocking.'; errEl.classList.remove('hidden'); return; }
        const f = document.getElementById('editBlockedFrom').value;
        const t = document.getElementById('editBlockedTo').value;
        if (f && t && t < f) { errEl.textContent = '"Blocked Until" must be after "Blocked From".'; errEl.classList.remove('hidden'); return; }
    }

    btn.disabled = true; btn.textContent = 'Saving...';

    const updateResult = await window.flexamApi.rooms.update({
        id: rawId, name: fd.get('roomName'), building: fd.get('building'),
        capacity: parseInt(fd.get('capacity')), floor: fd.get('floor') || '',
        campus: newCampus, locked: wantBlock ? 1 : 0
    });

    if (!updateResult.success) {
        btn.disabled = false; btn.textContent = 'Save Changes';
        errEl.textContent = updateResult.message || 'Failed to update room.';
        errEl.classList.remove('hidden');
        return;
    }

    if (wantBlock) {
        const rs = document.getElementById('editBlockReasonSelect').value;
        const cr = document.getElementById('editCustomReasonInput').value.trim();
        await window.flexamApi.rooms.block({
            id: rawId,
            block_reason: rs === 'Other' ? (cr || 'Other') : rs,
            blocked_from: document.getElementById('editBlockedFrom').value || null,
            blocked_to:   document.getElementById('editBlockedTo').value   || null
        });
    } else if (isLocked) {
        await window.flexamApi.rooms.unblock(rawId);
    }

    btn.disabled = false; btn.textContent = 'Save Changes';
    window.closeEditRoomModal();
    showToast(wantBlock ? 'Room blocked successfully.' : 'Room updated successfully.');
    await refreshAllData();
};
window.closeEditRoomModal = function() { const m = document.getElementById('editRoomModal'); if (m) m.remove(); };
};

// ── Delete Room ──────────────────────────────────────────────────────────
window.deleteRoom = function(rawId) {
    const room = window._findRoom(rawId);
    const roomLabel = room ? `${room.name}${room.building ? ' — ' + room.building : ''}` : 'this room';

    const existing = document.getElementById('deleteRoomModal');
    if (existing) existing.remove();

    const modal = document.createElement('div');
    modal.id = 'deleteRoomModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-[999] p-4';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden" style="animation:slideUp .2s ease">
        <div class="p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">Delete Room</h3>
            <p class="text-slate-500 text-sm mb-1">Are you sure you want to delete</p>
            <p class="font-semibold text-slate-800 text-sm mb-4">${esc(roomLabel)}</p>
            <p class="text-xs text-red-500 bg-red-50 rounded-lg px-3 py-2 mb-6">This cannot be undone. Any schedules using this room may be affected.</p>
            <div class="flex gap-3">
                <button onclick="document.getElementById('deleteRoomModal').remove()" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-sm">Cancel</button>
                <button id="confirmDeleteRoomBtn" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition text-sm">Delete</button>
            </div>
        </div>
    </div>`;

    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

    document.getElementById('confirmDeleteRoomBtn').onclick = async () => {
        const btn = document.getElementById('confirmDeleteRoomBtn');
        btn.disabled = true; btn.textContent = 'Deleting...';
        const result = await window.flexamApi.rooms.delete(rawId);
        modal.remove();
        if (result.success) { showToast('Room deleted.'); await refreshAllData(); }
        else showToast(result.message || 'Error deleting room.', 'error');
    };
};

// ── Block Room Modal ──────────────────────────────────────────────────────
window.openBlockRoomModal = function(rawId) {
    const room = window._findRoom(rawId);
    if (!room) { showToast('Room not found', 'error'); return; }
    if (document.getElementById('blockRoomModal')) return;
    const today = new Date().toISOString().split('T')[0];

    const modal = document.createElement('div');
    modal.id = 'blockRoomModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
        <div class="flex justify-between items-center px-8 py-5 border-b border-slate-200">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Block Room</h2>
                <p class="text-sm text-slate-400 mt-0.5">${esc(room.name)} · ${esc(room.building||'')}</p>
            </div>
            <button type="button" id="closeBlockRoomBtn" class="text-slate-400 hover:text-slate-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="blockRoomForm" class="px-8 py-6 space-y-5">
            <div class="flex items-start gap-2 p-3 rounded-xl bg-orange-50 border border-orange-200">
                <svg class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <p class="text-xs text-orange-700">Blocking this room will make it <strong>unavailable for scheduling</strong>. Existing approved schedules will not be automatically removed.</p>
            </div>
            <div class="space-y-1.5 text-left">
                <label class="block text-sm font-semibold text-slate-700">Reason <span class="text-red-500">*</span></label>
                <select id="blockReasonSelect" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-red-400 outline-none text-sm">
                    <option value="">— Select a reason —</option>
                    <option value="Maintenance / Repair">Maintenance / Repair</option>
                    <option value="Renovation">Renovation</option>
                    <option value="Reserved for Special Event">Reserved for Special Event</option>
                    <option value="Pest Control">Pest Control</option>
                    <option value="Electrical / Plumbing Work">Electrical / Plumbing Work</option>
                    <option value="Safety Inspection">Safety Inspection</option>
                    <option value="Other">Other (specify below)</option>
                </select>
            </div>
            <div id="customReasonWrapper" class="hidden space-y-1.5 text-left">
                <label class="block text-sm font-semibold text-slate-700">Custom Reason</label>
                <input type="text" id="customReasonInput" placeholder="Describe the reason..." class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5 text-left"><label class="block text-sm font-semibold text-slate-700">Blocked From</label><input type="date" id="blockedFrom" min="${today}" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm"><p class="text-[11px] text-slate-400">Leave blank for indefinite</p></div>
                <div class="space-y-1.5 text-left"><label class="block text-sm font-semibold text-slate-700">Blocked Until</label><input type="date" id="blockedTo" min="${today}" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-400 outline-none text-sm"><p class="text-[11px] text-slate-400">Leave blank for indefinite</p></div>
            </div>
            <div id="blockRoomErr" class="hidden text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3 pt-2">
                <button type="button" id="cancelBlockRoomBtn" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition">Cancel</button>
                <button type="submit" id="blockRoomBtn" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition">Block Room</button>
            </div>
        </form>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);

    document.getElementById('closeBlockRoomBtn').onclick  = window.closeBlockRoomModal;
    document.getElementById('cancelBlockRoomBtn').onclick = window.closeBlockRoomModal;

    document.getElementById('blockReasonSelect').addEventListener('change', function() {
        document.getElementById('customReasonWrapper').classList.toggle('hidden', this.value !== 'Other');
    });

    document.getElementById('blockRoomForm').onsubmit = async function(e) {
        e.preventDefault();
        const errEl     = document.getElementById('blockRoomErr');
        const reasonSel = document.getElementById('blockReasonSelect').value;
        errEl.classList.add('hidden');
        if (!reasonSel) { errEl.textContent = 'Please select a reason.'; errEl.classList.remove('hidden'); return; }
        const f = document.getElementById('blockedFrom').value;
        const t = document.getElementById('blockedTo').value;
        if (f && t && t < f) { errEl.textContent = '"Blocked Until" must be after "Blocked From".'; errEl.classList.remove('hidden'); return; }

        const cr  = document.getElementById('customReasonInput').value.trim();
        const btn = document.getElementById('blockRoomBtn');
        btn.disabled = true; btn.textContent = 'Blocking...';

        const result = await window.flexamApi.rooms.block({
            id: rawId,
            block_reason: reasonSel === 'Other' ? (cr || 'Other') : reasonSel,
            blocked_from: f || null,
            blocked_to:   t || null
        });

        btn.disabled = false; btn.textContent = 'Block Room';
        if (result.success) {
            window.closeBlockRoomModal();
            activityLog('Room Blocked', `"${room.name}" · ${room.campus || ''}`);
            showToast(`"${room.name}" has been blocked.`);
            await refreshAllData();
        } else {
            errEl.textContent = result.message || 'Failed to block room.';
            errEl.classList.remove('hidden');
        }
    };
};
window.closeBlockRoomModal = function() { const m = document.getElementById('blockRoomModal'); if (m) m.remove(); };

// ── Unblock ───────────────────────────────────────────────────────────────
window.unblockRoom = async function(rawId) {
    const room = window._findRoom(rawId);
    const name = room ? room.name : 'this room';
    if (!confirm(`Unblock "${name}"? It will become available for scheduling again.`)) return;
    const result = await window.flexamApi.rooms.unblock(rawId);
    if (result.success) { activityLog('Room Unblocked', `"${name}" · ${room ? room.campus || '' : ''}`); showToast(`"${name}" is now available.`); await refreshAllData(); }
    else showToast(result.message || 'Error unblocking room', 'error');
};

// ── Delete ────────────────────────────────────────────────────────────────

        // ─────────────────────────────────────────────────────────────────────────
        // 19. PROCTORS CRUD (campus-aware)
        // ─────────────────────────────────────────────────────────────────────────
        function openAddProctorModal() {
            if (document.getElementById('addProctorModal')) return;
            const modal = document.createElement('div');
            modal.id = 'addProctorModal';
            modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
            modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200">
                    <h2 class="text-xl font-bold">Add Proctor</h2>
                    <button type="button" onclick="closeAddProctorModal()" class="text-slate-400 hover:text-slate-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <form id="addProctorForm" class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Proctor Name *</label><input type="text" name="proctorName" placeholder="e.g., Dr. John Doe" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">College/Program *</label><input type="text" name="collegeProgram" placeholder="e.g., CCS - BSCS" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Email *</label><input type="email" name="email" placeholder="email@example.com" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Phone</label><input type="tel" name="phone" placeholder="+63-912-345-6789" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm"></div>
                        <div class="space-y-1.5 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">Campus *</label>${campusSelectHtml('campus')}</div>
                    </div>
                    <div id="addProctorErr" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
                    <div class="flex gap-3 mt-5"><button type="submit" id="addProctorBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">Create Proctor</button><button type="button" onclick="closeAddProctorModal()" class="px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200 text-sm">Cancel</button></div>
                </form>
            </div>`;
            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            function checkAddProctorDuplicate() {
            const name   = (document.querySelector('#addProctorForm input[name="proctorName"]')?.value || '').trim().toLowerCase();
            const campus = (document.querySelector('#addProctorForm select[name="campus"]')?.value || '').trim();
            if (!name || !campus) { checkAndShowDuplicate('addProctorForm', 'addProctorDupBanner', false); return; }
            const dup = allData.find(d =>
                d.type === 'proctor' &&
                (d.name || '').toLowerCase() === name &&
                (d.campus || '') === campus
            );
            checkAndShowDuplicate('addProctorForm', 'addProctorDupBanner', !!dup,
                `Proctor <strong>${document.querySelector('#addProctorForm input[name="proctorName"]').value.trim()}</strong> already exists in the <strong>${campus}</strong> campus.`
            );
        }
                document.querySelector('#addProctorForm input[name="proctorName"]')?.addEventListener('input', checkAddProctorDuplicate);
                document.querySelector('#addProctorForm select[name="campus"]')?.addEventListener('change', checkAddProctorDuplicate);
                document.getElementById('addProctorForm').onsubmit = async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const btn = document.getElementById('addProctorBtn');
                btn.disabled = true; btn.textContent = 'Creating...';
                const result = await window.flexamApi.proctors.create({
                    name: fd.get('proctorName'), college_program: fd.get('collegeProgram'),
                    email: fd.get('email'), phone: fd.get('phone') || '',
                    campus: fd.get('campus')
                });
                btn.disabled = false; btn.textContent = 'Create Proctor';
                if (result.success) { closeAddProctorModal(); showToast('Proctor created!'); await refreshAllData(); }
                else { const err = document.getElementById('addProctorErr'); err.textContent = result.message || 'Error'; err.classList.remove('hidden'); }
            };
        }
        function closeAddProctorModal() { const m = document.getElementById('addProctorModal'); if (m) m.remove(); }

        function editProctor(proctorId) {
            const proctor = findById('proctor', proctorId);
            if (!proctor) { showToast('Proctor not found', 'error'); return; }
            if (document.getElementById('editProctorModal')) return;
            const modal = document.createElement('div');
            modal.id = 'editProctorModal';
            modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
            modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="flex justify-between items-center px-6 py-4 border-b border-slate-200">
                    <h2 class="text-xl font-bold">Edit Proctor</h2>
                    <button type="button" onclick="closeEditProctorModal()" class="text-slate-400 hover:text-slate-600"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <form id="editProctorForm" class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Proctor Name *</label><input type="text" name="proctorName" value="${esc(proctor.name || '')}" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">College/Program *</label><input type="text" name="collegeProgram" value="${esc(proctor.collegeProgram || proctor.college_program || '')}" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Email *</label><input type="email" name="email" value="${esc(proctor.email || '')}" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required></div>
                        <div class="space-y-1.5 text-left"><label class="block text-sm font-medium text-slate-700">Phone</label><input type="tel" name="phone" value="${proctor.phone && proctor.phone !== 'N/A' ? esc(proctor.phone) : ''}" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm"></div>
                        <div class="space-y-1.5 text-left md:col-span-2"><label class="block text-sm font-medium text-slate-700">Campus *</label>${campusSelectHtml('campus', proctor.campus || '')}</div>
                    </div>
                    <div id="editProctorErr" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
                    <div class="flex gap-3 mt-5"><button type="submit" id="editProctorBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">Update Proctor</button><button type="button" onclick="closeEditProctorModal()" class="px-6 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200 text-sm">Cancel</button></div>
                </form>
            </div>`;
            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            document.getElementById('editProctorForm').onsubmit = async (e) => {
                e.preventDefault();
                const fd = new FormData(e.target);
                const btn = document.getElementById('editProctorBtn');
                btn.disabled = true; btn.textContent = 'Updating...';
                const result = await window.flexamApi.proctors.update({
                    id: proctorId, name: fd.get('proctorName'),
                    college_program: fd.get('collegeProgram'), email: fd.get('email'),
                    phone: fd.get('phone') || '', campus: fd.get('campus')
                });
                btn.disabled = false; btn.textContent = 'Update Proctor';
                if (result.success) { closeEditProctorModal(); showToast('Proctor updated!'); await refreshAllData(); }
                else { const err = document.getElementById('editProctorErr'); err.textContent = result.message || 'Error'; err.classList.remove('hidden'); }
            };
        }
        function closeEditProctorModal() { const m = document.getElementById('editProctorModal'); if (m) m.remove(); }

        async function deleteProctor(proctorId) {
            if (!confirm('Delete this proctor?')) return;
            const result = await window.flexamApi.proctors.delete(proctorId);
            if (result.success) { showToast('Proctor deleted.'); await refreshAllData(); }
            else showToast(result.message || 'Error', 'error');
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 20. SCHEDULES CRUD (campus-aware)
        // ─────────────────────────────────────────────────────────────────────────
       function openAddScheduleModal() {
            if (document.getElementById('addScheduleModal')) return;

            // Only show available (non-blocked) rooms
            const availableRooms = allData.filter(d => d.type === 'room' && !d.locked);

            const modal = document.createElement('div');
            modal.id = 'addScheduleModal';
            modal.className = 'fixed inset-0 bg-black/50 z-50 overflow-y-auto';
            modal.innerHTML = `
            <div class="min-h-screen px-4 py-8 flex items-start justify-center">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="flex justify-between items-center px-8 py-6 border-b border-slate-200">
                    <h2 class="text-2xl font-bold">Create Exam Schedule</h2>
                    <button type="button" onclick="closeAddScheduleModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="addScheduleForm" class="px-8 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Course</label>
                            <select id="courseSelect" name="course" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                <option value="" disabled selected>Select Course</option>
                                ${(() => {
                                    const seen = {};
                                    allData.filter(d => d.type === 'course').forEach(c => {
                                        const key = (c.course_code || '').toLowerCase();
                                        if (!seen[key]) {
                                            seen[key] = { ...c, _colleges: [], _programs: [] };
                                        }
                                        // Collect unique colleges
                                        const col = (c.college || '').trim();
                                        if (col && !seen[key]._colleges.includes(col)) seen[key]._colleges.push(col);
                                        // Collect unique programs
                                        const prog = (c.program || '').trim();
                                        if (prog && !seen[key]._programs.includes(prog)) seen[key]._programs.push(prog);
                                        // Keep the best course_name (not equal to code)
                                        const cur = (seen[key].course_name || '').trim();
                                        const nw  = (c.course_name || '').trim();
                                        if (nw && nw.toLowerCase() !== key && cur.toLowerCase() === key) {
                                            seen[key].course_name = nw;
                                        }
                                    });
                                    return Object.values(seen)
                                        .sort((a, b) => (a.course_code||'').localeCompare(b.course_code||''))
                                        .map(c => `<option value="${dbId(c.id)}" data-college="${c._colleges[0]||''}" data-colleges="${encodeURIComponent(JSON.stringify(c._colleges))}" data-programs="${encodeURIComponent(JSON.stringify(c._programs))}">${c.course_code} - ${c.course_name}</option>`)
                                        .join('');
                                })()}
                            </select>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">College</label>
                            <div id="collegeFieldWrap">
                                <input type="text" id="collegeField" name="college" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500" placeholder="Auto-filled from course" readonly>
                            </div>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Program <span class="text-red-400">*</span></label>
                            <div id="programFieldWrap">
                                <input type="text" id="programField" name="program" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500" placeholder="Auto-filled from course" readonly>
                            </div>
                            <p class="text-[10px] text-slate-400">Auto-filled when course is selected. Select manually if multiple programs.</p>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Exam Type</label>
                            <select name="examType" id="addExamTypeSelect" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                <option value="" disabled selected>Select Type</option>
                                <option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option><option value="Others">Others</option>
                            </select>
                            <div id="addExamTypeOtherWrap" style="display:none;margin-top:8px;">
                                <label style="font-size:0.7rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">Specify Exam Type <span style="color:#ef4444">*</span></label>
                                <input type="text" id="addExamTypeOther" name="examTypeOther" placeholder="e.g. Qualifying Exam, Thesis Defense..." style="width:100%;padding:0.75rem 1rem;border:1px solid #10b981;border-radius:0.5rem;font-size:0.875rem;color:#1e293b;background:white;outline:none;box-sizing:border-box;">
                            </div>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Semester</label>
                            <select name="semester" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                <option value="" disabled selected>Select Semester</option>
                                <option>1st Semester</option><option>2nd Semester</option><option>Summer</option>
                            </select>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Year Level</label>
                            <select name="yearLevel" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                ${yearLevelOptions('')}
                            </select>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Section</label>
                            <input type="text" name="section" placeholder="e.g. 1-Y1-1" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm">
                            <p class="text-[10px] text-slate-400">Enter section number only (e.g. <strong>1-Y1-1</strong>). Program is stored separately.</p>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Date</label>
                            <input type="date" id="dateField" name="date" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" min="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Duration</label>
                            <select name="duration" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                <option>1 Hour</option><option>1.5 Hours</option><option>2 Hours</option><option>2.5 Hours</option><option>3 Hours</option>
                            </select>
                        </div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Campus *</label>
                            ${campusSelectHtml('campus')}
                        </div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Proctor</label>
                            <select name="proctor" multiple class="w-full min-h-[100px] px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm">
                                <option value="">No Proctor</option>
                                ${allData.filter(d=>d.type==='proctor').map(p=>`<option value="${dbId(p.id)}">${p.name}${p.campus?' ('+p.campus+')':''}</option>`).join('')}
                            </select>
                        </div>
                        <div class="space-y-2 text-left md:col-span-2">
    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
        Room
        <span class="ml-2 text-[10px] font-normal text-emerald-600 normal-case">(${availableRooms.length} available)</span>
    </label>
    <label class="flex items-center gap-2 mb-2 cursor-pointer select-none">
       <input type="checkbox" id="isOnlineCheck" name="is_online" value="1"
    class="w-4 h-4 rounded text-emerald-600 accent-emerald-600"
    onchange="handleOnlineToggle(this.checked)">
        <span class="text-sm font-medium text-slate-600">🌐 Online Exam <span class="text-xs text-slate-400 font-normal">(no room required)</span></span>
    </label>
    <div id="roomSelectWrap">
        ${availableRooms.length === 0
            ? `<div class="w-full px-4 py-3 border border-red-200 rounded-lg bg-red-50 text-sm text-red-600 font-medium">
                   ⚠️ No rooms are currently available. All rooms are blocked.
               </div>`
            : `<select id="roomSelect" name="room" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                   <option value="" disabled selected>Select Room</option>
                    ${getRoomOptions()}
               </select>`}
    </div>
</div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Select Time Slot</label>
                            <div id="timeSlotContainer" class="border border-slate-200 rounded-lg p-4 bg-slate-50 text-center text-sm text-slate-400">Select date and room first</div>
                        </div>
                    </div>
                    <div id="addScheduleErr" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="closeAddScheduleModal()" class="px-8 py-3.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200">Cancel</button>
                        <button type="submit" id="addScheduleBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3.5 rounded-lg transition" ${availableRooms.length === 0 ? 'disabled title="No available rooms"' : ''}>Save Schedule</button>
                    </div>
                </form>
            </div>
            </div>`;
            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            const modalCampus = document.querySelector('#addScheduleModal select[name="campus"]');
            if (modalCampus) {
                modalCampus.addEventListener('change', function() {
                    const selectedCampus = this.value;

        // Filter rooms by campus
        const roomSelect = document.getElementById('roomSelect');
        if (roomSelect) {
            roomSelect.innerHTML = '<option value="" disabled selected>Select Room</option>' + 
                                   getRoomOptions('', selectedCampus);
        }

        // Filter proctors by campus
        const proctorSelect = document.querySelector('#addScheduleModal select[name="proctor"]');
        if (proctorSelect) {
            const filteredProctors = allData.filter(d =>
                d.type === 'proctor' && (!selectedCampus || (d.campus || '') === selectedCampus)
            );
            proctorSelect.innerHTML = `<option value="">No Proctor</option>` +
                filteredProctors.map(p =>
                    `<option value="${dbId(p.id)}">${p.name}${p.campus ? ' (' + p.campus + ')' : ''}</option>`
                ).join('');
        }
    });

    window.handleOnlineToggle = function(checked) {
    const roomWrap = document.getElementById('roomSelectWrap');
    if (roomWrap) roomWrap.style.display = checked ? 'none' : 'block';
    const roomSel = document.getElementById('roomSelect');
    if (roomSel) roomSel.required = !checked;

    if (!checked) { updateTimeSlots(); return; }

    const date = document.getElementById('dateField')?.value;
    const container = document.getElementById('timeSlotContainer');
    if (!container) return;
    if (!date) {
        container.innerHTML = '<div class="text-center text-sm text-slate-400">Select a date first</div>';
        return;
    }

    const durationStr = document.querySelector('#addScheduleForm select[name="duration"]')?.value || '1 Hour';
    const dur = parseFloat(durationStr) || 1;
    const durMins = Math.round(dur * 60);
    const startTimes = [420,480,540,600,660,780,840,900,960,1020,1080,1140,1200];
    const LUNCH_START = 720, LUNCH_END = 780, DAY_END = 1260;

    function toAMPM(m) {
        const h = Math.floor(m/60), min = m%60, ap = h>=12?'PM':'AM';
        const h12 = h>12 ? h-12 : h===0 ? 12 : h;
        return String(h12).padStart(2,'0') + ':' + String(min).padStart(2,'0') + ' ' + ap;
    }

    const now = new Date();
    const todayStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
    const nowMins = date === todayStr ? now.getHours()*60 + now.getMinutes() : 0;

    const slots = [];
    for (const start of startTimes) {
        const end = start + durMins;
        if (end > DAY_END) continue;
        slots.push({ label: toAMPM(start) + ' - ' + toAMPM(end), past: date === todayStr && start <= nowMins });
    }

    if (!slots.length) {
        container.innerHTML = '<div class="text-center text-sm text-orange-500">No slots fit this duration.</div>';
        return;
    }

    container.innerHTML = '<div class="grid grid-cols-2 gap-2">' + slots.map(({label, past}) => {
        const cls = past
            ? 'border-slate-200 bg-slate-50 cursor-not-allowed opacity-50'
            : 'border-slate-200 bg-white cursor-pointer hover:border-emerald-500';
        const textCls = past ? 'text-slate-400' : 'text-slate-700';
        const badge = past ? '<span class="ml-auto text-xs text-slate-400 font-semibold">Past</span>' : '';
        return `<label class="flex items-center gap-2 p-3 border ${cls} rounded-lg transition">
            <input type="radio" name="timeSlot" value="${label}" ${past ? 'disabled' : ''} class="text-emerald-600" required>
            <span class="text-sm ${textCls}">${label}</span>${badge}
        </label>`;
    }).join('') + '</div>';
};
}

            const courseSelect = document.getElementById('courseSelect');
            const collegeField = document.getElementById('collegeField');
            const dateField    = document.getElementById('dateField');
            const roomSelect   = document.getElementById('roomSelect');

            // Helper: given a college + course code, return programs from allData course records
            // that match both that college and that course code.
            function getAddProgramsForCollege(collegeValue, allPrograms, courseCode) {
                if (!collegeValue) return allPrograms;
                const cv  = collegeValue.trim().toLowerCase();
                const cc  = (courseCode || '').trim().toLowerCase();

                // Primary: pull programs directly from allData course records
                if (cc) {
                    const matched = [...new Set(
                        allData
                            .filter(d =>
                                d.type === 'course' &&
                                (d.course_code || '').trim().toLowerCase() === cc &&
                                (d.college || '').trim().toLowerCase() === cv
                            )
                            .map(d => (d.program || '').trim())
                            .filter(Boolean)
                    )];
                    if (matched.length > 0) return matched;
                }

                // Fallback: intersect allPrograms with the college record's own program list
                const colRec = allData.find(d =>
                    d.type === 'college' && (
                        (d.code || '').toLowerCase() === cv ||
                        (d.name || '').toLowerCase() === cv
                    )
                );
                if (!colRec || !Array.isArray(colRec.programs) || colRec.programs.length === 0) return allPrograms;
                const colProgs = colRec.programs.map(p =>
                    (p.includes('=') ? p.split('=')[0].trim() : p.trim()).toLowerCase()
                );
                const filtered = allPrograms.filter(p => colProgs.includes(p.toLowerCase()));
                return filtered.length > 0 ? filtered : allPrograms;
            }

            // Helper: render program field into #programFieldWrap based on current college + course code
            function renderAddProgramField(allPrograms, courseCode) {
                const progWrap        = document.getElementById('programFieldWrap');
                const collegeEl       = document.getElementById('collegeField');
                const selectedCollege = collegeEl ? (collegeEl.value || '') : '';
                const filtered        = getAddProgramsForCollege(selectedCollege, allPrograms, courseCode);

                if (filtered.length > 1) {
                    progWrap.innerHTML = `<select id="programField" name="program"
                        class="w-full px-4 py-3 border border-emerald-400 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                        ${filtered.map(p => `<option value="${p}">${p}</option>`).join('')}
                    </select>`;
                } else {
                    progWrap.innerHTML = `<input type="text" id="programField" name="program"
                        value="${filtered[0] || ''}"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500"
                        placeholder="Auto-filled from course" readonly>`;
                }
            }

            if (courseSelect) {
                courseSelect.addEventListener('change', function() {
                    const opt      = this.options[this.selectedIndex];
                    const rawCols  = opt.getAttribute('data-colleges') || '';
                    const rawProgs = opt.getAttribute('data-programs') || '';
                    const colleges = rawCols  ? JSON.parse(decodeURIComponent(rawCols))  : [];
                    const programs = rawProgs ? JSON.parse(decodeURIComponent(rawProgs)) : [];
                    const colWrap  = document.getElementById('collegeFieldWrap');

                    const selectedCourseCode = opt.text.split(' - ')[0].trim();

                    // College field
                    if (colleges.length > 1) {
                        colWrap.innerHTML = `<select id="collegeField" name="college"
                            class="w-full px-4 py-3 border border-emerald-400 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                            ${colleges.map(col => `<option value="${col}">${col}</option>`).join('')}
                        </select>`;
                        // Wire college change → re-filter programs by selected college + course code
                        document.getElementById('collegeField').addEventListener('change', function() {
                            renderAddProgramField(programs, selectedCourseCode);
                        });
                    } else {
                        colWrap.innerHTML = `<input type="text" id="collegeField" name="college"
                            value="${colleges[0] || ''}"
                            class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500"
                            placeholder="Auto-filled from course" readonly>`;
                    }

                    // Program field — filtered by the initially-selected college + course code
                    renderAddProgramField(programs, selectedCourseCode);
                    updateTimeSlots();
                });
            }
            if (dateField) dateField.addEventListener('change', () => {
    const isOnline = document.getElementById('isOnlineCheck')?.checked;
    if (isOnline) {
        handleOnlineToggle(true);
    } else {
        updateTimeSlots();
    }
});
            if (roomSelect) roomSelect.addEventListener('change', updateTimeSlots);
            const durationSel = document.querySelector('#addScheduleForm select[name="duration"]');
            if (durationSel) durationSel.addEventListener('change', () => {
    const isOnline = document.getElementById('isOnlineCheck')?.checked;
    if (isOnline) {
        handleOnlineToggle(true);
    } else {
        updateTimeSlots();
    }
});

            function generateTimeSlots(durationStr, selectedDate) {
                const dur = parseFloat(durationStr) || 1;
                const durMins = Math.round(dur * 60);
                const startTimes = [420,480,540,600,660,780,840,900,960,1020,1080,1140,1200];
                const LUNCH_START=720, LUNCH_END=780, DAY_END=1260;
                function toAMPM(m) {
                    const h=Math.floor(m/60), min=m%60, ap=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
                    return String(h12).padStart(2,'0')+':'+String(min).padStart(2,'0')+' '+ap;
                }
                // If the selected date is today, get current time in minutes and block past slots
                const now = new Date();
                const todayStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
                const isToday = selectedDate === todayStr;
                const nowMins = isToday ? now.getHours()*60 + now.getMinutes() : 0;
                const slots=[];
                for (const start of startTimes) {
                    const end=start+durMins;
                    if (end>DAY_END) continue;
                    slots.push({ label: toAMPM(start)+' - '+toAMPM(end), past: isToday && start <= nowMins });
                }
                return slots;
            }

            function updateTimeSlots() {
                const date = dateField?.value, room = roomSelect?.value;
                const duration = document.querySelector('#addScheduleForm select[name="duration"]')?.value || '1 Hour';
                const container = document.getElementById('timeSlotContainer');
                if (!container) return;
                if (!date || !room) { container.innerHTML = '<div class="text-center text-sm text-slate-400">Select date and room first</div>'; return; }
                const slots = generateTimeSlots(duration, date);
                if (slots.length===0) { container.innerHTML='<div class="text-center text-sm text-orange-500">No slots fit this duration in the day.</div>'; return; }
                const occupied = allData.filter(d => d.type==='schedule' && d.exam_date===date && dbId(String(d.room_id||d.room||''))===room).map(s => s.time_slot||s.timeSlot);
                container.innerHTML = `<div class="grid grid-cols-2 gap-2">${slots.map(({label: slot, past}) => {
                    const occ = occupied.includes(slot);
                    const disabled = occ || past;
                    const cls = occ ? 'border-red-200 bg-red-50 cursor-not-allowed'
                               : past ? 'border-slate-200 bg-slate-50 cursor-not-allowed opacity-50'
                               : 'border-slate-200 bg-white cursor-pointer hover:border-emerald-500';
                    const textCls = occ ? 'text-red-400 line-through' : past ? 'text-slate-400' : 'text-slate-700';
                    const badge = occ ? '<span class="ml-auto text-xs text-red-500 font-semibold">Occupied</span>'
                                : past ? '<span class="ml-auto text-xs text-slate-400 font-semibold">Past</span>' : '';
                    return `<label class="flex items-center gap-2 p-3 border ${cls} rounded-lg transition">
                        <input type="radio" name="timeSlot" value="${slot}" ${disabled?'disabled':''} class="text-emerald-600" required>
                        <span class="text-sm ${textCls}">${slot}</span>
                        ${badge}
                    </label>`;
                }).join('')}</div>`;
                function checkAddScheduleDuplicate() {
                const course   = document.getElementById('courseSelect')?.value || '';
                const type     = getEffectiveExamType('addScheduleForm');
                const date     = document.getElementById('dateField')?.value || '';
                const campus   = document.querySelector('#addScheduleForm select[name="campus"]')?.value || '';
                const timeSlot = document.querySelector('#addScheduleForm input[name="timeSlot"]:checked')?.value || '';
                const section  = (document.querySelector('#addScheduleForm input[name="section"]')?.value || '').trim().toLowerCase();
                const college  = (document.querySelector('#addScheduleForm select[name="college"]')?.value || '').trim().toLowerCase();
                if (!course || !type || !date || !campus) { checkAndShowDuplicate('addScheduleForm', 'addSchedDupBanner', false); return; }
                // Only flag duplicate if the SAME section has the SAME course + exam_type on the same date.
                // Different sections are allowed to schedule the same course on the same day (at different times).
                const dup = allData.find(d =>
                    d.type === 'schedule' &&
                    (d.status || '') !== 'Rejected' &&
                    dbId(String(d.course_id || '')) === course &&
                    (d.exam_type || '') === type &&
                    (d.exam_date || d.date || '') === date &&
                    (d.campus || '') === campus &&
                    (section ? (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === section : true) &&
                    (college  ? (d.college || '').trim().toLowerCase() === college : true) &&
                    (!timeSlot || (d.time_slot || '') === timeSlot)
            );
            checkAndShowDuplicate('addScheduleForm', 'addSchedDupBanner', !!dup,
                `Section "<strong>${section.toUpperCase()}</strong>" already has a <strong>${type}</strong> exam for this course on <strong>${date}</strong> in the <strong>${campus}</strong> campus${timeSlot ? ' at <strong>' + timeSlot + '</strong>' : ''}.`
            );
        }
            document.getElementById('courseSelect')?.addEventListener('change', checkAddScheduleDuplicate);
            document.querySelector('#addScheduleForm select[name="examType"]')?.addEventListener('change', checkAddScheduleDuplicate);

            document.getElementById('dateField')?.addEventListener('change', checkAddScheduleDuplicate);
            document.querySelector('#addScheduleForm select[name="campus"]')?.addEventListener('change', checkAddScheduleDuplicate);
            // Hook into time slot clicks
            document.getElementById('timeSlotContainer')?.addEventListener('click', () => setTimeout(checkAddScheduleDuplicate, 50));
            }

            // ── Others exam type helpers ─────────────────────────────────────
                function getEffectiveExamType(formId) {
                    const sel = document.querySelector('#' + formId + ' select[name="examType"]');
                    if (!sel) return '';
                    if (sel.value === 'Others') {
                        return (document.querySelector('#' + formId + ' input[name="examTypeOther"]')?.value || '').trim();
                    }
                    return sel.value;
                }
                // Wire add-modal exam type select → show/hide textbox
                (function() {
                    const sel  = document.getElementById('addExamTypeSelect');
                    const wrap = document.getElementById('addExamTypeOtherWrap');
                    const inp  = document.getElementById('addExamTypeOther');
                    if (sel && wrap && inp) {
                        sel.addEventListener('change', function() {
                            if (this.value === 'Others') {
                                wrap.style.display = 'block';
                                inp.required = true;
                                inp.focus();
                            } else {
                                wrap.style.display = 'none';
                                inp.required = false;
                                inp.value = '';
                            }
                        });
                    }
                })();

            document.getElementById('addScheduleForm').onsubmit = async (e) => {
            e.preventDefault();
            const fd  = new FormData(e.target);
            const btn = document.getElementById('addScheduleBtn');
            const errEl = document.getElementById('addScheduleErr');
            btn.disabled = true; btn.textContent = 'Saving...';
            errEl.classList.add('hidden');

            // ── Block validation ──────────────────────────────────────────────
            const chosenRoomId = fd.get('room');
            const chosenRoom   = allData.find(d => d.type === 'room' && dbId(String(d.id)) === chosenRoomId);
            if (chosenRoom && chosenRoom.locked) {
                errEl.textContent = `Room "${chosenRoom.name}" is currently blocked and cannot be scheduled.`;
                errEl.classList.remove('hidden');
                btn.disabled = false; btn.textContent = 'Save Schedule';
                return;
            }

            // ── Duplicate check: same campus + course + exam_type + date + time_slot ──
            const newCampus   = fd.get('campus')    || '';
            const newCourse   = fd.get('course')    || '';
            const _rawExamType = fd.get('examType') || '';
            const newType     = _rawExamType === 'Others' ? (fd.get('examTypeOther') || '').trim() : _rawExamType;
            const newDate     = fd.get('date')      || '';
            const newTimeSlot = fd.get('timeSlot')  || '';
            const newSection  = (fd.get('section')  || '').trim().toLowerCase();
            const newCollege  = (fd.get('college')  || '').trim().toLowerCase();

            const duplicate = allData.find(d =>
                d.type === 'schedule' &&
                (d.status || '') !== 'Rejected' &&
                (d.campus || '') === newCampus &&
                dbId(String(d.course_id || '')) === newCourse &&
                (d.exam_type || '') === newType &&
                (d.exam_date || d.date || '') === newDate &&
                (d.time_slot || '') === newTimeSlot
            );

            if (duplicate) {
                errEl.textContent = `❌ A schedule for this course already exists in the ${newCampus} campus on the same date and time slot. Please choose a different campus, date, or time slot.`;
                errEl.classList.remove('hidden');
                btn.disabled = false; btn.textContent = 'Save Schedule';
                return;
            }

            // ── Duplicate section check: same course + college + section + exam_type + date ─────────────────────────
            if (newSection && newCollege) {
                const sectionDup = allData.find(d =>
                    d.type === 'schedule' &&
                    (d.status || '') !== 'Rejected' &&
                    (d.exam_type || '') === newType &&
                    (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === newSection &&
                    (d.college || '').trim().toLowerCase() === newCollege &&
                    String(d.course_id || d.course || '') === String(newCourse) &&
                    (d.exam_date || d.date || '').trim() === newDate
                );
                if (sectionDup) {
                    const _dd = (sectionDup.exam_date||'').trim(), _dr = (sectionDup.room_name||'').trim();
                    errEl.textContent = `❌ Section "${fd.get('section')}" under ${fd.get('college')} already has a ${newType} exam for this course${_dd?' on '+_dd:''}${_dr?' in '+_dr:''}.`;
                    errEl.classList.remove('hidden');
                    btn.disabled = false; btn.textContent = 'Save Schedule';
                    return;
                }
            }

            // ── Section time overlap: same section in two rooms at the same time ──────────
            if (newSection && newDate && newTimeSlot) {
                const sectionTimeConflict = allData.find(d =>
                    d.type === 'schedule' &&
                    (d.status || '') !== 'Rejected' &&
                    (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === newSection &&
                    (d.college || '').trim().toLowerCase() === newCollege &&
                    (d.exam_date || d.date || '') === newDate &&
                    (d.time_slot || '') === newTimeSlot
                );
                if (sectionTimeConflict) {
                    errEl.textContent = `❌ Section "${fd.get('section')}" (${fd.get('college') || '—'}) already has an exam at ${newTimeSlot} on ${newDate} (${sectionTimeConflict.course_code || '—'}). Students cannot be in two places at once.`;
                    errEl.classList.remove('hidden');
                    btn.disabled = false; btn.textContent = 'Save Schedule';
                    return;
                }
            }

            // ── Proctor conflict: same proctor assigned to two rooms at the same time ──────
            const newProctorId = fd.get('proctor') || '';
            if (newProctorId && newDate && newTimeSlot) {
                const proctorConflict = allData.find(d =>
                    d.type === 'schedule' &&
                    (d.status || '') !== 'Rejected' &&
                    String(d.proctor_id || '') === String(newProctorId) &&
                    (d.exam_date || d.date || '') === newDate &&
                    (d.time_slot || '') === newTimeSlot
                );
                if (proctorConflict) {
                    const pName = allData.find(d => d.type === 'proctor' && dbId(String(d.id)) === newProctorId)?.name || 'This proctor';
                    errEl.textContent = `❌ "${pName}" is already assigned to another exam at ${newTimeSlot} on ${newDate} (Room: ${proctorConflict.room_name || '—'}).`;
                    errEl.classList.remove('hidden');
                    btn.disabled = false; btn.textContent = 'Save Schedule';
                    return;
                }
            }

            const result = await window.flexamApi.schedules.create({
            course_id:     newCourse,
            college:       fd.get('college'),
            program:       fd.get('program') || '',
            exam_type:     newType,
            semester:      fd.get('semester') || '',
            year_level:    fd.get('yearLevel'),
            section:       fd.get('section') || '',       // ← ADD THIS
            section_name:  fd.get('section') || '',       // ← ADD THIS
            class_section: fd.get('section') || '',       // ← ADD THIS
            exam_date:     newDate,
            time_slot:     newTimeSlot,
            duration:      fd.get('duration'),
            room_id:       document.getElementById('isOnlineCheck')?.checked ? null : chosenRoomId,
            is_online:     document.getElementById('isOnlineCheck')?.checked ? 1 : 0,
            proctor_id:    fd.get('proctor') || null,
            campus:        newCampus,
            status:        'Pending'
        });
            btn.disabled = false; btn.textContent = 'Save Schedule';
            if (result.success) {
                const courseCode = allData.find(d => d.type === 'course' && String(dbId(String(d.id))) === String(newCourse))?.course_code || '';
                activityLog('Schedule Created', `${courseCode || ''} · ${newType} · ${newDate} · ${newCampus}`);
                closeAddScheduleModal(); showToast('Schedule created! Pending approval.'); await refreshAllData();
            }
            else { errEl.textContent = result.message || 'Error'; errEl.classList.remove('hidden'); }
                };  // closes addScheduleForm.onsubmit
            }  // ← THIS CLOSES openAddScheduleModal
                function closeAddScheduleModal() { const m = document.getElementById('addScheduleModal'); if (m) m.remove(); }

            // ═══════════════════════════════════════════════════════════════════
            // SPECIAL EXAM REGISTRATION — Modal, CRUD, Filter, PDF Export
            // ═══════════════════════════════════════════════════════════════════

            window.openSpecialExamModal = async function(editId) {
                if (document.getElementById('specialExamModal')) return;
                const isEdit = !!editId;
                const existing = isEdit ? (window._specialExams||[]).find(e => String(e.id) === String(editId)) : null;

                // Build college/program options
                const collegeMap = {};
                allData.filter(d => d.type === 'college' && d.code).forEach(c => {
                    if (!collegeMap[c.code]) collegeMap[c.code] = { name: c.name||'', programs: [] };
                    // Expand the programs array (e.g. "BSIT=Bachelor of Science..." → "BSIT")
                    if (Array.isArray(c.programs)) {
                        c.programs.forEach(p => {
                            const name = (p.includes('=') ? p.split('=')[0].trim() : p.trim());
                            if (name && !collegeMap[c.code].programs.includes(name))
                                collegeMap[c.code].programs.push(name);
                        });
                    }
                    // Also handle single c.program field if present
                    if (c.program && !collegeMap[c.code].programs.includes(c.program)) {
                        collegeMap[c.code].programs.push(c.program);
                    }
                });
                // Also gather programs from courses
                allData.filter(d => d.type === 'course' && d.college).forEach(c => {
                    const code = (c.college||'').trim();
                    if (!collegeMap[code]) collegeMap[code] = { name:'', programs: [] };
                    if (c.program && !collegeMap[code].programs.includes(c.program)) {
                        collegeMap[code].programs.push(c.program);
                    }
                });

                const collegeOptions = Object.keys(collegeMap).sort().map(code => {
                    const label = collegeMap[code].name ? `${code} — ${collegeMap[code].name}` : code;
                    return `<option value="${code}" data-programs="${encodeURIComponent(JSON.stringify(collegeMap[code].programs))}" ${existing&&existing.college===code?'selected':''}>${label}</option>`;
                }).join('');

                // Current SY default
                const cy = new Date().getFullYear();
                const defaultSY = `${cy}-${cy+1}`;

                const modal = document.createElement('div');
                modal.id = 'specialExamModal';
                modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
                modal.innerHTML = `
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl flex flex-col" style="max-height:90vh;">
                    <!-- Sticky Header -->
                    <div class="flex justify-between items-center px-8 py-5 bg-gradient-to-r from-purple-600 to-purple-700 rounded-t-2xl flex-shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-white leading-tight">Special Exam Registration</h2>
                                <p class="text-purple-200 text-xs mt-0.5">Fill in all required fields marked with *</p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('specialExamModal').remove()" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/70 hover:text-white hover:bg-white/20 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form id="specialExamForm" class="overflow-y-auto flex-1 px-8 py-6 space-y-5" style="scrollbar-width:thin;scrollbar-color:#c4b5fd #f1f5f9;">
                        <!-- Section 1: Exam Details -->
                        <div class="flex items-center gap-2 mb-1">
                            <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">1</span>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Exam Details</span>
                            <div class="flex-1 h-px bg-slate-100"></div>
                        </div>
                        <!-- Row 1: SY + Semester -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                            <div class="space-y-2 text-left">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">School Year <span class="text-red-400">*</span></label>
                                <input type="text" name="school_year" placeholder="e.g. 2025-2026" value="${existing?esc(existing.school_year||''):''}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                            </div>
                            <div class="space-y-2 text-left">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Semester <span class="text-red-400">*</span></label>
                                <select name="semester" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                                    <option value="" disabled ${!existing?'selected':''}>Select Semester</option>
                                    <option ${existing&&existing.semester==='1st Semester'?'selected':''}>1st Semester</option>
                                    <option ${existing&&existing.semester==='2nd Semester'?'selected':''}>2nd Semester</option>
                                    <option ${existing&&existing.semester==='Summer'?'selected':''}>Summer</option>
                                </select>
                            </div>
                        </div>
                        <!-- Row 2: Special Exam Type -->
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Special Exam Type <span class="text-red-400">*</span></label>
                            <select name="exam_type" id="spExamTypeSelect" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-purple-400 outline-none text-sm" required onchange="spExamTypeChanged(this)">
                                <option value="" disabled ${!existing?'selected':''}>Select Exam Type</option>
                                <option ${existing&&existing.exam_type==='Prelim'?'selected':''}>Prelim</option>
                                <option ${existing&&existing.exam_type==='Midterm'?'selected':''}>Midterm</option>
                                <option ${existing&&existing.exam_type==='Final'?'selected':''}>Final</option>
                                <option ${existing&&existing.exam_type==='Summer'?'selected':''}>Summer</option>
                                <option value="Others" ${existing&&!['Prelim','Midterm','Final','Summer'].includes(existing.exam_type)&&existing.exam_type?'selected':''}>Others</option>
                            </select>
                            <div id="spExamTypeOtherWrap" style="display:${existing&&!['Prelim','Midterm','Final','Summer'].includes(existing.exam_type)&&existing.exam_type?'block':'none'};margin-top:8px;">
                                <input type="text" name="exam_type_other" id="spExamTypeOther" placeholder="e.g. Qualifying Exam, Completion Exam..." value="${existing&&!['Prelim','Midterm','Final','Summer'].includes(existing.exam_type)?esc(existing.exam_type||''):''}" class="w-full px-4 py-3 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-400 outline-none text-sm">
                            </div>
                        </div>
                        <!-- Section 2: Student Information -->
                        <div class="flex items-center gap-2 pt-1">
                            <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">2</span>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Student Information</span>
                            <div class="flex-1 h-px bg-slate-100"></div>
                        </div>
                        <!-- Row 3: Student No + Last + First Name -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
                            <div class="space-y-2 text-left">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Student No. <span class="text-red-400">*</span></label>
                                <input type="text" name="student_no" placeholder="0000-000-0000" maxlength="12" oninput="this.value=this.value.replace(/[^0-9-]/g,'')" value="${existing?esc(existing.student_no||''):''}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                            </div>
                            <div class="space-y-2 text-left">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Last Name <span class="text-red-400">*</span></label>
                                <input type="text" name="last_name" placeholder="e.g. Dela Cruz" value="${existing?esc(existing.last_name||''):''}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                            </div>
                            <div class="space-y-2 text-left">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">First Name <span class="text-red-400">*</span></label>
                                <input type="text" name="first_name" placeholder="e.g. Juan" value="${existing?esc(existing.first_name||''):''}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                            </div>
                        </div>
                        <!-- Row 4: Campus (locked) -->
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Campus</label>
                            <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 font-semibold">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                ${currentUser.campus}
                            </div>
                            <input type="hidden" name="campus" value="${currentUser.campus}">
                        </div>
                        <!-- Row 5: College + Program -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                            <div class="space-y-2 text-left">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">College <span class="text-red-400">*</span></label>
                                <select name="college" id="spExamCollege" onchange="spExamCollegeChanged(this)" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                                    <option value="" disabled ${!existing?'selected':''}>Select College</option>
                                    ${collegeOptions}
                                </select>
                            </div>
                            <div class="space-y-2 text-left">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Program</label>
                                <select name="program" id="spExamProgram" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-purple-400 outline-none text-sm">
                                    <option value="">Select College first</option>
                                    ${existing&&existing.program?`<option value="${esc(existing.program)}" selected>${esc(existing.program)}</option>`:''}
                                </select>
                            </div>
                        </div>
                        <!-- Section 3: Registration Details -->
                        <div class="flex items-center gap-2 pt-1">
                            <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">3</span>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Registration Details</span>
                            <div class="flex-1 h-px bg-slate-100"></div>
                        </div>
                        <!-- Row 5: Receipt No -->
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Receipt No. <span class="text-red-400">*</span></label>
                            <input type="text" name="receipt_no" placeholder="e.g. OR-2024-00456" value="${existing?esc(existing.receipt_no||''):''}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                        </div>
                        <!-- Row 6: Reason -->
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Reason for Special Exam <span class="text-red-400">*</span></label>
                            <select name="reason_select" id="spExamReasonSelect" onchange="spExamReasonChanged(this)" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-purple-400 outline-none text-sm">
                                <option value="">— Select Reason —</option>
                                <option value="Personal Reason" ${existing&&existing.reason==='Personal Reason'?'selected':''}>Personal Reason</option>
                                <option value="Emergency" ${existing&&existing.reason==='Emergency'?'selected':''}>Emergency</option>
                                <option value="Failure to attend on time" ${existing&&existing.reason==='Failure to attend on time'?'selected':''}>Failure to attend on time</option>
                                <option value="Others" ${existing&&existing.reason&&!['Personal Reason','Emergency','Failure to attend on time'].includes(existing.reason)?'selected':''}>Others</option>
                            </select>
                            <div id="spExamReasonOtherWrap" style="display:${existing&&existing.reason&&!['Personal Reason','Emergency','Failure to attend on time'].includes(existing.reason)?'block':'none'}">
                                <input type="text" name="reason_other" id="spExamReasonOther" value="${existing&&existing.reason&&!['Personal Reason','Emergency','Failure to attend on time'].includes(existing.reason)?esc(existing.reason):''}" placeholder="Please specify your reason..." class="w-full px-4 py-3 border border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-400 outline-none text-sm">
                            </div>
                        </div>
                        <!-- Section 4: Course Selection -->
                        <div class="flex items-center gap-2 pt-1">
                            <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">4</span>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Course Selection</span>
                            <div class="flex-1 h-px bg-slate-100"></div>
                        </div>
                        <!-- Row 7: No. of Exams + dynamic course list -->
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">No. of Exams <span class="text-red-400">*</span></label>
                            <select name="num_exams" id="spExamNumExams" onchange="spExamNumChanged(this)" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-purple-400 outline-none text-sm" required>
                                <option value="" disabled ${!existing?'selected':''}>Select number</option>
                                ${[1,2,3,4,5,6,7,8,9,10].map(n=>`<option value="${n}" ${existing&&Number(existing.num_exams)===n?'selected':''}>${n}</option>`).join('')}
                            </select>
                        </div>
                        <!-- Dynamic courses table -->
                        <div id="spExamCoursesWrap" style="display:${existing&&existing.num_exams>0?'block':'none'};" class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Select Courses for Special Exam</label>
                            <div class="border border-purple-100 rounded-lg overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-purple-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-[10px] font-bold text-purple-500 uppercase">#</th>
                                            <th class="px-3 py-2 text-left text-[10px] font-bold text-purple-500 uppercase">Course</th>
                                        </tr>
                                    </thead>
                                    <tbody id="spExamCoursesBody">
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-[10px] text-slate-400">Select the courses for each special exam slot.</p>
                        </div>
                        <input type="hidden" name="exam_courses" id="spExamCoursesHidden" value="${existing?esc(existing.exam_courses||''):''}">
                        <div id="spExamErr" class="hidden mt-1 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
                    </form>
                    <!-- Sticky Footer -->
                    <div class="px-8 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex-shrink-0">
                        <div class="flex gap-3">
                            <button type="button" onclick="document.getElementById('specialExamModal').remove()" class="flex-1 px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-xl transition border border-slate-200 text-sm">Cancel</button>
                            <button type="button" onclick="document.getElementById('specialExamForm').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}))" class="flex-1 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold py-2.5 rounded-xl transition text-sm flex items-center justify-center gap-2 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${isEdit?'M5 13l4 4L19 7':'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'}"/></svg>
                                ${isEdit ? 'Save Changes' : 'Register Student'}
                            </button>
                        </div>
                    </div>
                </div>`;
                document.body.appendChild(modal);

                // Wire SY + Semester inputs — changing either rebuilds the course list
                setTimeout(() => {
                    const syEl  = document.querySelector('#specialExamModal [name="school_year"]');
                    const semEl = document.querySelector('#specialExamModal [name="semester"]');
                    const numEl = document.getElementById('spExamNumExams');
                    const rebuildCourses = () => { if (numEl && numEl.value) spExamNumChanged(numEl); };
                    if (syEl)  syEl.addEventListener('input',  rebuildCourses);
                    if (semEl) semEl.addEventListener('change', rebuildCourses);
                }, 0);

                // If editing, pre-populate programs
                if (existing && existing.college) {
                    setTimeout(() => {
                        const colSel = document.getElementById('spExamCollege');
                        if (colSel) spExamCollegeChanged(colSel, existing.program);
                        if (existing.num_exams) {
                            const numSel = document.getElementById('spExamNumExams');
                            if (numSel) spExamNumChanged(numSel, JSON.parse(existing.exam_courses||'[]'));
                        }
                    }, 0);
                }

                document.getElementById('specialExamForm').onsubmit = async function(e) {
                    e.preventDefault();
                    const fd = new FormData(this);
                    let examType = fd.get('exam_type');
                    if (examType === 'Others') examType = (fd.get('exam_type_other')||'').trim();
                    if (!examType) { document.getElementById('spExamErr').classList.remove('hidden'); document.getElementById('spExamErr').textContent = 'Please specify the exam type.'; return; }
                    let reason = fd.get('reason_select');
                    if (!reason) { document.getElementById('spExamErr').classList.remove('hidden'); document.getElementById('spExamErr').textContent = 'Please select a reason for the special exam.'; return; }
                    if (reason === 'Others') { reason = (fd.get('reason_other')||'').trim(); if (!reason) { document.getElementById('spExamErr').classList.remove('hidden'); document.getElementById('spExamErr').textContent = 'Please specify your reason.'; return; } }

                    // Collect course selections
                    const courseRows = document.querySelectorAll('#spExamCoursesBody tr');
                    const examCourses = [];
                    courseRows.forEach(row => {
                        const cSel = row.querySelector('select.sp-course-sel');
                        if (cSel) examCourses.push({ course_id: cSel.value, course_label: cSel.options[cSel.selectedIndex]?.text||'' });
                    });

                    const payload = {
                        school_year: fd.get('school_year'),
                        semester: fd.get('semester'),
                        exam_type: examType,
                        student_no: fd.get('student_no'),
                        last_name: fd.get('last_name'),
                        first_name: fd.get('first_name'),
                        college: fd.get('college'),
                        program: fd.get('program'),
                        receipt_no: fd.get('receipt_no'),
                        reason: reason,
                        num_exams: fd.get('num_exams'),
                        exam_courses: JSON.stringify(examCourses),
                        campus: currentUser.campus || '',
                    };
                    if (isEdit) payload.id = editId;

                    const errBox = document.getElementById('spExamErr');
                    errBox.classList.add('hidden');
                    try {
                        const result = isEdit
                            ? await window.flexamApi.special_exams.update(payload)
                            : await window.flexamApi.special_exams.create(payload);
                        if (!result.success) { errBox.classList.remove('hidden'); errBox.textContent = result.message||'Failed to save.'; return; }
                        document.getElementById('specialExamModal').remove();
                        await refreshAllData();
                        if (!isEdit) {
                            // Show evidence reminder popup
                            showSpecialExamEvidencePopup(fd.get('first_name'), fd.get('last_name'));
                        } else {
                            showToast('Special exam registration updated!');
                        }
                    } catch(err) {
                        errBox.classList.remove('hidden');
                        errBox.textContent = 'Network error. Please try again.';
                    }
                };
            };

            function spExamTypeChanged(sel) {
                const wrap = document.getElementById('spExamTypeOtherWrap');
                if (wrap) wrap.style.display = sel.value === 'Others' ? 'block' : 'none';
            }

            function spExamReasonChanged(sel) {
                const wrap = document.getElementById('spExamReasonOtherWrap');
                if (wrap) wrap.style.display = sel.value === 'Others' ? 'block' : 'none';
            }

            function spExamCollegeChanged(sel, preselectedProgram) {
                const progSel = document.getElementById('spExamProgram');
                if (!progSel) return;
                const opt = sel.options[sel.selectedIndex];
                let programs = [];
                try { programs = JSON.parse(decodeURIComponent(opt.dataset.programs||'[]')); } catch(e) {}
                progSel.innerHTML = programs.length === 0
                    ? '<option value="">No programs found</option>'
                    : '<option value="">Select Program</option>' + programs.map(p => `<option value="${esc(p)}" ${preselectedProgram===p?'selected':''}>${esc(p)}</option>`).join('');
                // Refresh courses list if num already set
                const numSel = document.getElementById('spExamNumExams');
                if (numSel && numSel.value) spExamNumChanged(numSel);
            }

            async function spExamNumChanged(sel, prefill) {
                const n = parseInt(sel.value) || 0;
                const wrap  = document.getElementById('spExamCoursesWrap');
                const tbody = document.getElementById('spExamCoursesBody');
                if (!wrap || !tbody) return;
                wrap.style.display = n > 0 ? 'block' : 'none';
                if (!n) return;

                // Read all filter inputs
                const sy      = (document.querySelector('[name="school_year"]')?.value || '').trim();
                const sem     = (document.querySelector('[name="semester"]')?.value    || '').trim();
                const college = (document.getElementById('spExamCollege')?.value       || '').trim();
                const program = (document.getElementById('spExamProgram')?.value       || '').trim();
                const campus  = (currentUser.campus || '').trim();

                // Show guard messages inside the table body if prerequisites are missing
                if (!sy || !sem) {
                    tbody.innerHTML = `<tr><td colspan="2" class="px-3 py-3 text-xs text-amber-500 text-center">⚠ Please enter a <strong>School Year</strong> and select a <strong>Semester</strong> before choosing courses.</td></tr>`;
                    return;
                }
                if (!college) {
                    tbody.innerHTML = `<tr><td colspan="2" class="px-3 py-3 text-xs text-amber-500 text-center">⚠ Please select a <strong>College</strong> first.</td></tr>`;
                    return;
                }

                // Loading state
                tbody.innerHTML = `<tr><td colspan="2" class="px-3 py-3 text-xs text-slate-400 text-center animate-pulse">Loading courses for ${esc(sy)} ${esc(sem)}…</td></tr>`;

                // Fetch filtered courses from the API
                const params = new URLSearchParams({ action: 'filter' });
                params.set('school_year', sy);
                params.set('semester',    sem);
                if (college) params.set('college', college);
                if (program) params.set('program', program);
                if (campus)  params.set('campus',  campus);

                let uniqueCourses = [];
                try {
                    const res  = await fetch(`../api/courses.php?${params.toString()}`);
                    const json = await res.json();
                    uniqueCourses = json.success ? (json.data || []) : [];
                } catch (e) {
                    console.error('Course filter error:', e);
                }

                const courseOpts = uniqueCourses
                    .sort((a,b) => (a.course_code||'').localeCompare(b.course_code||''))
                    .map(c => `<option value="${dbId(c.id)}">${esc(c.course_code)} — ${esc(c.course_name||'')}</option>`)
                    .join('');

                tbody.innerHTML = '';

                if (!uniqueCourses.length) {
                    tbody.innerHTML = `<tr><td colspan="2" class="px-3 py-3 text-xs text-slate-400 text-center">No courses found for <strong>${esc(sy)} ${esc(sem)}</strong>${college?' · '+esc(college):''}. Ensure courses are tagged with this School Year and Semester.</td></tr>`;
                    return;
                }

                for (let i = 0; i < n; i++) {
                    const pf = prefill && prefill[i] ? prefill[i] : {};
                    const row = document.createElement('tr');
                    row.className = 'border-t border-purple-50';
                    row.innerHTML = `
                        <td class="px-3 py-2 text-xs text-slate-400 font-bold">${i+1}</td>
                        <td class="px-3 py-2">
                            <select class="sp-course-sel w-full px-2 py-1.5 border border-slate-200 rounded-md text-xs bg-white focus:ring-1 focus:ring-purple-300 outline-none">
                                <option value="">Select course</option>
                                ${courseOpts}
                            </select>
                        </td>`;
                    tbody.appendChild(row);
                    // Pre-fill if editing
                    if (pf.course_id) {
                        const cSel = row.querySelector('.sp-course-sel');
                        if (cSel) cSel.value = pf.course_id;
                    }
                }
            }

            function showSpecialExamEvidencePopup(firstName, lastName) {
                const pop = document.createElement('div');
                pop.id = 'spExamEvidencePopup';
                pop.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
                pop.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="bg-amber-500 px-6 py-5 flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/25 rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg">Registration Successful!</h3>
                            <p class="text-amber-100 text-xs mt-0.5">Important reminder for the student</p>
                        </div>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <p class="text-slate-700 text-sm font-medium">
                            <span class="font-bold text-slate-900">${esc(firstName)} ${esc(lastName)}</span> has been successfully registered for a special examination.
                        </p>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-2">
                            <p class="text-xs font-bold text-amber-700 uppercase tracking-wide">⚠️ Student Must Present Valid Evidence to Proctor</p>
                            <p class="text-xs text-slate-600">The student is required to present <strong>at least one</strong> of the following during the exam:</p>
                            <ul class="text-xs text-slate-600 space-y-1 mt-2">
                                <li class="flex items-start gap-2"><span class="text-amber-500 font-bold mt-0.5">•</span>Official Receipt (OR) of payment</li>
                                <li class="flex items-start gap-2"><span class="text-amber-500 font-bold mt-0.5">•</span>Medical Certificate (for health-related absences)</li>
                                <li class="flex items-start gap-2"><span class="text-amber-500 font-bold mt-0.5">•</span>Screenshots or documents as proof of valid reason</li>
                                <li class="flex items-start gap-2"><span class="text-amber-500 font-bold mt-0.5">•</span>Any other official supporting document</li>
                            </ul>
                            <p class="text-[11px] text-amber-600 font-medium mt-2">Failure to present evidence may result in disqualification from taking the exam.</p>
                        </div>
                    </div>
                    <div class="px-6 pb-5 flex gap-3">
                        <button onclick="document.getElementById('spExamEvidencePopup').remove()" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-lg transition text-sm">Understood</button>
                    </div>
                </div>`;
                document.body.appendChild(pop);
                showToast('✅ Special exam registration saved!', 'success');
            }

            window.deleteSpecialExam = async function(id) {
                if (!confirm('Delete this special exam registration? This cannot be undone.')) return;
                const result = await window.flexamApi.special_exams.delete(id);
                if (result.success) {
                    showToast('Special exam registration deleted.', 'success');
                    await refreshAllData();
                } else {
                    showToast(result.message || 'Failed to delete.', 'error');
                }
            };

            window.openViewSpecialExam = function(id) {
                const e = (window._specialExams||[]).find(x=>String(x.id)===String(id));
                if (!e) return;
                const esc2 = s => { const d=document.createElement('div'); d.textContent=String(s??''); return d.innerHTML; };
                const courses = (() => { try { return JSON.parse(e.exam_courses||'[]'); } catch(_){return[];} })();
                const existing = document.getElementById('spViewModal');
                if (existing) existing.remove();
                const modal = document.createElement('div');
                modal.id = 'spViewModal';
                modal.className = 'fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm';
                modal.innerHTML = `<div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-purple-700 rounded-t-2xl flex items-center justify-between sticky top-0 z-10">
                        <div>
                            <h2 class="text-base font-bold text-white">Special Exam Registration</h2>
                            <p class="text-purple-200 text-xs mt-0.5">Student details &amp; enrolled exams</p>
                        </div>
                        <button type="button" onclick="document.getElementById('spViewModal').remove()" class="text-white/70 hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-5 space-y-4">
                        <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold text-sm shrink-0">${esc2((e.first_name||'?')[0].toUpperCase())}</div>
                                <div>
                                    <p class="font-bold text-slate-800 text-sm">${esc2((e.last_name||'')+(e.last_name&&e.first_name?', ':'')+( e.first_name||''))}</p>
                                    <p class="text-xs text-slate-500 font-mono">${esc2(e.student_no||'—')}</p>
                                </div>
                                <span class="ml-auto inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">${esc2(e.exam_type||'—')}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="bg-white rounded-lg px-3 py-2 border border-purple-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">College / Program</p>
                                    <p class="font-semibold text-slate-700">${esc2(e.college||'—')}${e.program?`<span class="text-slate-400"> / ${esc2(e.program)}</span>`:''}</p>
                                </div>
                                <div class="bg-white rounded-lg px-3 py-2 border border-purple-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Campus</p>
                                    <p class="font-semibold text-slate-700">${esc2(e.campus||'—')}</p>
                                </div>
                                <div class="bg-white rounded-lg px-3 py-2 border border-purple-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">School Year</p>
                                    <p class="font-semibold text-slate-700">${esc2(e.school_year||'—')}</p>
                                </div>
                                <div class="bg-white rounded-lg px-3 py-2 border border-purple-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Semester</p>
                                    <p class="font-semibold text-slate-700">${esc2(e.semester||'—')}</p>
                                </div>
                                <div class="bg-white rounded-lg px-3 py-2 border border-purple-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Receipt No.</p>
                                    <p class="font-semibold text-slate-700 font-mono">${esc2(e.receipt_no||'—')}</p>
                                </div>
                                <div class="bg-white rounded-lg px-3 py-2 border border-purple-100">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Reason</p>
                                    <p class="font-semibold text-slate-700">${esc2(e.reason||'—')}</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Courses for Special Exam</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">${courses.length} subject${courses.length!==1?'s':''}</span>
                            </div>
                            ${courses.length ? `<div class="space-y-2">${courses.map((c,i)=>`
                                <div class="flex items-center gap-3 bg-white border border-purple-100 rounded-xl px-3 py-2.5 shadow-sm">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-600 text-white text-[10px] font-bold shrink-0">${i+1}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-slate-800 truncate">${esc2(c.course_label||c.course_id||'—')}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-600 border border-purple-200 shrink-0">${esc2(c.exam_type||'')}</span>
                                </div>`).join('')}</div>` : `<div class="py-6 text-center text-slate-400 text-xs bg-slate-50 rounded-xl border border-slate-100">No courses recorded.</div>`}
                        </div>
                        <div class="flex gap-3 pt-1">
                            <button type="button" onclick="document.getElementById('spViewModal').remove()" class="flex-1 px-4 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-50 transition">Close</button>
                            <button type="button" onclick="document.getElementById('spViewModal').remove();openSpecialExamModal(${e.id})" class="flex-1 px-4 py-2.5 bg-purple-600 text-white rounded-xl text-sm font-semibold hover:bg-purple-700 shadow-sm transition">Edit Registration</button>
                        </div>
                    </div>
                </div>`;
                document.body.appendChild(modal);
                modal.addEventListener('click', ev => { if (ev.target === modal) modal.remove(); });
            };

            window.filterSpecialExams = function() {
                const q = (document.getElementById('spExamSearch')||{}).value?.toLowerCase()||'';
                const sy = (document.getElementById('spExamSYFilter')||{}).value||'';
                const sem = (document.getElementById('spExamSemFilter')||{}).value||'';
                const type = (document.getElementById('spExamTypeFilter')||{}).value?.toLowerCase()||'';
                document.querySelectorAll('.sp-exam-row').forEach(row => {
                    const matchQ  = !q   || (row.dataset.search||'').includes(q);
                    const matchSY = !sy  || row.dataset.sy === sy;
                    const matchSem= !sem || row.dataset.sem === sem;
                    const matchT  = !type|| (row.dataset.type||'').includes(type);
                    row.style.display = (matchQ && matchSY && matchSem && matchT) ? '' : 'none';
                });
            };

            window.spExamClearFilters = function() {
                ['spExamSearch','spExamSYFilter','spExamSemFilter','spExamTypeFilter'].forEach(id => {
                    const el = document.getElementById(id); if (el) el.value='';
                });
                filterSpecialExams();
            };

            window.exportSpecialExamPDF = function() {
                const rows = window._specialExams || [];
                if (!rows.length) { showToast('No special exam registrations to export.', 'error'); return; }
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
                const pageW = doc.internal.pageSize.getWidth();
                doc.setFillColor(109, 40, 217);
                doc.rect(0, 0, pageW, 18, 'F');
                doc.setTextColor(255,255,255);
                doc.setFontSize(13); doc.setFont(undefined,'bold');
                doc.text('Special Exam Registrations', 14, 12);
                doc.setFontSize(8); doc.setFont(undefined,'normal');
                doc.text('Exported: ' + new Date().toLocaleDateString(), pageW - 14, 12, { align: 'right' });
                doc.setTextColor(0,0,0);
                const head = [['Student No.','Last Name','First Name','College','Program','S.Y.','Semester','Exam Type','Receipt No.','No. of Exams','Reason']];
                const body = rows.map(e => [
                    e.student_no||'', e.last_name||'', e.first_name||'',
                    e.college||'', e.program||'', e.school_year||'',
                    e.semester||'', e.exam_type||'', e.receipt_no||'',
                    e.num_exams||'0', e.reason||''
                ]);
                doc.autoTable({ head, body, startY: 22, styles: { fontSize: 7 }, headStyles: { fillColor: [109,40,217] }, alternateRowStyles: { fillColor: [245,243,255] }, margin: { left: 14, right: 14 } });
                const dateStr = new Date().toISOString().split('T')[0];
                doc.save('special-exam-registrations-' + dateStr + '.pdf');
            };



            function editSchedule(scheduleId) {
            const sched = findById('schedule', scheduleId);
            if (!sched) { showToast('Schedule not found', 'error'); return; }
            if (document.getElementById('editScheduleModal')) return;

            const currentRoomId = dbId(String(sched.room_id || sched.room || ''));

            // Build room options: available rooms + the currently assigned room (even if now blocked, so admin sees it)
            const roomOptions = allData
                .filter(d => d.type === 'room' && (!d.locked || dbId(String(d.id)) === currentRoomId))
                .map(r => {
                    const rid       = dbId(String(r.id));
                    const isCurrent = rid === currentRoomId;
                    const isBlocked = !!r.locked;
                    return `<option value="${rid}" ${isCurrent ? 'selected' : ''} ${isBlocked ? 'disabled' : ''}>
                        ${r.name} - ${r.building}${r.campus ? ' (' + r.campus + ')' : ''} (Cap: ${r.capacity})${isBlocked ? ' 🚫 Blocked — choose another' : ''}
                    </option>`;
                }).join('');

            const modal = document.createElement('div');
            modal.id = 'editScheduleModal';
            modal.className = 'fixed inset-0 bg-black/50 z-50 overflow-y-auto';
            modal.innerHTML = `
            <div class="min-h-screen px-4 py-8 flex items-start justify-center">
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
                <div class="flex justify-between items-center px-8 py-6 border-b border-slate-200">
                    <h2 class="text-2xl font-bold">Edit Exam Schedule</h2>
                    <button type="button" onclick="closeEditScheduleModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="editScheduleForm" class="px-8 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Course</label>
                            <select id="editCourseSelect" name="course" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                <option value="" disabled>Select Course</option>
                                ${(() => {
                                    const seen = {};
                                    const schedCid = dbId(String(sched.course_id || ''));
                                    allData.filter(d => d.type === 'course').forEach(c => {
                                        const key = (c.course_code || '').toLowerCase();
                                        if (!seen[key]) {
                                            seen[key] = { ...c, _allIds: [dbId(String(c.id))] };
                                        } else {
                                            seen[key]._allIds.push(dbId(String(c.id)));
                                            // Keep the best course_name (not equal to code)
                                            const cur = (seen[key].course_name || '').trim();
                                            const nw  = (c.course_name || '').trim();
                                            if (nw && nw.toLowerCase() !== key && cur.toLowerCase() === key) {
                                                seen[key].course_name = nw;
                                                seen[key].college = c.college || seen[key].college;
                                                seen[key].id = c.id;
                                            }
                                            // If this record IS the currently-selected one, use its id as representative
                                            if (schedCid && dbId(String(c.id)) === schedCid) {
                                                seen[key].id = c.id;
                                                seen[key].college = c.college || seen[key].college;
                                            }
                                        }
                                    });
                                    return Object.values(seen)
                                        .sort((a, b) => (a.course_code||'').localeCompare(b.course_code||''))
                                        .map(c => {
                                            const isSelected = schedCid && c._allIds.includes(schedCid);
                                            // Build unique colleges list for this deduped course
                                            const allColleges = [...new Set(
                                                (c._allIds || [dbId(String(c.id))]).map(cid => {
                                                    const rec = allData.find(d => d.type === 'course' && dbId(String(d.id)) === cid);
                                                    return rec ? (rec.college || '').trim() : '';
                                                }).filter(Boolean)
                                            )];
                                            // Build unique programs list for this deduped course
                                            const allPrograms = [...new Set(
                                                (c._allIds || [dbId(String(c.id))]).map(cid => {
                                                    const rec = allData.find(d => d.type === 'course' && dbId(String(d.id)) === cid);
                                                    return rec ? (rec.program || '').trim() : '';
                                                }).filter(Boolean)
                                            )];
                                            return `<option value="${dbId(c.id)}" data-college="${c.college||''}" data-colleges="${encodeURIComponent(JSON.stringify(allColleges))}" data-programs="${encodeURIComponent(JSON.stringify(allPrograms))}" ${isSelected?'selected':''}>${c.course_code} - ${c.course_name}</option>`;
                                        })
                                        .join('');
                                })()}
                            </select>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">College</label>
                            <div id="editCollegeFieldWrap">
                                <input type="text" id="editCollegeField" name="college" value="${esc(sched.college||'')}" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500" readonly>
                            </div>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Program</label>
                            <div id="editProgramFieldWrap">
                                <input type="text" id="editProgramField" name="program" value="${esc(sched.program||sched.college_program||(sched.course_id ? (findById('course',sched.course_id)||{}).program||'' : ''))}" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500" placeholder="Auto-filled from course" readonly>
                            </div>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Exam Type</label>
                            <select name="examType" id="editExamTypeSelect" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                ${['Prelim','Midterm','Final','Summer'].map(t=>`<option ${(sched.exam_type||sched.examType)===t?'selected':''}>${t}</option>`).join('')}
                                <option value="Others" ${!['Prelim','Midterm','Final','Summer'].includes(sched.exam_type||sched.examType) && (sched.exam_type||sched.examType) ? 'selected' : ''}>Others</option>
                            </select>
                            <div id="editExamTypeOtherWrap" style="${!['Prelim','Midterm','Final','Summer'].includes(sched.exam_type||sched.examType) && (sched.exam_type||sched.examType) ? 'display:block' : 'display:none'};margin-top:8px;">
                                <label style="font-size:0.7rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px;">Specify Exam Type <span style="color:#ef4444">*</span></label>
                                <input type="text" id="editExamTypeOther" name="examTypeOther" placeholder="e.g. Qualifying Exam, Thesis Defense..." value="${!['Prelim','Midterm','Final','Summer'].includes(sched.exam_type||sched.examType) && (sched.exam_type||sched.examType) ? esc(sched.exam_type||sched.examType) : ''}" style="width:100%;padding:0.75rem 1rem;border:1px solid #10b981;border-radius:0.5rem;font-size:0.875rem;color:#1e293b;background:white;outline:none;box-sizing:border-box;">
                            </div>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Semester</label>
                            <select name="semester" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                <option value="" disabled ${!sched.semester?'selected':''}>Select Semester</option>
                                ${['1st Semester','2nd Semester','Summer'].map(s=>`<option ${sched.semester===s?'selected':''}>${s}</option>`).join('')}
                            </select>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Year Level</label>
                            <select name="yearLevel" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                ${YEAR_LEVELS.map(y=>`<option ${sched.year_level===y?'selected':''}>${y}</option>`).join('')}
                            </select>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Section</label>
                            <input type="text" name="section" value="${esc(sched.section||'')}" placeholder="e.g. 1-Y1-1" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm">
                            <p class="text-[10px] text-slate-400">Enter section number only (e.g. <strong>1-Y1-1</strong>). Program is stored separately.</p>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Date</label>
                            <input type="date" id="editDateField" name="date" value="${sched.exam_date||sched.date||''}" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none text-sm" min="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        <div class="space-y-2 text-left">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Duration</label>
                            <select name="duration" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                                ${['1 Hour','1.5 Hours','2 Hours','2.5 Hours','3 Hours'].map(d=>`<option ${sched.duration===d?'selected':''}>${d}</option>`).join('')}
                            </select>
                        </div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Campus *</label>
                            ${campusSelectHtml('campus', sched.campus || '')}
                        </div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Proctor</label>
                            <select name="proctor" multiple class="w-full min-h-[100px] px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm">
                                <option value="">No Proctor</option>
                                ${allData.filter(d=>d.type==='proctor').map(p=>`<option value="${dbId(p.id)}" ${dbId(String(sched.proctor_id||''))===dbId(String(p.id))?'selected':''}>${p.name}${p.campus?' ('+p.campus+')':''}</option>`).join('')}
                            </select>
                        </div>
                        <div class="space-y-2 text-left md:col-span-2">
    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Room</label>
    <label class="flex items-center gap-2 mb-2 cursor-pointer select-none">
        <input type="checkbox" id="editIsOnlineCheck" name="is_online" value="1"
            ${sched.is_online ? 'checked' : ''}
            class="w-4 h-4 rounded text-emerald-600 accent-emerald-600"
            onchange="
                const checked = this.checked;
                const wrap = document.getElementById('editRoomSelectWrap');
                if (wrap) wrap.style.display = checked ? 'none' : 'block';
                const sel = document.getElementById('editRoomSelect');
                if (sel) sel.required = !checked;
            ">
        <span class="text-sm font-medium text-slate-600">🌐 Online Exam <span class="text-xs text-slate-400 font-normal">(no room required)</span></span>
    </label>
    <div id="editRoomSelectWrap" ${sched.is_online ? 'style="display:none"' : ''}>
        ${(() => {
            const currentRoom = allData.find(d => d.type === 'room' && dbId(String(d.id)) === currentRoomId);
            const isCurrentBlocked = currentRoom && !!currentRoom.locked;
            return isCurrentBlocked
                ? `<div class="mb-2 flex items-start gap-2 p-3 rounded-lg bg-red-50 border border-red-200">
                       <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                       <p class="text-xs text-red-700">The previously assigned room <strong>${esc(currentRoom.name)}</strong> is now blocked. Please select a different available room before saving.</p>
                   </div>`
                : '';
        })()}
        <select id="editRoomSelect" name="room" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" ${sched.is_online ? '' : 'required'}>
            <option value="" disabled>Select Room</option>
            ${roomOptions}
        </select>
    </div>
</div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Time Slot</label>
                            <div id="editTimeSlotContainer" class="border border-slate-200 rounded-lg p-4 bg-slate-50 text-center text-sm text-slate-400">Loading slots…</div>
                        </div>
                        <div class="space-y-2 text-left md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Status</label>
                            <select name="status" class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm">
                                ${['Pending','Approved','Rejected'].map(s=>`<option ${(sched.status||'Pending')===s?'selected':''}>${s}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div id="editScheduleErr" class="hidden mt-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="closeEditScheduleModal()" class="px-8 py-3.5 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg transition border border-slate-200">Cancel</button>
                        <button type="submit" id="editScheduleBtn" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3.5 rounded-lg transition">Update Schedule</button>
                    </div>
                </form>
            </div>
            </div>`;
            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);

            const currentTimeSlot = sched.time_slot || sched.timeSlot || '';

            function generateEditTimeSlots(durationStr) {
                const dur = parseFloat(durationStr) || 1;
                const durMins = Math.round(dur * 60);
                const startTimes = [420,480,540,600,660,780,840,900,960,1020,1080,1140,1200];
                const LUNCH_START=720, LUNCH_END=780, DAY_END=1260;
                function toAMPM(m) {
                    const h=Math.floor(m/60), min=m%60, ap=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
                    return String(h12).padStart(2,'0')+':'+String(min).padStart(2,'0')+' '+ap;
                }
                const slots=[];
                for (const start of startTimes) {
                    const end=start+durMins;
                    if (end>DAY_END) continue;
                    slots.push(toAMPM(start)+' - '+toAMPM(end));
                }
                return slots;
            }

            function updateEditTimeSlots() {
                const date      = document.getElementById('editDateField').value;
                const room      = document.getElementById('editRoomSelect').value;
                const duration  = document.querySelector('#editScheduleForm select[name="duration"]')?.value || '1 Hour';
                const container = document.getElementById('editTimeSlotContainer');
                if (!date || !room) { container.innerHTML = '<div class="text-center text-sm text-slate-400">Select date and room first</div>'; return; }

                // Verify the chosen room is not blocked
                const chosenRoom = allData.find(d => d.type === 'room' && dbId(String(d.id)) === room);
                if (chosenRoom && chosenRoom.locked) {
                    container.innerHTML = `<div class="text-center text-sm text-red-500 font-medium p-3 bg-red-50 rounded-lg">
                        🚫 This room is blocked and cannot be scheduled. Please select a different room.
                    </div>`;
                    return;
                }

                const slots = generateEditTimeSlots(duration);
                if (slots.length===0) { container.innerHTML='<div class="text-center text-sm text-orange-500">No slots fit this duration in the day.</div>'; return; }
                const occupied = allData.filter(d =>
                    d.type === 'schedule' &&
                    (d.exam_date || d.date) === date &&
                    dbId(String(d.room_id || d.room || '')) === room &&
                    dbId(String(d.id)) !== scheduleId
                ).map(s => s.time_slot || s.timeSlot);

                container.innerHTML = `<div class="grid grid-cols-2 gap-2">${slots.map(slot => {
                    const occ     = occupied.includes(slot);
                    const current = slot === currentTimeSlot;
                    return `<label class="flex items-center gap-2 p-3 border ${occ&&!current?'border-red-200 bg-red-50 cursor-not-allowed':current?'border-emerald-400 bg-emerald-50':'border-slate-200 bg-white cursor-pointer hover:border-emerald-500'} rounded-lg transition">
                        <input type="radio" name="timeSlot" value="${slot}" ${occ&&!current?'disabled':''} ${current?'checked':''} class="text-emerald-600" required>
                        <span class="text-sm ${occ&&!current?'text-red-400 line-through':'text-slate-700'}">${slot}</span>
                        ${occ&&!current?'<span class="ml-auto text-xs text-red-500 font-semibold">Occupied</span>':''}
                        ${current?'<span class="ml-auto text-xs text-emerald-600 font-semibold">Current</span>':''}
                    </label>`;
                }).join('')}</div>`;
            }

            // Helper: given a college value (code or name) and the full programs list for the
            // selected course, return only programs that belong to that college in allData.
            // Helper: given a college + course code, return programs from allData course records
            // that match both that college and that course code.
            function getEditProgramsForCollege(collegeValue, allPrograms, courseCode) {
                if (!collegeValue) return allPrograms;
                const cv = collegeValue.trim().toLowerCase();
                const cc = (courseCode || '').trim().toLowerCase();

                // Primary: pull programs directly from allData course records
                if (cc) {
                    const matched = [...new Set(
                        allData
                            .filter(d =>
                                d.type === 'course' &&
                                (d.course_code || '').trim().toLowerCase() === cc &&
                                (d.college || '').trim().toLowerCase() === cv
                            )
                            .map(d => (d.program || '').trim())
                            .filter(Boolean)
                    )];
                    if (matched.length > 0) return matched;
                }

                // Fallback: intersect allPrograms with the college record's own program list
                const colRec = allData.find(d =>
                    d.type === 'college' && (
                        (d.code || '').toLowerCase() === cv ||
                        (d.name || '').toLowerCase() === cv
                    )
                );
                if (!colRec || !Array.isArray(colRec.programs) || colRec.programs.length === 0) return allPrograms;
                const colProgs = colRec.programs.map(p =>
                    (p.includes('=') ? p.split('=')[0].trim() : p.trim()).toLowerCase()
                );
                const filtered = allPrograms.filter(p => colProgs.includes(p.toLowerCase()));
                return filtered.length > 0 ? filtered : allPrograms;
            }

            // Helper: render program field into #editProgramFieldWrap filtered by selected college + course code
            function renderEditProgramField(allPrograms, curProg, courseCode) {
                const progWrap        = document.getElementById('editProgramFieldWrap');
                const collegeEl       = document.getElementById('editCollegeField');
                const selectedCollege = collegeEl ? (collegeEl.value || '') : '';
                const filtered        = getEditProgramsForCollege(selectedCollege, allPrograms, courseCode);

                if (filtered.length > 1) {
                    progWrap.innerHTML = `<select id="editProgramField" name="program"
                        class="w-full px-4 py-3 border border-emerald-400 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                        ${filtered.map(p => `<option value="${p}" ${p === curProg ? 'selected' : ''}>${p}</option>`).join('')}
                    </select>`;
                } else {
                    progWrap.innerHTML = `<input type="text" id="editProgramField" name="program"
                        value="${filtered[0] || ''}"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500"
                        placeholder="Auto-filled from course" readonly>`;
                }
            }

            document.getElementById('editCourseSelect').addEventListener('change', function() {
                const opt       = this.options[this.selectedIndex];
                const rawCols   = opt.getAttribute('data-colleges') || '';
                const rawProgs  = opt.getAttribute('data-programs') || '';
                const colleges  = rawCols  ? JSON.parse(decodeURIComponent(rawCols))  : [];
                const programs  = rawProgs ? JSON.parse(decodeURIComponent(rawProgs)) : [];
                const colWrap   = document.getElementById('editCollegeFieldWrap');
                const curCol    = "${esc(sched.college||'')}";
                const curProg   = "${esc(sched.program||sched.college_program||'')}";
                const selectedCourseCode = opt.text.split(' - ')[0].trim();

                // College field
                if (colleges.length > 1) {
                    colWrap.innerHTML = `<select id="editCollegeField" name="college"
                        class="w-full px-4 py-3 border border-emerald-400 rounded-lg bg-white focus:ring-2 focus:ring-emerald-500 outline-none text-sm" required>
                        ${colleges.map(col => `<option value="${col}" ${col === curCol ? 'selected' : ''}>${col}</option>`).join('')}
                    </select>`;
                    // Wire college change → re-filter programs by college + course code
                    document.getElementById('editCollegeField').addEventListener('change', function() {
                        renderEditProgramField(programs, curProg, selectedCourseCode);
                    });
                } else {
                    colWrap.innerHTML = `<input type="text" id="editCollegeField" name="college"
                        value="${colleges[0] || ''}"
                        class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-slate-50 outline-none text-sm text-slate-500"
                        readonly>`;
                }

                // Program field — filtered by the initially-selected college + course code
                renderEditProgramField(programs, curProg, selectedCourseCode);
            });
            document.getElementById('editDateField').addEventListener('change', updateEditTimeSlots);
            document.getElementById('editRoomSelect').addEventListener('change', updateEditTimeSlots);
            const editDurSel = document.querySelector('#editScheduleForm select[name="duration"]');
            if (editDurSel) editDurSel.addEventListener('change', updateEditTimeSlots);
            setTimeout(updateEditTimeSlots, 50);

            // On modal open: init program field so multi-program courses show a dropdown
            (function initEditProgramField() {
                const sel = document.getElementById('editCourseSelect');
                if (!sel || sel.selectedIndex < 0) return;
                const opt      = sel.options[sel.selectedIndex];
                if (!opt) return;
                const rawProgs = opt.getAttribute('data-programs') || '';
                const programs = rawProgs ? JSON.parse(decodeURIComponent(rawProgs)) : [];
                const courseCode = opt.text.split(' - ')[0].trim();
                const curProg = document.getElementById('editProgramField') ? document.getElementById('editProgramField').value : '';
                renderEditProgramField(programs, curProg, courseCode);
            })();

            // ── Wire Others exam type textbox (edit modal) ───────────────────
            (function() {
                const sel   = document.getElementById('editExamTypeSelect');
                const wrap  = document.getElementById('editExamTypeOtherWrap');
                const input = document.getElementById('editExamTypeOther');
                if (sel && wrap && input) {
                    sel.addEventListener('change', function() {
                        if (this.value === 'Others') {
                            wrap.style.display = 'block';
                            input.required = true;
                            input.focus();
                        } else {
                            wrap.style.display = 'none';
                            input.required = false;
                            input.value = '';
                        }
                    });
                }
            })();

            document.getElementById('editScheduleForm').onsubmit = async (e) => {
                e.preventDefault();
                const fd  = new FormData(e.target);
                const btn = document.getElementById('editScheduleBtn');
                const errEl = document.getElementById('editScheduleErr');
                errEl.classList.add('hidden');

                const newCampus   = fd.get('campus')    || '';
                const newCourse   = fd.get('course')    || '';
                const _rawEditExamType = fd.get('examType') || '';
                const newType     = _rawEditExamType === 'Others' ? (fd.get('examTypeOther') || '').trim() : _rawEditExamType;
                const newDate     = fd.get('date')      || '';
                const newTimeSlot = fd.get('timeSlot')  || '';
                const newSection  = (fd.get('section')  || '').trim().toLowerCase();
                const newCollege  = (fd.get('college')  || '').trim().toLowerCase();

                const duplicate = allData.find(d =>
                    d.type === 'schedule' &&
                    (d.status || '') !== 'Rejected' &&
                    dbId(String(d.id)) !== scheduleId &&          // exclude self
                    (d.campus || '') === newCampus &&
                    dbId(String(d.course_id || '')) === newCourse &&
                    (d.exam_type || '') === newType &&
                    (d.exam_date || d.date || '') === newDate &&
                    (d.time_slot || '') === newTimeSlot
                );

                if (duplicate) {
                    errEl.textContent = `❌ A schedule for this course already exists in the ${newCampus} campus on the same date and time slot.`;
                    errEl.classList.remove('hidden');
                    btn.disabled = false; btn.textContent = 'Update Schedule';
                    return;
                }

                // ── Duplicate section check: same course + college + section + exam_type + date ────────
                if (newSection && newCollege) {
                    const sectionDup = allData.find(d =>
                        d.type === 'schedule' &&
                        (d.status || '') !== 'Rejected' &&
                        dbId(String(d.id)) !== scheduleId &&      // exclude self
                        (d.exam_type || '') === newType &&
                        (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === newSection &&
                        (d.college || '').trim().toLowerCase() === newCollege &&
                        String(d.course_id || d.course || '') === String(fd.get('course')) &&
                        (d.exam_date || d.date || '').trim() === fd.get('date')
                    );
                    if (sectionDup) {
                        const _dd = (sectionDup.exam_date||'').trim(), _dr = (sectionDup.room_name||'').trim();
                        errEl.textContent = `❌ Section "${fd.get('section')}" under ${fd.get('college')} already has a ${newType} exam for this course${_dd?' on '+_dd:''}${_dr?' in '+_dr:''}.`;
                        errEl.classList.remove('hidden');
                        btn.disabled = false; btn.textContent = 'Update Schedule';
                        return;
                    }
                }

                // ── Section time overlap: same section in two rooms at the same time ──────────
                if (newSection && newDate && newTimeSlot) {
                    const sectionTimeConflict = allData.find(d =>
                        d.type === 'schedule' &&
                        (d.status || '') !== 'Rejected' &&
                        dbId(String(d.id)) !== scheduleId &&
                        (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === newSection &&
                        (d.college || '').trim().toLowerCase() === newCollege &&
                        (d.exam_date || d.date || '') === newDate &&
                        (d.time_slot || '') === newTimeSlot
                    );
                    if (sectionTimeConflict) {
                        errEl.textContent = `❌ Section "${fd.get('section')}" (${fd.get('college') || '—'}) already has an exam at ${newTimeSlot} on ${newDate} (${sectionTimeConflict.course_code || '—'}). Students cannot be in two places at once.`;
                        errEl.classList.remove('hidden');
                        btn.disabled = false; btn.textContent = 'Update Schedule';
                        return;
                    }
                }

                // ── Proctor conflict: same proctor assigned to two rooms at the same time ──────
                const editProctorId = fd.get('proctor') || '';
                if (editProctorId && newDate && newTimeSlot) {
                    const proctorConflict = allData.find(d =>
                        d.type === 'schedule' &&
                        (d.status || '') !== 'Rejected' &&
                        dbId(String(d.id)) !== scheduleId &&
                        String(d.proctor_id || '') === String(editProctorId) &&
                        (d.exam_date || d.date || '') === newDate &&
                        (d.time_slot || '') === newTimeSlot
                    );
                    if (proctorConflict) {
                        const pName = allData.find(d => d.type === 'proctor' && dbId(String(d.id)) === editProctorId)?.name || 'This proctor';
                        errEl.textContent = `❌ "${pName}" is already assigned to another exam at ${newTimeSlot} on ${newDate} (Room: ${proctorConflict.room_name || '—'}).`;
                        errEl.classList.remove('hidden');
                        btn.disabled = false; btn.textContent = 'Update Schedule';
                        return;
                    }
                }

                // Double-check: re-validate room is not blocked at submit time
                const chosenRoomId = fd.get('room');
                const chosenRoom   = allData.find(d => d.type === 'room' && dbId(String(d.id)) === chosenRoomId);
                if (chosenRoom && chosenRoom.locked) {
                    errEl.textContent = `Room "${chosenRoom.name}" is currently blocked and cannot be scheduled. Please select a different room.`;
                    errEl.classList.remove('hidden');
                    return;
                }

                btn.disabled = true; btn.textContent = 'Updating...';
                const result = await window.flexamApi.schedules.update({
                    id: scheduleId, course_id: fd.get('course'), college: fd.get('college'),
                    program: fd.get('program') || '',
                    exam_type: (fd.get('examType') === 'Others' ? (fd.get('examTypeOther') || '').trim() : fd.get('examType')), semester: fd.get('semester') || '',
                    year_level: fd.get('yearLevel'),
                    section:       fd.get('section') || '',
                    section_name:  fd.get('section') || '',
                    class_section: fd.get('section') || '',
                    exam_date: fd.get('date'), time_slot: fd.get('timeSlot'),
                    duration: fd.get('duration'),
                    room_id:  fd.get('is_online') ? null : fd.get('room'),
                    is_online: fd.get('is_online') ? 1 : 0,
                    proctorIds: fd.getAll('proctor').map(Number).filter(Boolean),
                    campus: fd.get('campus'), status: 'Pending'
                });
                btn.disabled = false; btn.textContent = 'Update Schedule';
                if (result.success) { closeEditScheduleModal(); showToast('Schedule updated!'); await refreshAllData(); }
                else { errEl.textContent = result.message || 'Error'; errEl.classList.remove('hidden'); }
            };
        }
        function closeEditScheduleModal() { const m = document.getElementById('editScheduleModal'); if (m) m.remove(); }
        window.exportSchedulePDF = async function() {
    // Load jsPDF and autoTable if not already loaded
    if (!window.jspdf) {
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            s.onload = resolve; s.onerror = reject;
            document.head.appendChild(s);
        });
    }
    if (!window.jspdf || !window.jspdf.jsPDF) {
        showToast('PDF library failed to load', 'error'); return;
    }
    if (typeof window.jspdf.jsPDF.API.autoTable === 'undefined') {
        await new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js';
            s.onload = resolve; s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    const { jsPDF } = window.jspdf;
    const schedules = allData.filter(d => d.type === 'schedule');

    // Read active filters from DOM — same as viewSchedFilter()
    const campus    = campusFilters['view-schedule'] || '';
    const search    = (document.getElementById('viewSchedSearch')?.value || '').toLowerCase();
    const college   = document.getElementById('viewSchedCollege')?.value || '';
    const semester  = (document.getElementById('viewSchedSemester')?.value || '').toLowerCase();
    const course    = (document.getElementById('viewSchedCourse')?.value || '').toLowerCase();
    const type      = document.getElementById('viewSchedType')?.value || '';
    const yearLevel = (document.getElementById('viewSchedYearLevel')?.value || '').toLowerCase();
    const status    = document.getElementById('viewSchedStatus')?.value || '';

    let filtered = campus ? schedules.filter(s => s.campus === campus) : schedules;
    if (college)   filtered = filtered.filter(s => (s.college || '') === college);
    if (semester)  filtered = filtered.filter(s => (s.semester || s.semester_name || '').toLowerCase() === semester);
    if (course)    filtered = filtered.filter(s => (s.course_code || '').toLowerCase() === course);
    if (type)      filtered = filtered.filter(s => (s.exam_type || '') === type);
    if (yearLevel) filtered = filtered.filter(s => (s.year_level || s.yearLevel || '').toLowerCase() === yearLevel);
    if (status)    filtered = filtered.filter(s => (s.status || 'Pending') === status);
    if (search)    filtered = filtered.filter(s =>
        (s.course_code || '').toLowerCase().includes(search) ||
        (s.course_name || '').toLowerCase().includes(search) ||
        (s.section || '').toLowerCase().includes(search) ||
        (s.room_name || s.room || '').toLowerCase().includes(search)
    );

    const now = new Date();
    const generated = now.toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' })
        + ', ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    // Header text
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.setTextColor(30, 41, 59);
    doc.text('FLEXAM - Exam Schedules' + (status ? ' (' + status + ')' : ''), 14, 18);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: ' + generated, 14, 25);
    doc.text('Our Lady of Fatima University' + (campus ? ' · ' + campus : ''), 14, 30);

    const activeFilters = [];
    if (search)    activeFilters.push('Search: "' + search + '"');
    if (college)   activeFilters.push('College: ' + college);
    if (semester)  activeFilters.push('Semester: ' + semester);
    if (course)    activeFilters.push('Course: ' + course.toUpperCase());
    if (type)      activeFilters.push('Type: ' + type);
    if (yearLevel) activeFilters.push('Year Level: ' + yearLevel);
    if (status)    activeFilters.push('Status: ' + status);
    if (activeFilters.length) {
        doc.setFontSize(8);
        doc.setTextColor(100, 116, 139);
        doc.text('Filters: ' + activeFilters.join('  |  '), 14, 36);
    }
    const headerEndY = activeFilters.length ? 42 : 35;

    // Guard: no schedules match the current filters
    if (filtered.length === 0) {
        showToast('No schedules available found.', 'error');
        return;
    }

    // Build table rows
    const tableRows = filtered.map(s => {
        const { courseName, roomName, examDate, semester } = resolveScheduleDisplay(s);
        const code = s.course_code || courseName || '—';
        const name = s.course_name || courseName || '—';
        const sem = s.semester || semester || '—';
        const yrLevel = s.year_level ? s.year_level + ' ' : '';
        const section = (yrLevel + (s.section || '')).trim() || '—';
        const examType = s.exam_type || '—';
        const rawDate = s.exam_date || s.date || '';
        let date = '—';
        if (rawDate && rawDate !== '0000-00-00') {
            try {
                const d = new Date(rawDate.substring(0,10) + 'T00:00:00');
                if (!isNaN(d.getTime())) date = d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: '2-digit', year: 'numeric' });
            } catch(e) { date = examDate || '—'; }
        } else { date = examDate || '—'; }
        const time = s.time_slot || '—';
        const room = s.is_online ? 'Online' : (roomName || '—');
        const college = s.college || '—';
        const program = s.program || s.college_program || '—';
        const camp = s.campus || '—';
        return [code, name, sem, section, college, program, examType, date, time, room, camp];
    });

    doc.autoTable({
        startY: headerEndY,
        head: [['Course Code', 'Course Name', 'Semester', 'Section', 'College', 'Program', 'Exam Type', 'Date', 'Time', 'Room', 'Campus']],
        body: tableRows.length > 0 ? tableRows : [['No schedules found', '', '', '', '', '', '', '', '', '', '']],
        styles: {
            fontSize: 7,
            cellPadding: 2.5,
            textColor: [55, 65, 81],
            lineColor: [226, 232, 240],
            lineWidth: 0.1,
        },
        headStyles: {
            fillColor: [4, 120, 87],
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 7,
        },
        alternateRowStyles: {
            fillColor: [240, 253, 244],
        },
        rowStyles: {
            fillColor: [255, 255, 255],
        },
        columnStyles: {
            0: { cellWidth: 20 },
            1: { cellWidth: 40 },
            2: { cellWidth: 18 },
            3: { cellWidth: 20 },
            4: { cellWidth: 26 },
            5: { cellWidth: 26 },
            6: { cellWidth: 18 },
            7: { cellWidth: 34 },
            8: { cellWidth: 24 },
            9: { cellWidth: 20 },
            10: { cellWidth: 18 },
        },
        margin: { left: 14, right: 14 },
        tableWidth: 'auto',
    });

    // Generate filename with date
    const dateStr = now.toISOString().split('T')[0];
    doc.save('exam-schedules-' + dateStr + '.pdf');
    showToast('PDF downloaded!');
};

        // ─────────────────────────────────────────────────────────────────────────
        // 21. CALENDAR
        // ─────────────────────────────────────────────────────────────────────────
        // ── Stable colour per schedule id so colours don't shuffle on re-render ──
        const _calColors = ['bg-blue-500','bg-purple-500','bg-pink-500','bg-orange-500','bg-teal-500','bg-indigo-500','bg-rose-500','bg-amber-500'];
        function _schedColor(id) {
            let hash = 0;
            String(id).split('').forEach(c => { hash = (hash * 31 + c.charCodeAt(0)) & 0xffffffff; });
            return _calColors[Math.abs(hash) % _calColors.length];
        }

        function renderCalendar() {
            const now      = new Date();
            const firstDay = new Date(calendarYear, calendarMonth, 1);
            const lastDay  = new Date(calendarYear, calendarMonth + 1, 0);
            const daysInMonth       = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();
            const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

            const approvedScheds = allData.filter(d => d.type === 'schedule' && d.status === 'Approved');
            const schedulesByDate = {};
            approvedScheds.forEach(s => {
                const dateStr = s.exam_date || s.date;
                if (!dateStr) return;
                const sd = new Date(dateStr.substring(0,10) + 'T00:00:00');
                if (sd.getMonth() === calendarMonth && sd.getFullYear() === calendarYear) {
                    const dk = sd.getDate();
                    if (!schedulesByDate[dk]) schedulesByDate[dk] = [];
                    schedulesByDate[dk].push(s);
                }
            });

            let html = `<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex flex-wrap justify-between items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900">${monthNames[calendarMonth]} ${calendarYear}</h2>
                    <div class="flex items-center gap-3">
                        <button onclick="navigateCalendar('prev')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                        <button onclick="navigateCalendar('today')" class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium hover:bg-emerald-100 transition">Today</button>
                        <button onclick="navigateCalendar('next')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                    </div>
                </div>
                <div class="calendar-grid">
                    ${dayNames.map(d=>`<div class="p-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider bg-slate-50 border-r border-b border-slate-200">${d}</div>`).join('')}`;

            for (let i = 0; i < startingDayOfWeek; i++) {
                html += `<div class="calendar-cell bg-slate-50"></div>`;
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = day === now.getDate() && calendarMonth === now.getMonth() && calendarYear === now.getFullYear();
                const ds      = schedulesByDate[day] || [];
                const hasEvts = ds.length > 0;
                const MAX_SHOW = 3;
                const visible  = ds.slice(0, MAX_SHOW);
                const overflow = ds.length - MAX_SHOW;

                // Build a padded ISO date string for the modal call
                const mm  = String(calendarMonth + 1).padStart(2,'0');
                const dd  = String(day).padStart(2,'0');
                const isoDate = `${calendarYear}-${mm}-${dd}`;

                html += `<div
                    class="calendar-cell ${isToday ? 'bg-emerald-50' : ''} ${hasEvts ? 'cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-colors' : 'cursor-default'}"
                    ${hasEvts ? `onclick="openDayModal('${isoDate}')"` : ''}>
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-sm font-semibold ${isToday ? 'text-emerald-600' : 'text-slate-700'}">${day}</span>
                        ${isToday ? '<span class="text-[10px] bg-emerald-600 text-white px-2 py-0.5 rounded-full font-bold">Today</span>' : ''}
                    </div>
                    ${visible.map(s => `<div class="event-bar ${_schedColor(s.id)}" title="${esc(s.course_code||s.course_name||s.course||'Exam')}${s.campus?' · '+s.campus:''}">${esc(s.course_code||s.course_name||s.course||'Exam')}</div>`).join('')}
                    ${overflow > 0 ? `<div class="mt-1 text-[10px] font-bold text-slate-400 pl-1">+${overflow} more</div>` : ''}
                </div>`;
            }

            html += `</div>
                <div class="p-6 border-t border-slate-200">
                    <h3 class="font-bold text-slate-800 mb-4">Exam Schedule Overview</h3>
                    ${approvedScheds.length === 0
                        ? '<div class="text-center py-8 text-slate-400 text-sm">No approved schedules yet.</div>'
                        : `<div class="space-y-2 max-h-64 overflow-y-auto">
                            ${approvedScheds.map(s => {
                                const { courseName, examDate, campus } = resolveScheduleDisplay(s);
                                return `<div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100 cursor-pointer hover:bg-slate-100 transition"
                                    onclick="openDayModal('${(s.exam_date||s.date||'').substring(0,10)}')">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-full ${_schedColor(s.id)} shrink-0"></div>
                                        <div>
                                            <h4 class="font-semibold text-sm text-slate-800">${esc(courseName||'Course')}</h4>
                                            <p class="text-xs text-slate-500">${esc(s.exam_type||s.examType||'Exam')} · ${esc(examDate||'No date')}${campus?' · '+esc(campus):''}</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold shrink-0">Approved</span>
                                </div>`;
                            }).join('')}
                          </div>`
                    }
                </div>
            </div>`;
            return html;
        }

        // ── Day Detail Modal ──────────────────────────────────────────────────────
        window.openDayModal = function(isoDate) {
            const existing = document.getElementById('calDayModal');
            if (existing) existing.remove();

            const [y, m, d] = isoDate.split('-').map(Number);
            const dateObj    = new Date(y, m - 1, d);
            const dayNames   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            const label      = `${dayNames[dateObj.getDay()]}, ${monthNames[m-1]} ${d}, ${y}`;

            const scheds = allData.filter(d => {
                if (d.type !== 'schedule') return false;
                const raw = (d.exam_date || d.date || '').substring(0, 10);
                return raw === isoDate;
            });

            scheds.sort((a, b) => (a.time_slot || '').localeCompare(b.time_slot || ''));

            const statusStyle = st => {
                if (st === 'Approved') return 'background:#d1fae5;color:#065f46;';
                if (st === 'Rejected') return 'background:#fee2e2;color:#991b1b;';
                return 'background:#fef3c7;color:#92400e;';
            };

            const cards = scheds.length === 0
                ? `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3.5rem 1rem;color:#94a3b8;">
                       <svg style="width:3rem;height:3rem;margin-bottom:0.75rem;color:#cbd5e1;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                       <p style="font-size:0.875rem;font-weight:600;color:#64748b;">No schedules on this day</p>
                   </div>`
                : scheds.map(s => {
                    const { courseName, roomName, proctorName, campus } = resolveScheduleDisplay(s);
                    const collegeProgram = [s.college, s.program].filter(Boolean).join(' · ') || '';
                    const subtitleParts = [s.exam_type || s.examType, s.year_level ? `${s.year_level} Year` : '', s.section ? `Section ${s.section}` : ''].filter(Boolean);
                    return `
                    <div style="border:1px solid #e2e8f0;border-left:4px solid #10b981;border-radius:0.75rem;padding:1rem;background:white;margin-bottom:0.75rem;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:0.75rem;">
                            <div style="flex:1;min-width:0;">
                                <p style="font-weight:700;color:#0f172a;font-size:0.875rem;line-height:1.3;">${esc(courseName || 'Course')}</p>
                                ${subtitleParts.length ? `<p style="font-size:0.75rem;color:#64748b;margin-top:0.25rem;">${subtitleParts.map(esc).join(' · ')}</p>` : ''}
                            </div>
                            <span style="font-size:0.7rem;font-weight:700;padding:0.25rem 0.625rem;border-radius:9999px;white-space:nowrap;flex-shrink:0;${statusStyle(s.status)}">${esc(s.status || 'Pending')}</span>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem 1rem;">
                            ${s.time_slot ? `<div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:#475569;">
                                <svg style="width:0.875rem;height:0.875rem;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>${esc(s.time_slot)}</span>
                            </div>` : ''}
                            ${roomName ? `<div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:#475569;">
                                <svg style="width:0.875rem;height:0.875rem;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/></svg>
                                <span>${esc(roomName)}</span>
                            </div>` : ''}
                            ${s.course_code ? `<div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:#475569;">
                                <svg style="width:0.875rem;height:0.875rem;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span>${esc(s.course_code)}</span>
                            </div>` : ''}
                            ${collegeProgram ? `<div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:#475569;">
                                <svg style="width:0.875rem;height:0.875rem;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                <span>${esc(collegeProgram)}</span>
                            </div>` : ''}
                            ${campus ? `<div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:#475569;grid-column:span 2;">
                                <svg style="width:0.875rem;height:0.875rem;color:#94a3b8;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>${esc(campus)}</span>
                            </div>` : ''}
                        </div>
                    </div>`;
                }).join('');

            const modal = document.createElement('div');
            modal.id        = 'calDayModal';
            modal.className = 'fixed inset-0 z-[500] flex items-center justify-center p-4';
            modal.style.background = 'rgba(0,0,0,0.5)';
            modal.style.backdropFilter = 'blur(2px)';
            modal.innerHTML = `
            <div style="background:white;border-radius:1.25rem;width:100%;max-width:480px;max-height:88vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.2);animation:slideUp .22s ease;">
                <div style="background:linear-gradient(135deg,#065f46,#059669);padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <div style="width:2.5rem;height:2.5rem;background:rgba(255,255,255,0.2);border-radius:0.625rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg style="width:1.25rem;height:1.25rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h2 style="font-size:0.9375rem;font-weight:700;color:white;line-height:1.2;">${label}</h2>
                            <p style="font-size:0.75rem;color:rgba(167,243,208,1);margin-top:0.125rem;">${scheds.length} exam${scheds.length !== 1 ? 's' : ''} scheduled</p>
                        </div>
                    </div>
                    <button onclick="document.getElementById('calDayModal').remove()"
                        style="width:2rem;height:2rem;background:rgba(255,255,255,0.2);border:none;border-radius:0.5rem;display:flex;align-items:center;justify-content:center;cursor:pointer;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        <svg style="width:1rem;height:1rem;color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div style="flex:1;overflow-y:auto;padding:1.25rem;scrollbar-width:thin;scrollbar-color:#a7f3d0 #f8fafc;">
                    ${cards}
                </div>
                <div style="padding:1rem 1.5rem;border-top:1px solid #f1f5f9;background:#f8fafc;display:flex;justify-content:flex-end;flex-shrink:0;">
                    <button onclick="document.getElementById('calDayModal').remove()"
                        style="padding:0.5rem 1.5rem;background:#059669;color:white;font-size:0.875rem;font-weight:600;border:none;border-radius:0.625rem;cursor:pointer;"
                        onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">Close</button>
                </div>
            </div>`;

            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
        };

        function navigateCalendar(direction) {
            if (direction === 'prev') { calendarMonth--; if (calendarMonth < 0) { calendarMonth = 11; calendarYear--; } }
            else if (direction === 'next') { calendarMonth++; if (calendarMonth > 11) { calendarMonth = 0; calendarYear++; } }
            else if (direction === 'today') { const n = new Date(); calendarMonth = n.getMonth(); calendarYear = n.getFullYear(); }
            const c = document.querySelector('#calendar-container');
            if (c) c.innerHTML = renderCalendar();
        }

        // ─────────────────────────────────────────────────────────────────────────
        // 22. ATTACH HANDLERS
        // ─────────────────────────────────────────────────────────────────────────
        function attachHandlers() {
            document.querySelectorAll('[data-view]').forEach(btn => {
            btn.onclick = () => {
            saveSidebarScroll();
            currentView = btn.dataset.view;
            localStorage.setItem('flexam_currentView', currentView);  // ← ADD THIS
            renderApp();
        };
            });

            if (currentView === 'analytics') {
                // Render charts after DOM is ready, respecting date + campus filters
                setTimeout(() => analyticsFilter(), 0);
            }

            if (currentView === 'users') {
                ['addUserModal','editUserModal','deleteUserModal'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.addEventListener('click', e => { if (e.target === el) closeModal(id); });
                });
                loadUsers();
            }
        }

            function renderDataButtons(view, recordCount) {
            const hasLog = lastImportLog && lastImportLog.failed > 0;
            const logTooltip = hasLog
        ? `Last import: ${lastImportLog.success} added, ${lastImportLog.failed} skipped`
        : '';

    return `
    <div class="flex items-center gap-2 flex-wrap">
        ${(view !== 'feedbacks' && view !== 'analytics') ? `<button onclick="handleTemplate('${view}')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm whitespace-nowrap">
            <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Template
        </button>` : ''}

        ${(view !== 'feedbacks' && view !== 'analytics') ? `<button onclick="handleImport('${view}')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm whitespace-nowrap">
            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Import
        </button>` : ''}

        <button onclick="handleExport('${view}')"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition shadow-sm whitespace-nowrap">
            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export
        </button>

         ${hasLog ? `
        <button onclick="reopenImportLog()" title="${logTooltip}"
            class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 border border-red-400 rounded-lg transition shadow-sm whitespace-nowrap">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            ${lastImportLog.failed} Import Error${lastImportLog.failed !== 1 ? 's' : ''}
        </button>` : ''}

        ${recordCount !== undefined
            ? `<span id="rowCountBadge" class="text-xs text-slate-400 ml-1 font-medium italic">${recordCount} records</span>`
            : ''}
    </div>`;
}   

// ── Dashboard Quick Export Modal ──────────────────────────────────────────────
window.openDashboardExportModal = function() {
    if (document.getElementById('dashExportModal')) document.getElementById('dashExportModal').remove();

    const views = [
        { id: 'schedule-mgmt', label: 'Schedules',  icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'bg-blue-100 text-blue-600',   count: allData.filter(d=>d.type==='schedule').length },
        { id: 'courses',       label: 'Courses',    icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', color: 'bg-emerald-100 text-emerald-600', count: allData.filter(d=>d.type==='course').length },
        { id: 'rooms',         label: 'Rooms',      icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', color: 'bg-cyan-100 text-cyan-600',     count: allData.filter(d=>d.type==='room').length },
        { id: 'proctors',      label: 'Proctors',   icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', color: 'bg-purple-100 text-purple-600', count: allData.filter(d=>d.type==='proctor').length },
        { id: 'colleges',      label: 'Colleges',   icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z', color: 'bg-orange-100 text-orange-600', count: allData.filter(d=>d.type==='college').length },
    ];

    const overlay = document.createElement('div');
    overlay.id = 'dashExportModal';
    overlay.className = 'modal-overlay';
    overlay.style.cssText = 'display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:50;align-items:center;justify-content:center;padding:1rem;';
    overlay.innerHTML = `
    <div class="modal-box" style="max-width:480px;">
        <div style="background:linear-gradient(135deg,#047857,#059669);padding:1.25rem 1.5rem;border-radius:1rem 1rem 0 0;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <p style="color:white;font-weight:700;font-size:1rem;margin:0;">Export Data</p>
                <p style="color:rgba(255,255,255,0.7);font-size:0.75rem;margin:0.2rem 0 0;">Download records as CSV</p>
            </div>
            <button onclick="document.getElementById('dashExportModal').remove()" style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.2);border:none;cursor:pointer;color:white;font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>
        </div>
        <div style="padding:1.25rem 1.5rem;display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
            ${views.map(v => `
            <button onclick="handleExport('${v.id}');document.getElementById('dashExportModal').remove();"
                style="display:flex;align-items:center;gap:0.75rem;padding:0.875rem;border:1.5px solid #e2e8f0;border-radius:0.75rem;background:white;cursor:pointer;text-align:left;transition:all 0.15s;"
                onmouseover="this.style.borderColor='#047857';this.style.background='#f0fdf4';"
                onmouseout="this.style.borderColor='#e2e8f0';this.style.background='white';">
                <div style="width:36px;height:36px;border-radius:8px;flex-shrink:0;display:flex;align-items:center;justify-content:center;" class="${v.color}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="${v.icon}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div>
                    <p style="font-size:0.8rem;font-weight:700;color:#1e293b;margin:0;">${v.label}</p>
                    <p style="font-size:0.7rem;color:#94a3b8;margin:0;">${v.count} record${v.count !== 1 ? 's' : ''}</p>
                </div>
            </button>`).join('')}
        </div>
        <div style="padding:0.75rem 1.5rem 1.25rem;border-top:1px solid #e2e8f0;text-align:center;">
            <p style="font-size:0.7rem;color:#94a3b8;margin:0;">Files will download as .csv format</p>
        </div>
    </div>`;

    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
};

                // ── CSV Export ────────────────────────────────────────────────────────────────
                window.handleExport = function(view) {
                    const configs = {
                        'schedule-mgmt': {
                            data: allData.filter(d => d.type === 'schedule').map(s => {
                                const { courseName, roomName, proctorName, examDate } = resolveScheduleDisplay(s);
                                const resolvedCollege = (()=>{
                    if(s.college) return s.college;
                    let course=allData.find(d=>d.type==='course'&&dbId(String(d.id))===dbId(String(s.course_id||'')));
                    if(!course&&(s.course_code||s.course_name)){const code=(s.course_code||s.course_name||'').trim().toLowerCase();course=allData.find(d=>d.type==='course'&&(d.course_code||'').toLowerCase()===code);}
                    const progName = course?(course.college||''):'';
                    if(!progName) return '';
                    const colRec = allData.find(d=>d.type==='college'&&Array.isArray(d.programs)&&d.programs.some(p=>p.toLowerCase()===progName.toLowerCase()));
                    return colRec ? colRec.code : progName;
                })();
                return {
                    course_code:  s.course_code  || s.course_name || courseName || '',
                    college:      resolvedCollege,
                    section:      s.section      || '',
                    exam_type:    s.exam_type    || '',
                    exam_date:    examDate        || '',
                    time_slot:    s.time_slot    || '',
                    duration:     s.duration     || '',
                    room_name:    s.is_online ? 'Online' : (roomName || ''),
                    is_online:    s.is_online ? 1 : 0,
                    proctor_name: proctorName     || '',
                    campus:       s.campus        || '',
                    status:       s.status        || 'Pending'
                };
            }),
            fields: ['course_code', 'program', 'exam_type', 'year_level', 'section', 'exam_date', 'time_slot', 'room_name', 'is_online', 'proctor_name', 'campus'],
            filename: 'schedules_export.csv'
        },
        'analytics': {
            data: allData.filter(d => d.type === 'schedule').map(s => {
                const { courseName, semester, roomName, proctorName, examDate, campus } = resolveScheduleDisplay(s);
                return {
                    id:           s.id                                      || '',
                    course_code:  s.course_code  || s.course_name || courseName || '',
                    course_name:  courseName                                || '',
                    college:      s.college                                 || '',
                    program:      s.program      || s.college_program       || '',
                    year_level:   s.year_level                              || '',
                    section:      s.section                                 || '',
                    exam_type:    s.exam_type                               || '',
                    semester:     s.semester     || semester                || '',
                    exam_date:    examDate                                  || '',
                    time_slot:    s.time_slot                               || '',
                    duration:     s.duration                                || '',
                    room:         roomName                                  || '',
                    proctor:      proctorName                               || '',
                    campus:       s.campus       || campus                  || '',
                    status:       s.status                                  || 'Pending'
                };
            }),
            fields: ['id','course_code','course_name','college','program','year_level','section','exam_type','semester','exam_date','time_slot','duration','room','proctor','campus','status'],
            filename: 'analytics_export.csv'
        },
        'feedbacks': {
            data: allData.filter(d => d.type === 'feedback').map(f => ({
                id:           f.id           || '',
                student_name: f.student_name || 'Anonymous',
                college:      f.college      || '',
                program:      f.program      || '',
                subject:      f.subject      || '',
                category:     f.category     || '',
                message:      f.message      || '',
                rating:       f.rating       || '',
                exam_difficulty: f.exam_difficulty || '',
                campus:       f.campus       || '',
                status:       f.is_read ? 'Read' : 'Unread',
                date:         f.created_at ? new Date(f.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) : ''
            })),
            fields: ['id','student_name','college','program','subject','category','message','exam_difficulty','rating','campus','status','date'],
            filename: 'feedbacks_export.csv'
        },
        'courses': {
            data: allData.filter(d => d.type === 'course'),
            fields: ['id','course_code','course_name','college','program','year_level','semester','campus'],
            filename: 'courses_export.csv'
        },
        'rooms': {
            data: allData.filter(d => d.type === 'room').map(r => ({
                ...r,
                locked: (r.locked === true || r.locked === 1 || r.locked === '1') ? 1 : 0
            })),
            fields: ['id','name','building','capacity','floor','campus','locked','block_reason','blocked_from','blocked_to'],
            filename: 'rooms_export.csv'
        },
        'colleges': {
    data: allData.filter(d => d.type === 'college').map(c => ({
        ...c,
        programs: Array.isArray(c.programs) ? c.programs.join(';') : (c.code || '')
    })),
    fields: ['id','name','programs','description','campus'],
    filename: 'colleges_export.csv'
},
        'proctors': {
            data: allData.filter(d => d.type === 'proctor'),
            fields: ['id','name','college_program','email','phone','campus'],
            filename: 'proctors_export.csv'
        }
    };

    const cfg = configs[view];
    if (!cfg) { showToast('Export not available for this view', 'error'); return; }
    if (cfg.data.length === 0) { showToast('No data to export', 'error'); return; }

    const header = cfg.fields.join(',');
    const rows = cfg.data.map(row =>
        cfg.fields.map(f => {
            const val = row[f] !== undefined && row[f] !== null ? String(row[f]) : '';
            return val.includes(',') || val.includes('"') || val.includes('\n')
                ? `"${val.replace(/"/g, '""')}"` : val;
        }).join(',')
    );
    const csv = [header, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = cfg.filename; a.click();
    URL.revokeObjectURL(url);
    showToast(`Exported ${cfg.data.length} records as CSV`);
};

// ── CSV Template Download ─────────────────────────────────────────────────────
window.handleTemplate = function(view) {
    const templates = {
        'schedule-mgmt': {
            fields: ['course_code', 'course_name', 'college', 'program', 'exam_type', 'year_level', 'semester', 'section', 'exam_date', 'time_slot', 'room_name', 'is_online', 'proctor_name', 'campus'],
            example: ['ITPM311', 'IT Project Management', 'CCS', 'BSIT', 'Prelim', '3rd Year', '1st Semester', '3-Y1-5', '2026-03-10', '08:00 AM - 09:00 AM', 'CAS201, CAS Building', '0', 'Dr. Juan Dela Cruz', 'Quezon City'],
            filename: 'schedule_template.csv'
        },
        'courses': {
            fields: ['course_code', 'course_name', 'college', 'program', 'year_level', 'semester', 'campus'],
            example: ['RIPH311', 'Research in Public Health', 'CCS', 'BSIT', '3rd Year', '1st Semester', 'Quezon City'],
            filename: 'courses_template.csv'
        },
        'rooms': {
            fields: ['building', 'name', 'capacity', 'floor', 'campus', 'blocked'],
            example: ['CAS', '201', '40', '2nd Floor', 'Quezon City', '0'],
            filename: 'rooms_template.csv',
            notes: '// blocked: 0 = not blocked, 1 = blocked'
        },
        'colleges': {
            fields: ['name', 'programs', 'description', 'campus'],
            example: ['College of Computer Studies', 'BSIT=BS in Information Technology', '', 'Quezon City'],
            filename: 'colleges_template.csv'
        },
        'proctors': {
            fields: ['name', 'college_program', 'email', 'phone', 'campus'],
            example: ['Dr. Juan Dela Cruz', 'BSIT', 'jdelacruz@fatima.edu.ph', '+63-912-345-6789', 'Quezon City'],
            filename: 'proctors_template.csv'
        },
        'feedbacks': {
            fields: ['student_name', 'message', 'rating', 'campus', 'date'],
            example: ['Juan Dela Cruz', 'The exam schedule was clear', '5', 'Quezon City', 'March 10, 2026'],
            filename: 'feedbacks_template.csv'
        },
        'analytics': {
            fields: ['course_code', 'program', 'exam_type', 'exam_date', 'campus', 'status'],
            example: ['ITPM311', 'BSIT', 'Prelim', 'March 10, 2026', 'Quezon City', 'Approved'],
            filename: 'analytics_template.csv'
        },
    };

    const tpl = templates[view];
    if (!tpl) { showToast('No template available', 'error'); return; }

    const escCsv = val => {
        const s = String(val ?? '');
        return (s.includes(',') || s.includes('"') || s.includes('\n'))
            ? `"${s.replace(/"/g, '""')}"` : s;
    };

    const lines = [
        tpl.fields.join(','),
        tpl.example.map(escCsv).join(','),
    ];

    const csv  = lines.join('\n') + '\n';
    const blob = new Blob([csv], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = tpl.filename; a.click();
    URL.revokeObjectURL(url);
    showToast('Template downloaded');
};

// ── CSV Import ────────────────────────────────────────────────────────────────
window.handleImport = function(view) {
    const viewLabels = {
        'schedule-mgmt': 'Schedules', 'analytics': 'Analytics', 'feedbacks': 'Feedbacks',
        'courses': 'Courses', 'rooms': 'Rooms', 'colleges': 'Colleges', 'proctors': 'Proctors'
    };
    const label = viewLabels[view] || 'Data';

    if (document.getElementById('importModal')) return;
    const modal = document.createElement('div');
    modal.id = 'importModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-5 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Import ${label}</h2>
                <p class="text-xs text-slate-400 mt-0.5">Upload CSV or Excel file with multiple sheets to import records</p>
            </div>
            <button onclick="document.getElementById('importModal').remove()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-2">
                <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-blue-700">Use the <strong>Template</strong> button to download the correct CSV format before importing.</p>
            </div>
            <div id="importDropZone"
                class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/30 transition"
                onclick="document.getElementById('importFileInput').click()"
                ondragover="event.preventDefault();this.classList.add('border-emerald-400','bg-emerald-50/30')"
                ondragleave="this.classList.remove('border-emerald-400','bg-emerald-50/30')"
                ondrop="event.preventDefault();this.classList.remove('border-emerald-400','bg-emerald-50/30');handleImportDrop(event,'${view}')">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <p class="text-sm font-semibold text-slate-600">Drop CSV or Excel file here</p>
                <p class="text-xs text-slate-400 mt-1">or click to browse (.csv or .xlsx, multi-sheet supported)</p>
                <input type="file" id="importFileInput" accept=".csv,.xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" onchange="processImportFile(this.files[0],'${view}')">    
            </div>
            <div id="importPreview" class="hidden space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700" id="importFileName"></p>
                    <span id="importRowCount" class="text-xs text-emerald-600 font-bold"></span>
                </div>
                <div class="max-h-40 overflow-y-auto border border-slate-200 rounded-lg text-xs font-mono" id="importPreviewTable"></div>
            </div>
            <div id="importError" class="hidden text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3">
                <button onclick="document.getElementById('importModal').remove()" class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-sm">Cancel</button>
                <button id="importConfirmBtn" onclick="confirmImport('${view}')" disabled class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition text-sm opacity-50 cursor-not-allowed">Import</button>
            </div>
        </div>
    </div>`;
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
};

window.markAdminNotifsRead = function() {
    const ids = allData
        .filter(d => d.type === 'schedule' && (d.status === 'Pending' || !d.status))
        .map(d => String(d.id));
    ids.forEach(id => adminReadNotifs.add(id));
    localStorage.setItem('adminReadNotifs', JSON.stringify([...adminReadNotifs]));
    renderApp();
};

window._importParsedRows = [];
window._importSelectedFile = null;

window.handleImportDrop = function(event, view) {
    const file = event.dataTransfer.files[0];
    if (file) processImportFile(file, view);
};

window._parseCSVLine = function(line) {
    const result = [];
    let current = '', inQuotes = false;
    for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        if (ch === '"') {
            if (inQuotes && line[i + 1] === '"') { current += '"'; i++; }
            else inQuotes = !inQuotes;
        } else if (ch === ',' && !inQuotes) {
            result.push(current.trim());
            current = '';
        } else {
            current += ch;
        }
    }
    result.push(current.trim());
    return result;
};

// ── Normalize any time-slot string to "HH:MM AM - HH:MM AM" ─────────────
window._normalizeTimeSlot = function(raw) {
    if (!raw) return '';
    raw = raw.trim();
    // Helper: validate that a parsed 12-hour hour is in range 1–12 (0 is invalid in 12-hr clock)
    const _validHour12 = h => parseInt(h, 10) >= 1 && parseInt(h, 10) <= 12;
    // Already correct format: "08:00 AM - 09:00 AM" — but still reject hour 00 or > 12
    if (/^\d{2}:\d{2} [AP]M - \d{2}:\d{2} [AP]M$/.test(raw)) {
        const h = parseInt(raw.substring(0, 2), 10);
        if (h === 0 || h > 12) return ''; // 00:xx and 13:xx+ are not valid 12-hour times
        return raw;
    }
    const pad = n => String(n).padStart(2, '0');
    // Match with or without minutes: "8AM", "8:00AM", "8:30 AM", "08:00 AM"
    const rangeRe = /(\d{1,2})(?::(\d{2}))?\s*(AM|PM|am|pm)\s*[-–]\s*(\d{1,2})(?::(\d{2}))?\s*(AM|PM|am|pm)/i;
    const match = raw.match(rangeRe);
    if (match) {
        const [, h1, m1 = '00', p1, h2, m2 = '00', p2] = match;
        // Reject if either hour is 0 (invalid in 12-hour clock)
        if (!_validHour12(h1) || !_validHour12(h2)) return '';
        return `${pad(h1)}:${m1.padStart(2,'0')} ${p1.toUpperCase()} - ${pad(h2)}:${m2.padStart(2,'0')} ${p2.toUpperCase()}`;
    }
    // Unrecognized format (e.g. "00:00", bare 24-hour times) — reject instead of storing garbage
    return '';
};

// ── Parse a cell that may contain "2026-02-28 7:00AM-9:00AM"
//    Returns { date: "2026-02-28", timeSlot: "07:00 AM - 09:00 AM" }
window._parseDateTimeCell = function(cell) {
    if (!cell) return { date: '', timeSlot: '' };
    cell = cell.trim();

    // Match "YYYY-MM-DD HH:MMam/pm-HH:MMam/pm"
    const dtMatch = cell.match(/^(\d{4}-\d{2}-\d{2})\s+(.+)$/);
    if (dtMatch) {
        return {
            date:     dtMatch[1],
            timeSlot: window._normalizeTimeSlot(dtMatch[2])
        };
    }
    // Pure date only
    if (/^\d{4}-\d{2}-\d{2}$/.test(cell)) return { date: cell, timeSlot: '' };
    // Pure time only
    return { date: '', timeSlot: window._normalizeTimeSlot(cell) };
};

// ── Helper: Load XLSX library and process Excel file ─────────────────────────
window._processExcelFile = function(file, view, callback) {
    console.log('🟢 _processExcelFile called with file:', file.name);
    
    // If XLSX not loaded, load it
    if (!window.XLSX) {
        console.log('📥 XLSX not in memory, loading from CDN...');
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
        script.onload = () => {
            console.log('✅ XLSX loaded, now parsing file...');
            parseFile();
        };
        script.onerror = () => {
            console.error('❌ Failed to load XLSX');
            callback({ error: 'Failed to load XLSX library', rows: [], header: [], sheetCount: 0 });
        };
        document.head.appendChild(script);
        return;
    }
    
    console.log('✅ XLSX already in memory, parsing now...');
    parseFile();
    
    function parseFile() {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                console.log('📖 FileReader completed, parsing Excel...');
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array', cellDates: true, dateNF: 'yyyy-mm-dd' });
                console.log('📋 Workbook loaded. Sheets:', workbook.SheetNames);
                
                const allRows = [];
                const headerSet = new Set();
                
                // ===== FIRST PASS: Collect ALL headers from ALL sheets =====
                console.log('\n🔍 PASS 1: Collecting headers from all sheets...');
                for (let i = 0; i < workbook.SheetNames.length; i++) {
                    const sheetName = workbook.SheetNames[i];
                    const ws = workbook.Sheets[sheetName];
                    if (!ws) continue;
                    
                    const sheetData = XLSX.utils.sheet_to_json(ws, { header: 1 });
                    if (sheetData.length < 2) continue;
                    
                    const headerRow = sheetData[0] || [];
                    headerRow.forEach(h => {
                        const normalized = String(h || '').trim().toLowerCase().replace(/\s+/g, '_');
                        const finalCol = normalized || `col_${headerRow.indexOf(h)}`;
                        headerSet.add(finalCol);
                    });
                }
                
                const finalHeaders = Array.from(headerSet).sort();
                console.log(`   📋 Found ${finalHeaders.length} unique columns:`, finalHeaders.slice(0, 8).join(', '), finalHeaders.length > 8 ? '...' : '');
                
                // ===== SECOND PASS: Process ALL rows from ALL sheets =====
                console.log('\n🔍 PASS 2: Processing rows from all sheets...');
                for (let i = 0; i < workbook.SheetNames.length; i++) {
                    const sheetName = workbook.SheetNames[i];
                    console.log(`\n📄 Sheet ${i + 1}/${workbook.SheetNames.length}: "${sheetName}"`);
                    
                    const ws = workbook.Sheets[sheetName];
                    if (!ws) {
                        console.warn(`   Sheet object not found`);
                        continue;
                    }
                    
                    const sheetData = XLSX.utils.sheet_to_json(ws, { header: 1 });
                    console.log(`   Has ${sheetData.length} rows total`);
                    
                    if (sheetData.length < 2) {
                        console.log(`   ⏭️ Skipping - need at least header + 1 data row`);
                        continue;
                    }
                    
                    const headerRow = sheetData[0] || [];
                    const sheetHeaders = headerRow.map((h, idx) => {
                        const normalized = String(h || '').trim().toLowerCase().replace(/\s+/g, '_');
                        return normalized || `col_${idx}`;
                    });
                    console.log(`   Headers:`, sheetHeaders.slice(0, 5).join(', '), sheetHeaders.length > 5 ? '...' : '');
                    
                    let rowCount = 0;
                    for (let r = 1; r < sheetData.length; r++) {
                        const rowData = sheetData[r] || [];
                        
                        // Skip empty rows
                        if (!rowData.some(c => c !== null && c !== undefined && c !== '')) {
                            continue;
                        }
                        
                        // Initialize with ALL final headers (empty by default)
                        const obj = {};
                        finalHeaders.forEach(h => obj[h] = '');
                        
                        // Fill in values from this sheet's columns
                        sheetHeaders.forEach((h, col) => {
                            const cellVal = rowData[col];
                            if (cellVal !== null && cellVal !== undefined && cellVal !== '') {
                                // cellDates:true makes XLSX return real JS Date objects for date cells.
                                // Convert them to YYYY-MM-DD so _toISODate / examDate parsing works correctly.
                                if (cellVal instanceof Date && !isNaN(cellVal.getTime())) {
                                    const yyyy = cellVal.getFullYear();
                                    const mm   = String(cellVal.getMonth() + 1).padStart(2, '0');
                                    const dd   = String(cellVal.getDate()).padStart(2, '0');
                                    obj[h] = `${yyyy}-${mm}-${dd}`;
                                } else {
                                    obj[h] = String(cellVal).trim();
                                }
                            }
                        });
                        
                        allRows.push(obj);
                        rowCount++;
                    }
                    console.log(`   ✅ Added ${rowCount} rows from "${sheetName}"`);
                }
                
                console.log(`\n✨ IMPORT SUMMARY:`);
                console.log(`   Sheets processed: ${workbook.SheetNames.length}`);
                console.log(`   Total rows: ${allRows.length}`);
                console.log(`   Total columns: ${finalHeaders.length}`);
                
                if (finalHeaders.length === 0) {
                    console.error('No headers found');
                    callback({ error: 'No valid header found', rows: [], header: [], sheetCount: workbook.SheetNames.length });
                    return;
                }
                
                if (allRows.length === 0) {
                    console.error('No data rows found');
                    callback({ error: `No data rows in ${workbook.SheetNames.length} sheet(s)`, rows: [], header: finalHeaders, sheetCount: workbook.SheetNames.length });
                    return;
                }
                
                callback({
                    error: null,
                    rows: allRows,
                    header: finalHeaders,
                    sheetCount: workbook.SheetNames.length
                });
                
            } catch (err) {
                console.error('❌ Parse error:', err);
                callback({ error: `Error: ${err.message}`, rows: [], header: [], sheetCount: 0 });
            }
        };
        reader.onerror = (err) => {
            console.error('❌ File read error:', err);
            callback({ error: 'File read error', rows: [], header: [], sheetCount: 0 });
        };
        reader.readAsArrayBuffer(file);
    }
};

// ── processImportFile ──────────────────────────────────────────────────────
window.processImportFile = function(file, view) {
    if (!file) return;
    window._importSelectedFile = file; // ← store reference so confirmImport can use it
    console.log('📂 Processing file:', file.name, '- Always trying Excel first, then CSV fallback');
 
    const tryExcel = () => {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                if (!window.XLSX) {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
                    script.onload = () => parseAsExcel(data);
                    script.onerror = tryCSV;
                    document.head.appendChild(script);
                    return;
                }
                parseAsExcel(data);
            } catch (err) {
                tryCSV();
            }
        };
        reader.readAsArrayBuffer(file);
    };
    
    const parseAsExcel = (data) => {
        try {
            const workbook = XLSX.read(data, { type: 'array' });
            window._processExcelFile(file, view, (result) => {
                if (result.error) {
                    tryCSV();
                    return;
                }
                console.log('✅ Excel imported:',  result.rows.length, 'rows from', result.sheetCount, 'sheet(s)');
                window._handleImportData(file, view, result.rows, result.header, result.sheetCount);
            });
        } catch (err) {
            console.log('Not Excel - trying CSV...');
            tryCSV();
        }
    };
    
    const tryCSV = () => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const rawLines = e.target.result.split('\n');
            const KNOWN_HEADERS = new Set([
                'course_code','course_name','course_co','college','program','year_level','semester','campus',
                'room_name','room_type','building','capacity','floor',
                'proctor_name','email','contact','department',
                'college_name','college_code','exam_type','exam_dat','exam_date',
                'time_slot','duration','proctor','status','section','date'
            ]);
            
            const looksLikeHeader = (cells) => { const lower = cells.map(c => c.replace(/^"|"$/g, '').trim().toLowerCase()); return lower.some(c => KNOWN_HEADERS.has(c)); };
            let sections = [];
            let currentHeader = null;
            let currentDataLines = [];
            
            for (const rawLine of rawLines) {
                const trimmed = rawLine.trim();
                if (!trimmed) {
                    if (currentHeader && currentDataLines.length) {
                        sections.push({ header: currentHeader, dataLines: [...currentDataLines] });
                        currentDataLines = [];
                    }
                    continue;
                }
                const parsed = window._parseCSVLine(trimmed).map(c => c.replace(/^"|"$/g, '').trim());
                if (looksLikeHeader(parsed)) {
                    if (currentHeader && currentDataLines.length) {
                        sections.push({ header: currentHeader, dataLines: [...currentDataLines] });
                        currentDataLines = [];
                    }
                    currentHeader = parsed.map(h => h.trim().toLowerCase());
                } else if (currentHeader) {
                    currentDataLines.push(trimmed);
                }
            }
            if (currentHeader && currentDataLines.length) {
                sections.push({ header: currentHeader, dataLines: [...currentDataLines] });
            }
            
            if (!sections.length) {
                const err = document.getElementById('importError');
                if (err) err.textContent = 'No data found in file';
                return;
            }
            
            const superHeaderSet = [];
            const superHeaderIndex = {};
            sections.forEach(sec => {
                sec.header.forEach(h => {
                    if (!(h in superHeaderIndex)) {
                        superHeaderIndex[h] = superHeaderSet.length;
                        superHeaderSet.push(h);
                    }
                });
            });
            
            const mergedRows = [];
            sections.forEach(sec => {
                sec.dataLines.forEach(line => {
                    const vals = window._parseCSVLine(line).map(v => v.replace(/^"|"$/g, '').trim());
                    const obj = {};
                    superHeaderSet.forEach(h => obj[h] = '');
                    sec.header.forEach((h, i) => { if (vals[i]) obj[h] = vals[i]; });
                    if (Object.values(obj).some(v => v)) mergedRows.push(obj);
                });
            });
            
            console.log('✅ CSV imported:', mergedRows.length, 'rows from', sections.length, 'section(s)');
            window._handleImportData(file, view, mergedRows, superHeaderSet, sections.length);
        };
        reader.readAsText(file);
    };
    
    tryExcel();
};

// ── Unified import data handler (used by both CSV and Excel) ──────────────────
window._handleImportData = function(file, view, rows, header, sheetCount) {
    function _toISODate(raw) {
        if (!raw) return '';
        raw = String(raw).trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw;
        // Excel serial number fallback (e.g. "46500") — only triggered if cellDates didn't convert it
        if (/^\d{4,5}$/.test(raw)) {
            const serial = parseInt(raw, 10);
            // Excel epoch: Jan 1 1900 = serial 1 (with Lotus 1-2-3 leap-year bug: serial 60 = Feb 29 1900 which didn't exist)
            const msPerDay = 86400000;
            const excelEpoch = new Date(Date.UTC(1899, 11, 30)); // Dec 30 1899
            const d = new Date(excelEpoch.getTime() + serial * msPerDay);
            if (!isNaN(d.getTime()) && d.getFullYear() >= 2000) {
                const yyyy = d.getUTCFullYear();
                const mm   = String(d.getUTCMonth() + 1).padStart(2, '0');
                const dd   = String(d.getUTCDate()).padStart(2, '0');
                return `${yyyy}-${mm}-${dd}`;
            }
        }
        // 'Month DD, YYYY' e.g. 'April 30, 2026' or 'March 10, 2026'
        const _mmap = {january:'01',february:'02',march:'03',april:'04',may:'05',june:'06',july:'07',august:'08',september:'09',october:'10',november:'11',december:'12',jan:'01',feb:'02',mar:'03',apr:'04',jun:'06',jul:'07',aug:'08',sep:'09',oct:'10',nov:'11',dec:'12'};
        const _wm = raw.match(/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/);
        if (_wm && _mmap[_wm[1].toLowerCase()]) return `${_wm[3]}-${_mmap[_wm[1].toLowerCase()]}-${_wm[2].padStart(2,'0')}`;
        // DD/MM/YYYY or YYYY/MM/DD
        const parts = raw.split('/');
        if (parts.length === 3) {
            const [a, b, c] = parts.map(p => p.trim());
            if (a.length === 4) return `${a}-${b.padStart(2,'0')}-${c.padStart(2,'0')}`;
            return c + '-' + b.padStart(2, '0') + '-' + a.padStart(2, '0');
        }
        return '';
    }

    if (view === 'schedule-mgmt') {
        rows.forEach(r => {
            const tsCell = r['time_slot'] || r['time_s'] || '';
            if (/^\d{4}-\d{2}-\d{2}/.test(tsCell)) {
                const parsed = window._parseDateTimeCell(tsCell);
                r._exam_date = parsed.date;
                r._time_slot = parsed.timeSlot;
            } else {
                const edCell = (r['exam_date'] || r['exam_dat'] || r['date_(yyyy-mm-dd)'] || r['date (yyyy-mm-dd)'] || r['date (month dd, yyyy)'] || r['date'] || '').trim();
                if (/year/i.test(edCell)) {
                    r._exam_date = '';
                    r._year_level_override = edCell;
                } else {
                    r._exam_date = _toISODate(edCell);
                    if (!r._exam_date && edCell) r._year_level_override = edCell;
                }
                r._time_slot = window._normalizeTimeSlot(tsCell);
            }
        });
    }

    window._importParsedRows = rows;

    const nameEl  = document.getElementById('importFileName');
    const countEl = document.getElementById('importRowCount');
    const preview = document.getElementById('importPreview');
    const table   = document.getElementById('importPreviewTable');
    const btn     = document.getElementById('importConfirmBtn');
    const errEl   = document.getElementById('importError');

    if (nameEl)  nameEl.textContent = file.name;
    if (preview) preview.classList.remove('hidden');

    let bannerText = '';
    if (sheetCount && sheetCount > 1) {
        bannerText = `ℹ️ Detected ${sheetCount} sheet${sheetCount !== 1 ? 's' : ''} — all rows have been merged (${rows.length} total).`;
        if (errEl) {
            errEl.textContent = bannerText;
            errEl.style.cssText = 'display:block;color:#1d4ed8;background:#eff6ff;border:1px solid #bfdbfe;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
            errEl.classList.remove('hidden');
        }
    } else if (errEl && errEl.textContent) {
        // Keep existing error
    } else if (errEl) {
        errEl.classList.add('hidden');
    }

    let _pastCount = 0;
    if (view === 'schedule-mgmt') {
        const _now = new Date();
        const _today = new Date(_now.getFullYear(), _now.getMonth(), _now.getDate());
        rows.forEach(r => {
            const dp = (r._exam_date || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (dp && new Date(+dp[1], +dp[2]-1, +dp[3]) < _today) _pastCount++;
        });
    }

    if (countEl) countEl.textContent = `${rows.length} row${rows.length !== 1 ? 's' : ''} ready`
        + (_pastCount > 0 ? ` · ⚠️ ${_pastCount} past-date row${_pastCount !== 1 ? 's' : ''} will be blocked` : '');
    
    if (_pastCount > 0 && errEl) {
        const _pastMsg = `⚠️ ${_pastCount} row${_pastCount !== 1 ? 's have' : ' has'} a past exam date and will be skipped during import.`;
        if (bannerText) {
            errEl.textContent += ' ' + _pastMsg;
        } else {
            errEl.textContent = _pastMsg;
            errEl.style.cssText = 'color:#92400e;background:#fffbeb;border:1px solid #fde68a;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
        }
        errEl.classList.remove('hidden');
    }

    const displayHeader = header.map(h => h.replace(/^"|"$/g,'').trim());
    const previewRows   = rows.slice(0, 5);
    const _now2  = new Date();
    const _today2 = new Date(_now2.getFullYear(), _now2.getMonth(), _now2.getDate());
    
    if (table) table.innerHTML = `
        <table class="w-full">
            <thead class="bg-slate-50 sticky top-0"><tr>
                ${displayHeader.map(h => `<th class="px-2 py-1.5 text-left text-[10px] uppercase text-slate-500 font-bold whitespace-nowrap">${h}</th>`).join('')}
                ${view === 'schedule-mgmt' ? '<th class="px-2 py-1.5 text-[10px] uppercase text-slate-500 font-bold"></th>' : ''}
            </tr></thead>
            <tbody>
                ${previewRows.map(r => {
                    let _isPast = false;
                    if (view === 'schedule-mgmt') {
                        const dp2 = (r._exam_date || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
                        _isPast = dp2 && new Date(+dp2[1], +dp2[2]-1, +dp2[3]) < _today2;
                    }
                    return `<tr class="${_isPast ? 'bg-red-50' : 'border-t border-slate-100'}">
                        ${header.map(h => `<td class="px-2 py-1.5 ${_isPast ? 'text-red-400 line-through' : 'text-slate-700'} truncate max-w-[120px]" title="${r[h]||''}">${r[h]||'—'}</td>`).join('')}
                        ${view === 'schedule-mgmt' ? `<td class="px-2 py-1 whitespace-nowrap">${_isPast ? '<span style="font-size:10px;font-weight:700;color:#dc2626;">⛔ PAST DATE</span>' : ''}</td>` : ''}
                    </tr>`;
                }).join('')}
            </tbody>
        </table>
        ${rows.length > 5 ? `<p class="text-center text-xs text-slate-400 p-2">...and ${rows.length - 5} more rows</p>` : ''}`;

    window._importFileHash = null;
    (async () => {
        const _btn   = document.getElementById('importConfirmBtn');
        const _errEl = document.getElementById('importError');
        try {
            const _hashBuf = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());
            window._importFileHash = Array.from(new Uint8Array(_hashBuf))
                .map(b => b.toString(16).padStart(2, '0')).join('');

            const _chk = await safeFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'check', file_hash: window._importFileHash, import_type: view === 'proctors' ? 'proctors' : view === 'colleges' ? 'colleges' : view === 'courses' ? 'courses' : view === 'rooms' ? 'rooms' : view === 'schedule-mgmt' ? 'schedule' : view })
            });

            if (_chk && (_chk.status === 'already_imported' || _chk.status === 'locked')) {
                const isPermBlocked = (_chk.status === 'already_imported');
                const _chkSecs = _chk.data?.seconds_left ?? _chk.seconds_left ?? 30;
                const msgText = isPermBlocked
                    ? '⛔ This file has already been imported into the system.'
                    : `⏳ Another user is currently importing this file. Please try again in ${_chkSecs} second${_chkSecs !== 1 ? 's' : ''}.`;

                // Keep modal open — show red banner inside AND toast at bottom
                if (_btn) {
                    _btn.disabled = true;
                    _btn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                if (_errEl) {
                    _errEl.textContent = msgText;
                    _errEl.style.cssText = 'display:block;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
                    _errEl.classList.remove('hidden');
                }
                showToast(msgText, 'error', 6000);
            } else {
                if (_btn) {
                    _btn.disabled = false;
                    _btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                if (_errEl && !_errEl.textContent.startsWith('ℹ️')) {
                    _errEl.textContent = '';
                    _errEl.classList.add('hidden');
                }
            }
        } catch(e) {
            window._importFileHash = null;
            if (_btn) {
                _btn.disabled = false;
                _btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    })();
};

window.showImportResults = function(success, failed, skippedRows) {
    // Log is already set in confirmImport before this is called
    document.getElementById('importLogBtn')?.remove();

    // ── Inject the log button directly into the toolbar ──────────────────
    // Remove any existing log button first
    document.getElementById('importLogBtn')?.remove();

   
    const totalRows = success + failed;
    const allGood   = failed === 0;
    const allBad    = success === 0 && failed > 0;
    const headerBg  = allGood ? 'bg-emerald-50' : allBad ? 'bg-red-50' : 'bg-amber-50';
    const iconBg    = allGood ? 'bg-emerald-100' : allBad ? 'bg-red-100' : 'bg-amber-100';
    const iconSvg   = allGood
        ? `<svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`
        : allBad
        ? `<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`
        : `<svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`;

    const resolvableRows = skippedRows.filter(s => s.canAutoResolve);
    const skippedHtml = skippedRows.length > 0
        ? `<div class="px-6 py-4 overflow-y-auto flex-1">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Why rows were skipped:</p>
            <div class="space-y-2 pr-1">
                ${skippedRows.map((s, i) =>
                    `<div class="flex items-start gap-3 p-3 rounded-xl ${s.canAutoResolve ? 'bg-purple-50 border border-purple-100' : s.type === 'duplicate' ? 'bg-orange-50 border border-orange-100' : 'bg-red-50 border border-red-100'}">
                        <div class="w-5 h-5 ${s.canAutoResolve ? 'bg-purple-200 text-purple-700' : 'bg-red-200 text-red-700'} rounded-full flex items-center justify-center shrink-0 mt-0.5 text-[10px] font-bold">${i + 1}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate" title="${s.label || ''}">${s.label || ('Row ' + (i + 1))}</p>
                            <p class="text-xs ${s.canAutoResolve ? 'text-purple-700' : 'text-red-600'} mt-0.5">${s.reason}</p>
                            ${s.canAutoResolve ? '<p class="text-[10px] text-purple-500 mt-1 font-medium">&#9889; A free room &amp; time slot will be assigned automatically</p>' : ''}
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 ${s.canAutoResolve ? 'bg-purple-100 text-purple-700' : s.type === 'duplicate' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-600'}">
                            ${s.canAutoResolve ? '&#9889; Fixable' : s.type === 'duplicate' ? '&#9888; Duplicate' : '&#10005; Error'}
                        </span>
                    </div>`
                ).join('')}
            </div>
           </div>`
        : `<div class="px-6 py-8 text-center">
               <div class="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-3">
                   <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
               </div>
               <p class="font-bold text-slate-700">All records imported successfully!</p>
               <p class="text-sm text-slate-400 mt-1">${success} record${success !== 1 ? 's' : ''} added</p>
           </div>`;

    const modal = document.createElement('div');
    modal.id = 'importResultModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/60 z-[200] px-4 py-10';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 ${headerBg}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 ${iconBg}">${iconSvg}</div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Import Results</h3>
                    <p class="text-xs text-slate-500 mt-0.5">${totalRows} row${totalRows !== 1 ? 's' : ''} processed</p>
                </div>
            </div>
            <button onclick="document.getElementById('importResultModal').remove()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4 flex gap-3 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 flex-1">
                <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Added</p>
                    <p class="text-2xl font-bold text-emerald-700">${success}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 border border-red-200 flex-1">
                <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Skipped</p>
                    <p class="text-2xl font-bold text-red-600">${failed}</p>
                </div>
            </div>
        </div>
        ${skippedHtml}
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
            <div>
                ${resolvableRows.length > 0 ? `
                <p class="text-xs text-purple-600 font-semibold">
                    &#9889; ${resolvableRows.length} booking conflict${resolvableRows.length > 1 ? 's' : ''} can be auto-fixed
                </p>` : ''}
            </div>
            <div class="flex gap-2">
                <button onclick="document.getElementById('importResultModal').remove()"
                    class="px-5 py-2.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-xl transition text-sm">
                    Done
                </button>
                ${resolvableRows.length > 0 ? `
                <button onclick="autoResolveImportConflicts(window._importResolvableRows)"
                    class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg>
                    Auto-Fix ${resolvableRows.length} Conflict${resolvableRows.length > 1 ? 's' : ''}
                </button>` : ''}
            </div>
        </div>
    </div>`;
    window._importResolvableRows = resolvableRows;
    // ── Persist so conflicts survive page reload ───────
    try {
        localStorage.setItem('flexam_pendingImportConflicts', JSON.stringify(resolvableRows));
    } catch(e) {}
    // ── Immediately refresh the Auto-Resolved card with the new conflict count ──
    renderApp();
    document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

        window.autoResolveImportConflicts = async function(resolvableRows) {
            if (!resolvableRows || resolvableRows.length === 0) return;
            document.getElementById('importResultModal')?.remove();

            // Build a live booking snapshot so we don't double-assign slots
            const liveSchedules = allData.filter(d => d.type === 'schedule').map(s => Object.assign({}, s));

            let fixed = 0, failed = 0, failedLabels = []; // each entry: { label, reason }

            for (const s of resolvableRows) {
                if (!s.importRow) { failed++; continue; }
                const { courseCode, examType, yearLevel, section, duration, examDate, campus,
                        semester, courseId, courseName, college, roomId, roomName, proctorId, proctorName } = s.importRow;

                const fix = _arFindFix({
                    id: '__import_' + Math.random(),
                    exam_date: examDate,
                    campus,
                    duration,
                    room_id: roomId,
                    time_slot: null
                }, liveSchedules);

                if (!fix) { failed++; failedLabels.push({ label: s.label, reason: 'No available room or time slot could be found for this date and campus.' }); continue; }

                try {
                    const result = await window.flexamApi.schedules.create({
                        course_id:    courseId,
                        course_code:  courseCode,
                        course_name:  courseName,
                        college,
                        exam_type:    examType,
                        semester:     semester || '1st Semester',
                        year_level:   yearLevel,
                        section:      section || '',
                        section_name: section || '',
                        class_section:section || '',
                        exam_date:    fix.date || examDate,
                        time_slot:    fix.slot,
                        duration,
                        room_id:      parseInt(fix.rid, 10) || fix.rid,
                        room_name:    fix.room.building ? fix.room.building + ', ' + fix.room.name : fix.room.name,
                        proctor_id:   proctorId || null,
                        proctor_name: proctorName || '',
                        campus,
                        status: 'Pending'
                    });
                    if (result && result.success) {
                        // Mark slot as taken in snapshot
                        liveSchedules.push({ id: result.data?.id || Math.random(), room_id: fix.rid, exam_date: fix.date || examDate, time_slot: fix.slot });
                        fixed++;
                    } else {
                        failed++;
                        failedLabels.push({ label: s.label, reason: (result && result.message) || 'Server rejected the record.' });
                    }
                } catch(e) { failed++; failedLabels.push({ label: s.label, reason: String(e.message || e) }); }
            }

            await refreshAllData();
            // ── Clear persisted conflicts — they've been fixed (or failed) ────
            // Only remove this campus's conflicts from shared localStorage.
            // Failed rows get canAutoResolve=false so they drop off the card count.
            const _myMc = (currentUser.campus || '').trim();
            const _keepConflicts = (window._importResolvableRows || []).map(r => {
                const _rc = (r.importRow && r.importRow.campus || '').trim();
                // Keep other-campus conflicts untouched
                if (_myMc && _rc && _rc !== _myMc) return r;
                // This campus's conflict — mark failed ones as non-fixable
                const lbl = r.label || '';
                const failInfo = failedLabels.find(f => (f.label || '') === lbl);
                if (failInfo) return { ...r, canAutoResolve: false, fixError: failInfo.reason || 'Could not be fixed.' };
                // Successfully fixed — exclude (return null, filtered below)
                return null;
            }).filter(Boolean);
            window._importResolvableRows = _keepConflicts;
            try { localStorage.setItem('flexam_pendingImportConflicts', JSON.stringify(_keepConflicts)); } catch(e) {}
            // ── Immediately refresh the Auto-Resolved card ──
            renderApp();

            if (failed === 0) {
                showToast(`&#10003; Auto-fixed ${fixed} import conflict${fixed !== 1 ? 's' : ''} — schedules created!`, 'success');
            } else {
                // Show a small results modal
                const m = document.createElement('div');
                m.className = 'fixed inset-0 flex items-center justify-center bg-black/60 z-[200] px-4';
                m.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 text-center">
                    <div class="w-14 h-14 ${fixed > 0 ? 'bg-purple-100' : 'bg-red-100'} rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 ${fixed > 0 ? 'text-purple-600' : 'text-red-500'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">Auto-Fix Complete</h3>
                    <p class="text-sm text-slate-500 mb-1">${fixed} fixed &nbsp;·&nbsp; <span class="text-red-500">${failed} could not be fixed</span></p>
                    ${failedLabels.length ? '<div class="text-left mt-3 space-y-2 max-h-60 overflow-y-auto pr-1">' + failedLabels.map(f => '<div class="bg-red-50 border border-red-100 rounded-lg px-3 py-2"><p class="text-xs font-semibold text-slate-700">' + (f.label || f) + '</p><p class="text-xs text-red-600 mt-0.5">' + (f.reason || 'Could not be fixed.') + '</p></div>').join('') + '</div>' : ''}
                    <button onclick="this.closest('.fixed').remove()" class="mt-5 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-sm transition">Done</button>
                </div>`;
                document.body.appendChild(m);
            }
        };

                // ── confirmImport ──────────────────────────────────────────────────────────
                window.confirmImport = async function(view) {
                    const rows = window._importParsedRows;
                    if (!rows || rows.length === 0) { showToast('No data to import', 'error'); return; }

                    // ── Import-lock: acquire before proceeding ───────────────────────────────
                    // NOTE: For rooms/courses/proctors/colleges, the PHP endpoint is the sole
                    // atomic gatekeeper (INSERT IGNORE on UNIQUE KEY). Doing a JS pre-acquire
                    // here would cause a dual-lock race where two users each set their own
                    // temp lock and both pass the same-user check. So we skip the JS
                    // pre-acquire for those views and let the PHP response handle blocking.
                    const _phpHandledViews = ['courses', 'proctors', 'colleges', 'rooms'];
                    const _lockHash = window._importFileHash;
                    if (_lockHash && !_phpHandledViews.includes(view)) {
                        const _lockRes = await safeFetch('../api/import_lock.php', {
                            method: 'POST',
                            body: JSON.stringify({ action: 'acquire', file_hash: _lockHash, import_type: view === 'proctors' ? 'proctors' : view === 'colleges' ? 'colleges' : view === 'courses' ? 'courses' : view === 'rooms' ? 'rooms' : view === 'schedule-mgmt' ? 'schedule' : view })
                        });
                        if (!_lockRes || !_lockRes.success) {
                            const _status = _lockRes?.status || '';
                            const _lockSecs = _lockRes?.data?.seconds_left ?? _lockRes?.seconds_left ?? 30;
                            const _toastMsg = _status === 'already_imported'
                                ? '⛔ This file has already been imported into the system.'
                                : `⏳ Another user is currently importing this file. Please try again in ${_lockSecs} second${_lockSecs !== 1 ? 's' : ''}.`;
                            // Keep modal open — show red banner inside AND toast at bottom
                            const _errElAcq = document.getElementById('importError');
                            if (_errElAcq) {
                                _errElAcq.textContent = _toastMsg;
                                _errElAcq.style.cssText = 'display:block;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
                                _errElAcq.classList.remove('hidden');
                            }
                            const _btn2 = document.getElementById('importConfirmBtn');
                            if (_btn2) { _btn2.disabled = true; _btn2.classList.add('opacity-50','cursor-not-allowed'); }
                            showToast(_toastMsg, 'error', 6000);
                            return;
                        }
                    }
                    // ─────────────────────────────────────────────────────────────────────────

                    document.dispatchEvent(new Event('flexam:importStart'));
                    const btn = document.getElementById('importConfirmBtn');
                    if (btn) { btn.disabled = true; btn.textContent = 'Importing...'; }

                    let success = 0, failed = 0;
                    const skippedRows = []; // track each skip with label + reason

                    // ── SCHEDULES ──────────────────────────────────────────────────────────
                    if (view === 'schedule-mgmt') {
                        // ── CRITICAL: snapshot allData BEFORE the loop so newly-created
                        //    schedules during this import don't pollute the duplicate check ──
                        const preImportScheduleData = allData.slice();
                        // Track section+college+examType combos added in THIS batch
                        const sessionSectionKeys = new Set();

                        for (const row of rows) {
                            try {
                                const courseCode = (row['course_co'] || row['course_code'] || '').trim();
                                if (!courseCode) {
                                    failed++;
                                    skippedRows.push({ label: 'Unknown row', reason: 'Missing course code — row skipped.', type: 'error' });
                                    continue;
                                }

                const program   = (row['program'] || '').trim();
                const examType  = (row['exam_typ'] || row['exam_type'] || 'Prelim').trim();
                const yearLevel = (row['year_level'] || row['year_leve'] || row._year_level_override || '1st Year').trim();
                const section   = (row['section'] || row['section_name'] || row['class_section'] || '').trim();
                const semester  = (row['semester'] || row['sem'] || '').trim();
                const duration  = (row['duration'] || '2 Hours').trim();
                const isOnlineCsv = ['1','yes','true','online'].includes((row['is_online'] || '').trim().toLowerCase()) ? 1 : 0;
                const rawRoom   = isOnlineCsv ? '' : (row['room_name'] || '').trim();

                // ── Parse exam_date: supports YYYY-MM-DD, "Month DD YYYY", DD/MM/YYYY, MM/DD/YYYY ──
                let examDate = (row._exam_date || row['exam_date'] || row['exam_dat'] || row['date_(yyyy-mm-dd)'] || row['date (yyyy-mm-dd)'] || row['date (month dd, yyyy)'] || row['date'] || '').trim();
                if (examDate && !examDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
                    const _mmap2 = {january:'01',february:'02',march:'03',april:'04',may:'05',june:'06',july:'07',august:'08',september:'09',october:'10',november:'11',december:'12',jan:'01',feb:'02',mar:'03',apr:'04',jun:'06',jul:'07',aug:'08',sep:'09',oct:'10',nov:'11',dec:'12'};
                    const _wm2 = examDate.match(/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/);
                    if (_wm2 && _mmap2[_wm2[1].toLowerCase()]) {
                        examDate = `${_wm2[3]}-${_mmap2[_wm2[1].toLowerCase()]}-${_wm2[2].padStart(2,'0')}`;
                    } else {
                        const parts = examDate.split('/');
                        if (parts.length === 3) {
                            const [a, b, c] = parts;
                            if (a.length === 4) {
                                examDate = `${a}-${b.padStart(2,'0')}-${c.padStart(2,'0')}`;
                            } else {
                                examDate = `${c}-${b.padStart(2,'0')}-${a.padStart(2,'0')}`;
                            }
                        }
                    }
                }
                // ── Parse time_slot ──
                const timeSlot = window._normalizeTimeSlot(row._time_slot || row['time_slot'] || '').trim();
                const proctorName = (row['proctor_name'] || '').trim();
                const campus    = (row['campus'] || '').trim() || currentUser.campus || '';
                const rowLabel  = `${courseCode}${examType ? ' · ' + examType : ''}${examDate ? ' · ' + examDate : ''}`;
                const roomShort = rawRoom.includes(',') ? rawRoom.split(',')[0].trim() : rawRoom;
                const buildingPart = rawRoom.includes(',') ? rawRoom.split(',').slice(1).join(',').trim() : 'Main Building';

                // ── BLOCK: Invalid room_name format ──────────────────────────────────────
                // Required format: "RoomNumber,BuildingCode" e.g. "501,VSB"
                // Must contain a comma, both parts non-empty, and NOT be an online exam row.
                if (rawRoom && !isOnlineCsv) {
                    const _roomFormatValid =
                        rawRoom.includes(',') &&
                        rawRoom.split(',')[0].trim() !== '' &&
                        rawRoom.split(',').slice(1).join(',').trim() !== '';

                    if (!_roomFormatValid) {
                        failed++;
                        skippedRows.push({
                            label:  rowLabel,
                            reason: `Invalid room format: "${rawRoom}". Use "RoomNumber,BuildingCode" format (e.g. "501,VSB"). Row skipped.`,
                            type:   'error'
                        });
                        continue;
                    }
                }

                // ── BLOCK: Invalid / unrecognized time slot (e.g. "00:00", bare 24-hour times) ──
                if (!timeSlot) {
                    const rawTs = (row._time_slot || row['time_slot'] || '').trim();
                    failed++;
                    skippedRows.push({
                        label:  rowLabel,
                        reason: `Invalid or missing time slot${rawTs ? ': "' + rawTs + '"' : ''}. Use 12-hour format, e.g. "07:00 AM - 08:00 AM". Schedule must be between 7:00 AM and 9:00 PM.`,
                        type:   'error'
                    });
                    continue;
                }

                // ── BLOCK: Past date / past time ─────────────────────────────────────
                // examDate is always YYYY-MM-DD here (normalised in processImportFile).
                // We use Date objects to avoid any string-comparison edge cases.
                if (examDate) {
                    const dp = examDate.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (dp) {
                        const examDObj      = new Date(+dp[1], +dp[2] - 1, +dp[3]);
                        const now           = new Date();
                        const todayMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());

                        if (examDObj < todayMidnight) {
                            failed++;
                            skippedRows.push({
                                label:  rowLabel,
                                reason: `Past date blocked: "${examDate}" is already in the past. Only future dates can be imported.`,
                                type:   'error'
                            });
                            continue;
                        }

                        if (examDObj.getTime() === todayMidnight.getTime() && timeSlot) {
                            // Today — check whether the slot's END time has already passed
                            const endPart  = timeSlot.split('-').pop().trim();
                            const em = endPart.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
                            if (em) {
                                let eh = +em[1], emm = +em[2];
                                const ep = em[3].toUpperCase();
                                if (ep === 'PM' && eh !== 12) eh += 12;
                                if (ep === 'AM' && eh === 12) eh  = 0;
                                if (now.getHours() * 60 + now.getMinutes() >= eh * 60 + emm) {
                                    failed++;
                                    skippedRows.push({
                                        label:  rowLabel,
                                        reason: `Past time blocked: The slot "${timeSlot}" on today (${examDate}) has already ended.`,
                                        type:   'error'
                                    });
                                    continue;
                                }
                            }
                        }
                    }
                }

                // ── Section duplicate check (runs FIRST — before room conflict check) ──
                // Blocks a row if section + college + exam_type + course + date already exists in the system
                // OR was already processed in this import batch.
                // Different course on a different date is ALLOWED — exams can span 3-4 days.
                // IMPORTANT: college must match exactly — same section name in a DIFFERENT
                // college is allowed and must NOT be blocked.
                const _earlyImportCollege = (row['college'] || row['college_name'] || '').trim().toLowerCase();
                const _earlyImportSection = section.toLowerCase();
                const _earlyImportCourse  = (row['course_code'] || row['course'] || '').trim().toLowerCase();
                const _earlyImportDate    = (row['exam_date'] || row['date'] || '').trim();
                const _earlyImportProgram = (row['program'] || row['programme'] || '').trim().toLowerCase();
                // Only run the check when both section AND college are present in the CSV row.
                // If college is missing we cannot safely determine scope, so we skip the block.
                if (_earlyImportSection && _earlyImportCollege) {
                    // Program is included so that same section+college with a DIFFERENT program
                    // (e.g. BSIT vs BSCS) is never treated as a duplicate.
                    const _sectionBatchKey = `${_earlyImportSection}|${_earlyImportCollege}|${_earlyImportProgram}|${examType.toLowerCase()}|${_earlyImportCourse}|${_earlyImportDate}`;

                    // Check 1: duplicate within this import batch (same college required via key)
                    if (sessionSectionKeys.has(_sectionBatchKey)) {
                        failed++;
                        skippedRows.push({
                            label:  rowLabel,
                            reason: `Duplicate in this import: Section "${_earlyImportSection.toUpperCase()}" under "${_earlyImportCollege.toUpperCase()}" for a ${examType} exam was already processed in this batch.`,
                            type:   'duplicate'
                        });
                        continue;
                    }

                    // Check 2: duplicate against pre-import snapshot (not live allData).
                    // Rule: same section + same college + same exam_type = duplicate.
                    // A section with the same name but a DIFFERENT college is NOT a duplicate.
                    // College is resolved with the same fallback chain used for display:
                    // d.college → linked course.college → course.program
                    // This handles schedules where d.college is empty in the DB.
                    const _resolveScheduleCollege = (d) => {
                        const direct = (d.college || '').trim().toLowerCase();
                        if (direct) return direct;
                        // fallback: find linked course
                        const cid = d.course_id ? dbId(String(d.course_id)) : null;
                        const ccode = (d.course_code || '').trim().toLowerCase();
                        const linkedCourse = preImportScheduleData.find(c =>
                            c.type === 'course' && (
                                (cid && dbId(String(c.id)) === cid) ||
                                (ccode && (c.course_code || '').toLowerCase() === ccode)
                            )
                        );
                        if (linkedCourse) {
                            const cc = (linkedCourse.college || '').trim().toLowerCase();
                            if (cc) return cc;
                            return (linkedCourse.program || '').trim().toLowerCase();
                        }
                        return '';
                    };
                    const _sectionExistsInDb = preImportScheduleData.find(d =>
                        d.type === 'schedule' &&
                        (d.exam_type || '').toLowerCase() === examType.toLowerCase() &&
                        (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === _earlyImportSection &&
                        _resolveScheduleCollege(d) === _earlyImportCollege &&
                        (_earlyImportProgram ? (d.program || '').trim().toLowerCase() === _earlyImportProgram : true) &&
                        (_earlyImportCourse ? (d.course_code || '').trim().toLowerCase() === _earlyImportCourse : true) &&
                        (_earlyImportDate   ? (d.exam_date || d.date || '').trim() === _earlyImportDate : true)
                    );
                    if (_sectionExistsInDb) {
                        failed++;
                        const _exDate   = (_sectionExistsInDb.exam_date || _sectionExistsInDb.date || '').trim();
                        const _exRoom   = (_sectionExistsInDb.room_name || '').trim();
                        const _exCourse = (_sectionExistsInDb.course_code || '').trim().toUpperCase();
                        skippedRows.push({
                            label:  rowLabel,
                            reason: `Duplicate: Section "${_earlyImportSection.toUpperCase()}" under "${_earlyImportCollege.toUpperCase()}" already has a ${examType} exam for course ${_exCourse}${_exDate ? ' on ' + _exDate : ''}${_exRoom ? ' in ' + _exRoom : ''}. Same course on the same date cannot be scheduled twice.`,
                            type:   'duplicate'
                        });
                        continue;
                    }
                }

                // ── Room booking conflict check ─────────────────────────
                // Block if the same room is already booked on the same date+timeslot (any course)
                const _preRoom = rawRoom ? allData.find(d =>
                    d.type === 'room' &&
                    (d.name || '').toLowerCase() === (rawRoom.includes(',') ? rawRoom.split(',')[0].trim() : rawRoom).toLowerCase() &&
                    (d.campus || '').toLowerCase().trim() === (campus || '').toLowerCase().trim()
                ) : null;
                if (_preRoom && examDate && timeSlot) {
                    const _preRid = dbId(String(_preRoom.id));
                    const _roomBooked = allData.find(d =>
                        d.type === 'schedule' &&
                        dbId(String(d.room_id || d.room || '')) === _preRid &&
                        (d.exam_date || d.date || '').trim() === examDate &&
                        _arOverlap(d.time_slot, timeSlot)
                    );
                    if (_roomBooked) {
                        failed++;
                        skippedRows.push({
                            label: rowLabel,
                            reason: `Room conflict: "${_preRoom.name}" is already booked on ${examDate} at ${timeSlot} by "${_roomBooked.course_code || _roomBooked.course_name || 'another exam'}". A free room & time slot will be assigned automatically.`,
                            type: 'error',
                            canAutoResolve: true,
                            importRow: {
                                courseCode, examType, yearLevel, section, duration,
                                examDate, campus,
                                semester:    semester || '1st Semester',
                                courseId:    null,
                                courseName:  courseCode,
                                college:     (row['college'] || row['college_name'] || '').trim() || (row['program'] || ''),
                                roomId:      parseInt(_preRid, 10) || null,
                                roomName:    rawRoom || roomShort,
                                proctorId:   null,
                                proctorName: proctorName,
                            }
                        });
                        continue;
                    }
                }

                // ── Duplicate check ─────────────────────────────────────
                // Only a true duplicate if SAME course + type + date + campus + time_slot
                // All 5 must match — different campus, date, or time = allowed
                const importProgram = (row['program'] || row['programme'] || '').trim().toLowerCase();
                    const importSection = section.toLowerCase();

                    const dupSchedMatch = examDate && timeSlot && allData.find(d => {
                        const dProgram  = (d.program || d.college || '').toLowerCase();
                        const dSection  = (d.section || d.section_name || d.class_section || '').toLowerCase();
                        const dCode     = (d.course_code || d.course_name || '').toLowerCase();
                        const dType     = (d.exam_type || '').trim();
                        const dDate     = (d.exam_date || d.date || '').trim();
                        const dCampus   = (d.campus || '').trim();
                        const dTimeSlot = (d.time_slot || '').trim();

                        const sameCode    = dCode     === courseCode.toLowerCase();
                        const sameType    = dType     === examType;
                        const sameDate    = dDate     === examDate;
                        const sameCampus  = dCampus   === campus;
                        const sameTime    = dTimeSlot === timeSlot;
                        const sameProgram = !importProgram || !dProgram || dProgram === importProgram;
                        const sameSection = !importSection || !dSection || dSection === importSection;

                        return d.type === 'schedule' &&
                            sameCode && sameType && sameDate && sameCampus && sameTime &&
                            sameProgram && sameSection;
                    });

                    if (dupSchedMatch) {
                        // Same course+type+date+campus+timeslot — check if room also matches
                        const dupRoomId   = String(dupSchedMatch.room_id || '');
                        // Quick inline room lookup (room var not resolved yet at this point)
                        const _earlyRoom  = rawRoom ? allData.find(d =>
                            d.type === 'room' &&
                            (d.name || '').toLowerCase() === (rawRoom.includes(',') ? rawRoom.split(',')[0].trim() : rawRoom).toLowerCase() &&
                            (d.campus || '').toLowerCase() === (campus || '').toLowerCase()
                        ) : null;
                        const thisRoomId  = _earlyRoom ? String(dbId(String(_earlyRoom.id))) : null;
                        const sameRoom    = thisRoomId && dupRoomId && thisRoomId === dupRoomId;

                        if (sameRoom) {
                            // Exact duplicate — block it
                            failed++;
                            skippedRows.push({
                                label:  rowLabel,
                                reason: `Duplicate: A ${examType} exam for "${courseCode}"${importProgram ? ' (' + importProgram.toUpperCase() + ')' : ''} already exists on ${examDate} at ${timeSlot}${campus ? ' in ' + campus : ''}${importSection ? ', section ' + importSection : ''}.`,
                                type:   'duplicate'
                            });
                        } else {
                            // Same slot, different room — room conflict, auto-fixable
                            failed++;
                            const _earlyProctor = proctorName ? allData.find(d => d.type === 'proctor' && (d.name || '').toLowerCase() === proctorName.toLowerCase()) : null;
                            const _earlyCourse  = allData.find(d => d.type === 'course' && (d.course_code || '').toLowerCase() === courseCode.toLowerCase() && (!campus || !d.campus || d.campus.toLowerCase() === campus.toLowerCase()));
                            skippedRows.push({
                                label:  rowLabel,
                                reason: `Room conflict: A ${examType} exam for "${courseCode}" on ${examDate} at ${timeSlot} is already booked in a different room. A free room & time slot will be assigned automatically.`,
                                type:   'error',
                                canAutoResolve: true,
                                importRow: {
                                    courseCode, examType, yearLevel, section, duration,
                                    examDate, campus,
                                    semester:    semester || '1st Semester',
                                    courseId:    _earlyCourse  ? dbId(String(_earlyCourse.id))  : null,
                                    courseName:  _earlyCourse  ? (_earlyCourse.course_name || courseCode) : courseCode,
                                    college:     _earlyCourse  ? (_earlyCourse.college || _earlyCourse.program || '') : (row['program'] || ''),
                                    roomId:      _earlyRoom    ? dbId(String(_earlyRoom.id))    : null,
                                    roomName:    rawRoom || roomShort,
                                    proctorId:   _earlyProctor ? dbId(String(_earlyProctor.id)) : null,
                                    proctorName: _earlyProctor ? _earlyProctor.name             : proctorName,
                                }
                            });
                        }
                        continue;
                    }

                // ── Find or create course ────────────────────────────────
                let course = allData.find(d =>
                d.type === 'course' &&
                (d.course_code || '').toLowerCase() === courseCode.toLowerCase() &&
                (!campus || !d.campus || d.campus.toLowerCase() === campus.toLowerCase())
            );
            if (!course) {
                const rawProgram    = (row['program']    || row['programme']    || '').trim();
                const rawCollege    = (row['college']    || row['college_name'] || rawProgram || '').trim();
                const rawCourseName = (row['course_name'] || row['course_na']   || '').trim();
                const rawSemester   = (row['semester']   || row['sem']          || '1st Semester').trim();

            // college is required by API — use a placeholder if missing so course gets created
                const collegeToSave = rawCollege || rawProgram || '';

                const cRes = await window.flexamApi.courses.create({
                course_code: courseCode,
                course_name: rawCourseName || courseCode,
                college:     collegeToSave,
                program:     rawProgram   || '',
                year_level:  yearLevel    || '1st Year',
                semester:    rawSemester  || '1st Semester',
                campus:      campus       || ''
            });
            if (cRes.success && cRes.data) {
                course = { ...cRes.data, type: 'course' };
                allData.push(course);
                console.log('Auto-created course:', courseCode, 'at campus:', campus);
        } else {
        console.warn('Auto-create course failed:', cRes.message, row);
    }
}

            let room = null;
                const roomNameForCreate = roomShort || `Room-${courseCode}`;
                const buildingForCreate = buildingPart || 'Main Building';
                // Look for existing room: same name + same campus + same building (exact match)
                room = allData.find(d =>
                    d.type === 'room' &&
                    (d.name || '').toLowerCase() === roomNameForCreate.toLowerCase() &&
                    (d.campus || '').toLowerCase() === (campus || '').toLowerCase() &&
                    (!d.building || (d.building || '').toLowerCase() === buildingForCreate.toLowerCase())
                );
                if (!room) {
                    const rRes = await window.flexamApi.rooms.create({
                        name: roomNameForCreate,
                        building: buildingForCreate,
                        capacity: 40,
                        floor: '',
                        campus: campus || '',
                        locked: 0
                    });
                    if (rRes.success && rRes.data) {
                        room = { ...rRes.data, type: 'room' };
                        allData.push(room);
                    } else {
                        room = allData.find(d =>
                            d.type === 'room' &&
                            (d.name || '').toLowerCase() === roomNameForCreate.toLowerCase()
                        );
                        if (!room) {
                            failed++;
                            skippedRows.push({
                                label:  rowLabel,
                                reason: `Failed to create room "${roomNameForCreate}": ${rRes.message || 'Unknown error'}`,
                                type:   'error'
                            });
                            continue;
                        }
                    }
                }
                if (room.locked) {
                    skippedRows.push({
                        label:  rowLabel,
                        reason: `Warning: Room "${room.name}" is currently blocked. Schedule created but room may be unavailable.`,
                        type:   'duplicate'
                    });
                }

                // ── Find proctor ─────────────────────────────────────────
                // ── Find proctor ─────────────────────────────────────────
                let proctor = null;
                if (proctorName) {
                    proctor = allData.find(d =>
                        d.type === 'proctor' &&
                        (d.name || '').toLowerCase() === proctorName.toLowerCase()
                    );
                }


                // ── Create schedule ──────────────────────────────────────
                const schedRes = await window.flexamApi.schedules.create({
                    course_id:    course  ? dbId(String(course.id))  : null,
                    course_code:  courseCode,
                    course_name:  course  ? (course.course_name || courseCode) : courseCode,
                    college:      (row['college'] || row['college_name'] || '').trim() || (course ? (course.college || course.program || '') : (row['program'] || '')),
                    program:      program || (course ? (course.program || '') : ''),
                    exam_type:    examType,
                    semester:     semester || (course ? (course.semester || '') : '') || '1st Semester',
                    year_level:   yearLevel,
                    section:       section || '',
                    section_name:  section || '',
                    class_section: section || '',
                    exam_date:    examDate || null,
                    time_slot:    timeSlot,
                    duration:     duration,
                    room_id:      isOnlineCsv ? null : (room ? dbId(String(room.id)) : null),
                    room_name:    isOnlineCsv ? '' : (rawRoom || roomShort),
                    proctor_id:   proctor ? dbId(String(proctor.id)) : null,
                    proctor_name: proctor ? proctor.name              : proctorName,
                    campus:       campus,
                    status:       'Pending',
                    is_online:    isOnlineCsv,
                    file_hash:    window._importFileHash || null
                });

                if (schedRes.success) {
                    success++;
                    // Mark this section+college+examType+course+date as used in this batch
                    // Use same college resolution as the early check (no program fallback)
                    // so the batch key is consistent between check and add.
                    const importCollege2   = (row['college'] || row['college_name'] || '').trim().toLowerCase();
                    const importCourse2    = (row['course_code'] || row['course'] || '').trim().toLowerCase();
                    const importDate2      = (row['exam_date'] || row['date'] || '').trim();
                    const importProgram2   = (row['program'] || row['programme'] || '').trim().toLowerCase();
                    if (importSection && importCollege2) {
                        sessionSectionKeys.add(`${importSection}|${importCollege2}|${importProgram2}|${examType.toLowerCase()}|${importCourse2}|${importDate2}`);
                    }
                } else {
                    failed++;
                    const isBookingConflict = (schedRes.message || '').toLowerCase().includes('booked') ||
                        (schedRes.message || '').toLowerCase().includes('time slot') ||
                        (schedRes.message || '').toLowerCase().includes('conflict') ||
                        (schedRes.message || '').toLowerCase().includes('occupied');
                    skippedRows.push({
                        label:   rowLabel,
                        reason:  schedRes.message || 'Server rejected the record.',
                        type:    'error',
                        canAutoResolve: isBookingConflict,
                        importRow: isBookingConflict ? {
                            courseCode, examType, yearLevel, section, duration,
                            examDate, campus,
                            semester:    semester || (course ? (course.semester || '') : '') || '1st Semester',
                            courseId:    course  ? dbId(String(course.id))  : null,
                            courseName:  course  ? (course.course_name || courseCode) : courseCode,
                            college:     (row['college'] || row['college_name'] || '').trim() || (course ? (course.college || course.program || '') : (row['program'] || '')),
                            roomId:      room    ? dbId(String(room.id))    : null,
                            roomName:    rawRoom || roomShort,
                            proctorId:   proctor ? dbId(String(proctor.id)) : null,
                            proctorName: proctor ? proctor.name             : proctorName,
                        } : null
                    });
                }

            } catch (err) {
                console.error('Schedule import row error:', err, row);
                failed++;
                skippedRows.push({
                    label:  (row['course_co'] || row['course_code'] || 'Unknown row'),
                    reason: String(err.message || err),
                    type:   'error'
                });
            }
        }

                // ── COLLEGES ───────────────────────────────────────────────────────────
                } else if (view === 'colleges') {
                let lastCollegeName = '';
                
                // ── CRITICAL: snapshot allData BEFORE the loop so newly-created colleges
                //    during this import don't pollute the duplicate check ──────────────
                const preImportData = allData.slice();
                
                // Track programs added in THIS batch: "collegeName|campus" → Set of acronyms
                const sessionPrograms = {};

    for (const row of rows) {
        try {
            if (row.name && row.name.trim()) lastCollegeName = row.name.trim();
            const collegeName   = lastCollegeName;
            const currentCampus = (row.campus   || '').trim();
            const program       = (row.programs || '').trim();
            const rowLabel      = `${collegeName}${program ? ' · ' + program : ''}`;

            if (!collegeName || !program) {
                failed++;
                skippedRows.push({ label: rowLabel || 'Empty row', reason: 'Missing college name or program.', type: 'error' });
                continue;
            }

            // Extract acronym from "ACRONYM=Full Name" or plain "ACRONYM"
            const importAcronym = (program.includes('=')
                ? program.split('=')[0].trim()
                : program.trim()
            ).toLowerCase();

            const sessionKey = `${collegeName.toLowerCase()}|${currentCampus.toLowerCase()}`;
            if (!sessionPrograms[sessionKey]) sessionPrograms[sessionKey] = new Set();

            // Check 1: duplicate WITHIN this import batch
            if (sessionPrograms[sessionKey].has(importAcronym)) {
                failed++;
                skippedRows.push({
                    label:  rowLabel,
                    reason: `Duplicate in this import: "${importAcronym.toUpperCase()}" for "${collegeName}" (${currentCampus}) was already processed in this batch.`,
                    type:   'duplicate'
                });
                continue;
            }

            // Check 2: duplicate against PRE-IMPORT snapshot only (not live allData)
            const existsInDb = preImportData.some(d =>
                d.type === 'college' &&
                (d.name   || '').toLowerCase() === collegeName.toLowerCase() &&
                (d.campus || '').toLowerCase() === currentCampus.toLowerCase() &&
                Array.isArray(d.programs) &&
                d.programs.some(p => {
                    const a = (p.includes('=') ? p.split('=')[0].trim() : p.trim()).toLowerCase();
                    return a === importAcronym;
                })
            );

            if (existsInDb) {
                failed++;
                skippedRows.push({
                    label:  rowLabel,
                    reason: `Already exists: Program "${importAcronym.toUpperCase()}" is already under "${collegeName}" in the ${currentCampus} campus.`,
                    type:   'duplicate'
                });
                continue;
            }

            // All clear — create it
            const result = await window.flexamApi.colleges.create({
                name:        collegeName,
                code:        (row.code || '').trim().toUpperCase() || importAcronym.substring(0, 5).toUpperCase(),
                description: row.description || '',
                programs:    [program],
                campus:      currentCampus
            });

            if (result && result.success) {
                success++;
                sessionPrograms[sessionKey].add(importAcronym); // mark done in this batch
                // Do NOT push to allData here — that's what caused the false positives
            } else {
                failed++;
                skippedRows.push({
                    label:  rowLabel,
                    reason: (result && result.message) || 'Failed to save.',
                    type:   'error'
                });
            }
        } catch (err) {
            failed++;
            skippedRows.push({
                label:  (row.name || row.programs || 'Row'),
                reason: String(err.message || err),
                type:   'error'
            });
        }
    }

    // ── ALL OTHER VIEWS ─────────────────────────────────────────────────────
    } else {
        // ── courses, rooms, colleges, proctors → dedicated PHP bulk importers ──
        // Same lock flow as schedule: PHP handles acquire + permanent release,
        // so the already_imported toast works identically for all import types.
        if (view === 'courses' || view === 'rooms' || view === 'colleges' || view === 'proctors') {
            const _file = window._importSelectedFile || document.getElementById('importFileInput')?.files?.[0];
            if (!_file) { showToast('No file selected.', 'error'); return; }
            const _endpoint = view === 'courses'  ? '../api/import_courses.php'
                            : view === 'rooms'    ? '../api/import_rooms.php'
                            : view === 'colleges' ? '../api/import_colleges.php'
                            :                       '../api/import_proctors.php';
            const _fd = new FormData();
            _fd.append('file', _file);
            let _bulkRes;
            try {
                const _r = await fetch(_endpoint, { method: 'POST', body: _fd });
                _bulkRes = await _r.json();
            } catch(e) {
                showToast('Network error during import: ' + e.message, 'error');
                return;
            }
            if (!_bulkRes.success) {
                const _st  = _bulkRes.status ?? _bulkRes.data?.status ?? '';
                const _msg = _bulkRes.message ?? '';
                const _secs = _bulkRes.data?.seconds_left ?? _bulkRes.seconds_left ?? 30;
                if (_st === 'already_imported') {
                    const _alreadyMsg = '⛔ This file has already been imported into the system.';
                    // Keep modal open — show red banner inside AND toast at bottom (matches schedule behavior)
                    const _errEl2 = document.getElementById('importError');
                    if (_errEl2) {
                        _errEl2.textContent = _alreadyMsg;
                        _errEl2.style.cssText = 'display:block;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
                        _errEl2.classList.remove('hidden');
                    }
                    // Disable the Import button so they cannot click again
                    const _btnAlready = document.getElementById('importConfirmBtn');
                    if (_btnAlready) { _btnAlready.disabled = true; _btnAlready.classList.add('opacity-50','cursor-not-allowed'); }
                    // Resume poller and refresh so winner's data appears in the table
                    document.dispatchEvent(new Event('flexam:importEnd'));
                    await refreshAllData();
                    showToast(_alreadyMsg, 'error', 6000);
                } else if (_st === 'locked') {
                    const _lockedMsg = `⏳ Another user is currently importing this file. Please try again in ${_secs} second${_secs !== 1 ? 's' : ''}.`;
                    showToast(_lockedMsg, 'error');
                    const _errEl3 = document.getElementById('importError');
                    if (_errEl3) {
                        _errEl3.style.cssText = 'display:block;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
                        _errEl3.classList.remove('hidden');
                    }
                    // Resume the real-time sync poller so table updates while countdown runs
                    document.dispatchEvent(new Event('flexam:importEnd'));
                    // Re-enable button with live countdown so user knows when to retry
                    const _btnRetry = document.getElementById('importConfirmBtn');
                    let _countdown = _secs;
                    const _tick = () => {
                        if (_errEl3) _errEl3.textContent = `⏳ Another user is importing this file. Retry in ${_countdown}s…`;
                        if (_btnRetry) { _btnRetry.disabled = true; _btnRetry.textContent = `Wait ${_countdown}s…`; _btnRetry.classList.add('opacity-50','cursor-not-allowed'); }
                        if (_countdown <= 0) {
                            if (_errEl3) { _errEl3.textContent = ''; _errEl3.classList.add('hidden'); }
                            if (_btnRetry) { _btnRetry.disabled = false; _btnRetry.textContent = 'Import'; _btnRetry.classList.remove('opacity-50','cursor-not-allowed'); }
                            return;
                        }
                        _countdown--;
                        setTimeout(_tick, 1000);
                    };
                    _tick();
                } else {
                    showToast(_msg || 'Import failed.', 'error');
                    document.getElementById('importModal')?.remove();
                    window._importParsedRows = [];
                    window._importFileHash = null;
                    document.dispatchEvent(new Event('flexam:importEnd'));
                    await refreshAllData();
                }
                return;
            }
            // PHP wraps payload inside `data` — read from there, fall back to root for safety
            const _bulkData = _bulkRes.data ?? _bulkRes;
            // colleges returns inserted_colleges + inserted_programs, others return inserted
            success = (_bulkData.inserted ?? 0) + (_bulkData.inserted_colleges ?? 0) + (_bulkData.inserted_programs ?? 0);
            failed  = _bulkData.skipped ?? 0;
            (_bulkData.errors || []).forEach(e => skippedRows.push({ label: e, reason: e, type: 'error' }));

            // ── Race-condition guard for colleges: if PHP returned success=true but
            //    nothing was actually inserted (all rows were duplicates because the
            //    winner already committed them), treat it as already_imported. ────────
            if (view === 'colleges' && success === 0 && failed > 0) {
                const _alreadyMsg = '⛔ This file has already been imported into the system.';
                const _errElRace = document.getElementById('importError');
                if (_errElRace) {
                    _errElRace.textContent = _alreadyMsg;
                    _errElRace.style.cssText = 'display:block;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
                    _errElRace.classList.remove('hidden');
                }
                const _btnRace = document.getElementById('importConfirmBtn');
                if (_btnRace) { _btnRace.disabled = true; _btnRace.classList.add('opacity-50','cursor-not-allowed'); }
                window._importFileHash = null;
                document.dispatchEvent(new Event('flexam:importEnd'));
                await refreshAllData();
                showToast(_alreadyMsg, 'error', 6000);
                return;
            }

            // PHP endpoint handled the lock — clear JS-side hash to prevent double-release
            window._importFileHash = null;
            window._importSelectedFile = null;

            document.getElementById('importModal')?.remove();
            window._importParsedRows = [];
            lastImportLog = failed > 0 ? { success, failed, skippedRows, timestamp: new Date(), view: currentView } : null;
            activityLog('Import Completed', `${success} added, ${failed} skipped · View: ${currentView}`);
            document.dispatchEvent(new Event('flexam:importEnd'));
            await refreshAllData();
            // Show immediate toast so user gets feedback right away
            if (success > 0) {
                showToast(`${success} record${success !== 1 ? 's' : ''} imported successfully!`, 'success');
            } else if (failed > 0) {
                showToast(`Import complete — all ${failed} row${failed !== 1 ? 's' : ''} were skipped.`, 'error');
            } else {
                showToast('Import complete — no records were added.', 'error');
            }
            showImportResults(success, failed, skippedRows);
            return;
        }

        const apiMap = {
            'feedbacks': (_row) => Promise.resolve({ success: false, message: 'Feedbacks cannot be imported.' })
        };

        const importFn = apiMap[view];
        if (!importFn) { showToast('Import not supported for this view', 'error'); return; }

        for (const row of rows) {
            const rowLabel = Object.values(row).filter(Boolean).join(' · ').substring(0, 60);
            try {
                const result = await importFn(row);
                if (result && result.success) {
                    success++;
                } else {
                    failed++;
                    skippedRows.push({
                        label:  rowLabel,
                        reason: (result && result.message) || 'Failed to save record.',
                        type:   'error'
                    });
                }
            } catch (err) {
                failed++;
                skippedRows.push({ label: rowLabel, reason: String(err.message || err), type: 'error' });
            }
        }
    }

   document.getElementById('importModal')?.remove();
window._importParsedRows = [];
window._importSelectedFile = null;
// ── Import-lock: release after import finishes ───────────────────────────
if (window._importFileHash) {
    await safeFetch('../api/import_lock.php', {
        method: 'POST',
        body: JSON.stringify({
            action:      'release',
            file_hash:   window._importFileHash,
            permanent:   success > 0,
            import_type: currentView === 'proctors' ? 'proctors' : currentView === 'colleges' ? 'colleges' : currentView === 'courses' ? 'courses' : currentView === 'rooms' ? 'rooms' : currentView === 'schedule-mgmt' ? 'schedule' : currentView
        })
    }).catch(() => {});
    window._importFileHash = null;
}
// ─────────────────────────────────────────────────────────────────────────
// Set the log BEFORE refreshAllData so renderApp() sees it
if (failed > 0) {
    lastImportLog = { success, failed, skippedRows, timestamp: new Date(), view: currentView };
} else {
    lastImportLog = null;
}
activityLog('Import Completed', `${success} added, ${failed} skipped · View: ${currentView}`);
document.dispatchEvent(new Event('flexam:importEnd'));
await refreshAllData();
showImportResults(success, failed, skippedRows);
};

        // ── Buildings Modal ───────────────────────────────────────────────────
        window.openBuildingsModal = function() {
            const buildingDetails = window._buildingDetails || {};
            const campusLabel     = window._buildingCampus  || 'All Campuses';

            const existing = document.getElementById('buildingsModal');
            if (existing) existing.remove();

            const allBuildings = Object.keys(buildingDetails).sort();
            const usedCount    = allBuildings.filter(b => buildingDetails[b].used > 0).length;

            function buildRows(q) {
                const list = q
                    ? allBuildings.filter(b => b.toLowerCase().includes(q.toLowerCase()))
                    : allBuildings;
                if (!list.length) return '<tr><td colspan="4" class="py-10 text-center text-sm text-slate-400">No buildings match your search.</td></tr>';

                const bookedIds = new Set(
                    allData.filter(d => d.type === 'schedule')
                           .map(d => String(d.room_id || d.room || ''))
                           .filter(Boolean)
                );

                return list.map(b => {
                    const { total, used } = buildingDetails[b];
                    const pct    = total > 0 ? Math.round((used / total) * 100) : 0;
                    const barCls = pct >= 70 ? 'bg-indigo-500' : pct >= 40 ? 'bg-violet-400' : pct > 0 ? 'bg-orange-400' : 'bg-slate-300';
                    const dot    = used > 0  ? 'bg-indigo-500' : 'bg-slate-300';
                    const badge  = used > 0  ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-slate-100 text-slate-500 border-slate-200';
                    const pctCls = pct >= 70 ? 'text-indigo-600' : pct >= 40 ? 'text-violet-600' : pct > 0 ? 'text-orange-500' : 'text-slate-400';

                    const rooms = allData.filter(d => d.type === 'room' && (d.building || '').trim() === b);
                    const roomChips = rooms.length
                        ? rooms.map(r => {
                            const inUse   = bookedIds.has(String(r.id));
                            const chipCls = inUse ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-slate-50 text-slate-500 border-slate-200';
                            const chipDot = inUse ? 'bg-indigo-500' : 'bg-slate-300';
                            const lock    = r.locked ? ' 🔒' : '';
                            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium border ${chipCls}"><span class="w-1.5 h-1.5 rounded-full ${chipDot}"></span>${r.name}${lock}${r.campus ? '<span class="opacity-50 ml-0.5"> · ' + r.campus + '</span>' : ''}</span>`;
                          }).join('')
                        : '<span class="text-[10px] text-slate-300 italic">No rooms on record</span>';

                    return `<tr class="border-b border-slate-100 hover:bg-slate-50/60 transition align-top">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 ${dot}"></span>
                                <span class="text-sm font-semibold text-slate-800">${b}</span>
                            </div>
                            <div class="flex flex-wrap gap-1 mt-1.5 ml-5">${roomChips}</div>
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border ${badge}">${used}/${total}</span>
                        </td>
                        <td class="px-5 py-3.5 text-center whitespace-nowrap">
                            <span class="text-sm font-bold ${pctCls}">${pct}%</span>
                        </td>
                        <td class="px-5 py-3.5 w-32">
                            <div class="w-full h-2 bg-slate-100 rounded-full">
                                <div class="h-full ${barCls} rounded-full transition-all" style="width:${pct}%"></div>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            }

            const modal = document.createElement('div');
            modal.id = 'buildingsModal';
            modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-[999] p-4';
            modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden" style="animation:slideUp .22s ease">
                <div class="flex justify-between items-center px-6 py-5 bg-indigo-600 text-white shrink-0">
                    <div>
                        <h2 class="text-lg font-bold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            Buildings Overview
                        </h2>
                        <p class="text-xs text-indigo-200 mt-0.5">${campusLabel} &middot; ${usedCount} of ${allBuildings.length} buildings in use</p>
                    </div>
                    <button onclick="document.getElementById('buildingsModal').remove()" class="text-white/70 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex items-center gap-3 px-6 py-3 bg-indigo-50 border-b border-indigo-100 shrink-0 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white text-indigo-700 border border-indigo-200 shadow-sm"><span class="w-2 h-2 rounded-full bg-indigo-500"></span>${usedCount} In Use</span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white text-slate-500 border border-slate-200 shadow-sm"><span class="w-2 h-2 rounded-full bg-slate-300"></span>${allBuildings.length - usedCount} Idle</span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white text-slate-600 border border-slate-200 shadow-sm">🏢 ${allBuildings.length} Total</span>
                </div>
                <div class="px-6 py-3 border-b border-slate-100 shrink-0">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                        <input id="bmSearch" type="text" placeholder="Search buildings..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                </div>
                <div class="overflow-y-auto flex-1">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Building &amp; Rooms</th>
                                <th class="px-5 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-widest">Used</th>
                                <th class="px-5 py-3 text-center text-[11px] font-bold text-slate-500 uppercase tracking-widest">%</th>
                                <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest w-32">Bar</th>
                            </tr>
                        </thead>
                        <tbody id="bmBody">${buildRows('')}</tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 flex justify-between items-center shrink-0 bg-slate-50/50">
                    <p class="text-xs text-slate-400 flex items-center gap-3">
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-indigo-500"></span>In use</span>
                        <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-300"></span>Idle</span>
                    </p>
                    <button onclick="document.getElementById('buildingsModal').remove()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition">Close</button>
                </div>
            </div>`;

            document.body.appendChild(modal); setTimeout(lockCampusSelects, 0);
            document.getElementById('bmSearch').addEventListener('input', e => {
                document.getElementById('bmBody').innerHTML = buildRows(e.target.value);
            });
            modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
        };
    </script>

    <!-- Fixed Pagination Bar -->
    <div id="global-pager" class="hidden fixed bottom-0 left-0 lg:left-64 right-0 z-30 bg-white border-t border-slate-200 shadow-lg px-6 py-2.5 flex items-center justify-between">
        <span id="global-pager-info" class="text-xs text-slate-400 font-medium"></span>
        <div id="global-pager-controls" class="flex items-center gap-1"></div>
    </div>

<!-- ═══ Year Level Manager Modal ═══ -->
<div id="yearLevelManagerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-800">Manage Year Levels</h2>
            <button onclick="closeYearLevelManager()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4">
            <p class="text-xs text-slate-500 mb-4">These year levels appear in all dropdowns across the system.</p>
            <div id="ylmList" class="space-y-2 mb-4 max-h-64 overflow-y-auto"></div>
            <div class="flex gap-2">
                <input id="ylmInput" type="text" placeholder="e.g. 5th Year"
                    class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                    onkeydown="if(event.key==='Enter') addYearLevelFromManager()" />
                <button onclick="addYearLevelFromManager()"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition">
                    + Add
                </button>
            </div>
            <p id="ylmError" class="hidden text-xs text-red-500 mt-2"></p>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 text-xs text-slate-400">
            Changes take effect immediately in all forms.
        </div>
    </div>
</div>
<script>
function openYearLevelManager() {
    renderYLMList();
    const modal = document.getElementById('yearLevelManagerModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => document.getElementById('ylmInput').focus(), 100);
}
function closeYearLevelManager() {
    const modal = document.getElementById('yearLevelManagerModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function renderYLMList() {
    const list = document.getElementById('ylmList');
    if (!YEAR_LEVELS.length) {
        list.innerHTML = '<p class="text-xs text-slate-400 text-center py-2">No year levels defined.</p>';
        return;
    }
    list.innerHTML = YEAR_LEVELS.map((y, i) => `
        <div class="flex items-center gap-2">
            <span class="flex-1 px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-sm text-slate-700">${y}</span>
            <button onclick="removeYearLevelFromManager(${i})"
                class="text-red-400 hover:text-red-600 hover:bg-red-50 border border-red-100 text-xs px-2 py-1 rounded-lg transition"
                ${YEAR_LEVELS.length <= 1 ? 'disabled title="Must keep at least one"' : ''}>
                Remove
            </button>
        </div>
    `).join('');
}
function addYearLevelFromManager() {
    const input = document.getElementById('ylmInput');
    const err = document.getElementById('ylmError');
    const val = input.value.trim();
    if (!val) { showYLMError('Please enter a year level name.'); return; }
    if (YEAR_LEVELS.includes(val)) { showYLMError('"' + val + '" already exists.'); return; }
    YEAR_LEVELS.push(val);
    saveYearLevels();
    input.value = '';
    err.classList.add('hidden');
    renderYLMList();
}
function removeYearLevelFromManager(index) {
    if (YEAR_LEVELS.length <= 1) { showYLMError('Must keep at least one year level.'); return; }
    YEAR_LEVELS.splice(index, 1);
    saveYearLevels();
    renderYLMList();
}
function showYLMError(msg) {
    const err = document.getElementById('ylmError');
    err.textContent = msg;
    err.classList.remove('hidden');
}
document.getElementById('yearLevelManagerModal').addEventListener('click', function(e) {
    if (e.target === this) closeYearLevelManager();
});
</script>

<!-- ══════════════════════════════════════════════════════════════════
     SEMESTER ARCHIVE — inline script (needs access to allData etc.)
════════════════════════════════════════════════════════════════════ -->
<script>
// ─── Semester Archive: Render View ────────────────────────────────────────────
// Archive history is now loaded from the DB via archive_semester.php?action=list
let _archiveHistory = [];
async function _loadArchiveHistory() {
    try {
        const params = new URLSearchParams({ action: 'list' });
        if (currentUser.campus) params.set('campus', currentUser.campus);
        const res  = await fetch('../api/archive_semester.php?' + params.toString());
        const json = await res.json();
        _archiveHistory = json.success ? (json.data || []) : [];
    } catch(e) { _archiveHistory = []; }
}

function renderSemesterArchiveView() {
    const schedules = allData.filter(d => d.type === 'schedule');
    const courses   = allData.filter(d => d.type === 'course');
    const proctors  = allData.filter(d => d.type === 'proctor');
    const rooms     = allData.filter(d => d.type === 'room');
    const colleges  = allData.filter(d => d.type === 'college');
    const feedbacks = allData.filter(d => d.type === 'feedback');
    // Load fresh archive history from DB then re-render the history table
    _loadArchiveHistory().then(() => {
        const el = document.getElementById('archiveHistoryContainer');
        if (el) el.innerHTML = _renderArchiveHistoryTable(_archiveHistory);
    });
    // Archive history and campus dropdown are now static
    const archives = _archiveHistory; // may be empty on first render; re-renders after load

    return `
    <div class="fade-in space-y-6">

        <!-- Page Header -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            
            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-extrabold text-slate-800 tracking-tight">Semester Archive</h2>
                        <p class="text-sm text-slate-500 mt-0.5">Export records by term or archive &amp; reset the system for a new semester.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        ${schedules.length} Schedule${schedules.length !== 1 ? 's' : ''}
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        ${courses.length} Course${courses.length !== 1 ? 's' : ''}
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/></svg>
                        ${feedbacks.length} Feedback${feedbacks.length !== 1 ? 's' : ''}
                    </div>
                </div>
            </div>
        </div>

        <!-- Two-column cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- CARD 1: Download by Semester -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-emerald-50 to-white">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Download All Data</p>
                        <p class="text-xs text-slate-500">Export all records in the system as CSV files.</p>
                    </div>
                </div>
                <div class="p-5 space-y-4 flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600">Academic Year</label>
                            <input id="archiveYearSel" type="text" placeholder="e.g. 2024-2025"
                                   class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-400 outline-none transition placeholder:text-slate-300">
                            <p class="text-[10px] text-slate-400">Leave blank to download all years</p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600">Semester</label>
                            <select id="archiveSemSel"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-emerald-400 outline-none transition">
                                <option value="">— All Semesters —</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                    </div>
                    <!-- Campus filter row -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-600">Campus</label>
                        <div class="flex items-center gap-2 px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100 text-slate-700 font-semibold">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3zm0 0c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z"/></svg>
                            ${esc(currentUser.campus)}
                            <span class="ml-auto text-[10px] px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded-full font-bold">Locked</span>
                        </div>
                        <input type="hidden" id="archiveCampusSel" value="${esc(currentUser.campus)}">
                        <p class="text-[10px] text-slate-400">Download scoped to your campus</p>
                    </div>
                    <div class="flex items-start gap-2 bg-emerald-50 border border-emerald-100 rounded-xl p-3">
                        <svg class="w-3.5 h-3.5 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-[11px] text-emerald-800 leading-relaxed">
                            Exports <strong>all data types</strong> (schedules, courses, feedbacks, proctors, rooms &amp; colleges) filtered by the selected term and campus. Leave all blank to download everything.
                        </p>
                    </div>
                    <div id="semPreviewResult"></div>
                    <div class="flex gap-2 pt-1">
                        <button onclick="previewBySemester()"
                                class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Preview Count
                        </button>
                        <button onclick="downloadBySemester()"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Archive & Reset -->
            <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden flex flex-col">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-amber-100 bg-gradient-to-r from-amber-50 to-white">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800">Start New Semester</p>
                        <p class="text-xs text-slate-500">Archive current term, then reset for the next.</p>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full font-bold border border-amber-200 uppercase tracking-wide whitespace-nowrap">Caution</span>
                </div>
                <div class="p-5 space-y-4 flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600">Academic Year to Close</label>
                            <input id="newSemYear" type="text" placeholder="e.g. 2024-2025"
                                   class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-amber-400 outline-none transition placeholder:text-slate-300">
                            <p class="text-[10px] text-slate-400">Format: YYYY-YYYY</p>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-600">Semester to Close</label>
                            <select id="newSemSem"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-amber-400 outline-none transition">
                                <option value="">— Select Semester —</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                    </div>
                    <!-- Campus field: locked for Campus Admin, open for Super Admin -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-slate-600">Campus</label>
                        ${currentUser.is_super_admin
                            ? `<select id="newSemCampus"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-amber-400 outline-none transition">
                                <option value="">— All Campuses —</option>
                                ${CAMPUSES.map(c => `<option value="${c}">${c}</option>`).join('')}
                               </select>
                               <p class="text-[10px] text-slate-400">Leave blank to reset all campuses</p>`
                            : `<div class="flex items-center gap-2 px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-slate-100 text-slate-700 font-semibold">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3zm0 0c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z"/></svg>
                                ${esc(currentUser.campus)}
                                <span class="ml-auto text-[10px] px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded-full font-bold">Locked</span>
                               </div>
                               <input type="hidden" id="newSemCampus" value="${esc(currentUser.campus)}">`
                        }
                    </div>
                    <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-100 rounded-xl p-3.5">
                        <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <p class="text-xs text-amber-800 leading-relaxed">
                            Downloads a full CSV backup of all current <strong>schedules, courses, feedbacks &amp; special exam registrations</strong>${!currentUser.is_super_admin && currentUser.campus ? ` for <strong>${esc(currentUser.campus)}</strong>` : ''}, then <strong>permanently deletes</strong> schedules and courses.
                            Proctors, rooms &amp; colleges are <strong>kept</strong>.
                        </p>
                    </div>
                    <button onclick="openNewSemesterModal()"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Archive &amp; Reset System
                    </button>
                </div>
            </div>
        </div>

        <!-- Archive History -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800">Archive History</p>
                        <p class="text-xs text-slate-500">All past semesters saved in the database — downloadable anytime.</p>
                    </div>
                </div>
            </div>
            <div id="archiveHistoryContainer">
                <div class="py-10 flex items-center justify-center text-xs text-slate-400">Loading archive history…</div>
            </div>
        </div>

    </div>`;
}

// ─── Render archive history table (used by both initial load & refresh) ────────
function _renderArchiveHistoryTable(archives) {
    if (!archives || archives.length === 0) {
        return `<div class="py-16 flex flex-col items-center gap-3 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center">
                        <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-400">No archives yet</p>
                        <p class="text-xs text-slate-300 mt-0.5">Archived semesters will appear here.</p>
                    </div>
                </div>`;
    }
    return `<div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Academic Year</th>
                            <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Semester</th>
                            <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Archived On</th>
                            <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Records</th>
                            <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Archived By</th>
                            <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Download</th>
                            <th class="px-5 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Retrieve</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        ${archives.map(a => `
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-800 text-sm">${esc(a.academic_year || '—')}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold
                                    ${a.semester === '1st Semester' ? 'bg-blue-50 text-blue-700 border border-blue-100' :
                                      a.semester === '2nd Semester' ? 'bg-violet-50 text-violet-700 border border-violet-100' :
                                      'bg-orange-50 text-orange-700 border border-orange-100'}">
                                    ${esc(a.semester || '—')}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                ${a.archived_at ? new Date(a.archived_at).toLocaleString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true}) : '—'}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap gap-1.5">
                                    <span class="text-[11px] px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full font-semibold border border-blue-100">${a.schedule_count ?? 0} sched</span>
                                    <span class="text-[11px] px-2 py-0.5 bg-purple-50 text-purple-700 rounded-full font-semibold border border-purple-100">${a.course_count ?? 0} courses</span>
                                    <span class="text-[11px] px-2 py-0.5 bg-slate-50 text-slate-600 rounded-full font-semibold border border-slate-100">${a.feedback_count ?? 0} fb</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">${esc(a.archived_by || '—')}</td>
                            <td class="px-5 py-3.5">
                                <button onclick="downloadArchivedTerm('${esc(a.academic_year)}','${esc(a.semester)}', currentUser.campus)"
                                    class="flex items-center gap-1 px-3 py-1.5 text-[11px] font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-lg transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download CSV
                                </button>
                            </td>
                            <td class="px-5 py-3.5">
                                <button onclick="openRetrieveModal(${a.id}, '${esc(a.academic_year)}', '${esc(a.semester)}', '${esc(a.campus || '')}')"
                                    class="flex items-center gap-1 px-3 py-1.5 text-[11px] font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Retrieve
                                </button>
                            </td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`;
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function _getSemesterFilteredData(yearVal, semVal, campusVal) {
    const schedules = allData.filter(d => d.type === 'schedule');
    const courses   = allData.filter(d => d.type === 'course');
    const feedbacks = allData.filter(d => d.type === 'feedback');

    function matchYearSem(d) {
        const dYear   = (d.academic_year || '').trim();
        const dSem    = (d.semester || '').trim();
        const dCampus = (d.campus || '').trim();
        const yearOk   = !yearVal   || dYear   === yearVal || dYear === '';
        const semOk    = !semVal    || dSem    === semVal;
        const campusOk = !campusVal || dCampus === campusVal;
        return yearOk && semOk && campusOk;
    }

    const filtSched    = schedules.filter(matchYearSem);
    const filtCourses  = courses.filter(matchYearSem);
    const filtFeedback = feedbacks.filter(d => {
        const dCampus = (d.campus || '').trim();
        return !campusVal || dCampus === campusVal;
    });

    return { filtSched, filtCourses, filtFeedback };
}


function _buildCSVBlob(data, fields) {
    const header = fields.join(',');
    const rows = data.map(row =>
        fields.map(f => {
            const val = row[f] !== undefined && row[f] !== null ? String(row[f]) : '';
            return val.includes(',') || val.includes('"') || val.includes('\n')
                ? `"${val.replace(/"/g, '""')}"` : val;
        }).join(',')
    );
    const csvString = [header, ...rows].join('\n');
    // Prepend UTF-8 BOM (\uFEFF) so Excel correctly reads special characters (e.g. em dash —)
    return new Blob(['\uFEFF' + csvString], { type: 'text/csv;charset=utf-8;' });
}

function _triggerDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a   = document.createElement('a');
    a.href     = url;
    a.download = filename;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { URL.revokeObjectURL(url); if (a.parentNode) a.parentNode.removeChild(a); }, 2000);
}

// Load SheetJS once then run callback
function _withXLSX(cb) {
    if (window.XLSX) { cb(); return; }
    const s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
    s.onload = cb;
    document.head.appendChild(s);
}

// Build a styled worksheet from headers + row arrays
function _makeSheet(XL, headers, rows) {
    const aoa = [headers, ...rows];
    const ws  = XL.utils.aoa_to_sheet(aoa);

    // Bold + light-green fill on header row
    headers.forEach((_, ci) => {
        const addr = XL.utils.encode_cell({ r: 0, c: ci });
        if (!ws[addr]) ws[addr] = { v: headers[ci], t: 's' };
        ws[addr].s = {
            font: { bold: true, color: { rgb: 'FF065F46' } },
            fill: { patternType: 'solid', fgColor: { rgb: 'FFD1FAE5' } },
            alignment: { horizontal: 'center', vertical: 'center' },
            border: { bottom: { style: 'thin', color: { rgb: 'FF047857' } } }
        };
    });

    // Auto column widths based on longest value per column
    ws['!cols'] = headers.map((h, ci) => {
        const maxLen = Math.max(
            String(h).length,
            ...rows.map(r => String(r[ci] ?? '').length)
        );
        return { wch: Math.min(Math.max(maxLen + 2, 10), 50) };
    });

    return ws;
}

// ─── Preview Count ─────────────────────────────────────────────────────────────
window.previewBySemester = function() {
    const yearVal   = document.getElementById('archiveYearSel')?.value    || '';
    const semVal    = document.getElementById('archiveSemSel')?.value     || '';
    const campusVal = document.getElementById('archiveCampusSel')?.value  || '';
    const { filtSched, filtCourses, filtFeedback } = _getSemesterFilteredData(yearVal, semVal, campusVal);
    const label = [yearVal || 'All Years', semVal || 'All Semesters', campusVal || 'All Campuses'].join(' · ');
    const el = document.getElementById('semPreviewResult');
    if (!el) return;
    el.innerHTML = `
        <div class="flex flex-wrap gap-3 mt-1">
            <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-100 rounded-lg">
                <span class="text-xs font-bold text-blue-700">Schedules</span>
                <span class="text-lg font-black text-blue-800">${filtSched.length}</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-emerald-50 border border-emerald-100 rounded-lg">
                <span class="text-xs font-bold text-emerald-700">Courses</span>
                <span class="text-lg font-black text-emerald-800">${filtCourses.length}</span>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-purple-50 border border-purple-100 rounded-lg">
                <span class="text-xs font-bold text-purple-700">Feedbacks</span>
                <span class="text-lg font-black text-purple-800">${filtFeedback.length}</span>
            </div>
            <span class="self-center text-xs text-slate-400 italic">${label}</span>
        </div>`;
};

// ─── Download — single XLSX, each data type on its own sheet tab ───────────────
window.downloadBySemester = function() {
    const yearVal   = (document.getElementById('archiveYearSel')?.value    || '').trim();
    const semVal    = (document.getElementById('archiveSemSel')?.value     || '').trim();
    const campusVal = (document.getElementById('archiveCampusSel')?.value  || '').trim();

    // Live records don't carry academic_year; filter by semester and campus.
    function matchSemFilter(d) {
        const semOk    = !semVal    || (d.semester || '').trim() === semVal;
        const campusOk = !campusVal || (d.campus   || '').trim() === campusVal;
        return semOk && campusOk;
    }

    const schedules = allData.filter(d => d.type === 'schedule' && matchSemFilter(d));
    const courses   = allData.filter(d => d.type === 'course'   && matchSemFilter(d));
    const feedbacks = allData.filter(d => d.type === 'feedback' && (!campusVal || (d.campus || '').trim() === campusVal));
    const proctors  = allData.filter(d => d.type === 'proctor'  && (!campusVal || (d.campus || '').trim() === campusVal));
    const rooms     = allData.filter(d => d.type === 'room'     && (!campusVal || (d.campus || '').trim() === campusVal));
    const colleges  = allData.filter(d => d.type === 'college'  && (!campusVal || (d.campus || '').trim() === campusVal));

    const total = schedules.length + courses.length + feedbacks.length + proctors.length + rooms.length + colleges.length;
    if (total === 0) { showToast('No data found for that filter.', 'error'); return; }

    const slug     = [yearVal || 'all-years', (semVal || 'all-semesters').replace(/\s+/g, '-'), (campusVal || 'all-campuses').replace(/\s+/g, '-')].join('_').toLowerCase();
    const filename = `${slug}_export.xlsx`;

    showToast('Preparing Excel file…');

    _withXLSX(() => {
        const XL = window.XLSX;
        const wb = XL.utils.book_new();

        // ── Schedules sheet ──
        const schedHeaders = ['ID','Course Code','Course Name','College','Program','Year Level','Section','Exam Type','Semester','Exam Date','Time Slot','Duration','Room','Proctor','Campus','Status'];
        const schedRows = schedules.map(s => {
            const { courseName, roomName, proctorName, examDate } = resolveScheduleDisplay(s);
            return [s.id||'', s.course_code||courseName||'', courseName||'', s.college||'',
                    s.program||'', s.year_level||'', s.section||'', s.exam_type||'',
                    s.semester||'', examDate||'', s.time_slot||'', s.duration||'',
                    roomName||'', proctorName||'', s.campus||'', s.status||'Pending'];
        });
        XL.utils.book_append_sheet(wb, _makeSheet(XL, schedHeaders, schedRows), 'Schedules');

        // ── Courses sheet ──
        const courseHeaders = ['ID','Course Code','Course Name','College','Program','Year Level','Semester','Campus'];
        const courseRows = courses.map(c => [c.id||'', c.course_code||'', c.course_name||'', c.college||'', c.program||'', c.year_level||'', c.semester||'', c.campus||'']);
        XL.utils.book_append_sheet(wb, _makeSheet(XL, courseHeaders, courseRows), 'Courses');

        // ── Feedbacks sheet ──
        const fbHeaders = ['ID','Student Name','College','Program','Subject','Category','Message','Rating','Campus','Submitted At'];
        const fbRows = feedbacks.map(f => [f.id||'', f.student_name||'Anonymous', f.college||'', f.program||'', f.subject||'', f.category||'', f.message||'', f.rating||'', f.campus||'', f.created_at||'']);
        XL.utils.book_append_sheet(wb, _makeSheet(XL, fbHeaders, fbRows), 'Feedbacks');

        // ── Proctors sheet ──
        const proHeaders = ['ID','Name','College / Program','Email','Phone','Campus'];
        const proRows = proctors.map(p => [p.id||'', p.name||'', p.college_program||'', p.email||'', p.phone||'', p.campus||'']);
        XL.utils.book_append_sheet(wb, _makeSheet(XL, proHeaders, proRows), 'Proctors');

        // ── Rooms sheet ──
        const roomHeaders = ['ID','Room Name','Building','Capacity','Floor','Campus','Locked'];
        const roomRows = rooms.map(r => [r.id||'', r.name||'', r.building||'', r.capacity||'', r.floor||'', r.campus||'', (r.locked===true||r.locked===1||r.locked==='1')?'Yes':'No']);
        XL.utils.book_append_sheet(wb, _makeSheet(XL, roomHeaders, roomRows), 'Rooms');

        // ── Colleges sheet ──
        const colHeaders = ['ID','College Name','Programs','Description','Campus'];
        const colRows = colleges.map(c => [c.id||'', c.name||'', Array.isArray(c.programs) ? c.programs.join('; ') : (c.programs||''), c.description||'', c.campus||'']);
        XL.utils.book_append_sheet(wb, _makeSheet(XL, colHeaders, colRows), 'Colleges');

        // ── Special Exam Registrations sheet ── (scoped to campus admin's campus)
        const campusFilter = campusVal || currentUser.campus || '';
        const specialExams = (window._specialExams || []).filter(e => {
            const semOk    = !semVal    || (e.semester || '').trim() === semVal;
            const campusOk = !campusFilter || (e.campus || '').trim() === campusFilter;
            return semOk && campusOk;
        });
        const spHeaders = ['ID','Student No.','Last Name','First Name','College','Program','Campus','School Year','Semester','Exam Type','Receipt No.','No. of Exams','Reason','Courses'];
        const spRows = specialExams.map(e => {
            const parsed = (() => { try { return JSON.parse(e.exam_courses||'[]'); } catch(_){return[];} })();
            const courseLabels = parsed.map(c => c.course_label || c.course_id || '').filter(Boolean).join('; ');
            return [e.id||'', e.student_no||'', e.last_name||'', e.first_name||'', e.college||'', e.program||'', e.campus||'', e.school_year||'', e.semester||'', e.exam_type||'', e.receipt_no||'', e.num_exams||0, e.reason||'', courseLabels];
        });
        XL.utils.book_append_sheet(wb, _makeSheet(XL, spHeaders, spRows), 'Special Exam Registrations');

        // bookSST: true ensures shared string table uses UTF-8, fixing garbled characters (e.g. em dash —)
        const wbOut = XL.write(wb, { bookType: 'xlsx', type: 'array', cellStyles: true, bookSST: true });
        const blob  = new Blob([wbOut], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        _triggerDownload(blob, filename);

        const totalWithSp = total + specialExams.length;
        showToast(`Downloaded ${totalWithSp} records → ${filename}`);
        activityLog('System Download', `Semester: ${semVal||'All'}, Campus: ${campusFilter||'All'}, Records: ${totalWithSp}, File: ${filename}`);
    });
};

window.openNewSemesterModal = async function() {
    const yearVal   = document.getElementById('newSemYear')?.value   || '';
    const semVal    = document.getElementById('newSemSem')?.value    || '';
    const campusVal = document.getElementById('newSemCampus')?.value || '';

    if (!yearVal || !semVal) {
        showToast('Please enter an Academic Year and select a Semester before archiving.', 'error');
        return;
    }

    // Scope counts: Campus Admin always scoped to their campus; Super Admin uses dropdown
    const effectiveCampus = campusVal || (!currentUser.is_super_admin ? currentUser.campus : '');

    // ── Duplicate guard: check archive_history before opening the modal ────────
    try {
        const dupParams = new URLSearchParams({ action: 'list', academic_year: yearVal, semester: semVal });
        if (effectiveCampus) dupParams.set('campus', effectiveCampus);
        const dupRes  = await fetch('../api/archive_semester.php?' + dupParams.toString());
        const dupJson = await dupRes.json();
        if (dupJson.success && Array.isArray(dupJson.data) && dupJson.data.length > 0) {
            const campusLabel = effectiveCampus || 'All Campuses';
            showToast(
                `⚠ "${yearVal} \u2013 ${semVal}" (${campusLabel}) has already been archived. Duplicate reset is not allowed.`,
                'error'
            );
            return;
        }
    } catch(e) {
        // Network error on pre-check — let the modal open; the server will still block it.
    }
    // ── End duplicate guard ───────────────────────────────────────────────────

    // Scope counts to the chosen campus (if any)
    function campusMatch(d) { return !effectiveCampus || (d.campus || '').trim() === effectiveCampus; }
    const schedCount = allData.filter(d => d.type === 'schedule' && campusMatch(d)).length;
    const courseCount = allData.filter(d => d.type === 'course'  && campusMatch(d)).length;
    const feedCount   = allData.filter(d => d.type === 'feedback' && campusMatch(d)).length;

    const campusLabel = effectiveCampus || 'All Campuses';

    if (document.getElementById('newSemModal')) document.getElementById('newSemModal').remove();

    const overlay = document.createElement('div');
    overlay.id = 'newSemModal';
    overlay.style.cssText = 'display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:60;align-items:center;justify-content:center;padding:1rem;';
    overlay.innerHTML = `
        <div style="background:white;border-radius:1rem;width:100%;max-width:460px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#d97706,#f59e0b);padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="color:white;font-weight:800;font-size:1rem;margin:0;">Archive &amp; Reset System</p>
                    <p style="color:rgba(255,255,255,0.8);font-size:0.75rem;margin:0.2rem 0 0;">${esc(yearVal || 'All Years')} · ${esc(semVal || 'All Semesters')} · ${esc(campusLabel)}</p>
                </div>
                <button onclick="document.getElementById('newSemModal').remove()"
                    style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.25);border:none;cursor:pointer;color:white;font-size:16px;">✕</button>
            </div>
            <!-- Body -->
            <div style="padding:1.5rem;space-y:1rem;">
                <div style="background:#fefce8;border:1.5px solid #fde68a;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;font-size:0.8rem;color:#92400e;line-height:1.6;">
                    <strong>This action will:</strong><br>
                    1️⃣ &nbsp;Auto-download all current data as CSV backups<br>
                    2️⃣ &nbsp;Permanently delete all <strong>${schedCount} schedule${schedCount!==1?'s':''}</strong> from the system<br>
                    3️⃣ &nbsp;Permanently delete all <strong>${courseCount} course${courseCount!==1?'s':''}</strong> from the system<br>
                    4️⃣ &nbsp;Permanently delete all <strong>${feedCount} feedback${feedCount!==1?'s':''}</strong> from the system<br>
                    ✅ &nbsp;Proctors, Rooms &amp; Colleges will be <strong>kept</strong>${campusVal ? `<br>🏫 &nbsp;Scoped to <strong>${esc(campusVal)}</strong> campus only` : ''}
                </div>
                <p style="font-size:0.82rem;color:#475569;margin-bottom:1rem;">Type <strong>RESET</strong> below to confirm this is intentional:</p>
                <input id="newSemConfirmInput" type="text" placeholder="Type RESET to confirm"
                    style="width:100%;padding:0.625rem 0.875rem;border:1.5px solid #e2e8f0;border-radius:0.5rem;font-size:0.875rem;outline:none;box-sizing:border-box;"
                    oninput="document.getElementById('newSemConfirmBtn').disabled = this.value !== 'RESET';"
                    onfocus="this.style.borderColor='#f59e0b';" onblur="this.style.borderColor='#e2e8f0';">
            </div>
            <!-- Footer -->
            <div style="padding:1rem 1.5rem 1.5rem;display:flex;justify-content:flex-end;gap:0.75rem;">
                <button onclick="document.getElementById('newSemModal').remove()"
                    style="padding:0.5rem 1.25rem;border:1.5px solid #e2e8f0;border-radius:0.625rem;background:white;color:#64748b;font-size:0.825rem;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button id="newSemConfirmBtn" disabled onclick="executeNewSemesterReset('${esc(yearVal)}','${esc(semVal)}','${esc(effectiveCampus)}')"
                    style="padding:0.5rem 1.25rem;border:none;border-radius:0.625rem;background:#d97706;color:white;font-size:0.825rem;font-weight:700;cursor:pointer;opacity:0.5;transition:opacity 0.15s;"
                    onmouseover="if(!this.disabled)this.style.background='#b45309';" onmouseout="this.style.background='#d97706';">
                    ⚠ Archive &amp; Reset
                </button>
            </div>
        </div>`;

    // Enable confirm button only when typed correctly
    overlay.querySelector('#newSemConfirmBtn').style.opacity = '0.5';
    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });

    // Watch the input dynamically
    setTimeout(() => {
        const inp = document.getElementById('newSemConfirmInput');
        const btn = document.getElementById('newSemConfirmBtn');
        if (inp && btn) {
            inp.addEventListener('input', () => {
                const ok = inp.value === 'RESET';
                btn.disabled = !ok;
                btn.style.opacity = ok ? '1' : '0.5';
                btn.style.cursor  = ok ? 'pointer' : 'not-allowed';
            });
        }
    }, 50);
};

// ─── Execute Archive & Reset (now calls archive_semester.php) ─────────────────
window.executeNewSemesterReset = async function(yearVal, semVal, campusVal) {
    const modal = document.getElementById('newSemModal');
    if (modal) modal.remove();

    showToast('Archiving data to database…');

    try {
        const payload = { academic_year: yearVal, semester: semVal };
        if (campusVal) payload.campus = campusVal;

        const res  = await fetch('../api/archive_semester.php?action=archive', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (!json.success) {
            // 409 = duplicate archive detected by the server
            if (res.status === 409) {
                showToast(
                    `\u26A0 Duplicate archive blocked: "${yearVal} \u2013 ${semVal}" has already been archived and reset. No changes were made.`,
                    'error'
                );
            } else {
                showToast('Archive failed: ' + (json.message || 'Unknown error'), 'error');
            }
            return;
        }

        const d = json.data;
        showToast('Archive saved! Preparing CSV backup downloads…');

        // Download CSVs from the newly archived data
        await downloadArchivedTerm(yearVal, semVal, campusVal);

        activityLog('Semester Archive & Reset',
            `Archived ${yearVal} ${semVal}${campusVal ? ' · ' + campusVal : ''}: ${d.scheduleCount} schedules, ${d.courseCount} courses, ${d.feedbackCount} feedbacks.`);

        await refreshAllData();

        // Refresh archive history table from DB
        await _loadArchiveHistory();
        const el = document.getElementById('archiveHistoryContainer');
        if (el) el.innerHTML = _renderArchiveHistoryTable(_archiveHistory);

        showToast(`✅ System reset! ${yearVal} ${semVal}${campusVal ? ' · ' + campusVal : ''} archived and cleared successfully.`);

    } catch(e) {
        showToast('Network error during archive: ' + e.message, 'error');
    }
};

// ─── Download archived term from DB as CSVs ────────────────────────────────────
window.downloadArchivedTerm = async function(yearVal, semVal, campusVal) {
    // Campus admins are always scoped to their own campus
    const effectiveCampus = currentUser.campus || campusVal;
    try {
        const params = new URLSearchParams({ action: 'download', type: 'all' });
        if (yearVal)         params.set('academic_year', yearVal);
        if (semVal)          params.set('semester', semVal);
        if (effectiveCampus) params.set('campus', effectiveCampus);

        const res  = await fetch('../api/archive_semester.php?' + params.toString());
        const json = await res.json();
        if (!json.success) { showToast('Download failed: ' + json.message, 'error'); return; }

        const d    = json.data;
        const slug = [yearVal || 'all-years', (semVal || 'all-semesters').replace(/\s+/g,'-'), (effectiveCampus || 'all-campuses').replace(/\s+/g,'-')].join('_').toLowerCase();

        const schedules = d.schedules || [];
        const courses   = d.courses   || [];
        const feedbacks = d.feedbacks || [];

        if (schedules.length === 0 && courses.length === 0 && feedbacks.length === 0) {
            showToast('No archived data found for that term.', 'error');
            return;
        }

        let downloaded = 0;
        if (schedules.length > 0) {
            const fields = ['orig_id','course_code','course_name','college','program','year_level','section',
                            'exam_type','orig_semester','academic_year','exam_date','time_slot','duration',
                            'room_name','is_online','proctor_name','orig_campus','status'];
            const rows = schedules.map(s => ({
                orig_id: s.orig_id||'', course_code: s.course_code||'', course_name: s.course_name||'',
                college: s.college||'', program: s.program||'', year_level: s.year_level||'',
                section: s.section||'', exam_type: s.exam_type||'', orig_semester: s.orig_semester||'',
                academic_year: s.academic_year||'', exam_date: s.exam_date||'', time_slot: s.time_slot||'',
                duration: s.duration||'', room_name: s.is_online ? 'Online' : (s.room_name||''), is_online: s.is_online ? 1 : 0, proctor_name: s.proctor_name||'',
                orig_campus: s.orig_campus||'', status: s.status||''
            }));
            await new Promise(r => setTimeout(r, 0));
            _triggerDownload(_buildCSVBlob(rows, fields), `${slug}_schedules.csv`);
            downloaded++;
        }
        if (courses.length > 0) {
            await new Promise(r => setTimeout(r, 300));
            const fields = ['orig_id','course_code','course_name','college','program','year_level','orig_semester','orig_campus'];
            _triggerDownload(_buildCSVBlob(courses, fields), `${slug}_courses.csv`);
            downloaded++;
        }
        if (feedbacks.length > 0) {
            await new Promise(r => setTimeout(r, 600));
            const fields = ['orig_id','student_name','college','program','subject','category','message','orig_campus','created_at'];
            _triggerDownload(_buildCSVBlob(feedbacks, fields), `${slug}_feedbacks.csv`);
            downloaded++;
        }
        if ((d.specialExams || []).length > 0) {
            await new Promise(r => setTimeout(r, 900));
            const spFields = ['orig_id','student_no','last_name','first_name','college','program',
                              'orig_campus','school_year','orig_semester','exam_type',
                              'receipt_no','num_exams','reason','exam_courses'];
            const spRows = d.specialExams.map(e => {
                const parsed = (() => { try { return JSON.parse(e.exam_courses||'[]'); } catch(_){return[];} })();
                const labels = parsed.map(c => c.course_label||c.course_id||'').filter(Boolean).join('; ');
                return {
                    orig_id: e.orig_id||'', student_no: e.student_no||'',
                    last_name: e.last_name||'', first_name: e.first_name||'',
                    college: e.college||'', program: e.program||'',
                    orig_campus: e.orig_campus||'', school_year: e.school_year||'',
                    orig_semester: e.orig_semester||'', exam_type: e.exam_type||'',
                    receipt_no: e.receipt_no||'', num_exams: e.num_exams||0,
                    reason: e.reason||'', exam_courses: labels
                };
            });
            _triggerDownload(_buildCSVBlob(spRows, spFields), `${slug}_special_exams.csv`);
            downloaded++;
        }

        showToast(`Downloaded ${downloaded} CSV file(s) for ${yearVal || 'all years'} ${semVal || ''}${effectiveCampus ? ' · ' + effectiveCampus : ''}.`);
    } catch(e) {
        showToast('Download error: ' + e.message, 'error');
    }
};

// ─── Retrieve Archive Modal ────────────────────────────────────────────────────
window.openRetrieveModal = function(archiveId, yearVal, semVal, campusVal) {
    const existing = document.getElementById('retrieveModal');
    if (existing) existing.remove();

    const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const overlay = document.createElement('div');
    overlay.id = 'retrieveModal';
    overlay.style.cssText = 'display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:60;align-items:center;justify-content:center;padding:1rem;';
    overlay.innerHTML = `
        <div style="background:white;border-radius:1rem;width:100%;max-width:460px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <div style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);padding:1.25rem 1.5rem;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <p style="color:white;font-weight:800;font-size:1rem;margin:0;">Retrieve Archived Semester</p>
                    <p style="color:rgba(255,255,255,0.8);font-size:0.75rem;margin:0.2rem 0 0;">${esc(yearVal)} · ${esc(semVal)}${campusVal ? ' · ' + esc(campusVal) : ''}</p>
                </div>
                <button onclick="document.getElementById('retrieveModal').remove()"
                    style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.25);border:none;cursor:pointer;color:white;font-size:16px;">✕</button>
            </div>
            <div style="padding:1.5rem;">
                <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:0.75rem;padding:1rem;margin-bottom:1rem;font-size:0.8rem;color:#1e40af;line-height:1.6;">
                    <strong>This will restore:</strong><br>
                    📅 &nbsp;All <strong>schedules</strong> from ${esc(yearVal)} ${esc(semVal)} back into the live system<br>
                    📚 &nbsp;All <strong>courses</strong> from ${esc(yearVal)} ${esc(semVal)} back into the live system<br>
                    💬 &nbsp;All <strong>feedbacks</strong> from ${esc(yearVal)} back into the live system<br><br>
                    <strong style="color:#dc2626;">⚠ The archive entry will be permanently removed.</strong>
                </div>
                <p style="font-size:0.82rem;color:#475569;margin-bottom:1rem;">Type <strong>RETRIEVE</strong> below to confirm:</p>
                <input id="retrieveConfirmInput" type="text" placeholder="Type RETRIEVE to confirm"
                    style="width:100%;padding:0.625rem 0.875rem;border:1.5px solid #e2e8f0;border-radius:0.5rem;font-size:0.875rem;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#3b82f6';" onblur="this.style.borderColor='#e2e8f0';">
            </div>
            <div style="padding:1rem 1.5rem 1.5rem;display:flex;justify-content:flex-end;gap:0.75rem;">
                <button onclick="document.getElementById('retrieveModal').remove()"
                    style="padding:0.5rem 1.25rem;border:1.5px solid #e2e8f0;border-radius:0.625rem;background:white;color:#64748b;font-size:0.825rem;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button id="retrieveConfirmBtn" disabled onclick="executeRetrieve(${archiveId}, '${esc(yearVal)}', '${esc(semVal)}')"
                    style="padding:0.5rem 1.25rem;border:none;border-radius:0.625rem;background:#1d4ed8;color:white;font-size:0.825rem;font-weight:700;cursor:not-allowed;opacity:0.5;transition:opacity 0.15s;">
                    ↩ Retrieve Semester
                </button>
            </div>
        </div>`;

    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });

    setTimeout(() => {
        const inp = document.getElementById('retrieveConfirmInput');
        const btn = document.getElementById('retrieveConfirmBtn');
        if (inp && btn) {
            inp.addEventListener('input', () => {
                const ok = inp.value === 'RETRIEVE';
                btn.disabled = !ok;
                btn.style.opacity = ok ? '1' : '0.5';
                btn.style.cursor  = ok ? 'pointer' : 'not-allowed';
            });
        }
    }, 50);
};

window.executeRetrieve = async function(archiveId, yearVal, semVal) {
    const modal = document.getElementById('retrieveModal');
    if (modal) modal.remove();

    showToast('Restoring archived data…');

    try {
        const res  = await fetch('../api/archive_semester.php?action=retrieve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ archive_id: archiveId }),
        });
        const json = await res.json();

        if (!json.success) {
            showToast('Retrieve failed: ' + (json.message || 'Unknown error'), 'error');
            return;
        }

        activityLog('Semester Retrieve', `Restored ${yearVal} ${semVal} from archive back to live system.`);

        await refreshAllData();

        await _loadArchiveHistory();
        const el = document.getElementById('archiveHistoryContainer');
        if (el) el.innerHTML = _renderArchiveHistoryTable(_archiveHistory);

        showToast(`✅ ${yearVal} ${semVal} has been restored to the live system.`);

    } catch(e) {
        showToast('Network error during retrieve: ' + e.message, 'error');
    }
};

// ─── Clear Archive History (removed — history now lives in the DB) ─────────────
window.clearArchiveHistory = function() {
    showToast('Archive history is now stored in the database and cannot be cleared from here.', 'error');
};

// ─── Real-time polling: refresh special exam registrations every 30 seconds ───
(function startSpecialExamPolling() {
    const _campusKey = (currentUser.campus || '').trim().toLowerCase();
    let _lastSpExamIds = (window._specialExams || []).map(e => e.id).join(',');

    setInterval(async () => {
        if (document.hidden) return;
        try {
            const spRes = await window.flexamApi.special_exams.list();
            const fresh = (spRes.data || []).filter(e =>
                !_campusKey || (e.campus || '').trim().toLowerCase() === _campusKey
            );
            const freshIds = fresh.map(e => e.id).join(',');
            if (freshIds !== _lastSpExamIds) {
                window._specialExams = fresh;
                _lastSpExamIds = freshIds;
                renderApp();
            }
        } catch (e) { /* silently ignore polling errors */ }
    }, 30000);
})();


// ═══════════════════════════════════════════════════════════════════════════
// ── CAMPUS ANNOUNCEMENTS ────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

// ── CAMPUS ADMIN ANNOUNCEMENTS – DB-backed, real-time ────────────────────────
// Role: campus_admin
//   • Automatically receives ALL superadmin (global) announcements.
//   • Also receives superadmin announcements targeted to this specific campus.
//   • Can post / edit / delete ONLY their own campus announcements.
//   • Their posts appear for heads and guests of the same campus within ≤5 s.
// ─────────────────────────────────────────────────────────────────────────────
(function () {
    'use strict';

    // `currentUser` is declared with `let` in a different <script> block and is
    // not accessible here. Read it from sessionStorage where it was stored at login.
    var _cu = (function () {
        try { return JSON.parse(sessionStorage.getItem('currentUser') || '{}'); } catch (e) { return {}; }
    })();
    var _myCampus = (_cu.campus || '').trim();
    var _lastAnns  = [];   // last known announcements — survives DOM rebuilds
    var _initDone  = false;

    // Called by renderApp() every time it rebuilds the DOM.
    // Uses the buffered _lastAnns so data is shown instantly without a new API call.
    window._flexAnnRefresh = function () {
        setTimeout(function () {
            if (_initDone) {
                // Replay buffered data into the freshly rebuilt DOM — no network call.
                _renderUnified(_lastAnns);
            }
            // If not yet initialised, FlexAnn's first onUpdate call will handle it.
        }, 0);
    };

    function _showError(msg) {
        setTimeout(function () {
            var g = document.getElementById('flexAnnCAUnifiedGrid');
            if (g) g.innerHTML = '<p class="text-sm text-red-400 py-4 text-center col-span-full">⚠ ' + msg + '</p>';
        }, 0);
    }

    FlexAnn.init({
        apiBase  : '../api/announcements.php',
        role     : 'campus_admin',
        campus   : _myCampus,   // server scopes response to global + own campus
        onUpdate : function (anns) {
            // Buffer the data so _flexAnnRefresh can use it after DOM rebuilds.
            _lastAnns = anns;
            _initDone = true;
            _renderUnified(anns);
        },
        onError  : function (msg) {
            _showError(msg);
        },
    });

    // ── Render all announcements in a single unified grid ────────────────────
    // Super admin cards (read-only, labelled) and campus admin cards (editable)
    // sit side-by-side in the same grid — no stacked sections.
    function _renderSysSection(anns) { _renderUnified(anns); }
    function _renderMySection(anns)  { /* unified — handled by _renderSysSection */ }

    function _renderUnified(anns) {
        var grid  = document.getElementById('flexAnnCAUnifiedGrid');
        var badge = document.getElementById('flexAnnCAMyBadge');
        if (!grid) return;

        var sysAnns = anns.filter(function (a) { return a.active && a.poster_role === 'Admin'; });
        var mine    = anns.filter(function (a) {
            return a.active && a.poster_role === 'CampusAdmin' &&
                   (a.campus || '').trim() === _myCampus;
        });

        var totalActive = sysAnns.length + mine.length;
        if (badge) badge.textContent = totalActive ? totalActive + ' active' : 'none';

        if (!totalActive) {
            grid.innerHTML = '<p class="text-sm text-slate-400 py-4 text-center col-span-full">No announcements yet. Click <strong>+ New Announcement</strong> to post one.</p>';
            return;
        }

        // Pinned first within each group, then by recency
        var sortGroup = function(arr) {
            return arr.filter(function(a){ return a.pinned; })
                      .concat(arr.filter(function(a){ return !a.pinned; }));
        };

        // Build card HTML — sys cards get a subtle "From Super Admin" label overlay
        var sysHtml = sortGroup(sysAnns).map(function(a) {
            return '<div class="relative">'
                + '<span class="absolute top-2 right-2 z-10 text-[9px] font-bold px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-full">Super Admin</span>'
                + FlexAnn.renderViewerCard(a)
                + '</div>';
        }).join('');

        var mineHtml = sortGroup(mine).map(function(a) {
            return FlexAnn.renderAdminCard(a, {
                canEdit      : true,
                canDelete    : true,
                isSuperAdmin : false,
            });
        }).join('');

        grid.innerHTML = sysHtml + mineHtml;

        // Wire up edit buttons
        grid.querySelectorAll('[data-ann-edit]').forEach(function (btn) {
            btn.onclick = function () {
                var id  = parseInt(btn.getAttribute('data-ann-edit'));
                var ann = FlexAnn.getCache().find(function (a) { return a.id === id; });
                FlexAnn.openEditModal(ann, {
                    isSuperAdmin : false,
                    campus       : _myCampus,
                    onSave       : function () {
                        if (typeof showToast === 'function') showToast('Announcement updated.', 'success');
                    },
                });
            };
        });

        // Wire up delete buttons
        grid.querySelectorAll('[data-ann-delete]').forEach(function (btn) {
            btn.onclick = function () {
                if (!confirm('Delete this announcement?')) return;
                FlexAnn.remove(parseInt(btn.getAttribute('data-ann-delete'))).then(function (d) {
                    if (d.success && typeof showToast === 'function') showToast('Deleted.', 'success');
                });
            };
        });
    }

    // Called by the "+ New Announcement" button
    window.openNewCampusAnnModal = function () {
        FlexAnn.openEditModal(null, {
            isSuperAdmin : false,
            campus       : _myCampus,
            onSave       : function () {
                if (typeof showToast === 'function') showToast('Announcement posted!', 'success');
            },
        });
    };
})();
// ── END CAMPUS ANNOUNCEMENTS ──────────────────────────────────────────────────
</script>
</body>
</html>