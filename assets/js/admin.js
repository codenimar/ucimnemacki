/* ═══════════════════════════════════════════════════
   Učim Nemački – admin.js
═══════════════════════════════════════════════════ */

'use strict';

// ── File upload previews ───────────────────────────
document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
    const previewId = input.dataset.preview;
    const preview   = document.getElementById(previewId);
    if (!preview) return;

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width:200px;max-height:200px;border-radius:8px;margin-top:.5rem;">`;
            };
            reader.readAsDataURL(file);
        } else if (file.type.startsWith('audio/')) {
            const url = URL.createObjectURL(file);
            preview.innerHTML = `<audio controls src="${url}" style="margin-top:.5rem;"></audio>`;
        }
    });
});

// ── Dynamic question type form ─────────────────────
const qTypeSelect = document.getElementById('questionType');
if (qTypeSelect) {
    qTypeSelect.addEventListener('change', () => toggleQuestionFields(qTypeSelect.value));
    toggleQuestionFields(qTypeSelect.value);
}

function toggleQuestionFields(type) {
    const sections = {
        choicesSection:      [1,2,3],
        matchingSection:     [4],
        fillSection:         [5],
        dragSection:         [6],
        tfSection:           [7],
        optionImagesSection: [8],
    };
    Object.entries(sections).forEach(([id, types]) => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('hidden', !types.includes(parseInt(type)));
    });
}

// ── Add / remove option rows ───────────────────────
let optionCount = document.querySelectorAll('.option-row').length || 4;

document.getElementById('addOption')?.addEventListener('click', () => {
    optionCount++;
    const container = document.getElementById('optionsContainer');
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'option-row d-flex align-center gap-2 mb-2';
    row.innerHTML = `
        <input type="text" name="options[]" class="form-control" placeholder="Opcija ${optionCount}" required>
        <input type="file" name="option_audio_files[]" class="form-control" accept="audio/*">
        <label class="d-flex align-center gap-1">
            <input type="radio" name="correct_option" value="${optionCount - 1}"> Tačan
        </label>
        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">✕</button>`;
    container.appendChild(row);
});

// ── Sortable question order ────────────────────────
const sortableList = document.getElementById('sortableQuestions');
if (sortableList) {
    let dragged = null;
    sortableList.querySelectorAll('[draggable]').forEach(item => {
        item.addEventListener('dragstart', () => { dragged = item; item.style.opacity = '.4'; });
        item.addEventListener('dragend',   () => { item.style.opacity = '1'; dragged = null; });
        item.addEventListener('dragover',  (e) => { e.preventDefault(); });
        item.addEventListener('drop', (e) => {
            e.preventDefault();
            if (dragged && dragged !== item) {
                const list   = item.parentNode;
                const items  = [...list.querySelectorAll('[draggable]')];
                const aIdx   = items.indexOf(dragged);
                const bIdx   = items.indexOf(item);
                if (aIdx < bIdx) list.insertBefore(dragged, item.nextSibling);
                else             list.insertBefore(dragged, item);
                _updateSortOrder(list);
            }
        });
    });
}

function _updateSortOrder(list) {
    list.querySelectorAll('[draggable]').forEach((item, i) => {
        const inp = item.querySelector('input[name="sort_order"]');
        if (inp) inp.value = i;
    });
}

// ── Confirm deletes ────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
        if (!confirm(el.dataset.confirm || 'Sigurno?')) e.preventDefault();
    });
});

// ── Admin AJAX delete ──────────────────────────────
document.querySelectorAll('.ajax-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm(btn.dataset.confirm || 'Da li ste sigurni?')) return;
        const res = await apiPost('/api/admin.php', {
            action:      'delete',
            target_type: btn.dataset.type,
            target_id:   btn.dataset.id,
        });
        if (res.success) {
            btn.closest('[data-row]')?.remove();
            showToast('Uspešno obrisano!', 'success');
        } else {
            showToast(res.message || 'Greška pri brisanju.', 'error');
        }
    });
});

// ── Rich text highlight table ──────────────────────
document.querySelectorAll('table.sortable-table th').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
        const table = th.closest('table');
        const col   = [...th.parentNode.children].indexOf(th);
        const asc   = th.dataset.dir !== 'asc';
        th.dataset.dir = asc ? 'asc' : 'desc';
        const rows  = [...table.tBodies[0].rows];
        rows.sort((a, b) => {
            const ta = a.cells[col]?.textContent.trim() ?? '';
            const tb = b.cells[col]?.textContent.trim() ?? '';
            const n  = parseFloat(ta) - parseFloat(tb);
            return isNaN(n) ? (asc ? ta.localeCompare(tb, 'sr') : tb.localeCompare(ta, 'sr')) : (asc ? n : -n);
        });
        rows.forEach(r => table.tBodies[0].appendChild(r));
        table.querySelectorAll('th').forEach(h => h.textContent = h.textContent.replace(/[▲▼]/g,'').trim());
        th.textContent = th.textContent + ' ' + (asc ? '▲' : '▼');
    });
});
