<!doctype html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FLEXAM - Exam Calendar</title>
<link rel="icon" type="image/svg+xml" href="../Image/OLFU.png">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
<script src="../assets/js/announcements.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Poppins', sans-serif;
        background-color: #f8fafc;
        color: #1e293b;
    }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .sidebar-link {
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        color: #64748b;
        font-weight: 500;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    .sidebar-link:hover { background-color: #f1f5f9; color: #1e293b; transform: translateX(2px); }
    .sidebar-link.active { background-color: #ecfdf5; color: #047857; font-weight: 600; }
    
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
        padding: 1rem 1.5rem;
        height: 80px;
    }

    .calendar-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        border-top: 1px solid #e2e8f0;
    }

    .calendar-cell {
        min-height: 100px;
        padding: 0.5rem;
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        background: white;
        position: relative;
        transition: background-color 0.1s;
    }
    .calendar-cell:hover { background-color: #f8fafc; }
    .calendar-cell.has-exams:hover { background-color: #f0fdf4; }

    .event-bar {
        padding: 3px 6px;
        border-radius: 4px;
        font-size: 10px;
        color: white;
        margin-top: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .event-bar:hover { opacity: 0.85; transform: translateY(-1px); }

    .day-number {
        font-size: 0.875rem;
        color: #334155;
        font-weight: 500;
        display: inline-flex;
        width: 1.75rem;
        height: 1.75rem;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
    }
    .day-number.today {
        background-color: #047857;
        color: white;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(4, 120, 87, 0.3);
    }

    .btn-today {
        background-color: #ecfdf5;
        color: #047857;
        font-weight: 600;
        padding: 0.375rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    .btn-today:hover { background-color: #d1fae5; }

    .nav-arrow {
        color: #64748b;
        padding: 0.375rem;
        border-radius: 0.375rem;
        transition: all 0.2s;
    }
    .nav-arrow:hover { background-color: #f1f5f9; color: #1e293b; }

    .fade-in { animation: fadeIn 0.3s ease; }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(1rem); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .toast-notification { z-index: 50; }
    .text-custom { color: #047857; }
    .bg-custom { background-color: #047857; }
    .border-custom { border-color: #047857; }

    /* ── Responsive sidebar ── */
    #mainSidebar { transition: transform 0.25s ease; }
    @media (max-width: 1023px) {
        #mainSidebar { position: fixed; inset-y: 0; left: 0; z-index: 40; transform: translateX(-100%); }
        #mainSidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,0.12); }
        #sidebarOverlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 30; }
        #sidebarOverlay.open { display: block; }
        .top-header { padding: 0.75rem 1rem; height: auto; min-height: 60px; }
    }
    @media (min-width: 1024px) {
        #hamburger-btn { display: none; }
    }

    /* ── Filter collapse (mobile) ── */
    #filter-panel { overflow: hidden; transition: max-height 0.25s ease, opacity 0.2s ease; }
    #filter-panel.collapsed { max-height: 0 !important; opacity: 0; }

    /* ── Calendar responsive ── */
    @media (max-width: 640px) {
        .calendar-cell { min-height: 46px; padding: 0.25rem; }
        .event-bar { font-size: 9px; padding: 2px 4px; }
        .day-number { width: 1.35rem; height: 1.35rem; font-size: 0.7rem; }
    }

    /* ── Table → card layout on mobile ── */
    @media (max-width: 767px) {
        .resp-table thead { display: none; }
        .resp-table tbody tr {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
            padding: 0.75rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .resp-table tbody td {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            padding: 0.3rem 0.25rem;
            border: none;
            font-size: 0.8rem;
        }
        .resp-table tbody td::before {
            content: attr(data-label);
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            min-width: 72px;
            padding-top: 2px;
            flex-shrink: 0;
        }
        .main-padding { padding: 0.875rem; }
    }
    @media (min-width: 768px) {
        .main-padding { padding: 1.5rem; }
    }
</style>
</head>
<body class="h-full bg-slate-50 text-slate-800">
<div id="app" class="h-full w-full"></div>

<script>
function checkAuth() {
    const userStr = sessionStorage.getItem('currentUser');
    if (!userStr) {
        const mockUser = { name: "Guest User", role: "Guest" };
        sessionStorage.setItem('currentUser', JSON.stringify(mockUser));
        return mockUser;
    }
    return JSON.parse(userStr);
}

let currentUser = checkAuth();
let currentView = sessionStorage.getItem('guest_currentView') || 'schedules';
let scheduleView = 'table';
let currentPage = 1;
let ITEMS_PER_PAGE = 15;
// allData holds ONLY approved schedules fetched from the API
let allData = [];
let allColleges = [];
let calendarDate = new Date();
let selectedDate = null;

let filters = { search: '', academicYear: '', courseCode: '', exam: '', yearLevel: '', campus: '', college: '', semester: '' };

// CAMPUSES — read from localStorage so any campus added in admin is reflected here automatically
const CAMPUSES = (() => {
    try {
        const saved = JSON.parse(localStorage.getItem('customCampuses'));
        return (Array.isArray(saved) && saved.length) ? saved : null;
    } catch(e) { return null; }
})() || ['Quezon City', 'Valenzuela', 'Antipolo', 'Pampanga', 'Laguna', 'Nueva Ecija'];
let showAdvancedFilters = true;

// ─── REAL-TIME POLLING ────────────────────────────────────────────────────────
let _pollInterval      = null;
let _pollPaused        = false;
let _lastPollAt        = null;
let _newlyApproved     = [];      // IDs of schedules approved since last render
let _isLive            = false;   // true once first successful poll lands
const POLL_MS          = 15000;   // poll every 15 seconds

async function _fetchApprovedSchedules() {
    const r = await fetch('../api/schedules.php?action=list');
    if (!r.ok) throw new Error(`HTTP ${r.status}`);
    const json = await r.json();
    const raw  = json.data || json || [];
    return raw.filter(s => (s.status || '').toLowerCase() === 'approved');
}

function _startPolling() {
    if (_pollInterval) return;               // already running
    _pollInterval = setInterval(_poll, POLL_MS);
}

function _stopPolling() {
    if (_pollInterval) { clearInterval(_pollInterval); _pollInterval = null; }
}

async function _poll() {
    if (_pollPaused) return;
    try {
        const fresh = await _fetchApprovedSchedules();
        _lastPollAt = new Date();

        // Compare by id to detect newly approved or removed items
        const oldIds   = new Set(allData.map(s => String(s.id || s.schedule_id || '')));
        const freshIds = new Set(fresh.map(s => String(s.id || s.schedule_id || '')));

        const added   = fresh.filter(s => {
            const sid = String(s.id || s.schedule_id || '');
            return sid && !oldIds.has(sid);
        });
        const removed = allData.filter(s => {
            const sid = String(s.id || s.schedule_id || '');
            return sid && !freshIds.has(sid);
        });

        if (added.length > 0 || removed.length > 0) {
            _newlyApproved = added.map(s => String(s.id || s.schedule_id || ''));
            allData = fresh;
            _isLive = true;
            _updateLiveIndicator();
            renderApp();                     // re-render with new data
            if (added.length > 0) {
                showToast(
                    added.length === 1
                        ? `📋 1 new exam schedule is now available!`
                        : `📋 ${added.length} new exam schedules are now available!`,
                    'success'
                );
            }
            if (removed.length > 0) {
                showToast(
                    removed.length === 1
                        ? `🗑️ 1 exam schedule has been removed.`
                        : `🗑️ ${removed.length} exam schedules have been removed.`,
                    'info'
                );
            }
            // Clear highlight after 8 seconds
            setTimeout(() => { _newlyApproved = []; _updateLiveIndicator(); }, 8000);
        } else {
            // Data unchanged — just refresh the live dot & timestamp
            allData = fresh;
            _isLive = true;
            _updateLiveIndicator();
        }
    } catch (err) {
        console.warn('[FLEXAM] Poll failed:', err.message);
        _isLive = false;
        _updateLiveIndicator();
    }
}

function _updateLiveIndicator() {
    const dot  = document.getElementById('live-dot');
    const txt  = document.getElementById('live-text');
    const badge = document.getElementById('live-new-badge');
    if (dot) {
        dot.className = _isLive
            ? 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0'
            : 'w-2 h-2 rounded-full bg-slate-300 shrink-0';
    }
    if (txt) {
        if (_lastPollAt) {
            const h = _lastPollAt.getHours().toString().padStart(2,'0');
            const m = _lastPollAt.getMinutes().toString().padStart(2,'0');
            const s = _lastPollAt.getSeconds().toString().padStart(2,'0');
            txt.textContent = `Live · updated ${h}:${m}:${s}`;
        } else {
            txt.textContent = _isLive ? 'Live' : 'Connecting…';
        }
    }
    if (badge) {
        if (_newlyApproved.length > 0) {
            badge.textContent = `+${_newlyApproved.length} New`;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

function formatDate(raw) {
    if (!raw || raw === '—') return '—';
    const d = new Date(raw.substring(0, 10) + 'T00:00:00');
    if (isNaN(d.getTime())) return raw;
    return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function showToast(message, type = 'success') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = `toast-notification fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 fade-in ${
        type === 'success' ? 'bg-[#047857] text-white' : type === 'error' ? 'bg-red-500 text-white' : 'bg-amber-500 text-white'
    }`;
    toast.innerHTML = `<div class="flex items-center gap-2">${type === 'success' ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' : ''}<span>${escapeHtml(message)}</span></div>`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ─── PDF EXPORT ───────────────────────────────────────────────────────────────
function exportToPDF() {
    // Apply the same filters used in renderSchedules()
    let filtered = [...allData];

    if (filters.search) {
        const q = filters.search.toLowerCase();
        filtered = filtered.filter(s =>
            (s.courseCode   || s.course_code   || '').toLowerCase().includes(q) ||
            (s.courseName   || s.course_name   || '').toLowerCase().includes(q) ||
            (s.roomName     || s.room_name     || '').toLowerCase().includes(q)
        );
    }
    if (filters.academicYear) filtered = filtered.filter(s => (s.academicYear || s.academic_year) === filters.academicYear);
    if (filters.courseCode)   filtered = filtered.filter(s => (s.courseCode   || s.course_code)   === filters.courseCode);
    if (filters.exam)         filtered = filtered.filter(s => (s.examType     || s.exam_type)     === filters.exam);
    if (filters.yearLevel)    filtered = filtered.filter(s => (s.yearLevel    || s.year_level)    === filters.yearLevel);
    if (filters.college)      filtered = filtered.filter(s => (s.college || '').toLowerCase() === filters.college.toLowerCase());
    if (filters.semester)     filtered = filtered.filter(s => (s.semester || s.semester_name || '').toLowerCase() === filters.semester.toLowerCase());
    if (filters.campus)       filtered = filtered.filter(s => (s.campus || '').toLowerCase() === filters.campus.toLowerCase());

    if (filtered.length === 0) { showToast('No schedules to export', 'warning'); return; }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');

    doc.setFontSize(18); doc.setTextColor(4, 120, 87);
    doc.text('FLEXAM - Exam Schedules' + (filters.exam ? ' (' + filters.exam + ')' : ''), 14, 18);
    doc.setFontSize(9); doc.setTextColor(100);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 25);
    doc.text('Our Lady of Fatima University', 14, 30);

    const activeFilters = [];
    if (filters.search)       activeFilters.push(`Search: "${filters.search}"`);
    if (filters.campus)       activeFilters.push(`Campus: ${filters.campus}`);
    if (filters.college)      activeFilters.push(`College: ${filters.college}`);
    if (filters.semester)     activeFilters.push(`Semester: ${filters.semester}`);
    if (filters.exam)         activeFilters.push(`Type: ${filters.exam}`);
    if (filters.yearLevel)    activeFilters.push(`Year Level: ${filters.yearLevel}`);
    if (filters.courseCode)   activeFilters.push(`Course: ${filters.courseCode}`);
    if (activeFilters.length) {
        doc.setFontSize(8);
        doc.setTextColor(100, 116, 139);
        doc.text('Filters: ' + activeFilters.join('  |  '), 14, 36);
    }

    const startY = activeFilters.length ? 42 : 35;

    doc.autoTable({
        head: [['Course Code', 'Course Name', 'Semester', 'Section', 'College', 'Program', 'Exam Type', 'Date', 'Time', 'Room', 'Campus']],
        body: filtered.map(s => [
            s.courseCode  || s.course_code  || '—',
            s.courseName  || s.course_name  || '—',
            s.semester    || s.semester_name|| '—',
            `${s.yearLevel || s.year_level || ''} ${s.section || ''}`.trim() || '—',
            s.college     || '—',
            s.program     || s.college_program || '—',
            s.examType    || s.exam_type    || '—',
            (() => { const raw = s.examDate || s.exam_date || ''; if (!raw || raw === '0000-00-00') return '—'; try { const d = new Date(raw.substring(0,10)+'T00:00:00'); return isNaN(d.getTime()) ? '—' : d.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'2-digit',year:'numeric'}); } catch(e) { return raw || '—'; } })(),
            `${s.examTime || s.time_start || ''} - ${s.endTime || s.time_end || ''}`.trim().replace(/^-|-$/, '').trim() || (s.time_slot || '—'),
            s.is_online ? 'Online' : (s.roomName || s.room_name || s.room || '—'),
            s.campus      || '—',
        ]),
        startY,
        theme: 'grid',
        styles: { fontSize: 7, cellPadding: 2.5 },
        headStyles: { fillColor: [4, 120, 87], textColor: 255, fontStyle: 'bold', fontSize: 7 },
        alternateRowStyles: { fillColor: [236, 253, 245] },
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
    });

    doc.save(`exam-schedules-${new Date().toISOString().split('T')[0]}.pdf`);
    showToast('PDF exported successfully!');
}


var _flexAnnCache = [];   // live cache – updated by FlexAnn polling every 5 s
var _guestCampus  = sessionStorage.getItem('guest_campus') || '';  // selected campus filter

// ── Announcements: real-time polling + BroadcastChannel ─────────────────────
// Polls every 10 s so new announcements appear without a page refresh.
// The BroadcastChannel gives instant updates when an admin acts in the same browser.
// The Page Visibility API skips polls while the tab is hidden and catches up on focus.
function _fetchAnnouncements() {
    fetch('../api/announcements.php?action=list&campus=all')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var fresh = data.data || [];
            // Only re-render the banner if something actually changed
            var sig = function(arr) {
                return arr.map(function(a) { return a.id + '|' + (a.active ? 1 : 0) + '|' + (a.updated_at || ''); }).join(',');
            };
            if (sig(fresh) === sig(_flexAnnCache)) return;
            _flexAnnCache = fresh;
            _refreshAnnouncementBanner();
        })
        .catch(function() { /* network error — silently ignore */ });
}

// Re-render the banner into whatever container is currently in the DOM.
function _refreshAnnouncementBanner() {
    var el = document.getElementById('announcement-banner-container');
    if (el) el.innerHTML = guestRenderAnnouncementBanner(currentView);
}

// Fetch once immediately on page load
_fetchAnnouncements();

// Poll every 10 seconds (skip while tab is hidden)
setInterval(function() {
    if (!document.hidden) _fetchAnnouncements();
}, 10000);

// Catch up instantly when user switches back to this tab
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) _fetchAnnouncements();
});

// Instant update when an admin posts/edits/deletes in the same browser
try {
    var _annChannel = new BroadcastChannel('flexam_announcements_v3');
    _annChannel.onmessage = function(e) {
        if (e.data && e.data.type === 'refresh') _fetchAnnouncements();
    };
} catch(e) { /* BroadcastChannel not supported */ }

/**
 * Build the announcement banner HTML for a given page view.
 * Called from inside the renderApp() template string.
 *
 * @param  {string} view  'all' | 'special_exam' | 'exam_schedule'
 * @return {string}       HTML string (empty string if no announcements)
 */
function guestRenderAnnouncementBanner(view) {
    var anns = _flexAnnCache.filter(function (a) {
        var isActive   = a.active == true || a.active === 1;
        var targetOk   = (a.target === 'all' || a.target === view);
        // Global announcements (campus is null/empty/'all') always show.
        // Campus-specific ones only show if the guest selected that campus.
        var isGlobal   = !a.campus || a.campus === '' || a.campus === 'all';
        var campusOk   = isGlobal || (_guestCampus && a.campus === _guestCampus);
        return isActive && targetOk && campusOk;
    });
    if (!anns.length) return '';

    // Pinned first, then chronological (server already orders by pinned DESC, created_at DESC)
    var pinned  = anns.filter(function (a) { return  a.pinned; });
    var regular = anns.filter(function (a) { return !a.pinned; });
    var ordered = pinned.concat(regular);

    var TYPE_STYLES = {
        info:    { emoji: 'ℹ️',  border: 'border-blue-200',    bg: 'bg-blue-50',    title: 'text-blue-800',    body: 'text-blue-700'    },
        warning: { emoji: '⚠️',  border: 'border-amber-200',   bg: 'bg-amber-50',   title: 'text-amber-800',   body: 'text-amber-700'   },
        success: { emoji: '✅',  border: 'border-emerald-200', bg: 'bg-emerald-50', title: 'text-emerald-800', body: 'text-emerald-700' },
        urgent:  { emoji: '🚨',  border: 'border-red-200',     bg: 'bg-red-50',     title: 'text-red-800',     body: 'text-red-700'     },
    };

    var html = '';
    ordered.forEach(function (ann) {
        var s     = TYPE_STYLES[ann.type] || TYPE_STYLES.info;
        var lines = (ann.body || '').split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
        var bodyHtml;
        if (lines.length > 1) {
            var items = lines.map(function (line) {
                var isNum = /^\d+\./.test(line);
                var text  = isNum ? line.replace(/^\d+\.\s*/, '') : line;
                return '<li class="flex items-start gap-2 ' + s.body + '">'
                     + '<span class="flex-shrink-0 font-bold">' + (isNum ? line.match(/^(\d+)/)[1] + '.' : '•') + '</span>'
                     + '<span>' + escapeHtml(text) + '</span>'
                     + '</li>';
            }).join('');
            bodyHtml = '<ol class="mt-1.5 space-y-1 text-xs leading-relaxed">' + items + '</ol>';
        } else {
            bodyHtml = '<p class="text-xs ' + s.body + ' mt-0.5 leading-relaxed">' + escapeHtml(ann.body || '') + '</p>';
        }
        var pinnedBadge = ann.pinned
            ? '<span class="flex-shrink-0 text-[10px] font-bold text-slate-400 mt-0.5">'
              + '<svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1a1 1 0 000-2H7a1 1 0 000 2h1v8l-2 2v2h5v5l1 1 1-1v-5h5v-2l-2-2z"/></svg>'
              + '</span>'
            : '';
        html += '<div class="flex items-start gap-3 border ' + s.border + ' ' + s.bg + ' rounded-xl px-4 py-3">'
              + '<span class="text-lg leading-none flex-shrink-0 mt-0.5">' + s.emoji + '</span>'
              + '<div class="flex-1 min-w-0">'
              + '<p class="text-sm font-bold ' + s.title + '">' + escapeHtml(ann.title) + '</p>'
              + bodyHtml
              + '<p class="text-[10px] text-slate-400 mt-1">Posted by ' + escapeHtml(ann.created_by || 'Admin') + '</p>'
              + '</div>'
              + pinnedBadge
              + '</div>';
    });
    return '<div class="mb-4 space-y-2 fade-in">' + html + '</div>';
}
// ── End System Announcements ─────────────────────────────────────────────────

function renderApp() {
    const app = document.getElementById('app');
    app.innerHTML = `
    <div class="h-full flex overflow-hidden">
        <div id="sidebarOverlay" onclick="closeSidebar()"></div>
        ${renderSidebar()}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            ${renderHeader()}
            <main class="flex-1 overflow-y-auto main-padding bg-slate-50 relative">${renderMainContent()}</main>
        </div>
    </div>`;
    attachMainHandlers();
    // Re-populate the announcement banner from cache after DOM rebuild.
    // guestRenderAnnouncementBanner() is called inside the template string
    // above using the cache at render time, so it is always current.
    // This extra call ensures the container is updated if the cache was
    // refreshed between the template evaluation and attachMainHandlers().
    _refreshAnnouncementBanner();
}

function renderSidebar() {
    return `
    <aside id="mainSidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col z-40 shrink-0 shadow-[2px_0_5px_rgba(0,0,0,0.02)] lg:relative">
        <div class="p-6">
            <div class="flex items-center gap-3">
                        <div class="shrink-0">
                            <img src="../Image/OLFU.png" alt="OLFU Logo" class="w-10 h-10 object-contain">
                        </div>
                <div>
                    <h1 class="font-bold text-slate-800 text-lg leading-tight">FLEXAM</h1>
                    <p class="text-[11px] text-slate-500 font-medium">Exam Scheduler</p>
                </div>
            </div>
        </div>
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto mt-2">
            ${[
                { id: 'schedules', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', label: 'Exam Schedules' },
                { id: 'special_exams', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', label: 'Special Exams' },
                { id: 'calendar', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', label: 'Calendar' },
                { id: 'feedback', icon: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z', label: 'Feedback' }
            ].map(item => `
                <button data-view="${item.id}" onclick="if(window.innerWidth<1024)closeSidebar()" class="sidebar-link w-full ${currentView === item.id ? 'active' : ''}">
                    <svg class="w-5 h-5 ${currentView === item.id ? 'text-[#047857]' : 'text-slate-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${item.icon}"/>
                    </svg>
                    <span>${item.label}</span>
                </button>
            `).join('')}
        </nav>
        <div class="p-4 mt-auto">
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 bg-slate-400 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-sm">
                        ${(currentUser.name || 'G').charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-700 truncate">${escapeHtml(currentUser.name || 'Guest User')}</p>
                        <span class="inline-block px-2 py-0.5 text-[10px] uppercase font-bold tracking-wide rounded-full bg-slate-200 text-slate-600">Guest</span>
                    </div>
                </div>
                <button id="logout-btn" class="w-full text-left text-xs font-medium text-red-500 hover:text-red-600 transition flex items-center gap-2 pl-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign Out
                </button>
            </div>
        </div>
    </aside>`;
}

function renderHeader() {
    const titles = { schedules: 'Exam Schedules', calendar: 'Exam Calendar', feedback: 'Feedback', special_exams: 'Special Exam Registration' };
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const todayStr = new Date().toLocaleDateString('en-US', dateOptions);
    return `
    <header class="top-header flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <button id="hamburger-btn" onclick="toggleSidebar()" class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg md:text-xl font-bold text-slate-900">${titles[currentView]}</h2>
                    <!-- Live new-schedule badge -->
                    <span id="live-new-badge" class="hidden inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500 text-white animate-pulse"></span>
                </div>
                <p class="text-xs md:text-sm text-slate-400 mt-0.5 hidden sm:block">Our Lady of Fatima University</p>
            </div>
        </div>
        <div class="flex items-center gap-3 md:gap-4">
            <span class="text-sm font-medium text-slate-500 hidden md:block">${todayStr}</span>
            <!-- Campus announcement filter -->
            <div class="flex items-center gap-1.5" title="Filter announcements by your campus">
                <svg class="w-4 h-4 text-slate-400 shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <select id="guest-campus-select"
                    class="text-sm border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-emerald-400 transition ${_guestCampus ? 'border-emerald-300 text-emerald-700' : ''}">
                    <option value="">All Campuses</option>
                    ${CAMPUSES
                        .map(c => `<option value="${c}" ${_guestCampus === c ? 'selected' : ''}>${c}</option>`)
                        .join('')}
                </select>
            </div>
            <!-- Live indicator pill -->
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-slate-200 bg-white shadow-sm" title="Schedules update automatically every 15 seconds">
                <span id="live-dot" class="${_isLive ? 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse' : 'w-2 h-2 rounded-full bg-slate-300'} shrink-0"></span>
                <span id="live-text" class="text-[11px] font-semibold text-slate-500 whitespace-nowrap">${_isLive && _lastPollAt ? (() => { const h=_lastPollAt.getHours().toString().padStart(2,'0'),m=_lastPollAt.getMinutes().toString().padStart(2,'0'),s=_lastPollAt.getSeconds().toString().padStart(2,'0'); return `Live · updated ${h}:${m}:${s}`; })() : 'Connecting…'}</span>
            </div>
        </div>
    </header>`;
}

function renderMainContent() {
    switch (currentView) {
        case 'schedules': return renderSchedules();
        case 'special_exams': return renderGuestSpecialExams();
        case 'calendar': return renderCalendar();
        case 'feedback': return renderFeedback();
        default: return renderSchedules();
    }
}

function renderGuestSpecialExams() {
    const cy = new Date().getFullYear();
    const defaultSY = `${cy}-${cy+1}`;
    const campuses = CAMPUSES;

    const collegeOpts = allColleges
        .reduce((acc, c) => {
            if (!c.code) return acc;
            const existing = acc.find(x => x.code === c.code);
            if (existing) {
                // Merge programs from every campus entry so none are lost
                const merged = new Set([...existing.programs, ...(c.programs || [])]);
                existing.programs = [...merged];
            } else {
                acc.push({ code: c.code, name: c.name||'', programs: [...(c.programs||[])] });
            }
            return acc;
        }, [])
        .sort((a,b) => a.code.localeCompare(b.code))
        .map(c => `<option value="${escapeHtml(c.code)}" data-programs='${JSON.stringify(c.programs)}'>${escapeHtml(c.code)}${c.name ? ' — '+escapeHtml(c.name) : ''}</option>`)
        .join('');

    return `
    <div class="fade-in max-w-4xl mx-auto text-left">
        <div id="announcement-banner-container">${guestRenderAnnouncementBanner('special_exam')}</div>
        <div class="bg-white rounded-xl border shadow-sm p-8">
            <div class="mb-7">
                <h3 class="text-xl font-bold text-slate-800">Special Exam Registration</h3>
                <p class="text-sm text-slate-500 mt-1">Fill in all required fields marked with *</p>
            </div>
            <div class="space-y-5">

                <!-- Section 1: Exam Details -->
                <div class="pb-1">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">1</span>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Exam Details</span>
                        <div class="flex-1 h-px bg-slate-100"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">School Year *</label>
                            <input id="spReg-school_year" type="text" placeholder="e.g. 2025-2026" value=""
                                oninput="spRegRebuildCourseSlots()"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Semester *</label>
                            <select id="spReg-semester" onchange="spRegRebuildCourseSlots()"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                                <option value="">Select Semester</option>
                                <option>1st Semester</option>
                                <option>2nd Semester</option>
                                <option>Summer</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Special Exam Type *</label>
                        <select id="spReg-exam_type" onchange="spRegExamTypeChanged(this)"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                            <option value="">Select Type</option>
                            <option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option>
                            <option value="Others">Others</option>
                        </select>
                        <div id="spReg-exam_type_other_wrap" style="display:none;margin-top:0.5rem">
                            <input type="text" id="spReg-exam_type_other" placeholder="Please specify exam type..."
                                class="w-full px-3 py-2.5 border border-purple-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Student Information -->
                <div class="pb-1">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">2</span>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Student Information</span>
                        <div class="flex-1 h-px bg-slate-100"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Student No. *</label>
                            <input type="text" id="spReg-student_no" placeholder="0000-000-0000" maxlength="12"
                                oninput="this.value=this.value.replace(/[^0-9-]/g,'')"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Last Name *</label>
                            <input type="text" id="spReg-last_name" placeholder="e.g. Dela Cruz"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">First Name *</label>
                            <input type="text" id="spReg-first_name" placeholder="e.g. Juan"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Campus *</label>
                        <select id="spReg-campus" onchange="spRegRebuildCourseSlots()"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                            <option value="">Select Campus</option>
                            ${campuses.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">College *</label>
                            <select id="spReg-college" onchange="spRegCollegeChanged(this)"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                                <option value="">Select College</option>
                                ${collegeOpts}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Program *</label>
                            <div id="spReg-programWrap">
                                <input id="spReg-program" type="text" value="" placeholder="Auto-filled from college"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 bg-slate-50 transition-shadow" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Registration Details -->
                <div class="pb-1">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">3</span>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Registration Details</span>
                        <div class="flex-1 h-px bg-slate-100"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Receipt No. *</label>
                        <input type="text" id="spReg-receipt_no" placeholder="e.g. OR-2024-00456"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Reason for Special Exam *</label>
                        <select id="spReg-reason-select" onchange="spRegReasonChanged(this)"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                            <option value="">— Select Reason —</option>
                            <option value="Personal Reason">Personal Reason</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Failure to attend on time">Failure to attend on time</option>
                            <option value="Others">Others</option>
                        </select>
                        <div id="spReg-reason-other-wrap" style="display:none;margin-top:0.5rem">
                            <input type="text" id="spReg-reason-other" placeholder="Please specify your reason..."
                                class="w-full px-3 py-2.5 border border-purple-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                        </div>
                    </div>
                </div>

                <!-- Section 4: Course Selection -->
                <div class="pb-1">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs font-bold">4</span>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Course Selection</span>
                        <div class="flex-1 h-px bg-slate-100"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">No. of Exams *</label>
                        <select id="spReg-num_exams" onchange="spRegNumExamsChanged(this)"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400 transition-shadow">
                            ${[1,2,3,4,5].map(n=>`<option value="${n}">${n}</option>`).join('')}
                        </select>
                    </div>
                    <div class="mt-4">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Select Courses for Special Exam</label>
                        <div id="spReg-coursesWrap" class="space-y-2 border border-slate-200 rounded-xl p-3 bg-slate-50"></div>
                        <p class="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Courses are filtered by School Year, Semester, College, and Program.
                        </p>
                    </div>
                </div>

                <div id="spExamFormError" class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-xl px-4 py-3"></div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-1">
                    <button id="spReg-submit-btn" onclick="submitGuestSpecialExam()"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-2.5 rounded-lg font-semibold text-sm transition-colors flex items-center gap-2 shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Register Student
                    </button>
                    <p class="text-xs text-slate-400">Fields marked <span class="text-red-500">*</span> are required</p>
                </div>
            </div>
        </div>
    </div>`;
}


function spRegExamTypeChanged(sel) {
    const wrap = document.getElementById('spReg-exam_type_other_wrap');
    if (wrap) wrap.style.display = sel.value === 'Others' ? 'block' : 'none';
}

function spRegReasonChanged(sel) {
    const wrap = document.getElementById('spReg-reason-other-wrap');
    if (wrap) wrap.style.display = sel.value === 'Others' ? 'block' : 'none';
}

// ── Rebuild the Program field: dropdown if multiple programs, readonly input if one/none ──
function spRegRenderProgramField() {
    const wrap   = document.getElementById('spReg-programWrap');
    const colSel = document.getElementById('spReg-college');
    if (!wrap || !colSel) return;
    const opt = colSel.options[colSel.selectedIndex];
    let programs = [];
    try { programs = JSON.parse(opt?.getAttribute('data-programs') || '[]'); } catch(e) {}

    // Strip "ACRONYM=Full Name" into { acronym, label } pairs
    const parsed = programs.map(p => {
        const eqIdx  = p.indexOf('=');
        const acronym = eqIdx !== -1 ? p.slice(0, eqIdx).trim() : p.trim();
        const label   = eqIdx !== -1 ? p.slice(eqIdx + 1).trim() : p.trim();
        return { acronym, label };
    });

    if (parsed.length > 1) {
        wrap.innerHTML = `<select id="spReg-program" onchange="spRegRebuildCourseSlots()"
            class="w-full px-3 py-2.5 border border-purple-400 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400">
            <option value="">— Select Program —</option>
            ${parsed.map(p => `<option value="${escapeHtml(p.acronym)}">${escapeHtml(p.acronym)}${p.label && p.label !== p.acronym ? ' — ' + escapeHtml(p.label) : ''}</option>`).join('')}
        </select>`;
    } else {
        const val = parsed[0]?.acronym || '';
        wrap.innerHTML = `<input id="spReg-program" type="text" value="${escapeHtml(val)}"
            placeholder="Auto-filled from college"
            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 bg-slate-50" readonly>`;
    }
    spRegRebuildCourseSlots();
}

function spRegCollegeChanged(sel) {
    const wrap = document.getElementById('spReg-programWrap');
    if (!sel.value) {
        if (wrap) wrap.innerHTML = `<input id="spReg-program" type="text" value="" placeholder="Auto-filled from college"
            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 bg-slate-50" readonly>`;
        spRegRebuildCourseSlots();
        return;
    }
    spRegRenderProgramField();
}

function spRegNumExamsChanged(sel) {
    spRegRebuildCourseSlots();
}

// ── Fetch courses from API filtered by SY + Semester + College + Program + Campus ──
async function spRegFetchCourses() {
    const sy      = (document.getElementById('spReg-school_year')?.value || '').trim();
    const sem     = (document.getElementById('spReg-semester')?.value    || '').trim();
    const college = (document.getElementById('spReg-college')?.value     || '').trim();
    const program = (document.getElementById('spReg-program')?.value     || '').trim();
    const campus  = (document.getElementById('spReg-campus')?.value      || '').trim();
    const params  = new URLSearchParams({ action: 'filter' });
    if (sy)      params.set('school_year', sy);
    if (sem)     params.set('semester',    sem);
    if (college) params.set('college',     college);
    if (program) params.set('program',     program);
    if (campus)  params.set('campus',      campus);
    try {
        const res  = await fetch(`../api/courses.php?${params.toString()}`);
        const json = await res.json();
        return json.success ? (json.data || []) : [];
    } catch(e) { console.error('spReg course filter error:', e); return []; }
}

// ── Rebuild course slots — identical style to admin (numbered div + single course select) ──
async function spRegRebuildCourseSlots() {
    const n    = parseInt(document.getElementById('spReg-num_exams')?.value || 1);
    const wrap = document.getElementById('spReg-coursesWrap');
    if (!wrap) return;

    const sy  = (document.getElementById('spReg-school_year')?.value || '').trim();
    const sem = (document.getElementById('spReg-semester')?.value    || '').trim();

    if (!sy) {
        wrap.innerHTML = `<p class="text-xs text-amber-500 text-center py-2">⚠ Please enter a <strong>School Year</strong> (e.g. 2025-2026) to load available courses.</p>`;
        return;
    }
    if (!sem) {
        wrap.innerHTML = `<p class="text-xs text-amber-500 text-center py-2">⚠ Please select a <strong>Semester</strong> to load available courses.</p>`;
        return;
    }

    const college = (document.getElementById('spReg-college')?.value || '').trim();
    const program = (document.getElementById('spReg-program')?.value || '').trim();

    if (!college) {
        wrap.innerHTML = `<p class="text-xs text-amber-500 text-center py-2">⚠ Please select a <strong>College</strong> to load available courses.</p>`;
        return;
    }

    wrap.innerHTML = `<p class="text-xs text-slate-400 text-center py-2 animate-pulse">Loading courses for ${escapeHtml(sy)} ${escapeHtml(sem)}…</p>`;

    const filteredCourses = await spRegFetchCourses();
    wrap.innerHTML = '';

    if (filteredCourses.length === 0) {
        const ctx = [sy, sem, college, program].filter(Boolean).join(' · ');
        wrap.innerHTML = `<p class="text-xs text-slate-400 text-center py-2">No courses found for <strong>${escapeHtml(ctx)}</strong>${college ? '' : '. Ensure courses are tagged with this School Year and Semester.'}.</p>`;
        return;
    }

    // Filter label (same as admin)
    const filterLabel = document.createElement('p');
    filterLabel.className = 'text-[10px] text-purple-600 font-semibold mb-1 px-1';
    filterLabel.textContent = `Showing ${filteredCourses.length} course(s) — ${sy} ${sem}${college ? ' · ' + college : ''}${program ? ' · ' + program : ''}`;
    wrap.appendChild(filterLabel);

    for (let i = 0; i < n; i++) {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-[auto_1fr] gap-2 items-center';
        row.innerHTML = `<span class="text-xs font-bold text-slate-400 w-5 text-center">${i+1}</span>
            <select class="sp-reg-course-sel px-2 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400 w-full">
                <option value="">— Select Course —</option>
                ${filteredCourses
                    .sort((a,b) => (a.course_code||'').localeCompare(b.course_code||''))
                    .map(c => {
                        const id    = c.id || c.course_id;
                        const code  = escapeHtml(c.course_code || '');
                        const name  = escapeHtml(c.course_name || '');
                        const label = code + (name ? ' — ' + name : '');
                        return `<option value="${id}" data-label="${label}">${code}${name ? ' — ' + name : ''}</option>`;
                    }).join('')}
            </select>`;
        wrap.appendChild(row);
    }
}

async function submitGuestSpecialExam() {
    const g = id => (document.getElementById(id)?.value || '').trim();
    const school_year   = g('spReg-school_year');
    const semester      = g('spReg-semester');
    const campus        = g('spReg-campus');
    const exam_type_raw = g('spReg-exam_type');
    const exam_type     = exam_type_raw === 'Others' ? g('spReg-exam_type_other') : exam_type_raw;
    const student_no    = g('spReg-student_no');
    const last_name     = g('spReg-last_name');
    const first_name    = g('spReg-first_name');
    const college       = g('spReg-college');
    const program       = g('spReg-program');
    const receipt_no    = g('spReg-receipt_no');
    const num_exams     = parseInt(document.getElementById('spReg-num_exams')?.value) || 1;
    const reason_select = g('spReg-reason-select');
    const reason        = reason_select === 'Others' ? g('spReg-reason-other') : reason_select;

    const errEl     = document.getElementById('spExamFormError');
    const successEl = document.getElementById('spExamSuccessMsg');
    const hideErr = () => { if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; } };
    const showErr = msg => { if (errEl) { errEl.textContent = msg; errEl.classList.remove('hidden'); errEl.scrollIntoView({ behavior:'smooth', block:'nearest' }); } };
    hideErr();
    if (successEl) successEl.classList.add('hidden');

    if (!school_year) return showErr('Please enter the school year.');
    if (!semester)    return showErr('Please select a semester.');
    if (!exam_type)   return showErr('Please select or specify the special exam type.');
    if (!student_no)  return showErr('Please enter your student number.');
    if (!last_name)   return showErr('Please enter your last name.');
    if (!first_name)  return showErr('Please enter your first name.');
    if (!campus)      return showErr('Please select your campus.');
    if (!college)     return showErr('Please select your college.');
    if (!receipt_no)  return showErr('Please enter your receipt number.');
    if (!reason_select) return showErr('Please select a reason for the special exam.');
    if (reason_select === 'Others' && !reason) return showErr('Please specify your reason.');

    const btn = document.getElementById('spReg-submit-btn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Submitting...'; }

    // Collect course selections from admin-style div slots
    const examCourses = [...document.querySelectorAll('#spReg-coursesWrap .sp-reg-course-sel')]
        .map(sel => {
            const opt = sel.options[sel.selectedIndex];
            return { course_id: sel.value, course_label: opt?.dataset?.label || opt?.text || '' };
        })
        .filter(e => e.course_id);

    try {
        const res = await fetch('../api/special_exams.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ school_year, semester, campus, exam_type, student_no, last_name, first_name, college, program, receipt_no, num_exams: Number(num_exams), reason, exam_courses: JSON.stringify(examCourses) })
        });
        const data = await res.json();
        if (data.success) {
            // Reset all fields
            ['spReg-semester','spReg-campus','spReg-exam_type','spReg-exam_type_other',
             'spReg-student_no','spReg-last_name','spReg-first_name','spReg-college','spReg-receipt_no','spReg-reason-select','spReg-reason-other']
             .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
            const sySel = document.getElementById('spReg-school_year');
            if (sySel) { const cy = new Date().getFullYear(); sySel.value = `${cy}-${cy+1}`; }
            // Reset program field to readonly placeholder
            const progWrap = document.getElementById('spReg-programWrap');
            if (progWrap) progWrap.innerHTML = `<input id="spReg-program" type="text" value="" placeholder="Select a college first" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 bg-slate-50" readonly>`;
            // Reset exam type other wrap
            const etWrap = document.getElementById('spReg-exam_type_other_wrap');
            if (etWrap) etWrap.style.display = 'none';
            // Reset reason other wrap
            const rWrap = document.getElementById('spReg-reason-other-wrap');
            if (rWrap) rWrap.style.display = 'none';
            // Reset num_exams to 1 and rebuild slots with SY only
            const numSel = document.getElementById('spReg-num_exams');
            if (numSel) numSel.value = '1';
            // Clear course slots
            const coursesWrap = document.getElementById('spReg-coursesWrap');
            if (coursesWrap) coursesWrap.innerHTML = '<p class="text-xs text-amber-500 text-center py-2">\u26a0 Please enter a School Year and select a Semester to load available courses.</p>';
            // Show evidence popup (same style as campus admin)
            showGuestSpecialExamSuccessPopup(first_name, last_name);
        } else {
            showErr(data.message || 'Failed to submit registration. Please try again.');
        }
    } catch (err) {
        showErr('Network error. Please check your connection and try again.');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Submit Registration'; }
    }
}

function showGuestSpecialExamSuccessPopup(firstName, lastName) {
    const existing = document.getElementById('guestSpExamSuccessPopup');
    if (existing) existing.remove();
    const pop = document.createElement('div');
    pop.id = 'guestSpExamSuccessPopup';
    pop.className = 'fixed inset-0 bg-black/50 z-[300] flex items-center justify-center p-4';
    pop.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-amber-500 px-6 py-5 flex items-center gap-3">
            <div class="w-10 h-10 bg-white/25 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h3 class="text-white font-bold text-lg">Registration Successful!</h3>
                <p class="text-amber-100 text-xs mt-0.5">Important reminder before your exam</p>
            </div>
        </div>
        <div class="px-6 py-5 space-y-4">
            <p class="text-slate-700 text-sm font-medium">
                <span class="font-bold text-slate-900">${escapeHtml(firstName)} ${escapeHtml(lastName)}</span> has been successfully registered for a special examination.
            </p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-2">
                <p class="text-xs font-bold text-amber-700 uppercase tracking-wide">⚠️ You Must Present Valid Evidence to the Proctor</p>
                <p class="text-xs text-slate-600">You are required to present <strong>at least one</strong> of the following during the exam:</p>
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
            <button onclick="document.getElementById('guestSpExamSuccessPopup').remove()" class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-lg transition text-sm">Understood</button>
        </div>
    </div>`;
    document.body.appendChild(pop);
    showToast('✅ Special exam registration saved!', 'success');
}

function renderSchedules() {
    let filtered = [...allData];

    // 1. Filtering Logic
    if (filters.search) {
        const search = filters.search.toLowerCase();
        filtered = filtered.filter(s =>
            ((s.course_code || s.courseCode || '').toLowerCase().includes(search)) ||
            ((s.course_name || s.courseName || '').toLowerCase().includes(search)) ||
            ((s.room_name   || s.roomName   || '').toLowerCase().includes(search))
        );
    }
    
    // Add the Campus Filter Logic
    if (filters.campus) {
        filtered = filtered.filter(s => s.campus === filters.campus);
    }
    
    if (filters.academicYear) filtered = filtered.filter(s => (s.academic_year || s.academicYear) === filters.academicYear);
    if (filters.courseCode)   filtered = filtered.filter(s => (s.course_code   || s.courseCode)   === filters.courseCode);
    if (filters.exam)         filtered = filtered.filter(s => (s.exam_type     || s.examType)     === filters.exam);
    if (filters.yearLevel)    filtered = filtered.filter(s => (s.year_level    || s.yearLevel)    === filters.yearLevel);
    if (filters.college)      filtered = filtered.filter(s => (s.college || '').toLowerCase() === filters.college.toLowerCase());
    if (filters.semester)     filtered = filtered.filter(s => (s.semester || s.semester_name || '').toLowerCase() === filters.semester.toLowerCase());

    const courseCodes = [...new Set(allData.map(s => s.course_code || s.courseCode).filter(Boolean))].sort();
    const _dataYears  = allData.map(s => s.year_level || s.yearLevel).filter(Boolean);
    const _storedYears = (() => { try { return JSON.parse(localStorage.getItem('customYearLevels')) || []; } catch(e) { return []; } })();
    const yearLevels  = [...new Set([..._storedYears, ..._dataYears])].filter(Boolean);
    const semesters   = ['1st Semester', '2nd Semester', 'Summer'];
    const campuses = CAMPUSES;

    return `
    <div class="fade-in space-y-4 max-w-7xl mx-auto text-left">
        <div id="announcement-banner-container">${guestRenderAnnouncementBanner('exam_schedule')}</div>
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <div class="flex gap-2">
                <button id="btn-table-view" class="${scheduleView === 'table' ? 'bg-[#047857] text-white' : 'bg-white'} px-3 py-2 rounded-lg text-sm border font-medium">Table View</button>
            </div>
            <div class="flex gap-2">
                <button id="toggle-filter-panel" class="lg:hidden flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm border bg-white text-slate-600 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                    Filters
                </button>
                <button id="export-pdf" class="bg-[#047857] text-white px-4 py-2 rounded-lg text-sm shadow-sm hover:bg-[#035f45] transition font-medium">Export PDF</button>
            </div>
        </div>

        <div class="bg-white rounded-xl border shadow-sm">
            <div class="p-4">
            <div id="filter-panel" class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3">
                <input type="text" id="filter-search" placeholder="Search code/room..." class="border rounded-lg p-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none" value="${escapeHtml(filters.search)}">
                
                <select id="filter-campus" class="border rounded-lg p-2 text-sm bg-white outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Campuses</option>
                    ${campuses.map(c => `<option value="${c}" ${filters.campus === c ? 'selected' : ''}>${c}</option>`).join('')}
                </select>

                <select id="filter-college" class="border rounded-lg p-2 text-sm bg-white outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Colleges</option>
                    ${allColleges
                        .reduce((acc, c) => {
                            if (c.code && !acc.find(x => x.code === c.code)) acc.push({ code: c.code, name: c.name || '' });
                            return acc;
                        }, [])
                        .sort((a, b) => a.code.localeCompare(b.code))
                        .map(c => `<option value="${c.code}" ${filters.college === c.code ? 'selected' : ''}>${c.code}${c.name ? ' — ' + c.name : ''}</option>`).join('')}
                </select>

                <select id="filter-semester" class="border rounded-lg p-2 text-sm bg-white outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Semesters</option>
                    ${semesters.map(s => `<option value="${escapeHtml(s)}" ${filters.semester === s ? 'selected' : ''}>${escapeHtml(s)}</option>`).join('')}
                </select>

                <select id="filter-course-code" class="border rounded-lg p-2 text-sm bg-white outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Courses</option>
                    ${courseCodes.map(c => `<option value="${c}" ${filters.courseCode === c ? 'selected' : ''}>${c}</option>`).join('')}
                </select>

                <select id="filter-exam" class="border rounded-lg p-2 text-sm bg-white outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Exams</option>
                    <option value="Prelim" ${filters.exam === 'Prelim' ? 'selected' : ''}>Prelim</option>
                    <option value="Midterm" ${filters.exam === 'Midterm' ? 'selected' : ''}>Midterm</option>
                    <option value="Final" ${filters.exam === 'Final' ? 'selected' : ''}>Final</option>
                    <option value="Summer" ${filters.exam === 'Summer' ? 'selected' : ''}>Summer</option>
                </select>

                <select id="filter-year-level" class="border rounded-lg p-2 text-sm bg-white outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Levels</option>
                    ${yearLevels.map(y => `<option value="${y}" ${filters.yearLevel === y ? 'selected' : ''}>${y}</option>`).join('')}
                </select>
            </div>
            </div>
            <div class="flex items-start justify-between px-4 pb-3 pt-1 gap-3 flex-wrap">
                <div class="flex items-start gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 max-w-xl">
                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>To export <strong>only your schedule</strong>, please apply your filters first (e.g. College, Semester, Year Level, Course) before clicking <strong>Export PDF</strong>. Exporting without filters will include all schedules.</span>
                </div>
                <button id="clear-filters" class="text-xs text-slate-500 hover:text-red-500 font-medium transition flex items-center gap-1 shrink-0">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear All Filters
                </button>
            </div>
        </div>

        ${scheduleView === 'table' ? (() => {
            const totalItems  = filtered.length;
            const totalPages  = Math.max(1, Math.ceil(totalItems / ITEMS_PER_PAGE));
            // Clamp currentPage in case filters reduced total pages
            if (currentPage > totalPages) currentPage = totalPages;
            const pageStart   = (currentPage - 1) * ITEMS_PER_PAGE;
            const pageEnd     = Math.min(pageStart + ITEMS_PER_PAGE, totalItems);
            const paginated   = filtered.slice(pageStart, pageEnd);

            // Build page number buttons (show up to 5 around current)
            const buildPageNums = () => {
                if (totalPages <= 1) return '';
                let nums = '';
                let start = Math.max(1, currentPage - 2);
                let end   = Math.min(totalPages, start + 4);
                if (end - start < 4) start = Math.max(1, end - 4);
                if (start > 1) nums += `<button data-page="1" class="pagination-btn px-3 py-1.5 rounded-lg text-sm border border-slate-200 bg-white text-slate-600 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 transition font-medium">1</button>`;
                if (start > 2) nums += `<span class="px-1 text-slate-400 text-sm">…</span>`;
                for (let p = start; p <= end; p++) {
                    nums += `<button data-page="${p}" class="pagination-btn px-3 py-1.5 rounded-lg text-sm border transition font-medium ${p === currentPage ? 'bg-[#047857] text-white border-[#047857] shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700'}">${p}</button>`;
                }
                if (end < totalPages - 1) nums += `<span class="px-1 text-slate-400 text-sm">…</span>`;
                if (end < totalPages) nums += `<button data-page="${totalPages}" class="pagination-btn px-3 py-1.5 rounded-lg text-sm border border-slate-200 bg-white text-slate-600 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 transition font-medium">${totalPages}</button>`;
                return nums;
            };

            return `
        <div class="bg-white rounded-xl border overflow-hidden shadow-sm">
            ${_newlyApproved.length > 0 ? `
            <div class="flex items-center gap-2 px-6 py-3 bg-emerald-50 border-b border-emerald-100">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                <span class="text-xs font-semibold text-emerald-700">${_newlyApproved.length} newly approved schedule${_newlyApproved.length > 1 ? 's' : ''} just appeared — highlighted below</span>
            </div>` : ''}
            <div class="flex items-center justify-between px-6 py-3 border-b border-slate-100 bg-slate-50/60">
                <p class="text-xs text-slate-500 font-medium">
                    ${totalItems === 0
                        ? 'No schedules found'
                        : `Showing <span class="font-semibold text-slate-700">${pageStart + 1}–${pageEnd}</span> of <span class="font-semibold text-slate-700">${totalItems}</span> schedule${totalItems !== 1 ? 's' : ''}`}
                </p>
                <div class="flex items-center gap-1">
                    <label class="text-xs text-slate-400 mr-1 hidden sm:inline">Per page:</label>
                    <select id="items-per-page-sel" class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        <option value="10" ${ITEMS_PER_PAGE===10?'selected':''}>10</option>
                        <option value="15" ${ITEMS_PER_PAGE===15?'selected':''}>15</option>
                        <option value="25" ${ITEMS_PER_PAGE===25?'selected':''}>25</option>
                        <option value="50" ${ITEMS_PER_PAGE===50?'selected':''}>50</option>
                    </select>
                </div>
            </div>
            <table class="resp-table w-full">
                <thead class="bg-slate-50 border-b">
                    <tr class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4 text-left">Course</th>
                        <th class="px-6 py-4 text-left">College / Program</th>
                        <th class="px-6 py-4 text-left">Section</th>
                        <th class="px-6 py-4 text-left">Semester</th>
                        <th class="px-6 py-4 text-left">Level</th>
                        <th class="px-6 py-4 text-left">Exam Type</th>
                        <th class="px-6 py-4 text-left">Schedule</th>
                        <th class="px-6 py-4 text-left">Building</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    ${paginated.length > 0 ? paginated.map(s => {
                        const sid = String(s.id || s.schedule_id || '');
                        const isNew = sid && _newlyApproved.includes(sid);
                        return `
                        <tr class="${isNew ? 'bg-emerald-50 border-l-4 border-l-emerald-500' : 'hover:bg-slate-50/50'} transition">
                            <td class="px-6 py-4" data-label="Course">
                                <div class="flex items-center gap-2">
                                    ${isNew ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500 text-white uppercase tracking-wide shrink-0">NEW</span>' : ''}
                                    <div>
                                        <div class="font-bold text-slate-800">${escapeHtml(s.course_code || s.courseCode || '—')}</div>
                                        <div class="text-[11px] text-slate-500">${escapeHtml(s.course_name || s.courseName || '—')}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4" data-label="College / Program">
                                <div class="font-medium text-slate-700">${escapeHtml(s.college || '—')}</div>
                                ${s.program ? `<div class="text-[11px] text-slate-400">${escapeHtml(s.program)}</div>` : ''}
                            </td>
                            <td class="px-6 py-4" data-label="Section">
                                ${(s.section || s.section_name)
                                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-violet-50 text-violet-700 border border-violet-100">${escapeHtml(s.section || s.section_name)}</span>`
                                    : `<span class="text-slate-300">—</span>`}
                            </td>
                            <td class="px-6 py-4" data-label="Semester">
                                ${(s.semester || s.semester_name)
                                    ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">${escapeHtml(s.semester || s.semester_name)}</span>`
                                    : `<span class="text-slate-300">—</span>`}
                            </td>
                            <td class="px-6 py-4 text-slate-600" data-label="Level">${escapeHtml(s.year_level || s.yearLevel || '—')}</td>
                            <td class="px-6 py-4" data-label="Exam Type">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 uppercase">
                                    ${escapeHtml(s.exam_type || s.examType || '—')}
                                </span>
                            </td>
                            <td class="px-6 py-4" data-label="Schedule">
                                <div class="font-semibold text-slate-700">${formatDate(s.exam_date || s.examDate || '—')}</div>
                                <div class="text-xs text-slate-400">${escapeHtml(s.time_slot || s.timeSlot || '—')}</div>
                            </td>
                            <td class="px-6 py-4" data-label="Building">
                                ${(() => {
                                    if (s.is_online) {
                                        return `<div class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                            Online
                                        </div>
                                        ${s.campus ? '<div class="text-[10px] text-emerald-600 font-bold uppercase tracking-tight mt-1">' + escapeHtml(s.campus) + '</div>' : ''}`;
                                    }
                                    const raw = (s.room_name || s.roomName || '').trim();
                                    const idx = raw.indexOf(',');
                                    const rm  = idx !== -1 ? raw.slice(0, idx).trim() : raw;
                                    const bld = idx !== -1 ? raw.slice(idx + 1).trim() : (s.building || '');
                                    const display = bld && rm ? bld + ', ' + rm : (bld || rm || '—');
                                    return `<div class="font-medium text-slate-700">${escapeHtml(display)}</div>
                                    ${s.campus ? '<div class="text-[10px] text-emerald-600 font-bold uppercase tracking-tight">' + escapeHtml(s.campus) + '</div>' : ''}`;
                                })()}
                            </td>
                        </tr>`;
                    }).join('')
                    : `<tr><td colspan="8" class="py-20 text-center text-slate-400">No schedules found.</td></tr>`}
                </tbody>
            </table>
            ${totalPages > 1 ? `
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/40">
                <p class="text-xs text-slate-400 order-2 sm:order-1">Page ${currentPage} of ${totalPages}</p>
                <div class="flex items-center gap-1.5 order-1 sm:order-2 flex-wrap justify-center">
                    <button id="page-prev" ${currentPage === 1 ? 'disabled' : ''} class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm border border-slate-200 bg-white text-slate-600 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 transition font-medium disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:border-slate-200 disabled:hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        <span class="hidden sm:inline">Prev</span>
                    </button>
                    ${buildPageNums()}
                    <button id="page-next" ${currentPage === totalPages ? 'disabled' : ''} class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm border border-slate-200 bg-white text-slate-600 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-700 transition font-medium disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:border-slate-200 disabled:hover:text-slate-600">
                        <span class="hidden sm:inline">Next</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>` : ''}
        </div>`;
        })() : renderCalendarInline()}
    </div>`;
}

function renderCalendarInline() {
    const today = new Date();
    const currentMonth = calendarDate.getMonth();
    const currentYear  = calendarDate.getFullYear();
    const monthNames   = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const daysOfWeek   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const firstDay     = new Date(currentYear, currentMonth, 1);
    const lastDay      = new Date(currentYear, currentMonth + 1, 0);
    const days = [];
    for (let i = 0; i < firstDay.getDay(); i++) days.push({ day: '', isCurrentMonth: false });
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const hasExams = allData.some(s => (s.examDate || s.exam_date) === dateStr);
        days.push({ day: i, isCurrentMonth: true, isToday: i === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear(), hasExams, dateStr });
    }
    return `
    <div class="calendar-card">
        <div class="calendar-toolbar">
            <h3 class="text-2xl font-bold">${monthNames[currentMonth]} ${currentYear}</h3>
            <div class="flex gap-2">
                <button id="cal-prev" class="nav-arrow"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                <button id="cal-today" class="btn-today">Today</button>
                <button id="cal-next" class="nav-arrow"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
            </div>
        </div>
        <div class="grid grid-cols-7 border-b">${daysOfWeek.map(d => `<div class="py-4 text-center text-sm font-semibold text-slate-500 border-r last:border-r-0">${d}</div>`).join('')}</div>
        <div class="grid grid-cols-7">${days.map(d => `<div class="calendar-cell${d.hasExams ? ' has-exams' : ''}" ${d.hasExams ? `onclick="openGuestDayModal('${d.dateStr}')"` : ''}>${d.day ? `<span class="day-number ${d.isToday ? 'today' : ''}">${d.day}</span>${d.hasExams ? '<div class="flex justify-center mt-1"><div class="exam-dot"></div></div>' : ''}` : ''}</div>`).join('')}</div>
    </div>`;
}

function renderCalendar() {
    const now          = new Date();
    const currentMonth = calendarDate.getMonth();
    const currentYear  = calendarDate.getFullYear();
    const daysInMonth  = new Date(currentYear, currentMonth + 1, 0).getDate();
    const startDOW     = new Date(currentYear, currentMonth, 1).getDay();
    const monthNames   = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const dayNames     = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const colors       = ['bg-blue-500','bg-purple-500','bg-pink-500','bg-orange-500','bg-teal-500'];

    // Group schedules by day number
    const byDate = {};
    allData.forEach(s => {
        const raw = s.examDate || s.exam_date; if (!raw) return;
        const d = new Date(raw + 'T00:00:00');
        if (d.getMonth() === currentMonth && d.getFullYear() === currentYear) {
            const dk = d.getDate();
            if (!byDate[dk]) byDate[dk] = [];
            byDate[dk].push(s);
        }
    });

    // Monthly list (all schedules this month)
    const monthlyScheds = allData.filter(s => {
        const raw = s.examDate || s.exam_date || '';
        return raw.startsWith(`${currentYear}-${String(currentMonth + 1).padStart(2, '0')}`);
    });

    let html = `
    <div class="fade-in max-w-7xl mx-auto text-left">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm" style="overflow:hidden;">
            <div class="p-4 md:p-6 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-lg md:text-xl font-bold text-slate-900">${monthNames[currentMonth]} ${currentYear}</h2>
                <div class="flex items-center gap-2 md:gap-3">
                    <button id="cal-prev" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                    <button id="cal-today" class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium hover:bg-emerald-100 transition">Today</button>
                    <button id="cal-next" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
                </div>
            </div>
            <div style="overflow-x:auto;">
            <div class="calendar-grid" style="min-width:560px;">
                ${dayNames.map(d => `<div class="p-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider bg-slate-50 border-l border-b border-slate-200">${d}</div>`).join('')}`;

    for (let i = 0; i < startDOW; i++) html += `<div class="calendar-cell bg-slate-50"></div>`;

    for (let day = 1; day <= daysInMonth; day++) {
        const isToday  = day === now.getDate() && currentMonth === now.getMonth() && currentYear === now.getFullYear();
        const ds       = byDate[day] || [];
        const dateStr  = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const hasScheds = ds.length > 0;
        html += `<div class="calendar-cell ${isToday ? 'bg-emerald-50' : ''} ${hasScheds ? 'cursor-pointer' : ''} transition-colors"
            ${hasScheds ? `onclick="openGuestDayModal('${dateStr}')"` : ''}>
            <div class="flex justify-between items-start mb-1">
                <span class="text-sm font-semibold ${isToday ? 'text-emerald-600' : 'text-slate-700'}">${day}</span>
                ${isToday ? '<span class="text-[10px] bg-emerald-600 text-white px-2 py-0.5 rounded-full font-bold">Today</span>' : ''}
            </div>
            ${ds.slice(0, 3).map((s, i) => `<div class="event-bar ${colors[i % colors.length]}" title="${escapeHtml(s.courseName || s.course_name || 'Exam')}">${escapeHtml(s.courseName || s.course_name || s.courseCode || s.course_code || 'Exam')}</div>`).join('')}
            ${ds.length > 3 ? `<div class="text-[9px] text-slate-400 font-semibold mt-0.5 pl-1">+${ds.length - 3} more</div>` : ''}
        </div>`;
    }

    html += `</div></div>
            <div class="p-4 md:p-6 border-t border-slate-200">
                <h3 class="font-bold text-slate-800 mb-4 text-sm md:text-base">Schedules This Month</h3>
                ${monthlyScheds.length === 0
                    ? '<p class="text-slate-400 text-sm text-center py-4">No schedules this month.</p>'
                    : `<div class="space-y-2 max-h-64 overflow-y-auto">${monthlyScheds.map(s => `
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                            <div>
                                <h4 class="font-semibold text-sm text-slate-800">${escapeHtml(s.courseName || s.course_name || s.courseCode || s.course_code || '—')}</h4>
                                <p class="text-xs text-slate-500">${escapeHtml(s.examType || s.exam_type || '—')} · ${formatDate(s.examDate || s.exam_date || '—')}</p>
                            </div>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Approved</span>
                        </div>`).join('')}</div>`}
            </div>
        </div>
    </div>`;

    return html;
}

// ── Day Detail Modal ──────────────────────────────────────────────────────────
window.openGuestDayModal = function(dateStr) {
    const existing = document.getElementById('guestDayModal');
    if (existing) existing.remove();

    const [y, m, d] = dateStr.split('-').map(Number);
    const dateObj    = new Date(y, m - 1, d);
    const dayNames   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const label      = `${dayNames[dateObj.getDay()]}, ${monthNames[m-1]} ${d}, ${y}`;

    const scheds = allData
        .filter(s => (s.examDate || s.exam_date || '') === dateStr)
        .sort((a, b) => (a.timeSlot || a.time_slot || '').localeCompare(b.timeSlot || b.time_slot || ''));

    const statusBadge = status => {
        const map = { Approved: 'bg-emerald-100 text-emerald-700', Pending: 'bg-amber-100 text-amber-700', Rejected: 'bg-red-100 text-red-700' };
        return map[status] || 'bg-slate-100 text-slate-600';
    };

    const accentColors = ['bg-blue-500','bg-purple-500','bg-pink-500','bg-orange-500','bg-teal-500','bg-indigo-500'];

    const cards = scheds.length === 0
        ? `<div class="flex flex-col items-center justify-center py-14 text-slate-400">
               <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
               <p class="font-semibold text-slate-500">No schedules on this day</p>
           </div>`
        : scheds.map((s, i) => {
            const courseName  = escapeHtml(s.courseName  || s.course_name  || s.courseCode || s.course_code || 'Course');
            const courseCode  = escapeHtml(s.courseCode  || s.course_code  || '');
            const examType    = escapeHtml(s.examType    || s.exam_type    || 'Exam');
            const timeSlot    = escapeHtml(s.timeSlot    || s.time_slot    || '');
            const roomName    = escapeHtml(s.roomName    || s.room_name    || '');
            const building    = escapeHtml(s.building    || '');
            const campus      = escapeHtml(s.campus      || '');
            const section     = escapeHtml(s.section     || s.section_name || '');
            const yearLevel   = escapeHtml(s.yearLevel   || s.year_level   || '');
            const college     = escapeHtml(s.college     || '');
            const program     = escapeHtml(s.program     || '');
            const status      = s.status || 'Approved';
            const roomDisplay = roomName ? (building ? `${building} — ${roomName}${campus ? ' (' + campus + ')' : ''}` : roomName) : '';
            return `
            <div class="flex gap-0 rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="w-1.5 shrink-0 ${accentColors[i % accentColors.length]}"></div>
                <div class="flex-1 p-4">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div>
                            <p class="font-bold text-slate-800 text-sm">${courseName}</p>
                            <p class="text-xs text-slate-500 mt-0.5">${examType}${yearLevel ? ' · ' + yearLevel : ''}${section ? ' · Section ' + section : ''}</p>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full ${statusBadge(status)}">${status}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        ${timeSlot ? `<div class="flex items-center gap-2 text-xs text-slate-600">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>${timeSlot}</span>
                        </div>` : ''}
                        ${roomDisplay ? `<div class="flex items-center gap-2 text-xs text-slate-600">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1"/></svg>
                            <span>${roomDisplay}</span>
                        </div>` : ''}
                        ${courseCode ? `<div class="flex items-center gap-2 text-xs text-slate-600">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span>${courseCode}</span>
                        </div>` : ''}
                        ${(college || program) ? `<div class="flex items-center gap-2 text-xs text-slate-600">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 13c0 5.523-4.477 10-9 10S3 18.523 3 13c0-.538.054-1.064.157-1.572L9.84 14.578 12 14z"/></svg>
                            <span>${college}${college && program ? ' · ' : ''}${program}</span>
                        </div>` : ''}
                        ${campus ? `<div class="flex items-center gap-2 text-xs text-slate-600">
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>${campus}</span>
                        </div>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');

    const modal = document.createElement('div');
    modal.id        = 'guestDayModal';
    modal.className = 'fixed inset-0 z-[500] flex items-center justify-center p-4 bg-black/50';
    modal.style.animation = 'fadeIn .18s ease';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col overflow-hidden" style="animation:slideUp .22s ease">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-5 bg-emerald-700 text-white shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold leading-tight">${label}</h2>
                    <p class="text-xs text-emerald-200 mt-0.5">${scheds.length} exam${scheds.length !== 1 ? 's' : ''} scheduled</p>
                </div>
            </div>
            <button onclick="document.getElementById('guestDayModal').remove()" class="w-8 h-8 rounded-lg bg-white/20 hover:bg-white/30 flex items-center justify-center transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-3">
            ${cards}
        </div>
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 shrink-0 flex justify-end">
            <button onclick="document.getElementById('guestDayModal').remove()" class="px-6 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-bold rounded-xl transition">Close</button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

function renderFeedback() {
    // Build campus → colleges map from allColleges
    const campusMap = {};
    allColleges.forEach(c => {
        const camp = (c.campus || '').trim();
        if (!campusMap[camp]) campusMap[camp] = [];
        campusMap[camp].push(c);
    });
    // Derive campus list: prefer known order, add any extras from data
    const knownCampuses = CAMPUSES;
    const dataCampuses  = Object.keys(campusMap).filter(Boolean);
    const campusList    = [...new Set([...knownCampuses, ...dataCampuses])];

    return `
    <div class="fade-in max-w-4xl mx-auto text-left">
        <div class="bg-white rounded-xl border shadow-sm p-8">
            <div class="mb-7">
                <h3 class="text-xl font-bold text-slate-800">Send Feedback</h3>
                <p class="text-sm text-slate-500 mt-1">Your feedback helps us improve exam scheduling for everyone.</p>
            </div>
            <form id="feedback-form" class="space-y-5" novalidate>

                <!-- Row 1: Student Number + Name -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Student Number</label>
                        <input type="text" id="feedback-studentNumber" placeholder="e.g., 2021-00001"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Name <span class="text-slate-400 text-xs font-normal">(optional)</span></label>
                        <input type="text" id="feedback-name" placeholder="Your full name or leave blank"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Campus / College / Program block -->
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 space-y-4">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Campus &amp; Department</p>

                    <!-- Campus -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Campus <span class="text-red-500">*</span></label>
                        <select id="feedback-campus"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">— Select your campus —</option>
                            ${campusList.map(c => `<option value="${c}">${c}</option>`).join('')}
                        </select>
                    </div>

                    <!-- College + Program side by side -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">College <span class="text-red-500">*</span></label>
                            <select id="feedback-college"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed"
                                disabled>
                                <option value="">— Select campus first —</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Program <span class="text-red-500">*</span></label>
                            <select id="feedback-program"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed"
                                disabled>
                                <option value="">— Select college first —</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Category <span class="text-red-500">*</span></label>
                    <select id="feedback-category" onchange="onFeedbackCategoryChange(this)"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">— Select a category —</option>
                        <option value="Scheduling">Scheduling Issues</option>
                        <option value="Room Problems">Room Problems</option>
                        <option value="System Bugs">System Bugs</option>
                        <option value="Feature Request">Feature Request</option>
                        <option value="Exam Difficulty">Exam Difficulty</option>
                        <option value="General">General Feedback</option>
                    </select>
                    <!-- Exam Difficulty sub-selector (shown only when Exam Difficulty is chosen) -->
                    <div id="exam-difficulty-wrap" style="display:none;" class="mt-3">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Difficulty Level <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label id="diff-opt-30" class="diff-option flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-amber-400 transition-all">
                                <input type="radio" name="exam_difficulty" value="30% - Medium Difficult" class="hidden diff-radio">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-700">30%</span>
                                    <span class="text-xs text-amber-600 font-medium">Medium Difficult</span>
                                </div>
                                <span class="ml-auto text-lg">😐</span>
                            </label>
                            <label id="diff-opt-50" class="diff-option flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-orange-400 transition-all">
                                <input type="radio" name="exam_difficulty" value="50% - Difficult" class="hidden diff-radio">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-700">50%</span>
                                    <span class="text-xs text-orange-600 font-medium">Difficult</span>
                                </div>
                                <span class="ml-auto text-lg">😓</span>
                            </label>
                            <label id="diff-opt-100" class="diff-option flex items-center gap-3 p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-red-400 transition-all">
                                <input type="radio" name="exam_difficulty" value="100% - Very Difficult" class="hidden diff-radio">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-700">100%</span>
                                    <span class="text-xs text-red-600 font-medium">Very Difficult</span>
                                </div>
                                <span class="ml-auto text-lg">😱</span>
                            </label>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Select how difficult the exam was for you.</p>
                    </div>
                </div>

                <!-- Subject -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Subject <span class="text-red-500">*</span></label>
                    <input type="text" id="feedback-subject" placeholder="Brief description of your feedback"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                </div>

                <!-- Message -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Message <span class="text-red-500">*</span></label>
                    <textarea id="feedback-message" rows="4" placeholder="Describe your feedback in detail..."
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none text-sm"></textarea>
                </div>

                <!-- Submit row -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 pt-1">
                    <button type="submit" id="feedback-submit-btn"
                        class="bg-[#047857] hover:bg-[#065f46] text-white px-8 py-2.5 rounded-lg font-semibold text-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Submit Feedback
                    </button>
                    <p class="text-xs text-slate-400">Fields marked <span class="text-red-500">*</span> are required</p>
                </div>
            </form>
        </div>
    </div>`;
}

// ─── EXAM DIFFICULTY CATEGORY HANDLER ────────────────────────────────────────
function onFeedbackCategoryChange(sel) {
    const wrap = document.getElementById('exam-difficulty-wrap');
    if (!wrap) return;
    if (sel.value === 'Exam Difficulty') {
        wrap.style.display = 'block';
        // Attach radio visual selection behaviour (idempotent)
        wrap.querySelectorAll('.diff-option').forEach(lbl => {
            lbl.onclick = () => {
                wrap.querySelectorAll('.diff-option').forEach(l => {
                    l.classList.remove('border-emerald-500','bg-emerald-50');
                    l.classList.add('border-slate-200');
                });
                lbl.classList.remove('border-slate-200');
                lbl.classList.add('border-emerald-500','bg-emerald-50');
                lbl.querySelector('.diff-radio').checked = true;
            };
        });
    } else {
        wrap.style.display = 'none';
        wrap.querySelectorAll('.diff-radio').forEach(r => r.checked = false);
        wrap.querySelectorAll('.diff-option').forEach(l => {
            l.classList.remove('border-emerald-500','bg-emerald-50');
            l.classList.add('border-slate-200');
        });
    }
}

// ─── EVENT HANDLERS ───────────────────────────────────────────────────────────
function toggleSidebar() {
    const s = document.getElementById('mainSidebar');
    const o = document.getElementById('sidebarOverlay');
    if (!s) return;
    s.classList.toggle('open');
    if (o) o.classList.toggle('open');
}
function closeSidebar() {
    const s = document.getElementById('mainSidebar');
    const o = document.getElementById('sidebarOverlay');
    if (s) s.classList.remove('open');
    if (o) o.classList.remove('open');
}

function attachMainHandlers() {
    document.querySelectorAll('[data-view]').forEach(btn => btn.onclick = () => { currentView = btn.dataset.view; sessionStorage.setItem('guest_currentView', currentView); renderApp(); });
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) logoutBtn.onclick = () => { sessionStorage.removeItem('currentUser'); window.location.href = '../login.php'; };

    // Campus selector — persists across page views via sessionStorage
    const campusSel = document.getElementById('guest-campus-select');
    if (campusSel) campusSel.onchange = () => {
        _guestCampus = campusSel.value;
        sessionStorage.setItem('guest_campus', _guestCampus);
        renderApp();
    };
    const btnTable = document.getElementById('btn-table-view');
    if (btnTable) btnTable.onclick = () => { scheduleView = 'table'; renderApp(); };
    const toggleF = document.getElementById('toggle-filters');
    if (toggleF) toggleF.onclick = () => { showAdvancedFilters = !showAdvancedFilters; renderApp(); };
    const toggleFP = document.getElementById('toggle-filter-panel');
    if (toggleFP) {
        const panel = document.getElementById('filter-panel');
        if (panel) {
            panel.style.maxHeight = panel.scrollHeight + 'px';
            toggleFP.onclick = () => {
                const isCollapsed = panel.classList.toggle('collapsed');
                toggleFP.querySelector('svg + span, span') && null;
                const txt = toggleFP.childNodes;
                toggleFP.setAttribute('aria-expanded', !isCollapsed);
                if (isCollapsed) { panel.style.maxHeight = '0'; }
                else { panel.classList.remove('collapsed'); panel.style.maxHeight = panel.scrollHeight + 'px'; }
            };
        }
    }
const clearF = document.getElementById('clear-filters');
    if (clearF) clearF.onclick = () => { 
        filters = { search:'', academicYear:'', courseCode:'', exam:'', yearLevel:'', campus:'', college:'', semester:'' };
        currentPage = 1;
        renderApp(); 
    };
    const btnPrev = document.getElementById('cal-prev');
    if (btnPrev) btnPrev.onclick = () => { calendarDate.setMonth(calendarDate.getMonth() - 1); renderApp(); };
    const btnNext = document.getElementById('cal-next');
    if (btnNext) btnNext.onclick = () => { calendarDate.setMonth(calendarDate.getMonth() + 1); renderApp(); };
    const btnToday = document.getElementById('cal-today');
    if (btnToday) btnToday.onclick = () => { calendarDate = new Date(); renderApp(); };
    const expPdf = document.getElementById('export-pdf');
    if (expPdf) expPdf.onclick = exportToPDF;

    // Filter change handlers — map element IDs to filter keys
    const filterMap = {
        'filter-search':        'search',
        'filter-campus':        'campus',
        'filter-college':       'college',
        'filter-semester':      'semester',
        'filter-academic-year': 'academicYear',
        'filter-course-code':   'courseCode',
        'filter-exam':          'exam',
        'filter-year-level':    'yearLevel'
    };

    let _searchDebounce = null;
    Object.entries(filterMap).forEach(([id, key]) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.onchange = (e) => { filters[key] = e.target.value; currentPage = 1; renderApp(); };
        if (key === 'search') el.oninput = (e) => {
            filters.search = e.target.value;
            currentPage = 1;
            clearTimeout(_searchDebounce);
            _searchDebounce = setTimeout(() => renderApp(), 300);
        };
    });

    // ── Pagination controls ──────────────────────────────────────────────────
    const pagePrev = document.getElementById('page-prev');
    if (pagePrev) pagePrev.onclick = () => { if (currentPage > 1) { currentPage--; renderApp(); window.scrollTo({top: 0, behavior: 'smooth'}); } };

    const pageNext = document.getElementById('page-next');
    if (pageNext) pageNext.onclick = () => { currentPage++; renderApp(); window.scrollTo({top: 0, behavior: 'smooth'}); };

    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.onclick = () => { currentPage = parseInt(btn.dataset.page); renderApp(); window.scrollTo({top: 0, behavior: 'smooth'}); };
    });

    const ippSel = document.getElementById('items-per-page-sel');
    if (ippSel) ippSel.onchange = (e) => {
        ITEMS_PER_PAGE = parseInt(e.target.value);
        currentPage = 1;
        renderApp();
    };

    // ── Feedback: campus → college cascade, program cascade, submit ──────────
    const form = document.getElementById('feedback-form');
    if (form) {
        // Campus → populate College dropdown
        const campusSel  = document.getElementById('feedback-campus');
        const collegeSel = document.getElementById('feedback-college');
        const programSel = document.getElementById('feedback-program');

        function resetSelect(el, placeholder) {
            el.innerHTML = `<option value="">${placeholder}</option>`;
            el.disabled = true;
        }

        if (campusSel) campusSel.onchange = () => {
            const chosenCampus = campusSel.value;
            resetSelect(collegeSel, '— Select college —');
            resetSelect(programSel, '— Select college first —');

            if (!chosenCampus) return;

            // Filter colleges for this campus
            const cols = allColleges.filter(c => (c.campus || '').trim() === chosenCampus);
            if (cols.length === 0) {
                // No colleges on record for this campus — still enable so user can proceed
                collegeSel.innerHTML = '<option value="">No colleges on record</option>';
                collegeSel.disabled = false;
                return;
            }
            cols.sort((a, b) => (a.code || '').localeCompare(b.code || ''));
            collegeSel.innerHTML = '<option value="">— Select college —</option>' +
                cols.map(c => `<option value="${c.code}" data-programs='${JSON.stringify(c.programs || [])}'>${c.code}${c.name ? ' — ' + c.name : ''}</option>`).join('');
            collegeSel.disabled = false;
        };

        if (collegeSel) collegeSel.onchange = () => {
            resetSelect(programSel, '— Select program —');
            const opt = collegeSel.options[collegeSel.selectedIndex];
            if (!opt || !opt.value) return;

            let programs = [];
            try { programs = JSON.parse(opt.getAttribute('data-programs') || '[]'); } catch(e) {}

            if (programs.length === 0) {
                programSel.innerHTML = '<option value="">No programs on record</option>';
                programSel.disabled = false;
                return;
            }
            // programs may be "CODE=Full Name" or just "CODE"
            programSel.innerHTML = '<option value="">— Select program —</option>' +
                programs.map(p => {
                    const parts = p.split('=');
                    const code  = parts[0].trim();
                    const label = parts[1] ? `${code} — ${parts[1].trim()}` : code;
                    return `<option value="${code}">${label}</option>`;
                }).join('');
            programSel.disabled = false;
        };

        form.onsubmit = async (e) => {
            e.preventDefault();
            const campus        = (document.getElementById('feedback-campus')?.value  || '').trim();
            const college       = (document.getElementById('feedback-college')?.value || '').trim();
            const program       = (document.getElementById('feedback-program')?.value || '').trim();
            const name          = document.getElementById('feedback-name').value.trim();
            const studentNumber = document.getElementById('feedback-studentNumber').value.trim();
            const category      = document.getElementById('feedback-category').value;
            const subject       = document.getElementById('feedback-subject').value.trim();
            const message       = document.getElementById('feedback-message').value.trim();

            // Exam difficulty (only required when category is Exam Difficulty)
            let examDifficulty = '';
            if (category === 'Exam Difficulty') {
                const checkedRadio = document.querySelector('input[name="exam_difficulty"]:checked');
                examDifficulty = checkedRadio ? checkedRadio.value : '';
                if (!examDifficulty) { showToast('Please select a difficulty level.', 'error'); return; }
            }

            if (!campus)   { showToast('Please select your campus.',   'error'); return; }
            if (!college)  { showToast('Please select your college.',  'error'); return; }
            if (!program)  { showToast('Please select your program.',  'error'); return; }
            if (!category) { showToast('Please select a category.',    'error'); return; }
            if (!subject)  { showToast('Please enter a subject.',      'error'); return; }
            if (!message)  { showToast('Please enter your message.',   'error'); return; }

            const submitBtn = document.getElementById('feedback-submit-btn');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Submitting...'; }
            try {
                const res = await fetch('../api/feedbacks.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_number: studentNumber, student_name: name || 'Anonymous', campus, college, program, category, subject, message, exam_difficulty: examDifficulty })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Feedback submitted successfully!');
                    form.reset();
                    // Reset cascades
                    if (collegeSel) { resetSelect(collegeSel, '— Select campus first —'); }
                    if (programSel) { resetSelect(programSel, '— Select college first —'); }
                    // Reset exam difficulty
                    const diffWrap = document.getElementById('exam-difficulty-wrap');
                    if (diffWrap) {
                        diffWrap.style.display = 'none';
                        diffWrap.querySelectorAll('.diff-radio').forEach(r => r.checked = false);
                        diffWrap.querySelectorAll('.diff-option').forEach(l => { l.classList.remove('border-emerald-500','bg-emerald-50'); l.classList.add('border-slate-200'); });
                    }
                } else {
                    showToast(data.message || 'Failed to submit feedback.', 'error');
                }
            } catch (err) {
                showToast('Network error. Please try again.', 'error');
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Submit Feedback'; }
            }
        };
    }
}

// ─── BOOTSTRAP ────────────────────────────────────────────────────────────────
// Show a loading state immediately
document.getElementById('app').innerHTML = `
    <div class="h-full flex items-center justify-center">
        <div class="text-center">
            <div class="w-10 h-10 border-4 border-[#047857] border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
            <p class="text-slate-500 text-sm font-medium">Loading schedules...</p>
        </div>
    </div>`;

// ─── FIX 1: Changed Promise.all → Promise.allSettled ─────────────────────────
// Original used Promise.all, which means if the colleges API fails (e.g. because
// it requires a logged-in session), the ENTIRE bootstrap throws and allData is
// never populated — even when the schedules API works fine.
// Promise.allSettled lets each request succeed or fail independently.
Promise.allSettled([
    fetch('../api/colleges.php?action=list').then(r => {
        if (!r.ok) throw new Error(`Colleges API responded with HTTP ${r.status}`);
        return r.json();
    }).catch(() => ({ data: [] })),
    fetch('../api/schedules.php?action=list').then(r => {
        if (!r.ok) throw new Error(`Schedules API responded with HTTP ${r.status}`);
        return r.json();
    }).catch(() => ({ data: [] })),
    fetch('../api/special_exams.php?action=list').then(r => {
        if (!r.ok) throw new Error(`Special Exams API responded with HTTP ${r.status}`);
        return r.json();
    }).catch(() => ({ data: [] }))
]).then(([collegesResult, schedulesResult, spExResult]) => {

    // Handle colleges — use value if fulfilled, empty array if rejected
    if (collegesResult.status === 'fulfilled') {
        allColleges = collegesResult.value.data || [];
    } else {
        console.warn('[FLEXAM] Colleges API failed:', collegesResult.reason);
        allColleges = [];
    }

    // ─── FIX 3: Handle schedules result independently with diagnostics ────────
    if (schedulesResult.status === 'fulfilled') {
        // Support both { data: [...] } envelope and a plain array response
        const raw = schedulesResult.value.data || schedulesResult.value || [];

        // Filter to only 'Approved' — case-insensitive for safety
        allData = raw.filter(s => (s.status || '').toLowerCase() === 'approved');

        // ─── FIX 4: Console diagnostics so the root cause is visible in DevTools
        console.log(`[FLEXAM] ${raw.length} total schedules received, ${allData.length} are Approved.`);
        if (raw.length > 0 && allData.length === 0) {
            const statuses = [...new Set(raw.map(s => s.status))];
            console.warn('[FLEXAM] Schedules exist but none have status "Approved". Found statuses:', statuses);
        }
        if (raw.length === 0) {
            console.warn('[FLEXAM] Schedules API returned an empty array. Check that the API endpoint allows unauthenticated access for guests.');
        }
    } else {
        console.error('[FLEXAM] Schedules API failed:', schedulesResult.reason);
        allData = [];
    }

    // Mark live and record first poll time
    window._specialExams = (spExResult.status === 'fulfilled') ? (spExResult.value.data || []) : [];
    _isLive = true;
    _lastPollAt = new Date();

    renderApp();

    // ─── Start real-time polling ──────────────────────────────────────────────
    _startPolling();

    // Pause polling when tab is hidden to save resources, resume when visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            _pollPaused = true;
        } else {
            _pollPaused = false;
            // Immediately poll on tab focus so data is fresh right away
            _poll();
        }
    });

}).catch(err => {
    // Safety net for any unexpected error inside the .then() block itself
    console.error('[FLEXAM] Unexpected bootstrap error:', err);
    allData = [];
    allColleges = [];
    renderApp();
});
</script>
</body>
</html>