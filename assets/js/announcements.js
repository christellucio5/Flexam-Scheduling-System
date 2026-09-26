/**
 * FLEXAM – Shared Announcement Engine
 * File: js/announcements.js
 *
 * Drop this <script src="../js/announcements.js"></script> into every page,
 * then call FlexAnn.init(config) once the page has loaded.
 *
 * Config shape:
 * {
 *   apiBase   : '../api/announcements.php',   // path to announcements.php
 *   role      : 'superadmin' | 'campus_admin' | 'head' | 'guest',
 *   campus    : 'Quezon City',                // current user's campus ('' for superadmin)
 *   campuses  : ['QC', 'Valenzuela', ...],    // full campus list (superadmin only)
 *   onUpdate  : function(announcements) {},   // called whenever data refreshes
 * }
 */

(function (global) {
    'use strict';

    var _cfg        = {};
    var _channel    = null;        // BroadcastChannel (same-browser instant sync)
    var _cache      = [];
    var _initialLoadDone = false;

    // ── Utilities ──────────────────────────────────────────────────────────────
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = String(s == null ? '' : s);
        return d.innerHTML;
    }

    function apiFetch(params, method, body) {
        method = method || 'GET';
        var url = _cfg.apiBase + '?' + new URLSearchParams(params).toString();
        var opts = { method: method, headers: { 'Content-Type': 'application/json' }, credentials: 'include' };
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts).then(function (r) { return r.json(); });
    }

    // ── Public: initialise ─────────────────────────────────────────────────────
    function init(cfg) {
        _cfg = Object.assign({
            apiBase  : '../api/announcements.php',
            role     : 'guest',
            campus   : '',
            campuses : [],
            onUpdate : function () {},
        }, cfg);

        // Initial load — fetch once on page open
        _load();

        // BroadcastChannel: reloads only when an admin posts/edits/deletes
        // in another tab of the same browser. No timer polling at all.
        try {
            _channel = new BroadcastChannel('flexam_announcements_v3');
            _channel.onmessage = function (e) {
                if (e.data && e.data.type === 'refresh') _load();
            };
        } catch (e) { /* not supported */ }
    }

    // ── Load all announcements ─────────────────────────────────────────────────
    function _load() {
        var params = { action: 'list' };
        if (_cfg.campus !== undefined) params.campus = _cfg.campus;
        apiFetch(params).then(function (data) {
            if (data.success) {
                var prev  = _cache.slice();
                var fresh = data.data || [];

                // Only call onUpdate when data actually changed — avoids unnecessary DOM thrashing.
                var sig = function (arr) {
                    return arr.map(function (a) { return a.id + '|' + (a.active ? 1 : 0) + '|' + (a.updated_at || ''); }).join(',');
                };
                var changed = sig(fresh) !== sig(prev);

                _cache = fresh;

                // Always fire onUpdate on the very first load so the container
                // renders correctly (including an empty state) regardless of
                // whether there are announcements.
                // On subsequent loads, only re-render when data actually changed.
                if (!_initialLoadDone || changed) {
                    _initialLoadDone = true;
                    _cfg.onUpdate(_cache.slice());
                }

                if (_initialLoadDone && prev.length > 0) _notifyNew(prev, _cache);
            } else {
                console.error('[FlexAnn] list failed:', data.message);
                if (typeof _cfg.onError === 'function') _cfg.onError(data.message || 'Failed to load announcements.');
            }
        }).catch(function (err) {
            console.error('[FlexAnn] network error:', err);
            if (typeof _cfg.onError === 'function') _cfg.onError('Network error loading announcements.');
        });
    }

    // Show a subtle in-page toast when a new announcement arrives (viewer roles)
    function _notifyNew(prev, current) {
        if (_cfg.role === 'superadmin' || _cfg.role === 'campus_admin') return;
        var prevIds = prev.map(function (a) { return a.id; });
        var fresh   = current.filter(function (a) { return a.active && prevIds.indexOf(a.id) === -1; });
        if (!fresh.length) return;
        fresh.forEach(function (a) {
            _showAnnToast(a);
        });
    }

    function _showAnnToast(ann) {
        try {
            var tm   = TYPE_META[ann.type] || TYPE_META.info;
            var colors = { blue: '#3b82f6', amber: '#f59e0b', emerald: '#10b981', red: '#ef4444' };
            var color  = colors[tm.color] || colors.blue;
            var toast  = document.createElement('div');
            toast.style.cssText = [
                'position:fixed','bottom:24px','right:24px','z-index:9999',
                'max-width:340px','padding:14px 18px','border-radius:14px',
                'background:#1e293b','color:#f8fafc','font-family:inherit',
                'box-shadow:0 8px 32px rgba(0,0,0,0.25)','display:flex',
                'align-items:flex-start','gap:12px','animation:flexann-slide-in .3s ease',
                'border-left:4px solid ' + color,
            ].join(';');
            toast.innerHTML = '<div style="flex:1;min-width:0;">'
                + '<p style="margin:0;font-size:12px;font-weight:700;opacity:.7;text-transform:uppercase;letter-spacing:.05em;">New Announcement</p>'
                + '<p style="margin:4px 0 0;font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                + String(ann.title || '').replace(/</g,'&lt;')
                + '</p></div>'
                + '<button style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:18px;line-height:1;padding:0;margin-left:4px;flex-shrink:0;" onclick="this.closest(\'div[style]\').remove()">×</button>';
            if (!document.getElementById('_flexann_kf')) {
                var s = document.createElement('style');
                s.id  = '_flexann_kf';
                s.textContent = '@keyframes flexann-slide-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}';
                document.head.appendChild(s);
            }
            document.body.appendChild(toast);
            setTimeout(function () { if (toast.parentNode) toast.remove(); }, 6000);
        } catch (e) {}
    }

    // ── Broadcast to other same-browser tabs ────────────────────────────────────
    function _broadcast() {
        try {
            if (_channel) _channel.postMessage({ type: 'refresh' });
        } catch (e) {}
    }

    // ── Public: create announcement ────────────────────────────────────────────
    function create(payload) {
        return apiFetch({ action: 'create' }, 'POST', payload).then(function (data) {
            if (data.success) {
                // Small delay ensures the DB write is fully committed before
                // we re-fetch, preventing the previous list from flashing up.
                setTimeout(function () { _load(); _broadcast(); }, 150);
            }
            return data;
        });
    }

    // ── Public: update announcement ────────────────────────────────────────────
    function update(payload) {
        return apiFetch({ action: 'update' }, 'POST', payload).then(function (data) {
            if (data.success) {
                setTimeout(function () { _load(); _broadcast(); }, 150);
            }
            return data;
        });
    }

    // ── Public: delete announcement ────────────────────────────────────────────
    function remove(id) {
        return apiFetch({ action: 'delete' }, 'POST', { id: id }).then(function (data) {
            if (data.success) {
                setTimeout(function () { _load(); _broadcast(); }, 150);
            }
            return data;
        });
    }

    // ── Public: get cached list ────────────────────────────────────────────────
    function getCache() { return _cache.slice(); }

    // ── Render helpers (shared card HTML) ─────────────────────────────────────
    var TYPE_META = {
        info:    { label: 'Info',    color: 'blue',   icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
        warning: { label: 'Warning', color: 'amber',  icon: 'M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z' },
        success: { label: 'Notice',  color: 'emerald',icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
        urgent:  { label: 'Urgent',  color: 'red',    icon: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    };
    var TARGET_META = {
        all:           { label: 'All Pages',     icon: '📢' },
        special_exam:  { label: 'Special Exam',  icon: '📝' },
        exam_schedule: { label: 'Exam Schedule', icon: '📅' },
    };

    function colorClasses(color) {
        var map = {
            blue:    { bg: 'bg-blue-50',    border: 'border-blue-200',    text: 'text-blue-700',    dot: 'bg-blue-500'    },
            amber:   { bg: 'bg-amber-50',   border: 'border-amber-200',   text: 'text-amber-700',   dot: 'bg-amber-500'   },
            emerald: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', dot: 'bg-emerald-500' },
            red:     { bg: 'bg-red-50',     border: 'border-red-200',     text: 'text-red-700',     dot: 'bg-red-500'     },
        };
        return map[color] || map.blue;
    }

    /**
     * Render a viewer card (read-only, no edit button).
     * Used in head.php, guest.php, and the read-only section of campus_admin.php
     */
    function renderViewerCard(ann) {
        var tm  = TYPE_META[ann.type]   || TYPE_META.info;
        var tgt = TARGET_META[ann.target] || TARGET_META.all;
        var cl  = colorClasses(tm.color);
        var lines = (ann.body || '').split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
        var bodyHtml = lines.map(function (l) {
            return '<p class="text-xs text-slate-600 leading-relaxed">' + esc(l) + '</p>';
        }).join('');
        var campusTag = ann.campus
            ? '<span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-full">📍 ' + esc(ann.campus) + '</span>'
            : '<span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-full">📢 All Campuses</span>';
        var pinnedTag = ann.pinned
            ? '<span class="text-[10px] font-bold text-slate-400">📌 Pinned</span>'
            : '';
        return '<div class="rounded-xl border ' + cl.border + ' ' + cl.bg + ' p-4 space-y-2 shadow-sm">'
            + '<div class="flex items-start justify-between gap-2 flex-wrap">'
            +   '<div class="flex items-center gap-1.5 flex-wrap">'
            +     '<span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 bg-white border ' + cl.border + ' ' + cl.text + ' rounded-full">'
            +       '<svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + tm.icon + '"/></svg>'
            +       tm.label
            +     '</span>'
            +     '<span class="text-[10px] font-semibold text-slate-400">' + tgt.icon + ' ' + tgt.label + '</span>'
            +     campusTag
            +     pinnedTag
            +   '</div>'
            + '</div>'
            + '<p class="text-sm font-bold text-slate-800 leading-snug">' + esc(ann.title) + '</p>'
            + '<div class="space-y-1">' + bodyHtml + '</div>'
            + '<p class="text-[10px] text-slate-400">Posted by ' + esc(ann.created_by) + '</p>'
            + '</div>';
    }

    /**
     * Render an editable card row (for admin / campus admin manage panel).
     */
    function renderAdminCard(ann, opts) {
        opts = opts || {};
        var canEdit   = opts.canEdit   !== false;
        var canDelete = opts.canDelete !== false;
        var campuses  = opts.campuses  || [];
        var isSuperAdmin = opts.isSuperAdmin || false;

        var tm  = TYPE_META[ann.type]   || TYPE_META.info;
        var tgt = TARGET_META[ann.target] || TARGET_META.all;
        var cl  = colorClasses(tm.color);
        var campusTag = ann.campus
            ? '<span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-full">📍 ' + esc(ann.campus) + '</span>'
            : '<span class="inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-full">📢 All Campuses</span>';
        var lines = (ann.body || '').split('\n').map(function (l) { return l.trim(); }).filter(Boolean);
        var bodyHtml = lines.map(function (l) {
            return '<p class="text-xs text-slate-600 leading-relaxed">' + esc(l) + '</p>';
        }).join('');
        var editBtns = '';
        if (canEdit || canDelete) {
            editBtns = '<div class="flex items-center gap-1 flex-shrink-0">';
            if (canEdit) {
                editBtns += '<button data-ann-edit="' + ann.id + '" title="Edit" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">'
                    + '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</button>';
            }
            if (canDelete) {
                editBtns += '<button data-ann-delete="' + ann.id + '" title="Delete" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition">'
                    + '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                    + '</button>';
            }
            editBtns += '</div>';
        }
        return '<div class="rounded-xl border ' + cl.border + ' ' + cl.bg + ' p-4 space-y-2 shadow-sm" data-ann-id="' + ann.id + '">'
            + '<div class="flex items-start justify-between gap-2">'
            +   '<div class="flex items-center gap-1.5 flex-wrap">'
            +     '<span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 bg-white border ' + cl.border + ' ' + cl.text + ' rounded-full">'
            +       '<svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + tm.icon + '"/></svg>'
            +       tm.label
            +     '</span>'
            +     '<span class="text-[10px] font-semibold text-slate-400">' + tgt.icon + ' ' + tgt.label + '</span>'
            +     campusTag
            +     (ann.pinned ? '<span class="text-[10px] font-bold text-slate-400">📌 Pinned</span>' : '')
            +     (!ann.active ? '<span class="text-[10px] font-bold px-1.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-400 rounded-full">Inactive</span>' : '')
            +   '</div>'
            +   editBtns
            + '</div>'
            + '<p class="text-sm font-bold text-slate-800 leading-snug">' + esc(ann.title) + '</p>'
            + '<div class="space-y-1">' + bodyHtml + '</div>'
            + '<p class="text-[10px] text-slate-400">Posted by ' + esc(ann.created_by || 'Admin') + '</p>'
            + '</div>';
    }

    /**
     * Open the Create/Edit modal.
     * @param {object|null} existing - If null, opens for create.
     * @param {object} opts - { isSuperAdmin, campuses, campus, onSave }
     */
    function openEditModal(existing, opts) {
        opts = opts || {};
        var isEdit   = !!existing;
        var campuses = opts.campuses || [];
        var isSA     = opts.isSuperAdmin || false;

        existing = existing || { type: 'info', target: 'all', campus: 'all', title: '', body: '', pinned: false, active: true };

        var campusOpts = '';
        if (isSA) {
            campusOpts  = '<option value="all"' + (!existing.campus || existing.campus === 'all' ? ' selected' : '') + '>📢 All Campuses</option>';
            campuses.forEach(function (c) {
                campusOpts += '<option value="' + esc(c) + '"' + (existing.campus === c ? ' selected' : '') + '>' + esc(c) + '</option>';
            });
        }

        var modal = document.createElement('div');
        modal.id  = 'flexAnnEditModal';
        modal.setAttribute('style', [
            'position:fixed','inset:0','z-index:9999',
            'display:flex','align-items:center','justify-content:center',
            'padding:1rem','background:rgba(0,0,0,0.6)',
            'backdrop-filter:blur(4px)','-webkit-backdrop-filter:blur(4px)',
        ].join(';'));
        modal.innerHTML = ''
            + '<div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col" style="max-height:92vh;">'
            + '<div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-t-2xl flex items-center justify-between flex-shrink-0">'
            +   '<div>'
            +     '<h2 class="text-base font-bold text-white">' + (isEdit ? 'Edit Announcement' : 'New Announcement') + '</h2>'
            +     '<p class="text-emerald-200 text-xs mt-0.5">Fill in all required fields</p>'
            +   '</div>'
            +   '<button id="flexAnnModalClose" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/70 hover:text-white hover:bg-white/20 transition-colors">'
            +     '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round"/></svg>'
            +   '</button>'
            + '</div>'
            + '<div class="overflow-y-auto flex-1 px-6 py-5 space-y-4" style="scrollbar-width:thin;">'
            +   '<div>'
            +     '<label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Title *</label>'
            +     '<input id="flexAnnTitle" type="text" placeholder="Announcement title…" value="' + esc(existing.title) + '" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">'
            +   '</div>'
            +   '<div>'
            +     '<label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Body</label>'
            +     '<textarea id="flexAnnBody" rows="4" placeholder="One line per step or paragraph…" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-emerald-400">' + esc(existing.body) + '</textarea>'
            +   '</div>'
            +   '<div class="grid grid-cols-2 gap-3">'
            +     '<div>'
            +       '<label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Type</label>'
            +       '<select id="flexAnnType" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">'
            +         '<option value="info"'    + (existing.type === 'info'    ? ' selected' : '') + '>ℹ️ Info</option>'
            +         '<option value="warning"' + (existing.type === 'warning' ? ' selected' : '') + '>⚠️ Warning</option>'
            +         '<option value="success"' + (existing.type === 'success' ? ' selected' : '') + '>✅ Notice</option>'
            +         '<option value="urgent"'  + (existing.type === 'urgent'  ? ' selected' : '') + '>🚨 Urgent</option>'
            +       '</select>'
            +     '</div>'
            +     '<div>'
            +       '<label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Page Target</label>'
            +       '<select id="flexAnnTarget" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">'
            +         '<option value="all"'           + (existing.target === 'all'           ? ' selected' : '') + '>📢 All Pages</option>'
            +         '<option value="special_exam"'  + (existing.target === 'special_exam'  ? ' selected' : '') + '>📝 Special Exam</option>'
            +         '<option value="exam_schedule"' + (existing.target === 'exam_schedule' ? ' selected' : '') + '>📅 Exam Schedule</option>'
            +       '</select>'
            +     '</div>'
            +   '</div>'
            + (isSA
                ? '<div>'
                +   '<label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Campus Scope</label>'
                +   '<select id="flexAnnCampus" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400">' + campusOpts + '</select>'
                +   '<p class="text-[10px] text-slate-400 mt-1">Choose a campus or "All Campuses" to broadcast everywhere.</p>'
                + '</div>'
                : '')
            +   '<div class="flex items-center gap-6">'
            +     '<label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer font-medium">'
            +       '<input id="flexAnnPinned" type="checkbox"' + (existing.pinned ? ' checked' : '') + ' class="w-4 h-4 rounded accent-emerald-600"> Pin to top'
            +     '</label>'
            +     (isEdit
                    ? '<label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer font-medium">'
                    +   '<input id="flexAnnActive" type="checkbox"' + (existing.active ? ' checked' : '') + ' class="w-4 h-4 rounded accent-emerald-600"> Active'
                    + '</label>'
                    : '')
            +   '</div>'
            +   '<div id="flexAnnError" class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-xl px-4 py-3"></div>'
            + '</div>'
            + '<div class="px-6 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex-shrink-0 flex gap-3">'
            +   '<button id="flexAnnCancelBtn" class="flex-1 px-4 py-2.5 border border-slate-200 bg-white text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-100 transition">Cancel</button>'
            +   '<button id="flexAnnSaveBtn" class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold shadow-sm transition">' + (isEdit ? 'Save Changes' : 'Post Announcement') + '</button>'
            + '</div>'
            + '</div>';

        // Remove any existing modal before opening a new one
        var _old = document.getElementById('flexAnnEditModal');
        if (_old) _old.remove();

        document.body.appendChild(modal);
        modal.addEventListener('click', function (e) { if (e.target === modal) modal.remove(); });
        modal.querySelector('#flexAnnModalClose').onclick = function () { modal.remove(); };
        modal.querySelector('#flexAnnCancelBtn').onclick  = function () { modal.remove(); };

        modal.querySelector('#flexAnnSaveBtn').onclick = function () {
            var title  = modal.querySelector('#flexAnnTitle').value.trim();
            var body   = modal.querySelector('#flexAnnBody').value.trim();
            var type   = modal.querySelector('#flexAnnType').value;
            var target = modal.querySelector('#flexAnnTarget').value;
            var pinned = modal.querySelector('#flexAnnPinned').checked;
            var campus = isSA
                ? (modal.querySelector('#flexAnnCampus') ? modal.querySelector('#flexAnnCampus').value : 'all')
                : (opts.campus && opts.campus.trim() !== '' ? opts.campus.trim() : '');
            var active = isEdit ? (modal.querySelector('#flexAnnActive') ? modal.querySelector('#flexAnnActive').checked : true) : true;
            var errEl  = modal.querySelector('#flexAnnError');

            if (!title) {
                errEl.textContent = 'Title is required.';
                errEl.classList.remove('hidden');
                return;
            }
            errEl.classList.add('hidden');

            var btn = modal.querySelector('#flexAnnSaveBtn');
            btn.disabled = true;
            btn.textContent = 'Saving…';

            var payload = { type: type, target: target, campus: campus, title: title, body: body, pinned: pinned, active: active };
            var promise;
            if (isEdit) {
                payload.id = existing.id;
                promise = update(payload);
            } else {
                promise = create(payload);
            }

            promise.then(function (data) {
                if (data.success) {
                    modal.remove();
                    if (typeof opts.onSave === 'function') opts.onSave();
                } else {
                    errEl.textContent = data.message || 'Failed to save.';
                    errEl.classList.remove('hidden');
                    btn.disabled = false;
                    btn.textContent = isEdit ? 'Save Changes' : 'Post Announcement';
                }
            }).catch(function () {
                errEl.textContent = 'Network error. Please try again.';
                errEl.classList.remove('hidden');
                btn.disabled = false;
                btn.textContent = isEdit ? 'Save Changes' : 'Post Announcement';
            });
        };
    }

    // ── Public API ─────────────────────────────────────────────────────────────
    global.FlexAnn = {
        init          : init,
        create        : create,
        update        : update,
        remove        : remove,
        getCache      : getCache,
        reload        : _load,
        renderViewerCard : renderViewerCard,
        renderAdminCard  : renderAdminCard,
        openEditModal    : openEditModal,
        TYPE_META     : TYPE_META,
        TARGET_META   : TARGET_META,
    };

}(window));