/* ═══════════════════════════════════════════════════
   Učim Nemački – main.js
═══════════════════════════════════════════════════ */

'use strict';

// ── CSRF helpers ───────────────────────────────────
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Fetch wrapper ──────────────────────────────────
async function apiPost(url, data = {}) {
    const form = new FormData();
    form.append('csrf_token', csrfToken());
    Object.entries(data).forEach(([k, v]) => form.append(k, v));
    try {
        const res = await fetch(url, { method: 'POST', body: form });
        return await res.json();
    } catch (e) {
        return { error: 'Mrežna greška. Proverite internet vezu.' };
    }
}

// ── Toast Notifications ────────────────────────────
function showToast(message, type = 'info', title = '', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const titles = { success: 'Uspešno!', error: 'Greška!', warning: 'Upozorenje!', info: 'Informacija' };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <div class="toast-body">
            <div class="toast-title">${title || titles[type] || ''}</div>
            <div class="toast-msg">${message}</div>
        </div>
        <button class="toast-close" aria-label="Zatvori">×</button>`;

    container.appendChild(toast);
    toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));

    if (duration > 0) setTimeout(() => removeToast(toast), duration);
}

function removeToast(toast) {
    toast.style.animation = 'fadeOut .3s ease forwards';
    setTimeout(() => toast.remove(), 300);
}

// ── Hamburger / Mobile menu ────────────────────────
const hamburger  = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');

if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
        const open = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('open', open);
        hamburger.setAttribute('aria-expanded', String(open));
        mobileMenu.setAttribute('aria-hidden', String(!open));
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
            mobileMenu.classList.remove('open');
            hamburger.classList.remove('open');
            hamburger.setAttribute('aria-expanded', 'false');
        }
    });
}

// ── User dropdown ──────────────────────────────────
const userDropdown  = document.getElementById('userDropdown');
const userAvatarBtn = document.getElementById('userAvatarBtn');

if (userAvatarBtn && userDropdown) {
    userAvatarBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = userDropdown.classList.toggle('open');
        userAvatarBtn.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', () => {
        userDropdown.classList.remove('open');
        userAvatarBtn.setAttribute('aria-expanded', 'false');
    });
}

// ── Navbar scroll effect ───────────────────────────
const navbar = document.getElementById('navbar');
if (navbar) {
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
}

// ── Dark / light mode toggle ───────────────────────
const darkToggleBtn = document.getElementById('darkToggle');
const prefersDark   = window.matchMedia('(prefers-color-scheme: dark)');

function applyDark(on) {
    document.body.classList.toggle('dark', on);
    if (darkToggleBtn) darkToggleBtn.textContent = on ? '☀️' : '🌙';
    localStorage.setItem('darkMode', on ? '1' : '0');
}

const storedDark = localStorage.getItem('darkMode');
applyDark(storedDark !== null ? storedDark === '1' : prefersDark.matches);

darkToggleBtn?.addEventListener('click', () => applyDark(!document.body.classList.contains('dark')));

// ── Smooth scroll for anchor links ────────────────
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
        const target = document.querySelector(link.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ── Parrot mascot bubble ───────────────────────────
const parrotBubble  = document.getElementById('parrotBubble');
const parrotMessage = document.getElementById('parrotMessage');
const parrotClose   = document.getElementById('parrotClose');

const parrotMessages = [
    'Zdravo! Učimo zajedno! 🎉',
    'Odličan napredak! Nastavi tako! 💪',
    'Nemački nije težak — samo vežbaj! 🇩🇪',
    'Svaki novi test te čini boljim! ⭐',
    'Sehr gut! Bravo! 🦜',
    'Ne predaj se — uspeh je blizu! 🌟',
    'Danke schön za učenje! 🙏',
    'Übung macht den Meister – Vežba čini majstora! 📚',
    'Guten Morgen! Vreme je za učenje! ☀️',
    'Wunderbar! Čudesno! 🎊',
];

let parrotShown = false;
function showParrotBubble() {
    if (parrotShown || !parrotBubble) return;
    const msg = parrotMessages[Math.floor(Math.random() * parrotMessages.length)];
    if (parrotMessage) parrotMessage.textContent = msg;
    parrotBubble.classList.remove('hidden');
    parrotShown = true;
    setTimeout(() => hideParrotBubble(), 8000);
}

function hideParrotBubble() {
    parrotBubble?.classList.add('hidden');
}

parrotClose?.addEventListener('click', hideParrotBubble);

// Show parrot after a delay on page load
setTimeout(showParrotBubble, 3500);

// ── Number count-up animation ──────────────────────
function animateCount(el, end, duration = 1200) {
    const start    = 0;
    const startTs  = performance.now();
    const step = (ts) => {
        const progress = Math.min((ts - startTs) / duration, 1);
        const ease     = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(start + (end - start) * ease).toLocaleString('sr');
        if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
}

// Trigger count-up when elements come into view
const countObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el  = entry.target;
            const end = parseInt(el.dataset.count ?? el.textContent, 10);
            if (!isNaN(end)) animateCount(el, end);
            countObserver.unobserve(el);
        }
    });
}, { threshold: .3 });

document.querySelectorAll('[data-count]').forEach(el => countObserver.observe(el));

// ── Scroll-reveal animation ────────────────────────
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-slide-up');
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: .1 });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ── Confirm delete helper ──────────────────────────
function confirmDelete(message = 'Da li ste sigurni da želite da obrišete ovaj element?') {
    return window.confirm(message);
}

// ── Countdown timer ────────────────────────────────
class CountdownTimer {
    constructor(el, seconds, onTick, onEnd) {
        this.el      = el;
        this.seconds = seconds;
        this.onTick  = onTick;
        this.onEnd   = onEnd;
        this.interval= null;
    }

    start() {
        this.render();
        this.interval = setInterval(() => {
            this.seconds--;
            this.render();
            if (this.onTick) this.onTick(this.seconds);
            if (this.seconds <= 0) { this.stop(); if (this.onEnd) this.onEnd(); }
        }, 1000);
    }

    stop() { clearInterval(this.interval); }

    render() {
        const m = Math.floor(Math.max(this.seconds, 0) / 60);
        const s = Math.max(this.seconds, 0) % 60;
        if (this.el) this.el.textContent = `${m}:${s.toString().padStart(2, '0')}`;
    }
}

// ── Flashcard flip ─────────────────────────────────
document.querySelectorAll('.flashcard').forEach(card => {
    card.addEventListener('click', () => card.classList.toggle('flipped'));
    card.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.classList.toggle('flipped'); }
    });
});

// ── Search / filter helper ─────────────────────────
function filterList(inputId, listSelector, textSelector) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', () => {
        const q = input.value.toLowerCase().trim();
        document.querySelectorAll(listSelector).forEach(item => {
            const text = item.querySelector(textSelector)?.textContent.toLowerCase() ?? '';
            item.style.display = text.includes(q) ? '' : 'none';
        });
    });
}

// ── Confetti (CSS-only, JS-generated) ─────────────
function launchConfetti(count = 80) {
    const container = document.createElement('div');
    container.className = 'confetti-container';
    document.body.appendChild(container);

    const colors = ['#6B21A8','#9333EA','#16A34A','#F59E0B','#2563EB','#EC4899','#F87171'];
    for (let i = 0; i < count; i++) {
        const piece = document.createElement('div');
        piece.className = 'confetti-piece';
        const color    = colors[Math.floor(Math.random() * colors.length)];
        const left     = Math.random() * 100;
        const delay    = Math.random() * 2;
        const duration = 2.5 + Math.random() * 2;
        piece.style.cssText = `left:${left}%;background:${color};animation-duration:${duration}s;animation-delay:${delay}s;opacity:0`;
        container.appendChild(piece);
    }

    setTimeout(() => container.remove(), 5000);
}

// ── Expose globals ─────────────────────────────────
window.showToast     = showToast;
window.launchConfetti= launchConfetti;
window.CountdownTimer= CountdownTimer;
window.filterList    = filterList;
window.confirmDelete = confirmDelete;
window.apiPost       = apiPost;
