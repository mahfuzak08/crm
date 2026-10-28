/* Lake View CRM — vanilla JS helpers. No inline scripts (CSP: script-src 'self'). */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const meta = n => (document.querySelector(`meta[name="${n}"]`) || {}).content || '';
  const BASE = meta('base-url');

  /* ---------- fetch wrapper (CSRF + AJAX headers) ---------- */
  async function api(url, opts = {}) {
    const headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': meta('csrf-token') }, opts.headers || {});
    if (opts.json !== false) headers.Accept = 'application/json';
    const res = await fetch(url, Object.assign({ credentials: 'same-origin' }, opts, { headers }));
    if (res.status === 401) { location.reload(); throw new Error('unauthenticated'); }
    return res;
  }
  window.CRM = { api, toast, money, cents };

  function toast(message, type = 'success') {
    const box = $('#toasts');
    if (!box || !window.bootstrap) { alert(message); return; }
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0`;
    el.setAttribute('role', 'status');
    el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    el.querySelector('.toast-body').textContent = message;
    box.appendChild(el);
    new bootstrap.Toast(el, { delay: 3500 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  /* ---------- money helpers (integer cents, display only — server recalculates) ---------- */
  function cents(v) {
    v = String(v ?? '').replace(/,/g, '').trim();
    if (!/^-?\d+(\.\d{0,2})?$/.test(v)) return 0;
    const neg = v.startsWith('-'); if (neg) v = v.slice(1);
    const [i, d = ''] = v.split('.');
    const c = parseInt(i || '0', 10) * 100 + parseInt((d + '00').slice(0, 2), 10);
    return neg ? -c : c;
  }
  function money(c) {
    const neg = c < 0; c = Math.abs(c);
    const s = Math.floor(c / 100).toLocaleString('en-US') + '.' + String(c % 100).padStart(2, '0');
    return (neg ? '-' : '') + s;
  }
  const plain = c => (c < 0 ? '-' : '') + Math.floor(Math.abs(c) / 100) + '.' + String(Math.abs(c) % 100).padStart(2, '0');

  /* ---------- sidebar ---------- */
  document.addEventListener('click', e => {
    const t = e.target.closest('[data-sidebar-open]');
    if (t) { $('#sidebar').classList.add('show'); return; }
    if (e.target.closest('[data-sidebar-close]')) { $('#sidebar').classList.remove('show'); return; }
    const g = e.target.closest('[data-toggle-group]');
    if (g) {
      const grp = g.parentElement; grp.classList.toggle('open');
      g.setAttribute('aria-expanded', grp.classList.contains('open'));
    }
  });

  /* ---------- auto-submit selects ---------- */
  document.addEventListener('change', e => {
    if (e.target.matches('[data-autosubmit]')) e.target.form.submit();
    if (e.target.matches('[data-autosubmit-ajax]')) e.target.form.requestSubmit();
  });

  /* ---------- log "shared via WhatsApp/email" then follow the link ---------- */
  document.addEventListener('click', e => {
    const a = e.target.closest('[data-log-share]');
    if (!a) return;
    const fd = new FormData(); fd.append('channel', a.dataset.channel || 'whatsapp');
    api(a.dataset.logShare, { method: 'POST', body: fd, keepalive: true }).catch(() => {});
  });

  /* ---------- confirmation for dangerous actions ---------- */
  let confirmed = null;
  function askConfirm(msg) {
    return new Promise(resolve => {
      if (!window.bootstrap) { resolve(window.confirm(msg)); return; }
      let m = $('#confirmModal');
      if (!m) {
        m = document.createElement('div');
        m.id = 'confirmModal'; m.className = 'modal fade'; m.tabIndex = -1;
        m.innerHTML = '<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-body pt-4"><div class="d-flex gap-3"><i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i><div class="msg"></div></div></div><div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger ok">Confirm</button></div></div></div>';
        document.body.appendChild(m);
      }
      m.querySelector('.msg').textContent = msg;
      const modal = bootstrap.Modal.getOrCreateInstance(m);
      let result = false;
      m.querySelector('.ok').onclick = () => { result = true; modal.hide(); };
      m.addEventListener('hidden.bs.modal', () => resolve(result), { once: true });
      modal.show();
    });
  }
  document.addEventListener('submit', async e => {
    const f = e.target;
    const msg = f.dataset.confirm || (e.submitter && e.submitter.dataset.confirm);
    if (msg && confirmed !== f) {
      e.preventDefault();
      if (await askConfirm(msg)) {
        confirmed = f;
        // carry a "reason" prompt if requested
        if (f.dataset.reason !== undefined) {
          const r = prompt('Reason (optional):', '') ?? null;
          if (r === null) { confirmed = null; return; }
          let h = f.querySelector('input[name="reason"]');
          if (!h) { h = document.createElement('input'); h.type = 'hidden'; h.name = 'reason'; f.appendChild(h); }
          h.value = r;
        }
        if (e.submitter && e.submitter.name) {
          const h = document.createElement('input'); h.type = 'hidden'; h.name = e.submitter.name; h.value = e.submitter.value; f.appendChild(h);
        }
        f.requestSubmit ? f.requestSubmit() : f.submit();
      }
      return;
    }
    confirmed = null;
    // double-submit guard for POST forms
    if ((f.method || '').toLowerCase() === 'post' && !f.hasAttribute('data-ajax-form')) {
      if (f.dataset.submitting) { e.preventDefault(); return; }
      f.dataset.submitting = '1';
      $$('button[type="submit"], button:not([type])', f).forEach(b => { b.disabled = true; });
      setTimeout(() => { delete f.dataset.submitting; $$('button', f).forEach(b => { b.disabled = false; }); }, 8000);
    }
  });

  /* ---------- AJAX forms (status updates etc.) ---------- */
  document.addEventListener('submit', async e => {
    const f = e.target;
    if (!f.hasAttribute('data-ajax-form') || e.defaultPrevented) return;
    e.preventDefault();
    try {
      const res = await api(f.action, { method: 'POST', body: new FormData(f) });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.ok === false) { toast(data.message || 'Request failed', 'danger'); return; }
      toast(data.message || 'Saved');
      if (data.redirect && f.dataset.ajaxForm === 'redirect') location.href = data.redirect;
      else if (f.dataset.ajaxForm === 'reload') setTimeout(() => location.reload(), 400);
    } catch (err) { toast('Network error', 'danger'); }
  });

  /* ---------- live list filtering + AJAX pagination ---------- */
  function initLiveList(form) {
    const target = $(form.dataset.target);
    if (!target) return;
    let timer, ctrl;
    const load = async url => {
      ctrl && ctrl.abort(); ctrl = new AbortController();
      target.classList.add('loading');
      try {
        const res = await api(url, { signal: ctrl.signal, json: false });
        if (res.ok) { target.innerHTML = await res.text(); history.replaceState(null, '', url); }
      } catch (_) { /* aborted */ }
      target.classList.remove('loading');
    };
    const go = () => {
      const p = new URLSearchParams(new FormData(form));
      for (const [k, v] of [...p]) if (v === '') p.delete(k);
      load(form.action.split('?')[0] + (p.toString() ? '?' + p : ''));
    };
    form.addEventListener('input', e => { if (e.target.type === 'search' || e.target.type === 'text') { clearTimeout(timer); timer = setTimeout(go, 300); } });
    form.addEventListener('change', e => { if (e.target.type !== 'search' && e.target.type !== 'text') go(); });
    form.addEventListener('submit', e => { e.preventDefault(); go(); });
    target.addEventListener('click', e => {
      const a = e.target.closest('.pagination a, a.sort-link');
      if (a && a.href) { e.preventDefault(); load(a.href); }
    });
  }
  $$('form[data-live-filter]').forEach(initLiveList);

  /* ---------- remote lookup: <input data-lookup="/api/lookup/customers" data-lookup-target="#customer_id"> ---------- */
  $$('[data-lookup]').forEach(input => {
    const sel = $(input.dataset.lookupTarget);
    let timer;
    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const q = input.value.trim();
        if (q.length < 2) return;
        const res = await api(BASE + input.dataset.lookup + '?q=' + encodeURIComponent(q));
        if (!res.ok) return;
        const items = await res.json();
        const cur = sel.value;
        sel.innerHTML = '<option value="">— select —</option>';
        (items.results || []).forEach(it => {
          const o = document.createElement('option'); o.value = it.id; o.textContent = it.text;
          if (String(it.id) === cur) o.selected = true;
          sel.appendChild(o);
        });
        if (items.results && items.results.length === 1) { sel.value = items.results[0].id; sel.dispatchEvent(new Event('change', { bubbles: true })); }
      }, 300);
    });
  });

  /* ---------- show a block only while a field has a value: data-show-when="name=value" ("name" alone = any value) ---------- */
  $$('[data-show-when]').forEach(el => {
    const [name, want] = el.dataset.showWhen.split('=');
    const form = el.closest('form');
    if (!form) return;
    const box = el.matches('select, input, textarea') ? el.closest('[class*="col"]') || el : el;
    const sync = () => {
      let val = '';
      form.querySelectorAll(`[name="${name}"]`).forEach(f => { if ((f.type !== 'radio' && f.type !== 'checkbox') || f.checked) val = f.value; });
      box.classList.toggle('d-none', want === undefined ? val === '' : val !== want);
    };
    form.addEventListener('change', sync);
    form.addEventListener('input', sync);
    sync();
  });

  /* ---------- number stepper: <div class="input-group"><button data-step="-1">…<input type=number>… ---------- */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-step]');
    if (!b) return;
    const input = b.parentElement.querySelector('input[type="number"]');
    if (!input) return;
    const min = input.min === '' ? -Infinity : Number(input.min);
    input.value = Math.max(min, (parseInt(input.value || '0', 10) || 0) + Number(b.dataset.step));
    input.dispatchEvent(new Event('input', { bubbles: true }));
  });

  /* ---------- share sale / offer calculator ---------- */
  const calcForm = $('[data-share-calc]');
  if (calcForm) {
    const f = n => calcForm.querySelector(`[name="${n}"]`);
    const project = f('project_id'), pkg = f('package_id'), qty = f('quantity');
    const info = $('#share-availability');
    const cards = $('#pkg-cards');
    let proj = null;
    const feeOverride = calcForm.dataset.feeOverride === '1';
    const set = (id, v) => { const el = $(id); if (el) el.textContent = v; };

    // Sale type "Direct" clears the referral fields so they are never submitted by accident.
    calcForm.addEventListener('change', e => {
      if (e.target.name === 'sale_type' && e.target.value === 'direct') {
        if (f('director_id')) f('director_id').value = '';
        if (f('customer_unit_price')) f('customer_unit_price').value = '';
        recalc();
      }
    });

    function renderCards() {
      if (!cards) return;
      cards.innerHTML = '';
      if (!proj) { cards.innerHTML = '<div class="text-muted small">Select a project to see its packages.</div>'; return; }
      const opts = [{ id: '', name: 'Per share', note: 'Any number of shares', price: proj.share_price }]
        .concat(proj.packages.map(p => ({ id: String(p.id), name: p.name, note: `${p.shares_count} share${p.shares_count > 1 ? 's' : ''}`, price: p.price })));
      opts.forEach(o => {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'pkg-card' + (String(pkg.value) === o.id ? ' active' : '');
        b.setAttribute('role', 'radio'); b.setAttribute('aria-checked', String(pkg.value) === o.id ? 'true' : 'false');
        b.innerHTML = '<span class="pkg-name"></span><span class="pkg-note"></span><span class="pkg-price"></span>';
        b.querySelector('.pkg-name').textContent = o.name;
        b.querySelector('.pkg-note').textContent = o.note;
        b.querySelector('.pkg-price').textContent = money(cents(o.price));
        b.addEventListener('click', () => { pkg.value = o.id; pkg.dispatchEvent(new Event('change', { bubbles: true })); });
        cards.appendChild(b);
      });
    }

    async function loadProject() {
      proj = null;
      if (!project.value) { pkg.innerHTML = '<option value="">— none (per share) —</option>'; if (info) info.innerHTML = ''; renderCards(); recalc(); return; }
      const res = await api(BASE + '/api/projects/' + encodeURIComponent(project.value) + '/shares');
      if (!res.ok) return;
      proj = await res.json();
      const keep = pkg.dataset.selected || pkg.value;
      pkg.innerHTML = '<option value="">— none (per share) —</option>';
      proj.packages.forEach(p => {
        const o = document.createElement('option');
        o.value = p.id; o.textContent = `${p.name} (${p.shares_count} shares @ ${money(cents(p.price))})`;
        o.dataset.shares = p.shares_count; o.dataset.price = p.price; o.dataset.name = p.name;
        if (String(p.id) === String(keep)) o.selected = true;
        pkg.appendChild(o);
      });
      // Most sales use a package: preselect the only/first one on a fresh form.
      if (!keep && cards && proj.packages.length) pkg.value = String(proj.packages[0].id);
      pkg.dataset.selected = '';
      if (info) info.innerHTML = `<span class="badge text-bg-success">${proj.available} available</span> <span class="text-muted small">of ${proj.total} · ${proj.sold} sold · ${proj.reserved} reserved</span>`;
      renderCards();
      recalc(true);
    }
    function recalc(fromProject) {
      const opt = pkg.selectedOptions[0];
      const q = Math.max(0, parseInt(qty.value || '0', 10));
      let shares = q, unit = proj ? cents(proj.share_price) : cents(f('unit_price').value);
      const isPkg = opt && opt.value;
      if (isPkg) { shares = q * parseInt(opt.dataset.shares, 10); unit = cents(opt.dataset.price); }
      if (proj) f('unit_price').value = plain(unit);
      const sub = unit * q;
      if (proj && (!feeOverride || fromProject === true)) {
        f('registration_fee').value = plain(cents(proj.registration_fee) * shares);
        f('development_fee').value = plain(cents(proj.development_fee) * shares);
      }
      const disc = cents(f('discount').value);
      const fees = cents(f('registration_fee').value) + cents(f('development_fee').value) + cents(f('other_fee').value);
      const total = sub - disc + fees;
      set('#calc-shares', shares); set('#calc-subtotal', money(sub)); set('#calc-total', money(total)); set('#bar-total', money(total));
      set('#sum-discount', disc ? '−' + money(disc) : '0.00'); set('#sum-fees', money(fees));
      if (proj) {
        set('#sum-item', proj.project_name || (project.selectedOptions[0] || {}).textContent || '');
        set('#sum-line', `${isPkg ? opt.dataset.name : 'Per share'} · ${q} × ${money(unit)}`);
      } else { set('#sum-item', 'No project selected'); set('#sum-line', '—'); }
      set('#qty-help', isPkg ? `Number of "${opt.dataset.name}" packages (${shares} share${shares === 1 ? '' : 's'})` : 'Number of shares');
      const paid = f('initial_payment');
      if (paid) { set('#sum-paid', money(cents(paid.value))); set('#calc-due', money(total - cents(paid.value))); }
      const custUnit = f('customer_unit_price');
      if (custUnit) {
        const ok = custUnit.value && f('director_id') && f('director_id').value;
        const profit = ok ? (cents(custUnit.value) - unit) * q : 0;
        set('#calc-cust-total', ok ? money(total + profit) : '—'); set('#calc-profit', ok ? money(profit) : '—');
        set('#bar-sub', ok ? `Customer pays ${money(total + profit)} · profit ${money(profit)}` : '');
      }
      if (cards) $$('.pkg-card', cards).forEach((c, i) => {
        const on = (i === 0 && !isPkg) || (isPkg && proj && String(proj.packages[i - 1]?.id) === String(opt.value));
        c.classList.toggle('active', !!on); c.setAttribute('aria-checked', on ? 'true' : 'false');
      });
      const warn = $('#calc-warning');
      if (warn) warn.classList.toggle('d-none', !(proj && shares > proj.available));
    }
    project.addEventListener('change', loadProject);
    calcForm.addEventListener('input', e => { if (e.target !== project) recalc(); });
    pkg.addEventListener('change', () => recalc(true));
    if (project.value) loadProject(); else { renderCards(); recalc(); }
  }

  /* ---------- invoice line items ---------- */
  const inv = $('[data-invoice-items]');
  if (inv) {
    const body = $('tbody', inv), tpl = $('#item-row-template');
    const recalc = () => {
      let sub = 0;
      $$('tr', body).forEach(tr => {
        const qh = cents(tr.querySelector('[name="items[qty][]"]').value); // qty in hundredths
        const p = cents(tr.querySelector('[name="items[price][]"]').value);
        const amt = Math.round(qh * p / 100);
        tr.querySelector('.amt').textContent = money(amt); sub += amt;
      });
      const form = inv.closest('form');
      const disc = cents(form.querySelector('[name="discount"]').value);
      const pct = form.querySelector('[name="tax_percent"]').value || '0';
      const base = sub - disc;
      const bp = Math.round(parseFloat(pct) * 100) || 0;
      const tax = Math.floor((base * bp + 5000) / 10000);
      const other = cents(form.querySelector('[name="other_charge"]').value);
      $('#inv-subtotal').textContent = money(sub); $('#inv-tax').textContent = money(tax);
      $('#inv-total').textContent = money(base + tax + other);
    };
    inv.closest('form').addEventListener('input', recalc);
    inv.addEventListener('click', e => {
      if (e.target.closest('[data-add-row]')) { body.appendChild(tpl.content.cloneNode(true)); recalc(); }
      if (e.target.closest('[data-remove-row]') && $$('tr', body).length > 1) { e.target.closest('tr').remove(); recalc(); }
    });
    recalc();
  }

  /* ---------- payment: invoice due lookup ---------- */
  const payInv = $('[data-invoice-due]');
  if (payInv) {
    const show = () => {
      const o = payInv.selectedOptions[0];
      const el = $('#invoice-due-info');
      if (el) el.textContent = o && o.dataset.due ? 'Due: ' + money(cents(o.dataset.due)) : '';
      const amt = payInv.form.querySelector('[name="amount"]');
      if (amt && o && o.dataset.due && !amt.value) amt.value = o.dataset.due;
    };
    payInv.addEventListener('change', show); show();
  }

  /* ---------- copy to clipboard ---------- */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-copy]');
    if (!b) return;
    navigator.clipboard.writeText(b.dataset.copy).then(() => toast('Copied to clipboard'));
  });

  /* ---------- permission matrix select all / none ---------- */
  document.addEventListener('click', e => {
    if (e.target.id === 'permAll' || e.target.id === 'permNone') $$('.perm').forEach(c => { if (!c.disabled) c.checked = e.target.id === 'permAll'; });
  });

  /* ---------- print ---------- */
  document.addEventListener('click', e => { if (e.target.closest('[data-print]')) window.print(); });

  /* ---------- SVG charts from <script type="application/json" data-chart> ---------- */
  const NS = 'http://www.w3.org/2000/svg';
  const svgEl = (tag, attrs) => { const el = document.createElementNS(NS, tag); for (const k in attrs) el.setAttribute(k, attrs[k]); return el; };
  const short = n => Math.abs(n) >= 1e7 ? (n / 1e7).toFixed(1) + 'Cr' : Math.abs(n) >= 1e5 ? (n / 1e5).toFixed(1) + 'L' : Math.abs(n) >= 1e3 ? (n / 1e3).toFixed(0) + 'K' : String(Math.round(n));

  $$('script[data-chart]').forEach(node => {
    let cfg; try { cfg = JSON.parse(node.textContent); } catch (_) { return; }
    const holder = document.getElementById(node.dataset.chart);
    if (!holder) return;
    const W = 640, H = 240, P = { l: 44, r: 10, t: 12, b: 26 };
    const svg = svgEl('svg', { viewBox: `0 0 ${W} ${H}`, class: 'chart', role: 'img', 'aria-label': cfg.title || 'chart' });
    const labels = cfg.labels || [], series = cfg.series || [];
    let max = Math.max(1, ...series.flatMap(s => s.data.map(Number)));
    if (cfg.count) max = Math.max(4, Math.ceil(max / 4) * 4); // whole-number axis for counts
    const iw = W - P.l - P.r, ih = H - P.t - P.b;
    for (let i = 0; i <= 4; i++) {
      const y = P.t + ih - ih * i / 4;
      svg.appendChild(svgEl('line', { x1: P.l, x2: W - P.r, y1: y, y2: y, class: 'grid' }));
      const t = svgEl('text', { x: P.l - 6, y: y + 3, 'text-anchor': 'end' }); t.textContent = cfg.count ? String(Math.round(max * i / 4)) : short(max * i / 4); svg.appendChild(t);
    }
    const bw = iw / Math.max(1, labels.length);
    labels.forEach((l, i) => { const t = svgEl('text', { x: P.l + bw * i + bw / 2, y: H - 8, 'text-anchor': 'middle' }); t.textContent = l; svg.appendChild(t); });
    const n = series.length;
    series.forEach((s, si) => {
      if (cfg.type === 'line') {
        const pts = s.data.map((v, i) => `${P.l + bw * i + bw / 2},${P.t + ih - ih * Number(v) / max}`).join(' ');
        svg.appendChild(svgEl('polyline', { points: pts, fill: 'none', stroke: s.color, 'stroke-width': 2.5, 'stroke-linejoin': 'round' }));
        s.data.forEach((v, i) => {
          const c = svgEl('circle', { cx: P.l + bw * i + bw / 2, cy: P.t + ih - ih * Number(v) / max, r: 3, fill: s.color });
          const tt = svgEl('title', {}); tt.textContent = `${labels[i]}: ${money(Math.round(Number(v) * 100))}`; c.appendChild(tt); svg.appendChild(c);
        });
      } else {
        const gw = bw * 0.7, w = gw / n;
        s.data.forEach((v, i) => {
          const h = ih * Number(v) / max;
          const r = svgEl('rect', { x: P.l + bw * i + (bw - gw) / 2 + w * si, y: P.t + ih - h, width: Math.max(1, w - 2), height: h, rx: 2, fill: s.color });
          const tt = svgEl('title', {}); tt.textContent = `${s.name} · ${labels[i]}: ${cfg.count ? v : money(Math.round(Number(v) * 100))}`; r.appendChild(tt); svg.appendChild(r);
        });
      }
    });
    holder.appendChild(svg);
    const lg = document.createElement('div'); lg.className = 'chart-legend mt-1';
    series.forEach(s => { const sp = document.createElement('span'); sp.innerHTML = `<i style="background:${s.color}"></i>`; sp.append(s.name); lg.appendChild(sp); });
    holder.appendChild(lg);
  });

  /* ---------- phone "app" shell ---------- */
  // Back arrow: go back inside the app when we came from it, otherwise follow the link (dashboard).
  document.addEventListener('click', e => {
    const a = e.target.closest('a[data-back]');
    if (!a) return;
    if (history.length > 1 && document.referrer.startsWith(location.origin)) { e.preventDefault(); history.back(); }
  });
  // Search icon in the app bar opens the global search under it.
  document.addEventListener('click', e => {
    if (!e.target.closest('[data-search-toggle]')) return;
    const f = $('#topSearch');
    if (!f) return;
    document.body.classList.toggle('search-open');
    if (document.body.classList.contains('search-open')) f.querySelector('input').focus();
  });

  // Floating "+" button for the page's main create action (e.g. "New Lead").
  const mainAction = $$('.page-actions a.btn-brand').find(a => a.querySelector('.bi-plus-lg, .bi-cart-plus, .bi-person-plus'));
  if (mainAction) {
    const fab = document.createElement('a');
    fab.href = mainAction.href; fab.className = 'fab'; fab.setAttribute('aria-label', mainAction.textContent.trim());
    fab.innerHTML = '<i class="bi bi-plus-lg"></i>';
    document.body.appendChild(fab);
    document.body.classList.add('has-fab');
  }

  // List filters: on phones keep only the search box, the rest behind a "Filters" button.
  $$('form.filters').forEach(form => {
    const extra = $$(':scope > :not(.search):not([type="hidden"])', form);
    if (!extra.length) return;
    const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'btn btn-light filter-toggle';
    btn.innerHTML = '<i class="bi bi-funnel"></i><span class="visually-hidden">Filters</span>';
    const active = extra.some(el => el.value);
    if (active) btn.classList.add('active');
    btn.addEventListener('click', () => form.classList.toggle('open'));
    form.prepend(btn);
    const search = $('.search', form);
    if (search) form.prepend(search);
  });

  // Tables become stacked cards on phones: label every cell with its column header.
  function labelTables(root) {
    $$('table.table', root).forEach(t => {
      if (t.closest('.d-none.d-md-block') || t.dataset.cards) return;
      const heads = $$(':scope > thead th', t).map(th => th.textContent.trim());
      if (!heads.length) return;
      t.dataset.cards = '1'; t.classList.add('table-cards');
      $$(':scope > tbody > tr, :scope > tfoot > tr', t).forEach(tr => {
        const cells = $$(':scope > td, :scope > th', tr);
        // "Subtotal | 1,000" style rows (label cell spanning columns): show as one label/value line.
        if (cells.length === 2 && cells[0].colSpan > 1) { tr.classList.add('tr-kv'); return; }
        let col = 0;
        cells.forEach(td => {
          if (td.colSpan >= heads.length) td.classList.add('td-full');
          else if (heads[col]) td.dataset.label = heads[col];
          col += td.colSpan || 1;
        });
      });
    });
  }
  labelTables(document);
  // Lists refreshed by AJAX filtering/pagination.
  new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(n => { if (n.nodeType === 1) labelTables(n); })))
    .observe($('.content') || document.body, { childList: true, subtree: true });
})();
