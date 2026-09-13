<?php
session_start();
if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Alerts — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      .bg-ambient{background:linear-gradient(120deg,#eef2ff 0%,#ecfdf5 50%,#f0f9ff 100%)}
      .page-enter{animation:fadeIn 360ms ease both}
      @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
      .alert-card{transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
      .alert-card:hover{transform:translateY(-2px);box-shadow:0 14px 28px -18px rgba(15,23,42,.45)}
      .alert-card.is-unread{border-left:4px solid #f97316}
      .alert-card.is-read{border-left:4px solid #94a3b8}
      .alert-card.is-resolved{border-left:4px solid #10b981;background:linear-gradient(135deg,#fff 0%,#f0fdf4 100%)}
      .filter-control{width:100%;border:1px solid #cbd5e1;border-radius:.625rem;padding:.55rem .7rem;background:#fff;font-size:.875rem;outline:none}
      .filter-control:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.14)}
    </style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased page-enter">
    <div class="flex">
      <?php include __DIR__ . '/partials/sidebar.php'; ?>

      <main class="min-w-0 flex-1 p-4 sm:p-6">
        <div class="max-w-7xl mx-auto">
          <header class="mb-6 overflow-hidden rounded-2xl border border-emerald-100 bg-white shadow-sm">
            <div class="flex flex-col gap-4 bg-gradient-to-r from-emerald-50 via-white to-sky-50 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7 sm:py-6">
              <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-xl text-white shadow-lg shadow-emerald-600/20">⚠</div>
                <div>
                  <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700">AIR-BOUND Control Center</p>
                  <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Alerts Management</h2>
                  <p class="mt-1 text-sm text-slate-600">Review live air-quality events, acknowledge them, and track resolution status.</p>
                </div>
              </div>
              <div class="flex items-center gap-3 self-start rounded-xl border border-white/80 bg-white/80 px-3 py-2 text-sm shadow-sm sm:self-auto"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-500">👤</span><span class="text-slate-600"><?php echo $userName; ?></span></div>
            </div>
          </header>

          <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center">
              <div class="inline-flex w-full rounded-xl bg-slate-100 p-1 sm:w-auto"><button id="btnLive" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition sm:flex-none">Live alerts</button><button id="btnHistory" class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900 sm:flex-none">Alert history</button></div>
              <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600"><span class="inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 font-medium text-orange-700"><span class="h-2 w-2 rounded-full bg-orange-500"></span>Unread</span><span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600"><span class="h-2 w-2 rounded-full bg-slate-400"></span>Read</span><span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Resolved</span></div>
              <div class="flex items-center gap-3 xl:ml-auto"><div class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">Unread <span id="unreadCount" class="ml-1 rounded-md bg-rose-600 px-1.5 py-0.5 text-xs text-white">0</span></div><div id="liveSyncBadge" class="text-xs text-slate-500">Live sync: waiting...</div></div>
            </div>

            <div id="historyControls" class="mt-5 hidden border-t border-slate-100 pt-5">
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                <select id="filterLevel" class="filter-control"><option value="">All levels</option><option value="unhealthy">Unhealthy</option><option value="very_unhealthy">Very Unhealthy</option><option value="emergency">Emergency</option></select>
                <select id="filterDevice" class="filter-control"><option value="">All devices</option></select>
                <input id="filterStart" type="date" class="filter-control" aria-label="Start date" />
                <input id="filterEnd" type="date" class="filter-control" aria-label="End date" />
                <button id="applyFilters" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Apply filters</button>
                <button id="clearFilters" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Clear</button>
                <div class="flex items-center gap-1 rounded-lg bg-slate-50 p-1"><button id="rangeToday" class="rounded px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-white">Today</button><button id="rangeWeek" class="rounded px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-white">Week</button><button id="rangeMonth" class="rounded px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-white">Month</button><button id="rangeYear" class="rounded px-2 py-1.5 text-xs font-medium text-slate-600 hover:bg-white">Year</button></div>
              </div>
            </div>
          </section>

          <section aria-live="polite"><div id="alertList" class="space-y-3"><div data-empty-live class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">Waiting for live alerts...</div></div></section>
          <div id="pagination" class="mt-5 flex flex-wrap items-center justify-center gap-3"><button id="prevPage" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Previous</button><div class="rounded-lg bg-white px-4 py-2 text-sm text-slate-600 shadow-sm">Page <span id="pageDisplay" class="font-semibold text-slate-900">1</span></div><button id="nextPage" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Next</button></div>
        </div>
      </main>
    </div>

    <script>
    (function(){
      const alertList = document.getElementById('alertList');
      const btnLive = document.getElementById('btnLive');
      const btnHistory = document.getElementById('btnHistory');
      const historyControls = document.getElementById('historyControls');
      const applyFilters = document.getElementById('applyFilters');
      const clearFilters = document.getElementById('clearFilters');
      const filterLevel = document.getElementById('filterLevel');
      const filterDevice = document.getElementById('filterDevice');
      const filterStart = document.getElementById('filterStart');
      const filterEnd = document.getElementById('filterEnd');
      const unreadEl = document.getElementById('unreadCount');
      const liveSyncBadge = document.getElementById('liveSyncBadge');

      let viewMode = 'live';
      let unread = 0;
      let currentPage = 1;
      const PAGE_LIMIT = 10;
      let liveRefreshTimer = null;
      let liveLatestAlertId = 0;
      let liveLatestSensorId = 0;
      let lastLiveSyncAt = null;
      let liveInitialized = false;

      function scrollAlertsToBottom(){
        try{
          window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }catch(e){
          window.scrollTo(0, document.body.scrollHeight);
        }
      }

      function escapeHtml(s){ if(s===null||s===undefined) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

      function renderAlertItem(a){
        const ts = a.created_at ? new Date(a.created_at*1000).toLocaleString() : '';
        const device = escapeHtml(a.device_name || 'Device');
        const rawLevel = String(a.level || 'Alert');
        const level = escapeHtml(rawLevel.replace(/_/g, ' '));
        const levelKey = rawLevel.toLowerCase();
        const isResolved = Boolean(a.resolved_at);
        const isRead = Boolean(a.read_at) || isResolved;
        const state = isResolved ? 'resolved' : (isRead ? 'read' : 'unread');
        const stateLabel = isResolved ? 'Resolved' : (isRead ? 'Read' : 'Unread');
        const stateClass = isResolved ? 'bg-emerald-100 text-emerald-700' : (isRead ? 'bg-slate-100 text-slate-600' : 'bg-orange-100 text-orange-700');
        const severityClass = levelKey === 'emergency' ? 'bg-rose-100 text-rose-700 ring-rose-200' : (levelKey.includes('very') ? 'bg-red-100 text-red-700 ring-red-200' : 'bg-orange-100 text-orange-700 ring-orange-200');

        const LABELS = { pm25: 'PM2.5', pm10: 'PM10', mq135: 'CO', dust: 'NO₂' };
        const key = a.pollutant || null;
        const label = key ? (LABELS[key] || key.toUpperCase()) : null;
        const unit = a.unit ? escapeHtml(a.unit) : '';
        const value = (a.value !== null && a.value !== undefined) ? Number(a.value).toFixed(1) : '';
        const color = a.color || '#f97316';
        const icon = a.icon || '⚠️';

        let pollutantHtml = '';
        if(key){
          pollutantHtml = `
            <div class="flex min-w-0 items-start gap-3 sm:gap-4">
              <div class="flex-shrink-0">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl text-xl text-white shadow-sm" style="background:${escapeHtml(color)}">${escapeHtml(icon)}</div>
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1"><span class="font-semibold text-slate-800">${device}</span><span class="text-xs text-slate-400">•</span><time class="text-xs text-slate-500">${ts}</time></div>
                <div class="mt-2 flex flex-wrap items-end gap-x-3 gap-y-1"><span class="text-sm font-semibold uppercase tracking-wide text-slate-500">${escapeHtml(label || '')}</span><span class="text-2xl font-bold tracking-tight text-slate-900">${escapeHtml(value)} <span class="text-sm font-medium text-slate-500">${unit}</span></span></div>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">${escapeHtml(a.message || 'Air-quality threshold has been exceeded.')}</p>
              </div>
            </div>
          `;
        } else {
          pollutantHtml = `
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-x-2 gap-y-1"><strong class="text-slate-800">${device}</strong><span class="text-xs text-slate-400">•</span><time class="text-xs text-slate-500">${ts}</time></div>
              <p class="mt-2 text-sm leading-6 text-slate-600">${escapeHtml(a.message || 'Air-quality threshold has been exceeded.')}</p>
            </div>
          `;
        }

        return `
          <article class="alert-card is-${state} rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-id="${a.id}" data-state="${state}">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="min-w-0 flex-1">${pollutantHtml}</div>
              <div class="flex flex-wrap items-center gap-2 lg:max-w-[15rem] lg:justify-end">
                <span class="rounded-full px-2.5 py-1 text-xs font-bold capitalize ring-1 ${severityClass}">${level}</span>
                <span data-alert-state class="rounded-full px-2.5 py-1 text-xs font-semibold ${stateClass}">${stateLabel}</span>
              </div>
            </div>
            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
              <span class="text-xs text-slate-400">Alert #${escapeHtml(a.id)}${a.lat !== null && a.lng !== null ? ' • Location recorded' : ''}</span>
              <div class="flex flex-wrap gap-2">
                <button class="mark-read rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-45" ${isRead ? 'disabled' : ''}>${isRead ? 'Marked read' : 'Mark as read'}</button>
                <button class="resolve rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-45" ${isResolved ? 'disabled' : ''}>${isResolved ? 'Resolved' : 'Resolve'}</button>
                <button class="delete rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">Delete</button>
              </div>
            </div>
          </article>
        `;
      }

      function prependAlert(a){
        if(!alertList.dataset.view) alertList.dataset.view = 'live';
        if(alertList.querySelector('[data-empty-live]')) alertList.innerHTML = '';
        alertList.insertAdjacentHTML('afterbegin', renderAlertItem(a));
        attachActions();
      }

      function setUnread(n){ unread = n; unreadEl.textContent = String(n); }

      function setLiveSyncBadge(inserted, sensorId, paused){
        lastLiveSyncAt = new Date();
        const time = lastLiveSyncAt.toLocaleTimeString();
        const insertedText = inserted ? ` +${inserted} new alerts` : '';
        const stateText = paused ? 'Paused' : 'Running';
        liveSyncBadge.textContent = `Live sync (Demo Sensor): ${stateText} • ${time}${insertedText}`;
        liveSyncBadge.className = paused ? 'text-xs text-slate-500' : (inserted ? 'text-xs text-emerald-700' : 'text-xs text-slate-500');
      }

      async function seedLiveAlerts(){
        try{
          const res = await fetch('api/live_alerts.php?seed=1&limit=10');
          const js = await res.json();
          const alerts = js.alerts || [];
          if(typeof js.synced_until_sensor_id !== 'undefined'){
            liveLatestSensorId = Number(js.synced_until_sensor_id || liveLatestSensorId);
          }
          setLiveSyncBadge(0, liveLatestSensorId, Boolean(js.paused));
          if(alerts.length){
            alertList.dataset.view = 'live';
            alertList.innerHTML = alerts.map(renderAlertItem).join('');
            liveInitialized = true;
            liveLatestAlertId = Number(alerts[alerts.length - 1].id || 0);
          }else{
            alertList.dataset.view = 'live';
            alertList.innerHTML = '<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm" data-empty-live>Waiting for live alerts...</div>';
          }
        }catch(e){
          console.error('seed live load', e);
        }
      }

      async function fetchLiveAlerts(){
        try{
          if(!liveInitialized){
            await seedLiveAlerts();
          }

          const res = await fetch('api/live_alerts.php?limit=10&last_sensor_id=' + encodeURIComponent(liveLatestSensorId));
          const js = await res.json();
          const alerts = js.alerts || [];
          if(typeof js.synced_until_sensor_id !== 'undefined'){
            liveLatestSensorId = Number(js.synced_until_sensor_id || liveLatestSensorId);
          }
          setLiveSyncBadge(Number(js.inserted || 0), liveLatestSensorId, Boolean(js.paused));
          if(alerts.length){
            alertList.dataset.view = 'live';
            alerts.forEach(prependAlert);
            liveLatestAlertId = Number(alerts[alerts.length - 1].id || liveLatestAlertId);
          }else{
            if(!liveInitialized){
              liveLatestAlertId = 0;
              alertList.dataset.view = 'live';
              alertList.innerHTML = '<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm" data-empty-live>Waiting for live alerts...</div>';
            }
          }

          try{
            const unreadRes = await fetch('api/alerts.php?unread=1&limit=1');
            const unreadJs = await unreadRes.json();
            setUnread((unreadJs.alerts || []).length);
          }catch(err){ /* ignore unread counter issues */ }
        }catch(e){
          console.error('live load', e);
          alertList.innerHTML = '<div class="rounded-2xl border border-rose-100 bg-rose-50 p-6 text-center text-sm text-rose-700">Unable to load live alerts. Please try again.</div>';
        }
      }

      function startLiveRefresh(){
        stopLiveRefresh();
        liveRefreshTimer = setInterval(()=>{
          if(viewMode === 'live') fetchLiveAlerts();
        }, 3000);
      }

      function stopLiveRefresh(){
        if(liveRefreshTimer){
          clearInterval(liveRefreshTimer);
          liveRefreshTimer = null;
        }
      }

      async function fetchDevices(){
        try{ const res = await fetch('api/devices.php'); const js = await res.json(); const devices = js.devices || []; filterDevice.innerHTML = '<option value="">All devices</option>' + devices.map(d=>`<option value="${d.id}">${escapeHtml(d.name)}</option>`).join(''); }catch(e){ console.warn('devices load failed', e); }
      }

      async function fetchHistory(){
        const params = new URLSearchParams();
        if(filterLevel.value) params.set('level', filterLevel.value);
        if(filterDevice.value) params.set('device_id', filterDevice.value);
        if(filterStart.value) params.set('start', Math.floor(new Date(filterStart.value).getTime()/1000));
        if(filterEnd.value) params.set('end', Math.floor((new Date(filterEnd.value).getTime() + (24*3600*1000) - 1)/1000));
        params.set('limit', PAGE_LIMIT);
        params.set('page', currentPage);
        try{
          const res = await fetch('api/alerts.php?'+params.toString());
          const js = await res.json();
          const alerts = js.alerts || [];
          if(alerts.length === 0) alertList.innerHTML = '<div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">No alerts match the selected filters.</div>'; else alertList.innerHTML = alerts.map(renderAlertItem).join('');
          attachActions();
          // pagination UI
          const pagination = document.getElementById('pagination');
          const pageDisplay = document.getElementById('pageDisplay');
          const prevBtn = document.getElementById('prevPage');
          const nextBtn = document.getElementById('nextPage');
          if(pagination){
            pagination.classList.remove('hidden');
            pageDisplay.textContent = String(js.page || currentPage);
            prevBtn.disabled = currentPage <= 1;
            // if fewer results than limit, disable next
            nextBtn.disabled = alerts.length < PAGE_LIMIT;
          }
        }catch(e){ console.error('history load', e); }
      }

      function attachActions(){
        alertList.querySelectorAll('.mark-read').forEach(btn=> btn.onclick = async (ev)=>{
          const card = ev.target.closest('[data-id]');
          const id = card.getAttribute('data-id');
          const res = await fetch('api/alerts.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({action:'mark_read', id: parseInt(id)})});
          if(!res.ok) return;
          if(viewMode === 'history') { fetchHistory(); return; }
          card.dataset.state = 'read';
          card.classList.remove('is-unread'); card.classList.add('is-read');
          const statePill = card.querySelector('[data-alert-state]');
          if(statePill){ statePill.className = 'rounded-full px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-600'; statePill.textContent = 'Read'; }
          ev.target.textContent = 'Marked read'; ev.target.disabled = true;
          setUnread(Math.max(0, unread - 1));
        });

        alertList.querySelectorAll('.resolve').forEach(btn=> btn.onclick = async (ev)=>{
          const card = ev.target.closest('[data-id]');
          const id = card.getAttribute('data-id');
          const res = await fetch('api/alerts.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({action:'resolve', id: parseInt(id)})});
          if(!res.ok) return;
          if(viewMode === 'history') { fetchHistory(); return; }
          const wasUnread = card.dataset.state === 'unread';
          card.dataset.state = 'resolved';
          card.classList.remove('is-unread','is-read'); card.classList.add('is-resolved');
          const statePill = card.querySelector('[data-alert-state]');
          if(statePill){ statePill.className = 'rounded-full px-2.5 py-1 text-xs font-semibold bg-emerald-100 text-emerald-700'; statePill.textContent = 'Resolved'; }
          const readBtn = card.querySelector('.mark-read'); if(readBtn){ readBtn.textContent = 'Marked read'; readBtn.disabled = true; }
          ev.target.textContent = 'Resolved'; ev.target.disabled = true;
          if(wasUnread) setUnread(Math.max(0, unread - 1));
        });

        alertList.querySelectorAll('.delete').forEach(btn=> btn.onclick = async (ev)=>{
          if(!confirm('Delete this alert?')) return;
          const id = ev.target.closest('[data-id]').getAttribute('data-id');
          await fetch('api/alerts.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({action:'delete', id: parseInt(id)})});
          if(viewMode === 'history') fetchHistory(); else ev.target.closest('[data-id]').remove();
        });
      }

      // UI controls
      function setActiveViewButton(active, inactive){
        active.classList.add('bg-emerald-600', 'text-white', 'shadow-sm');
        active.classList.remove('text-slate-600');
        inactive.classList.remove('bg-emerald-600', 'text-white', 'shadow-sm');
        inactive.classList.add('text-slate-600');
      }
      btnLive.addEventListener('click', ()=>{ viewMode = 'live'; setActiveViewButton(btnLive, btnHistory); historyControls.classList.add('hidden'); fetchLiveAlerts(); startLiveRefresh(); });
      btnHistory.addEventListener('click', ()=>{ viewMode = 'history'; setActiveViewButton(btnHistory, btnLive); historyControls.classList.remove('hidden'); stopLiveRefresh(); fetchHistory(); });

      applyFilters.addEventListener('click', ()=>{ currentPage = 1; if(viewMode === 'history') fetchHistory(); });
      clearFilters.addEventListener('click', ()=>{ filterLevel.value=''; filterDevice.value=''; filterStart.value=''; filterEnd.value=''; currentPage = 1; if(viewMode === 'history') fetchHistory(); });

      // quick range buttons
      function toDateInput(d){ const yyyy = d.getFullYear(); const mm = String(d.getMonth()+1).padStart(2,'0'); const dd = String(d.getDate()).padStart(2,'0'); return `${yyyy}-${mm}-${dd}`; }
      document.getElementById('rangeToday').addEventListener('click', ()=>{ const now = new Date(); filterStart.value = toDateInput(now); filterEnd.value = toDateInput(now); currentPage = 1; if(viewMode === 'history') fetchHistory(); });
      document.getElementById('rangeWeek').addEventListener('click', ()=>{ const now = new Date(); const start = new Date(now.getTime() - (7*24*3600*1000)); filterStart.value = toDateInput(start); filterEnd.value = toDateInput(now); currentPage = 1; if(viewMode === 'history') fetchHistory(); });
      document.getElementById('rangeMonth').addEventListener('click', ()=>{ const now = new Date(); const start = new Date(now.getTime() - (30*24*3600*1000)); filterStart.value = toDateInput(start); filterEnd.value = toDateInput(now); currentPage = 1; if(viewMode === 'history') fetchHistory(); });
      document.getElementById('rangeYear').addEventListener('click', ()=>{ const now = new Date(); const start = new Date(now.getTime() - (365*24*3600*1000)); filterStart.value = toDateInput(start); filterEnd.value = toDateInput(now); currentPage = 1; if(viewMode === 'history') fetchHistory(); });

      // pagination buttons
      document.getElementById('prevPage').addEventListener('click', ()=>{ if(currentPage > 1){ currentPage--; fetchHistory(); } });
      document.getElementById('nextPage').addEventListener('click', ()=>{ currentPage++; fetchHistory(); });

      // init
      document.addEventListener('DOMContentLoaded', ()=>{ fetchDevices(); btnLive.click(); });
      window.addEventListener('beforeunload', stopLiveRefresh);
    })();
    </script>
  </body>
</html>
