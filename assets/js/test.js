/* ═══════════════════════════════════════════════════
   Učim Nemački – test.js
   Handles all 7 question types
═══════════════════════════════════════════════════ */

'use strict';

class QuizEngine {
    constructor(testData) {
        this.test      = testData.test;
        this.questions = testData.questions;
        this.total     = this.questions.length;
        this.current   = 0;
        this.score     = 0;
        this.maxScore  = this.questions.reduce((s, q) => s + q.points, 0);
        this.answers   = [];
        this.startTime = Date.now();
        this.timer     = null;
        this.timeLeft  = this.test.time_limit;
        this.finished  = false;

        this.wrap        = document.getElementById('quizWrap');
        this.progressBar = document.getElementById('quizProgress');
        this.questionEl  = document.getElementById('questionContent');
        this.timerEl     = document.getElementById('timerDisplay');
        this.qNumEl      = document.getElementById('questionNum');

        // Correct / wrong audio
        this.correctAudio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAA==');
        this.wrongAudio   = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAA==');
        this.answerAudio  = new Audio();

        this._initTimer();
        this._showQuestion();
    }

    // ── Timer ──────────────────────────────────────
    _initTimer() {
        const timerEl = this.timerEl;
        if (!timerEl) return;
        this.timer = new CountdownTimer(
            timerEl,
            this.timeLeft,
            (s) => {
                if (timerEl) {
                    timerEl.parentElement?.classList.toggle('warning', s <= 60 && s > 20);
                    timerEl.parentElement?.classList.toggle('danger',  s <= 20);
                }
            },
            () => this._finish(true)
        );
        this.timer.start();
    }

    // ── Progress ───────────────────────────────────
    _updateProgress() {
        const pct = Math.round((this.current / this.total) * 100);
        if (this.progressBar) {
            this.progressBar.style.width = pct + '%';
        }
        if (this.qNumEl) {
            this.qNumEl.textContent = `${this.current + 1} / ${this.total}`;
        }
    }

    // ── Show question ──────────────────────────────
    _showQuestion() {
        if (this.current >= this.total) { this._finish(); return; }
        const q = this.questions[this.current];
        this._updateProgress();
        const html = this._buildQuestion(q);
        if (this.questionEl) {
            this.questionEl.innerHTML = html;
            this.questionEl.classList.remove('animate-slide-up');
            void this.questionEl.offsetWidth;
            this.questionEl.classList.add('animate-slide-up');
        }
        this._bindQuestion(q);
    }

    // ── Build question HTML ────────────────────────
    _buildQuestion(q) {
        const media = q.media ? q.media.find(m => m.display_context === 'question') : null;
        let html    = `<div class="question-num">Pitanje ${this.current + 1} od ${this.total} · ${q.points} poena</div>`;

        // Image (type 1 or any with image)
        if (media && media.media_type === 'image') {
            html += `<img src="/uploads/${media.file_path}" class="question-img" alt="Slika pitanja">`;
        }
        // Audio (type 3 or any with audio)
        if (media && media.media_type === 'audio') {
            html += `<div class="mb-2">
                <button class="btn btn-outline btn-sm" onclick="document.getElementById('qAudio').play()">
                    🔊 Poslušaj zvuk
                </button>
                <audio id="qAudio" src="/uploads/${media.file_path}" preload="auto"></audio>
            </div>`;
        }

        html += `<div class="question-text">${this._esc(q.question_text)}</div>`;

        // Build by type
        switch (parseInt(q.type)) {
            case 1: case 2: case 3:
                html += this._buildChoices(q); break;
            case 4:
                html += this._buildMatching(q); break;
            case 5:
                html += this._buildFillBlank(q); break;
            case 6:
                html += this._buildDragOrder(q); break;
            case 7:
                html += this._buildTrueFalse(q); break;
            case 8:
                html += this._buildPictureChoices(q); break;
        }

        if (q.hint_text) {
            html += `<button class="btn btn-ghost btn-sm mt-2" onclick="document.getElementById('hintBox').classList.toggle('hidden')">
                💡 Pokaži nagoveštaj
            </button>
            <div id="hintBox" class="hint-box hidden">${this._esc(q.hint_text)}</div>`;
        }

        html += `<div id="feedbackBox" class="mt-2 hidden"></div>`;
        return html;
    }

    _buildChoices(q) {
        let html = '<div class="options-grid">';
        q.options?.forEach((opt, i) => {
            html += `<button class="option-btn" data-index="${i}" data-correct="${opt.is_correct}"
                data-audio="${this._findOptionAudioPath(q, i)}"
                onclick="quiz._selectChoice(this, ${q.id})">${this._esc(opt.option_text)}</button>`;
        });
        html += '</div>';
        return html;
    }

    _buildTrueFalse(q) {
        const trueAudio  = q.media ? q.media.find(m => m.display_context === 'tf_true' && m.media_type === 'audio') : null;
        const falseAudio = q.media ? q.media.find(m => m.display_context === 'tf_false' && m.media_type === 'audio') : null;
        return `<div class="options-grid">
            <button class="option-btn" data-value="Tačno" data-audio="${trueAudio ? '/uploads/' + this._esc(trueAudio.file_path) : ''}" onclick="quiz._selectTF(this, ${q.id}, 'Tačno')">✅ Tačno</button>
            <button class="option-btn" data-value="Netačno" data-audio="${falseAudio ? '/uploads/' + this._esc(falseAudio.file_path) : ''}" onclick="quiz._selectTF(this, ${q.id}, 'Netačno')">❌ Netačno</button>
        </div>`;
    }

    _buildFillBlank(q) {
        const parts = q.question_text.split('___');
        return `<div class="fill-blank-wrap">
            <span>${this._esc(parts[0] || '')}</span>
            <input type="text" id="blankInput" class="blank-input" placeholder="odgovor" autocomplete="off">
            <span>${this._esc(parts[1] || '')}</span>
        </div>
        <button class="btn btn-primary mt-3" onclick="quiz._submitFill(${q.id})">Potvrdi odgovor</button>`;
    }

    _buildDragOrder(q) {
        const words = JSON.parse(q.correct_answer || '[]');
        const shuffled = [...words].sort(() => Math.random() - .5);
        return `
        <p class="text-muted mb-2">Složite reči u pravilan redosled:</p>
        <div class="drag-words" id="dragTarget"></div>
        <div class="drag-words mt-2" id="dragSource">
            ${shuffled.map((w, i) => `<span class="drag-word" draggable="true" data-word="${this._esc(w)}" id="dw${i}">${this._esc(w)}</span>`).join('')}
        </div>
        <button class="btn btn-primary mt-3" onclick="quiz._submitDrag(${q.id})">Potvrdi redosled</button>`;
    }

    _buildMatching(q) {
        const pairs = JSON.parse(q.correct_answer || '[]');
        const lefts   = pairs.map(p => p[0]);
        const rights  = [...pairs.map(p => p[1])].sort(() => Math.random() - .5);
        return `
        <div class="matching-grid">
            <div id="matchLeft">${lefts.map((w, i) => `<div class="match-item" data-index="${i}">${this._esc(w)}</div>`).join('')}</div>
            <div id="matchRight">${rights.map((w, i) => `<div class="match-item" data-pair="${this._esc(w)}" id="mr${i}">${this._esc(w)}</div>`).join('')}</div>
        </div>
        <button class="btn btn-primary mt-3" onclick="quiz._submitMatching(${q.id})">Potvrdi sparivanje</button>`;
    }

    _buildPictureChoices(q) {
        let html = '<div class="options-grid">';
        q.options?.forEach((opt, i) => {
            const media = q.media ? q.media.find(m => m.display_context === 'option_' + i) : null;
            const imgHtml = media
                ? `<img src="/uploads/${this._esc(media.file_path)}" alt="Opcija ${i + 1}" class="option-img">`
                : `<span class="text-muted">Opcija ${i + 1}</span>`;
            html += `<button class="option-btn option-btn--image" data-index="${i}" data-correct="${opt.is_correct}"
                data-audio="${this._findOptionAudioPath(q, i)}"
                onclick="quiz._selectChoice(this, ${q.id})">${imgHtml}</button>`;
        });
        html += '</div>';
        return html;
    }

    // ── Bind interaction (drag-drop) ───────────────
    _bindQuestion(q) {
        if (parseInt(q.type) === 6) this._bindDragOrder();
        if (parseInt(q.type) === 4) this._bindMatching(q);
    }

    _bindDragOrder() {
        const src  = document.getElementById('dragSource');
        const tgt  = document.getElementById('dragTarget');
        if (!src || !tgt) return;
        let dragEl = null;

        document.querySelectorAll('.drag-word').forEach(el => {
            el.addEventListener('dragstart', () => { dragEl = el; el.classList.add('dragging'); });
            el.addEventListener('dragend',   () => { dragEl = null; el.classList.remove('dragging'); });
        });

        [src, tgt].forEach(zone => {
            zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
            zone.addEventListener('dragleave',  () => zone.classList.remove('drag-over'));
            zone.addEventListener('drop', e => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                if (dragEl) zone.appendChild(dragEl);
            });
        });
    }

    _matchSelected = null;

    _bindMatching(q) {
        document.querySelectorAll('#matchLeft .match-item').forEach(el => {
            el.addEventListener('click', () => {
                document.querySelectorAll('#matchLeft .match-item').forEach(e => e.classList.remove('selected'));
                el.classList.add('selected');
                this._matchSelected = el;
            });
        });
        document.querySelectorAll('#matchRight .match-item').forEach(el => {
            el.addEventListener('click', () => {
                if (!this._matchSelected) return;
                this._matchSelected.dataset.matched = el.dataset.pair;
                this._matchSelected.classList.add('matched');
                el.classList.add('matched');
                this._matchSelected = null;
            });
        });
    }

    // ── Answer handlers ────────────────────────────
    _selectChoice(btn, qId) {
        const q = this.questions[this.current];
        this._playAnswerAudio(btn.dataset.audio);
        document.querySelectorAll('.option-btn').forEach(b => b.disabled = true);
        const correct = btn.dataset.correct === '1';
        btn.classList.add(correct ? 'correct' : 'wrong');
        if (correct) {
            this.score += q.points;
            this._feedback(true);
        } else {
            this._feedback(false);
            // Show correct
            document.querySelectorAll('.option-btn').forEach(b => { if (b.dataset.correct === '1') b.classList.add('correct'); });
        }
        this.answers.push({ question_id: qId, answer: btn.textContent.trim(), correct });
        setTimeout(() => this._next(), 1500);
    }

    _selectTF(btn, qId, value) {
        const q       = this.questions[this.current];
        const correct = value === q.correct_answer;
        this._playAnswerAudio(btn.dataset.audio);
        document.querySelectorAll('.option-btn').forEach(b => {
            b.disabled = true;
            if (b.dataset.value === q.correct_answer) b.classList.add('correct');
        });
        if (!correct) btn.classList.add('wrong');
        if (correct) this.score += q.points;
        this._feedback(correct);
        this.answers.push({ question_id: qId, answer: value, correct });
        setTimeout(() => this._next(), 1500);
    }

    _submitFill(qId) {
        const q   = this.questions[this.current];
        const inp = document.getElementById('blankInput');
        if (!inp) return;
        const val     = inp.value.trim();
        const correct = val.toLowerCase() === (q.correct_answer ?? '').toLowerCase();
        inp.disabled  = true;
        inp.style.borderBottomColor = correct ? '#16A34A' : '#DC2626';
        if (!correct) {
            const fb = document.getElementById('feedbackBox');
            if (fb) { fb.classList.remove('hidden'); fb.innerHTML = `<div class="alert alert-warning">Tačan odgovor: <strong>${this._esc(q.correct_answer)}</strong></div>`; }
        }
        if (correct) this.score += q.points;
        this._feedback(correct);
        this.answers.push({ question_id: qId, answer: val, correct });
        setTimeout(() => this._next(), 1800);
    }

    _submitDrag(qId) {
        const q    = this.questions[this.current];
        const tgt  = document.getElementById('dragTarget');
        const words= [...(tgt?.querySelectorAll('.drag-word') ?? [])].map(e => e.dataset.word);
        const expected = JSON.parse(q.correct_answer || '[]');
        const correct  = JSON.stringify(words) === JSON.stringify(expected);
        if (correct) this.score += q.points;
        this._feedback(correct);
        if (!correct) {
            const fb = document.getElementById('feedbackBox');
            if (fb) { fb.classList.remove('hidden'); fb.innerHTML = `<div class="alert alert-warning">Tačan redosled: <strong>${expected.join(' ')}</strong></div>`; }
        }
        this.answers.push({ question_id: qId, answer: words.join(' '), correct });
        setTimeout(() => this._next(), 2000);
    }

    _submitMatching(qId) {
        const q     = this.questions[this.current];
        const pairs = JSON.parse(q.correct_answer || '[]');
        let right   = 0;
        document.querySelectorAll('#matchLeft .match-item').forEach((el, i) => {
            const expected = pairs[i]?.[1];
            const matched  = el.dataset.matched;
            if (matched && matched === expected) { right++; el.classList.add('matched'); }
            else el.classList.add('mismatched');
        });
        const correct = right === pairs.length;
        if (correct) this.score += q.points;
        else this.score += Math.round(q.points * (right / pairs.length));
        this._feedback(correct);
        this.answers.push({ question_id: qId, answer: String(right) + '/' + pairs.length, correct });
        setTimeout(() => this._next(), 2000);
    }

    // ── Feedback ───────────────────────────────────
    _feedback(correct) {
        const el = document.getElementById('feedbackBox');
        if (!el) return;
        el.classList.remove('hidden');
        if (correct) {
            el.innerHTML = '<div class="alert alert-success">✅ Tačno! Odlično!</div>';
        } else {
            el.innerHTML = '<div class="alert alert-error">❌ Netačno. Pokušaj ponovo na sledećem testu!</div>';
        }
    }

    _playAnswerAudio(path) {
        if (!path) return;
        try {
            this.answerAudio.pause();
            this.answerAudio.src = path;
            this.answerAudio.currentTime = 0;
            this.answerAudio.play().catch(() => {});
        } catch (e) {
            console.debug('Answer audio playback failed', e);
        }
    }

    _findOptionAudioPath(q, index) {
        const optionAudio = q.media ? q.media.find(m => m.display_context === 'option_audio_' + index && m.media_type === 'audio') : null;
        return optionAudio ? '/uploads/' + this._esc(optionAudio.file_path) : '';
    }

    // ── Next question ──────────────────────────────
    _next() {
        this.current++;
        if (this.current >= this.total) this._finish();
        else this._showQuestion();
    }

    // ── Finish ─────────────────────────────────────
    _finish(timeout = false) {
        if (this.finished) return;
        this.finished = true;
        this.timer?.stop();
        const timeSpent = Math.round((Date.now() - this.startTime) / 1000);

        // Save progress via API
        fetch('/api/progress.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: document.querySelector('meta[name="csrf-token"]')?.content,
                test_id:    this.test.id,
                score:      this.score,
                max_score:  this.maxScore,
                time_spent: timeSpent,
                answers:    this.answers,
            })
        }).catch(() => {});

        const pct = this.maxScore > 0 ? Math.round((this.score / this.maxScore) * 100) : 0;
        const url = `/pages/test-result.php?test_id=${this.test.id}&score=${this.score}&max=${this.maxScore}&time=${timeSpent}&timeout=${timeout ? 1 : 0}`;
        window.location.href = url;
    }

    _esc(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }
}

// ── Bootstrap on page load ─────────────────────────
let quiz;
document.addEventListener('DOMContentLoaded', () => {
    const dataEl = document.getElementById('quizData');
    if (!dataEl) return;
    try {
        const testData = JSON.parse(dataEl.textContent);
        quiz = new QuizEngine(testData);
    } catch (e) {
        console.error('Quiz data parse error', e);
        showToast('Greška pri učitavanju testa. Pokušajte ponovo.', 'error');
    }
});
