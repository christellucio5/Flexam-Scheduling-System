<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Program Head') {
    header('Location: ../login.php');
    exit;
}

$currentUser = $_SESSION['user'];
$displayName = $currentUser['full_name'] ?? 'Program Head';
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FLEXAM - Program Head Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../js/announcements.js"></script>      
    <script>
    // ── Special Exam helpers ──────────────────────────────────────────────
    window.filterSpecialExams = function() {
        const search=(document.getElementById('spExamSearch')?.value||'').toLowerCase();
        const sy=document.getElementById('spExamSYFilter')?.value||'';
        const sem=document.getElementById('spExamSemFilter')?.value||'';
        const type=(document.getElementById('spExamTypeFilter')?.value||'').toLowerCase();
        document.querySelectorAll('#specialExamBody .sp-exam-row').forEach(row=>{
            const m=(!search||(row.dataset.search||'').includes(search))&&(!sy||row.dataset.sy===sy)&&(!sem||row.dataset.sem===sem)&&(!type||(row.dataset.type||'').includes(type));
            row.style.display=m?'':'none';
        });
    };
    window.spExamClearFilters=function(){['spExamSearch','spExamSYFilter','spExamSemFilter','spExamTypeFilter'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});filterSpecialExams();};
    window.spExamReasonChanged = function(sel) { var wrap = document.getElementById('spReasonOtherWrap'); if (wrap) wrap.style.display = sel.value === 'Others' ? 'block' : 'none'; };
    window.openSpecialExamModal = async function(editId) {
        if (document.getElementById('specialExamModal')) return;
        const isEdit=!!editId; const existing=isEdit?(window._specialExams||[]).find(e=>String(e.id)===String(editId)):null;
        const colleges=allData.filter(d=>d.type==='college'); const courses=allData.filter(d=>d.type==='course');
        const modal=document.createElement('div'); modal.id='specialExamModal';
        modal.className='fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm';
        const esc2=s=>{const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;};
        modal.innerHTML=`<style>#specialExamModalBox{scrollbar-width:thin;scrollbar-color:#c4b5fd #f5f3ff;}#specialExamModalBox::-webkit-scrollbar{width:6px;}#specialExamModalBox::-webkit-scrollbar-track{background:#f5f3ff;border-radius:99px;}#specialExamModalBox::-webkit-scrollbar-thumb{background:#c4b5fd;border-radius:99px;}#specialExamModalBox::-webkit-scrollbar-thumb:hover{background:#a78bfa;}</style><div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col" style="max-height:90vh;"><div class="px-8 py-5 bg-gradient-to-r from-purple-600 to-purple-700 rounded-t-2xl flex items-center justify-between flex-shrink-0"><div><h2 class="text-lg font-bold text-white">${isEdit?'Edit':'New'} Special Exam Registration</h2><p class="text-purple-200 text-xs mt-0.5">Fill in all required fields marked with *</p></div><button type="button" onclick="document.getElementById('specialExamModal').remove()" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="2.5"/></svg></button></div><div id="specialExamModalBox" class="px-8 py-6 space-y-5 overflow-y-auto flex-1"><div class="grid grid-cols-2 gap-4"><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">School Year *</label><input id="spSY" type="text" placeholder="e.g. 2025-2026" value="${existing?.school_year||''}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Semester *</label><select id="spSem" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400"><option value="">Select Semester</option>${['1st Semester','2nd Semester','Summer'].map(s=>`<option value="${s}" ${existing?.semester===s?'selected':''}>${s}</option>`).join('')}</select></div></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Special Exam Type *</label><select id="spExamType" onchange="document.getElementById('spExamTypeOtherWrap').style.display=this.value==='Others'?'block':'none'" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400"><option value="">Select Type</option>${['Prelim','Midterm','Final','Summer','Others'].map(t=>`<option value="${t}" ${existing?.exam_type===t?'selected':''}>${t}</option>`).join('')}</select><div id="spExamTypeOtherWrap" style="display:${existing?.exam_type==='Others'?'block':'none'};margin-top:8px"><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Please specify *</label><input id="spExamTypeOther" type="text" placeholder="Specify exam type…" value="${existing?.exam_type_other||''}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"></div></div><div class="grid grid-cols-3 gap-4"><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Student No. *</label><input id="spStudNo" type="text" placeholder="0000-000-0000" maxlength="12" oninput="this.value=this.value.replace(/[^0-9-]/g,'')" value="${existing?.student_no||''}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Last Name *</label><input id="spLastName" type="text" placeholder="e.g. Dela Cruz" value="${existing?.last_name||''}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">First Name *</label><input id="spFirstName" type="text" placeholder="e.g. Juan" value="${existing?.first_name||''}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"></div></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Campus</label><div class="flex items-center gap-2 px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 font-semibold"><svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>${esc2(currentUser.campus||'')}</div></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">College *</label><select id="spCollege" onchange="spRebuildProgram();spRebuildCourses();" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400"><option value="">Select College</option>${colleges.map(c=>`<option value="${c.code||c.name}" data-programs='${JSON.stringify(c.programs||[])}' ${existing?.college===(c.code||c.name)?'selected':''}>${c.code||c.name}${c.name&&c.code?' — '+c.name:''}</option>`).join('')}</select></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Program</label><select id="spProgram" onchange="spRebuildCourses();" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400"><option value="">— Select Program —</option></select></div></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Receipt No. *</label><input id="spReceipt" type="text" value="${existing?.receipt_no||''}" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Reason *</label><select id="spReasonSelect" onchange="spExamReasonChanged(this)" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400"><option value="">— Select Reason —</option>${['Personal Reason','Emergency','Failure to attend on time'].concat(['Others']).map(r=>{const isOther=existing?.reason&&!['Personal Reason','Emergency','Failure to attend on time'].includes(existing.reason);const sel=(existing?.reason===r||(r==='Others'&&isOther))?'selected':'';return`<option value="${r}" ${sel}>${r}</option>`;}).join('')}</select><div id="spReasonOtherWrap" style="display:${existing?.reason&&!['Personal Reason','Emergency','Failure to attend on time'].includes(existing.reason)?'block':'none'};margin-top:8px"><input id="spReasonOther" type="text" placeholder="Please specify your reason..." value="${existing?.reason&&!['Personal Reason','Emergency','Failure to attend on time'].includes(existing.reason)?esc2(existing.reason):''}" class="w-full px-3 py-2.5 border border-purple-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400"></div></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">No. of Exams *</label><select id="spNumExams" onchange="updateSpExamSlots()" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-purple-400">${[1,2,3,4,5].map(n=>`<option value="${n}" ${(existing?.num_exams||1)==n?'selected':''}>${n}</option>`).join('')}</select></div><div><label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Courses for Special Exam</label><div id="spExamSlots" class="space-y-2 border border-slate-200 rounded-xl p-3 bg-slate-50"></div></div><div id="spFormError" class="hidden text-sm text-red-500 bg-red-50 border border-red-200 rounded-xl px-4 py-3"></div></div><div class="px-8 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex-shrink-0 flex gap-3"><button type="button" onclick="document.getElementById('specialExamModal').remove()" class="flex-1 px-4 py-2.5 border border-slate-200 bg-white text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-100 transition-colors">Cancel</button><button type="button" onclick="submitSpecialExam(${isEdit?editId:'null'})" class="flex-1 px-4 py-2.5 bg-purple-600 text-white rounded-xl text-sm font-semibold hover:bg-purple-700 shadow-sm transition-colors">${isEdit?'Save Changes':'Register Student'}</button></div></div>`;
        document.body.appendChild(modal);
        const existingCourses=(()=>{try{return JSON.parse(existing?.exam_courses||'[]');}catch(e){return[];}})();

        // ── Rebuild Program dropdown when College changes ─────────────────────
        window.spRebuildProgram = function() {
            const colSel = document.getElementById('spCollege');
            const progSel = document.getElementById('spProgram');
            if (!colSel || !progSel) return;
            const opt = colSel.options[colSel.selectedIndex];
            let programs = [];
            try { programs = JSON.parse(opt?.getAttribute('data-programs') || '[]'); } catch(e) {}
            // Strip "ACRONYM=Full Name" → show acronym only as value, full label as text
            progSel.innerHTML = '<option value="">— All Programs —</option>' +
                programs.map(p => {
                    const eqIdx = p.indexOf('=');
                    const acronym = eqIdx !== -1 ? p.slice(0, eqIdx).trim() : p.trim();
                    const label   = eqIdx !== -1 ? p.slice(eqIdx + 1).trim() : p.trim();
                    const sel     = existing?.program === acronym ? 'selected' : '';
                    return `<option value="${esc2(acronym)}" ${sel}>${esc2(acronym)}${label && label !== acronym ? ' — ' + esc2(label) : ''}</option>`;
                }).join('');
        };

        // ── Rebuild course slots filtered by College + Program ────────────────
        window.spRebuildCourses = function() { buildSlots(); };

        // Fetch courses from the API filtered by SY + Semester + College + Program + Campus
        async function getFilteredCourses() {
            const sy      = (document.getElementById('spSY')?.value      || '').trim();
            const sem     = (document.getElementById('spSem')?.value     || '').trim();
            const colVal  = (document.getElementById('spCollege')?.value || '').trim();
            const progVal = (document.getElementById('spProgram')?.value || '').trim();
            const campus  = (currentUser.campus || '').trim();

            const params = new URLSearchParams({ action: 'filter' });
            if (sy)      params.set('school_year', sy);
            if (sem)     params.set('semester',    sem);
            if (colVal)  params.set('college',     colVal);
            if (progVal) params.set('program',     progVal);
            if (campus)  params.set('campus',      campus);

            try {
                const res  = await fetch(`../api/courses.php?${params.toString()}`);
                const json = await res.json();
                return json.success ? (json.data || []) : [];
            } catch (e) {
                console.error('Course filter error:', e);
                return [];
            }
        }

        async function buildSlots(){
            const n=parseInt(document.getElementById('spNumExams')?.value||1);
            const slots=document.getElementById('spExamSlots');
            if(!slots)return;
            const sy      = (document.getElementById('spSY')?.value      || '').trim();
            const sem     = (document.getElementById('spSem')?.value     || '').trim();
            const colVal  = (document.getElementById('spCollege')?.value || '').trim();
            const progVal = (document.getElementById('spProgram')?.value || '').trim();

            // Step-by-step guards: SY → Semester → College → Program
            if (!sy) {
                slots.innerHTML='<p class="text-xs text-amber-500 text-center py-2 italic">⚠ Please enter a School Year (e.g. 2025-2026) to load courses.</p>';
                return;
            }
            if (!sem) {
                slots.innerHTML='<p class="text-xs text-amber-500 text-center py-2 italic">⚠ Please select a Semester to load courses.</p>';
                return;
            }
            if (!colVal) {
                slots.innerHTML='<p class="text-xs text-slate-400 text-center py-2 italic">Please select a College first.</p>';
                return;
            }
            if (!progVal) {
                slots.innerHTML='<p class="text-xs text-slate-400 text-center py-2 italic">Please select a Program to see available courses.</p>';
                return;
            }

            slots.innerHTML=`<p class="text-xs text-slate-400 text-center py-2 animate-pulse">Loading courses for ${esc2(sy)} ${esc2(sem)}…</p>`;
            const filteredCourses = await getFilteredCourses();
            slots.innerHTML='';

            if (!filteredCourses.length) {
                slots.innerHTML=`<p class="text-xs text-slate-400 text-center py-2 italic">No courses found for <strong>${esc2(sy)} ${esc2(sem)}</strong> · ${esc2(colVal)}${progVal?' · '+esc2(progVal):''}. Ensure courses are tagged with this School Year and Semester.</p>`;
                return;
            }

            for(let i=0;i<n;i++){
                const ec=existingCourses[i]||{};
                const row=document.createElement('div');
                row.className='grid grid-cols-[auto_1fr] gap-2 items-center';
                const opts = filteredCourses.map(c=>`<option value="${c.id||c.course_id}" data-label="${esc2((c.code||c.course_code||'')+(c.name||c.course_name?' — '+(c.name||c.course_name):''))}" ${String(ec.course_id)===String(c.id||c.course_id)?'selected':''}>${c.code||c.course_code||''} — ${c.name||c.course_name||''}</option>`).join('');
                row.innerHTML=`<span class="text-xs font-bold text-slate-400 w-5 text-center">${i+1}</span><select class="sp-course-sel px-2 py-2 border border-slate-200 rounded-lg text-sm bg-white w-full"><option value="">— Select Course —</option>${opts}</select>`;
                slots.appendChild(row);
            }
        }

        window.updateSpExamSlots=buildSlots;

        // Wire SY and Semester inputs so changing them also rebuilds the course list
        setTimeout(() => {
            const syEl  = document.getElementById('spSY');
            const semEl = document.getElementById('spSem');
            if (syEl)  syEl.addEventListener('input',  buildSlots);
            if (semEl) semEl.addEventListener('change', buildSlots);
        }, 0);

        // On open: pre-populate program dropdown if editing, then build slots
        spRebuildProgram();
        buildSlots();
    };
    window.submitSpecialExam=async function(editId){const get=id=>document.getElementById(id)?.value?.trim()||'';const payload={school_year:get('spSY'),semester:get('spSem'),exam_type:get('spExamType'),exam_type_other:get('spExamTypeOther'),student_no:get('spStudNo'),last_name:get('spLastName'),first_name:get('spFirstName'),college:get('spCollege'),program:get('spProgram'),receipt_no:get('spReceipt'),reason:(()=>{const sel=document.getElementById('spReasonSelect')?.value||'';if(!sel)return'';if(sel==='Others')return(document.getElementById('spReasonOther')?.value||'').trim();return sel;})(),num_exams:parseInt(get('spNumExams')||'1'),campus:currentUser.campus||''};const errEl=document.getElementById('spFormError');const missing=['school_year','semester','exam_type','student_no','last_name','first_name','college','receipt_no','reason'].find(f=>!payload[f]);if(missing){errEl.textContent=`Field "${missing.replace('_',' ')}" is required.`;errEl.classList.remove('hidden');return;}if(payload.exam_type==='Others'&&!payload.exam_type_other){errEl.textContent='Please specify the exam type.';errEl.classList.remove('hidden');return;}if(payload.reason===''){errEl.textContent='Please select a reason for the special exam.';errEl.classList.remove('hidden');return;}if(document.getElementById('spReasonSelect')?.value==='Others'&&!document.getElementById('spReasonOther')?.value?.trim()){errEl.textContent='Please specify your reason.';errEl.classList.remove('hidden');return;}errEl.classList.add('hidden');payload.exam_courses=JSON.stringify([...document.querySelectorAll('#spExamSlots > div')].map(row=>{const sel=row.querySelector('.sp-course-sel');const opt=sel?.options[sel.selectedIndex];return{course_id:sel?.value||'',course_label:opt?.dataset?.label||opt?.text||''};}).filter(e=>e.course_id));if(editId)payload.id=editId;const res=editId?await window.flexamApi.special_exams.update(payload):await window.flexamApi.special_exams.create(payload);if(!res?.success){errEl.textContent=res?.message||'Error saving.';errEl.classList.remove('hidden');return;}document.getElementById('specialExamModal')?.remove();const spRes=await window.flexamApi.special_exams.list();const _mc=(currentUser.campus||'').trim().toLowerCase();window._specialExams=(spRes.data||[]).filter(e=>!_mc||(e.campus||'').trim().toLowerCase()===_mc);window._schedTab='special';renderApp();if(!editId){showSpecialExamEvidencePopup(payload.first_name,payload.last_name);}else if(typeof showToast==='function'){showToast('Registration updated.','success');}};
    window.showSpecialExamEvidencePopup = function(firstName, lastName) {
        const existing = document.getElementById('spExamEvidencePopup');
        if (existing) existing.remove();
        const esc2 = s => { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; };
        const pop = document.createElement('div');
        pop.id = 'spExamEvidencePopup';
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
                    <span class="font-bold text-slate-900">${esc2(firstName)} ${esc2(lastName)}</span> has been successfully registered for a special examination.
                </p>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-2">
                    <p class="text-xs font-bold text-amber-700 uppercase tracking-wide">⚠️ You Must Present Valid Evidence to the Proctor</p>
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
        if (typeof showToast === 'function') showToast('✅ Special exam registration saved!', 'success');
    };
    window.deleteSpecialExam=async function(id){if(!confirm('Delete this special exam registration?'))return;const res=await window.flexamApi.special_exams.delete(id);if(res?.success){window._specialExams=(window._specialExams||[]).filter(e=>e.id!==id);renderApp();if(typeof showToast==='function')showToast('Registration deleted.','success');}else if(typeof showToast==='function')showToast(res?.message||'Delete failed.','error');};
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
    window.exportSpecialExamPDF=function(){const rows=window._specialExams||[];if(!rows.length){if(typeof showToast==='function')showToast('No registrations to export.','error');return;}const win=window.open('','_blank');const esc2=s=>{const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;};win.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Special Exam Registrations</title><style>body{font-family:Arial,sans-serif;font-size:11px;padding:20px}h2{font-size:16px;margin-bottom:4px}p{color:#666;margin-bottom:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ddd;padding:6px 8px;text-align:left}th{background:#6d28d9;color:#fff;font-size:10px;text-transform:uppercase}tr:nth-child(even){background:#f9f7ff}@media print{button{display:none}}</style></head><body><h2>Special Exam Registrations</h2><p>Generated: ${new Date().toLocaleString()}</p><button onclick="window.print()" style="margin-bottom:12px;padding:6px 16px;background:#6d28d9;color:#fff;border:none;border-radius:6px;cursor:pointer">Print / Save PDF</button><table><thead><tr><th>Student No.</th><th>Last Name</th><th>First Name</th><th>College/Program</th><th>S.Y.</th><th>Semester</th><th>Exam Type</th><th>Receipt No.</th><th># Exams</th><th>Campus</th><th>Reason</th></tr></thead><tbody>${rows.map(e=>`<tr><td>${esc2(e.student_no||'')}</td><td>${esc2(e.last_name||'')}</td><td>${esc2(e.first_name||'')}</td><td>${esc2(e.college||'')}${e.program?' / '+esc2(e.program):''}</td><td>${esc2(e.school_year||'')}</td><td>${esc2(e.semester||'')}</td><td>${esc2(e.exam_type||'')}</td><td>${esc2(e.receipt_no||'')}</td><td style="text-align:center">${e.num_exams||0}</td><td>${esc2(e.campus||'')}</td><td>${esc2(e.reason||'')}</td></tr>`).join('')}</tbody></table></body></html>`);win.document.close();};

</script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <link rel="icon" type="image/svg+xml" href="../Image/OLFU.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif !important; }
        body { margin: 0; padding: 0; background-color: #f8fafc; color: #1e293b; }
        button, .sidebar-link span { font-weight: 400 !important; }
        .sidebar-link {
            transition: all 0.2s ease; display: flex; align-items: flex-start;
            gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem;
            color: #64748b; font-size: 0.875rem; margin-bottom: 0.25rem; line-height: 1.25;
        }
        .sidebar-link:hover { background-color: #f1f5f9; color: #1e293b; }
        .sidebar-link.active { background-color: #ecfdf5; color: #047857; }
        .sidebar-link.active span { font-weight: 500 !important; }
        .logo-box {
            background: #047857; border-radius: 0.75rem; display: flex;
            align-items: center; justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(4, 120, 87, 0.2);
        }
        .top-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem; height: 80px; }
        .stat-card-small {
            background: white; padding: 1rem 1.25rem; border-radius: 0.75rem;
            border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;
        }
        .btn-action-large {
            padding: 0.6rem 1.5rem; font-size: 0.875rem; border-radius: 0.6rem;
            display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease; white-space: nowrap;
        }
        .filter-select {
            padding: 0.5rem 2.25rem 0.5rem 0.75rem; border: 1px solid #e2e8f0;
            border-radius: 0.5rem; font-size: 0.8rem; color: #374151; background: #f8fafc;
            appearance: none; cursor: pointer; transition: all 0.2s ease; white-space: nowrap;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1rem;
        }
        .filter-select:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        .sm-dd-item { background:#f8fafc; } .sm-dd-item:hover { background:#2563eb !important; color:#fff !important; }
        .filter-tag {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600;
            background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;
        }
        .filter-tag button { line-height: 1; color: #047857; font-size: 0.85rem; }
        .filter-tag button:hover { color: #065f46; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-top: 1px solid #e2e8f0; }
        .calendar-cell { min-height: 100px; padding: 0.5rem; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; background: white; position: relative; overflow: hidden; min-width: 0; }
        .event-bar {
            padding: 4px 8px; border-radius: 4px; font-size: 10px; color: white;
            margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            cursor: pointer; transition: all 0.2s ease;
            width: 100%; box-sizing: border-box; display: block;
        }
        .event-bar:hover { opacity: 0.8; transform: translateY(-1px); }
        .quick-icon-card { transition: all 0.3s ease; }
        .quick-icon-card:hover { transform: translateY(-4px); }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 50; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: 1rem; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; animation: slideUp 0.3s ease; }
        .modal-box { background: white; border-radius: 1rem; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; animation: slideUp 0.3s ease; box-shadow: 0 25px 50px rgba(0,0,0,0.25); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .form-label { font-size: 0.875rem; font-weight: 500; color: #334155; margin-bottom: 0.5rem; display: block; }
        .form-label .required { color: #ef4444; margin-left: 2px; }
        .form-input, .form-select {
            width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #e2e8f0;
            border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b;
            background: white; transition: all 0.2s ease;
        }
        .form-input:focus, .form-select:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        .form-input::placeholder { color: #94a3b8; }
        .form-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1.25rem; padding-right: 2.5rem; }
        .toast { position: fixed; bottom: 1.5rem; right: 1.5rem; color: white; padding: 0.75rem 1.25rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideUp 0.3s ease; }
        .notif-item { transition: background 0.15s ease; }
        .notif-item.is-read { opacity: 0.6; }
        .mark-read-btn { transition: all 0.15s ease; }
        .mark-read-btn:hover { transform: scale(1.1); }

        /* ── Mobile Responsive ── */
        #sidebarOverlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45); z-index: 30;
        }
        #sidebarOverlay.active { display: block; }

        @media (max-width: 767px) {
            #sidebar {
                position: fixed; top: 0; left: 0; height: 100%;
                transform: translateX(-100%); transition: transform 0.25s ease;
                z-index: 40;
            }
            #sidebar.open { transform: translateX(0); }
            .top-header { padding: 0.75rem 1rem; height: auto; min-height: 64px; }
            main { padding: 1rem !important; }
        }

        /* ── Campus Tab Strip ── */
        .campus-tab-strip {
            display: flex; align-items: center; gap: 0.25rem; flex-wrap: wrap;
            padding: 0.625rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        }
        .campus-tab {
            display: inline-flex; align-items: center; gap: 0.375rem;
            padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.75rem;
            font-weight: 600; cursor: pointer; border: 1.5px solid #e2e8f0;
            transition: all 0.18s ease; color: #64748b; background: white;
            white-space: nowrap; user-select: none;
        }
        .campus-tab:hover { border-color: #a7f3d0; color: #047857; background: #f0fdf4; }
        .campus-tab.active { background: #047857; border-color: #047857; color: white; box-shadow: 0 2px 6px rgba(4,120,87,0.25); }
        .campus-tab .tab-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 1.1rem; height: 1.1rem; padding: 0 0.2rem; border-radius: 9999px;
            font-size: 0.6rem; font-weight: 700; background: rgba(255,255,255,0.25); color: inherit;
        }
        .campus-tab.active .tab-count { background: rgba(255,255,255,0.3); color: white; }
        .campus-badge {
            display: inline-flex; align-items: center; padding: 0.25rem 0.75rem;
            border-radius: 9999px; font-size: 0.7rem; font-weight: 700;
            background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;
        }

        /* ── Logout Confirmation Modal ── */
        .logout-modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            display: flex; align-items: center; justify-content: center; z-index: 100;
            animation: fadeIn 0.2s ease;
        }
        .logout-modal-box {
            background: white; border-radius: 1rem; width: 90%; max-width: 400px;
            overflow: hidden; animation: slideUp 0.3s ease; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        .logout-modal-box .lm-header {
            padding: 2rem 1.5rem 1.25rem; text-align: center; border-bottom: 1px solid #f1f5f9;
        }
        .logout-modal-box .lm-icon {
            width: 60px; height: 60px; background: #fef2f2; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;
        }
        .logout-modal-box .lm-title {
            font-size: 1.125rem; font-weight: 700; color: #1e293b; margin-bottom: 0.375rem;
        }
        .logout-modal-box .lm-subtitle {
            font-size: 0.8125rem; color: #64748b; line-height: 1.5;
        }
        .logout-modal-box .lm-footer {
            padding: 1.25rem 1.5rem; display: flex; gap: 0.75rem;
        }
        .logout-modal-box .lm-btn-cancel {
            flex: 1; padding: 0.75rem; border: 1.5px solid #e2e8f0; border-radius: 0.6rem;
            font-size: 0.875rem; font-weight: 600; color: #475569; background: white;
            cursor: pointer; transition: all 0.2s ease;
        }
        .logout-modal-box .lm-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
        .logout-modal-box .lm-btn-confirm {
            flex: 1; padding: 0.75rem; border: none; border-radius: 0.6rem;
            font-size: 0.875rem; font-weight: 600; color: white; background: #ef4444;
            cursor: pointer; transition: all 0.2s ease;
        }
        .logout-modal-box .lm-btn-confirm:hover { background: #dc2626; }
    </style>
</head>
<body class="h-full bg-slate-50">
<div id="app" class="h-full w-full"></div>

<script>
// ─────────────────────────────────────────────────────────────────────────────
// 1. SESSION & STATE
// ─────────────────────────────────────────────────────────────────────────────
let currentUser      = <?php echo json_encode($currentUser); ?>;

// ── Activity Logging (shared key with admin.php) ──────────────────────────
function activityLog(action, details = '', targetUser = '') {
    try {
        const LOG_KEY  = 'flexam_activityLog';
        const MAX_LOGS = 500;
        const existing = JSON.parse(localStorage.getItem(LOG_KEY) || '[]');
        const entry = {
            id:          Date.now() + '_' + Math.random().toString(36).slice(2,7),
            timestamp:   new Date().toISOString(),
            actor:       currentUser.full_name || 'Program Head',
            actorId:     currentUser.id        || '',
            actorRole:   currentUser.role      || 'Program Head',
            actorCampus: currentUser.campus    || '',
            action, details, targetUser
        };
        existing.unshift(entry);
        if (existing.length > MAX_LOGS) existing.splice(MAX_LOGS);
        localStorage.setItem(LOG_KEY, JSON.stringify(existing));
    } catch(e) {}
}
let currentView      = sessionStorage.getItem('head_currentView') || 'dashboard';
let allData          = [];
let allSchedulesRaw  = []; // unfiltered schedules for View Schedules

// ── Restore pending import conflicts so the Auto-Resolved widget persists across reloads ──
window._importResolvableRows = (() => { try { const s = localStorage.getItem('flexam_pendingImportConflicts'); return s ? JSON.parse(s) : []; } catch(e) { return []; } })();
let campusFilters    = { 'view-schedules': '' };
let calendarMonth = new Date().getMonth();
let calendarYear  = new Date().getFullYear();

// ── Notification read state ──
const NOTIF_STORAGE_KEY = `flexam_read_notifs_${currentUser.id || 'ph'}`;
let readNotifIds = new Set(JSON.parse(localStorage.getItem(NOTIF_STORAGE_KEY) || '[]'));

function saveReadNotifs() {
    localStorage.setItem(NOTIF_STORAGE_KEY, JSON.stringify([...readNotifIds]));
}

window.markNotifRead = function(id) {
    readNotifIds.add(String(id));
    saveReadNotifs();
    const headerEl = document.querySelector('header');
    if (headerEl) headerEl.outerHTML = renderHeader();
    attachHandlers();
};

window.markAllNotifsRead = function() {
    const schedules = allData.filter(d => d.type === 'schedule' && (d.status === 'Approved' || d.status === 'Rejected'));
    schedules.forEach(s => readNotifIds.add(String(s.id)));
    saveReadNotifs();
    const headerEl = document.querySelector('header');
    if (headerEl) headerEl.outerHTML = renderHeader();
    attachHandlers();
    showToast('All notifications marked as read');
};

// ── View Schedules filters ──
let scheduleSearchTerm     = '';
let scheduleFilterExamType = 'all';
let scheduleFilterCampus   = 'all';
let scheduleFilterSemester = 'all';

// ── Pagination state ──
let vsCurrentPage = 1;   // View Schedules current page
let smCurrentPage = 1;   // Schedule Management current page
const PAGE_SIZE   = 10;
let vsCollegeFilter        = 'all';
let vsStatusFilter         = 'all';

// ── Analytics filters ──
let analyticsPeriod        = 'all';
let analyticsDateFrom      = '';
let analyticsDateTo        = '';
let analyticsCampus        = 'all';
let analyticsSchedPage     = 0;
let analyticsCoursePage    = 0;
let analyticsRejPage       = 0;
let analyticsFeedPage      = 0;
let analyticsRoomUtilPage  = 0;

// ── Head Analytics extra sections (room util, charts, proctor, conflicts) ──
let headRoomUtilPage       = 1;
const HEAD_ROOM_UTIL_PER_PAGE = 10;
let headProctorDateFilter  = 'all';
let _headProctorPage       = 1;
const HEAD_PROCTOR_PAGE_SIZE = 10;
let _headProctorAllRows    = [];
let _headConflictsPage     = 1;
const HEAD_CONFLICTS_PAGE_SIZE = 15;
const HEAD_PIE_COLORS          = ['#10b981','#3b82f6','#8b5cf6','#f97316','#ec4899','#06b6d4','#84cc16','#f59e0b','#14b8a6','#6366f1','#f43f5e','#a855f7'];
const HEAD_BUILDING_PIE_COLORS = ['#f59e0b','#f97316','#eab308','#fb923c','#fbbf24','#fcd34d','#fde68a','#fef3c7'];
const HEAD_ROOM_PIE_COLORS     = ['#3b82f6','#2563eb','#60a5fa','#0ea5e9','#38bdf8','#06b6d4','#22d3ee','#93c5fd'];
const HEAD_PROCTOR_PIE_COLORS  = ['#8b5cf6','#6366f1','#3b82f6','#14b8a6','#10b981','#f59e0b','#f97316','#ec4899','#06b6d4','#84cc16','#a855f7','#f43f5e'];

// ── Schedule Management filters ──
let scheduleMgmtSearch     = '';
let scheduleMgmtCampus     = 'all';
let scheduleMgmtCourse     = 'all';
let scheduleMgmtExamType   = 'all';
let scheduleMgmtSemester   = 'all';

// ── Courses filters ──
let courseSearch       = '';
let courseCampus       = 'all';
let courseCollege      = 'all';
let courseCurrentPage  = 1;   // Courses current page

// ── Proctors filters ──
let proctorSearch  = '';
let proctorCampus  = 'all';

const CAMPUSES = ['Quezon City','Valenzuela','Antipolo','Cabanatuan','Laguna','Nueva Ecija'];

        // --- YEAR LEVELS (admin-configurable) ---
        let YEAR_LEVELS = (() => {
            try { return JSON.parse(localStorage.getItem('customYearLevels')) || null; } catch(e) { return null; }
        })() || ['1st Year','2nd Year','3rd Year','4th Year','5th Year','6th Year'];

        const SEMESTERS = ['1st Semester', '2nd Semester'];

        function yearLevelOptions(selected = '') {
            return `<option value="" disabled ${!selected ? 'selected' : ''}>Select Year Level</option>` +
                YEAR_LEVELS.map(y => `<option ${y === selected ? 'selected' : ''}>${y}</option>`).join('');
        }

// ─────────────────────────────────────────────────────────────────────────────
// 2. API LAYER
// ─────────────────────────────────────────────────────────────────────────────
// ── Helper: fetch with rich error extraction ──────────────────────────────
async function flexamFetch(url, options = {}) {
    const res = await fetch(url, options);
    const json = await res.json().catch(() => ({}));
    if (!res.ok && !json.message) {
        // Build a human-readable message from the HTTP status
        const hints = {
            403: 'You do not have permission to perform this action. This may be because the record belongs to a different college.',
            401: 'Your session has expired. Please log in again.',
            404: 'The requested resource was not found.',
            500: 'A server error occurred. Please try again or contact support.',
        };
        json.message = hints[res.status] || `Server error (HTTP ${res.status})`;
        json.success  = false;
    }
    return json;
}

window.flexamApi = {
    colleges:  { list: () => flexamFetch('../api/colleges.php?action=list') },
    rooms:     { list: () => flexamFetch('../api/rooms.php?action=list') },
    proctors:  {
        list:   ()     => flexamFetch('../api/proctors.php?action=list'),
        create: (data) => flexamFetch('../api/proctors.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }),
    },
    courses:   {
        list:   ()     => flexamFetch('../api/courses.php?action=list'),
        create: (data) => flexamFetch('../api/courses.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }),
    },
    schedules: {
        list:   ()     => flexamFetch('../api/schedules.php?action=list'),
        create: (data) => flexamFetch('../api/schedules.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }),
        update: (data) => flexamFetch('../api/schedules.php?action=update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        }),
        delete: (id)   => flexamFetch('../api/schedules.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        }),
    },
    special_exams: {
        list:   ()     => flexamFetch('../api/special_exams.php?action=list'),
        create: (data) => flexamFetch('../api/special_exams.php?action=create', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }),
        update: (data) => flexamFetch('../api/special_exams.php?action=update', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) }),
        delete: (id)   => flexamFetch('../api/special_exams.php?action=delete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) })
    },
    feedbacks: { list: () => flexamFetch('../api/feedbacks.php?action=list') },
};

// ─────────────────────────────────────────────────────────────────────────────
// 3. GLOBAL DATA REFRESH (WITH DEPARTMENT FILTERING)
// ─────────────────────────────────────────────────────────────────────────────
async function refreshAllData() {
    try {
        const [colRes, romRes, proRes, couRes, schRes, feeRes, spExRes] = await Promise.all([
            window.flexamApi.colleges.list(),
            window.flexamApi.rooms.list(),
            window.flexamApi.proctors.list(),
            window.flexamApi.courses.list(),
            window.flexamApi.schedules.list(),
            window.flexamApi.feedbacks.list(),
            window.flexamApi.special_exams.list()
        ]);

        const myCampus  = (currentUser.campus  || '').trim().toLowerCase();
        const myCollege = (currentUser.college || '').toUpperCase().trim();
        const myUserId  = String(currentUser.id || '');

        // ── Campus-first filter helpers ──────────────────────────────────────
        // For non-schedule items: match campus OR created by this user
        const matchCampus = (item) => !myCampus || (item.campus || '').trim().toLowerCase() === myCampus || (myUserId && String(item.created_by || '') === myUserId);

        // For schedules: strict campus match only
        const matchScheduleCampus = (s) => {
            if (!myCampus) return true;
            const sc = (s.campus || s.room_campus || '').trim().toLowerCase();
            if (!sc) return true; // no campus set yet
            return sc === myCampus;
        };

        // Courses: campus match is sufficient — college filter removed so all
        // courses belonging to this campus are visible regardless of college field
        const filteredCourses = (couRes.data || []).filter(c => matchCampus(c));

        // Schedules: filter by campus (include no-campus ones as fallback)
        const filteredSchedules = (schRes.data || []).filter(s => matchScheduleCampus(s));

        // Schedules for View Schedules — restricted to the Head's own campus only
        allSchedulesRaw = (schRes.data || []).filter(s => matchScheduleCampus(s));

        // Proctors: campus match only — show all proctors in the same campus as the Head
        const filteredProctors = (proRes.data || []).filter(p => matchCampus(p));

        // Rooms: filter by campus
        const filteredRooms = (romRes.data || []).filter(r => matchCampus(r));

        // Colleges: filter by campus
        const filteredColleges = (colRes.data || []).filter(c => matchCampus(c));

        // Feedbacks: API already scopes by college server-side (feedbacks.php requireAuth + role check).
        // Do NOT re-filter by campus here — feedback submissions don't store a campus field,
        // so matchCampus(f) would incorrectly drop all feedbacks.
        const filteredFeedbacks = (feeRes.data || []);
        window._specialExams = (spExRes.data || []).filter(e =>
            !myCampus || (e.campus || '').trim().toLowerCase() === myCampus
        );

        allData = [
            ...filteredColleges.map(i => ({ ...i, type: 'college' })),
            ...filteredRooms.map(i => ({ ...i, type: 'room' })),
            ...filteredProctors.map(i => ({ ...i, type: 'proctor' })),
            ...filteredCourses.map(i => ({ ...i, type: 'course' })),
            ...filteredSchedules.map(i => ({ ...i, type: 'schedule' })),
            ...filteredFeedbacks.map(i => ({ ...i, type: 'feedback' }))
        ];

    } catch (err) { console.warn('API load failed:', err); }
    renderApp();
}
document.addEventListener('DOMContentLoaded', () => {
    activityLog('Session Started', `Logged in as Program Head`);
    refreshAllData();
});

// ── Styled block modal (replaces raw alert) ───────────────────────────────────
function _showImportBlockModal(message) {
    const existing = document.getElementById('_importBlockModal');
    if (existing) existing.remove();
    const modal = document.createElement('div');
    modal.id = '_importBlockModal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;';
    modal.innerHTML = `
    <div style="background:white;border-radius:1rem;max-width:440px;width:100%;padding:1.75rem;box-shadow:0 20px 60px rgba(0,0,0,0.2);text-align:center;">
        <div style="width:52px;height:52px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;">⛔</div>
        <h3 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0 0 0.5rem;">Import Blocked</h3>
        <p style="font-size:0.85rem;color:#64748b;margin:0 0 1.25rem;line-height:1.5;">${message}</p>
        <button onclick="document.getElementById('_importBlockModal').remove()"
            style="padding:0.6rem 1.75rem;background:#047857;color:white;border:none;border-radius:0.6rem;font-size:0.875rem;font-weight:700;cursor:pointer;">OK</button>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
}

// ── Real-time sync — polls all data types every 5 s, re-renders only on change ──
(function startRealtimeSync() {
    // Per-type fingerprints: count|firstId|lastId
    const _hashes = { schedule: '', college: '', room: '', proctor: '', course: '' };
    let _paused = false;

    document.addEventListener('flexam:importStart', () => { _paused = true;  });
    document.addEventListener('flexam:importEnd',   () => { _paused = false; });

    function _fp(items) {
        return items.length + '|' + (items[0]?.id ?? '') + '|' + (items[items.length - 1]?.id ?? '');
    }

    async function _poll() {
        if (_paused) return;
        try {
            const [colRes, romRes, proRes, couRes, schRes] = await Promise.all([
                window.flexamApi.colleges.list(),
                window.flexamApi.rooms.list(),
                window.flexamApi.proctors.list(),
                window.flexamApi.courses.list(),
                window.flexamApi.schedules.list(),
            ]);

            // Mirror the exact same filters used in refreshAllData
            const myCampus  = (currentUser.campus  || '').trim().toLowerCase();
            const myCollege = (currentUser.college || '').toUpperCase().trim();
            const myUserId  = String(currentUser.id || '');

            const matchCampus = (item) =>
                !myCampus ||
                (item.campus || '').trim().toLowerCase() === myCampus ||
                (myUserId && String(item.created_by || '') === myUserId);

            const matchScheduleCampus = (s) => {
                if (!myCampus) return true;
                const sc = (s.campus || s.room_campus || '').trim().toLowerCase();
                return !sc || sc === myCampus;
            };

            const fresh = {
                college:  (colRes.data || []).filter(c => matchCampus(c)),
                room:     (romRes.data || []).filter(r => matchCampus(r)),
                proctor:  (proRes.data || []).filter(p => matchCampus(p)),
                course:   (couRes.data || []).filter(c => matchCampus(c)),
                schedule: (schRes.data || []).filter(s => matchScheduleCampus(s)),
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
                // keep feedbacks — not polled (only change on explicit user action)
                ...allData.filter(d => d.type === 'feedback'),
            ];

            // Also keep allSchedulesRaw in sync (used by View Schedules)
            allSchedulesRaw = fresh.schedule;

            renderApp();

            // Flash live dot
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

    setInterval(_poll, 5000);
})();

// ─────────────────────────────────────────────────────────────────────────────
// 4. SIGN OUT — shows confirmation modal before logging out
// ─────────────────────────────────────────────────────────────────────────────
window.handleSignOut = function() {
    if (document.getElementById('logoutConfirmModal')) return;

    const modal = document.createElement('div');
    modal.id = 'logoutConfirmModal';
    modal.className = 'logout-modal-overlay';
    modal.innerHTML = `
    <div class="logout-modal-box">
        <div class="lm-header">
            <div class="lm-icon">
                <svg width="28" height="28" fill="none" stroke="#ef4444" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>
            <p class="lm-title">Sign Out</p>
            <p class="lm-subtitle">Are you sure you want to sign out?</p>
        </div>
        <div class="lm-footer">
            <button class="lm-btn-cancel" onclick="document.getElementById('logoutConfirmModal').remove()">
                Cancel
            </button>
            <button class="lm-btn-confirm" onclick="sessionStorage.clear();localStorage.removeItem('currentUser');window.location.href='../logout.php';">
                Sign Out
            </button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) modal.remove();
    });
};

// ─────────────────────────────────────────────────────────────────────────────
// 5. HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    const t = document.createElement('div');
    t.className = `toast ${type === 'success' ? 'bg-emerald-700' : 'bg-red-600'}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function exportToCSV(headers, rows, filename) {
    const csv = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c||'').replace(/"/g,'""')}"`).join(','))].join('\n');
    const a = Object.assign(document.createElement('a'), { href: URL.createObjectURL(new Blob([csv], {type:'text/csv'})), download: filename });
    a.click(); URL.revokeObjectURL(a.href);
    showToast(`Exported ${filename} successfully!`);
}
function downloadTemplate(headers, sample, filename) { exportToCSV(headers, [sample], filename); }

function activeFilterTags(tags) {
    if (!tags.length) return '';
    return `<div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100 mt-2">
        ${tags.map(t => `
        <span class="filter-tag">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
            ${t.label}
            <button onclick="${t.clear}" title="Remove filter">×</button>
        </span>`).join('')}
        <button onclick="${tags.map(t=>t.clear).join(';')}" class="text-xs text-slate-400 hover:text-slate-600 underline transition ml-1">Clear all</button>
    </div>`;
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. RENDER ENGINE
// ─────────────────────────────────────────────────────────────────────────────
function renderApp() {
    // Always close the course custom dropdown on any re-render
    const _dd = document.getElementById('smCourseDd');
    if (_dd) _dd.style.display = 'none';
    const _btn = document.getElementById('smCourseBtn');
    if (_btn) { _btn.style.borderColor = ''; _btn.style.boxShadow = ''; }
    const app = document.getElementById('app');
    if (!app) return;

    // Remember if course search was focused and its cursor position
    const prevFocusId  = document.activeElement?.id;
    const prevSelStart = document.activeElement?.selectionStart;
    const prevSelEnd   = document.activeElement?.selectionEnd;

    app.innerHTML = `
        <div class="h-full flex overflow-hidden">
            ${renderSidebar()}
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-left">
                ${renderHeader()}
                <main class="flex-1 overflow-y-auto p-6 bg-slate-50 relative">
                    ${renderMainContent()}
                </main>
            </div>
        </div>`;
    attachHandlers();

    // Restore focus + caret so typing in search inputs feels uninterrupted
    if (prevFocusId) {
        const el = document.getElementById(prevFocusId);
        if (el && typeof el.focus === 'function') {
            el.focus();
            if (prevSelStart !== undefined && el.setSelectionRange) {
                try { el.setSelectionRange(prevSelStart, prevSelEnd); } catch(e) {}
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. SIDEBAR
// ─────────────────────────────────────────────────────────────────────────────
function renderSidebar() {
    const menu = [
        { id:'dashboard',     label:'Dashboard',   icon:'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
        { id:'view-schedules',label:'View Schedules', icon:'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' },
        { id:'schedule-mgmt', label:'Schedule', labelSecond:'Management', icon:'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', multiline:true },
        { id:'analytics',     label:'Analytics',   icon:'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z' },
        { id:'feedbacks',     label:'Feedbacks',   icon:'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z' },
        { id:'calendar',      label:'Calendar',    icon:'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
        { id:'courses',       label:'Courses',     icon:'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
        { id:'proctors',      label:'Proctors',    icon:'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' }
    ];
    return `
    <div id="sidebarOverlay" onclick="closeMobileSidebar()"></div>
    <aside id="sidebar" class="w-64 bg-white border-r border-slate-200 flex flex-col z-20 shrink-0">
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
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto mt-2 text-left">
            ${menu.map(m => `
                <button data-view="${m.id}" class="sidebar-link w-full ${currentView===m.id?'active':''}" onclick="closeMobileSidebar()">
                    <svg class="w-5 h-5 shrink-0 ${currentView===m.id?'text-emerald-600':'text-slate-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${m.icon}"/>
                    </svg>
                    ${m.multiline
                        ? `<span class="flex flex-col text-left"><span>${m.label}</span><span>${m.labelSecond}</span></span>`
                        : `<span>${m.label}</span>`}
                </button>`).join('')}
        </nav>
        <div class="p-4 mt-auto">
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 bg-emerald-600 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-sm shrink-0">
                        ${currentUser.full_name ? currentUser.full_name.charAt(0).toUpperCase() : 'P'}
                    </div>
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-semibold text-slate-700 truncate">${currentUser.full_name || 'Program Head'}</p>
                        <div class="flex flex-wrap items-center gap-1 mt-0.5">
                            <span class="inline-block px-2 py-0.5 text-[10px] uppercase font-bold tracking-wide rounded-full bg-emerald-100 text-emerald-700">Head</span>
                            ${currentUser.campus ? `<span class="inline-block px-2 py-0.5 text-[10px] font-bold tracking-wide rounded-full bg-blue-100 text-blue-700 truncate max-w-full">${currentUser.campus}</span>` : ''}
                        </div>
                        ${currentUser.college ? `<div class="flex flex-wrap items-center gap-1 mt-1">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 truncate max-w-full">
                                <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                ${currentUser.college}
                            </span>
                        </div>` : ''}
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

// ─────────────────────────────────────────────────────────────────────────────
// 8. HEADER
// ─────────────────────────────────────────────────────────────────────────────
function renderHeader() {
    const titles = {
        'dashboard':'Dashboard','view-schedules':'Examination Schedules',
        'schedule-mgmt':'Schedule Management','analytics':'Analytics Dashboard',
        'feedbacks':'Student Feedbacks','calendar':'Exam Calendar',
        'courses':'Courses','proctors':'Proctor Management'
    };
    const todayStr = new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

    const approvedScheds = allData.filter(d=>d.type==='schedule'&&d.status==='Approved');
    const rejectedScheds = allData.filter(d=>d.type==='schedule'&&d.status==='Rejected');
    const allNotifScheds = [...approvedScheds, ...rejectedScheds];

    const unreadCount = allNotifScheds.filter(s => !readNotifIds.has(String(s.id))).length;
    const hasAnyNotifs = allNotifScheds.length > 0;

    const fmtDate = s => {
        const raw = s.exam_date||s.date||'';
        if (!raw) return '—';
        return new Date(raw+'T00:00:00').toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
    };

    function notifItem(s, statusType) {
        const isRead = readNotifIds.has(String(s.id));
        const isApproved = statusType === 'approved';
        return `
        <div class="notif-item flex items-start gap-3 px-4 py-2.5 hover:bg-slate-50 transition border-b border-slate-50 last:border-0 ${isRead ? 'is-read' : ''}">
            <div class="w-8 h-8 ${isApproved ? 'bg-emerald-100' : 'bg-red-100'} rounded-xl flex items-center justify-center shrink-0 mt-0.5">
                ${isApproved
                    ? `<svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
                    : `<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate">${s.course_name||s.course_code||'—'}</p>
                <p class="text-xs text-slate-500 mt-0.5">${s.exam_type||'—'} · ${fmtDate(s)}</p>
                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded-full text-[10px] font-bold ${isApproved ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'}">
                    ${isApproved ? '✓ Approved' : '✕ Rejected'}
                </span>
            </div>
            ${!isRead ? `
            <button onclick="markNotifRead(${s.id})" title="Mark as read"
                class="mark-read-btn shrink-0 mt-1 w-6 h-6 flex items-center justify-center rounded-full bg-slate-100 hover:bg-red-100 text-slate-400 hover:text-red-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </button>` : `
            <span class="shrink-0 mt-1 w-6 h-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-300" title="Read">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                </svg>
            </span>`}
        </div>`;
    }

    let dropdownBody = '';
    if (!hasAnyNotifs) {
        dropdownBody = `<div class="py-10 text-center">
            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <p class="text-sm font-semibold text-slate-600">No updates yet</p>
            <p class="text-xs text-slate-400 mt-0.5">You'll be notified when schedules are reviewed</p>
        </div>`;
    } else {
        dropdownBody = `<div class="max-h-72 overflow-y-auto">`;
        if (approvedScheds.length > 0) {
            dropdownBody += `<div class="px-4 pt-3 pb-1"><p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest flex items-center gap-1"><span class="inline-block w-2 h-2 bg-emerald-500 rounded-full"></span>Approved (${approvedScheds.length})</p></div>`;
            dropdownBody += approvedScheds.slice(0,5).map(s => notifItem(s, 'approved')).join('');
        }
        if (rejectedScheds.length > 0) {
            dropdownBody += `<div class="px-4 pt-3 pb-1 ${approvedScheds.length>0?'border-t border-slate-100':''}"><p class="text-[10px] font-bold text-red-500 uppercase tracking-widest flex items-center gap-1"><span class="inline-block w-2 h-2 bg-red-500 rounded-full"></span>Rejected (${rejectedScheds.length})</p></div>`;
            dropdownBody += rejectedScheds.slice(0,5).map(s => notifItem(s, 'rejected')).join('');
        }
        dropdownBody += `</div>`;
        if (unreadCount > 0) {
            dropdownBody += `
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50">
                <button onclick="markAllNotifsRead()" class="w-full flex items-center justify-center gap-1.5 text-xs font-semibold text-red-600 hover:text-red-700 transition py-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Mark all as read
                </button>
            </div>`;
        } else {
            dropdownBody += `
            <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50 text-center">
                <span class="text-xs text-slate-400 font-medium flex items-center justify-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                    All caught up
                </span>
            </div>`;
        }
    }

    return `
    <header class="top-header flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <button id="hamburgerBtn" class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition" onclick="openMobileSidebar()" aria-label="Open menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="text-left">
                <h2 class="text-lg md:text-xl font-bold text-slate-900 leading-tight">${titles[currentView]||'Portal'}</h2>
                <p class="text-xs md:text-sm text-slate-400 mt-0.5 hidden sm:block">Our Lady of Fatima University</p>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <span class="text-sm font-medium text-slate-500 hidden md:block">${todayStr}</span>
            <span id="_realtimeDot" title="Live sync active" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:#047857;opacity:0;transition:opacity 0.4s;"><span style="width:8px;height:8px;background:#22c55e;border-radius:50%;display:inline-block;animation:_pulse 1s ease-in-out infinite;"></span>Live</span>
            <style>@keyframes _pulse{0%,100%{opacity:1}50%{opacity:.4}}</style>
            <div class="flex items-center gap-2">
               <div class="relative" id="notifWrapper">
                    <button id="notifBtn" onclick="toggleHeadNotif()" class="p-2 text-slate-500 hover:bg-slate-100 rounded-full transition relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        ${unreadCount > 0 ? `<span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none shadow">${unreadCount > 99 ? '99+' : unreadCount}</span>` : ''}
                    </button>
                    <div id="headNotifDropdown" class="hidden absolute right-0 top-12 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">Schedule Updates</h3>
                                <p class="text-xs text-slate-400">${approvedScheds.length} approved · ${rejectedScheds.length} rejected${unreadCount > 0 ? ` · <span class="text-red-600 font-semibold">${unreadCount} unread</span>` : ''}</p>
                            </div>
                            ${unreadCount > 0 ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">${unreadCount} new</span>` : ''}
                        </div>
                        ${dropdownBody}
                    </div>
                </div>

            </div>
        </div>
    </header>`;
}

// ─────────────────────────────────────────────────────────────────────────────
// 9. MAIN CONTENT ROUTER
// ─────────────────────────────────────────────────────────────────────────────
        function renderMainContent() {
            const schedules         = allData.filter(d => d.type === 'schedule');
            const totalSchedules    = schedules.length;
            const pendingSchedules  = schedules.filter(s => (s.status||'Pending') === 'Pending').length;
            const approvedSchedules = schedules.filter(s => s.status === 'Approved').length;
            const scheduledPercent  = totalSchedules > 0 ? Math.round((approvedSchedules/totalSchedules)*100) : 0;

    // ── DASHBOARD ──────────────────────────────────────────────────────────
    if (currentView === 'dashboard') {
        return `
        <div class="fade-in space-y-6 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                ${renderStat('Total Schedules',totalSchedules,'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','emerald')}
                ${renderStat('Pending',pendingSchedules,'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z','cyan')}
                ${renderStat('Approved',approvedSchedules,'M5 13l4 4L19 7','lime')}
                ${renderStat('Efficiency',scheduledPercent+'%','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','emerald')}
            </div>
            <!-- General / dashboard announcement banner -->
            <div id="flexAnnBanner_all" class="space-y-3 mb-2" style="display:none;"></div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div class="lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm p-6 min-h-[400px] flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-800">Recent Schedules</h3>
                        <button onclick="currentView='view-schedules';vsCurrentPage=1;sessionStorage.setItem('head_currentView',currentView);renderApp()" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">View all →</button>
                    </div>
                    ${schedules.length > 0 ? `
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full">
                            <thead><tr class="border-b border-slate-200">
                                <th class="text-left text-xs font-bold text-slate-500 uppercase pb-3 px-2">Course</th>
                                <th class="text-left text-xs font-bold text-slate-500 uppercase pb-3 px-2">Exam Type</th>
                                <th class="text-left text-xs font-bold text-slate-500 uppercase pb-3 px-2">Date & Time</th>
                                <th class="text-left text-xs font-bold text-slate-500 uppercase pb-3 px-2">Room</th>
                                <th class="text-left text-xs font-bold text-slate-500 uppercase pb-3 px-2">Status</th>
                            </tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                ${schedules.slice(-8).reverse().map(s => {
                                    const status = s.status||'Pending';
                                    const sc = status==='Approved'?'bg-emerald-100 text-emerald-700':status==='Rejected'?'bg-red-100 text-red-700':'bg-orange-100 text-orange-700';
                                    const rawDate = s.exam_date||s.date||'';
                                    const dateDisplay = (() => { if (!rawDate || rawDate === '0000-00-00' || rawDate === '0000-00-00 00:00:00') return '—'; try { const d = new Date(rawDate.substring(0,10)+'T00:00:00'); return isNaN(d.getTime()) ? '—' : d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); } catch(e) { return '—'; } })();
                                    return `<tr class="hover:bg-slate-50">
                                        <td class="py-3 px-2"><div class="font-semibold text-sm text-slate-800">${s.course_code||'—'}</div><div class="text-xs text-slate-500">${s.course_name||''}</div></td>
                                        <td class="py-3 px-2 text-sm text-slate-600">${s.exam_type||'—'}</td>
                                        <td class="py-3 px-2"><div class="text-sm text-slate-700 font-medium">${dateDisplay}</div><div class="text-xs text-slate-500 mt-0.5">${s.time_slot||''}</div></td>
                                        <td class="py-3 px-2 text-sm text-slate-600">${s.is_online ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;">Online</span>' : (s.room_name||s.room||'—')}</td>
                                        <td class="py-3 px-2"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${sc}">${status}</span></td>
                                    </tr>`;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>` : `<div class="flex-1 flex items-center justify-center"><p class="text-slate-400 text-sm">No schedules added yet</p></div>`}
                </div>
                <div class="lg:col-span-2 space-y-4">
                    <h3 class="font-bold text-slate-800 text-lg text-left">Quick Actions</h3>
                    <div class="grid grid-cols-3 gap-0">
                        ${renderIconAction('Add Course','M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253','emerald',"openAddCourseModal()")}
                        ${renderIconAction('Add Schedule','M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','blue',"openAddScheduleModal()")}
                        ${renderIconAction('Add Proctors','M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z','purple',"openAddProctorModal()")}
                        ${renderIconAction('Analytics','M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','amber',"currentView='analytics';analyticsPeriod='all';analyticsDateFrom='';analyticsDateTo='';analyticsSchedPage=0;analyticsCoursePage=0;analyticsRejPage=0;analyticsFeedPage=0;analyticsRoomUtilPage=0;sessionStorage.setItem('head_currentView',currentView);renderApp()")}
                        ${renderIconAction('Calendar','M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z','green',"openCalendarModal()")}
                    </div>
                </div>
            </div>
        </div>`;
    }

    // ── VIEW SCHEDULES ─────────────────────────────────────────────────────
    if (currentView === 'view-schedules') {
        // Use campus-filtered schedules — Head users only see their own campus
        const vsSchedules = allSchedulesRaw.length > 0 ? allSchedulesRaw : allData.filter(d => d.type === 'schedule');
        const vsCollegeOptions = allData.filter(d => d.type === 'college' && d.code)
            .reduce((acc, c) => { if (!acc.find(x => x.code === c.code)) acc.push({ code: c.code, name: c.name || '' }); return acc; }, [])
            .sort((a, b) => a.code.localeCompare(b.code));
        const vsSemesters = [...new Set(vsSchedules.map(s => s.semester||'').filter(Boolean))].sort();

        return `
        <div class="fade-in space-y-0">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <!-- Search + Filters bar -->
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
                    ${SEMESTERS.map(s => '<option value="' + s + '">' + s + '</option>').join('')}
                </select>
                <select id="viewSchedCourse" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[110px]">
                    <option value="">All Courses</option>
                    ${[...new Set(allData.filter(d => d.type === 'course' && d.course_code).map(c => c.course_code))].sort().map(c => `<option value="${c}">${c}</option>`).join('')}
                </select>
                <select id="viewSchedType" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[110px]">
                    <option value="">All Exams</option>
                    <option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option>
                </select>
                <select id="viewSchedYear" onchange="viewSchedFilter()" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 max-w-[100px]">
                    <option value="">All Levels</option>
                    ${YEAR_LEVELS.map(y=>`<option value="${y}">${y}</option>`).join("")}
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

            <!-- Amber hint + Export PDF -->
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-start gap-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 max-w-xl">
                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>To export <strong>only specific schedule</strong>, please apply filters first (e.g. College, Semester, Year Level, Course) before clicking <strong>Export PDF</strong>. Exporting without filters will include all schedules.</span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span id="viewSchedCount" class="text-xs text-slate-400">${vsSchedules.length} records</span>
                    <button onclick="vsExportPDF()"
                        class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition shadow-sm whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>

            <!-- Table -->
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
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date &amp; Time</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Duration</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Room</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Proctor</th>
                            <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                        </tr>
                    </thead>
                    <tbody id="viewSchedBody" class="divide-y divide-slate-100">
                        ${vsSchedules.length === 0
                            ? `<tr><td colspan="12" class="py-24 text-center">
                                   <div class="flex flex-col items-center gap-3">
                                       <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center">
                                           <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                       </div>
                                       <p class="text-slate-400 text-sm font-medium">No schedules found</p>
                                       <p class="text-slate-300 text-xs">Try adjusting your search or filters</p>
                                   </div>
                               </td></tr>`
                            : vsSchedules.map(sched => {
                                const status = sched.status || 'Pending';
                                const college = resolveCollegeAcronym(sched);
                                const { courseName, semester, roomName, proctorName, examDate } = resolveScheduleDisplay(sched);
                                const statusBadge = status === 'Approved'
                                    ? '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">✓ Approved</span>'
                                    : status === 'Rejected'
                                    ? '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">✕ Rejected</span>'
                                    : '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">⏳ Pending</span>';
                                return '<tr class="hover:bg-slate-50/50 transition"'
                                    + ' data-college-row="' + esc(college) + '"'
                                    + ' data-semester-row="' + esc(semester || sched.semester || '') + '"'
                                    + ' data-search="' + esc((sched.course_code||courseName||'').toLowerCase() + ' ' + college.toLowerCase() + ' ' + (sched.exam_type||'').toLowerCase() + ' ' + (sched.section||'').toLowerCase()) + '"'
                                    + ' data-type-row="' + esc((sched.exam_type||'').toLowerCase()) + '"'
                                    + ' data-year-row="' + esc(sched.year_level || '') + '"'
                                    + ' data-course-row="' + esc((sched.course_code||'').toLowerCase()) + '"'
                                    + ' data-status-row="' + esc(status) + '">'
                                    + '<td class="px-6 py-4 text-sm font-medium text-slate-900">' + esc(sched.course_code || courseName || '—') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + esc(college) + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + (sched.program ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-violet-50 text-violet-700 border border-violet-100">' + esc(sched.program) + '</span>' : '<span class="text-slate-300">—</span>') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + ((() => { const sem = semester || sched.semester || ''; return sem ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">' + esc(sem) + '</span>' : '<span class="text-slate-300">—</span>'; })()) + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + esc(sched.exam_type || '—') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + esc(sched.year_level || '—') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + esc(sched.section || sched.section_name || sched.class_section || '—') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + (examDate || '—') + (sched.time_slot ? '<br><span class="text-xs text-slate-400">' + esc(sched.time_slot) + '</span>' : '') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + esc(sched.duration || '—') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + esc(roomName || '—') + '</td>'
                                    + '<td class="px-6 py-4 text-sm text-slate-600">' + esc(proctorName || '—') + '</td>'
                                    + '<td class="px-6 py-4">' + statusBadge + '</td>'
                                    + '</tr>';
                            }).join('')}
                    </tbody>
                </table>
            </div>
            <!-- View Schedules Pagination -->
            <div id="vsPaginationBar" class="px-4 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-2 flex-wrap">
                <span id="vsPaginationInfo" class="text-xs text-slate-500"></span>
                <div id="vsPaginationBtns" class="flex items-center gap-1"></div>
            </div>
        </div>
        </div>`;
    }

    // ── SCHEDULE MANAGEMENT ────────────────────────────────────────────────
    if (currentView === 'schedule-mgmt') {
        if (!window._schedTab) window._schedTab = 'schedules';
        const activeTab = window._schedTab;
        const courses  = allData.filter(d => d.type === 'course');
        const schedCourseOptions = [...new Map(
            courses.filter(c => c.course_code || c.code)
                   .map(c => [c.course_code||c.code, { code: c.course_code||c.code, name: c.course_name||c.name||c.course_code||c.code }])
        ).values()].sort((a,b) => a.code.localeCompare(b.code));

        let filtered = schedules;
        if (scheduleMgmtSearch) {
            const q = scheduleMgmtSearch.toLowerCase();
            filtered = filtered.filter(s =>
                (s.course_code||'').toLowerCase().includes(q) ||
                (s.course_name||'').toLowerCase().includes(q) ||
                (s.room_name||s.room||'').toLowerCase().includes(q) ||
                (s.exam_type||'').toLowerCase().includes(q) ||
                (s.proctor_name||'').toLowerCase().includes(q)
            );
        }
        if (scheduleMgmtCampus   !== 'all') filtered = filtered.filter(s => (s.campus||s.room_campus||'') === scheduleMgmtCampus);
        if (scheduleMgmtCourse   !== 'all') filtered = filtered.filter(s => s.course_code === scheduleMgmtCourse);
        if (scheduleMgmtExamType !== 'all') filtered = filtered.filter(s => s.exam_type === scheduleMgmtExamType);
        if (scheduleMgmtSemester !== 'all') filtered = filtered.filter(s => (s.semester||'') === scheduleMgmtSemester);

        const smSemesters = [...new Set(schedules.map(s => s.semester||'').filter(Boolean))].sort();
        const tags = [];
        if (scheduleMgmtCampus   !== 'all') tags.push({ label: `Campus: ${scheduleMgmtCampus}`,     clear: "renderApp()" });
        if (scheduleMgmtCourse   !== 'all') tags.push({ label: `Course: ${scheduleMgmtCourse}`,     clear: "scheduleMgmtCourse='all';renderApp()" });
        if (scheduleMgmtExamType !== 'all') tags.push({ label: `Type: ${scheduleMgmtExamType}`,     clear: "scheduleMgmtExamType='all';renderApp()" });
        if (scheduleMgmtSemester !== 'all') tags.push({ label: `Semester: ${scheduleMgmtSemester}`, clear: "scheduleMgmtSemester='all';renderApp()" });

        const smTotalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        if (smCurrentPage > smTotalPages) smCurrentPage = smTotalPages;
        if (smCurrentPage < 1) smCurrentPage = 1;
        const smPageStart = (smCurrentPage - 1) * PAGE_SIZE;
        const smPageEnd   = smPageStart + PAGE_SIZE;
        const smPagedRows = filtered.slice(smPageStart, smPageEnd);

        const { autoResolvedPct, autoResolvedLabel, conflictedCount } = computeAnalyticsExtras(schedules);
        const arColor = conflictedCount === 0 ? 'text-emerald-600' : autoResolvedPct >= 80 ? 'text-purple-600' : 'text-red-500';

        return `
        <div class="fade-in space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-left">
                <div class="stat-card-small">
                    <div><p class="text-[10px] font-bold text-slate-400 uppercase mb-0.5">Total Schedules</p><h3 class="text-2xl font-bold text-slate-800">${schedules.length}</h3></div>
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
                <div id="autoResolvedCard" class="stat-card-small cursor-pointer hover:border-purple-300 hover:shadow-md transition group" onclick="autoResolveConflicts()" title="Click to check / auto-resolve scheduling conflicts">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 mb-0.5">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Auto-Resolved</p>
                            <span class="relative flex h-1.5 w-1.5" title="Live — updates every 5 seconds">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full ${conflictedCount === 0 ? 'bg-emerald-400' : 'bg-purple-400'} opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 ${conflictedCount === 0 ? 'bg-emerald-500' : 'bg-purple-500'}"></span>
                            </span>
                        </div>
                        <h3 class="text-2xl font-bold ${arColor}">${autoResolvedPct}%</h3>
                        <p class="text-[10px] ${conflictedCount === 0 ? 'text-emerald-400' : 'text-purple-400'} mt-0.5 group-hover:text-purple-600 transition">${conflictedCount === 0 ? 'No conflicts detected' : autoResolvedLabel}</p>
                    </div>
                    <div class="w-9 h-9 ${conflictedCount === 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-purple-50 text-purple-600'} rounded-md flex items-center justify-center shrink-0 ml-4 group-hover:bg-purple-100 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg></div>
                </div>
            </div>

            <!-- TAB STRIP -->
            <div class="flex border-b border-slate-200 bg-white rounded-t-xl overflow-hidden shadow-sm">
                <button onclick="window._schedTab='schedules';smCurrentPage=1;renderApp()" class="flex items-center gap-2 px-6 py-3.5 text-sm font-semibold border-b-2 transition whitespace-nowrap ${activeTab==='schedules' ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'}">
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

            <!-- TAB 1: SCHEDULES -->
            <div style="display:${activeTab==='schedules'?'block':'none'}">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm text-left space-y-3">
                <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></span>
                        <input type="text" id="scheduleMgmtSearch" value="${scheduleMgmtSearch}" placeholder="Search by course, room, proctor, exam type..."
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <button onclick="downloadScheduleTemplate()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Template
                        </button>
                        <button onclick="importSchedules()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>Import
                        </button>
                        <button onclick="exportSchedules()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Export
                        </button>
                        <button onclick="openAddScheduleModal()" class="btn-action-large bg-emerald-600 text-white hover:bg-emerald-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Schedule
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                        Filter:
                    </span>
                    <div style="position:relative;display:inline-block;">
                        <button type="button" id="smCourseBtn" class="filter-select" style="cursor:pointer;white-space:nowrap;"
                            onmousedown="event.preventDefault();var d=document.getElementById('smCourseDd'),b=document.getElementById('smCourseBtn'),open=d.style.display==='block';d.style.display=open?'none':'block';b.style.borderColor=open?'':'#10b981';b.style.boxShadow=open?'':'0 0 0 3px rgba(16,185,129,0.1)';">
                            ${scheduleMgmtCourse==='all'?'All Courses':scheduleMgmtCourse}
                        </button>
                        <div id="smCourseDd" style="display:none;position:absolute;top:calc(100% + 2px);left:0;z-index:99999;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;box-shadow:0 4px 16px rgba(0,0,0,0.10);min-width:150px;max-height:260px;overflow-y:auto;padding:4px 0;">
                            <div class="sm-dd-item" onmousedown="event.preventDefault();scheduleMgmtCourse='all';smCurrentPage=1;document.getElementById('smCourseDd').style.display='none';renderApp();"
                                style="padding:0.5rem 0.85rem;font-size:0.82rem;cursor:pointer;color:#1f2937;background:#f8fafc;">All Courses</div>
                            ${schedCourseOptions.map(c=>`<div class="sm-dd-item" onmousedown="event.preventDefault();scheduleMgmtCourse='${c.code}';smCurrentPage=1;document.getElementById('smCourseDd').style.display='none';renderApp();"
                                style="padding:0.5rem 0.85rem;font-size:0.82rem;cursor:pointer;color:#1f2937;white-space:nowrap;background:#f8fafc;">${c.code}</div>`).join('')}
                        </div>
                    </div>
                    <select id="smExamTypeFilter" class="filter-select" onmousedown="closeSmCourseDd()">
                        <option value="all">All Exam Types</option>
                        ${['Prelim','Midterm','Final','Summer'].map(t=>`<option value="${t}" ${scheduleMgmtExamType===t?'selected':''}>${t}</option>`).join('')}
                    </select>
                    <select id="smSemesterFilter" class="filter-select" onmousedown="closeSmCourseDd()">
                        <option value="all">All Semesters</option>
                        <option value="1st Semester" ${scheduleMgmtSemester==='1st Semester'?'selected':''}>1st Semester</option>
                        <option value="2nd Semester" ${scheduleMgmtSemester==='2nd Semester'?'selected':''}>2nd Semester</option>
                    </select>
                    ${(scheduleMgmtCampus !== 'all' || scheduleMgmtCourse !== 'all' || scheduleMgmtExamType !== 'all' || scheduleMgmtSemester !== 'all' || scheduleMgmtSearch !== '') ? `
                    <button onclick="scheduleMgmtSearch='';scheduleMgmtCourse='all';scheduleMgmtExamType='all';scheduleMgmtSemester='all';smCurrentPage=1;renderApp()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-semibold transition border border-red-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear Filters
                    </button>` : ''}
                </div>
                ${activeFilterTags(tags)}
            </div>

            <div class="flex items-center justify-between px-1">
                <p class="text-sm text-slate-500">Showing <span class="font-semibold text-slate-700">${smPagedRows.length}</span> of <span class="font-semibold text-slate-700">${filtered.length}</span> schedules${filtered.length !== schedules.length ? ` (${schedules.length} total)` : ''}</p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                <!-- ── Bulk Action Toolbar ── -->
                <div id="schedBulkBar" style="display:none;" class="px-4 py-2.5 bg-emerald-50 border-b border-emerald-200 flex flex-wrap items-center gap-3">
                    <span id="schedBulkCount" class="text-xs font-bold text-emerald-700"></span>
                    <div class="flex items-center gap-2 ml-auto flex-wrap">
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

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200"><tr>
                        <th class="pl-4 pr-2 py-3 text-center w-10">
                            <input type="checkbox" id="schedSelectAll" title="Select all visible schedules"
                                onchange="toggleAllSchedRows(this.checked)"
                                class="w-4 h-4 rounded border-slate-300 text-emerald-600 accent-emerald-600 cursor-pointer">
                        </th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Course</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Program</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Semester</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Exam Type</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Year Level</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Section</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date & Time</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Duration</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Room</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Proctor</th>
                        <th class="px-6 py-3 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-3 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                    </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            ${filtered.length === 0 ? `<tr><td colspan="13" class="py-20 text-center text-sm text-slate-400">
                                ${(scheduleMgmtSearch||scheduleMgmtCourse!=='all'||scheduleMgmtExamType!=='all'||scheduleMgmtSemester!=='all') ? 'No schedules found.' : 'No schedules yet. Click "Add Schedule" to create one.'}
                            </td></tr>` : smPagedRows.map(s => {
                                const status = s.status || 'Pending';
                                const safeId = String(s.id || '').replace(/'/g, "\\'");
                                const courseName = s.course_name ? `<div class="font-semibold text-sm text-slate-900">${s.course_code || '—'}</div><div class="text-xs text-slate-500">${s.course_name}</div>` : `<div class="font-semibold text-sm text-slate-900">${s.course_code || '—'}</div>`;
                                const roomName = s.is_online
                                ? '<span style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;">Online</span>'
                                : (s.room_name || s.room || '—');
                                const proctorName = s.proctor_name || '—';
                                const rawDate = s.exam_date || s.date || '';
                                const dateDisplay = (() => {
                                    if (!rawDate || rawDate === '0000-00-00' || rawDate === '0000-00-00 00:00:00') return '—';
                                    try { const d = new Date(rawDate.substring(0,10) + 'T00:00:00'); return isNaN(d.getTime()) ? '—' : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); } catch(e) { return '—'; }
                                })();
                                const resolvedSemester = s.semester || (() => { const c = findById('course', s.course_id); return c ? c.semester || '' : ''; })();
                                const statusBadge = status === 'Approved'
                                    ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">✓ Approved</span>`
                                    : status === 'Rejected'
                                    ? `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">✕ Rejected</span>`
                                    : `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">⏳ Pending</span>`;
                                return `
                        <tr class="hover:bg-slate-50/50 transition sched-mgmt-row" data-id="${safeId}">
                            <td class="pl-4 pr-2 py-4 text-center w-10">
                                <input type="checkbox" class="sched-row-cb w-4 h-4 rounded border-slate-300 text-emerald-600 accent-emerald-600 cursor-pointer"
                                    data-id="${safeId}" onchange="onSchedRowCbChange()">
                            </td>
                            <td class="px-6 py-4">${courseName}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${s.program || s.college || '—'}</td>
                            <td class="px-6 py-4">${resolvedSemester ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">' + resolvedSemester + '</span>' : '<span class="text-slate-300">—</span>'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${s.exam_type || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${s.year_level || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${s.section || s.section_name || s.class_section || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${dateDisplay}${s.time_slot ? '<br><span class="text-xs text-slate-400">' + s.time_slot + '</span>' : ''}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${s.duration || '—'}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${roomName}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">${proctorName}</td>
                            <td class="px-6 py-4">${statusBadge}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-1">
                                    <button onclick="editSchedule('${safeId}')" title="Edit" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
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
                <!-- Schedule Management Pagination -->
                <div id="smPaginationBar" class="px-4 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between gap-2 flex-wrap">
                    <span id="smPaginationInfo" class="text-xs text-slate-500"></span>
                    <div id="smPaginationBtns" class="flex items-center gap-1"></div>
                </div>
            </div>
        </div>
            </div><!-- end schedules tab -->

            <!-- TAB 2: SPECIAL EXAM REGISTRATIONS -->
            <div style="display:${activeTab==='special'?'block':'none'}">
            <div class="bg-white rounded-b-xl border border-purple-200 shadow-sm overflow-hidden -mt-px">
                <div class="px-5 py-4 border-b border-purple-100 flex flex-wrap items-center gap-3">
                    <div><h3 class="text-sm font-bold text-slate-800">Special Exam Registrations</h3><p class="text-[11px] text-slate-400">Students registered to take special / removal examinations</p></div>
                    <span class="ml-auto text-xs font-semibold text-purple-600 bg-purple-50 border border-purple-100 px-2.5 py-1 rounded-full">${(window._specialExams||[]).length} registered</span>
                </div>
                <div class="p-4 flex flex-col md:flex-row gap-3 items-center border-b border-slate-100">
                    <div class="relative flex-1 w-full"><input id="spExamSearch" type="text" placeholder="Search by student name, no., college..." oninput="filterSpecialExams()" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-purple-300"></div>
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                        <select id="spExamSYFilter" onchange="filterSpecialExams()" class="filter-select"><option value="">All S.Y.</option>${(() => { const cy=new Date().getFullYear(); const s=new Set((window._specialExams||[]).map(e=>e.school_year).filter(Boolean)); s.add(`${cy}-${cy+1}`); s.add(`${cy-1}-${cy}`); return [...s].sort().reverse().map(sy=>`<option value="${sy}">${sy}</option>`).join(''); })()}</select>
                        <select id="spExamSemFilter" onchange="filterSpecialExams()" class="filter-select"><option value="">All Semesters</option><option>1st Semester</option><option>2nd Semester</option><option>Summer</option></select>
                        <select id="spExamTypeFilter" onchange="filterSpecialExams()" class="filter-select"><option value="">All Types</option><option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option><option>Others</option></select>
                        <button onclick="spExamClearFilters()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800">Clear</button>
                        <button onclick="exportSpecialExamPDF()" class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-slate-600 hover:bg-slate-700 rounded-lg transition shadow-sm"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Export PDF</button>
                        <button onclick="openSpecialExamModal()" class="flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 shadow-sm transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4" stroke-width="2.5"/></svg>Add Registration</button>
                    </div>
                </div>
                <div class="overflow-x-auto"><table class="w-full">
                    <thead class="bg-purple-50 border-b border-purple-100"><tr>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Student No.</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Last Name</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">First Name</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">College / Program</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">S.Y.</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Semester</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Exam Type</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Receipt No.</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">No. of Exams</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Campus</th>
                        <th class="px-4 py-3 text-left text-[11px] font-bold text-purple-500 uppercase tracking-widest">Reason</th>
                        <th class="px-4 py-3 text-right text-[11px] font-bold text-purple-500 uppercase tracking-widest">Actions</th>
                    </tr></thead>
                    <tbody id="specialExamBody" class="divide-y divide-slate-100">
                        ${(() => { const rows=window._specialExams||[]; if(!rows.length) return `<tr><td colspan="12" class="py-16 text-center"><p class="text-slate-400 text-sm font-medium">No special exam registrations yet</p><p class="text-slate-300 text-xs">Click "Add Registration" to register a student</p></td></tr>`; const esc2=s=>{const d=document.createElement('div');d.textContent=String(s??'');return d.innerHTML;}; return rows.map(e=>{
  const courses=(() => { try { return JSON.parse(e.exam_courses||'[]'); } catch(_){return[];} })();
  return `<tr class="hover:bg-purple-50/30 transition sp-exam-row" data-search="${(e.student_no||'').toLowerCase()} ${(e.last_name||'').toLowerCase()} ${(e.first_name||'').toLowerCase()} ${(e.college||'').toLowerCase()}" data-sy="${e.school_year||''}" data-sem="${e.semester||''}" data-type="${(e.exam_type||'').toLowerCase()}" data-campus="${e.campus||''}"><td class="px-4 py-3 text-sm font-mono">${esc2(e.student_no||'—')}</td><td class="px-4 py-3 text-sm font-bold">${esc2(e.last_name||'—')}</td><td class="px-4 py-3 text-sm">${esc2(e.first_name||'—')}</td><td class="px-4 py-3 text-sm"><span class="font-semibold">${esc2(e.college||'—')}</span>${e.program?`<br><span class="text-xs text-slate-400">${esc2(e.program)}</span>`:''}</td><td class="px-4 py-3 text-sm text-slate-600">${esc2(e.school_year||'—')}</td><td class="px-4 py-3 text-sm text-slate-600">${esc2(e.semester||'—')}</td><td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">${esc2(e.exam_type||'—')}</span></td><td class="px-4 py-3 text-sm font-mono">${esc2(e.receipt_no||'—')}</td><td class="px-4 py-3 text-sm text-center font-bold text-purple-700">${esc2(String(e.num_exams||'0'))}</td><td class="px-4 py-3 text-sm">${e.campus?`<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">${esc2(e.campus)}</span>`:'<span class="text-slate-300">—</span>'}</td><td class="px-4 py-3 text-sm text-slate-500 max-w-[160px]"><span class="truncate block">${esc2(e.reason||'—')}</span></td><td class="px-4 py-3 text-right whitespace-nowrap"><button onclick="openViewSpecialExam(${e.id})" class="p-1.5 text-purple-400 hover:text-purple-700 hover:bg-purple-50 rounded-lg transition" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button><button onclick="openSpecialExamModal(${e.id})" class="p-1.5 text-blue-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button><button onclick="deleteSpecialExam(${e.id})" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td></tr>`;}).join(''); })()}
                    </tbody>
                </table></div>
            </div>
            </div><!-- end special tab -->
        </div>`
    }

    // ── ANALYTICS ──────────────────────────────────────────────────────────
    if (currentView === 'analytics') {
        const myCollege = (currentUser.college || '').trim().toUpperCase();
        // schedules is already campus-scoped from refreshAllData; use it directly
        const analyticsSchedules = schedules;
        const today = new Date(); today.setHours(0,0,0,0);
        function inRange(s) {
            const raw = s.exam_date||s.date;
            // For 'all' period, include records even if they have no date set
            if (analyticsPeriod === 'all') return true;
            if (!raw || raw === '0000-00-00' || raw === '0000-00-00 00:00:00') return false;
            const d = new Date(raw.substring(0,10)+'T00:00:00'); d.setHours(0,0,0,0);
            if (isNaN(d.getTime())) return false;
            if (analyticsPeriod==='today') return d.getTime()===today.getTime();
            if (analyticsPeriod==='week') {
                const start=new Date(today); start.setDate(today.getDate()-today.getDay());
                const end=new Date(start);   end.setDate(start.getDate()+6);
                return d>=start && d<=end;
            }
            if (analyticsPeriod==='month') return d.getMonth()===today.getMonth() && d.getFullYear()===today.getFullYear();
            if (analyticsPeriod==='custom') {
                if (analyticsDateFrom && d<new Date(analyticsDateFrom+'T00:00:00')) return false;
                if (analyticsDateTo   && d>new Date(analyticsDateTo  +'T00:00:00')) return false;
                return true;
            }
            return true;
        }
        let fa = analyticsSchedules.filter(inRange);
        // Head analytics: filter by college, then optionally by program
        if (myCollege) {
            fa = fa.filter(s => (s.college||'').trim().toUpperCase() === myCollege);
        }
        // Optional: further filter by specific program if head has one
        const myProgram = (currentUser.program || '').trim().toUpperCase();
        if (myProgram) {
            fa = fa.filter(s => (s.program||'').trim().toUpperCase() === myProgram);
        }

        const faTotal=fa.length, faApproved=fa.filter(s=>s.status==='Approved').length,
              faPending=fa.filter(s=>(s.status||'Pending')==='Pending').length,
              faRejected=fa.filter(s=>s.status==='Rejected').length,
              faEff=faTotal>0?Math.round((faApproved/faTotal)*100):0;
        const periodLabel = analyticsPeriod==='today'?'Today':analyticsPeriod==='week'?'This Week':analyticsPeriod==='month'?'This Month':analyticsPeriod==='custom'?`${analyticsDateFrom||'?'} → ${analyticsDateTo||'?'}`:'All Time';
        const analyticsTags=[];
        if (analyticsPeriod!=='all') analyticsTags.push({label:`Period: ${periodLabel}`, clear:"analyticsPeriod='all';analyticsDateFrom='';analyticsDateTo='';analyticsSchedPage=0;analyticsCoursePage=0;analyticsRejPage=0;analyticsFeedPage=0;renderApp()"});
        // Scope label for display
        const _scopeLabel = myProgram ? myProgram : (myCollege || 'Your College/Program');
        return `
        <div class="fade-in space-y-5 text-left">

            <!-- ── Scope Badge ── -->
            <div class="flex items-center gap-2 px-4 py-2.5 bg-blue-50 border border-blue-200 rounded-xl text-xs font-semibold text-blue-700">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                Showing analytics for: <span class="font-bold text-blue-900">${_scopeLabel}</span>
                ${currentUser.campus ? `<span class="ml-1 text-blue-500">· ${currentUser.campus}</span>` : ''}
            </div>

            <!-- ── Filter Bar ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <div class="flex flex-wrap gap-3 items-center justify-between">
                    <div class="flex flex-wrap gap-2 items-center">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Period:</span>
                        ${['all','today','week','month','custom'].map(p=>{
                            const lbl={all:'All Time',today:'Today',week:'This Week',month:'This Month',custom:'Custom Range'};
                            return `<button onclick="analyticsPeriod='${p}';analyticsSchedPage=0;analyticsCoursePage=0;analyticsRejPage=0;analyticsFeedPage=0;renderApp()" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition ${analyticsPeriod===p?'bg-emerald-600 text-white shadow-sm':'bg-slate-100 text-slate-600 hover:bg-slate-200'}">${lbl[p]}</button>`;
                        }).join('')}
                        ${(analyticsPeriod !== 'all') ? `
                        <button onclick="analyticsPeriod='all';analyticsDateFrom='';analyticsDateTo='';analyticsSchedPage=0;analyticsCoursePage=0;analyticsRejPage=0;analyticsFeedPage=0;renderApp()"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-semibold transition border border-red-200">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear
                        </button>` : ''}
                    </div>
                    <button onclick="exportAnalytics()" class="btn-action-large bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export CSV
                    </button>
                </div>
                ${analyticsPeriod==='custom'?`
                <div class="flex flex-wrap gap-3 items-center pt-3 mt-3 border-t border-slate-100">
                    <span class="text-xs font-semibold text-slate-500">Date Range:</span>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-500">From</label>
                        <input type="date" id="analyticsDateFrom" value="${analyticsDateFrom}"
                            class="text-sm px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-500">To</label>
                        <input type="date" id="analyticsDateTo" value="${analyticsDateTo}"
                            class="text-sm px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                    </div>
                    <span class="text-xs text-slate-400">${faTotal} result${faTotal!==1?'s':''} in range</span>
                </div>`:''}
                ${activeFilterTags(analyticsTags)}
            </div>

            <!-- ── KPI Stat Cards ── -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <!-- Total -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-50 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider truncate">Total</p>
                        <p class="text-2xl font-bold text-slate-800 leading-tight">${faTotal}</p>
                    </div>
                </div>
                <!-- Approved -->
                <div class="bg-white rounded-xl border border-emerald-100 shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider truncate">Approved</p>
                        <p class="text-2xl font-bold text-emerald-600 leading-tight">${faApproved}</p>
                    </div>
                </div>
                <!-- Pending -->
                <div class="bg-white rounded-xl border border-orange-100 shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-orange-400 uppercase tracking-wider truncate">Pending</p>
                        <p class="text-2xl font-bold text-orange-500 leading-tight">${faPending}</p>
                    </div>
                </div>
                <!-- Rejected -->
                <div class="bg-white rounded-xl shadow-sm p-4 flex items-center gap-3" style="border:1px solid ${faRejected>0?'#fecaca':'#e2e8f0'}">
                    <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-red-400 uppercase tracking-wider truncate">Rejected</p>
                        <p class="text-2xl font-bold text-red-600 leading-tight">${faRejected}</p>
                    </div>
                </div>
                <!-- Efficiency -->
                <div class="bg-white rounded-xl border border-blue-100 shadow-sm p-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider truncate">Efficiency</p>
                        <p class="text-2xl font-bold text-blue-600 leading-tight">${faEff}%</p>
                    </div>
                </div>
            </div>



            <!-- ── Room Utilization ── -->
            ${(() => {
                const esc2 = v => String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                const rooms = allData.filter(d => d.type === 'room');
                if (!rooms.length) return '';
                const roomMap = {};
                fa.forEach(s => {
                    const rid = String(s.room_id || s.room || '');
                    if (!rid || rid === '0') return;
                    if (!roomMap[rid]) {
                        const r = rooms.find(r => String(r.id) === rid);
                        roomMap[rid] = {
                            name: r ? (r.building ? r.building+', '+r.name : r.name) : (s.room_name||rid),
                            campus: r ? (r.campus||'') : (s.campus||''),
                            total:0, approved:0, pending:0
                        };
                    }
                    roomMap[rid].total++;
                    if ((s.status||'Pending')==='Approved') roomMap[rid].approved++;
                    if ((s.status||'Pending')==='Pending')  roomMap[rid].pending++;
                });
                const roomEntries = Object.values(roomMap).sort((a,b)=>b.total-a.total);
                if (!roomEntries.length) return '';
                const maxUtil = roomEntries[0].total || 1;
                const UTIL_PG = 10;
                const utilTotal = roomEntries.length;
                const utilPages = Math.max(1, Math.ceil(utilTotal/UTIL_PG));
                const utilCur   = Math.min(typeof analyticsRoomUtilPage!=='undefined'?analyticsRoomUtilPage:0, utilPages-1);
                const utilRows  = roomEntries.slice(utilCur*UTIL_PG, utilCur*UTIL_PG+UTIL_PG);
                return `
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-5 bg-cyan-500 rounded-full"></div>
                            <h3 class="font-bold text-slate-800 text-sm">Room Utilization</h3>
                            <span class="text-xs text-slate-400">— ${_scopeLabel}</span>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">${utilTotal} room${utilTotal!==1?'s':''}</span>
                    </div>
                    <div class="overflow-x-auto"><table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200"><tr>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Room</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Campus</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Total Exams</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Approved</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Pending</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4 w-40">Utilization</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            ${utilRows.map(r => {
                                const pct = Math.round((r.total/maxUtil)*100);
                                return '<tr class="hover:bg-slate-50/60 transition">'
                                    + '<td class="py-3 px-4 text-xs font-semibold text-slate-800">' + esc2(r.name) + '</td>'
                                    + '<td class="py-3 px-4 text-xs text-slate-500">' + (r.campus ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">' + esc2(r.campus) + '</span>' : '—') + '</td>'
                                    + '<td class="py-3 px-4 text-xs font-bold text-slate-800">' + r.total + '</td>'
                                    + '<td class="py-3 px-4"><span class="text-xs font-semibold text-emerald-700">' + r.approved + '</span></td>'
                                    + '<td class="py-3 px-4"><span class="text-xs font-semibold text-orange-600">' + r.pending + '</span></td>'
                                    + '<td class="py-3 px-4"><div class="flex items-center gap-2"><div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-cyan-500 rounded-full" style="width:' + pct + '%"></div></div><span class="text-[10px] text-slate-500 w-7 text-right">' + pct + '%</span></div></td>'
                                    + '</tr>';
                            }).join('')}
                        </tbody>
                    </table></div>
                    ${utilPages > 1 ? (() => {
                        const nc = d => 'w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition text-xs'+(d?' opacity-30 pointer-events-none':'');
                        const pgs=[]; for(let p=0;p<utilPages;p++){if(p===0||p===utilPages-1||(p>=utilCur-2&&p<=utilCur+2))pgs.push(p);else if(pgs[pgs.length-1]!=='…')pgs.push('…');}
                        return '<div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-3">'
                            + '<span class="text-xs text-slate-400">Showing ' + (utilCur*UTIL_PG+1) + '–' + Math.min(utilCur*UTIL_PG+UTIL_PG,utilTotal) + ' of ' + utilTotal + ' rooms</span>'
                            + '<div class="flex items-center gap-1">'
                            + '<button onclick="analyticsRoomUtilPage=Math.max(0,' + (utilCur-1) + ');renderApp()" class="' + nc(utilCur===0) + '" ' + (utilCur===0?'disabled':'') + '>‹</button>'
                            + pgs.map(p=>p==='…'?'<span class="w-7 h-7 flex items-center justify-center text-xs text-slate-400">…</span>':'<button onclick="analyticsRoomUtilPage='+p+';renderApp()" class="w-7 h-7 flex items-center justify-center rounded-lg text-xs font-semibold transition '+(p===utilCur?'bg-cyan-600 text-white shadow-sm':'border border-slate-200 text-slate-600 hover:bg-slate-50')+'">'+(p+1)+'</button>').join('')
                            + '<button onclick="analyticsRoomUtilPage=Math.min(' + (utilPages-1) + ',' + (utilCur+1) + ');renderApp()" class="' + nc(utilCur===utilPages-1) + '" ' + (utilCur===utilPages-1?'disabled':'') + '>›</button>'
                            + '</div></div>';
                    })() : ''}
                </div>`;
            })()}

            <!-- ── Schedule Details Table ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                        <div class="w-1.5 h-5 bg-slate-400 rounded-full"></div>
                        <h3 class="font-bold text-slate-800 text-sm">Schedule Details</h3>
                        <span class="text-xs text-slate-400">— ${periodLabel}</span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">${faTotal} record${faTotal!==1?'s':''}</span>
                </div>
                ${(() => {
                    if (fa.length === 0) return `<div class="py-16 text-center">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">No schedules found</p>
                        <p class="text-xs text-slate-400 mt-1">Try selecting a different period.</p>
                    </div>`;
                    const PAGE_SIZE = 5;
                    const totalPages = Math.ceil(fa.length / PAGE_SIZE);
                    const curPage = Math.min(analyticsSchedPage, totalPages - 1);
                    const pageRows = fa.slice(curPage * PAGE_SIZE, curPage * PAGE_SIZE + PAGE_SIZE);
                    const pageNums = Array.from({length: totalPages}, (_,i) => i);
                    return `
                    <div class="overflow-x-auto"><table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200"><tr>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Course</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Exam Type</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Date</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Room</th>
                            <th class="text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider py-3 px-4">Status</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            ${pageRows.map(s=>{
                                const status=s.status||'Pending';
                                const sb=status==='Approved'?'bg-emerald-100 text-emerald-700':status==='Rejected'?'bg-red-100 text-red-700':'bg-orange-100 text-orange-700';
                                return `<tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3 px-4"><div class="text-xs font-semibold text-slate-800">${s.course_code||'—'}</div><div class="text-[11px] text-slate-400">${s.course_name||''}</div></td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium ${s.exam_type==='Midterm'?'bg-blue-100 text-blue-700':s.exam_type==='Final'?'bg-purple-100 text-purple-700':'bg-amber-100 text-amber-700'}">${s.exam_type||'—'}</span></td>
                                    <td class="py-3 px-4 text-xs text-slate-600">${(()=>{ const r=s.exam_date||s.date||''; if(!r||r==='0000-00-00'||r==='0000-00-00 00:00:00') return '—'; try{ const d=new Date(r.substring(0,10)+'T00:00:00'); return isNaN(d.getTime())?'—':d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }catch(e){return '—';} })()}</td>
                                    <td class="py-3 px-4 text-xs text-slate-600">${s.room_name||s.room||'—'}</td>
                                    <td class="py-3 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ${sb}">${status}</span></td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table></div>
                    ${totalPages > 1 ? (() => {
                        const schedPages = [];
                        for (let p = 0; p < totalPages; p++) {
                            if (p === 0 || p === totalPages - 1 || (p >= curPage - 2 && p <= curPage + 2)) {
                                schedPages.push(p);
                            } else if (schedPages[schedPages.length - 1] !== '…') {
                                schedPages.push('…');
                            }
                        }
                        return `
                    <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-400">Showing ${curPage*PAGE_SIZE+1}–${Math.min(curPage*PAGE_SIZE+PAGE_SIZE,fa.length)} of ${fa.length} records</span>
                        <div class="flex items-center gap-1">
                            <button onclick="analyticsSchedPage=Math.max(0,${curPage}-1);renderApp()"
                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition text-xs${curPage===0?' opacity-30 pointer-events-none':''}"
                                ${curPage===0?'disabled':''}>‹</button>
                            ${schedPages.map(p => p === '…'
                                ? `<span class="w-7 h-7 flex items-center justify-center text-xs text-slate-400">…</span>`
                                : `<button onclick="analyticsSchedPage=${p};renderApp()" class="w-7 h-7 flex items-center justify-center rounded-lg text-xs font-semibold transition ${p===curPage?'bg-emerald-600 text-white shadow-sm':'border border-slate-200 text-slate-600 hover:bg-slate-50'}">${p+1}</button>`
                            ).join('')}
                            <button onclick="analyticsSchedPage=Math.min(${totalPages-1},${curPage}+1);renderApp()"
                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition text-xs${curPage===totalPages-1?' opacity-30 pointer-events-none':''}"
                                ${curPage===totalPages-1?'disabled':''}>›</button>
                        </div>
                    </div>`;
                    })() : ''}`;
                })()}
            </div>
            ${(() => {
                const FEED_PAGE_SIZE = 5;
                const feedbacks = allData.filter(d => d.type === 'feedback');
                const unread = feedbacks.filter(f => !f.is_read).length;
                const byCategory = {};
                feedbacks.forEach(f => {
                    const cat = f.category || 'General';
                    byCategory[cat] = (byCategory[cat] || 0) + 1;
                });
                const catEntries = Object.entries(byCategory).sort((a,b) => b[1]-a[1]);
                const maxCat = catEntries.length ? catEntries[0][1] : 1;
                const catColors = ['bg-emerald-500','bg-blue-500','bg-purple-500','bg-orange-500','bg-pink-500'];
                const feedTotalPages = Math.max(1, Math.ceil(feedbacks.length / FEED_PAGE_SIZE));
                const feedCurPage   = Math.min(analyticsFeedPage, feedTotalPages - 1);
                const feedPageRows  = feedbacks.slice(feedCurPage * FEED_PAGE_SIZE, feedCurPage * FEED_PAGE_SIZE + FEED_PAGE_SIZE);

                return `
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
                        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                            Student Feedbacks — ${currentUser.college || 'Your Department'}
                        </h3>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">${feedbacks.length} record${feedbacks.length!==1?'s':''}</span>
                            <button onclick="currentView='feedbacks';sessionStorage.setItem('head_currentView',currentView);renderApp()" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition">View all →</button>
                        </div>
                    </div>
                    ${feedbacks.length === 0 ? `
                    <div class="py-16 text-center">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">No student feedbacks yet</p>
                        <p class="text-xs text-slate-400 mt-1">Feedbacks for your department will appear here.</p>
                    </div>` : `
                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-left">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total</p>
                                <h4 class="text-2xl font-bold text-slate-800">${feedbacks.length}</h4>
                            </div>
                            <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 text-left">
                                <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-1">Unread</p>
                                <h4 class="text-2xl font-bold text-emerald-700">${unread}</h4>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-left">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Read</p>
                                <h4 class="text-2xl font-bold text-slate-800">${feedbacks.length - unread}</h4>
                            </div>
                        </div>
                        ${catEntries.length > 0 ? `
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">By Category</p>
                            <div class="space-y-2">
                                ${catEntries.map(([cat, count], i) => `
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-semibold text-slate-600 w-28 shrink-0 truncate" title="${cat}">${cat}</span>
                                    <div class="flex-1 h-5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full ${catColors[i % catColors.length]} rounded-full flex items-center justify-end pr-2"
                                             style="width:${Math.max(8, Math.round((count/maxCat)*100))}%">
                                            <span class="text-white text-[10px] font-bold">${count}</span>
                                        </div>
                                    </div>
                                </div>`).join('')}
                            </div>
                        </div>` : ''}
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Feedbacks</p>
                            <div class="space-y-2">
                                ${feedPageRows.map(f => {
                                    const date = f.created_at ? new Date(f.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
                                    const isUnread = !f.is_read;
                                    return `
                                    <div class="flex items-start gap-3 p-3 rounded-lg ${isUnread ? 'bg-emerald-50 border border-emerald-100' : 'bg-slate-50 border border-slate-100'}">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${isUnread ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-200 text-slate-600'}">
                                            ${(f.student_name || 'A').charAt(0).toUpperCase()}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs font-semibold text-slate-700">${f.student_name || 'Anonymous'}</span>
                                                ${isUnread ? '<span class="inline-block w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>' : ''}
                                                ${f.category ? `<span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-500">${f.category}</span>` : ''}
                                                <span class="ml-auto text-[10px] text-slate-400 shrink-0">${date}</span>
                                            </div>
                                            ${f.subject ? `<p class="text-xs font-medium text-slate-600 mt-0.5 truncate">${f.subject}</p>` : ''}
                                            <p class="text-xs text-slate-500 truncate italic">"${f.message}"</p>
                                        </div>
                                    </div>`;
                                }).join('')}
                            </div>
                        </div>
                    </div>
                    ${feedTotalPages > 1 ? (() => {
                        const feedPages = [];
                        for (let p = 0; p < feedTotalPages; p++) {
                            if (p === 0 || p === feedTotalPages - 1 || (p >= feedCurPage - 2 && p <= feedCurPage + 2)) {
                                feedPages.push(p);
                            } else if (feedPages[feedPages.length - 1] !== '…') {
                                feedPages.push('…');
                            }
                        }
                        return `
                        <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                            <span class="text-xs text-slate-400">Showing ${feedCurPage*FEED_PAGE_SIZE+1}–${Math.min(feedCurPage*FEED_PAGE_SIZE+FEED_PAGE_SIZE,feedbacks.length)} of ${feedbacks.length} feedback${feedbacks.length!==1?'s':''}</span>
                            <div class="flex items-center gap-1">
                                <button onclick="analyticsFeedPage=Math.max(0,${feedCurPage}-1);renderApp()"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition text-xs${feedCurPage===0?' opacity-30 pointer-events-none':''}"
                                    ${feedCurPage===0?'disabled':''}>‹</button>
                                ${feedPages.map(p => p === '…'
                                    ? `<span class="w-7 h-7 flex items-center justify-center text-xs text-slate-400">…</span>`
                                    : `<button onclick="analyticsFeedPage=${p};renderApp()" class="w-7 h-7 flex items-center justify-center rounded-lg text-xs font-semibold transition ${p===feedCurPage?'bg-emerald-600 text-white shadow-sm':'border border-slate-200 text-slate-600 hover:bg-slate-50'}">${p+1}</button>`
                                ).join('')}
                                <button onclick="analyticsFeedPage=Math.max(0,Math.min(${feedTotalPages-1},${feedCurPage}+1));renderApp()"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition text-xs${feedCurPage===feedTotalPages-1?' opacity-30 pointer-events-none':''}"
                                    ${feedCurPage===feedTotalPages-1?'disabled':''}>›</button>
                            </div>
                        </div>`;
                    })() : ''}
                    `}
                </div>`;
            })()}
            <!-- ── Rejected Schedules (College-scoped) ───────────────────────── -->
            ${(() => {
                const myCollege = (currentUser.college || '').trim().toUpperCase();
                const myProgram = (currentUser.program || '').trim().toUpperCase();
                const allSched = allData.filter(d => d.type === 'schedule');
                let rejScopes = allSched.filter(s => s.status === 'Rejected');
                // Scope to head's college, then program
                if (myCollege) rejScopes = rejScopes.filter(s => (s.college||'').trim().toUpperCase() === myCollege);
                if (myProgram) rejScopes = rejScopes.filter(s => (s.program||'').trim().toUpperCase() === myProgram);
                const scopeLabel = myProgram || myCollege || 'Your College';
                const esc = v => String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

                const REJ_PAGE_SIZE = 10;
                const rejTotal = rejScopes.length;
                const rejTotalPages = Math.max(1, Math.ceil(rejTotal / REJ_PAGE_SIZE));
                const rejCurPage = Math.min(analyticsRejPage, rejTotalPages - 1);
                const rejPageRows = rejScopes.slice(rejCurPage * REJ_PAGE_SIZE, rejCurPage * REJ_PAGE_SIZE + REJ_PAGE_SIZE);

                const rejRows = rejPageRows.map(s => `
                    <tr class="hover:bg-red-50/30 transition">
                        <td class="py-3 px-4">
                            <div class="text-xs font-semibold text-slate-800">${esc(s.course_code)}</div>
                            <div class="text-[11px] text-slate-500 truncate max-w-[160px]">${esc(s.course_name)}</div>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-600">${esc(s.program||'—')}</td>
                        <td class="py-3 px-4"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ${s.exam_type==='Midterm'?'bg-blue-100 text-blue-700':s.exam_type==='Final'?'bg-purple-100 text-purple-700':'bg-amber-100 text-amber-700'}">${esc(s.exam_type||'—')}</span></td>
                        <td class="py-3 px-4 text-xs text-slate-600">${(()=>{ const r=s.exam_date||s.date||''; if(!r||r==='0000-00-00'||r==='0000-00-00 00:00:00') return '—'; try{ const d=new Date(r.substring(0,10)+'T00:00:00'); return isNaN(d.getTime())?'—':d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }catch(e){return '—';} })()}</td>
                        <td class="py-3 px-4 text-xs text-slate-600">${esc(s.room_name||s.room||'—')}</td>
                        <td class="py-3 px-4 text-[11px] text-slate-500 max-w-[200px] truncate" title="${esc(s.rejection_reason||s.remarks||'')}">
                            ${s.rejection_reason||s.remarks ? `<span class="italic text-red-500">"${esc((s.rejection_reason||s.remarks||'').substring(0,60))}${(s.rejection_reason||s.remarks||'').length>60?'…':''}"</span>` : '<span class="text-slate-300">—</span>'}
                        </td>
                    </tr>`).join('');

                return `
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-red-50 rounded-lg shrink-0">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-sm">Rejected Schedules</h3>
                                <p class="text-[10px] text-slate-400">${esc(scopeLabel)} · ${rejTotal} rejected schedule${rejTotal!==1?'s':''}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                            ${rejTotal} Rejected
                        </span>
                    </div>
                    ${rejTotal === 0 ? `
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
                                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Program</th>
                                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Exam Type</th>
                                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
                                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Room</th>
                                    <th class="py-3 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Reason</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">${rejRows}</tbody>
                        </table>
                    </div>
                    ${rejTotalPages > 1 ? (() => {
                        const rejPages = [];
                        for (let p = 0; p < rejTotalPages; p++) {
                            if (p === 0 || p === rejTotalPages - 1 || (p >= rejCurPage - 2 && p <= rejCurPage + 2)) {
                                rejPages.push(p);
                            } else if (rejPages[rejPages.length - 1] !== '…') {
                                rejPages.push('…');
                            }
                        }
                        return `
                    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-400">Showing ${rejCurPage*REJ_PAGE_SIZE+1}–${Math.min(rejCurPage*REJ_PAGE_SIZE+REJ_PAGE_SIZE,rejTotal)} of ${rejTotal} rejected schedules</span>
                        <div class="flex items-center gap-1">
                            <button onclick="analyticsRejPage=Math.max(0,${rejCurPage}-1);renderApp()"
                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition text-xs${rejCurPage===0?' opacity-30 pointer-events-none':''}"
                                ${rejCurPage===0?'disabled':''}>‹</button>
                            ${rejPages.map(p => p === '…'
                                ? `<span class="w-7 h-7 flex items-center justify-center text-xs text-slate-400">…</span>`
                                : `<button onclick="analyticsRejPage=${p};renderApp()" class="w-7 h-7 flex items-center justify-center rounded-lg text-xs font-semibold transition ${p===rejCurPage?'bg-red-600 text-white shadow-sm':'border border-slate-200 text-slate-600 hover:bg-slate-50'}">${p+1}</button>`
                            ).join('')}
                            <button onclick="analyticsRejPage=Math.min(${rejTotalPages-1},${rejCurPage}+1);renderApp()"
                                class="w-7 h-7 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition text-xs${rejCurPage===rejTotalPages-1?' opacity-30 pointer-events-none':''}"
                                ${rejCurPage===rejTotalPages-1?'disabled':''}>›</button>
                        </div>
                    </div>`;
                    })() : ''}`}
                </div>`;
            })()}

            <!-- ── Proctor Analytics ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
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
                        <div id="headProctorDatePills" class="flex items-center gap-1 bg-slate-100 rounded-lg p-1">
                            <button onclick="headProctorAnalyticsFilter('all')"   data-hppill="all"   class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">All Time</button>
                            <button onclick="headProctorAnalyticsFilter('day')"   data-hppill="day"   class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">Today</button>
                            <button onclick="headProctorAnalyticsFilter('week')"  data-hppill="week"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Week</button>
                            <button onclick="headProctorAnalyticsFilter('month')" data-hppill="month" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Month</button>
                            <button onclick="headProctorAnalyticsFilter('year')"  data-hppill="year"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap">This Year</button>
                        </div>
                        <button onclick="exportHeadProctorAnalyticsPDF()"
                            class="flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition shadow-sm whitespace-nowrap">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download PDF
                        </button>
                    </div>
                </div>
                <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex items-center gap-4 flex-wrap">
                    <span id="headProctorAnalyticsSummary" class="text-xs text-slate-400 font-medium"></span>
                </div>
                <div class="p-4">
                    <div id="headProctorAnalyticsChart" class="space-y-3"></div>
                </div>
                <div class="overflow-x-auto border-t border-slate-100">
                    <table class="w-full text-left" id="headProctorAnalyticsTable">
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
                        <tbody id="headProctorAnalyticsBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
                <div id="headProctorAnalyticsPager" class="flex items-center justify-between px-4 py-3 border-t border-slate-100 flex-wrap gap-2"></div>
            </div>

            <!-- ── Scheduling Conflicts ── -->
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
                    <div id="headConflictSummaryBadge"></div>
                </div>
                <div id="headConflictTypePills" class="flex flex-wrap gap-1.5 mb-4">
                    <button onclick="headConflictsTypeFilter('all')"     data-hcpill="all"     class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap bg-white text-slate-700 shadow-sm border border-slate-200">All Types</button>
                    <button onclick="headConflictsTypeFilter('locked')"  data-hcpill="locked"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">🔒 Locked Room</button>
                    <button onclick="headConflictsTypeFilter('double')"  data-hcpill="double"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">⚠️ Double-Booking</button>
                    <button onclick="headConflictsTypeFilter('noroom')"  data-hcpill="noroom"  class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">📋 No Room</button>
                    <button onclick="headConflictsTypeFilter('proctor')" data-hcpill="proctor" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">👤 Proctor Conflict</button>
                    <button onclick="headConflictsTypeFilter('section')" data-hcpill="section" class="px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap text-slate-500 hover:text-slate-700">🎓 Section Overlap</button>
                </div>
                <div id="headConflictsTable"></div>
                <div id="headConflictsPager" class="flex items-center justify-between px-1 pt-3 border-t border-slate-100 flex-wrap gap-2 mt-3"></div>
            </div>

        </div>`;
    }



    // ── FEEDBACKS ──────────────────────────────────────────────────────────
   if (currentView === 'feedbacks') {
    const feedbacks = allData.filter(d => d.type === 'feedback');
    const unread = feedbacks.filter(f => !f.is_read).length;

    // Programs available in this head's feedbacks
    const fbPrograms = [...new Set(feedbacks.map(f => (f.program||'').trim()).filter(Boolean))].sort();
    const fbCategories = [...new Set(feedbacks.map(f => (f.category||'').trim()).filter(Boolean))].sort();
    
    return `
    <div class="fade-in space-y-6">
        <!-- Header -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-wrap justify-between items-center gap-4">
            <div>
                <h3 class="text-lg font-bold text-slate-800">Student Feedbacks — ${currentUser.college || 'Your Department'}</h3>
                <p class="text-sm text-slate-500">${feedbacks.length} feedback${feedbacks.length !== 1 ? 's' : ''} · <span class="text-emerald-600 font-semibold">${unread} unread</span></p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button onclick="headMarkAllFbRead()" class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Mark all read
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[180px]">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input id="headFbSearch" type="text" placeholder="Search by name, subject, message…"
                    oninput="headFeedbacksFilter()"
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            ${fbPrograms.length > 1 ? `
            <select id="headFbProgramFilter" onchange="headFeedbacksFilter()" class="filter-select">
                <option value="">All Programs</option>
                ${fbPrograms.map(p => `<option value="${p}">${p}</option>`).join('')}
            </select>` : ''}
            ${fbCategories.length > 0 ? `
            <select id="headFbCategoryFilter" onchange="headFeedbacksFilter()" class="filter-select">
                <option value="">All Categories</option>
                ${fbCategories.map(c => `<option value="${c}">${c}</option>`).join('')}
            </select>` : ''}
            <select id="headFbStatusFilter" onchange="headFeedbacksFilter()" class="filter-select">
                <option value="">All Status</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
            </select>
            <button onclick="headClearFbFilters()" class="text-xs text-slate-400 hover:text-slate-700 font-medium px-2 whitespace-nowrap transition">Clear</button>
        </div>

        ${feedbacks.length === 0 ? `
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm min-h-[400px] flex flex-col items-center justify-center p-10 text-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-6 text-slate-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                </div>
                <h3 class="text-slate-600 font-bold text-lg">No feedbacks yet</h3>
                <p class="text-sm text-slate-400 mt-1">Student feedback for ${currentUser.college || 'your department'} will appear here once submitted.</p>
            </div>
        ` : `
            <div id="headFeedbacksBody" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${feedbacks.map(f => {
                    const date = f.created_at ? new Date(f.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
                    const isUnread = !f.is_read;
                    const searchAttr = ((f.student_name||'') + ' ' + (f.subject||'') + ' ' + (f.message||'')).toLowerCase();
                    return `
                    <div onclick="openFeedbackDetail(${JSON.stringify(f).replace(/"/g,'&quot;')})"
                         data-hfb-program="${(f.program||'').trim()}"
                         data-hfb-category="${(f.category||'').trim()}"
                         data-hfb-status="${isUnread ? 'unread' : 'read'}"
                         data-hfb-search="${searchAttr}"
                         class="bg-white p-5 rounded-xl border ${isUnread ? 'border-emerald-200 shadow-md' : 'border-slate-200 shadow-sm'} transition cursor-pointer hover:shadow-lg hover:border-emerald-300 hover:-translate-y-0.5 transform">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-sm shrink-0">
                                    ${(f.student_name || 'A').charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800">${f.student_name || 'Anonymous'}</span>
                                    ${isUnread ? '<span class="ml-2 inline-block w-2 h-2 bg-emerald-500 rounded-full align-middle"></span>' : ''}
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs text-slate-400">${date}</span>
                                <button onclick="event.stopPropagation();headDeleteFeedback(${f.id})" title="Delete"
                                    class="p-1 text-slate-300 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mb-2">
                            ${f.program  ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-100">${f.program}</span>` : ''}
                            ${f.category ? `<span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">${f.category}</span>` : ''}
                            ${f.exam_difficulty ? `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${f.exam_difficulty.startsWith('30') ? 'bg-amber-50 text-amber-700 border border-amber-200' : f.exam_difficulty.startsWith('50') ? 'bg-orange-50 text-orange-700 border border-orange-200' : 'bg-red-50 text-red-700 border border-red-200'}">${f.exam_difficulty.startsWith('30') ? '😐' : f.exam_difficulty.startsWith('50') ? '😓' : '😱'} ${f.exam_difficulty}</span>` : ''}
                        </div>
                        ${f.subject ? `<p class="text-sm font-semibold text-slate-700 mb-1">${f.subject}</p>` : ''}
                        <p class="text-sm text-slate-600 italic line-clamp-2">"${f.message}"</p>
                        ${f.student_number ? `<p class="text-xs text-slate-400 mt-2">Student #: ${f.student_number}</p>` : ''}
                        <span class="text-[10px] text-emerald-600 font-semibold mt-2 block">Click to view →</span>
                    </div>`;
                }).join('')}
            </div>
            <div id="headFbNoResults" class="hidden bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <p class="font-semibold text-slate-500">No feedbacks match your filters</p>
                <button onclick="headClearFbFilters()" class="mt-3 text-xs text-emerald-600 hover:text-emerald-700 font-semibold underline">Clear filters</button>
            </div>
        `}
    </div>`;
}

    // ── CALENDAR ───────────────────────────────────────────────────────────
    if (currentView === 'calendar') {
        return `<div class="fade-in"><div id="calendar-container">${renderCalendar()}</div></div>`;
    }

    // ── COURSES ────────────────────────────────────────────────────────────
    if (currentView === 'courses') {
        const courses = allData.filter(d => d.type === 'course');
        // Build college options from the colleges API data (same as admin)
        // colleges have: { name, code, programs[] }
        const collegeData = allData.filter(d => d.type === 'college');
        const collegeOptions = collegeData
            .map(c => ({ code: (c.code || '').trim(), name: (c.name || '').trim() }))
            .filter(c => c.code || c.name)
            .reduce((acc, c) => {
                if (!acc.find(x => x.code === c.code)) acc.push(c);
                return acc;
            }, [])
            .sort((a, b) => (a.code || a.name).localeCompare(b.code || b.name));

        let filteredCourses = courses;
        if (courseSearch) {
            const q = courseSearch.toLowerCase();
            filteredCourses = filteredCourses.filter(c =>
                (c.course_code||'').toLowerCase().includes(q) ||
                (c.course_name||'').toLowerCase().includes(q) ||
                (c.program||'').toLowerCase().includes(q) ||
                (c.college||'').toLowerCase().includes(q)
            );
        }
        if (courseCampus  !== 'all') filteredCourses = filteredCourses.filter(c => (c.campus||'') === courseCampus);
        if (courseCollege !== 'all') filteredCourses = filteredCourses.filter(c => {
            const col = (c.college || '').toLowerCase();
            const prg = (c.program || '').toLowerCase();
            const sel = courseCollege.toLowerCase();
            return col === sel || prg === sel || col.includes(sel) || prg.includes(sel);
        });

        const courseTags = [];
        if (courseCollege !== 'all') courseTags.push({ label: `College: ${courseCollege}`,  clear: "courseCollege='all';renderApp()" });
        const hasCourseFilters = courseCollege !== 'all' || courseSearch !== '';

        // Build per-course grouped data for the panel (mirrors admin.php logic)
        const headCourseGroups = {};
        courses.forEach(c => {
            const key = (c.course_code || '').toLowerCase();
            if (!headCourseGroups[key]) headCourseGroups[key] = { ...c, _programs: [], _normProgs: [], _campuses: [], _colleges: [], _progDetails: {}, _progList: [], _ids: [] };
            headCourseGroups[key]._ids.push(c.id);
            const prog  = (c.program  || '').trim();
            const col   = (c.college  || '').trim();
            const camp  = (c.campus   || '').trim();
            // Normalize program to avoid duplicates like "BSPSYCH" vs "BS PSYCH"
            const normProg = prog.replace(/\s+/g, '').toUpperCase();
            // Use composite key (normalized) so same program in different colleges/campuses is kept separately
            const compositeKey = `${normProg}||${col}||${camp}`;
            if (normProg && !headCourseGroups[key]._progList.find(p => p._key === compositeKey)) {
                headCourseGroups[key]._progList.push({
                    _key:       compositeKey,
                    program:    prog,
                    year_level: c.year_level || '',
                    semester:   c.semester   || '',
                    college:    col,
                    campus:     camp,
                });
            }
            // Keep _programs as unique acronym list (for backward compat with table badge count)
            if (normProg && !headCourseGroups[key]._normProgs.includes(normProg)) {
                headCourseGroups[key]._normProgs.push(normProg);
                headCourseGroups[key]._programs.push(prog);
                // _progDetails keyed by normProg for consistent lookup
                headCourseGroups[key]._progDetails[normProg] = {
                    year_level: c.year_level || '',
                    semester:   c.semester   || '',
                    college:    col,
                    campus:     camp,
                };
            }
            if (camp && !headCourseGroups[key]._campuses.includes(camp)) headCourseGroups[key]._campuses.push(camp);
            if (col  && !headCourseGroups[key]._colleges.includes(col))  headCourseGroups[key]._colleges.push(col);
            const curName = (headCourseGroups[key].course_name || '').trim().toLowerCase();
            const newName = (c.course_name || '').trim();
            if (newName && newName.toLowerCase() !== key && curName === key) headCourseGroups[key].course_name = newName;
        });
        window._headCourseGroups = headCourseGroups;

        return `
        <div class="fade-in space-y-6">

            <!-- Course Detail Side Panel Overlay -->
            <div id="headCoursePanelOverlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.4);backdrop-filter:blur(2px);z-index:998;" onclick="closeHeadCoursePanel()"></div>

            <!-- Course Detail Slide-in Side Panel -->
            <div id="headCourseDetailSidePanel" style="position:fixed;top:0;right:-460px;height:100vh;width:440px;background:white;z-index:999;box-shadow:-8px 0 40px rgba(0,0,0,0.14);display:flex;flex-direction:column;transition:right 0.35s cubic-bezier(0.4,0,0.2,1);">
                <!-- Panel Header -->
                <div style="padding:24px 24px 0;border-bottom:1px solid #f1f5f9;flex-shrink:0;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:16px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div id="headCoursePanelIcon" style="width:44px;height:44px;border-radius:10px;background:#ecfdf5;color:#047857;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:800;letter-spacing:-0.5px;flex-shrink:0;"></div>
                            <div>
                                <h2 style="font-size:0.95rem;font-weight:700;color:#0f172a;line-height:1.3;" id="headCoursePanelTitle"></h2>
                                <p style="font-size:0.72rem;color:#64748b;margin-top:2px;" id="headCoursePanelSubtitle"></p>
                            </div>
                        </div>
                        <button onclick="closeHeadCoursePanel()" style="width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:white;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;transition:all 0.15s;" onmouseover="this.style.background='#f1f5f9';this.style.color='#0f172a'" onmouseout="this.style.background='white';this.style.color='#64748b'">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <!-- Stats bar -->
                    <div id="headCoursePanelStats" style="display:flex;gap:12px;padding-bottom:16px;flex-wrap:wrap;"></div>
                </div>
                <!-- Panel Body -->
                <div style="flex:1;overflow-y:auto;padding:20px 24px;" id="headCoursePanelBody"></div>
                <!-- Panel Footer -->
                <div style="padding:16px 24px;border-top:1px solid #f1f5f9;flex-shrink:0;">
                    <button onclick="closeHeadCoursePanel()" style="width:100%;padding:10px;border-radius:8px;background:#f1f5f9;color:#475569;font-size:0.82rem;font-weight:600;border:none;cursor:pointer;transition:background 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Close</button>
                </div>
            </div>

            <!-- Filter / Actions bar -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[200px]">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg>
                        </span>
                        <input id="courseSearchInput" type="text" value="${courseSearch}" oninput="courseSearch=this.value;clearTimeout(window._cst);window._cst=setTimeout(()=>{courseCurrentPage=1;renderApp();},300)" placeholder="Search courses..." class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-emerald-500">
                    </div>
                    <select id="courseCollegeFilter" class="filter-select" onchange="courseCollege=this.value;courseCurrentPage=1;renderApp()">
                        <option value="all">All Colleges</option>
                        ${collegeOptions.map(col=>`<option value="${col.code}" ${courseCollege===col.code?'selected':''}>${col.code}${col.name?' — '+col.name:''}</option>`).join('')}
                    </select>
                    <button onclick="courseSearch='';courseCollege='all';courseCurrentPage=1;renderApp()" class="text-slate-500 text-sm font-medium px-2 hover:text-slate-800">Clear</button>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-4 flex-wrap gap-2">
                    <p class="text-sm text-slate-500">Showing <span class="font-semibold text-slate-700">${[...new Set(filteredCourses.map(c=>(c.course_code||'')))].length}</span> of <span class="font-semibold text-slate-700">${[...new Set(courses.map(c=>(c.course_code||'')))].length}</span> courses</p>
                    <div class="flex items-center gap-2">
                        <button onclick="downloadCourseTemplate()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Template
                        </button>
                        <button onclick="importCourses()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>Import
                        </button>
                        <button onclick="exportCourses()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Export
                        </button>
                        <button onclick="openAddCourseModal()" class="btn-action-large bg-emerald-600 text-white hover:bg-emerald-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Course
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <!-- Campus Tab Strip -->
                <div class="px-5 py-3 border-b border-slate-100">
                    ${renderHeadCampusTabs(filteredCourses)}
                </div>
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
                        <tbody id="headCoursesBody" class="divide-y divide-slate-100">
                            ${(() => {
                                const groups = {};
                                filteredCourses.forEach(c => {
                                    const key = (c.course_code || '').toLowerCase();
                                    if (!groups[key]) groups[key] = { ...c, _programs: [], _normProgs: [], _campuses: [], _ids: [] };
                                    groups[key]._ids.push(c.id);
                                    const prog = (c.program || '').trim();
                                    const normProg = prog.replace(/\s+/g, '').toUpperCase();
                                    if (normProg && !groups[key]._normProgs.includes(normProg)) {
                                        groups[key]._normProgs.push(normProg);
                                        groups[key]._programs.push(prog);
                                    }
                                    const camp = (c.campus || '').trim();
                                    if (camp && !groups[key]._campuses.includes(camp)) groups[key]._campuses.push(camp);
                                    const curName = (groups[key].course_name || '').trim().toLowerCase();
                                    const newName = (c.course_name || '').trim();
                                    if (newName && newName.toLowerCase() !== key && curName === key) groups[key].course_name = newName;
                                });
                                const grouped = Object.values(groups);
                                if (grouped.length === 0) {
                                    return `<tr><td colspan="6" class="py-16 text-center text-sm text-slate-400">${courseSearch||courseCollege!=='all' ? 'No courses match your filters.' : 'No courses found. Click "Add Course" to get started.'}</td></tr>`;
                                }

                                // ── Pagination ──
                                const cpTotal = grouped.length;
                                const cpPages = Math.max(1, Math.ceil(cpTotal / PAGE_SIZE));
                                if (courseCurrentPage > cpPages) courseCurrentPage = cpPages;
                                if (courseCurrentPage < 1)        courseCurrentPage = 1;
                                const cpStart  = (courseCurrentPage - 1) * PAGE_SIZE;
                                const cpEnd    = Math.min(cpStart + PAGE_SIZE, cpTotal);
                                const pageRows = grouped.slice(cpStart, cpEnd);

                                return pageRows.map(c => {
                                    const safeId    = String(c.id).replace(/[^a-zA-Z0-9_-]/g,'_');
                                    const codeKey   = (c.course_code || '').toLowerCase();
                                    const courseName = (c.course_name||'').trim();
                                    const displayName = (courseName && courseName.toLowerCase() !== codeKey) ? courseName : '';

                                    const programCell = c._programs.length === 0
                                        ? '<span class="text-slate-300 text-xs">—</span>'
                                        : `<button onclick="showHeadCoursePanel('${codeKey.replace(/'/g,"\\'")}');event.stopPropagation();" class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100 rounded-full hover:bg-blue-100 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            ${c._programs.length} program${c._programs.length !== 1 ? 's' : ''}
                                        </button>`;

                                    const campusCell = c._campuses.length === 0
                                        ? '<span class="text-slate-400 text-xs">—</span>'
                                        : `<span class="campus-badge">${c._campuses[0]}</span>`;

                                    return `
                                    <tr class="hover:bg-slate-50/50 transition"
                                        data-search="${codeKey} ${(c.course_name||'').toLowerCase()} ${c._campuses.join(' ').toLowerCase()} ${c._programs.join(' ').toLowerCase()}">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-slate-800">${c.course_code||'—'}</span>
                                                ${displayName
                                                    ? `<span class="text-xs text-slate-400 mt-0.5">${displayName}</span>`
                                                    : `<span class="text-xs text-slate-300 mt-0.5">—</span>`}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">${programCell}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full
                                                ${c.year_level==='1st Year'?'bg-purple-50 text-purple-700 border border-purple-100':
                                                  c.year_level==='2nd Year'?'bg-blue-50 text-blue-700 border border-blue-100':
                                                  c.year_level==='3rd Year'?'bg-emerald-50 text-emerald-700 border border-emerald-100':
                                                  c.year_level==='4th Year'?'bg-orange-50 text-orange-700 border border-orange-100':
                                                  'bg-slate-50 text-slate-600 border border-slate-200'}">
                                                ${c.year_level||'—'}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-slate-500">${c.semester||'—'}</td>
                                        <td class="px-6 py-4" data-campus-cell>${campusCell}</td>
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

                <!-- Courses Pagination Footer -->
                ${(() => {
                    const groups = {};
                    filteredCourses.forEach(c => {
                        const key = (c.course_code || '').toLowerCase();
                        if (!groups[key]) groups[key] = true;
                    });
                    const cpTotal = Object.keys(groups).length;
                    const cpPages = Math.max(1, Math.ceil(cpTotal / PAGE_SIZE));
                    if (cpPages <= 1) return '';
                    const cpStart = (courseCurrentPage - 1) * PAGE_SIZE + 1;
                    const cpEnd   = Math.min(courseCurrentPage * PAGE_SIZE, cpTotal);
                    const btnBase = 'inline-flex items-center justify-center min-w-[32px] h-8 px-2 rounded-lg text-xs font-semibold transition border';
                    const btnActive = 'bg-emerald-600 text-white border-emerald-600 shadow-sm';
                    const btnInactive = 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
                    const btnDisabled = 'bg-white text-slate-300 border-slate-100 cursor-not-allowed';

                    let pages = [];
                    if (cpPages <= 7) {
                        for (let i = 1; i <= cpPages; i++) pages.push(i);
                    } else {
                        pages.push(1);
                        if (courseCurrentPage > 4) pages.push('…');
                        for (let i = Math.max(2, courseCurrentPage - 2); i <= Math.min(cpPages - 1, courseCurrentPage + 2); i++) pages.push(i);
                        if (courseCurrentPage < cpPages - 3) pages.push('…');
                        pages.push(cpPages);
                    }

                    const pageButtons = pages.map(p =>
                        p === '…'
                            ? `<span class="inline-flex items-center justify-center min-w-[32px] h-8 px-1 text-xs text-slate-400">…</span>`
                            : `<button onclick="courseCurrentPage=${p};renderApp()" class="${btnBase} ${p === courseCurrentPage ? btnActive : btnInactive}">${p}</button>`
                    ).join('');

                    return `
                    <div class="flex items-center justify-between px-6 py-3 border-t border-slate-100 bg-slate-50/50">
                        <span class="text-xs text-slate-500">
                            Showing <span class="font-semibold text-slate-700">${cpStart}–${cpEnd}</span> of <span class="font-semibold text-slate-700">${cpTotal}</span> courses
                        </span>
                        <div class="flex items-center gap-1">
                            <button onclick="courseCurrentPage=Math.max(1,courseCurrentPage-1);renderApp()"
                                class="${btnBase} ${courseCurrentPage === 1 ? btnDisabled : btnInactive}"
                                ${courseCurrentPage === 1 ? 'disabled' : ''}>
                                ‹
                            </button>
                            ${pageButtons}
                            <button onclick="courseCurrentPage=Math.min(${cpPages},courseCurrentPage+1);renderApp()"
                                class="${btnBase} ${courseCurrentPage === cpPages ? btnDisabled : btnInactive}"
                                ${courseCurrentPage === cpPages ? 'disabled' : ''}>
                                ›
                            </button>
                        </div>
                    </div>`;
                })()}
            </div>
        </div>`;
    }

    // ── PROCTORS ───────────────────────────────────────────────────────────
    if (currentView === 'proctors') {
        const proctors = allData.filter(d => d.type === 'proctor');

        let filteredProctors = proctors;
        if (proctorSearch) {
            const q = proctorSearch.toLowerCase();
            filteredProctors = filteredProctors.filter(p =>
                (p.name||'').toLowerCase().includes(q) ||
                (p.email||'').toLowerCase().includes(q) ||
                (p.college_program||p.collegeProgram||'').toLowerCase().includes(q)
            );
        }
        if (proctorCampus !== 'all') filteredProctors = filteredProctors.filter(p => (p.campus||'') === proctorCampus);

        const proctorTags = [];

        return `
        <div class="fade-in space-y-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-3">
                <div class="flex flex-col md:flex-row flex-wrap gap-3 items-stretch md:items-center">
                    <div class="relative flex-1 min-w-[200px]">
                        <span class="absolute inset-y-0 left-3 flex items-center text-slate-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg></span>
                        <input id="proctorSearchInput" type="text" value="${proctorSearch}" placeholder="Search proctors..."
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <button onclick="downloadProctorTemplate()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Template
                        </button>
                        <button onclick="importProctors()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>Import
                        </button>
                        <button onclick="exportProctors()" class="btn-action-large bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Export
                        </button>
                        <button onclick="openAddProctorModal()" class="btn-action-large bg-emerald-600 text-white hover:bg-emerald-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Proctor
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                        Filter:
                    </span>
                    ${(proctorSearch !== '') ? `
                    <button onclick="proctorSearch='';renderApp()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-xs font-semibold transition border border-red-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear Filters
                    </button>` : ''}
                </div>
                ${activeFilterTags(proctorTags)}
            </div>

            <div class="px-1"><p class="text-sm text-slate-500">Showing <span class="font-semibold text-slate-700">${filteredProctors.length}</span> of <span class="font-semibold text-slate-700">${proctors.length}</span> proctors</p></div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200"><tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Proctor Name</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">College/Program</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Campus</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        ${filteredProctors.length === 0
                            ? `<tr><td colspan="5" class="py-16 text-center text-sm text-slate-400">${proctorSearch ? 'No proctors match your search or filters.' : 'No proctors found.'}</td></tr>`
                            : filteredProctors.map(p => `
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-700 text-xs font-bold shrink-0">${(p.name||'?').charAt(0).toUpperCase()}</div>
                                        <span class="text-sm font-medium text-slate-900">${p.name||'—'}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">${p.collegeProgram||p.college_program||'—'}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">${p.email||'—'}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">${p.phone||'—'}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">${p.campus||'—'}</span>
                                </td>
                            </tr>`).join('')}
                    </tbody>
                </table>
            </div>
        </div>`;
    }

    return `<div class="p-20 text-center text-slate-400">View under construction</div>`;
}

// ─────────────────────────────────────────────────────────────────────────────
// 10. SMALL UI HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function renderStat(title, value, path, color) {
    const colors = { emerald:'bg-emerald-50 text-emerald-600', cyan:'bg-cyan-50 text-cyan-600', lime:'bg-lime-50 text-lime-600' };
    return `<div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex justify-between items-center text-left">
        <div><p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">${title}</p><h3 class="text-3xl font-bold text-slate-800">${value}</h3></div>
        <div class="w-12 h-12 ${colors[color]} rounded-lg flex items-center justify-center"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="${path}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    </div>`;
}

function renderIconAction(label, iconPath, color, onclick='') {
    const schemes = { emerald:'bg-emerald-500 shadow-emerald-200', blue:'bg-blue-500 shadow-blue-200', purple:'bg-purple-500 shadow-purple-200', amber:'bg-amber-500 shadow-amber-200', green:'bg-green-500 shadow-green-200' };
    return `<div class="text-center cursor-pointer group p-2 quick-icon-card" ${onclick?`onclick="${onclick}"`:''}><div class="${schemes[color]} w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-2 shadow-lg"><svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="${iconPath}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div><p class="text-xs font-semibold text-slate-600 group-hover:text-slate-900 transition truncate text-center">${label}</p></div>`;
}

function renderBarChart(items, keyFn) {
    return renderDonutChart(items, keyFn);
}

function renderDonutChart(items, keyFn, colorList) {
    colorList = colorList || ['#10b981','#3b82f6','#8b5cf6','#f97316','#ec4899','#06b6d4','#f59e0b','#14b8a6','#6366f1','#f43f5e'];
    const counts = {};
    items.forEach(item => { const k = keyFn(item); counts[k] = (counts[k]||0)+1; });
    const entries = Object.entries(counts).sort((a,b)=>b[1]-a[1]);
    const total = entries.reduce((s,[,v])=>s+v, 0);
    if (!entries.length || total === 0) return '<p class="text-slate-400 text-sm text-center py-8">No data available</p>';
    const cx=90, cy=90, r=72, holeR=42;
    let startAngle = -Math.PI/2;
    const slices = entries.map(([label,count],i) => {
        const angle = (count/total)*2*Math.PI;
        const end = startAngle+angle;
        const x1=cx+r*Math.cos(startAngle), y1=cy+r*Math.sin(startAngle);
        const x2=cx+r*Math.cos(end),         y2=cy+r*Math.sin(end);
        const ix1=cx+holeR*Math.cos(startAngle), iy1=cy+holeR*Math.sin(startAngle);
        const ix2=cx+holeR*Math.cos(end),         iy2=cy+holeR*Math.sin(end);
        const large=angle>Math.PI?1:0;
        const pct=Math.round((count/total)*100);
        const pathD=angle<0.02?'':
            'M '+x1+' '+y1+' A '+r+' '+r+' 0 '+large+' 1 '+x2+' '+y2+' L '+ix2+' '+iy2+' A '+holeR+' '+holeR+' 0 '+large+' 0 '+ix1+' '+iy1+' Z';
        startAngle=end;
        return {label,count,pct,color:colorList[i%colorList.length],pathD};
    });
    const svgPaths=slices.map(s=>s.pathD?
        '<path d="'+s.pathD+'" fill="'+s.color+'" stroke="white" stroke-width="1.5" opacity="0.93"><title>'+s.label+': '+s.count+' ('+s.pct+'%)</title></path>':'').join('');
    const legend=entries.map(([label,count],i)=>{
        const pct=Math.round((count/total)*100);
        return '<div class="flex items-center gap-2 min-w-0 py-0.5">'+
            '<span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:'+colorList[i%colorList.length]+'"></span>'+
            '<span class="text-[11px] text-slate-600 truncate font-medium flex-1" title="'+label+'">'+label+'</span>'+
            '<span class="text-[11px] font-bold text-slate-700 shrink-0">'+count+'</span>'+
            '<span class="text-[10px] text-slate-400 shrink-0 w-8 text-right">'+pct+'%</span>'+
        '</div>';
    }).join('');
    return '<div class="flex flex-col sm:flex-row items-center gap-5">'+
        '<svg viewBox="0 0 180 180" width="160" height="160" class="shrink-0">'+
            svgPaths+
            '<circle cx="'+cx+'" cy="'+cy+'" r="'+(holeR-2)+'" fill="white"/>'+
            '<text x="'+cx+'" y="'+(cy-5)+'" text-anchor="middle" font-size="20" font-weight="bold" fill="#1e293b">'+total+'</text>'+
            '<text x="'+cx+'" y="'+(cy+11)+'" text-anchor="middle" font-size="8" fill="#94a3b8">total</text>'+
        '</svg>'+
        '<div class="flex-1 w-full space-y-1 min-w-0">'+legend+'</div>'+
    '</div>';
}

// ─────────────────────────────────────────────────────────────────────────────
// 11. CALENDAR
// ─────────────────────────────────────────────────────────────────────────────
function renderCalendar() {
    const now = new Date();
    const firstDay   = new Date(calendarYear, calendarMonth, 1);
    const lastDay    = new Date(calendarYear, calendarMonth+1, 0);
    const daysInMonth = lastDay.getDate();
    const startDOW   = firstDay.getDay();
    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const dayNames   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const approvedScheds = allData.filter(d => d.type==='schedule' && d.status==='Approved');
    const byDate = {};
    approvedScheds.forEach(s => {
        const raw = s.exam_date||s.date; if (!raw) return;
        const d = new Date(raw+'T00:00:00');
        if (d.getMonth()===calendarMonth && d.getFullYear()===calendarYear) {
            const dk = d.getDate(); if (!byDate[dk]) byDate[dk]=[];
            byDate[dk].push(s);
        }
    });
    let html = `<div class="bg-white rounded-xl border border-slate-200 shadow-sm" style="overflow:hidden;">
        <div class="p-6 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-xl font-bold text-slate-900">${monthNames[calendarMonth]} ${calendarYear}</h2>
            <div class="flex items-center gap-3">
                <button onclick="navigateCalendar('prev')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>
                <button onclick="navigateCalendar('today')" class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg text-sm font-medium hover:bg-emerald-100 transition">Today</button>
                <button onclick="navigateCalendar('next')" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>
            </div>
        </div>
        <div style="overflow-x:auto;">
        <div class="calendar-grid" style="min-width:560px;">
            ${dayNames.map(d=>`<div class="p-3 text-center font-bold text-xs text-slate-500 uppercase tracking-wider bg-slate-50 border-l border-b border-slate-200">${d}</div>`).join('')}`;
    for (let i=0; i<startDOW; i++) html+=`<div class="calendar-cell bg-slate-50"></div>`;
    for (let day=1; day<=daysInMonth; day++) {
        const isToday = day===now.getDate() && calendarMonth===now.getMonth() && calendarYear===now.getFullYear();
        const ds = byDate[day]||[];
        const colors=['bg-blue-500','bg-purple-500','bg-pink-500','bg-orange-500','bg-teal-500'];
        const dateStr = `${calendarYear}-${String(calendarMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const hasScheds = ds.length > 0;
        html+=`<div class="calendar-cell ${isToday?'bg-emerald-50':''} ${hasScheds?'cursor-pointer hover:bg-slate-50':''} transition-colors"
            ${hasScheds ? `onclick="openDayModal('${dateStr}')"` : ''}>
            <div class="flex justify-between items-start mb-1">
                <span class="text-sm font-semibold ${isToday?'text-emerald-600':'text-slate-700'}">${day}</span>
                ${isToday?'<span class="text-[10px] bg-emerald-600 text-white px-2 py-0.5 rounded-full font-bold">Today</span>':''}
            </div>
            ${ds.slice(0,3).map((s,i)=>`<div class="event-bar ${colors[i%colors.length]}" title="${s.course_name||'Exam'}">${s.course_name||s.course_code||'Exam'}</div>`).join('')}
            ${ds.length>3?`<div class="text-[9px] text-slate-400 font-semibold mt-0.5 pl-1">+${ds.length-3} more</div>`:''}
        </div>`;
    }
    html+=`</div></div>
        <div class="p-6 border-t border-slate-200">
            <h3 class="font-bold text-slate-800 mb-4">Approved Schedules This Month</h3>
            ${approvedScheds.length===0?'<p class="text-slate-400 text-sm text-center py-4">No approved schedules.</p>':`<div class="space-y-2 max-h-64 overflow-y-auto">${approvedScheds.map(s=>`
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <div><h4 class="font-semibold text-sm text-slate-800">${s.course_name||s.course_code||'—'}</h4><p class="text-xs text-slate-500">${s.exam_type||'—'} · ${s.exam_date||s.date||'—'}</p></div>
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Approved</span>
                </div>`).join('')}</div>`}
        </div>
    </div>`;
    return html;
}

// ── Day Detail Modal ──────────────────────────────────────────────────────────
window.openDayModal = function(dateStr) {
    const existing = document.getElementById('calDayModal');
    if (existing) existing.remove();

    const dayScheds = allData.filter(d =>
        d.type === 'schedule' &&
        (d.exam_date || d.date || '').trim() === dateStr
    ).sort((a, b) => (a.time_slot || '').localeCompare(b.time_slot || ''));

    const fmtDate = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
    });

    const statusBadge = s => {
        if (s.status === 'Approved')  return 'bg-emerald-100 text-emerald-700';
        if (s.status === 'Rejected')  return 'bg-red-100 text-red-600';
        return 'bg-amber-100 text-amber-700';
    };
    const statusLabel = s => {
        if (s.status === 'Approved') return '✓ Approved';
        if (s.status === 'Rejected') return '✕ Rejected';
        return '⏳ Pending';
    };
    const colors = ['border-l-blue-500','border-l-purple-500','border-l-pink-500','border-l-orange-500','border-l-teal-500'];

    const schedRows = dayScheds.length === 0
        ? `<div class="text-center py-12">
               <div class="w-14 h-14 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                   <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
               </div>
               <p class="text-sm font-semibold text-slate-500">No schedules on this day</p>
           </div>`
        : dayScheds.map((s, i) => {
            const room    = allData.find(d => d.type === 'room' && String(d.id) === String(s.room_id || s.room));
            const proctor = allData.find(d => d.type === 'proctor' && String(d.id) === String(s.proctor_id));
            return `
            <div class="border border-slate-200 border-l-4 ${colors[i % colors.length]} rounded-xl p-4 hover:bg-slate-50 transition">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-slate-900 text-sm truncate">${s.course_name || s.course_code || '—'}</p>
                        <p class="text-xs text-slate-500 mt-0.5">${s.course_code || ''} ${s.college ? '· ' + s.college : ''}</p>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold shrink-0 ${statusBadge(s)}">${statusLabel(s)}</span>
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs text-slate-600 mt-3">
                    ${s.time_slot ? `<div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>${s.time_slot}</span></div>` : ''}
                    ${s.exam_type ? `<div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><span>${s.exam_type}</span></div>` : ''}
                    ${s.section ? `<div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg><span>Section ${s.section}</span></div>` : ''}
                    ${s.year_level ? `<div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg><span>${s.year_level}</span></div>` : ''}
                    ${room ? `<div class="flex items-center gap-1.5 col-span-2"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg><span>${room.building} — ${room.name || ''}${room.campus ? ' (' + room.campus + ')' : ''}</span></div>` : (s.room_name ? `<div class="flex items-center gap-1.5 col-span-2"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg><span>${s.room_name}</span></div>` : '')}
                    ${proctor ? `<div class="flex items-center gap-1.5 col-span-2"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span>${proctor.name}</span></div>` : (s.proctor_name ? `<div class="flex items-center gap-1.5 col-span-2"><svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span>${s.proctor_name}</span></div>` : '')}
                </div>
            </div>`;
        }).join('');

    const modal = document.createElement('div');
    modal.id = 'calDayModal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4';
    modal.style.background = 'rgba(0,0,0,0.45)';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden" style="animation:slideUp .2s ease">
        <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-emerald-700 to-emerald-600 shrink-0">
            <div>
                <h2 class="text-base font-bold text-white">${fmtDate}</h2>
                <p class="text-xs text-emerald-200 mt-0.5">${dayScheds.length} schedule${dayScheds.length !== 1 ? 's' : ''} on this day</p>
            </div>
            <button onclick="document.getElementById('calDayModal').remove()"
                class="w-8 h-8 flex items-center justify-center rounded-lg bg-white/20 hover:bg-white/35 text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto flex-1 p-5 space-y-3">
            ${schedRows}
        </div>
        <div class="px-6 py-4 border-t border-slate-100 flex justify-end shrink-0 bg-slate-50">
            <button onclick="document.getElementById('calDayModal').remove()"
                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">Close</button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

function navigateCalendar(direction) {
    if (direction==='prev') { calendarMonth--; if (calendarMonth<0) { calendarMonth=11; calendarYear--; } }
    else if (direction==='next') { calendarMonth++; if (calendarMonth>11) { calendarMonth=0; calendarYear++; } }
    else { const n=new Date(); calendarMonth=n.getMonth(); calendarYear=n.getFullYear(); }
    const c = document.querySelector('#calendar-container');
    if (c) c.innerHTML = renderCalendar();
}

// ─────────────────────────────────────────────────────────────────────────────
// CALENDAR MODAL (Quick Action)
// ─────────────────────────────────────────────────────────────────────────────
let modalCalMonth = new Date().getMonth();
let modalCalYear  = new Date().getFullYear();

function renderCalendarModalBody() {
    const now         = new Date();
    const firstDay    = new Date(modalCalYear, modalCalMonth, 1);
    const lastDay     = new Date(modalCalYear, modalCalMonth + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startDOW    = firstDay.getDay();
    const monthNames  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const dayNames    = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    const approvedScheds = allData.filter(d => d.type === 'schedule' && d.status === 'Approved');
    const byDate = {};
    approvedScheds.forEach(s => {
        const raw = s.exam_date || s.date; if (!raw) return;
        const d = new Date(raw + 'T00:00:00');
        if (d.getMonth() === modalCalMonth && d.getFullYear() === modalCalYear) {
            const dk = d.getDate();
            if (!byDate[dk]) byDate[dk] = [];
            byDate[dk].push(s);
        }
    });

    const colors = ['bg-blue-500','bg-purple-500','bg-pink-500','bg-orange-500','bg-teal-500'];

    let gridCells = '';
    for (let i = 0; i < startDOW; i++) gridCells += `<div class="calendar-cell bg-slate-50 opacity-40"></div>`;
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = day === now.getDate() && modalCalMonth === now.getMonth() && modalCalYear === now.getFullYear();
        const ds = byDate[day] || [];
        const dateStr = `${modalCalYear}-${String(modalCalMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const hasScheds = ds.length > 0;
        gridCells += `<div class="calendar-cell ${isToday ? 'bg-emerald-50' : ''} ${hasScheds ? 'cursor-pointer hover:bg-slate-50' : ''} transition-colors"
            ${hasScheds ? `onclick="openDayModal('${dateStr}')"` : ''}>
            <div class="flex justify-between items-start mb-1">
                <span class="text-sm font-semibold ${isToday ? 'text-emerald-600' : 'text-slate-700'}">${day}</span>
                ${isToday ? '<span class="text-[9px] bg-emerald-600 text-white px-1.5 py-0.5 rounded-full font-bold">Today</span>' : ''}
            </div>
            ${ds.slice(0, 2).map((s, i) => `<div class="event-bar ${colors[i % colors.length]}" title="${s.course_name || 'Exam'}">${s.course_code || s.course_name || 'Exam'}</div>`).join('')}
            ${ds.length > 2 ? `<div class="text-[9px] text-slate-400 font-semibold mt-0.5">+${ds.length - 2} more</div>` : ''}
        </div>`;
    }

    // This month's approved schedules list
    const thisMonthScheds = approvedScheds.filter(s => {
        const raw = s.exam_date || s.date; if (!raw) return false;
        const d = new Date(raw + 'T00:00:00');
        return d.getMonth() === modalCalMonth && d.getFullYear() === modalCalYear;
    });

    const schedList = thisMonthScheds.length === 0
        ? `<p class="text-slate-400 text-sm text-center py-4">No approved schedules this month.</p>`
        : `<div class="space-y-2 max-h-40 overflow-y-auto pr-1">
            ${thisMonthScheds.map(s => `
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                <div>
                    <p class="font-semibold text-sm text-slate-800">${s.course_name || s.course_code || '—'}</p>
                    <p class="text-xs text-slate-500">${s.exam_type || '—'} · ${s.exam_date || s.date || '—'} ${s.time_slot ? '· ' + s.time_slot : ''}</p>
                </div>
                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold whitespace-nowrap">Approved</span>
            </div>`).join('')}
          </div>`;

    return `
    <!-- Header nav -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <button onclick="navigateCalendarModal('prev')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span class="font-bold text-slate-800 text-sm w-36 text-center">${monthNames[modalCalMonth]} ${modalCalYear}</span>
            <button onclick="navigateCalendarModal('next')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
        <button onclick="navigateCalendarModal('today')" class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-semibold hover:bg-emerald-100 transition">Today</button>
    </div>
    <!-- Day labels -->
    <div class="px-4 pt-3">
        <div class="calendar-grid mb-0" style="border:none;">
            ${dayNames.map(d => `<div class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-wider pb-1">${d}</div>`).join('')}
        </div>
        <!-- Grid cells -->
        <div class="calendar-grid" style="font-size:0.75rem;">
            ${gridCells}
        </div>
    </div>
    <!-- Schedule list -->
    <div class="px-6 py-4 border-t border-slate-100 mt-2">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Approved Schedules — ${monthNames[modalCalMonth]}</p>
        ${schedList}
    </div>`;
}

window.openCalendarModal = function() {
    if (document.getElementById('calendarQuickModal')) return;
    // Reset to current month on open
    modalCalMonth = new Date().getMonth();
    modalCalYear  = new Date().getFullYear();

    const overlay = document.createElement('div');
    overlay.id = 'calendarQuickModal';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:50;display:flex;align-items:center;justify-content:center;padding:1rem;backdrop-filter:blur(2px);';
    overlay.innerHTML = `
    <div id="calendarQuickModalBox" style="background:white;border-radius:1.25rem;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,0.18);display:flex;flex-direction:column;">
        <!-- Title bar -->
        <div style="background:linear-gradient(135deg,#047857,#059669);padding:1.1rem 1.5rem;border-radius:1.25rem 1.25rem 0 0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:0.6rem;">
                <div style="background:rgba(255,255,255,0.2);border-radius:0.6rem;padding:0.4rem;">
                    <svg width="18" height="18" fill="none" stroke="white" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <p style="color:white;font-weight:700;font-size:0.95rem;margin:0;">Exam Calendar</p>
                    <p style="color:rgba(255,255,255,0.7);font-size:0.7rem;margin:0;">Approved exam schedules</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <button onclick="currentView='calendar';sessionStorage.setItem('head_currentView',currentView);document.getElementById('calendarQuickModal').remove();renderApp();"
                    style="padding:0.35rem 0.85rem;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);border-radius:0.5rem;color:white;font-size:0.72rem;font-weight:600;cursor:pointer;transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    Full View →
                </button>
                <button onclick="document.getElementById('calendarQuickModal').remove();document.body.style.overflow='';"
                    style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.2);border:none;cursor:pointer;color:white;font-size:16px;display:flex;align-items:center;justify-content:center;transition:background 0.15s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.35)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">✕</button>
            </div>
        </div>
        <!-- Calendar body -->
        <div id="calendarModalBody">
            ${renderCalendarModalBody()}
        </div>
    </div>`;

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    overlay.addEventListener('click', e => {
        if (e.target === overlay) {
            overlay.remove();
            document.body.style.overflow = '';
        }
    });
};

window.navigateCalendarModal = function(direction) {
    if (direction === 'prev')  { modalCalMonth--; if (modalCalMonth < 0)  { modalCalMonth = 11; modalCalYear--; } }
    else if (direction === 'next')  { modalCalMonth++; if (modalCalMonth > 11) { modalCalMonth = 0;  modalCalYear++; } }
    else { const n = new Date(); modalCalMonth = n.getMonth(); modalCalYear = n.getFullYear(); }
    const body = document.getElementById('calendarModalBody');
    if (body) body.innerHTML = renderCalendarModalBody();
};

// ─────────────────────────────────────────────────────────────────────────────
// 12. EXPORT / IMPORT / TEMPLATE
// ─────────────────────────────────────────────────────────────────────────────
window.toggleHeadNotif = function() {
    const dd = document.getElementById('headNotifDropdown');
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
window.exportSchedules = () => exportToCSV(
    ['Course Code','Course Name','Exam Type','Date','Time Slot','Room','is_online','Proctor','Status'],
    allData.filter(d=>d.type==='schedule').map(s=>[s.course_code||'',s.course_name||'',s.exam_type||'',s.exam_date||s.date||'',s.time_slot||'',s.is_online?'Online':(s.room_name||s.room||''),s.is_online?1:0,s.proctor_name||'',s.status||'Pending']),
    'schedules_export.csv'
);
window.vsExportPDF = function() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    const now = new Date();
    const generated = now.toLocaleDateString('en-US', { month: 'numeric', day: 'numeric', year: 'numeric' })
        + ', ' + now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

    // Read active filter values directly from DOM
    const search   = (document.getElementById('viewSchedSearch')?.value || '').toLowerCase();
    const college  = document.getElementById('viewSchedCollege')?.value || '';
    const semester = document.getElementById('viewSchedSemester')?.value || '';
    const course   = (document.getElementById('viewSchedCourse')?.value || '').toLowerCase();
    const examType = document.getElementById('viewSchedType')?.value || '';
    const year     = document.getElementById('viewSchedYear')?.value || '';
    const status   = document.getElementById('viewSchedStatus')?.value || '';
    const campus   = (currentUser?.campus || '').trim();

    // Header text — matches campus_admin style
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
    if (search)   activeFilters.push('Search: "' + search + '"');
    if (college)  activeFilters.push('College: ' + college);
    if (semester) activeFilters.push('Semester: ' + semester);
    if (course)   activeFilters.push('Course: ' + course.toUpperCase());
    if (examType) activeFilters.push('Type: ' + examType);
    if (year)     activeFilters.push('Year Level: ' + year);
    if (status)   activeFilters.push('Status: ' + status);
    if (activeFilters.length) {
        doc.setFontSize(8);
        doc.setTextColor(100, 116, 139);
        doc.text('Filters: ' + activeFilters.join('  |  '), 14, 36);
    }
    const headerEndY = activeFilters.length ? 42 : 35;

    // Apply same filters as the table
    let rows = allData.filter(d => d.type === 'schedule');
    if (college)  rows = rows.filter(s => (s.college||'') === college);
    if (examType) rows = rows.filter(s => (s.exam_type||'') === examType);
    if (semester) rows = rows.filter(s => (s.semester||'') === semester);
    if (year)     rows = rows.filter(s => (s.year_level||'') === year);
    if (status)   rows = rows.filter(s => (s.status||'Pending') === status);
    if (search)   rows = rows.filter(s =>
        (s.course_code||'').toLowerCase().includes(search) ||
        (s.course_name||'').toLowerCase().includes(search) ||
        (s.room_name||s.room||'').toLowerCase().includes(search) ||
        (s.proctor_name||'').toLowerCase().includes(search)
    );

    // Guard: no schedules match the current filters
    if (rows.length === 0) {
        showToast('No schedules available for the selected filters.', 'error');
        return;
    }

    doc.autoTable({
        startY: headerEndY,
        head: [['Course Code', 'Course Name', 'Semester', 'Section', 'College', 'Program', 'Exam Type', 'Date', 'Time', 'Room', 'Campus']],
        body: rows.length > 0 ? rows.map(s => {
            const rawDate = s.exam_date||s.date||'';
            const dateStr = (() => { if (!rawDate || rawDate === '0000-00-00' || rawDate === '0000-00-00 00:00:00') return '—'; try { const d = new Date(rawDate.substring(0,10)+'T00:00:00'); return isNaN(d.getTime()) ? '—' : d.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'2-digit',year:'numeric'}); } catch(e) { return '—'; } })();
            const yrLevel = s.year_level ? s.year_level + ' ' : '';
            const section = (yrLevel + (s.section||'')).trim() || '—';
            const room = s.is_online ? 'Online' : (s.room_name||s.room||'—');
            return [
                s.course_code||'—', s.course_name||'—', s.semester||'—',
                section, s.college||'—', s.program||s.college_program||'—',
                s.exam_type||'—', dateStr, s.time_slot||'—',
                room, s.campus||'—'
            ];
        }) : [['No schedules found for current filters', '', '', '', '', '', '', '', '', '', '']],
        styles: {
            fontSize: 7,
            cellPadding: 2.5,
            textColor: [55, 65, 81],
            lineColor: [226, 232, 240],
            lineWidth: 0.1,
        },
        headStyles: { fillColor: [4,120,87], textColor: [255,255,255], fontStyle:'bold', fontSize: 7 },
        alternateRowStyles: { fillColor: [240, 253, 244] },
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

    const campusSuffix = campus ? '_' + campus.replace(/\s+/g,'') : '';
    doc.save('ExamSchedules' + campusSuffix + '.pdf');
};

window.downloadScheduleTemplate = () => downloadTemplate(
    ['Course Code','Course Name','College','Program','Year Level','Semester','Section','Exam Type','exam_date','Time Slot','Duration','Room Name','is_online','Proctor Name','Campus'],
    ['RIZL211','Life and Works of Rizal','CCS','BSIT','1st Year','1st Semester','BSIT 1-Y1-1','Prelim','2026-04-10','08:00 AM - 10:00 AM','2 Hours','CAS201','0','Dr. Juan Dela Cruz','Quezon City'],
    'schedule_template.csv'
);
// ── Parse a single CSV line respecting quoted fields ──────────────────────
function _headParseCSVLine(line) {
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
        } else { current += ch; }
    }
    result.push(current.trim());
    return result;
}

window.importSchedules = () => {
    // ── Open the Import modal (same look as admin.php) ────────────────────
    if (document.getElementById('headImportModal')) return;
    const modal = document.createElement('div');
    modal.id = 'headImportModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-5 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Import Schedules</h2>
                <p class="text-xs text-slate-400 mt-0.5">Upload a CSV file to import records</p>
            </div>
            <button onclick="document.getElementById('headImportModal').remove()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-2">
                <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-blue-700">Use the <strong>Template</strong> button to download the correct CSV format before importing.</p>
            </div>
            <!-- Drop zone -->
            <div id="headImportDropZone"
                class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/30 transition"
                onclick="document.getElementById('headImportFileInput').click()"
                ondragover="event.preventDefault();this.classList.add('border-emerald-400','bg-emerald-50/30')"
                ondragleave="this.classList.remove('border-emerald-400','bg-emerald-50/30')"
                ondrop="event.preventDefault();this.classList.remove('border-emerald-400','bg-emerald-50/30');window._headProcessImportFile(event.dataTransfer.files[0])">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <p class="text-sm font-semibold text-slate-600">Drop CSV file here</p>
                <p class="text-xs text-slate-400 mt-1">or click to browse</p>
                <input type="file" id="headImportFileInput" accept=".csv" class="hidden"
                    onchange="window._headProcessImportFile(this.files[0])">
            </div>
            <!-- Preview (hidden until file selected) -->
            <div id="headImportPreview" class="hidden space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700 truncate" id="headImportFileName"></p>
                    <span id="headImportRowCount" class="text-xs text-emerald-600 font-bold whitespace-nowrap ml-2"></span>
                </div>
                <div class="max-h-40 overflow-auto border border-slate-200 rounded-lg text-xs font-mono" id="headImportPreviewTable"></div>
            </div>
            <!-- Error / lock message -->
            <div id="headImportError" class="hidden text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <!-- Action buttons -->
            <div class="flex gap-3">
                <button onclick="document.getElementById('headImportModal').remove()"
                    class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition text-sm">Cancel</button>
                <button id="headImportConfirmBtn" disabled
                    onclick="window._headConfirmImport()"
                    class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition text-sm opacity-50 cursor-not-allowed">Import</button>
            </div>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

// ── Stored parsed rows + file hash (used by confirm) ─────────────────────
window._headImportParsedRows = [];
window._headImportFileHash   = null;
window._headImportFileName   = '';

// ── Process the selected/dropped file: parse CSV + show preview ──────────
window._headProcessImportFile = function(file) {
    if (!file || !file.name.endsWith('.csv')) {
        const err = document.getElementById('headImportError');
        if (err) { err.textContent = 'Please upload a valid .csv file.'; err.classList.remove('hidden'); }
        return;
    }
    window._headImportFileName = file.name;

    const reader = new FileReader();
    reader.onload = function(ev) {
        const lines  = ev.target.result.split('\n').filter(l => l.trim());
        const header = _headParseCSVLine(lines[0]).map(h => h.replace(/^"|"$/g,'').trim().toLowerCase());
        const displayHeader = _headParseCSVLine(lines[0]).map(h => h.replace(/^"|"$/g,'').trim());
        const rows = lines.slice(1).map(line => {
            const vals = _headParseCSVLine(line).map(v => v.replace(/^"|"$/g,'').trim());
            const obj = {};
            header.forEach((h, i) => { obj[h] = vals[i] !== undefined ? vals[i] : ''; });
            return obj;
        }).filter(r => Object.values(r).some(v => v));

        window._headImportParsedRows = rows;

        // ── Update UI ──────────────────────────────────────────────────────
        const nameEl   = document.getElementById('headImportFileName');
        const countEl  = document.getElementById('headImportRowCount');
        const preview  = document.getElementById('headImportPreview');
        const tableEl  = document.getElementById('headImportPreviewTable');
        const errEl    = document.getElementById('headImportError');

        if (errEl)  { errEl.textContent = ''; errEl.classList.add('hidden'); errEl.style.cssText = ''; }
        if (nameEl)  nameEl.textContent  = file.name;

        // Count past-date rows to warn the user before they confirm
        const _now0    = new Date();
        const _today0  = new Date(_now0.getFullYear(), _now0.getMonth(), _now0.getDate());
        let _pastCount = 0;
        rows.forEach(r => {
            const rawD = (r['exam_date'] || r['exam_dat'] || r['date_(yyyy-mm-dd)'] || r['date (yyyy-mm-dd)'] || r['date (month dd, yyyy)'] || r['date'] || '').trim();
            // Normalize to YYYY-MM-DD for comparison
            let normD = rawD;
            if (!/^\d{4}-\d{2}-\d{2}$/.test(rawD)) {
                const _mmap2 = {january:'01',february:'02',march:'03',april:'04',may:'05',june:'06',july:'07',august:'08',september:'09',october:'10',november:'11',december:'12',jan:'01',feb:'02',mar:'03',apr:'04',jun:'06',jul:'07',aug:'08',sep:'09',oct:'10',nov:'11',dec:'12'};
                const _wm2 = rawD.match(/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/);
                if (_wm2 && _mmap2[_wm2[1].toLowerCase()]) normD = `${_wm2[3]}-${_mmap2[_wm2[1].toLowerCase()]}-${_wm2[2].padStart(2,'0')}`;
            }
            r._norm_exam_date = normD; // cache for preview highlighting below
            const dp0 = normD.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (dp0 && new Date(+dp0[1], +dp0[2]-1, +dp0[3]) < _today0) _pastCount++;
        });

        if (countEl) countEl.textContent = `${rows.length} row${rows.length !== 1 ? 's' : ''} ready`
            + (_pastCount > 0 ? ` · ⚠️ ${_pastCount} past-date row${_pastCount !== 1 ? 's' : ''} will be blocked` : '');
        if (preview) preview.classList.remove('hidden');

        if (_pastCount > 0 && errEl) {
            const _pastMsg = `⚠️ ${_pastCount} row${_pastCount !== 1 ? 's have' : ' has'} a past exam date and will be skipped during import.`;
            errEl.textContent = _pastMsg;
            errEl.style.cssText = 'display:block;color:#92400e;background:#fffbeb;border:1px solid #fde68a;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;margin-top:0.5rem;';
            errEl.classList.remove('hidden');
        }

        // ── Preview table (first 5 rows) — highlight past-date rows in red ──
        const _now2   = new Date();
        const _today2 = new Date(_now2.getFullYear(), _now2.getMonth(), _now2.getDate());
        const previewRows = rows.slice(0, 5);
        if (tableEl) tableEl.innerHTML = `
            <table class="w-full">
                <thead class="bg-slate-50 sticky top-0"><tr>
                    ${displayHeader.map(h => `<th class="px-2 py-1.5 text-left text-[10px] uppercase text-slate-500 font-bold whitespace-nowrap">${h}</th>`).join('')}
                    <th class="px-2 py-1.5 text-[10px] uppercase text-slate-500 font-bold"></th>
                </tr></thead>
                <tbody>
                    ${previewRows.map(r => {
                        const dp2 = (r._norm_exam_date || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
                        const _isPast = dp2 && new Date(+dp2[1], +dp2[2]-1, +dp2[3]) < _today2;
                        return `<tr class="${_isPast ? 'bg-red-50' : 'border-t border-slate-100'}">
                            ${header.map(h => `<td class="px-2 py-1.5 ${_isPast ? 'text-red-400 line-through' : 'text-slate-700'} truncate max-w-[120px]" title="${r[h]||''}">${r[h]||'—'}</td>`).join('')}
                            <td class="px-2 py-1 whitespace-nowrap">${_isPast ? '<span style="font-size:10px;font-weight:700;color:#dc2626;">⛔ PAST DATE</span>' : ''}</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
            ${rows.length > 5 ? `<p class="text-center text-xs text-slate-400 p-2">…and ${rows.length - 5} more rows</p>` : ''}`;
    };
    reader.readAsText(file);

    // ── Hash + lock-check (async, enables/disables Import button) ─────────
    window._headImportFileHash = null;
    (async () => {
        const _btn   = document.getElementById('headImportConfirmBtn');
        const _errEl = document.getElementById('headImportError');
        try {
            const _hashBuf = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());
            window._headImportFileHash = Array.from(new Uint8Array(_hashBuf))
                .map(b => b.toString(16).padStart(2,'0')).join('');

            const _chk = await flexamFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'check', file_hash: window._headImportFileHash, import_type: 'schedule' })
            }).catch(() => null);

            if (_chk && (_chk.status === 'already_imported' || _chk.status === 'locked')) {
                // ── Blocked ────────────────────────────────────────────────
                if (_btn) { _btn.disabled = true; _btn.classList.add('opacity-50','cursor-not-allowed'); }
                if (_errEl) {
                    const isPerma = _chk.status === 'already_imported';
                    const _dispMsg = isPerma ? '⛔ This file has already been imported into the system.' : '⏳ This file is currently being processed by the system. Please wait a moment and try again.';
                    _errEl.textContent = _dispMsg;
                    _errEl.style.cssText = 'display:block;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;';
                    _errEl.classList.remove('hidden');
                    showToast(_dispMsg, 'error');
                }
            } else {
                // ── Free ───────────────────────────────────────────────────
                if (_btn) { _btn.disabled = false; _btn.classList.remove('opacity-50','cursor-not-allowed'); }
                if (_errEl) { _errEl.textContent = ''; _errEl.classList.add('hidden'); }
            }
        } catch(_e) {
            // Fail open — let user try
            window._headImportFileHash = null;
            if (_btn) { _btn.disabled = false; _btn.classList.remove('opacity-50','cursor-not-allowed'); }
        }
    })();
};

// ── Confirm Import — acquires lock then runs the actual import loop ───────
window._headConfirmImport = async function() {
    const rows = window._headImportParsedRows;
    if (!rows || rows.length === 0) { showToast('No data to import', 'error'); return; }

    const _btn   = document.getElementById('headImportConfirmBtn');
    const _errEl = document.getElementById('headImportError');
    const _importHash = window._headImportFileHash;
    const file = { name: window._headImportFileName }; // lightweight ref for logging

    // ── Acquire lock ──────────────────────────────────────────────────────
    if (_importHash) {
        const _acq = await flexamFetch('../api/import_lock.php', {
            method: 'POST',
            body: JSON.stringify({ action: 'acquire', file_hash: _importHash, import_type: 'schedule' })
        }).catch(() => null);

        if (!_acq || !_acq.success) {
            const _isPerma = _acq?.status === 'already_imported';
            const _acqMsg  = _isPerma ? '⛔ This file has already been imported into the system.' : '⏳ This file is currently being processed by the system. Please wait a moment and try again.';
            if (_errEl) {
                _errEl.textContent = _acqMsg;
                _errEl.style.cssText = 'display:block;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.8rem;';
                _errEl.classList.remove('hidden');
            }
            showToast(_acqMsg, 'error');
            return;
        }
    }

    // ── UI: disable button + show loading ─────────────────────────────────
    if (_btn) { _btn.disabled = true; _btn.textContent = 'Importing…'; }

        let success = 0, failed = 0, skipped = [];

        document.dispatchEvent(new Event('flexam:importStart'));
        // ── Snapshot allData BEFORE the loop ──────────────────────────────────
        const preImportSnapshot = allData.slice();
        // Track section+college+examType added in THIS batch
        const sessionSectionKeys = new Set();

        for (const row of rows) {
            const courseCode = row['course_code'] || row['course_co'] || '';
            if (!courseCode) { failed++; skipped.push({ label: 'Unknown row', reason: 'Missing course code' }); continue; }

            const examType  = row['exam_type'] || 'Midterm';
            // Support all column name variants (exam_date matches admin & campus_admin templates)
            const rawDate   = row['exam_date'] || row['exam_dat'] || row['date_(yyyy-mm-dd)'] || row['date (yyyy-mm-dd)'] || row['date (month dd, yyyy)'] || row['date'] || '';
            // Normalize date to YYYY-MM-DD
            const examDate  = (() => {
                if (!rawDate) return '';
                const s = rawDate.trim();
                if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
                // "Month DD, YYYY" e.g. "April 10, 2026"
                const _mmap = {january:'01',february:'02',march:'03',april:'04',may:'05',june:'06',july:'07',august:'08',september:'09',october:'10',november:'11',december:'12',jan:'01',feb:'02',mar:'03',apr:'04',jun:'06',jul:'07',aug:'08',sep:'09',oct:'10',nov:'11',dec:'12'};
                const _wm = s.match(/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/);
                if (_wm && _mmap[_wm[1].toLowerCase()]) return `${_wm[3]}-${_mmap[_wm[1].toLowerCase()]}-${_wm[2].padStart(2,'0')}`;
                // DD/MM/YYYY or YYYY/MM/DD
                const _sp = s.split('/');
                if (_sp.length === 3) {
                    const [a, b, c] = _sp;
                    if (a.length === 4) return `${a}-${b.padStart(2,'0')}-${c.padStart(2,'0')}`;
                    return `${c}-${b.padStart(2,'0')}-${a.padStart(2,'0')}`;
                }
                return s;
            })();
            const timeSlot   = (function(raw) {
                if (!raw) return '';
                raw = raw.trim();
                // Helper: validate that a parsed 12-hour hour is in range 1-12 (0 is invalid in 12-hr clock)
                const _validHour12 = h => parseInt(h, 10) >= 1 && parseInt(h, 10) <= 12;
                // Already correct format -- but still reject hour 00 or > 12
                if (/^\d{2}:\d{2} [AP]M - \d{2}:\d{2} [AP]M$/.test(raw)) {
                    const h = parseInt(raw.substring(0, 2), 10);
                    if (h === 0 || h > 12) return ''; // 00:xx and 13:xx+ are not valid 12-hour times
                    return raw;
                }
                const pad = n => String(n).padStart(2, '0');
                const rangeRe = /(\d{1,2})(?::(\d{2}))?\s*(AM|PM|am|pm)\s*[-–]\s*(\d{1,2})(?::(\d{2}))?\s*(AM|PM|am|pm)/i;
                const match = raw.match(rangeRe);
                if (match) {
                    const [, h1, m1 = '00', p1, h2, m2 = '00', p2] = match;
                    // Reject if either hour is 0 (invalid in 12-hour clock)
                    if (!_validHour12(h1) || !_validHour12(h2)) return '';
                    return `${pad(h1)}:${m1.padStart(2,'0')} ${p1.toUpperCase()} - ${pad(h2)}:${m2.padStart(2,'0')} ${p2.toUpperCase()}`;
                }
                // Unrecognized format (e.g. "00:00", bare 24-hour times) -- reject
                return '';
            })(row['time_slot'] || '');
            const campus     = row['campus'] || '';
            const program    = row['program'] || '';
            const section    = (row['section'] || '').trim();
            const yearLevel  = (row['year_level'] || row['year_leve'] || '').trim();
            const rowLabel   = `${courseCode} · ${examType} · ${section || examDate}`;

            // -- BLOCK: Invalid / unrecognized time slot (e.g. "00:00", bare 24-hour times) --
            if (!timeSlot) {
                const rawTs = (row['time_slot'] || '').trim();
                skipped.push({
                    label:  rowLabel,
                    reason: `Invalid or missing time slot${rawTs ? ': "' + rawTs + '"' : ''}. Use 12-hour format, e.g. "07:00 AM - 08:00 AM". Schedule must be between 7:00 AM and 9:00 PM.`,
                    type:   'error'
                });
                continue;
            }

            // ── BLOCK: Past date / past time ─────────────────────────────────────
            // examDate is always YYYY-MM-DD here (normalised above).
            // Use Date objects to avoid string-comparison edge cases.
            if (examDate) {
                const dp = examDate.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                if (dp) {
                    const examDObj      = new Date(+dp[1], +dp[2] - 1, +dp[3]);
                    const now           = new Date();
                    const todayMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate());

                    if (examDObj < todayMidnight) {
                        failed++;
                        skipped.push({
                            label:  rowLabel,
                            reason: `Past date blocked: "${examDate}" is already in the past. Only future dates can be imported.`,
                            type:   'error'
                        });
                        continue;
                    }

                    if (examDObj.getTime() === todayMidnight.getTime() && timeSlot) {
                        // Today — check whether the slot's END time has already passed
                        const endPart = timeSlot.split('-').pop().trim();
                        const em = endPart.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
                        if (em) {
                            let eh = +em[1], emm = +em[2];
                            const ep = em[3].toUpperCase();
                            if (ep === 'PM' && eh !== 12) eh += 12;
                            if (ep === 'AM' && eh === 12) eh  = 0;
                            if (now.getHours() * 60 + now.getMinutes() >= eh * 60 + emm) {
                                failed++;
                                skipped.push({
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
            // ── END: Past date / past time block ─────────────────────────────────

            // Auto-find or create course
            let course = allData.find(d =>
                d.type === 'course' &&
                (d.course_code || '').toLowerCase() === courseCode.toLowerCase() &&
                (!campus || !d.campus || d.campus.toLowerCase() === campus.toLowerCase())
            ) || allData.find(d =>
                d.type === 'course' &&
                (d.course_code || '').toLowerCase() === courseCode.toLowerCase()
            );
            if (!course) {
                const rawProgram    = (row['program']    || row['programme']    || '').trim();
                const rawCollege    = (row['college']    || row['college_name'] || rawProgram || '').trim();
                const rawCourseName = (row['course_name'] || row['course_na']   || '').trim();
                const rawSemester   = (row['semester']   || row['sem']          || '1st Semester').trim();
                const rawYearLevel  = (row['year_level'] || row['year_leve']    || '1st Year').trim();
                const res = await window.flexamApi.courses.create({
                    course_code: courseCode,
                    course_name: rawCourseName || courseCode,
                    college:     rawCollege    || rawProgram || '',
                    program:     rawProgram    || '',
                    year_level:  rawYearLevel  || '1st Year',
                    semester:    rawSemester   || '1st Semester',
                    campus:      campus        || ''
                });
                if (res.success && res.data) {
                    course = { ...res.data, type: 'course' };
                    allData.push(course);
                } else if (res.success) {
                    course = { id: res.id, course_code: courseCode, course_name: rawCourseName || courseCode, type: 'course' };
                    allData.push({ ...course });
                }
            }

            // Auto-find or create room
            const isOnlineCsv  = ['1','yes','true','online'].includes((row['is_online'] || '').trim().toLowerCase()) ? 1 : 0;
            const rawRoomName  = isOnlineCsv ? '' : (row['room_name'] || row['room'] || '');
            const roomParts    = rawRoomName.split(',');
            const roomCode     = roomParts[0].trim();
            const roomBuilding = roomParts[1]?.trim() || 'Main Building';

            // ── BLOCK: Invalid room_name format ──────────────────────────────────────
            // Required format: "RoomNumber,BuildingCode" e.g. "501,VSB"
            // Must contain a comma, both parts non-empty, and NOT be an online exam row.
            if (rawRoomName && !isOnlineCsv) {
                const _roomFormatValid =
                    rawRoomName.includes(',') &&
                    rawRoomName.split(',')[0].trim() !== '' &&
                    rawRoomName.split(',').slice(1).join(',').trim() !== '';

                if (!_roomFormatValid) {
                    failed++;
                    skipped.push({
                        label:  rowLabel,
                        reason: `Invalid room format: "${rawRoomName}". Use "RoomNumber,BuildingCode" format (e.g. "501,VSB"). Row skipped.`,
                        type:   'error'
                    });
                    continue;
                }
            }

            // Look for existing room: same name + same campus + same building (exact match)
            let room = allData.find(d =>
                d.type === 'room' &&
                (d.name||'').toLowerCase() === roomCode.toLowerCase() &&
                (!d.campus || (d.campus||'').toLowerCase() === (campus||'').toLowerCase()) &&
                (!d.building || (d.building||'').toLowerCase() === roomBuilding.toLowerCase())
            );
            // Fallback: match by raw room_name string (covers "101, SAB LG" style with spaces after comma)
            if (!room && rawRoomName) {
                room = allData.find(d =>
                    d.type === 'room' &&
                    (d.name||'').toLowerCase() === roomCode.toLowerCase() &&
                    (!d.campus || (d.campus||'').toLowerCase() === (campus||'').toLowerCase())
                );
            }
            if (!room && roomCode) {
                const res = await window.flexamApi.rooms?.create?.({
                    name: roomCode, building: roomBuilding,
                    capacity: 40, campus: campus, locked: 0
                });
                if (res?.success && (res.id || res.data?.id)) {
                    room = { id: res.id || res.data?.id, name: roomCode, building: roomBuilding, campus, type: 'room' };
                    allData.push({ ...room });
                } else if (res?.success) {
                    // API returned success but no id — re-query allData to find it
                    room = allData.find(d =>
                        d.type === 'room' &&
                        (d.name||'').toLowerCase() === roomCode.toLowerCase() &&
                        (!d.campus || (d.campus||'').toLowerCase() === (campus||'').toLowerCase())
                    );
                }
            }
            // Use rawRoomName directly so stored room_name always matches the CSV exactly
            const roomName = rawRoomName || (room ? (room.building ? `${room.name}, ${room.building}` : room.name) : '');

            // Find proctor
            const proctorName = row['proctor_name'] || row['proctor'] || '';
            const proctor = allData.find(d => d.type === 'proctor' && (d.name||'').toLowerCase() === proctorName.toLowerCase());

            // ── Section duplicate check (runs BEFORE create) ──────────────────
            // Rule: same section + same college + same exam_type + same course + same date = duplicate.
            // Same section in a DIFFERENT college, or on a DIFFERENT date with a DIFFERENT course, is allowed
            // (exams span 3-4 days with different subjects each day).
            const _importCollege = (row['college'] || row['college_name'] || '').trim().toLowerCase();
            const _importSection = section.toLowerCase();
            const _importCourse  = (row['course_code'] || row['course'] || '').trim().toLowerCase();
            const _importDate    = examDate;
            const _importProgram = (row['program'] || row['programme'] || '').trim().toLowerCase();
            if (_importSection && _importCollege) {
                const _batchKey = `${_importSection}|${_importCollege}|${_importProgram}|${examType.toLowerCase()}|${_importCourse}|${_importDate}`;

                // Check 1: duplicate within this import batch
                if (sessionSectionKeys.has(_batchKey)) {
                    failed++;
                    skipped.push({
                        label:  rowLabel,
                        reason: `Duplicate in this import: Section "${_importSection.toUpperCase()}" under "${_importCollege.toUpperCase()}" for a ${examType} exam was already processed in this batch.`,
                        type:   'duplicate'
                    });
                    continue;
                }

                // Check 2: duplicate against pre-import snapshot only (not live allData).
                // College is resolved with a fallback chain — d.college → linked course.college
                // → course.program — so schedules with empty d.college are still matched.
                const _resolveScheduleCollege = (d) => {
                    const direct = (d.college || '').trim().toLowerCase();
                    if (direct) return direct;
                    const cid   = d.course_id ? String(d.course_id) : null;
                    const ccode = (d.course_code || '').trim().toLowerCase();
                    const linked = preImportSnapshot.find(c =>
                        c.type === 'course' && (
                            (cid && String(c.id) === cid) ||
                            (ccode && (c.course_code || '').toLowerCase() === ccode)
                        )
                    );
                    if (linked) {
                        const cc = (linked.college || '').trim().toLowerCase();
                        if (cc) return cc;
                        return (linked.program || '').trim().toLowerCase();
                    }
                    return '';
                };
                const _existsInDb = preImportSnapshot.find(d =>
                    d.type === 'schedule' &&
                    (d.exam_type || '').toLowerCase() === examType.toLowerCase() &&
                    (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === _importSection &&
                    _resolveScheduleCollege(d) === _importCollege &&
                    (_importProgram ? (d.program || '').trim().toLowerCase() === _importProgram : true) &&
                    (_importCourse ? (d.course_code || '').trim().toLowerCase() === _importCourse : true) &&
                    (_importDate   ? (d.exam_date || d.date || '').trim() === _importDate : true)
                );
                if (_existsInDb) {
                    failed++;
                    const _exDate   = (_existsInDb.exam_date || _existsInDb.date || '').trim();
                    const _exRoom   = (_existsInDb.room_name || '').trim();
                    const _exCourse = (_existsInDb.course_code || '').trim().toUpperCase();
                    skipped.push({
                        label:  rowLabel,
                        reason: `Duplicate: Section "${_importSection.toUpperCase()}" under "${_importCollege.toUpperCase()}" already has a ${examType} exam for course ${_exCourse}${_exDate ? ' on ' + _exDate : ''}${_exRoom ? ' in ' + _exRoom : ''}. Same course on the same date cannot be scheduled twice.`,
                        type:   'duplicate'
                    });
                    continue;
                }
            }

            try {
                // ── Room booking conflict check ──────────────────────────────────────
                // Block if the same room is already booked on the same date+timeslot.
                // Mark as auto-fixable so the import modal shows the "⚡ Fixable" badge
                // and the Auto-Fix button can assign a free room+slot automatically.
                if (!isOnlineCsv && room && examDate && timeSlot) {
                    const _preRid = dbId(String(room.id));
                    const _roomBooked = allData.find(d =>
                        d.type === 'schedule' &&
                        resolveRoomId(d) === _preRid &&
                        (d.exam_date || d.date || '').trim() === examDate &&
                        _arOverlap(d.time_slot, timeSlot)
                    );
                    if (_roomBooked) {
                        failed++;
                        skipped.push({
                            label: rowLabel,
                            reason: `Room conflict: "${room.name}" is already booked on ${examDate} at ${timeSlot} by "${_roomBooked.course_code || _roomBooked.course_name || 'another exam'}". A free room & time slot will be assigned automatically.`,
                            type: 'error',
                            canAutoResolve: true,
                            importRow: {
                                courseCode,
                                examType,
                                yearLevel,
                                section,
                                duration:    row['duration'] || '2 Hours',
                                examDate,
                                campus,
                                semester:    row['semester'] || '1st Semester',
                                courseId:    course?.id || null,
                                courseName:  course?.course_name || row['course_name'] || courseCode,
                                college:     (row['college'] || row['college_name'] || '').trim() || course?.college || '',
                                roomId:      parseInt(_preRid, 10) || null,
                                roomName:    rawRoomName || roomName,
                                proctorId:   proctor?.id || null,
                                proctorName: proctorName,
                                program:     program,
                            }
                        });
                        continue;
                    }
                }
                // ── End room conflict check ──────────────────────────────────────────

                const result = await window.flexamApi.schedules.create({
                    course_id:    course?.id || null,
                    course_code:  courseCode,
                    course_name:  course?.course_name || row['course_name'] || courseCode,
                    college:      (row['college'] || row['college_name'] || '').trim() || course?.college || '',
                    program:      program,
                    section:      section,
                    year_level:   yearLevel,
                    semester:     row['semester'] || '',
                    exam_type:    examType,
                    exam_date:    examDate,
                    time_slot:    timeSlot,
                    duration:     row['duration'] || '2 Hours',
                    room_id:      isOnlineCsv ? null : (room?.id || null),
                    room_name:    isOnlineCsv ? '' : roomName,
                    campus:       campus,
                    proctor_id:   proctor?.id || null,
                    proctor_name: proctorName,
                    status:       'Pending',
                    is_online:    isOnlineCsv,
                    file_hash:    _importHash || null
                });
                if (result.success) {
                    success++;
                    // Mark this section+college+examType+course+date as used in this batch
                    if (_importSection && _importCollege) {
                        sessionSectionKeys.add(`${_importSection}|${_importCollege}|${_importProgram}|${examType.toLowerCase()}|${_importCourse}|${_importDate}`);
                    }
                    allData.push({ ...result.data||{}, type:'schedule', course_code: courseCode, exam_type: examType, exam_date: examDate, campus, time_slot: timeSlot, program, section, college: (row['college'] || row['college_name'] || '').trim() || course?.college || '', year_level: yearLevel, status:'Pending' });
                } else {
                    failed++;
                    skipped.push({ label: rowLabel, reason: result.message || 'Server rejected the row', type: 'error' });
                }
            } catch(err) {
                failed++;
                skipped.push({ label: rowLabel, reason: err.message, type: 'error' });
            }
        }
        document.dispatchEvent(new Event('flexam:importEnd'));
        await refreshAllData();
        // ── Import-lock: release ─────────────────────────────────────────
        if (_importHash) {
            await flexamFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'release', file_hash: _importHash, permanent: success > 0, import_type: 'schedule' })
            }).catch(()=>{});
        }
        // ────────────────────────────────────────────────────────────────
        // Close the import modal before showing results
        const _importModal = document.getElementById('headImportModal');
        if (_importModal) _importModal.remove();

        activityLog('Schedule Import', `${success} added, ${failed} skipped · ${window._headImportFileName}`);
        showImportResults(window._headImportFileName, success, failed, skipped);
};

function showImportResults(filename, success, failed, skipped) {
    const existing = document.getElementById('importResultsModal');
    if (existing) existing.remove();

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

    const resolvableRows = skipped.filter(s => s.canAutoResolve);

    const skippedHtml = skipped.length > 0
        ? `<div class="px-6 py-4 overflow-y-auto flex-1">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Why rows were skipped:</p>
            <div class="space-y-2 pr-1">
                ${skipped.map((s, i) =>
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
    modal.id = 'importResultsModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/60 z-[200] px-4 py-10';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 ${headerBg}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 ${iconBg}">${iconSvg}</div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Import Results</h3>
                    <p class="text-xs text-slate-500 mt-0.5">${totalRows} row${totalRows !== 1 ? 's' : ''} processed · ${filename}</p>
                </div>
            </div>
            <button onclick="document.getElementById('importResultsModal').remove()" class="text-slate-400 hover:text-slate-600">
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
                <button onclick="document.getElementById('importResultsModal').remove(); showToast(${success} > 0 ? '${success} record${success !== 1 ? 's' : ''} imported successfully!' : 'Import complete — no records were added.', ${success} > 0 ? 'success' : 'error')"
                    class="px-5 py-2.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-xl transition text-sm">
                    Done
                </button>
                ${resolvableRows.length > 0 ? `
                <button onclick="document.getElementById('importResultsModal').remove(); autoResolveImportConflicts(window._importResolvableRows)"
                    class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg>
                    Auto-Fix ${resolvableRows.length} Conflict${resolvableRows.length > 1 ? 's' : ''}
                </button>` : ''}
            </div>
        </div>
    </div>`;

    window._importResolvableRows = resolvableRows;
    // ── Persist so conflicts survive page reload ──────────────────────────
    try { localStorage.setItem('flexam_pendingImportConflicts', JSON.stringify(resolvableRows)); } catch(e) {}
    // ── Immediately refresh the Auto-Resolved card with the new conflict count ──
    renderApp();

    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
}

window.autoResolveImportConflicts = async function(resolvableRows) {
    if (!resolvableRows || resolvableRows.length === 0) return;
    document.getElementById('importResultsModal')?.remove();

    // Build a live booking snapshot so we don't double-assign slots
    const liveSchedules = allData.filter(d => d.type === 'schedule').map(s => Object.assign({}, s));

    let fixed = 0, failed = 0, failedLabels = [];

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

        if (!fix) {
            failed++;
            failedLabels.push({ label: s.label, reason: 'No available room or time slot could be found for this date and campus.' });
            continue;
        }

        try {
            const result = await window.flexamApi.schedules.create({
                course_id:     courseId,
                course_code:   courseCode,
                course_name:   courseName,
                college,
                exam_type:     examType,
                semester:      semester || '1st Semester',
                year_level:    yearLevel,
                section:       section || '',
                section_name:  section || '',
                class_section: section || '',
                exam_date:     fix.date || examDate,
                time_slot:     fix.slot,
                duration,
                room_id:       parseInt(fix.rid, 10) || fix.rid,
                room_name:     fix.room.building ? fix.room.building + ', ' + fix.room.name : fix.room.name,
                proctor_id:    proctorId || null,
                proctor_name:  proctorName || '',
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

    // Keep only conflicts that failed to fix; clear successfully fixed ones.
    // Mark server-rejected rows as non-fixable so they no longer inflate the card count.
    const _stillPending = resolvableRows.filter(r => {
        const lbl = r.label || '';
        return failedLabels.some(f => (f.label || '') === lbl);
    }).map(r => {
        const lbl = r.label || '';
        const failInfo = failedLabels.find(f => (f.label || '') === lbl);
        return { ...r, canAutoResolve: false, fixError: failInfo?.reason || 'Could not be fixed.' };
    });
    window._importResolvableRows = _stillPending;
    try { localStorage.setItem('flexam_pendingImportConflicts', JSON.stringify(_stillPending)); } catch(e) {}
    // ── Immediately refresh the Auto-Resolved card to reflect resolved/failed conflicts ──
    renderApp();

    if (failed === 0) {
        showToast(`✓ Auto-fixed ${fixed} import conflict${fixed !== 1 ? 's' : ''} — schedules created!`, 'success');
    } else {
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

window.exportCourses = () => exportToCSV(
    ['Course Code','Course Name','College','Program','Year Level','Semester','Campus'],
    allData.filter(d=>d.type==='course').map(c=>[c.course_code||'',c.course_name||'',c.college||'',c.program||'',c.year_level||'',c.semester||'',c.campus||'']),
    'courses_export.csv'
);
window.downloadCourseTemplate = () => downloadTemplate(
    ['Course Code','Course Name','College','Program','Year Level','Semester','Campus'],
    ['CS101','Introduction to Computing','CCS','BSCS','1st Year','1st Semester','Quezon City'],
    'course_template.csv'
);
window.importCourses = () => {
    const input = document.createElement('input');
    input.type = 'file'; input.accept = '.csv';
    input.onchange = async (e) => {
        const file = e.target.files[0]; if (!file) return;
        // ── Hash file for import-lock ─────────────────────────────────────────
        let _importHash = null;
        try {
            const _hbuf = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());
            _importHash = Array.from(new Uint8Array(_hbuf)).map(b=>b.toString(16).padStart(2,'0')).join('');
        } catch(_e) {}
        if (_importHash) {
            // ── CHECK first (already_imported / locked guard) ────────────
            const _chkFirst = await flexamFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'check', file_hash: _importHash, import_type: 'courses' })
            }).catch(() => null);
            if (_chkFirst && (_chkFirst.status === 'already_imported' || _chkFirst.status === 'locked')) {
                const _chkMsg = _chkFirst.status === 'already_imported'
                    ? '⛔ This file has already been imported into the system.'
                    : '⏳ This file is currently being processed by the system. Please wait a moment and try again.';
                _showImportBlockModal(_chkMsg);
                return;
            }
            // ── ACQUIRE lock ─────────────────────────────────────────────
            const _chk = await flexamFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'acquire', file_hash: _importHash, import_type: 'courses' })
            });
            if (!_chk || !_chk.success) {
                const _st2 = _chk?.status || '';
                const _altMsg = _st2 === 'already_imported'
                    ? '⛔ This file has already been imported into the system.'
                    : '⏳ This file is currently being processed by the system. Please wait a moment and try again.';
                _showImportBlockModal(_altMsg);
                return;
            }
        }
        // ─────────────────────────────────────────────────────────────────────
        const text = await file.text();
        const lines = text.trim().split('\n');
        const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g,'').toLowerCase().replace(/ /g,'_'));
        const rows = lines.slice(1).map(line => {
            const vals = line.split(',');
            const obj = {};
            headers.forEach((h, i) => { obj[h] = (vals[i]||'').trim().replace(/^"|"$/g,''); });
            return obj;
        }).filter(r => Object.values(r).some(v => v));

        let success = 0, failed = 0, skipped = [];
        for (const row of rows) {
            const code    = (row['course_code'] || '').trim();
            const rCampus = (row['campus']      || '').trim();
            if (!code) { failed++; skipped.push({ label: 'Unknown', reason: 'Missing course code', type: 'error' }); continue; }
            // Duplicate check: same course_code + campus
            const isDup = allData.some(d =>
                d.type === 'course' &&
                (d.course_code || '').trim().toLowerCase() === code.toLowerCase() &&
                (d.campus      || '').trim()               === rCampus
            );
            if (isDup) { failed++; skipped.push({ label: code, reason: `Course '${code}' already exists in '${rCampus || 'this campus'}'.`, type: 'duplicate' }); continue; }
            try {
                const result = await window.flexamApi.courses.create({
                    course_code: code, course_name: row['course_name']||code,
                    college: row['college']||'', program: row['program']||'',
                    year_level: row['year_level']||'', semester: row['semester']||'', campus: rCampus
                });
                if (result.success) success++;
                else { failed++; skipped.push({ label: code, reason: result.message||'Server error', type: 'error' }); }
            } catch(err) { failed++; skipped.push({ label: code, reason: err.message, type: 'error' }); }
        }
        // ── Import-lock: release ─────────────────────────────────────────────
        if (_importHash) {
            await flexamFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'release', file_hash: _importHash, permanent: success > 0, import_type: 'courses' })
            }).catch(()=>{});
        }
        // ─────────────────────────────────────────────────────────────────────
        await refreshAllData();
        showImportResults(file.name, success, failed, skipped);
    };
    input.click();
};

window.exportProctors = () => exportToCSV(
    ['Name','College/Program','Email','Phone','Campus'],
    allData.filter(d=>d.type==='proctor').map(p=>[p.name||'',p.college_program||p.collegeProgram||'',p.email||'',p.phone||'',p.campus||'']),
    'proctors_export.csv'
);
window.downloadProctorTemplate = () => downloadTemplate(
    ['Name','College/Program','Email','Phone','Campus'],
    ['Dr. Juan Dela Cruz','CCS - BSCS','jdelacruz@olfu.edu.ph','+63-912-345-6789','Quezon City'],
    'proctor_template.csv'
);
window.importProctors = () => {
    document.getElementById('headProctorImportModal')?.remove();

    const modal = document.createElement('div');
    modal.id = 'headProctorImportModal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;';
    modal.innerHTML = `
    <div style="background:white;border-radius:1rem;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden;">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:flex-start;justify-content:space-between;">
            <div>
                <h2 style="font-size:1.05rem;font-weight:700;color:#1e293b;margin:0;">Import Proctors</h2>
                <p style="font-size:0.78rem;color:#94a3b8;margin:0.2rem 0 0;">Upload a CSV file to import records</p>
            </div>
            <button onclick="document.getElementById('headProctorImportModal').remove()"
                style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.25rem;line-height:1;padding:2px;">&#x2715;</button>
        </div>
        <div style="padding:1.25rem 1.5rem;">
            <div style="display:flex;align-items:flex-start;gap:0.5rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:1rem;">
                <svg style="width:16px;height:16px;color:#3b82f6;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                <p style="font-size:0.78rem;color:#1e40af;margin:0;">Use the <strong>Template</strong> button to download the correct CSV format before importing.</p>
            </div>
            <div id="headProcImportDrop"
                onclick="document.getElementById('headProcImportFile').click()"
                ondragover="event.preventDefault();this.style.borderColor='#10b981';this.style.background='#f0fdf4';"
                ondragleave="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc';"
                ondrop="event.preventDefault();this.style.borderColor='#cbd5e1';this.style.background='#f8fafc';window._headProcHandleFile(event.dataTransfer.files[0]);"
                style="border:2px dashed #cbd5e1;border-radius:0.75rem;background:#f8fafc;padding:2rem 1rem;text-align:center;cursor:pointer;transition:all 0.2s;">
                <svg style="width:32px;height:32px;color:#94a3b8;margin:0 auto 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <p id="headProcDropLabel" style="font-size:0.85rem;font-weight:500;color:#475569;margin:0;">Drop CSV file here</p>
                <p style="font-size:0.75rem;color:#94a3b8;margin:0.25rem 0 0;">or click to browse</p>
                <input type="file" id="headProcImportFile" accept=".csv" style="display:none;"
                    onchange="window._headProcHandleFile(this.files[0])">
            </div>
            <div id="headProcImportPreview" style="margin-top:0.75rem;max-height:180px;overflow:auto;display:none;"></div>
            <div id="headProcImportError" style="display:none;margin-top:0.5rem;padding:0.5rem 0.75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:0.5rem;font-size:0.8rem;color:#991b1b;"></div>
        </div>
        <div style="padding:1rem 1.5rem;border-top:1px solid #f1f5f9;display:flex;gap:0.75rem;justify-content:flex-end;">
            <button onclick="document.getElementById('headProctorImportModal').remove()"
                style="padding:0.5rem 1.25rem;border:1.5px solid #e2e8f0;border-radius:0.625rem;background:white;color:#64748b;font-size:0.85rem;font-weight:600;cursor:pointer;">Cancel</button>
            <button id="headProcImportBtn" disabled
                style="padding:0.5rem 1.25rem;border:none;border-radius:0.625rem;background:#10b981;color:white;font-size:0.85rem;font-weight:700;cursor:not-allowed;opacity:0.5;transition:all 0.2s;">
                Import
            </button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

    window._headProcHandleFile = async (file) => {
        if (!file) return;
        const errEl   = document.getElementById('headProcImportError');
        const btn     = document.getElementById('headProcImportBtn');
        const label   = document.getElementById('headProcDropLabel');
        const preview = document.getElementById('headProcImportPreview');

        errEl.style.display = 'none'; errEl.textContent = '';
        btn.disabled = true; btn.style.opacity = '0.5'; btn.style.cursor = 'not-allowed';
        label.textContent = file.name;

        const text  = await file.text();
        const lines = text.trim().split('\n').filter(l => l.trim());
        if (lines.length < 2) {
            errEl.textContent = 'The CSV file appears to be empty or has no data rows.';
            errEl.style.display = 'block'; return;
        }
        const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g,''));
        const previewRows = lines.slice(1, 6);
        preview.style.display = 'block';
        preview.innerHTML = `<table style="width:100%;border-collapse:collapse;font-size:0.73rem;">
            <thead><tr style="background:#f8fafc;">${headers.map(h => `<th style="padding:4px 8px;text-align:left;border:1px solid #e2e8f0;color:#64748b;font-weight:600;white-space:nowrap;">${h}</th>`).join('')}</tr></thead>
            <tbody>${previewRows.map(l => {
                const vals = l.split(',').map(v => v.trim().replace(/^"|"$/g,''));
                return `<tr>${vals.map(v => `<td style="padding:4px 8px;border:1px solid #e2e8f0;color:#374151;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${v||'—'}</td>`).join('')}</tr>`;
            }).join('')}
            ${lines.length > 6 ? `<tr><td colspan="${headers.length}" style="padding:4px 8px;text-align:center;color:#94a3b8;border:1px solid #e2e8f0;">...and ${lines.length - 6} more rows</td></tr>` : ''}
            </tbody></table>`;

        let _importHash = null;
        try {
            const buf = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());
            _importHash = Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2,'0')).join('');
        } catch(_) {}

        window._headProcCurrentFile = file;
        window._headProcCurrentHash = _importHash;

        if (_importHash) {
            try {
                const chk = await flexamFetch('../api/import_lock.php', {
                    method: 'POST',
                    body: JSON.stringify({ action: 'check', file_hash: _importHash, import_type: 'proctors' })
                });
                if (chk && (chk.status === 'already_imported' || chk.status === 'locked')) {
                    const msg = chk.status === 'already_imported'
                        ? '⛔ This file has already been imported into the system.'
                        : '⏳ This file is currently being processed. Please wait and try again.';
                    errEl.textContent = msg; errEl.style.display = 'block'; return;
                }
            } catch(_) {}
        }

        btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer';
        btn.onclick = () => window._headProcRunImport();
    };

    window._headProcRunImport = async () => {
        const file = window._headProcCurrentFile;
        const _importHash = window._headProcCurrentHash;
        if (!file) return;

        const btn = document.getElementById('headProcImportBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Importing\u2026'; btn.style.opacity = '0.7'; }

        if (_importHash) {
            const acq = await flexamFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'acquire', file_hash: _importHash, import_type: 'proctors' })
            }).catch(() => null);
            if (!acq || !acq.success) {
                const msg = acq?.status === 'already_imported'
                    ? '⛔ This file has already been imported into the system.'
                    : '⏳ This file is currently being processed. Please wait and try again.';
                const errEl = document.getElementById('headProcImportError');
                if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
                if (btn) { btn.disabled = false; btn.textContent = 'Import'; btn.style.opacity = '1'; }
                return;
            }
        }

        document.getElementById('headProctorImportModal')?.remove();

        const text    = await file.text();
        const lines   = text.trim().split('\n');
        const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g,'').toLowerCase().replace(/ /g,'_'));
        const rows    = lines.slice(1).map(line => {
            const vals = line.split(',');
            const obj  = {};
            headers.forEach((h, i) => { obj[h] = (vals[i]||'').trim().replace(/^"|"$/g,''); });
            return obj;
        }).filter(r => Object.values(r).some(v => v));

        let success = 0, failed = 0, skipped = [];
        const _batchNames  = new Set();
        const _batchEmails = new Set();

        for (const row of rows) {
            const name   = row['name'] || '';
            const campus = (row['campus'] || '').trim();
            const email  = (row['email']  || '').trim().toLowerCase();
            if (!name) { failed++; skipped.push({ label: 'Unknown', reason: 'Missing proctor name', type: 'error' }); continue; }
            const nameKey = name.trim().toLowerCase() + '||' + campus.toLowerCase();
            const isDup = allData.some(d =>
                d.type === 'proctor' &&
                (d.name   || '').trim().toLowerCase() === name.trim().toLowerCase() &&
                (d.campus || '').trim()               === campus
            ) || _batchNames.has(nameKey);
            if (isDup) { failed++; skipped.push({ label: name, reason: `Proctor '${name}' already exists in '${campus}'.`, type: 'duplicate' }); continue; }
            if (email) {
                const isEmailDup = allData.some(d =>
                    d.type === 'proctor' &&
                    (d.email || '').trim().toLowerCase() === email
                ) || _batchEmails.has(email);
                if (isEmailDup) { failed++; skipped.push({ label: name, reason: `Email '${email}' is already registered to another proctor.`, type: 'duplicate' }); continue; }
            }
            _batchNames.add(nameKey);
            if (email) _batchEmails.add(email);
            try {
                const result = await window.flexamApi.proctors.create({
                    name, college_program: row['college/program']||row['college_program']||'',
                    email, phone: row['phone']||'', campus
                });
                if (result.success) success++;
                else { failed++; skipped.push({ label: name, reason: result.message||'Server error', type: 'error' }); }
            } catch(err) { failed++; skipped.push({ label: name, reason: err.message, type: 'error' }); }
        }

        if (_importHash) {
            await flexamFetch('../api/import_lock.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'release', file_hash: _importHash, permanent: success > 0, import_type: 'proctors' })
            }).catch(() => {});
        }

        await refreshAllData();
        showImportResults(file.name, success, failed, skipped);
    };
};

window.exportAnalytics = () => {
    const myCollege = (currentUser.college || '').trim().toUpperCase();
    // allData schedules are already campus-scoped from refreshAllData
    const allSched = allData.filter(d => d.type === 'schedule');
    const today = new Date(); today.setHours(0,0,0,0);
    let fa = allSched.filter(s => {
        if (analyticsPeriod === 'all') return true;
        const raw=s.exam_date||s.date;
        if (!raw || raw === '0000-00-00' || raw === '0000-00-00 00:00:00') return false;
        const d=new Date(raw.substring(0,10)+'T00:00:00'); d.setHours(0,0,0,0);
        if (isNaN(d.getTime())) return false;
        if (analyticsPeriod==='today') return d.getTime()===today.getTime();
        if (analyticsPeriod==='week') {
            const start=new Date(today); start.setDate(today.getDate()-today.getDay());
            const end=new Date(start);   end.setDate(start.getDate()+6);
            return d>=start && d<=end;
        }
        if (analyticsPeriod==='month') return d.getMonth()===today.getMonth() && d.getFullYear()===today.getFullYear();
        if (analyticsPeriod==='custom') {
            if (analyticsDateFrom && d<new Date(analyticsDateFrom+'T00:00:00')) return false;
            if (analyticsDateTo   && d>new Date(analyticsDateTo  +'T00:00:00')) return false;
            return true;
        }
        return true;
    });
    if (analyticsCampus!=='all') fa=fa.filter(s=>(s.campus||s.room_campus||'')===analyticsCampus);
    // Head: additionally scope by college/program
    const _expCollege = (currentUser.college || '').trim().toUpperCase();
    const _expProgram = (currentUser.program || '').trim().toUpperCase();
    if (_expCollege) {
        fa = fa.filter(s =>
            (s.college||'').trim().toUpperCase() === _expCollege ||
            (s.program||'').trim().toUpperCase() === _expCollege
        );
    }
    if (_expProgram) {
        fa = fa.filter(s => (s.program||'').trim().toUpperCase() === _expProgram);
    }
    const periodLabel=analyticsPeriod==='today'?'Today':analyticsPeriod==='week'?'This Week':analyticsPeriod==='month'?'This Month':analyticsPeriod==='custom'?`${analyticsDateFrom||'?'}_to_${analyticsDateTo||'?'}`:'All_Time';
    const campusLabel=analyticsCampus!=='all'?`_${analyticsCampus.replace(/ /g,'_')}`:'';
    const summaryRows = [
        ['--- SUMMARY ---',''],
        ['Period', periodLabel.replace(/_/g,' ')],
        ['Campus', analyticsCampus==='all'?'All Campuses':analyticsCampus],
        ['Total Schedules', fa.length],
        ['Approved', fa.filter(x=>x.status==='Approved').length],
        ['Pending',  fa.filter(x=>(x.status||'Pending')==='Pending').length],
        ['Rejected', fa.filter(x=>x.status==='Rejected').length],
        ['Midterm Exams', fa.filter(x=>x.exam_type==='Midterm').length],
        ['Final Exams',   fa.filter(x=>x.exam_type==='Final').length],
        ['Quiz',          fa.filter(x=>x.exam_type==='Quiz').length],
        ['Special',       fa.filter(x=>x.exam_type==='Special').length],
        ['',''],
        ['--- SCHEDULE DETAILS ---','','','','',''],
    ];
    const detailHeader = ['Course Code','Course Name','Exam Type','Date','Time Slot','Room','Proctor','Status'];
    const detailRows   = fa.map(s=>[s.course_code||'',s.course_name||'',s.exam_type||'',s.exam_date||s.date||'',s.time_slot||'',s.room_name||s.room||'',s.proctor_name||'',s.status||'Pending']);
    const allRows = [...summaryRows, detailHeader, ...detailRows];
    exportToCSV(['Metric','Value','','','','','',''], allRows, `analytics_${periodLabel}${campusLabel}.csv`);
};

// ─────────────────────────────────────────────────────────────────────────────
// 13. ADD COURSE MODAL
// ─────────────────────────────────────────────────────────────────────────────
window.openAddCourseModal = function() {
    if (document.getElementById('addCourseModal')) return;
    // Deduplicate colleges by code for the dropdown
    const _seenCodes = new Set();
    const colleges = allData.filter(d => {
        if (d.type !== 'college' || !d.code) return false;
        if (_seenCodes.has(d.code)) return false;
        _seenCodes.add(d.code);
        return true;
    }).sort((a, b) => a.code.localeCompare(b.code));
    const modal = document.createElement('div');
    modal.id = 'addCourseModal';
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
    <div class="modal-content">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">Add Course</h2>
            <button onclick="closeModal('addCourseModal')" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="addCourseForm" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Course Code <span class="required">*</span></label><input type="text" name="courseCode" class="form-input" placeholder="e.g., CS101" required></div>
                <div><label class="form-label">Course Name <span class="required">*</span></label><input type="text" name="courseName" class="form-input" placeholder="e.g., Introduction to Computing" required></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">College <span class="required">*</span></label>
                    <select name="college" class="form-select" required>
                        <option value="" disabled selected>Select College</option>
                        ${colleges.map(c=>`<option value="${c.code}">${c.code}${c.name ? ' — ' + c.name : ''}</option>`).join('')}
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div><label class="form-label">Program <span class="required">*</span></label><input type="text" name="program" class="form-input" placeholder="e.g., BSCS" required></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Year Level <span class="required">*</span></label>
                    <select name="yearLevel" class="form-select" required>
                        ${yearLevelOptions("")}
                    </select>
                </div>
                <div><label class="form-label">Semester <span class="required">*</span></label>
                    <select name="semester" class="form-select" required>
                        <option value="" disabled selected>Select Semester</option>
                        <option>1st Semester</option><option>2nd Semester</option><option>Summer</option>
                    </select>
                </div>
            </div>
            <div class="mb-6"><label class="form-label">Campus <span class="required">*</span></label>
                <input type="text" name="campus" class="form-input bg-slate-50 font-medium text-emerald-700" value="${currentUser.campus||''}" readonly required>
            </div>
            <div id="addCourseErr" class="hidden mb-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="flex-1 bg-emerald-600 text-white py-3 rounded-lg font-medium hover:bg-emerald-700 transition">Create Course</button>
                <button type="button" onclick="closeModal('addCourseModal')" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-lg font-medium hover:bg-slate-200 transition">Cancel</button>
            </div>
        </form>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target===modal) closeModal('addCourseModal'); });
    document.getElementById('addCourseForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd=new FormData(e.target), btn=e.target.querySelector('[type="submit"]');
        btn.disabled=true; btn.textContent='Creating...';
        const result = await window.flexamApi.courses.create({ course_code:fd.get('courseCode'), course_name:fd.get('courseName'), college:fd.get('college'), program:fd.get('program'), year_level:fd.get('yearLevel'), semester:fd.get('semester'), campus:fd.get('campus') });
        btn.disabled=false; btn.textContent='Create Course';
        if (result.success) { closeModal('addCourseModal'); showToast('Course created successfully!'); await refreshAllData(); }
        else { const err=document.getElementById('addCourseErr'); err.textContent=result.message||'Failed to create course.'; err.classList.remove('hidden'); }
    };
};

// ─────────────────────────────────────────────────────────────────────────────
// 14. ADD SCHEDULE MODAL
// ─────────────────────────────────────────────────────────────────────────────
window.openAddScheduleModal = function() {
    if (document.getElementById('addScheduleModal')) return;
    const headCampus = currentUser.campus || '';
    const courses    = allData.filter(d => d.type==='course');
    const rooms      = allData.filter(d => d.type==='room' && (!headCampus || (d.campus||'') === headCampus));
    const allProctors = allData.filter(d => d.type==='proctor' && (!headCampus || (d.campus||'') === headCampus));
    const modal = document.createElement('div');
    modal.id = 'addScheduleModal';
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
    <div class="modal-content">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">Create Exam Schedule</h2>
            <button onclick="closeModal('addScheduleModal')" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="addScheduleForm" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Course <span class="required">*</span></label>
                    <select name="course_id" id="schedCourseSelect" class="form-select" required>
                        <option value="" disabled selected>Select Course</option>
                        ${(()=>{
                            const seen = {};
                            courses.forEach(c => {
                                const key = (c.course_code||"").toLowerCase();
                                if (!seen[key]) seen[key] = { ...c, _colleges:[], _programs:[], _normProgs:[], _years:[], _sems:[] };
                                const col=(c.college||"").trim(); if(col&&!seen[key]._colleges.includes(col)) seen[key]._colleges.push(col);
                                const prg=(c.program||"").trim();
                                const normPrg=prg.replace(/\s+/g,'').toUpperCase();
                                if(normPrg&&!seen[key]._normProgs.includes(normPrg)){seen[key]._normProgs.push(normPrg);seen[key]._programs.push(prg);}
                                const yr=(c.year_level||"").trim(); if(yr&&!seen[key]._years.includes(yr)) seen[key]._years.push(yr);
                                const sem=(c.semester||"").trim(); if(sem&&!seen[key]._sems.includes(sem)) seen[key]._sems.push(sem);
                                const cn=(c.course_name||"").trim();
                                if(cn&&cn.toLowerCase()!==key&&(seen[key].course_name||"").toLowerCase()===key) seen[key].course_name=cn;
                            });
                            return Object.values(seen)
                                .sort((a,b)=>(a.course_code||"").localeCompare(b.course_code||""))
                                .map(c => '<option value="'+c.id+'"'
                                    +' data-code="'+(c.course_code||'')+'"'
                                    +' data-name="'+(c.course_name||c.course_code||'')+'"'
                                    +' data-colleges="'+encodeURIComponent(JSON.stringify(c._colleges))+'"'
                                    +' data-programs="'+encodeURIComponent(JSON.stringify(c._programs))+'"'
                                    +' data-years="'+encodeURIComponent(JSON.stringify(c._years))+'"'
                                    +' data-sems="'+encodeURIComponent(JSON.stringify(c._sems))+'"'
                                    +'>'+c.course_code+' — '+(c.course_name||c.course_code)+'</option>')
                                .join("");
                        })()}
                    </select>
                </div>
                <div><label class="form-label">College</label>
                    <div id="schedCollegeFieldWrap">
                        <input type="text" id="schedCollegeField" name="college" class="form-input bg-slate-50" placeholder="Auto-filled from course" readonly>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="form-label">Program</label>
                    <div id="schedProgramFieldWrap">
                        <input type="text" id="schedProgramField" name="program" class="form-input bg-slate-50" placeholder="Auto-filled from course" readonly>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Auto-filled when course is selected. Choose a college above to filter programs.</p>
                </div>
                <div><label class="form-label">Exam Type <span class="required">*</span></label>
                    <select name="exam_type" id="addExamTypeSelect" class="form-select" required>
                        <option value="" disabled selected>Select Type</option>
                        <option>Prelim</option><option>Midterm</option><option>Final</option><option>Summer</option><option value="Others">Others</option>
                    </select>
                    <div id="addExamTypeOtherWrap" style="display:none;margin-top:8px;">
                        <label class="form-label" style="font-size:0.75rem;margin-bottom:4px;">Please specify exam type <span class="required">*</span></label>
                        <input type="text" id="addExamTypeOther" name="exam_type_other" placeholder="e.g. Qualifying Exam, Thesis Defense..." style="width:100%;padding:0.625rem 0.875rem;border:1px solid #10b981;border-radius:0.5rem;font-size:0.875rem;color:#1e293b;background:white;box-sizing:border-box;">
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Year Level <span class="required">*</span></label>
                    <select id="schedYearLevel" name="year_level" class="form-select" required>
                        ${yearLevelOptions("")}
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Semester <span class="required">*</span></label>
                    <select name="semester" id="schedSemesterSelect" class="form-select" required>
                        <option value="" disabled selected>Select Semester</option>
                        <option>1st Semester</option><option>2nd Semester</option><option>Summer</option>
                    </select>
                </div>
                <div><label class="form-label">Section</label>
                    <input type="text" name="section" class="form-input" placeholder="1-Y1-1 e.g.">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Date <span class="required">*</span></label><input type="date" id="schedDateField" name="exam_date" class="form-input" min="${new Date().toISOString().split('T')[0]}" required></div>
                <div><label class="form-label">Duration</label>
                    <select name="duration" class="form-select" required>
                        <option>1 Hour</option><option>1.5 Hours</option><option value="2 Hours" selected>2 Hours</option><option>2.5 Hours</option><option>3 Hours</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
    <label class="form-label">Room <span class="required">*</span></label>
    <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;cursor:pointer;">
       <input type="checkbox" id="schedIsOnlineCheck" name="is_online" value="1"
    style="width:16px;height:16px;accent-color:#10b981;cursor:pointer;"
    onchange="handleHeadOnlineToggle(this.checked)">
        <span style="font-size:0.875rem;font-weight:500;color:#475569;">🌐 Online Exam <span style="font-size:0.75rem;color:#94a3b8;font-weight:400;">(no room required)</span></span>
    </label>
    <div id="schedRoomWrap">
        <select name="room_id" id="schedRoomSelect" class="form-select" required>
            <option value="" disabled selected>Select Room</option>
            ${rooms.map(r => {
                if (r.locked) {
                    const reason = r.block_reason ? ` — ${r.block_reason}` : '';
                    return `<option value="${r.id}" data-campus="${r.campus||''}" disabled style="color:#94a3b8;">🔒 ${r.name} — ${r.building}${r.campus?' ('+r.campus+')':''} [Blocked by Admin${reason}]</option>`;
                }
                return `<option value="${r.id}" data-campus="${r.campus||''}">${r.name} — ${r.building}${r.campus?' ('+r.campus+')':''} (Cap: ${r.capacity})</option>`;
            }).join('')}
        </select>
    </div>
</div>
                <div id="addProctorPickerWrap">
                    <label class="form-label">Proctor
                        <span id="schedProctorCampusTag" class="ml-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400">Select a room first</span>
                    </label>
                    <!-- Hidden select keeps form submission working -->
                    <select name="proctor_id" id="schedProctorSelect" style="display:none;">
                        <option value="">No Proctor</option>
                        ${allProctors.map(p=>`<option value="${p.id}" data-campus="${p.campus||''}">${p.name}${p.campus?' ('+p.campus+')':''}</option>`).join('')}
                    </select>
                    <!-- Checkbox picker -->
                    <div id="addProctorPicker" style="border:1.5px solid #e2e8f0;border-radius:0.5rem;background:white;overflow:hidden;">
                        <div id="addProctorPickerHeader" onclick="toggleProctorPicker('add')" style="display:flex;align-items:center;justify-content:space-between;padding:0.65rem 0.875rem;cursor:pointer;user-select:none;gap:0.5rem;">
                            <span id="addProctorPickerLabel" style="font-size:0.875rem;color:#94a3b8;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Click to select proctors…</span>
                            <svg id="addProctorPickerChevron" style="width:16px;height:16px;color:#94a3b8;flex-shrink:0;transition:transform 0.2s;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div id="addProctorPickerDropdown" style="display:none;border-top:1px solid #f1f5f9;max-height:200px;overflow-y:auto;">
                            <div style="padding:0.4rem;">
                                <input type="text" placeholder="Search proctors…" oninput="filterProctorPicker('add',this.value)" style="width:100%;padding:0.45rem 0.7rem;border:1.5px solid #e2e8f0;border-radius:0.4rem;font-size:0.8125rem;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
                            </div>
                            <div id="addProctorPickerList" style="padding:0 0.4rem 0.4rem;">
                                ${allProctors.map(p=>`
                                <label style="display:flex;align-items:center;gap:0.5rem;padding:0.45rem 0.5rem;border-radius:0.3rem;cursor:pointer;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" value="${p.id}" data-campus="${p.campus||''}" onchange="syncProctorPicker('add')" style="width:15px;height:15px;accent-color:#10b981;cursor:pointer;flex-shrink:0;">
                                    <span style="font-size:0.85rem;color:#1e293b;">${p.name}${p.campus?` <span style="color:#94a3b8;font-size:0.75rem;">(${p.campus})</span>`:''}</span>
                                </label>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="form-label">Select Time Slot <span class="required">*</span></label>
                <div id="schedTimeSlots" class="border border-slate-200 rounded-lg p-4 bg-slate-50 text-center text-sm text-slate-400">Select date and room first</div>
            </div>
            <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg mb-4">
                <p class="text-xs text-orange-700 font-medium">⏳ Schedules you submit will be sent for Admin approval before being published.</p>
            </div>
            <div id="addScheduleErr" class="hidden mb-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeModal('addScheduleModal')" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-lg font-medium hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="addScheduleSubmitBtn" class="flex-1 bg-emerald-600 text-white py-3 rounded-lg font-medium hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-400">Submit for Approval</button>
            </div>
        </form>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target===modal) closeModal('addScheduleModal'); });

    // Filter proctors by the selected room's campus — rebuild checkbox picker
    function filterProctorsByCampus() {
        const roomSel = document.getElementById('schedRoomSelect');
        const tag = document.getElementById('schedProctorCampusTag');
        const selectedOpt = roomSel.options[roomSel.selectedIndex];
        const campus = selectedOpt ? (selectedOpt.getAttribute('data-campus') || '') : '';

        const filtered = campus
            ? allProctors.filter(p => (p.campus || '') === campus)
            : allProctors;

        // Rebuild hidden select
        const hiddenSel = document.getElementById('schedProctorSelect');
        if (hiddenSel) {
            hiddenSel.innerHTML = '<option value="">No Proctor</option>' +
                filtered.map(p => `<option value="${p.id}" data-campus="${p.campus||''}">${p.name}${p.campus?' ('+p.campus+')':''}</option>`).join('');
        }

        // Rebuild checkbox list
        const pickerList = document.getElementById('addProctorPickerList');
        if (pickerList) {
            pickerList.innerHTML = filtered.map(p => `
                <label style="display:flex;align-items:center;gap:0.5rem;padding:0.45rem 0.5rem;border-radius:0.3rem;cursor:pointer;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                    <input type="checkbox" value="${p.id}" data-campus="${p.campus||''}" onchange="syncProctorPickerHead('add')" style="width:15px;height:15px;accent-color:#10b981;cursor:pointer;flex-shrink:0;">
                    <span style="font-size:0.85rem;color:#1e293b;">${p.name}${p.campus ? ' <span style=\"color:#94a3b8;font-size:0.75rem;\">('+p.campus+')</span>' : ''}</span>
                </label>`).join('');
            syncProctorPickerHead('add');
        }

        // Update campus tag
        if (campus) {
            tag.textContent = campus;
            tag.className = 'ml-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700';
        } else {
            tag.textContent = 'Select a room first';
            tag.className = 'ml-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-400';
        }
    }

    // ── Helper: rebuild program dropdown filtered by selected college ──────
    function rebuildSchedProgramField(allPrograms, selectedCollege, courseCode) {
        const progWrap = document.getElementById('schedProgramFieldWrap');
        if (!progWrap) return;

        // Map program → college from allData courses matching this course_code
        const progCollegeMap = {};
        allData.filter(d => d.type === 'course' && (d.course_code || '').toLowerCase() === (courseCode || '').toLowerCase())
            .forEach(d => {
                const prog = (d.program || '').trim();
                const col  = (d.college  || '').trim();
                if (prog) progCollegeMap[prog] = col;
            });

        // Filter to programs belonging to the selected college
        const filtered = selectedCollege
            ? allPrograms.filter(p => (progCollegeMap[p] || '').toLowerCase() === selectedCollege.toLowerCase())
            : allPrograms;

        const displayPrograms = filtered.length > 0 ? filtered : allPrograms;

        if (displayPrograms.length > 1) {
            progWrap.innerHTML = '<select id="schedProgramField" name="program" class="form-select">'
                + displayPrograms.map(p => '<option value="' + p + '">' + p + '</option>').join('')
                + '</select>';
        } else {
            progWrap.innerHTML = '<input type="text" id="schedProgramField" name="program" class="form-input bg-slate-50" value="' + (displayPrograms[0] || '') + '" placeholder="Auto-filled from course" readonly>';
        }
    }

    document.getElementById('schedCourseSelect').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        let colleges=[], programs=[], years=[], sems=[];
        try { colleges = JSON.parse(decodeURIComponent(opt.getAttribute('data-colleges')||'%5B%5D')); } catch(e){}
        try { programs = JSON.parse(decodeURIComponent(opt.getAttribute('data-programs')||'%5B%5D')); } catch(e){}
        try { years    = JSON.parse(decodeURIComponent(opt.getAttribute('data-years')   ||'%5B%5D')); } catch(e){}
        try { sems     = JSON.parse(decodeURIComponent(opt.getAttribute('data-sems')    ||'%5B%5D')); } catch(e){}

        // Extract course_code from the option text (format: "CODE — Name")
        const courseCode = opt.text.split(/\s*[—\-]\s*/)[0].trim();

        // ── College — dropdown if multiple, readonly input if single ────────
        const colWrap = document.getElementById('schedCollegeFieldWrap');
        if (colWrap) {
            if (colleges.length > 1) {
                colWrap.innerHTML = '<select id="schedCollegeField" name="college" class="form-select">'
                    + '<option value="">All Colleges</option>'
                    + colleges.map(c => '<option value="' + c + '">' + c + '</option>').join('')
                    + '</select>';

                // Wire college → program filtering
                document.getElementById('schedCollegeField').addEventListener('change', function() {
                    rebuildSchedProgramField(programs, this.value, courseCode);
                });
            } else {
                colWrap.innerHTML = '<input type="text" id="schedCollegeField" name="college" class="form-input bg-slate-50" value="' + (colleges[0] || '') + '" placeholder="Auto-filled from course" readonly>';
            }
        }

        // ── Program — initial render filtered by first college ───────────
        rebuildSchedProgramField(programs, colleges[0] || '', courseCode);

        // Year Level — do NOT auto-fill; leave for user to pick
        const yearSel = document.getElementById('schedYearLevel');
        if (yearSel) yearSel.value = '';
        // Semester — pre-select if only one
        const semSel = document.getElementById('schedSemesterSelect');
        if (semSel && sems.length === 1) semSel.value = sems[0];
        else if (semSel) semSel.value = '';
        renderTimeSlots();
    });
    document.getElementById('schedDateField').addEventListener('change', renderTimeSlots);
    document.getElementById('schedRoomSelect').addEventListener('change', function() {
        filterProctorsByCampus();
        renderTimeSlots();
    });
    document.querySelector('#addScheduleForm select[name="duration"]').addEventListener('change', renderTimeSlots);

    // ── Wire Others exam type textbox toggle ──────────────────────────────
    const _addExamTypeSel  = document.getElementById('addExamTypeSelect');
    const _addExamTypeWrap  = document.getElementById('addExamTypeOtherWrap');
    const _addExamTypeInput = document.getElementById('addExamTypeOther');
    if (_addExamTypeSel && _addExamTypeWrap && _addExamTypeInput) {
        _addExamTypeSel.addEventListener('change', function() {
            if (this.value === 'Others') {
                _addExamTypeWrap.style.display = 'block';
                _addExamTypeInput.required = true;
                _addExamTypeInput.focus();
            } else {
                _addExamTypeWrap.style.display = 'none';
                _addExamTypeInput.required = false;
                _addExamTypeInput.value = '';
            }
        });
    }

    window.handleHeadOnlineToggle = function(checked) {
    const wrap = document.getElementById('schedRoomWrap');
    if (wrap) wrap.style.display = checked ? 'none' : 'block';
    const roomSel = document.getElementById('schedRoomSelect');
    if (roomSel) roomSel.required = !checked;
    renderTimeSlots();

    const date = document.getElementById('schedDateField')?.value;
    const container = document.getElementById('schedTimeSlots');
    if (!container) return;
    if (!date) {
        container.innerHTML = '<div style="text-align:center;color:#94a3b8;font-size:0.875rem;">Select a date first</div>';
        return;
    }

    const durationStr = document.querySelector('#addScheduleForm select[name="duration"]')?.value || '2 Hours';
    const dur = parseFloat(durationStr) || 2;
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
        container.innerHTML = '<div style="text-align:center;color:#f97316;">No slots fit this duration.</div>';
        return;
    }

    container.innerHTML = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;">' + slots.map(({label, past}) =>
        `<label style="display:flex;align-items:center;gap:0.5rem;padding:0.75rem;border:1px solid #e2e8f0;border-radius:0.5rem;background:${past?'#f8fafc':'white'};cursor:${past?'not-allowed':'pointer'};opacity:${past?'0.5':'1'};">
            <input type="radio" name="time_slot" value="${label}" ${past?'disabled':''} style="accent-color:#10b981;" required>
            <span style="font-size:0.875rem;color:${past?'#94a3b8':'#1e293b'};">${label}</span>
        </label>`
    ).join('') + '</div>';
};

    // ── Live conflict detection: disable submit as soon as a duplicate is found ──
    // ── Others exam type toggle helpers ─────────────────────────────────
    window.toggleAddExamTypeOther = function(sel) {
        const wrap  = document.getElementById('addExamTypeOtherWrap');
        const input = document.getElementById('addExamTypeOther');
        if (!wrap || !input) return;
        if (sel.value === 'Others') {
            wrap.style.display = 'block';
            input.required = true;
            input.focus();
        } else {
            wrap.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    };
    window.toggleEditExamTypeOther = function(sel) {
        const wrap  = document.getElementById('editExamTypeOtherWrap');
        const input = document.getElementById('editExamTypeOther');
        if (!wrap || !input) return;
        if (sel.value === 'Others') {
            wrap.style.display = 'block';
            input.required = true;
            input.focus();
        } else {
            wrap.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    };
    function getEffectiveExamType(formId) {
        const sel = document.querySelector('#' + formId + ' select[name="exam_type"]');
        if (!sel) return '';
        if (sel.value === 'Others') {
            return (document.querySelector('#' + formId + ' input[name="exam_type_other"]')?.value || '').trim();
        }
        return sel.value;
    }
    // ─────────────────────────────────────────────────────────────────────────
    function checkAddSchedConflict() {
        const section   = (document.querySelector('#addScheduleForm input[name="section"]')?.value || '').trim().toLowerCase();
        const yearLevel = (document.getElementById('schedYearLevel')?.value || '').trim().toLowerCase();
        const date      = (document.getElementById('schedDateField')?.value || '').trim();
        const examType  = getEffectiveExamType('addScheduleForm').trim().toLowerCase();
        const courseId  = (document.querySelector('#addScheduleForm select[name="course_id"]')?.value ||
                           document.querySelector('#addScheduleForm input[name="course_id"]')?.value || '').trim();
        const timeSlot  = (document.querySelector('#addScheduleForm input[name="time_slot"]:checked')?.value || '').trim();
        const roomId    = (document.getElementById('schedRoomSelect')?.value || '').trim();
        const proctorId = (document.querySelector('#addScheduleForm select[name="proctor_id"]')?.value || '').trim();
        const submitBtn = document.getElementById('addScheduleSubmitBtn');
        const errEl     = document.getElementById('addScheduleErr');
        if (!submitBtn) return;

        const active = allData.filter(d => d.type === 'schedule' && (d.status || '') !== 'Rejected');

        // Check 1: Room conflict (same room + date + time)
        if (roomId && date && timeSlot) {
            const roomConflict = active.find(d =>
                String(d.room_id || d.room || '') === String(roomId) &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === timeSlot
            );
            if (roomConflict) {
                errEl.textContent = `This room is already booked at ${timeSlot} on ${date} (${roomConflict.course_code || '—'}).`;
                errEl.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Cannot Submit — Conflict Detected';
                return;
            }
        }

        // Check 2: Section time overlap — college-scoped
        // '1A' in Engineering and '1A' in Nursing are different student groups
        const college = (document.getElementById('schedCollegeField')?.value ||
                          document.querySelector('#addScheduleForm select[name="college"]')?.value || '').trim().toLowerCase();
        if (section && date && timeSlot && college) {
            const sectionTimeConflict = active.find(d =>
                (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === section &&
                (d.college || '').trim().toLowerCase() === college &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === timeSlot
            );
            if (sectionTimeConflict) {
                errEl.textContent = `Section "${section.toUpperCase()}" already has an exam at ${timeSlot} on ${date} (${sectionTimeConflict.course_code || '—'}). Students cannot be in two places at once.`;
                errEl.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Cannot Submit — Conflict Detected';
                return;
            }
        }

        // Check 3: Proctor conflict (same proctor, same date + time)
        if (proctorId && date && timeSlot) {
            const proctorConflict = active.find(d =>
                String(d.proctor_id || '') === String(proctorId) &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === timeSlot
            );
            if (proctorConflict) {
                errEl.textContent = `This proctor is already assigned to another exam at ${timeSlot} on ${date} (Room: ${proctorConflict.room_name || '—'}).`;
                errEl.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Cannot Submit — Conflict Detected';
                return;
            }
        }

        // Check 4: Section + course + date uniqueness (same exam twice for same section)
        if (section && yearLevel && date && examType) {
            const conflict = active.find(d =>
                (d.exam_date || d.date || '').trim() === date &&
                (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === section &&
                (d.year_level || '').trim().toLowerCase() === yearLevel &&
                (d.exam_type || '').trim().toLowerCase() === examType &&
                (courseId ? String(d.course_id || d.course || '') === String(courseId) : true)
            );
            if (conflict) {
                const fmtDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                errEl.textContent = `Section "${section.toUpperCase()}" (${conflict.year_level}) already has a ${conflict.exam_type} exam for this course scheduled on ${fmtDate}${conflict.time_slot ? ' at ' + conflict.time_slot : ''}.`;
                errEl.classList.remove('hidden');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Cannot Submit — Conflict Detected';
                return;
            }
        }

        // All clear
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit for Approval';
        errEl.classList.add('hidden');
    }
    document.getElementById('schedDateField').addEventListener('change', checkAddSchedConflict);
    document.getElementById('schedDateField').addEventListener('change', () => { renderTimeSlots(); });
    document.querySelector('#addScheduleForm select[name="duration"]')?.addEventListener('change', () => { renderTimeSlots(); });
    document.getElementById('schedRoomSelect')?.addEventListener('change', () => { renderTimeSlots(); });
    document.querySelector('#addScheduleForm select[name="exam_type"]').addEventListener('change', checkAddSchedConflict);
    document.querySelector('#addScheduleForm input[name="section"]').addEventListener('input', checkAddSchedConflict);
    document.getElementById('schedYearLevel').addEventListener('change', checkAddSchedConflict);
    document.getElementById('schedRoomSelect')?.addEventListener('change', checkAddSchedConflict);
    // Attach conflict checker to picker checkboxes (hidden select doesn't fire 'change' from JS)
    document.getElementById('addProctorPickerList')?.addEventListener('change', checkAddSchedConflict);
    // ──────────────────────────────────────────────────────────────────────────

    function generateTimeSlots(durationStr, selectedDate) {
        const dur = parseFloat(durationStr) || 1;
        const durMins = Math.round(dur * 60);
        const startTimes = [420,480,540,600,660,780,840,900,960,1020,1080,1140,1200];
        const LUNCH_START=720, LUNCH_END=780, DAY_END=1260;
        function toAMPM(m) {
            const h=Math.floor(m/60), min=m%60, ap=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
            return `${String(h12).padStart(2,'0')}:${String(min).padStart(2,'0')} ${ap}`;
        }
        const now = new Date();
        const todayStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
        const isToday = selectedDate === todayStr;
        const nowMins = isToday ? now.getHours()*60 + now.getMinutes() : 0;
        const slots=[];
        for (const start of startTimes) {
            const end=start+durMins;
            if (end>DAY_END) continue;
            if (start<LUNCH_END && end>LUNCH_START) continue;
            slots.push({ label: `${toAMPM(start)} - ${toAMPM(end)}`, past: isToday && start <= nowMins });
        }
        return slots;
    }

   function renderTimeSlots() {
    const date=document.getElementById('schedDateField').value;
    const isOnline = document.getElementById('schedIsOnlineCheck')?.checked;
    const roomId=document.getElementById('schedRoomSelect').value;
    const duration=document.querySelector('#addScheduleForm select[name="duration"]')?.value||'1 Hour';
    const container=document.getElementById('schedTimeSlots');
    if (!date || (!roomId && !isOnline)) { container.innerHTML='<div class="text-center text-sm text-slate-400">Select date and room first</div>'; return; }
        const occupied=allData.filter(d=>d.type==='schedule'&&(d.exam_date||d.date)===date&&String(d.room_id||d.room)===String(roomId)).map(d=>d.time_slot);
        const slots=generateTimeSlots(duration, date);
        if (slots.length===0) { container.innerHTML='<div class="text-center text-sm text-orange-500">No available slots fit this duration in the day.</div>'; return; }
        container.innerHTML=`<div class="grid grid-cols-2 gap-2">${slots.map(({label:slot,past})=>{
            const occ=occupied.includes(slot);
            const disabled=occ||past;
            const cls=occ?'border-red-200 bg-red-50 cursor-not-allowed':past?'border-slate-200 bg-slate-50 cursor-not-allowed opacity-50':'border-slate-200 bg-white cursor-pointer hover:border-emerald-500';
            const textCls=occ?'text-red-400 line-through':past?'text-slate-400':'text-slate-700';
            const badge=occ?'<span class="ml-auto text-xs text-red-500 font-semibold">Taken</span>':past?'<span class="ml-auto text-xs text-slate-400 font-semibold">Past</span>':'';
            return `<label class="flex items-center gap-2 p-3 border ${cls} rounded-lg transition">
                <input type="radio" name="time_slot" value="${slot}" ${disabled?'disabled':''} class="text-emerald-600" required>
                <span class="text-sm ${textCls}">${slot}</span>
                ${badge}
            </label>`;
        }).join('')}</div>`;
    }
    document.getElementById('addScheduleForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd=new FormData(e.target), btn=e.target.querySelector('[type="submit"]');
        const errEl = document.getElementById('addScheduleErr');
        errEl.classList.add('hidden');
        if (!fd.get('time_slot')) { errEl.textContent='Please select a time slot.'; errEl.classList.remove('hidden'); return; }

        btn.disabled=true; btn.textContent='Submitting...';
        const courseOpt=document.querySelector('#schedCourseSelect option:checked');
        const roomOpt=document.querySelector('#schedRoomSelect option:checked');
        const roomCampus=roomOpt?.getAttribute('data-campus')||currentUser.campus||'';
        const result=await window.flexamApi.schedules.create({ course_id:fd.get('course_id'), course_code:courseOpt?.getAttribute('data-code')||'', course_name:courseOpt?.getAttribute('data-name')||'', college:(document.getElementById('schedCollegeField')?.value||document.querySelector('#schedCollegeFieldWrap select')?.value||''), program:fd.get('program')||'', exam_type:(fd.get('exam_type')==='Others'?(fd.get('exam_type_other')||'').trim():fd.get('exam_type')), year_level:fd.get('year_level'), semester:fd.get('semester')||'', section:fd.get('section')||'', section_name:fd.get('section')||'', class_section:fd.get('section')||'', exam_date:fd.get('exam_date'), time_slot:fd.get('time_slot'), duration:fd.get('duration'), room_id: document.getElementById('schedIsOnlineCheck')?.checked ? null : fd.get('room_id'),
is_online: document.getElementById('schedIsOnlineCheck')?.checked ? 1 : 0,
proctor_id:fd.get('proctor_id')||null, campus:roomCampus, status:'Pending'});
        btn.disabled=false; btn.textContent='Submit for Approval';
        if (result.success) { 
            activityLog('Schedule Submitted', `${courseOpt?.getAttribute('data-code')||''} · ${(fd.get('exam_type')==='Others'?(fd.get('exam_type_other')||'').trim():fd.get('exam_type'))||''} · ${fd.get('exam_date')||''} · ${roomCampus}`);
            closeModal('addScheduleModal'); showToast('Schedule submitted for admin approval!'); await refreshAllData(); 
        }
        else { errEl.textContent=result.message||'Failed to submit schedule.'; errEl.classList.remove('hidden'); }
    };
};

// ─────────────────────────────────────────────────────────────────────────────
// 15. ADD PROCTOR MODAL
// ─────────────────────────────────────────────────────────────────────────────
window.openAddProctorModal = function() {
    if (document.getElementById('addProctorModal')) return;
    const modal = document.createElement('div');
    modal.id = 'addProctorModal';
    modal.className = 'modal-overlay active';
    modal.innerHTML = `
    <div class="modal-content">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">Add Proctor</h2>
            <button onclick="closeModal('addProctorModal')" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="addProctorForm" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Proctor Name <span class="required">*</span></label><input type="text" name="name" class="form-input" placeholder="e.g., Dr. John Doe" required></div>
                <div><label class="form-label">College/Program <span class="required">*</span></label><input type="text" name="college_program" class="form-input" placeholder="e.g., CCS - BSCS" required></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Email <span class="required">*</span></label><input type="email" name="email" class="form-input" placeholder="email@example.com" required></div>
                <div><label class="form-label">Phone</label><input type="tel" id="addProctorPhone" name="phone" class="form-input" placeholder="+63-912-345-6789" inputmode="numeric" maxlength="11"></div>
            </div>
            <div class="mb-6"><label class="form-label">Campus <span class="required">*</span></label>
                <input type="text" name="campus" class="form-input bg-slate-50 font-medium text-emerald-700" value="${currentUser.campus||''}" readonly required>
            </div>
            <div id="addProctorErr" class="hidden mb-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="flex-1 bg-emerald-600 text-white py-3 rounded-lg font-medium hover:bg-emerald-700 transition">Create Proctor</button>
                <button type="button" onclick="closeModal('addProctorModal')" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-lg font-medium hover:bg-slate-200 transition">Cancel</button>
            </div>
        </form>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target===modal) closeModal('addProctorModal'); });

    // ── Phone: allow only digits, +, -, spaces, and () ───────────────────────
    const phoneInput = document.getElementById('addProctorPhone');
    if (phoneInput) {
        phoneInput.addEventListener('keydown', e => {
            // Always allow control keys
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            const allowed = ['Backspace','Delete','Tab','Enter','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
            if (allowed.includes(e.key)) return;
            // Only allow: digits, +, -, space, (, )
            if (!/^[\d+\-\s()]$/.test(e.key)) e.preventDefault();
        });
        phoneInput.addEventListener('input', () => {
            // Strip any characters that slipped through (e.g. paste)
            let clean = phoneInput.value.replace(/[^\d+\-\s()]/g, '');
            // Enforce max 11 characters
            if (clean.length > 11) clean = clean.slice(0, 11);
            if (phoneInput.value !== clean) phoneInput.value = clean;
        });
    }
    // ─────────────────────────────────────────────────────────────────────────
    document.getElementById('addProctorForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd  = new FormData(e.target);
        const btn = e.target.querySelector('[type="submit"]');
        const err = document.getElementById('addProctorErr');
        err.classList.add('hidden');
        btn.disabled = true; btn.textContent = 'Creating...';
        try {
            const result = await window.flexamApi.proctors.create({
                name:            fd.get('name'),
                college_program: fd.get('college_program'),
                email:           fd.get('email'),
                phone:           fd.get('phone') || '',
                campus:          fd.get('campus')
            });
            if (result && result.success) {
                closeModal('addProctorModal');
                showToast('Proctor created successfully!');
                await refreshAllData();
            } else {
                err.textContent = (result && result.message) ? result.message : 'Failed to create proctor. Please try again.';
                err.classList.remove('hidden');
            }
        } catch (ex) {
            err.textContent = 'Network error: ' + ex.message;
            err.classList.remove('hidden');
        }
        btn.disabled = false; btn.textContent = 'Create Proctor';
    };
};

window.openFeedbackDetail = function(f) {
    if (document.getElementById('feedbackDetailModal')) document.getElementById('feedbackDetailModal').remove();
    const date = f.created_at ? new Date(f.created_at).toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) : 'Unknown date';
    const modal = document.createElement('div');
    modal.id = 'feedbackDetailModal';
    modal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-[slideUp_0.3s_ease]">
        <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-5 flex justify-between items-start">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-lg">
                    ${(f.student_name || 'A').charAt(0).toUpperCase()}
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">${f.student_name || 'Anonymous'}</h2>
                    ${f.student_number ? `<p class="text-emerald-100 text-xs">Student #: ${f.student_number}</p>` : ''}
                </div>
            </div>
            <button onclick="document.getElementById('feedbackDetailModal').remove()" class="text-white/70 hover:text-white transition p-1 rounded-lg hover:bg-white/10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex flex-wrap gap-3">
            ${f.category ? `<span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-emerald-100 text-emerald-700">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                ${f.category}
            </span>` : ''}
            ${f.college ? `<span class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1 rounded-full bg-blue-100 text-blue-700">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                ${f.college}
            </span>` : ''}
            <span class="inline-flex items-center gap-1 text-xs text-slate-500 ml-auto">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                ${date}
            </span>
        </div>
        <div class="px-6 py-5 space-y-4">
            ${f.subject ? `
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Subject</p>
                <p class="text-base font-semibold text-slate-800">${f.subject}</p>
            </div>` : ''}
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Message</p>
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4">
                    <p class="text-sm text-slate-700 leading-relaxed italic">"${f.message}"</p>
                </div>
            </div>
            ${f.exam_difficulty ? `
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Exam Difficulty</p>
                <div class="flex items-center gap-3 p-3 rounded-xl border ${f.exam_difficulty.startsWith('30') ? 'bg-amber-50 border-amber-200' : f.exam_difficulty.startsWith('50') ? 'bg-orange-50 border-orange-200' : 'bg-red-50 border-red-200'}">
                    <span class="text-2xl">${f.exam_difficulty.startsWith('30') ? '😐' : f.exam_difficulty.startsWith('50') ? '😓' : '😱'}</span>
                    <div>
                        <p class="text-sm font-bold ${f.exam_difficulty.startsWith('30') ? 'text-amber-700' : f.exam_difficulty.startsWith('50') ? 'text-orange-700' : 'text-red-700'}">${f.exam_difficulty}</p>
                        <p class="text-xs ${f.exam_difficulty.startsWith('30') ? 'text-amber-500' : f.exam_difficulty.startsWith('50') ? 'text-orange-500' : 'text-red-500'}">Student-reported difficulty level</p>
                    </div>
                </div>
            </div>` : ''}
            ${!f.is_read ? `
            <div class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full"></span>
                <p class="text-xs text-emerald-700 font-medium">This feedback is unread</p>
            </div>` : ''}
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button onclick="document.getElementById('feedbackDetailModal').remove()" 
                class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition text-sm">
                Close
            </button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
};

// ─────────────────────────────────────────────────────────────────────────────
// AUTO-RESOLVE CONFLICTS (mirrored from campus_admin)
// ─────────────────────────────────────────────────────────────────────────────

// ── Shared helpers ────────────────────────────────────────────────────────────
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
    const DAY_END = 1260;
    function toAMPM(m) {
        const h=Math.floor(m/60), min=m%60, ap=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
        return String(h12).padStart(2,'0')+':'+String(min).padStart(2,'0')+' '+ap;
    }
    return starts.filter(s => s + dur <= DAY_END).map(s => toAMPM(s) + ' - ' + toAMPM(s + dur));
}

// ── resolveRoomId ────────────────────────────────────────────────────────────
// Returns the numeric/string room ID for a schedule, even when room_id is null
// but room_name was stored (happens when CSV import saves room_name but the room
// lookup/create failed to return an id at import time).
// Falls back to matching room_name against allData rooms by name + building.
function resolveRoomId(s) {
    const direct = dbId(String(s.room_id || s.room || ''));
    if (direct) return direct;
    const rn = (s.room_name || '').trim();
    if (!rn) return '';
    // room_name is stored as "RoomCode,Building" or "RoomCode, Building"
    const parts    = rn.split(',');
    const nameKey  = parts[0].trim().toLowerCase();
    const buildKey = parts.slice(1).join(',').trim().toLowerCase();
    const matched  = allData.find(d =>
        d.type === 'room' &&
        (d.name || '').trim().toLowerCase() === nameKey &&
        (!buildKey || (d.building || '').trim().toLowerCase() === buildKey)
    );
    return matched ? dbId(String(matched.id)) : '';
}
// ── END resolveRoomId ─────────────────────────────────────────────────────────

function _arFindFix(sched, liveSchedules) {
    const origDate = sched.exam_date || sched.date || '';
    const origSlot = sched.time_slot || '';
    const campus   = (sched.campus || '').toLowerCase().trim();

    const rooms = allData.filter(d =>
        d.type === 'room' &&
        !d.locked &&
        (!campus || (d.campus || '').toLowerCase().trim() === campus)
    );

    const durSlots = sched.duration ? _arSlotsForDur(sched.duration) : [];
    const ALL_SLOTS = [
        '07:00 AM - 08:00 AM','08:00 AM - 09:00 AM','09:00 AM - 10:00 AM','10:00 AM - 11:00 AM','11:00 AM - 12:00 PM',
        '12:00 PM - 01:00 PM','01:00 PM - 02:00 PM','02:00 PM - 03:00 PM','03:00 PM - 04:00 PM','04:00 PM - 05:00 PM',
        '05:00 PM - 06:00 PM','06:00 PM - 07:00 PM','07:00 PM - 08:00 PM','08:00 PM - 09:00 PM'
    ];

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
            if (slot === origSlot && origSlot && rid === resolveRoomId(sched)) continue;
            const clash = liveSchedules.find(other =>
                dbId(String(other.id)) !== dbId(String(sched.id)) &&
                (other.exam_date || other.date) === origDate &&
                resolveRoomId(other) === rid &&
                _arOverlap(other.time_slot, slot)
            );
            if (!clash) return { room, rid: parseInt(rid, 10) || rid, slot, date: origDate, sameDay: slot === origSlot };
        }
    }
    return null;
}

function computeAnalyticsExtras(schedules) {
    // Build a stable room map so lookups are O(1) and order-independent
    const _roomMap = {};
    allData.forEach(d => { if (d.type === 'room') _roomMap[dbId(String(d.id))] = d; });

    let conflictedCount = 0;
    const campusConflicts = {}; // { campusName: count }
    schedules.forEach(s => {
        // Online exams don't need a room — never count them as conflicted
        if (s.is_online) return;
        const roomId = resolveRoomId(s);
        // No room assigned yet — not a conflict, just unscheduled; skip
        if (!roomId) return;
        const room = _roomMap[roomId];
        // Room was assigned but is now missing or locked — real conflict
        if (!room || room.locked) {
            conflictedCount++;
            const c = s.campus || 'Unknown'; campusConflicts[c] = (campusConflicts[c] || 0) + 1;
            return;
        }
        if (s.exam_date && s.time_slot) {
            const hasClash = schedules.some(other =>
                dbId(String(other.id)) !== dbId(String(s.id)) &&
                resolveRoomId(other) === roomId &&
                (other.exam_date || other.date) === (s.exam_date || s.date) &&
                _arOverlap(other.time_slot, s.time_slot)
            );
            if (hasClash) {
                conflictedCount++;
                const c = s.campus || 'Unknown'; campusConflicts[c] = (campusConflicts[c] || 0) + 1;
            }
        }
    });

    // Also count pending fixable import rows (skipped at import, never saved)
    // Filter by current campus so cross-campus imports don't pollute head stats
    const _myCampus = (currentUser.campus || '').trim();
    const pendingImportConflicts = (window._importResolvableRows || []).filter(r =>
        r.canAutoResolve && r.importRow &&
        (!_myCampus || !r.importRow.campus || (r.importRow.campus || '').trim() === _myCampus)
    ).length;
    conflictedCount += pendingImportConflicts;

    const totalDenominator  = schedules.length + pendingImportConflicts;
    const liveConflicts     = conflictedCount - pendingImportConflicts;
    const cleanCount        = schedules.length - liveConflicts;
    const autoResolvedPct   = totalDenominator > 0
        ? Math.round((cleanCount / totalDenominator) * 100)
        : 100;
    const autoResolvedLabel = conflictedCount === 0
        ? 'No conflicts detected'
        : `${conflictedCount} conflict${conflictedCount > 1 ? 's' : ''} remaining`;

    return { autoResolvedPct, autoResolvedLabel, conflictedCount, campusConflicts };
}

window.autoResolveConflicts = function() {
    const schedules = allData.filter(d => d.type === 'schedule');
    const _roomLookup = {};
    allData.forEach(d => { if (d.type === 'room') _roomLookup[dbId(String(d.id))] = d; });

    const conflicts = [];
    schedules.forEach(s => {
        if (s.is_online) return;
        const issues = [];
        const roomId = resolveRoomId(s);
        // No room assigned yet — not a conflict, just unscheduled
        if (!roomId) return;
        const room   = roomId ? _roomLookup[roomId] : undefined;

        if (!room || room.locked)
            issues.push(room && room.locked ? 'Room is blocked' : 'Room no longer exists');

        if (room && !room.locked && s.exam_date && s.time_slot) {
            const clash = schedules.find(other =>
                dbId(String(other.id)) !== dbId(String(s.id)) &&
                resolveRoomId(other) === roomId &&
                (other.exam_date || other.date) === (s.exam_date || s.date) &&
                _arOverlap(other.time_slot, s.time_slot)
            );
            if (clash) issues.push(`Double-booked with "${clash.course_name || clash.course_code || 'another exam'}"`);
        }

        if (issues.length > 0) conflicts.push({ sched: s, issues });
    });

    const modal = document.createElement('div');
    modal.className = 'modal-overlay active';
    modal.id = 'autoResolveModal';

    if (conflicts.length === 0) {
        // Check for pending fixable import rows that were skipped and never saved
        const pendingFix = (window._importResolvableRows || []).filter(r => r.canAutoResolve && r.importRow);
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
        document.body.appendChild(modal);
        return;
    }

    // Enrich conflicts with suggested fixes
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

    const esc = s => { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; };

    const rows = enriched.map(({ sched, issues, fix }) => {
        const name    = sched.course_name || sched.course_code || 'Unknown Course';
        const date    = sched.exam_date || sched.date || '—';
        const curSlot = sched.time_slot || '—';
        const curRoom = _roomLookup[dbId(String(sched.room_id || sched.room || ''))]?.name || '—';

        const issueHtml = issues.map(i =>
            `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-50 border border-red-100 text-red-600">⚠ ${esc(i)}</span>`
        ).join('');

        const suggestHtml = fix
            ? `<div class="mt-2 flex items-center gap-1.5 p-2 rounded-lg bg-emerald-50 border border-emerald-100">
                    <svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-[10px] text-emerald-700">Move to room </span>
                    <span class="font-semibold">${fix.room.building ? esc(fix.room.building) + ', ' : ''}${esc(fix.room.name)}</span>
                    &nbsp;·&nbsp; <span class="font-semibold">${esc(fix.slot)}</span>
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
                <div class="flex flex-col gap-1">${issueHtml}</div>
                ${suggestHtml}
            </td>
        </tr>`;
    }).join('');

    const resolvableCount   = enriched.filter(e => e.fix).length;
    const unresolvableCount = enriched.length - resolvableCount;

    // Also collect pending import rows so they are shown alongside live conflicts
    const pendingFix = (window._importResolvableRows || []).filter(r => r.canAutoResolve && r.importRow);

    const totalFixable = resolvableCount + pendingFix.length;

    const importRowsHtml = pendingFix.length > 0 ? `
        <div class="px-4 pt-3 pb-1">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                Unplaced import rows (${pendingFix.length}) — skipped at import, not yet saved
            </p>
            <div class="space-y-1.5">
                ${pendingFix.map((r, i) => `
                <div class="flex items-start gap-3 p-2.5 rounded-xl bg-purple-50 border border-purple-100">
                    <div class="w-5 h-5 bg-purple-200 text-purple-700 rounded-full flex items-center justify-center shrink-0 mt-0.5 text-[10px] font-bold">${i + 1}</div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">${r.label || ('Row ' + (i + 1))}</p>
                        <p class="text-xs text-purple-700 mt-0.5">${r.reason || ''}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 bg-purple-100 text-purple-700">&#9889; Fixable</span>
                </div>`).join('')}
            </div>
        </div>` : '';

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
                        ${conflicts.length} schedule conflict${conflicts.length > 1 ? 's' : ''}${pendingFix.length > 0 ? ` &nbsp;+&nbsp; <span class="text-purple-600 font-semibold">${pendingFix.length} unplaced import row${pendingFix.length > 1 ? 's' : ''}</span>` : ''}
                        &nbsp;·&nbsp;
                        <span class="text-purple-600 font-semibold">${totalFixable} can be auto-fixed</span>
                        ${unresolvableCount > 0 ? `&nbsp;·&nbsp;<span class="text-amber-600 font-semibold">${unresolvableCount} need manual fix</span>` : ''}
                    </p>
                </div>
            </div>
            <button onclick="document.getElementById('autoResolveModal').remove()" class="text-slate-400 hover:text-slate-600 p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="overflow-y-auto" style="max-height:420px">
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
            ${importRowsHtml}
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-slate-400">Suggested fixes reassign to a different room on the <strong>same day &amp; same time</strong>. Manual-fix items are skipped.</p>
            <div class="flex gap-2 shrink-0">
                <button onclick="document.getElementById('autoResolveModal').remove()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 border border-slate-200 rounded-lg bg-white transition">Cancel</button>
                ${totalFixable > 0 ? `
                <button id="confirmAutoResolveBtn" onclick="runAutoResolve()" class="px-5 py-2 text-sm font-semibold bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-width="2"/></svg>
                    Apply ${totalFixable} Fix${totalFixable > 1 ? 'es' : ''}
                </button>` : ''}
            </div>
        </div>
    </div>`;
    document.body.appendChild(modal);
    window._pendingConflicts = enriched;
    window._pendingImportFix = pendingFix;
};

window.runAutoResolve = async function() {
    const enriched   = (window._pendingConflicts || []).filter(e => e.fix);
    const importRows = window._pendingImportFix || [];
    if (!enriched.length && !importRows.length) return;

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

    if (failed === 0 && fixed > 0) {
        showToast(`✓ Applied ${fixed} fix${fixed !== 1 ? 'es' : ''} successfully!`, 'success');
    } else if (fixed > 0) {
        showToast(`Applied ${fixed}, ${failed} failed to save`, 'success');
    } else if (failed > 0) {
        showToast(`${failed} fix${failed !== 1 ? 'es' : ''} failed to save`, 'error');
    }

    // Now handle import rows — delegate to the existing handler
    if (importRows.length > 0 && typeof autoResolveImportConflicts === 'function') {
        autoResolveImportConflicts(importRows);
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
// HELPERS FOR VIEW SCHEDULES (mirrored from admin)
// ─────────────────────────────────────────────────────────────────────────────
function dbId(id) { if (!id) return id; return String(id).replace(/^[a-z]+_/, ''); }

function findById(type, rawId) {
    const strId = String(rawId);
    return allData.find(d =>
        d.type === type &&
        (String(d.id) === strId || dbId(String(d.id)) === strId || String(d.id) === dbId(strId))
    );
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function resolveToCollegeCode(value) {
    if (!value || !value.trim()) return '';
    const v = value.trim().toLowerCase();
    const byCode = allData.find(d => d.type === 'college' && (d.code || '').toLowerCase() === v);
    if (byCode) return byCode.code;
    const byProgram = allData.find(d =>
        d.type === 'college' && Array.isArray(d.programs) &&
        d.programs.some(p => { const a = (p.includes('=') ? p.split('=')[0] : p).trim().toLowerCase(); return a === v; })
    );
    if (byProgram) return byProgram.code;
    return value.trim();
}

function resolveCollegeAcronym(sched) {
    function isValid(v) { return v && v.trim() !== '' && v.trim().toUpperCase() !== 'UNKNOWN'; }
    if (isValid(sched.college)) return resolveToCollegeCode(sched.college.trim()) || sched.college.trim();
    const schedCourseId = sched.course_id ? dbId(String(sched.course_id)) : null;
    let course = null;
    if (schedCourseId) {
        course = allData.find(d => d.type === 'course' &&
            (dbId(String(d.id)) === schedCourseId || String(d.id) === schedCourseId));
    }
    if (!course) {
        const code = (sched.course_code || sched.course_name || '').trim().toLowerCase();
        if (code) course = allData.find(d => d.type === 'course' && (d.course_code || '').toLowerCase() === code);
    }
    if (course) {
        if (isValid(course.college)) return resolveToCollegeCode(course.college.trim()) || course.college.trim();
        if (isValid(course.program)) return resolveToCollegeCode(course.program.trim()) || course.program.trim();
    }
    if (isValid(sched.program)) return resolveToCollegeCode(sched.program.trim()) || sched.program.trim();
    return '—';
}

function resolveScheduleDisplay(sched) {
    let courseName = sched.course_code || sched.course_name || sched.course || '';
    let semester = sched.semester || '';
    if (sched.course_id) {
        const c = findById('course', sched.course_id);
        if (c) { if (!courseName) courseName = c.course_code || c.course_name || ''; if (!semester) semester = c.semester || ''; }
    }
   let roomName = '';
    if (sched.is_online) {
        roomName = 'Online';
    } else {
        const _roomLookup = findById('room', sched.room_id);
        if (_roomLookup) {
            roomName = _roomLookup.building ? _roomLookup.building + ', ' + _roomLookup.name : _roomLookup.name;
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
    let examDate = '';
    const rawDateVal = sched.exam_date || sched.date || '';
    if (rawDateVal && rawDateVal !== '0000-00-00' && rawDateVal !== '0000-00-00 00:00:00') {
        try {
            const d = new Date(rawDateVal.includes('T') ? rawDateVal : rawDateVal.substring(0,10) + 'T00:00:00');
            if (!isNaN(d.getTime())) examDate = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        } catch(e) {}
    }
    let campus = sched.campus || sched.campus_name || '';
    if (!campus && sched.room_id) { const r = findById('room', sched.room_id); if (r) campus = r.campus || ''; }
    return { courseName, semester, roomName, proctorName, examDate, campus };
}

function renderCampusTabs(view, filterFn, items) {
    const active = campusFilters[view] || '';
    const stripId = 'campus-tab-strip-' + view;
    const counts = { '': items.length };
    CAMPUSES.forEach(c => { counts[c] = items.filter(i => (i.campus || i.room_campus || '') === c).length; });
    const tabs = [{ value: '', label: 'All Campuses' }, ...CAMPUSES.map(c => ({ value: c, label: c }))];
    return '<div class="campus-tab-strip" id="' + stripId + '">'
        + '<span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-2 shrink-0">Campus</span>'
        + tabs.map(tab => {
            const count = counts[tab.value] ?? 0;
            const isActive = tab.value === active;
            return '<button class="campus-tab ' + (isActive ? 'active' : '') + '"'
                + ' onclick="campusFilters[\'' + view + '\']=\'' + tab.value + '\';document.querySelectorAll(\'#' + stripId + ' .campus-tab\').forEach(b=>b.classList.remove(\'active\'));this.classList.add(\'active\');' + filterFn + '"'
                + ' type="button">' + tab.label + '<span class="tab-count">' + count + '</span></button>';
        }).join('')
        + '</div>';
}

function viewSchedFilter() {
    const search   = (document.getElementById('viewSchedSearch')?.value || '').toLowerCase();
    const college  = document.getElementById('viewSchedCollege')?.value || '';
    const semester = document.getElementById('viewSchedSemester')?.value || '';
    const course   = (document.getElementById('viewSchedCourse')?.value || '').toLowerCase();
    const type     = document.getElementById('viewSchedType')?.value || '';
    const year     = document.getElementById('viewSchedYear')?.value || '';
    const status   = document.getElementById('viewSchedStatus')?.value || '';
    const rows = Array.from(document.querySelectorAll('#viewSchedBody tr'));

    // Determine which rows match the current filters
    const matchedRows = rows.filter(row => {
        const rowSearch   = (row.getAttribute('data-search')      || '').toLowerCase();
        const rowCollege  =  row.getAttribute('data-college-row') || '';
        const rowStatus   =  row.getAttribute('data-status-row')  || '';
        const rowType     =  row.getAttribute('data-type-row')    || '';
        const rowSemester =  row.getAttribute('data-semester-row')|| '';
        const rowYear     =  row.getAttribute('data-year-row')    || '';
        const rowCourse   = (row.getAttribute('data-course-row')  || '').toLowerCase();
        return (!search   || rowSearch.includes(search))
            && (!college  || rowCollege  === college)
            && (!semester || rowSemester === semester)
            && (!course   || rowCourse   === course)
            && (!type     || rowType     === type.toLowerCase())
            && (!year     || rowYear     === year)
            && (!status   || rowStatus   === status);
    });

    // Clamp page
    const totalPages = Math.max(1, Math.ceil(matchedRows.length / PAGE_SIZE));
    if (vsCurrentPage > totalPages) vsCurrentPage = 1;
    const pageStart = (vsCurrentPage - 1) * PAGE_SIZE;
    const pageEnd   = pageStart + PAGE_SIZE;

    // Show only current page rows
    rows.forEach(row => row.style.display = 'none');
    matchedRows.slice(pageStart, pageEnd).forEach(row => row.style.display = '');

    // Count badge
    const badge = document.getElementById('viewSchedCount');
    if (badge) badge.textContent = matchedRows.length === rows.length
        ? rows.length + ' records'
        : matchedRows.length + ' of ' + rows.length + ' records';

    // Pagination controls
    renderVsPagination(matchedRows.length, totalPages);
}

function renderVsPagination(total, totalPages) {
    const info = document.getElementById('vsPaginationInfo');
    const btns = document.getElementById('vsPaginationBtns');
    if (!info || !btns) return;
    const pageStart = (vsCurrentPage - 1) * PAGE_SIZE + 1;
    const pageEnd   = Math.min(vsCurrentPage * PAGE_SIZE, total);
    info.textContent = total === 0 ? 'No records' : `Showing ${pageStart}–${pageEnd} of ${total}`;
    btns.innerHTML   = buildPaginationButtons(vsCurrentPage, totalPages, 'vs');
}

function renderSmPagination(total, totalPages) {
    const info = document.getElementById('smPaginationInfo');
    const btns = document.getElementById('smPaginationBtns');
    if (!info || !btns) return;
    const pageStart = (smCurrentPage - 1) * PAGE_SIZE + 1;
    const pageEnd   = Math.min(smCurrentPage * PAGE_SIZE, total);
    info.textContent = total === 0 ? 'No records' : `Showing ${pageStart}–${pageEnd} of ${total}`;
    btns.innerHTML   = buildPaginationButtons(smCurrentPage, totalPages, 'sm');
}

function buildPaginationButtons(current, totalPages, prefix) {
    if (totalPages <= 1) return '';
    const base     = 'min-w-[32px] h-8 px-2 rounded-lg text-xs font-semibold transition border ';
    const active   = base + 'bg-emerald-600 text-white border-emerald-600 shadow-sm';
    const inactive = base + 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 cursor-pointer';
    const disabled = base + 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed';
    let html = '';
    html += current > 1
        ? `<button onclick="goPage('${prefix}',${current-1})" class="${inactive}">‹</button>`
        : `<button class="${disabled}" disabled>‹</button>`;
    // Page numbers with ellipsis
    const pages = [];
    if (totalPages <= 7) {
        for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 4) pages.push('…');
        for (let i = Math.max(2, current - 2); i <= Math.min(totalPages - 1, current + 2); i++) pages.push(i);
        if (current < totalPages - 3) pages.push('…');
        pages.push(totalPages);
    }
    pages.forEach(p => {
        if (p === '…') {
            html += `<span class="min-w-[32px] h-8 flex items-center justify-center text-xs text-slate-400">…</span>`;
        } else {
            html += `<button onclick="goPage('${prefix}',${p})" class="${p === current ? active : inactive}">${p}</button>`;
        }
    });
    html += current < totalPages
        ? `<button onclick="goPage('${prefix}',${current+1})" class="${inactive}">›</button>`
        : `<button class="${disabled}" disabled>›</button>`;
    return html;
}

function goPage(prefix, page) {
    if (prefix === 'vs') { vsCurrentPage = page; viewSchedFilter(); }
    else if (prefix === 'sm') { smCurrentPage = page; renderApp(); }
}

function viewSchedClearFilters() {
    ['viewSchedSearch','viewSchedCollege','viewSchedSemester','viewSchedCourse','viewSchedType','viewSchedYear','viewSchedStatus'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    vsCurrentPage = 1;
    renderApp();
}

// ─────────────────────────────────────────────────────────────────────────────
// EDIT & DELETE SCHEDULE (Schedule Management)
// ─────────────────────────────────────────────────────────────────────────────
window.editSchedule = function(scheduleId) {
    const sched = allData.find(d => d.type === 'schedule' && (String(d.id) === String(scheduleId) || dbId(String(d.id)) === String(scheduleId)));
    if (!sched) { showToast('Schedule not found', 'error'); return; }
    if (document.getElementById('editScheduleModal')) return;

    const courses    = allData.filter(d => d.type === 'course');
    const rooms      = allData.filter(d => d.type === 'room');
    const proctors   = allData.filter(d => d.type === 'proctor');
    const currentRoomId = String(sched.room_id || sched.room || '');

    const modal = document.createElement('div');
    modal.id = 'editScheduleModal';
    modal.className = 'fixed inset-0 bg-black/50 z-50 overflow-y-auto';
    modal.innerHTML = `
    <div class="min-h-screen px-4 py-8 flex items-start justify-center">
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
        <div class="flex justify-between items-center px-8 py-5 border-b border-slate-200">
            <h2 class="text-xl font-bold text-slate-900">Edit Schedule</h2>
            <button type="button" onclick="closeEditScheduleModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editScheduleForm" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Course <span class="required">*</span></label>
                    <select name="course_id" id="editSchedCourseSelect" class="form-select" required>
                        <option value="" disabled>Select Course</option>
                        ${courses.map(c => `<option value="${c.id}" data-college="${c.college||''}" ${String(sched.course_id||'')=== String(c.id)?'selected':''}>${c.course_code} — ${c.course_name}</option>`).join('')}
                    </select>
                </div>
                <div><label class="form-label">College</label>
                    <input type="text" id="editSchedCollegeField" name="college" value="${esc(sched.college||'')}" class="form-input bg-slate-50" placeholder="Auto-filled" readonly>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Exam Type <span class="required">*</span></label>
                    <select name="exam_type" id="editExamTypeSelect" class="form-select" required>
                        ${['Prelim','Midterm','Final','Summer'].map(t=>`<option ${(sched.exam_type||sched.examType)===t?'selected':''}>${t}</option>`).join('')}
                        <option value="Others" ${!['Prelim','Midterm','Final','Summer'].includes(sched.exam_type||sched.examType) && (sched.exam_type||sched.examType) ? 'selected' : ''}>Others</option>
                    </select>
                    <div id="editExamTypeOtherWrap" style="${!['Prelim','Midterm','Final','Summer'].includes(sched.exam_type||sched.examType) && (sched.exam_type||sched.examType) ? 'display:block' : 'display:none'};margin-top:8px;">
                        <label class="form-label" style="font-size:0.75rem;margin-bottom:4px;">Please specify exam type <span class="required">*</span></label>
                        <input type="text" id="editExamTypeOther" name="exam_type_other" placeholder="e.g. Qualifying Exam, Thesis Defense..." value="${!['Prelim','Midterm','Final','Summer'].includes(sched.exam_type||sched.examType) && (sched.exam_type||sched.examType) ? esc(sched.exam_type||sched.examType) : ''}" style="width:100%;padding:0.625rem 0.875rem;border:1px solid #10b981;border-radius:0.5rem;font-size:0.875rem;color:#1e293b;background:white;box-sizing:border-box;">
                    </div>
                </div>
                <div><label class="form-label">Semester <span class="required">*</span></label>
                    <select name="semester" class="form-select" required>
                        <option value="" disabled ${!sched.semester?'selected':''}>Select Semester</option>
                        ${['1st Semester','2nd Semester','Summer'].map(s=>`<option ${sched.semester===s?'selected':''}>${s}</option>`).join('')}
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Year Level <span class="required">*</span></label>
                    <select name="year_level" class="form-select" required>
                        ${YEAR_LEVELS.map(y=>`<option ${sched.year_level===y?"selected":""}>${y}</option>`).join("")}
                    </select>
                </div>
                <div><label class="form-label">Section</label>
                    <input type="text" name="section" value="${esc(sched.section||sched.section_name||sched.class_section||'')}" placeholder="e.g., A, B, IT-3A" class="form-input">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Date <span class="required">*</span></label>
                    <input type="date" id="editSchedDateField" name="exam_date" value="${sched.exam_date||sched.date||''}" class="form-input" min="${new Date().toISOString().split('T')[0]}" required>
                </div>
                <div><label class="form-label">Duration</label>
                    <select name="duration" class="form-select" required>
                        ${['1 Hour','1.5 Hours','2 Hours','2.5 Hours','3 Hours'].map(d=>`<option ${sched.duration===d?'selected':''}>${d}</option>`).join('')}
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label class="form-label">Room <span class="required">*</span></label>
    <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;cursor:pointer;">
        <input type="checkbox" id="editSchedIsOnlineCheck" name="is_online" value="1"
            ${sched.is_online ? 'checked' : ''}
            style="width:16px;height:16px;accent-color:#10b981;cursor:pointer;"
            onchange="
                const checked = this.checked;
                const wrap = document.getElementById('editSchedRoomWrap');
                if (wrap) wrap.style.display = checked ? 'none' : 'block';
                const sel = document.getElementById('editSchedRoomSelect');
                if (sel) sel.required = !checked;
            ">
        <span style="font-size:0.875rem;font-weight:500;color:#475569;">🌐 Online Exam <span style="font-size:0.75rem;color:#94a3b8;font-weight:400;">(no room required)</span></span>
    </label>
    <div id="editSchedRoomWrap" ${sched.is_online ? 'style="display:none"' : ''}>
        <select name="room_id" id="editSchedRoomSelect" class="form-select" ${sched.is_online ? '' : 'required'}>
            <option value="" disabled>Select Room</option>
            ${rooms.map(r => {
                const isCurrent = String(r.id) === currentRoomId;
                if (r.locked) {
                    const reason = r.block_reason ? ` — ${r.block_reason}` : '';
                    return `<option value="${r.id}" data-campus="${r.campus||''}" ${isCurrent?'selected':''} disabled style="color:#94a3b8;">🔒 ${r.name} — ${r.building}${r.campus?' ('+r.campus+')':''} [Blocked by Admin${reason}]</option>`;
                }
                return `<option value="${r.id}" data-campus="${r.campus||''}" ${isCurrent?'selected':''}>${r.name} — ${r.building}${r.campus?' ('+r.campus+')':''}</option>`;
            }).join('')}
        </select>
    </div>
</div>
                <div id="editProctorPickerWrap">
                    <label class="form-label">Proctor</label>
                    <!-- Hidden select keeps form submission working -->
                    <select name="proctor_id" id="editSchedProctorSelect" style="display:none;">
                        <option value="">No Proctor</option>
                        ${proctors.map(p=>`<option value="${p.id}" ${String(sched.proctor_id||'')===String(p.id)?'selected':''}>${p.name}${p.campus?' ('+p.campus+')':''}</option>`).join('')}
                    </select>
                    <!-- Checkbox picker -->
                    <div id="editProctorPicker" style="border:1.5px solid #e2e8f0;border-radius:0.5rem;background:white;overflow:hidden;">
                        <div id="editProctorPickerHeader" onclick="toggleProctorPicker('edit')" style="display:flex;align-items:center;justify-content:space-between;padding:0.65rem 0.875rem;cursor:pointer;user-select:none;gap:0.5rem;">
                            <span id="editProctorPickerLabel" style="font-size:0.875rem;color:#94a3b8;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Click to select proctors…</span>
                            <svg id="editProctorPickerChevron" style="width:16px;height:16px;color:#94a3b8;flex-shrink:0;transition:transform 0.2s;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div id="editProctorPickerDropdown" style="display:none;border-top:1px solid #f1f5f9;max-height:200px;overflow-y:auto;">
                            <div style="padding:0.4rem;">
                                <input type="text" placeholder="Search proctors…" oninput="filterProctorPicker('edit',this.value)" style="width:100%;padding:0.45rem 0.7rem;border:1.5px solid #e2e8f0;border-radius:0.4rem;font-size:0.8125rem;outline:none;box-sizing:border-box;" onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e2e8f0'">
                            </div>
                            <div id="editProctorPickerList" style="padding:0 0.4rem 0.4rem;">
                                ${proctors.map(p=>`
                                <label style="display:flex;align-items:center;gap:0.5rem;padding:0.45rem 0.5rem;border-radius:0.3rem;cursor:pointer;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" value="${p.id}" ${String(sched.proctor_id||'')===String(p.id)?'checked':''} onchange="syncProctorPicker('edit')" style="width:15px;height:15px;accent-color:#10b981;cursor:pointer;flex-shrink:0;">
                                    <span style="font-size:0.85rem;color:#1e293b;">${p.name}${p.campus?` <span style="color:#94a3b8;font-size:0.75rem;">(${p.campus})</span>`:''}</span>
                                </label>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="form-label">Select Time Slot <span class="required">*</span></label>
                <div id="editSchedTimeSlots" class="border border-slate-200 rounded-lg p-4 bg-slate-50 text-center text-sm text-slate-400">Loading slots…</div>
            </div>
            <div id="editScheduleErr" class="hidden mb-3 text-red-600 text-sm bg-red-50 px-3 py-2 rounded-lg"></div>
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeEditScheduleModal()" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-lg font-medium hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="editScheduleBtn" class="flex-1 bg-emerald-600 text-white py-3 rounded-lg font-medium hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-slate-400">Save Changes</button>
            </div>
        </form>
    </div></div>`;
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) closeEditScheduleModal(); });

    document.getElementById('editSchedCourseSelect').addEventListener('change', function() {
        document.getElementById('editSchedCollegeField').value = this.options[this.selectedIndex].getAttribute('data-college') || '';
    });

    function generateEditTimeSlots(durationStr, selectedDate) {
        const dur = parseFloat(durationStr) || 1;
        const durMins = Math.round(dur * 60);
        const startTimes = [420,480,540,600,660,780,840,900,960,1020,1080,1140,1200];
        const LUNCH_START=720, LUNCH_END=780, DAY_END=1260;
        function toAMPM(m) {
            const h=Math.floor(m/60), min=m%60, ap=h>=12?'PM':'AM', h12=h>12?h-12:h===0?12:h;
            return `${String(h12).padStart(2,'0')}:${String(min).padStart(2,'0')} ${ap}`;
        }
        const now = new Date();
        const todayStr = now.getFullYear()+'-'+String(now.getMonth()+1).padStart(2,'0')+'-'+String(now.getDate()).padStart(2,'0');
        const isToday = selectedDate === todayStr;
        const nowMins = isToday ? now.getHours()*60 + now.getMinutes() : 0;
        const slots=[];
        for (const start of startTimes) {
            const end = start + durMins;
            if (end > DAY_END) continue;
            slots.push({ label: `${toAMPM(start)} - ${toAMPM(end)}`, past: isToday && start <= nowMins });
        }
        return slots;
    }

    function renderEditTimeSlots() {
        const date   = document.getElementById('editSchedDateField').value;
        const roomId = document.getElementById('editSchedRoomSelect').value;
        const duration = document.querySelector('#editScheduleForm select[name="duration"]')?.value || '1 Hour';
        const container = document.getElementById('editSchedTimeSlots');
        const currentTimeSlot = sched.time_slot || '';
        if (!date || !roomId) { container.innerHTML = '<div class="text-center text-sm text-slate-400">Select date and room first</div>'; return; }
        const occupied = allData.filter(d =>
            d.type === 'schedule' &&
            String(d.id) !== String(sched.id) &&
            (d.exam_date||d.date) === date &&
            String(d.room_id||d.room) === String(roomId)
        ).map(d => d.time_slot);
        const slots = generateEditTimeSlots(duration, date);
        if (!slots.length) { container.innerHTML = '<div class="text-center text-sm text-orange-500">No slots fit this duration.</div>'; return; }
        container.innerHTML = `<div class="grid grid-cols-2 gap-2">${slots.map(({label:slot,past}) => {
            const occ     = occupied.includes(slot);
            const current = slot === currentTimeSlot;
            return `<label class="flex items-center gap-2 p-3 border ${occ&&!current?'border-red-200 bg-red-50 cursor-not-allowed':current?'border-emerald-400 bg-emerald-50':past?'border-slate-200 bg-slate-50 cursor-not-allowed opacity-50':'border-slate-200 bg-white cursor-pointer hover:border-emerald-500'} rounded-lg transition">
                <input type="radio" name="time_slot" value="${slot}" ${(occ&&!current)||(past&&!current)?'disabled':''} ${current?'checked':''} class="text-emerald-600" required>
                <span class="text-sm ${occ&&!current?'text-red-400 line-through':past&&!current?'text-slate-400':'text-slate-700'}">${slot}</span>
                ${occ&&!current?'<span class="ml-auto text-xs text-red-500 font-semibold">Taken</span>':past&&!current?'<span class="ml-auto text-xs text-slate-400 font-semibold">Past</span>':''}
                ${current?'<span class="ml-auto text-xs text-emerald-600 font-semibold">Current</span>':''}
            </label>`;
        }).join('')}</div>`;
    }

    document.getElementById('editSchedDateField').addEventListener('change', renderEditTimeSlots);
    document.getElementById('editSchedRoomSelect').addEventListener('change', renderEditTimeSlots);
    document.querySelector('#editScheduleForm select[name="duration"]').addEventListener('change', renderEditTimeSlots);

    // ── Wire Others exam type textbox toggle (edit modal) ─────────────────
    const _editExamTypeSel   = document.getElementById('editExamTypeSelect');
    const _editExamTypeWrap  = document.getElementById('editExamTypeOtherWrap');
    const _editExamTypeInput = document.getElementById('editExamTypeOther');
    if (_editExamTypeSel && _editExamTypeWrap && _editExamTypeInput) {
        _editExamTypeSel.addEventListener('change', function() {
            if (this.value === 'Others') {
                _editExamTypeWrap.style.display = 'block';
                _editExamTypeInput.required = true;
                _editExamTypeInput.focus();
            } else {
                _editExamTypeWrap.style.display = 'none';
                _editExamTypeInput.required = false;
                _editExamTypeInput.value = '';
            }
        });
    }
    setTimeout(renderEditTimeSlots, 50);

    // ── Live conflict detection for edit form ─────────────────────────────────
    function checkEditSchedConflict() {
        const section   = (document.querySelector('#editScheduleForm input[name="section"]')?.value || '').trim().toLowerCase();
        const yearLevel = (document.querySelector('#editScheduleForm select[name="year_level"]')?.value || '').trim().toLowerCase();
        const date      = (document.getElementById('editSchedDateField')?.value || '').trim();
        const examType  = getEffectiveExamType('editScheduleForm').trim().toLowerCase();
        const courseId  = (document.querySelector('#editScheduleForm select[name="course_id"]')?.value ||
                           document.querySelector('#editScheduleForm input[name="course_id"]')?.value || '').trim();
        const timeSlot  = (document.querySelector('#editScheduleForm input[name="time_slot"]:checked')?.value || '').trim();
        const roomId    = (document.querySelector('#editScheduleForm select[name="room_id"]')?.value || '').trim();
        const proctorId = (document.querySelector('#editScheduleForm select[name="proctor_id"]')?.value || '').trim();
        const saveBtn   = document.getElementById('editScheduleBtn');
        const errEl     = document.getElementById('editScheduleErr');
        if (!saveBtn) return;

        const active = allData.filter(d =>
            d.type === 'schedule' &&
            (d.status || '') !== 'Rejected' &&
            String(d.id) !== String(sched.id)   // exclude self
        );

        // Check 1: Room conflict (same room + date + time)
        if (roomId && date && timeSlot) {
            const roomConflict = active.find(d =>
                String(d.room_id || d.room || '') === String(roomId) &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === timeSlot
            );
            if (roomConflict) {
                errEl.textContent = `This room is already booked at ${timeSlot} on ${date} (${roomConflict.course_code || '—'}).`;
                errEl.classList.remove('hidden');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Cannot Save — Conflict Detected';
                return;
            }
        }

        // Check 2: Section time overlap — college-scoped
        // '1A' in Engineering and '1A' in Nursing are different student groups
        const college = (document.getElementById('editSchedCollegeField')?.value ||
                          document.querySelector('#editScheduleForm select[name="college"]')?.value || '').trim().toLowerCase();
        if (section && date && timeSlot && college) {
            const sectionTimeConflict = active.find(d =>
                (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === section &&
                (d.college || '').trim().toLowerCase() === college &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === timeSlot
            );
            if (sectionTimeConflict) {
                errEl.textContent = `Section "${section.toUpperCase()}" already has an exam at ${timeSlot} on ${date} (${sectionTimeConflict.course_code || '—'}). Students cannot be in two places at once.`;
                errEl.classList.remove('hidden');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Cannot Save — Conflict Detected';
                return;
            }
        }

        // Check 3: Proctor conflict (same proctor, same date + time)
        if (proctorId && date && timeSlot) {
            const proctorConflict = active.find(d =>
                String(d.proctor_id || '') === String(proctorId) &&
                (d.exam_date || d.date || '') === date &&
                (d.time_slot || '') === timeSlot
            );
            if (proctorConflict) {
                errEl.textContent = `This proctor is already assigned to another exam at ${timeSlot} on ${date} (Room: ${proctorConflict.room_name || '—'}).`;
                errEl.classList.remove('hidden');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Cannot Save — Conflict Detected';
                return;
            }
        }

        // Check 4: Section + course + date uniqueness (same exam twice for same section)
        if (section && yearLevel && date && examType) {
            const conflict = active.find(d =>
                (d.exam_date || d.date || '').trim() === date &&
                (d.section || d.section_name || d.class_section || '').trim().toLowerCase() === section &&
                (d.year_level || '').trim().toLowerCase() === yearLevel &&
                (d.exam_type || '').trim().toLowerCase() === examType &&
                (courseId ? String(d.course_id || d.course || '') === String(courseId) : true)
            );
            if (conflict) {
                const fmtDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                errEl.textContent = `Section "${section.toUpperCase()}" (${conflict.year_level}) already has a ${conflict.exam_type} exam for this course scheduled on ${fmtDate}${conflict.time_slot ? ' at ' + conflict.time_slot : ''}.`;
                errEl.classList.remove('hidden');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Cannot Save — Conflict Detected';
                return;
            }
        }

        // All clear
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
        errEl.classList.add('hidden');
    }
    document.getElementById('editSchedDateField').addEventListener('change', checkEditSchedConflict);
    document.querySelector('#editScheduleForm select[name="exam_type"]').addEventListener('change', checkEditSchedConflict);
    document.querySelector('#editScheduleForm input[name="section"]').addEventListener('input', checkEditSchedConflict);
    document.querySelector('#editScheduleForm select[name="year_level"]').addEventListener('change', checkEditSchedConflict);
    document.querySelector('#editScheduleForm select[name="room_id"]')?.addEventListener('change', checkEditSchedConflict);
    // Attach conflict checker to picker checkboxes
    document.getElementById('editProctorPickerList')?.addEventListener('change', checkEditSchedConflict);
    setTimeout(checkEditSchedConflict, 60); // run once on open to catch pre-filled conflicts
    // ──────────────────────────────────────────────────────────────────────────

    document.getElementById('editScheduleForm').onsubmit = async (e) => {
        e.preventDefault();
        const fd  = new FormData(e.target);
        const btn = document.getElementById('editScheduleBtn');
        const errEl = document.getElementById('editScheduleErr');
        errEl.classList.add('hidden');
        if (!fd.get('time_slot')) { errEl.textContent = 'Please select a time slot.'; errEl.classList.remove('hidden'); return; }

        btn.disabled = true; btn.textContent = 'Saving...';
        const result = await window.flexamApi.schedules.update({
            id:            sched.id,
            course_id:     fd.get('course_id'),
            college:       document.getElementById('editSchedCollegeField').value,
            exam_type:     (fd.get('exam_type')==='Others'?(fd.get('exam_type_other')||'').trim():fd.get('exam_type')),
            semester:      fd.get('semester') || '',
            year_level:    fd.get('year_level'),
            section:       fd.get('section') || '',
            section_name:  fd.get('section') || '',
            class_section: fd.get('section') || '',
            exam_date:     fd.get('exam_date'),
            time_slot:     fd.get('time_slot'),
            duration:      fd.get('duration'),
            room_id:       document.getElementById('editSchedIsOnlineCheck')?.checked ? null : fd.get('room_id'),
            is_online:     document.getElementById('editSchedIsOnlineCheck')?.checked ? 1 : 0,
            proctor_id:    fd.get('proctor_id') || null,
            campus:        sched.campus || currentUser.campus || '',
            status:        'Pending'
        });
        btn.disabled = false; btn.textContent = 'Save Changes';
        if (result.success) { 
            activityLog('Schedule Updated', `${result.data?.course_code||sched.course_code||''} · ${result.data?.exam_type||sched.exam_type||''} · ${result.data?.exam_date||sched.exam_date||''}`);
            closeEditScheduleModal(); showToast('Schedule updated!'); await refreshAllData(); 
        }
        else { errEl.textContent = result.message || 'Failed to update schedule.'; errEl.classList.remove('hidden'); }
    };
};

window.closeEditScheduleModal = function() { const m = document.getElementById('editScheduleModal'); if (m) m.remove(); };
function closeEditScheduleModal() { window.closeEditScheduleModal(); }

window.deleteSchedule = async function(id) {
    if (!confirm('Are you sure you want to delete this schedule? This cannot be undone.')) return;
    try {
        const result = await window.flexamApi.schedules.delete(id);
        if (result.success) { 
            activityLog('Schedule Deleted', `Schedule #${id}`);
            showToast('Schedule deleted successfully'); await refreshAllData(); 
        }
        else { showToast(result.message || 'Failed to delete schedule', 'error'); }
    } catch (err) { showToast('Error deleting schedule', 'error'); }
};

// ─────────────────────────────────────────────────────────────────────────────
// BULK SCHEDULE SELECTION & ACTIONS (head.php — delete only)
// ─────────────────────────────────────────────────────────────────────────────

function getSelectedSchedIds() {
    return [...document.querySelectorAll('#schedMgmtBody .sched-row-cb:checked, .sched-mgmt-row .sched-row-cb:checked')]
        .filter(cb => { const row = cb.closest('tr'); return row && row.style.display !== 'none'; })
        .map(cb => cb.getAttribute('data-id'));
}

window.onSchedRowCbChange = function() {
    const ids  = getSelectedSchedIds();
    const bar  = document.getElementById('schedBulkBar');
    const cnt  = document.getElementById('schedBulkCount');
    if (bar) bar.style.display = ids.length > 0 ? '' : 'none';
    if (cnt) cnt.textContent   = `${ids.length} schedule${ids.length !== 1 ? 's' : ''} selected`;
    const allVisible = [...document.querySelectorAll('.sched-row-cb')]
        .filter(cb => cb.closest('tr')?.style.display !== 'none');
    const selAll = document.getElementById('schedSelectAll');
    if (selAll) {
        selAll.checked       = allVisible.length > 0 && allVisible.every(cb => cb.checked);
        selAll.indeterminate = ids.length > 0 && !selAll.checked;
    }
};

window.toggleAllSchedRows = function(checked) {
    document.querySelectorAll('.sched-row-cb').forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none') cb.checked = checked;
    });
    window.onSchedRowCbChange();
};

window.clearSchedSelection = function() {
    document.querySelectorAll('.sched-row-cb').forEach(cb => cb.checked = false);
    const selAll = document.getElementById('schedSelectAll');
    if (selAll) { selAll.checked = false; selAll.indeterminate = false; }
    window.onSchedRowCbChange();
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
            if (result.success) { activityLog('Schedule Deleted (Bulk)', `Schedule #${id}`); deleted++; }
            else { failed++; }
        } catch(e) { failed++; }
    }
    await refreshAllData(); window.clearSchedSelection();
    showToast(`🗑️ Deleted ${deleted} schedule(s)${failed > 0 ? ` · ${failed} failed` : ''}.`);
};

// 16. MODAL CLOSE
// ─────────────────────────────────────────────────────────────────────────────
window.closeModal = function(id) { const el=document.getElementById(id); if (el) el.remove(); document.body.style.overflow=''; };

// ─────────────────────────────────────────────────────────────────────────────
// FEEDBACK HELPERS (Program Head)
// ─────────────────────────────────────────────────────────────────────────────
window.headFeedbacksFilter = function() {
    const program  = (document.getElementById('headFbProgramFilter')?.value  || '').toLowerCase();
    const category = (document.getElementById('headFbCategoryFilter')?.value || '').toLowerCase();
    const status   = (document.getElementById('headFbStatusFilter')?.value   || '');
    const search   = (document.getElementById('headFbSearch')?.value         || '').toLowerCase();

    let visible = 0;
    document.querySelectorAll('#headFeedbacksBody [data-hfb-program]').forEach(card => {
        const rowProgram  = (card.getAttribute('data-hfb-program')  || '').toLowerCase();
        const rowCategory = (card.getAttribute('data-hfb-category') || '').toLowerCase();
        const rowStatus   = card.getAttribute('data-hfb-status')    || '';
        const rowSearch   = (card.getAttribute('data-hfb-search')   || '').toLowerCase();

        const show =
            (!program  || rowProgram  === program)  &&
            (!category || rowCategory === category) &&
            (!status   || rowStatus   === status)   &&
            (!search   || rowSearch.includes(search));

        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noResults = document.getElementById('headFbNoResults');
    const body = document.getElementById('headFeedbacksBody');
    if (noResults) noResults.classList.toggle('hidden', visible > 0 || !body);
};

window.headClearFbFilters = function() {
    ['headFbProgramFilter','headFbCategoryFilter','headFbStatusFilter'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    const si = document.getElementById('headFbSearch');
    if (si) si.value = '';
    headFeedbacksFilter();
};

window.headMarkAllFbRead = async function() {
    const res = await flexamFetch('../api/feedbacks.php?action=mark_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: 0 })
    });
    if (res.success) { showToast('All feedbacks marked as read'); await refreshAllData(); }
    else showToast(res.message || 'Error', 'error');
};

window.headDeleteFeedback = async function(id) {
    if (!confirm('Delete this feedback?')) return;
    const res = await flexamFetch('../api/feedbacks.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if (res.success) { showToast('Feedback deleted'); await refreshAllData(); }
    else showToast(res.message || 'Error', 'error');
};

// ─────────────────────────────────────────────────────────────────────────────
// MOBILE SIDEBAR HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function openMobileSidebar() {
    const sb  = document.getElementById('sidebar');
    const ov  = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.add('open');
    if (ov) ov.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeMobileSidebar() {
    const sb  = document.getElementById('sidebar');
    const ov  = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.remove('open');
    if (ov) ov.classList.remove('active');
    document.body.style.overflow = '';
}

// ─────────────────────────────────────────────────────────────────────────────
// 17. ATTACH HANDLERS
// ─────────────────────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════
// ── HEAD ANALYTICS EXTRA SECTIONS ───────────────────────────────────────────
// Room Utilization, Building/Room Donuts, Proctor Analytics, Conflicts
// All data is pre-filtered to the head's college/program before these run.
// ═══════════════════════════════════════════════════════════════════════════

// ── Shared pie-chart renderer (mirrors campus_admin) ────────────────────────
function headRenderPieChart(el, entries, colorList, emptyMsg) {
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

// ── Get the head's college-/program-filtered schedule set ────────────────────
function _headFilteredSchedules() {
    const myCollege = (currentUser.college || '').trim().toUpperCase();
    const myProgram = (currentUser.program || '').trim().toUpperCase();
    let fa = allData.filter(d => d.type === 'schedule');
    if (myCollege) {
        fa = fa.filter(s => (s.college || '').trim().toUpperCase() === myCollege);
    }
    if (myProgram) {
        fa = fa.filter(s => (s.program || '').trim().toUpperCase() === myProgram);
    }
    return fa;
}

// ── Render Room Util + Donut Charts + Conflicts (called after DOM paint) ──────
function renderHeadAnalyticsCharts() {
    const schedules = _headFilteredSchedules();
    const myCampus  = (currentUser.campus || '').trim();

    // ── Scheduling Conflicts ─────────────────────────────────────────────────
    const conflictsTableEl = document.getElementById('headConflictsTable');
    const conflictBadgeEl  = document.getElementById('headConflictSummaryBadge');
    if (conflictsTableEl) {
        // Conflicts are checked against all campus schedules (same room can be
        // used by other colleges), but only those involving the head's college/program.
        const myCollege = (currentUser.college || '').trim().toUpperCase();
        const myProgram = (currentUser.program  || '').trim().toUpperCase();
        function _isMySchedule(s) {
            if (myProgram) return (s.program || '').trim().toUpperCase() === myProgram;
            if (myCollege) {
                return (s.college || '').trim().toUpperCase() === myCollege ||
                       (s.program || '').trim().toUpperCase() === myCollege;
            }
            return true;
        }
        const allScheds = schedules; // already college/program-filtered
        const conflicts = [];

        // 1. Locked room
        allScheds.forEach(s => {
            if (s.is_online) return;
            const r = allData.find(d => d.type === 'room' && dbId(String(d.id)) === dbId(String(s.room_id || s.room || '')));
            if (r && r.locked) conflicts.push({
                type: 'locked', badge: '🔒 Locked Room', bg: 'bg-red-50', bdg: 'bg-red-100 text-red-700',
                course: s.course_code || s.course || '—',
                section: [s.year_level, s.section].filter(Boolean).join(' / ') || '—',
                college: s.college || '—',
                issue: `Room <strong>${r.name}${r.building ? ', ' + r.building : ''}</strong> is locked/blocked`,
                date: s.exam_date || s.date || '—',
                campus: s.campus || '—'
            });
        });

        // 2. Double-booking (same room + date + time — check across all campus schedules)
        const allCampusScheds = allData.filter(d => d.type === 'schedule' && (!myCampus || (d.campus || '') === myCampus));
        const seen = {};
        allCampusScheds.forEach(s => {
            if (s.is_online) return;
            const rid  = dbId(String(s.room_id || s.room || ''));
            const date = (s.exam_date || s.date || '').trim();
            const time = (s.time_slot || s.start_time || '').trim();
            if (!rid || !date || !time) return;
            const key = `${rid}|${date}|${time}`;
            if (seen[key]) {
                const prev = seen[key];
                // Only surface if at least one side belongs to this head's scope
                if (!_isMySchedule(s) && !_isMySchedule(prev)) return;
                const r = allData.find(d => d.type === 'room' && dbId(String(d.id)) === rid);
                conflicts.push({
                    type: 'double', badge: '⚠️ Double-Booking', bg: 'bg-orange-50', bdg: 'bg-orange-100 text-orange-700',
                    course: `${s.course_code || '?'} & ${prev.course_code || '?'}`,
                    section: [s.year_level, s.section].filter(Boolean).join(' / ') || '—',
                    college: s.college || '—',
                    issue: `${r ? r.name + (r.building ? ', ' + r.building : '') : 'Room #' + rid} double-booked at ${time}`,
                    date, campus: s.campus || '—'
                });
            } else { seen[key] = s; }
        });

        // 3. No room assigned
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

        // 4. Proctor double-assignment
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
                    course: `${s.course_code || '?'} & ${prev.course_code || '?'}`,
                    section: [s.year_level, s.section].filter(Boolean).join(' / ') || '—',
                    college: s.college || '—',
                    issue: `${s.proctor_name || 'Proctor #' + pid} is assigned to two rooms at ${time}`,
                    date, campus: s.campus || '—'
                });
            } else { seenProctors[key] = s; }
        });

        // 5. Section time overlap
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
                    course: `${s.course_code || '?'} & ${prev.course_code || '?'}`,
                    section: sec.toUpperCase(),
                    college: s.college || '—',
                    issue: `Section scheduled in two rooms simultaneously at ${time}`,
                    date, campus: s.campus || '—'
                });
            } else { seenSections[key] = s; }
        });

        // Summary badge
        if (conflictBadgeEl) {
            const lk = conflicts.filter(c => c.type === 'locked').length;
            const db = conflicts.filter(c => c.type === 'double').length;
            const nr = conflicts.filter(c => c.type === 'noroom').length;
            const pt = conflicts.filter(c => c.type === 'proctor').length;
            const st = conflicts.filter(c => c.type === 'section').length;
            if (conflicts.length === 0) {
                conflictBadgeEl.innerHTML = '<span class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>No conflicts detected</span>';
            } else {
                conflictBadgeEl.innerHTML =
                    '<div class="flex flex-wrap gap-2">' +
                    (lk ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700">🔒 ${lk} Locked Room${lk !== 1 ? 's' : ''}</span>` : '') +
                    (db ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-orange-100 text-orange-700">⚠️ ${db} Double-Booking${db !== 1 ? 's' : ''}</span>` : '') +
                    (nr ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-200 text-slate-600">📋 ${nr} No Room${nr !== 1 ? 's' : ''}</span>` : '') +
                    (pt ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700">👤 ${pt} Proctor Conflict${pt !== 1 ? 's' : ''}</span>` : '') +
                    (st ? `<span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">🎓 ${st} Section Overlap${st !== 1 ? 's' : ''}</span>` : '') +
                    '</div>';
            }
        }

        window._headConflictsAll  = conflicts;
        window._headConflictsType = 'all';
        _headConflictsPage = 1;

        const pillsEl = document.getElementById('headConflictTypePills');
        if (conflicts.length === 0) {
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
            if (pillsEl) pillsEl.style.display = '';
            renderHeadConflictsPage(1);
        }
    }

    // ── Also render proctor analytics ────────────────────────────────────────
    if (document.getElementById('headProctorAnalyticsChart')) {
        renderHeadProctorAnalytics();
        headProctorAnalyticsFilter(headProctorDateFilter);
    }
}
window.renderHeadAnalyticsCharts = renderHeadAnalyticsCharts;

// ── Proctor Analytics ─────────────────────────────────────────────────────────
function headProctorAnalyticsFilter(period) {
    headProctorDateFilter = period || 'all';
    document.querySelectorAll('#headProctorDatePills [data-hppill]').forEach(btn => {
        const isActive = btn.dataset.hppill === headProctorDateFilter;
        btn.className = 'px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap ' +
            (isActive ? 'bg-white text-purple-700 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700');
    });
    renderHeadProctorAnalytics();
}
window.headProctorAnalyticsFilter = headProctorAnalyticsFilter;

function renderHeadProctorAnalytics() {
    const now   = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    function inHeadProctorDateRange(s) {
        const raw = (s.exam_date || s.date || '').trim();
        if (!raw || raw === '0000-00-00') return headProctorDateFilter === 'all';
        const d = new Date(raw.substring(0, 10) + 'T00:00:00');
        if (isNaN(d.getTime())) return headProctorDateFilter === 'all';
        if (headProctorDateFilter === 'day')   return d >= today && d < new Date(today.getTime() + 86400000);
        if (headProctorDateFilter === 'week') {
            const ws = new Date(today); ws.setDate(today.getDate() - today.getDay());
            return d >= ws && d < new Date(ws.getTime() + 7 * 86400000);
        }
        if (headProctorDateFilter === 'month') return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
        if (headProctorDateFilter === 'year')  return d.getFullYear() === now.getFullYear();
        return true;
    }

    // Filter by head's college/program AND date range
    const myCollege = (currentUser.college || '').trim().toUpperCase();
    const myProgram = (currentUser.program  || '').trim().toUpperCase();
    const myCampus  = (currentUser.campus   || '').trim();

    // All campus schedules within the date range (unfiltered by college/program)
    const allCampusSchedules = allData.filter(d => d.type === 'schedule' && inHeadProctorDateRange(d));

    // Schedules scoped to the head's college/program (used for totalExams count)
    let schedules = allCampusSchedules;
    if (myCollege) {
        schedules = schedules.filter(s => (s.college || '').trim().toUpperCase() === myCollege);
    }
    if (myProgram) {
        schedules = schedules.filter(s => (s.program || '').trim().toUpperCase() === myProgram);
    }

    // Proctors whose college_program contains the head's college OR program.
    // Fall back to campus-only match if head has no college/program set.
    const allCampusProctors = allData.filter(d => d.type === 'proctor' && (!myCampus || (d.campus || '').trim().toLowerCase() === myCampus.toLowerCase()));
    const proctors = allCampusProctors.filter(p => {
        if (!myCollege && !myProgram) return true;
        const cp = (p.college_program || p.collegeProgram || '').toUpperCase();
        if (myProgram && cp.includes(myProgram)) return true;
        if (myCollege && cp.includes(myCollege)) return true;
        return false;
    });

    const totalExams = schedules.length;
    // shareBase uses ALL campus schedules — same denominator as campus admin so % Share matches
    const shareBase = allCampusSchedules.length;

    // Seed proctorMap with ALL matched proctors (even those with 0 assignments)
    const proctorMap = {};
    proctors.forEach(p => {
        proctorMap[String(p.id)] = { id: p.id, name: p.name || '—', campus: p.campus || '—', total: 0, approved: 0, pending: 0 };
    });

    // Count assignments using ALL campus schedules — a proctor assigned to ANY
    // schedule on this campus counts, even if the schedule's college/program
    // field is blank or stored differently. Only proctors already seeded in
    // proctorMap (i.e. belonging to the head's scope) are counted.
    allCampusSchedules.forEach(s => {
        const pids = (s.proctor_ids && s.proctor_ids.length)
            ? s.proctor_ids.map(String)
            : (s.proctor_id ? [String(s.proctor_id)] : []);
        pids.forEach(pid => {
            if (!proctorMap[pid]) return; // skip proctors outside this head's scope
            proctorMap[pid].total++;
            if (s.status === 'Approved') proctorMap[pid].approved++;
            if (s.status === 'Pending' || !s.status) proctorMap[pid].pending++;
        });
    });

    // Show all matched proctors — those with assignments first, then the rest alphabetically
    const proctorRows = Object.values(proctorMap).sort((a, b) => b.total - a.total || a.name.localeCompare(b.name));
    const periodLabel = { all: 'All Time', day: 'Today', week: 'This Week', month: 'This Month', year: 'This Year' }[headProctorDateFilter] || 'All Time';

    const summaryEl = document.getElementById('headProctorAnalyticsSummary');
    if (summaryEl) {
        const assigned = proctorRows.filter(p => p.total > 0).length;
        summaryEl.textContent = `${proctorRows.length} proctor${proctorRows.length !== 1 ? 's' : ''} · ${assigned} assigned · ${shareBase} exam${shareBase !== 1 ? 's' : ''} · Period: ${periodLabel}`;
    }

    const chartEl = document.getElementById('headProctorAnalyticsChart');
    if (chartEl) {
        const pieEntries = proctorRows.filter(p => p.total > 0).slice(0, 12).map(p => [p.name, p.total]);
        headRenderPieChart(chartEl, pieEntries, HEAD_PROCTOR_PIE_COLORS, 'No proctor assignments found for this period.');
    }

    const bodyEl = document.getElementById('headProctorAnalyticsBody');
    if (bodyEl) {
        if (proctorRows.length === 0) {
            bodyEl.innerHTML = `<tr><td colspan="7" class="py-10 text-center text-slate-400 text-sm">No proctors found for your college/program.</td></tr>`;
            const pagerEl = document.getElementById('headProctorAnalyticsPager');
            if (pagerEl) pagerEl.innerHTML = '';
        } else {
            _headProctorAllRows = proctorRows;
            _headProctorPage = 1;
            renderHeadProctorPage(_headProctorPage, shareBase);
        }
    }
}

function renderHeadProctorPage(page, totalExams) {
    const rows = _headProctorAllRows;
    const total = rows.length;
    if (!total) return;
    const totalPages = Math.ceil(total / HEAD_PROCTOR_PAGE_SIZE);
    _headProctorPage = Math.max(1, Math.min(page, totalPages));
    const start = (_headProctorPage - 1) * HEAD_PROCTOR_PAGE_SIZE;
    const slice = rows.slice(start, start + HEAD_PROCTOR_PAGE_SIZE);
    const te = typeof totalExams !== 'undefined' ? totalExams : rows.reduce((sum, p) => sum + p.total, 0);

    const bodyEl = document.getElementById('headProctorAnalyticsBody');
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

    const pagerEl = document.getElementById('headProctorAnalyticsPager');
    if (!pagerEl) return;
    const btnBase   = 'px-2.5 py-1 rounded text-[11px] font-medium transition';
    const btnActive = 'bg-purple-600 text-white shadow-sm';
    const btnInact  = 'text-slate-600 hover:bg-slate-100';
    const maxV = 7;
    let ps = Math.max(1, _headProctorPage - Math.floor(maxV / 2));
    let pe = Math.min(totalPages, ps + maxV - 1);
    if (pe - ps < maxV - 1) ps = Math.max(1, pe - maxV + 1);
    let pageButtons = '';
    if (ps > 1) pageButtons += `<button onclick="renderHeadProctorPage(1)" class="${btnBase} ${btnInact}">1</button><span class="text-slate-300 text-xs px-1">…</span>`;
    for (let p = ps; p <= pe; p++) {
        pageButtons += `<button onclick="renderHeadProctorPage(${p})" class="${btnBase} ${p === _headProctorPage ? btnActive : btnInact}">${p}</button>`;
    }
    if (pe < totalPages) pageButtons += `<span class="text-slate-300 text-xs px-1">…</span><button onclick="renderHeadProctorPage(${totalPages})" class="${btnBase} ${btnInact}">${totalPages}</button>`;

    pagerEl.innerHTML = `
        <p class="text-[10px] text-slate-400">Showing ${start + 1}–${Math.min(start + HEAD_PROCTOR_PAGE_SIZE, total)} of ${total} proctor${total !== 1 ? 's' : ''}</p>
        <div class="flex items-center gap-1">
            <button onclick="renderHeadProctorPage(${_headProctorPage - 1})" ${_headProctorPage === 1 ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg> Prev
            </button>
            <div class="flex items-center gap-0.5">${pageButtons}</div>
            <button onclick="renderHeadProctorPage(${_headProctorPage + 1})" ${_headProctorPage === totalPages ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                Next <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>`;
}
window.renderHeadProctorPage = renderHeadProctorPage;

// ── Conflicts pagination & type-filter ────────────────────────────────────────
function renderHeadConflictsPage(page) {
    const allConflicts = window._headConflictsAll || [];
    const typeFilter   = window._headConflictsType || 'all';
    const filtered     = typeFilter === 'all' ? allConflicts : allConflicts.filter(c => c.type === typeFilter);
    const total        = filtered.length;
    const conflictsTableEl = document.getElementById('headConflictsTable');
    if (!conflictsTableEl) return;

    if (total === 0) {
        conflictsTableEl.innerHTML = `<div class="py-10 text-center text-slate-400 text-sm">No conflicts of this type found.</div>`;
        const pagerEl = document.getElementById('headConflictsPager');
        if (pagerEl) pagerEl.innerHTML = '';
        return;
    }

    const totalPages = Math.ceil(total / HEAD_CONFLICTS_PAGE_SIZE);
    _headConflictsPage = Math.max(1, Math.min(page, totalPages));
    const start = (_headConflictsPage - 1) * HEAD_CONFLICTS_PAGE_SIZE;
    const slice = filtered.slice(start, start + HEAD_CONFLICTS_PAGE_SIZE);

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

    const pagerEl = document.getElementById('headConflictsPager');
    if (!pagerEl) return;
    if (totalPages <= 1) {
        pagerEl.innerHTML = `<p class="text-[10px] text-slate-400">${total} conflict${total !== 1 ? 's' : ''} found</p>`;
        return;
    }
    const btnBase   = 'px-2.5 py-1 rounded text-[11px] font-medium transition';
    const btnActive = 'bg-red-500 text-white shadow-sm';
    const btnInact  = 'text-slate-600 hover:bg-slate-100';
    const maxV = 7;
    let ps = Math.max(1, _headConflictsPage - Math.floor(maxV / 2));
    let pe = Math.min(totalPages, ps + maxV - 1);
    if (pe - ps < maxV - 1) ps = Math.max(1, pe - maxV + 1);
    let pageButtons = '';
    if (ps > 1) pageButtons += `<button onclick="renderHeadConflictsPage(1)" class="${btnBase} ${btnInact}">1</button><span class="text-slate-300 text-xs px-1">…</span>`;
    for (let p = ps; p <= pe; p++) {
        pageButtons += `<button onclick="renderHeadConflictsPage(${p})" class="${btnBase} ${p === _headConflictsPage ? btnActive : btnInact}">${p}</button>`;
    }
    if (pe < totalPages) pageButtons += `<span class="text-slate-300 text-xs px-1">…</span><button onclick="renderHeadConflictsPage(${totalPages})" class="${btnBase} ${btnInact}">${totalPages}</button>`;

    pagerEl.innerHTML = `
        <p class="text-[10px] text-slate-400">Showing ${start + 1}–${Math.min(start + HEAD_CONFLICTS_PAGE_SIZE, total)} of ${total} conflict${total !== 1 ? 's' : ''}</p>
        <div class="flex items-center gap-1">
            <button onclick="renderHeadConflictsPage(${_headConflictsPage - 1})" ${_headConflictsPage === 1 ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg> Prev
            </button>
            <div class="flex items-center gap-0.5">${pageButtons}</div>
            <button onclick="renderHeadConflictsPage(${_headConflictsPage + 1})" ${_headConflictsPage === totalPages ? 'disabled' : ''} class="${btnBase} text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center gap-1">
                Next <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>`;
}
window.renderHeadConflictsPage = renderHeadConflictsPage;

window.headConflictsTypeFilter = function(type) {
    window._headConflictsType = type;
    _headConflictsPage = 1;
    document.querySelectorAll('#headConflictTypePills [data-hcpill]').forEach(btn => {
        const isActive = btn.dataset.hcpill === type;
        btn.className = 'px-3 py-1 rounded-md text-xs font-semibold transition whitespace-nowrap ' +
            (isActive ? 'bg-white text-red-600 shadow-sm border border-slate-200' : 'text-slate-500 hover:text-slate-700');
    });
    renderHeadConflictsPage(1);
};

// ── Export Proctor PDF (head scoped) ─────────────────────────────────────────
window.exportHeadProctorAnalyticsPDF = async function() {
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
    const now        = new Date();
    const today      = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const myCollege  = (currentUser.college || '').trim().toUpperCase();
    const myProgram  = (currentUser.program  || '').trim().toUpperCase();
    const myCampus   = (currentUser.campus   || '').trim();
    const scopeLabel = myProgram || myCollege || 'Your Department';

    function inPdfDateRange(s) {
        const raw = (s.exam_date || s.date || '').trim();
        if (!raw || raw === '0000-00-00') return headProctorDateFilter === 'all';
        const d = new Date(raw.substring(0, 10) + 'T00:00:00');
        if (isNaN(d.getTime())) return headProctorDateFilter === 'all';
        if (headProctorDateFilter === 'day')   return d >= today && d < new Date(today.getTime() + 86400000);
        if (headProctorDateFilter === 'week')  { const ws = new Date(today); ws.setDate(today.getDate() - today.getDay()); return d >= ws && d < new Date(ws.getTime() + 7 * 86400000); }
        if (headProctorDateFilter === 'month') return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
        if (headProctorDateFilter === 'year')  return d.getFullYear() === now.getFullYear();
        return true;
    }

    // All campus schedules in date range (unfiltered by college/program)
    const allCampusSchedulesPdf = allData.filter(d => d.type === 'schedule' && inPdfDateRange(d));

    // College/program-scoped schedules for totalExams count only
    let schedules = allCampusSchedulesPdf;
    if (myCollege) schedules = schedules.filter(s => (s.college || '').trim().toUpperCase() === myCollege);
    if (myProgram) schedules = schedules.filter(s => (s.program || '').trim().toUpperCase() === myProgram);

    // Proctors scoped to head's college/program (same logic as renderHeadProctorAnalytics)
    const allCampusProctorsPdf = allData.filter(d => d.type === 'proctor' && (!myCampus || (d.campus || '').trim().toLowerCase() === myCampus.toLowerCase()));
    const proctors = allCampusProctorsPdf.filter(p => {
        if (!myCollege && !myProgram) return true;
        const cp = (p.college_program || p.collegeProgram || '').toUpperCase();
        if (myProgram && cp.includes(myProgram)) return true;
        if (myCollege && cp.includes(myCollege)) return true;
        return false;
    });

    const totalExams = schedules.length;
    // shareBase = all campus exams — same denominator as campus admin so % Share matches
    const shareBasePdf = allCampusSchedulesPdf.length;
    const periodLabel = { all: 'All Time', day: 'Today', week: 'This Week', month: 'This Month', year: 'This Year' }[headProctorDateFilter] || 'All Time';

    // Seed map with scoped proctors, count using ALL campus schedules (same fix as render)
    const proctorMap = {};
    proctors.forEach(p => { proctorMap[String(p.id)] = { id: p.id, name: p.name || '—', campus: p.campus || '—', total: 0, approved: 0, pending: 0 }; });
    allCampusSchedulesPdf.forEach(s => {
        const pids = (s.proctor_ids && s.proctor_ids.length) ? s.proctor_ids.map(String) : (s.proctor_id ? [String(s.proctor_id)] : []);
        pids.forEach(pid => {
            if (!proctorMap[pid]) return; // skip proctors outside this head's scope
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
    doc.text(`Scope: ${scopeLabel} · Campus: ${myCampus || 'All'} · Period: ${periodLabel} · ${rows.length} proctors · ${shareBasePdf} total exams`, 14, 26);
    doc.text(`Generated: ${generated}`, 14, 33);
    doc.autoTable({
        startY: 46,
        head: [['#', 'Proctor Name', 'Campus', 'Total Exams', 'Approved', 'Pending', '% Share']],
        body: rows.map((p, i) => [i + 1, p.name, p.campus, p.total, p.approved, p.pending, (shareBasePdf > 0 ? Math.round((p.total / shareBasePdf) * 100) : 0) + '%']),
        styles: { fontSize: 9, cellPadding: 3 },
        headStyles: { fillColor: [88, 28, 135], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [248, 245, 255] },
        columnStyles: { 0: { halign: 'center', cellWidth: 10 }, 3: { halign: 'center' }, 4: { halign: 'center' }, 5: { halign: 'center' }, 6: { halign: 'center' } }
    });
    const slug = (myProgram || myCollege || 'dept').toLowerCase().replace(/\s+/g, '-');
    doc.save(`proctor-analytics-${slug}-${headProctorDateFilter}.pdf`);
    showToast('PDF downloaded!', 'success');
};

// ── END HEAD ANALYTICS EXTRA SECTIONS ────────────────────────────────────────

function attachHandlers() {
    document.querySelectorAll('[data-view]').forEach(btn => {
        btn.onclick = () => { currentView=btn.dataset.view; sessionStorage.setItem('head_currentView', currentView); if (currentView==='analytics') { analyticsPeriod='all'; analyticsDateFrom=''; analyticsDateTo=''; analyticsSchedPage=0; analyticsCoursePage=0; analyticsRejPage=0; analyticsRoomUtilPage=0; } renderApp(); };
    });

    const si = document.getElementById('scheduleSearch');
    if (si) si.addEventListener('input', e => { scheduleSearchTerm=e.target.value; renderApp(); });
    const etf = document.getElementById('examTypeFilter');
    if (etf) etf.addEventListener('change', e => { scheduleFilterExamType=e.target.value; renderApp(); });
    const vcf = document.getElementById('vsCollegeFilter');
    if (vcf) vcf.addEventListener('change', e => { vsCollegeFilter=e.target.value; renderApp(); });
    const vsStatusF = document.getElementById('vsStatusFilter');
    if (vsStatusF) vsStatusF.addEventListener('change', e => { vsStatusFilter=e.target.value; renderApp(); });

    const adf = document.getElementById('analyticsDateFrom');
    if (adf) adf.addEventListener('change', e => { analyticsDateFrom=e.target.value; renderApp(); });
    const adt = document.getElementById('analyticsDateTo');
    if (adt) adt.addEventListener('change', e => { analyticsDateTo=e.target.value; renderApp(); });

    const sms = document.getElementById('scheduleMgmtSearch');
    if (sms) sms.addEventListener('input', e => { scheduleMgmtSearch=e.target.value; smCurrentPage=1; renderApp(); });
    // smCourseFilter replaced by custom dropdown (selectSmCourse handles state)

    const smExam = document.getElementById('smExamTypeFilter');
    if (smExam) {
        smExam.addEventListener('mousedown', () => closeSmCourseDd());
        smExam.addEventListener('change', e => { scheduleMgmtExamType=e.target.value; smCurrentPage=1; renderApp(); });
    }
    const smSem = document.getElementById('smSemesterFilter');
    if (smSem) {
        smSem.addEventListener('mousedown', () => closeSmCourseDd());
        smSem.addEventListener('change', e => { scheduleMgmtSemester=e.target.value; smCurrentPage=1; renderApp(); });
    }

    // Course filters now use inline onchange/oninput handlers — no attachHandlers needed
    const pi = document.getElementById('proctorSearchInput');
    if (pi) pi.addEventListener('input', e => { proctorSearch=e.target.value; renderApp(); });

    // Initialize pagination controls after render
    if (currentView === 'view-schedules') {
        viewSchedFilter();
    }
    // Fire head analytics extra sections (room util, donuts, proctor, conflicts)
    if (currentView === 'analytics') {
        setTimeout(renderHeadAnalyticsCharts, 0);
    }
    if (currentView === 'schedule-mgmt' && window._schedTab !== 'special') {
        const smTP = Math.max(1, Math.ceil(smCurrentPage > 1 ? 1 : 1, 1));
        // Compute filtered count for SM pagination info
        let smF = allData.filter(d => d.type === 'schedule');
        if (scheduleMgmtSearch) { const q = scheduleMgmtSearch.toLowerCase(); smF = smF.filter(s => (s.course_code||'').toLowerCase().includes(q)||(s.course_name||'').toLowerCase().includes(q)||(s.room_name||s.room||'').toLowerCase().includes(q)||(s.exam_type||'').toLowerCase().includes(q)||(s.proctor_name||'').toLowerCase().includes(q)); }
        if (scheduleMgmtCampus   !== 'all') smF = smF.filter(s => (s.campus||s.room_campus||'') === scheduleMgmtCampus);
        if (scheduleMgmtCourse   !== 'all') smF = smF.filter(s => s.course_code === scheduleMgmtCourse);
        if (scheduleMgmtExamType !== 'all') smF = smF.filter(s => s.exam_type === scheduleMgmtExamType);
        if (scheduleMgmtSemester !== 'all') smF = smF.filter(s => (s.semester||'') === scheduleMgmtSemester);
        const smTotalPages = Math.max(1, Math.ceil(smF.length / PAGE_SIZE));
        renderSmPagination(smF.length, smTotalPages);
    }
}

// ── Campus Tab Strip for Courses (mirrors admin.php renderCampusTabs) ────
function renderHeadCampusTabs(items) {
    const active = courseCampus === 'all' ? '' : courseCampus;
    const counts = { '': items.length };
    // count unique course codes per campus
    CAMPUSES.forEach(c => {
        counts[c] = items.filter(i => (i.campus || '') === c).length;
    });
    const tabs = [{ label: 'All Campuses', value: '' }, ...CAMPUSES.map(c => ({ label: c, value: c }))];
    return `<div class="flex items-center gap-1 flex-wrap">
        ${tabs.map(t => `
        <button onclick="courseCampus='${t.value||'all'}';courseCurrentPage=1;renderApp()"
            class="campus-tab ${active === t.value ? 'active' : ''}">
            ${t.label}
            <span class="tab-count">${counts[t.value] ?? 0}</span>
        </button>`).join('')}
    </div>`;
}

// ── Course Detail Slide-in Side Panel (mirrors admin.php) ─────────────────
function showHeadCoursePanel(codeKey) {
    const groups = window._headCourseGroups || {};
    const c = groups[codeKey];
    if (!c) return;

    const code      = c.course_code  || '';
    const name      = c.course_name  || '';
    const progList  = c._progList    || [];   // full list with composite dedup
    const programs  = c._programs    || [];   // unique acronyms (for stats count)
    const campuses  = c._campuses    || [];
    const colleges  = c._colleges    || [];
    const progDetails = c._progDetails || {};

    const displayName = (name && name.toLowerCase() !== code.toLowerCase()) ? name : '';
    const iconText = code.substring(0, 4).toUpperCase() || '?';

    document.getElementById('headCoursePanelIcon').textContent    = iconText;
    document.getElementById('headCoursePanelTitle').textContent   = code || 'Course';
    document.getElementById('headCoursePanelSubtitle').textContent = displayName || (campuses.length ? campuses.join(', ') : '');

    // Stats bar — use progList.length for the true count
    const allYears = [...new Set(progList.map(p => p.year_level).filter(Boolean))];
    const allSems  = [...new Set(progList.map(p => p.semester).filter(Boolean))];
    document.getElementById('headCoursePanelStats').innerHTML = `
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 14px;display:flex;flex-direction:column;gap:1px;">
            <span style="font-size:1.1rem;font-weight:800;color:#047857;">${progList.length}</span>
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
    const body = document.getElementById('headCoursePanelBody');
    const esc = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    let html = '';

    // Course Details section
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

    // Programs section — iterate over progList (full composite list)
    if (progList.length === 0) {
        html += `<div style="text-align:center;padding:32px 0;color:#94a3b8;">
            <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block;opacity:0.4"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p style="font-size:0.82rem;">No programs listed.</p></div>`;
    } else {
        html += `<p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:12px;">All Programs (${progList.length})</p>`;
        const colors = [
            ['#ecfdf5','#047857'],['#eff6ff','#3b82f6'],['#fdf4ff','#9333ea'],
            ['#fff7ed','#ea580c'],['#fefce8','#ca8a04'],['#f0fdf4','#16a34a']
        ];
        html += progList.map((pd, i) => {
            const prog = pd.program || '';
            // Look up full program name from colleges data
            const collegeRec = pd.college
                ? allData.find(d =>
                    d.type === 'college' &&
                    ((d.code||'').toLowerCase() === pd.college.toLowerCase() ||
                     (d.name||'').toLowerCase() === pd.college.toLowerCase())
                  )
                : null;
            const fullName = collegeRec
                ? ((collegeRec.programs||[]).find(p => {
                    const a = p.includes('=') ? p.split('=')[0].trim() : p.trim();
                    return a.toLowerCase() === prog.toLowerCase();
                  })||'').split('=')[1]?.trim()||''
                : '';
            const linkedCollegeName = collegeRec
                ? (collegeRec.name || collegeRec.code || pd.college || '')
                : (pd.college || '');
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
                        ${pd.campus ? `<span style="font-size:0.65rem;font-weight:600;color:#b45309;background:#fffbeb;border:1px solid #fde68a;padding:1px 6px;border-radius:99px;">${esc(pd.campus)}</span>` : ''}
                    </div>
                </div>
                <span style="font-size:0.65rem;font-weight:700;color:#94a3b8;background:#f1f5f9;padding:2px 7px;border-radius:99px;flex-shrink:0;">#${i+1}</span>
            </div>`;
        }).join('');
    }

    body.innerHTML = html;

    // Open panel
    document.getElementById('headCoursePanelOverlay').style.display = 'block';
    document.getElementById('headCourseDetailSidePanel').style.right = '0';
}

function closeHeadCoursePanel() {
    const panel   = document.getElementById('headCourseDetailSidePanel');
    const overlay = document.getElementById('headCoursePanelOverlay');
    if (panel)   panel.style.right   = '-460px';
    if (overlay) overlay.style.display = 'none';
}

// ── Programs modal for course rows in head.php ────────────────────────────
function openHeadProgramsModal(id, programs, courseCode) {
    const existing = document.getElementById('headProgramsModal');
    if (existing) existing.remove();
    const modal = document.createElement('div');
    modal.id = 'headProgramsModal';
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black/50 z-50 p-4';
    let searchVal = '';
    function renderList(q) {
        const filtered = q ? programs.filter(p => p.toLowerCase().includes(q.toLowerCase())) : programs;
        return filtered.length === 0
            ? '<p class="text-sm text-slate-400 text-center py-4">No programs match your search.</p>'
            : filtered.map(prog => {
                const colRec = allData.find(d =>
                    d.type === 'college' && Array.isArray(d.programs) &&
                    d.programs.some(p => { const a = p.includes('=') ? p.split('=')[0].trim() : p.trim(); return a.toLowerCase() === prog.toLowerCase(); })
                );
                const fullName = colRec
                    ? ((colRec.programs.find(p => { const a = p.includes('=') ? p.split('=')[0].trim() : p.trim(); return a.toLowerCase() === prog.toLowerCase(); }) || '').split('=')[1]?.trim() || '')
                    : '';
                const initial = prog.charAt(0).toUpperCase();
                return `<span class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100 rounded-full">
                    <span class="w-5 h-5 rounded-full bg-blue-200 text-blue-800 flex items-center justify-center text-[10px] font-bold shrink-0">${initial}</span>
                    ${prog}${fullName ? ` — <span class="font-normal text-blue-600">${fullName}</span>` : ''}
                </span>`;
            }).join('');
    }
    modal.innerHTML = `
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex justify-between items-center px-6 py-5 bg-emerald-600 text-white">
            <div>
                <h2 class="text-lg font-bold">📋 ${courseCode} — Programs</h2>
                <p class="text-xs text-emerald-200 mt-0.5">${programs.length} program${programs.length !== 1 ? 's' : ''} enrolled in this course</p>
            </div>
            <button onclick="document.getElementById('headProgramsModal').remove()" class="text-white/70 hover:text-white">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-6 py-4 space-y-3">
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2"/></svg></span>
                <input id="headProgModalSearch" type="text" placeholder="Search programs..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div id="headProgModalList" class="flex flex-wrap gap-2 min-h-[60px]">${renderList('')}</div>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-slate-500">${programs.length} program${programs.length !== 1 ? 's' : ''}</span>
            <button onclick="document.getElementById('headProgramsModal').remove()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">Done</button>
        </div>
    </div>`;
    document.body.appendChild(modal);
    document.getElementById('headProgModalSearch').addEventListener('input', e => {
        document.getElementById('headProgModalList').innerHTML = renderList(e.target.value);
    });
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
}

// Campus dropdown toggle for head.php course rows
function headToggleDrop(btn) {
    const dropId = btn.getAttribute('data-dropid');
    const drop = document.getElementById(dropId);
    if (!drop) return;
    const isHidden = drop.classList.contains('hidden');
    // Close all open dropdowns first
    document.querySelectorAll('.hcdrop').forEach(el => el.classList.add('hidden'));
    // Open this one if it was closed
    if (isHidden) {
        drop.classList.remove('hidden');
        // Close when clicking outside
        setTimeout(() => {
            function outsideClick(e) {
                if (!drop.contains(e.target) && e.target !== btn) {
                    drop.classList.add('hidden');
                    document.removeEventListener('click', outsideClick);
                }
            }
            document.addEventListener('click', outsideClick);
        }, 0);
    }
}

// ─── Proctor Checkbox Picker Helpers (Head) ───────────────────────────────────
window.toggleProctorPicker = function(mode) {
    const dropdown = document.getElementById(mode + 'ProctorPickerDropdown');
    const chevron  = document.getElementById(mode + 'ProctorPickerChevron');
    const picker   = document.getElementById(mode + 'ProctorPicker');
    if (!dropdown) return;
    const isOpen = dropdown.style.display !== 'none';
    dropdown.style.display = isOpen ? 'none' : 'block';
    if (chevron) chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    if (picker)  picker.style.borderColor = isOpen ? '#e2e8f0' : '#10b981';
    if (!isOpen) {
        setTimeout(function() {
            document.addEventListener('click', function closeHandler(e) {
                const wrap = document.getElementById(mode + 'ProctorPickerWrap');
                if (wrap && !wrap.contains(e.target)) {
                    dropdown.style.display = 'none';
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                    if (picker)  picker.style.borderColor = '#e2e8f0';
                    document.removeEventListener('click', closeHandler);
                }
            });
        }, 10);
    }
};

// Used by filterProctorsByCampus rebuild and initial render
window.syncProctorPickerHead = function(mode) {
    window.syncProctorPicker(mode);
};

window.syncProctorPicker = function(mode) {
    const idMap = { add: 'schedProctorSelect', edit: 'editSchedProctorSelect' };
    const list      = document.getElementById(mode + 'ProctorPickerList');
    const hiddenSel = document.getElementById(idMap[mode]);
    const label     = document.getElementById(mode + 'ProctorPickerLabel');
    if (!list || !hiddenSel) return;
    const checked = Array.from(list.querySelectorAll('input[type="checkbox"]:checked'));
    const vals    = checked.map(function(c) { return c.value; });
    // Sync hidden select
    Array.from(hiddenSel.options).forEach(function(opt) {
        opt.selected = vals.includes(opt.value);
    });
    // Update label text
    if (label) {
        if (vals.length === 0) {
            label.textContent = 'Click to select proctors…';
            label.style.color = '#94a3b8';
        } else {
            var names = checked.map(function(c) {
                var span = c.closest('label') ? c.closest('label').querySelector('span') : null;
                return span ? span.textContent.trim() : c.value;
            });
            label.textContent = names.join(', ');
            label.style.color = '#1e293b';
        }
    }
};

window.filterProctorPicker = function(mode, query) {
    const list = document.getElementById(mode + 'ProctorPickerList');
    if (!list) return;
    const q = query.toLowerCase().trim();
    Array.from(list.querySelectorAll('label')).forEach(function(lbl) {
        lbl.style.display = (!q || lbl.textContent.toLowerCase().includes(q)) ? 'flex' : 'none';
    });
};

// Auto-init edit picker label after modal is injected into DOM
(function() {
    var observer = new MutationObserver(function() {
        var editList = document.getElementById('editProctorPickerList');
        if (editList && !editList.dataset.initialized) {
            editList.dataset.initialized = '1';
            window.syncProctorPicker('edit');
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
})();

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
// ── VIEWER ANNOUNCEMENTS – head role (DB-backed, real-time polling) ─────────
// ═══════════════════════════════════════════════════════════════════════════
// Role: head
//   • Viewer only – no create / edit / delete controls.
//   • Sees: all superadmin global announcements
//           + superadmin announcements targeted to their specific campus
//           + campus admin announcements for their campus.
//   • Toast notification appears automatically when a new announcement arrives.
//   • Banners are split by target: all / special_exam / exam_schedule.
// ─────────────────────────────────────────────────────────────────────────────
(function () {
    'use strict';

    var _campus = <?php echo json_encode(trim($currentUser['campus'] ?? '')); ?>;

    FlexAnn.init({
        apiBase  : '../api/announcements.php',
        role     : 'head',
        campus   : _campus,   // server scopes response to global + this campus
        onUpdate : function (anns) {
            _renderBanner('all',           anns);
            _renderBanner('special_exam',  anns);
            _renderBanner('exam_schedule', anns);
        },
    });

    /**
     * Populate a banner div for the given target.
     * Shows announcements whose target matches OR is 'all'.
     * Pinned announcements always appear first.
     */
    function _renderBanner(target, anns) {
        var el = document.getElementById('flexAnnBanner_' + target);
        if (!el) return;

        var visible = anns.filter(function (a) {
            return a.active && (a.target === target || a.target === 'all');
        });

        var ordered = visible.filter(function (a) { return  a.pinned; })
                             .concat(visible.filter(function (a) { return !a.pinned; }));

        if (!ordered.length) {
            el.style.display = 'none';
            return;
        }
        el.style.display = '';
        el.innerHTML = ordered.map(function (a) { return FlexAnn.renderViewerCard(a); }).join('');
    }
})();
// ── END VIEWER ANNOUNCEMENTS ──────────────────────────────────────────────────



function closeSmCourseDd() {
    var dd = document.getElementById('smCourseDd');
    var btn = document.getElementById('smCourseBtn');
    if (dd) dd.style.display = 'none';
    if (btn) { btn.style.borderColor = ''; btn.style.boxShadow = ''; }
}
// Close smCourseDd when clicking outside
document.addEventListener('mousedown', function(e) {
    var dd = document.getElementById('smCourseDd');
    var btn = document.getElementById('smCourseBtn');
    if (dd && dd.style.display === 'block' && btn && !btn.contains(e.target) && !dd.contains(e.target)) {
        closeSmCourseDd();
    }
});

</script>