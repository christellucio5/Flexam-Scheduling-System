/**
 * flexam-sdk.js  –  Fixed version
 * Place at: assets/js/flexam-sdk.js
 * Loaded from: Admin/admin.php  →  relative path to api/ is  ../api
 */

(function () {
    'use strict';

    // ── PATH FIX ─────────────────────────────────────────────
    // admin.php is at  /FLEXAM/Admin/admin.php
    // api/ is at       /FLEXAM/api/
    // So from admin.php, the relative path is  ../api
    const API_BASE = '../api';

    // ── Core fetch wrapper ────────────────────────────────────
    async function apiFetch(endpoint, body = null) {
        const url  = `${API_BASE}/${endpoint}`;
        const opts = {
            method      : body ? 'POST' : 'GET',
            credentials : 'same-origin',
            headers     : { 'Content-Type': 'application/json' },
        };
        if (body) opts.body = JSON.stringify(body);
        try {
            const res = await fetch(url, opts);
            // If response is not OK, try to parse error
            if (!res.ok) {
                let errMsg = `HTTP ${res.status}`;
                try { const j = await res.json(); errMsg = j.message || errMsg; } catch {}
                return { success: false, message: errMsg, data: [] };
            }
            return await res.json();
        } catch (err) {
            console.error('[flexamApi]', endpoint, err);
            return { success: false, message: String(err), data: [] };
        }
    }

    // ── flexamApi – per-resource CRUD ─────────────────────────
    window.flexamApi = {

        colleges: {
            list  : ()  => apiFetch('colleges.php?action=list'),
            delete: (id)=> apiFetch('colleges.php?action=delete', { id }),
            create: (d) => apiFetch('colleges.php?action=create', {
                name        : d.name        || '',
                code        : d.code        || '',
                description : d.description || '',
                programs    : Array.isArray(d.programs) ? d.programs : [],
            }),
            update: (d) => apiFetch('colleges.php?action=update', {
                id          : d.id,
                name        : d.name        || '',
                code        : d.code        || '',
                description : d.description || '',
                programs    : Array.isArray(d.programs) ? d.programs : [],
            }),
        },

        courses: {
            list  : ()  => apiFetch('courses.php?action=list'),
            delete: (id)=> apiFetch('courses.php?action=delete', { id }),
            create: (d) => apiFetch('courses.php?action=create', {
                courseCode : d.courseCode  || d.course_code  || '',
                courseName : d.courseName  || d.course_name  || '',
                college    : d.college     || '',
                program    : d.program     || '',
                yearLevel  : d.yearLevel   || d.year_level   || '',
                semester   : d.semester    || '',
            }),
            update: (d) => apiFetch('courses.php?action=update', {
                id         : d.id,
                courseCode : d.courseCode  || d.course_code  || '',
                courseName : d.courseName  || d.course_name  || '',
                college    : d.college     || '',
                program    : d.program     || '',
                yearLevel  : d.yearLevel   || d.year_level   || '',
                semester   : d.semester    || '',
            }),
        },

        rooms: {
            list      : ()  => apiFetch('rooms.php?action=list'),
            delete    : (id)=> apiFetch('rooms.php?action=delete',      { id }),
            toggleLock: (id)=> apiFetch('rooms.php?action=toggle_lock', { id }),
            create: (d) => apiFetch('rooms.php?action=create', {
                name     : d.name     || '',
                building : d.building || '',
                capacity : parseInt(d.capacity) || 0,
                floor    : d.floor    || '',
                locked   : d.locked   ? 1 : 0,
            }),
            update: (d) => apiFetch('rooms.php?action=update', {
                id       : d.id,
                name     : d.name     || '',
                building : d.building || '',
                capacity : parseInt(d.capacity) || 0,
                floor    : d.floor    || '',
                locked   : d.locked   ? 1 : 0,
            }),
        },

        proctors: {
            list  : ()  => apiFetch('proctors.php?action=list'),
            delete: (id)=> apiFetch('proctors.php?action=delete', { id }),
            create: (d) => apiFetch('proctors.php?action=create', {
                name           : d.name           || '',
                collegeProgram : d.collegeProgram  || d.college_program || '',
                email          : d.email           || '',
                phone          : d.phone           || '',
            }),
            update: (d) => apiFetch('proctors.php?action=update', {
                id             : d.id,
                name           : d.name           || '',
                collegeProgram : d.collegeProgram  || d.college_program || '',
                email          : d.email           || '',
                phone          : d.phone           || '',
            }),
        },

        schedules: {
            list   : ()  => apiFetch('schedules.php?action=list'),
            delete : (id)=> apiFetch('schedules.php?action=delete',  { id }),
            approve: (id)=> apiFetch('schedules.php?action=approve', { id }),
            reject : (id)=> apiFetch('schedules.php?action=reject',  { id }),
            create: (d) => apiFetch('schedules.php?action=create', {
                courseId    : d.courseId    || d.course_id    || null,
                courseCode  : d.courseCode  || d.course_code  || '',
                courseName  : d.courseName  || d.course_name  || '',
                college     : d.college     || '',
                examType    : d.examType    || d.exam_type    || '',
                yearLevel   : d.yearLevel   || d.year_level   || '',
                examDate    : d.examDate    || d.exam_date    || '',
                timeSlot    : d.timeSlot    || d.time_slot    || '',
                duration    : d.duration    || '',
                roomId      : d.roomId      || d.room_id      || null,
                roomName    : d.roomName    || d.room_name    || '',
                proctorId   : d.proctorId   || d.proctor_id   || null,
                proctorName : d.proctorName || d.proctor_name || '',
            }),
            update: (d) => apiFetch('schedules.php?action=update', {
                id          : d.id,
                examType    : d.examType    || d.exam_type    || '',
                yearLevel   : d.yearLevel   || d.year_level   || '',
                examDate    : d.examDate    || d.exam_date    || '',
                timeSlot    : d.timeSlot    || d.time_slot    || '',
                duration    : d.duration    || '',
                roomId      : d.roomId      || d.room_id      || null,
                roomName    : d.roomName    || d.room_name    || '',
                proctorId   : d.proctorId   || d.proctor_id   || null,
                proctorName : d.proctorName || d.proctor_name || '',
            }),
        },
    };

    // ── Data mappers: DB row → portal object ──────────────────
    function mapCollege(row) {
        return {
            id          : `college_${row.id}`,
            _dbId       : row.id,
            type        : 'college',
            name        : row.name        || '',
            code        : row.code        || '',
            description : row.description || '',
            programs    : Array.isArray(row.programs) ? row.programs : [],
        };
    }

    function mapCourse(row) {
        return {
            id         : `course_${row.id}`,
            _dbId      : row.id,
            type       : 'course',
            courseCode : row.course_code || '',
            courseName : row.course_name || '',
            college    : row.college     || '',
            program    : row.program     || '',
            yearLevel  : row.year_level  || '',
            semester   : row.semester    || '',
        };
    }

    function mapRoom(row) {
        return {
            id       : `room_${row.id}`,
            _dbId    : row.id,
            type     : 'room',
            name     : row.name     || '',
            building : row.building || '',
            capacity : parseInt(row.capacity) || 0,
            floor    : row.floor    || '',
            locked   : row.locked == 1 || row.locked === true,
        };
    }

    function mapProctor(row) {
        return {
            id             : `proctor_${row.id}`,
            _dbId          : row.id,
            type           : 'proctor',
            name           : row.name            || '',
            collegeProgram : row.college_program  || '',
            email          : row.email            || '',
            phone          : row.phone            || '',
        };
    }

    function mapSchedule(row) {
        const code = row.course_code || '';
        const name = row.course_name || '';
        return {
            id          : `schedule_${row.id}`,
            _dbId       : row.id,
            type        : 'schedule',
            courseId    : row.course_id   || null,
            roomId      : row.room_id     || null,
            proctorId   : row.proctor_id  || null,
            course      : code && name ? `${code} – ${name}` : (code || name || '—'),
            courseCode  : code,
            courseName  : name,
            college     : row.college      || '',
            examType    : row.exam_type    || '',
            yearLevel   : row.year_level   || '',
            date        : row.exam_date    || '',
            dateTime    : row.exam_date
                            ? row.exam_date + (row.time_slot ? ' ' + row.time_slot : '')
                            : '',
            timeSlot    : row.time_slot    || '',
            duration    : row.duration     || '',
            room        : row.room_name    || '',
            roomName    : row.room_name    || '',
            proctor     : row.proctor_name || '',
            proctorName : row.proctor_name || '',
            status      : row.status       || 'Pending',
            createdBy   : row.created_by   || null,
        };
    }

    // ── dataSdk ───────────────────────────────────────────────
    let _onDataChanged = null;

    window.dataSdk = {
        init(handler) {
            _onDataChanged = handler.onDataChanged;
            return this.refresh();
        },

        async refresh() {
            try {
                const [colleges, courses, rooms, proctors, schedules] = await Promise.all([
                    apiFetch('colleges.php?action=list'),
                    apiFetch('courses.php?action=list'),
                    apiFetch('rooms.php?action=list'),
                    apiFetch('proctors.php?action=list'),
                    apiFetch('schedules.php?action=list'),
                ]);

                const allData = [
                    ...(colleges.data  || []).map(mapCollege),
                    ...(courses.data   || []).map(mapCourse),
                    ...(rooms.data     || []).map(mapRoom),
                    ...(proctors.data  || []).map(mapProctor),
                    ...(schedules.data || []).map(mapSchedule),
                ];

                if (_onDataChanged) _onDataChanged(allData);
                return { isOk: true };
            } catch (err) {
                console.error('[dataSdk] refresh error:', err);
                if (_onDataChanged) _onDataChanged([]);
                return { isOk: false };
            }
        },

        async create(item) {
            if (item.type === 'feedback') {
                const res = await apiFetch('feedbacks.php?action=create', item);
                return { isOk: res.success };
            }
            console.warn('[dataSdk] unexpected create() type:', item.type);
            return { isOk: false };
        },
    };

    // ── Auto-refresh after every successful write ─────────────
    const WRITE_ACTIONS = ['create', 'update', 'delete', 'approve', 'reject', 'toggleLock'];
    Object.keys(window.flexamApi).forEach(resource => {
        WRITE_ACTIONS.forEach(action => {
            const orig = window.flexamApi[resource][action];
            if (typeof orig !== 'function') return;
            window.flexamApi[resource][action] = async (...args) => {
                const result = await orig(...args);
                if (result.success) await window.dataSdk.refresh();
                return result;
            };
        });
    });

})();
