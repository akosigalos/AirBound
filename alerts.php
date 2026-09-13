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
    <style>html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roberto,"Helvetica Neue",Arial}.bg-ambient{background: linear-gradient(120deg,#eef2ff 0%, #ecfdf5 50%, #f0f9ff 100%);} .page-enter{animation:fadeIn 360ms ease both}@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}</style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased page-enter">
    <div class="flex">
      <?php include __DIR__ . '/partials/sidebar.php'; ?>

      <main class="flex-1 p-6">
        <div class="max-w-7xl mx-auto px-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl font-semibold">Alerts Management</h2>
              <p class="text-sm text-slate-600">Live alerts from monitored devices. Default view: live-only.</p>
            </div>
            <div class="text-sm text-slate-500">User: <?php echo $userName; ?></div>
          </div>

          <div class="mb-4 flex items-center gap-3">
            <div class="flex items-center gap-2">
              <button id="btnLive" class="px-3 py-1 rounded bg-emerald-600 text-white">Live</button>
              <button id="btnHistory" class="px-3 py-1 rounded bg-slate-100">History</button>
            </div>
            <div id="historyControls" class="ml-4 hidden items-center gap-2">
              <div class="flex items-center gap-2">
                <button id="rangeToday" class="text-sm px-2 py-1 rounded bg-slate-100">Today</button>
                <button id="rangeWeek" class="text-sm px-2 py-1 rounded bg-slate-100">Week</button>
                <button id="rangeMonth" class="text-sm px-2 py-1 rounded bg-slate-100">Month</button>
                <button id="rangeYear" class="text-sm px-2 py-1 rounded bg-slate-100">Year</button>
              </div>
              <select id="filterLevel" class="text-sm rounded border px-2 py-1">
                <option value="">All levels</option>
                <option value="unhealthy">Unhealthy</option>
                <option value="very_unhealthy">Very Unhealthy</option>
                <option value="emergency">Emergency</option>
              </select>
              <select id="filterDevice" class="text-sm rounded border px-2 py-1"><option value="">All devices</option></select>
              <input id="filterStart" type="date" class="text-sm rounded border px-2 py-1" />
              <input id="filterEnd" type="date" class="text-sm rounded border px-2 py-1" />
              <button id="applyFilters" class="text-sm px-3 py-1 rounded bg-emerald-600 text-white">Apply</button>
              <button id="clearFilters" class="text-sm px-3 py-1 rounded bg-slate-100">Clear</button>
            </div>
            <div class="ml-auto flex flex-col items-end gap-1 text-sm text-slate-500">
              <div>Unread: <span id="unreadCount">0</span></div>
              <div id="liveSyncBadge" class="text-xs text-slate-500">Live sync (Demo Sensor): waiting...</div>
            </div>
          </div>

          <div id="alertList" class="space-y-3">Waiting for live alerts...</div>
          <div id="pagination" class="mt-3 flex justify-center items-center gap-3">
            <button id="prevPage" class="text-sm px-3 py-1 rounded bg-slate-100">Previous Page</button>
            <div class="text-sm text-slate-600">Page <span id="pageDisplay">1</span></div>
            <button id="nextPage" class="text-sm px-3 py-1 rounded bg-slate-100">Next Page</button>
          </div>

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
        const level = escapeHtml(a.level || 'Alert');

        const LABELS = { pm25: 'PM2.5', pm10: 'PM10', mq135: 'CO', dust: 'NO₂' };
        const key = a.pollutant || null;
        const label = key ? (LABELS[key] || key.toUpperCase()) : null;
        const unit = a.unit ? escapeHtml(a.unit) : '';
        const value = (a.value !== null && a.value !== undefined) ? Number(a.value).toFixed(1) : '';
        const color = a.color || '#f97316';
        const icon = a.icon || '⚠️';

        // pollutant display block
        let pollutantHtml = '';
        if(key){
          pollutantHtml = `
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold" style="background:${escapeHtml(color)}">${escapeHtml(icon)}</div>
              </div>
              <div>
                <div class="text-sm text-slate-500">${device} • <span class="text-xs text-slate-400">${ts}</span></div>
                <div class="mt-1 text-sm text-slate-700 font-semibold">${escapeHtml(label || '')}</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">${escapeHtml(value)} <span class="text-sm font-normal text-slate-500">${unit}</span></div>
                <div class="mt-1 text-sm text-slate-500">${escapeHtml(a.message || '')}</div>
              </div>
            </div>
          `;
        } else {
          pollutantHtml = `
            <div>
              <div class="text-sm"><strong>${device}</strong> <span class="text-xs text-slate-500">• ${ts}</span></div>
              <div class="text-sm font-medium">${escapeHtml(a.message || '')}</div>
            </div>
          `;
        }

        // level pill color
        const levelColor = (level && level.toLowerCase().includes('unhealthy')) ? 'text-rose-600' : 'text-amber-600';

        return `
          <div class="p-3 bg-white rounded border flex items-start justify-between" data-id="${a.id}">
            <div class="flex-1">${pollutantHtml}</div>
            <div class="ml-4 flex flex-col items-end gap-3">
              <div class="px-3 py-1 rounded-full bg-slate-50 text-sm font-semibold ${levelColor}">${escapeHtml(level)}</div>
              <div class="flex flex-col items-end">
                <div class="flex gap-2">
                  <button class="mark-read text-xs px-3 py-1 rounded bg-slate-100">Read</button>
                  <button class="resolve text-xs px-3 py-1 rounded bg-emerald-100 text-emerald-700">Resolve</button>
                  <button class="delete text-xs px-3 py-1 rounded bg-rose-100 text-rose-700">Delete</button>
                </div>
              </div>
            </div>
          </div>
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
            alertList.innerHTML = '<div class="text-sm text-slate-500" data-empty-live>Waiting for live alerts...</div>';
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
              alertList.innerHTML = '<div class="text-sm text-slate-500" data-empty-live>Waiting for live alerts...</div>';
            }
          }

          try{
            const unreadRes = await fetch('api/alerts.php?unread=1&limit=1');
            const unreadJs = await unreadRes.json();
            setUnread((unreadJs.alerts || []).length);
          }catch(err){ /* ignore unread counter issues */ }
        }catch(e){
          console.error('live load', e);
          alertList.innerHTML = '<div class="text-sm text-rose-600">Unable to load live alerts.</div>';
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
          if(alerts.length === 0) alertList.innerHTML = '<div class="text-sm text-slate-500">No alerts</div>'; else alertList.innerHTML = alerts.map(renderAlertItem).join('');
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
          const id = ev.target.closest('[data-id]').getAttribute('data-id');
          await fetch('api/alerts.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({action:'mark_read', id: parseInt(id)})});
          if(viewMode === 'history') fetchHistory(); else ev.target.closest('[data-id]').classList.add('opacity-60');
        });

        alertList.querySelectorAll('.resolve').forEach(btn=> btn.onclick = async (ev)=>{
          const id = ev.target.closest('[data-id]').getAttribute('data-id');
          await fetch('api/alerts.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({action:'resolve', id: parseInt(id)})});
          if(viewMode === 'history') fetchHistory(); else ev.target.closest('[data-id]').remove();
        });

        alertList.querySelectorAll('.delete').forEach(btn=> btn.onclick = async (ev)=>{
          if(!confirm('Delete this alert?')) return;
          const id = ev.target.closest('[data-id]').getAttribute('data-id');
          await fetch('api/alerts.php', {method:'POST', headers:{'Content-Type':'application/json'}, credentials:'same-origin', body: JSON.stringify({action:'delete', id: parseInt(id)})});
          if(viewMode === 'history') fetchHistory(); else ev.target.closest('[data-id]').remove();
        });
      }

      // UI controls
      btnLive.addEventListener('click', ()=>{ viewMode = 'live'; btnLive.classList.add('bg-emerald-600'); btnLive.classList.remove('bg-slate-100'); btnHistory.classList.remove('bg-emerald-600'); btnHistory.classList.add('bg-slate-100'); historyControls.classList.add('hidden'); fetchLiveAlerts(); startLiveRefresh(); });
      btnHistory.addEventListener('click', ()=>{ viewMode = 'history'; btnHistory.classList.add('bg-emerald-600'); btnHistory.classList.remove('bg-slate-100'); btnLive.classList.remove('bg-emerald-600'); btnLive.classList.add('bg-slate-100'); historyControls.classList.remove('hidden'); stopLiveRefresh(); fetchHistory(); });

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
