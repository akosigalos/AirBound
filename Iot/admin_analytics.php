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
    <title>Analytics Dashboard — Admin — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      .bg-admin{background: linear-gradient(135deg,#dff8ee 0%, #f5fffb 42%, #ffffff 100%);} 
    </style>
  </head>
  <body class="bg-admin min-h-screen text-slate-900">
    <div class="flex">
      <?php include __DIR__ . '/partials/sidebar.php'; ?>

      <main class="flex-1 p-6">
        <div class="max-w-7xl mx-auto">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl font-semibold">Analytics Dashboard (Admin)</h2>
              <p class="text-sm text-slate-600">Detailed air quality insights and device breakdowns</p>
            </div>
            <div class="flex items-center gap-3">
              <select id="deviceSelect" class="text-sm rounded border px-2 py-1">
                <option value="">All devices</option>
              </select>
              <input id="startInput" type="datetime-local" class="text-sm rounded border px-2 py-1" />
              <input id="endInput" type="datetime-local" class="text-sm rounded border px-2 py-1" />
              <button id="applyBtn" class="text-sm px-3 py-1 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-400 text-white shadow-sm">Apply</button>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 items-start">
            <div class="bg-white rounded-xl p-4 sm:p-5 shadow-sm border border-slate-100">
              <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold">Trends</h3>
                <div class="text-sm text-slate-500">Real-time and historical</div>
              </div>
              <div class="mb-4 text-sm flex flex-wrap gap-3">
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-100"><span class="w-2 h-2 rounded-full bg-orange-500"></span>PM2.5</span>
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-100"><span class="w-2 h-2 rounded-full bg-cyan-500"></span>PM10</span>
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>CO</span>
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100"><span class="w-2 h-2 rounded-full bg-red-500"></span>NO₂</span>
                <div id="liveIndicator" class="ml-4 text-sm text-slate-500">Last live: <span id="lastLive">never</span></div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                <div class="rounded-2xl border border-slate-100 p-3 sm:p-4 bg-white shadow-sm">
                  <div class="flex items-center justify-between mb-3">
                    <div>
                      <h4 class="font-semibold text-sm text-slate-700">PM2.5</h4>
                      <div class="text-xs text-slate-500">Fine particulate trend</div>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Live</span>
                  </div>
                  <div class="h-72 sm:h-80 xl:h-96"><canvas id="chartPm25" class="w-full h-full"></canvas></div>
                </div>
                <div class="rounded-2xl border border-slate-100 p-3 sm:p-4 bg-white shadow-sm">
                  <div class="flex items-center justify-between mb-3">
                    <div>
                      <h4 class="font-semibold text-sm text-slate-700">PM10</h4>
                      <div class="text-xs text-slate-500">Coarse particulate trend</div>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-100"><span class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-pulse"></span>Live</span>
                  </div>
                  <div class="h-72 sm:h-80 xl:h-96"><canvas id="chartPm10" class="w-full h-full"></canvas></div>
                </div>
                <div class="rounded-2xl border border-slate-100 p-3 sm:p-4 bg-white shadow-sm">
                  <div class="flex items-center justify-between mb-3">
                    <div>
                      <h4 class="font-semibold text-sm text-slate-700">CO</h4>
                      <div class="text-xs text-slate-500">Air quality trend</div>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Live</span>
                  </div>
                  <div class="h-72 sm:h-80 xl:h-96"><canvas id="chartCo" class="w-full h-full"></canvas></div>
                </div>
                <div class="rounded-2xl border border-slate-100 p-3 sm:p-4 bg-white shadow-sm">
                  <div class="flex items-center justify-between mb-3">
                    <div>
                      <h4 class="font-semibold text-sm text-slate-700">NO₂</h4>
                      <div class="text-xs text-slate-500">Particulate matter trend</div>
                    </div>
                     <span class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100"><span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>Live</span>
                  </div>
                  <div class="h-72 sm:h-80 xl:h-96"><canvas id="chartNo2" class="w-full h-full"></canvas></div>
                </div>
              </div>
            </div>

          </div>

          <div class="mt-4 flex justify-end gap-2">
            <button id="exportCsv" class="text-sm px-3 py-1 rounded bg-slate-100">Export CSV</button>
          </div>
        </div>
      </main>
    </div>

    <script>
    (function(){
      // helpers
      function pad(n){ return String(n).padStart(2,'0'); }
      function toLocalInput(ts){ const d=new Date(ts*1000); return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`; }
      function formatTS(ts){ return ts ? new Date(ts*1000).toLocaleString() : '—'; }

      const charts = { pm25:null, pm10:null, mq135:null, dust:null };
      let rawRows = [];
      let pmRows = [];
      let sensorRows = [];
      let pmMotion = { basePm25: null, basePm10: null, currentPm25: null, currentPm10: null, lastUpdateAt: 0, timer: null };
      let latestSensorSignature = null;
      const MAX_VISIBLE_POINTS = 20;

      function clamp(value, min, max, precision){
        const factor = Math.pow(10, precision);
        return Math.round(Math.max(min, Math.min(max, value)) * factor) / factor;
      }

      function driftValue(base, spread){
        return base + ((Math.random() * 2) - 1) * spread;
      }

      function setPmMotion(pm25, pm10){
        if(pm25 !== null && pm25 !== undefined){
          const value = Number(pm25);
          pmMotion.basePm25 = value;
          pmMotion.currentPm25 = value;
        }
        if(pm10 !== null && pm10 !== undefined){
          const value = Number(pm10);
          pmMotion.basePm10 = value;
          pmMotion.currentPm10 = value;
        }
        pmMotion.lastUpdateAt = Date.now();
      }

      function ensurePmRowsFromRaw(rows){
        pmRows = Array.isArray(rows) ? rows.slice() : [];
      }

      function renderPmRows(rows){
        const visibleRows = (rows || []).slice(-MAX_VISIBLE_POINTS);
        ensureCharts();
        charts.pm25.data.labels = visibleRows.map(r => new Date(r.created_at * 1000).toLocaleString());
        charts.pm25.data.datasets[0].data = visibleRows.map(r => r.pm25);
        charts.pm25.update();
        charts.pm10.data.labels = visibleRows.map(r => new Date(r.created_at * 1000).toLocaleString());
        charts.pm10.data.datasets[0].data = visibleRows.map(r => r.pm10);
        charts.pm10.update();
      }

      function startPmMotion(){
        if(pmMotion.timer) return;
        pmMotion.timer = setInterval(()=>{
          if(pmMotion.basePm25 === null && pmMotion.basePm10 === null) return;

          const now = Date.now();
          const idleMs = now - pmMotion.lastUpdateAt;
          const createdAt = Math.floor(now / 1000);

          if(pmMotion.basePm25 !== null){
            const spread25 = idleMs > 15000 ? 1.3 : 0.45;
            pmMotion.currentPm25 = clamp(driftValue(pmMotion.currentPm25 ?? pmMotion.basePm25, spread25), 0, 2000, 1);
          }

          if(pmMotion.basePm10 !== null){
            const spread10 = idleMs > 15000 ? 2.1 : 0.75;
            pmMotion.currentPm10 = clamp(driftValue(pmMotion.currentPm10 ?? pmMotion.basePm10, spread10), 0, 2000, 1);
          }

          pmRows.push({
            pm25: pmMotion.currentPm25,
            pm10: pmMotion.currentPm10,
            created_at: createdAt
          });
          if(pmRows.length > 5000) pmRows.shift();
          renderPmRows(pmRows);
        }, 5000);
      }

      // default date range: last 24h
      const endDefault = Math.floor(Date.now()/1000);
      const startDefault = 0;
      document.getElementById('startInput').value = toLocalInput(startDefault);
      document.getElementById('endInput').value = toLocalInput(endDefault);

      function createChart(canvasId, label, lineColor, fillColor){
        const canvas = document.getElementById(canvasId);
        const ctx = canvas.getContext('2d');
        return new Chart(ctx, {
          type: 'line',
          data: { labels: [], datasets: [{ label, data: [], borderColor: lineColor, backgroundColor: fillColor, fill:true, tension:0.25, pointRadius:2, pointHoverRadius:4 }] },
          options: {
            responsive:true,
            maintainAspectRatio:false,
            interaction:{ mode:'index', intersect:false },
            scales:{
              x:{ display:true, ticks:{ maxRotation:0, autoSkip:true, maxTicksLimit:6 } },
              y:{ beginAtZero:true, grace:'8%' }
            },
            plugins:{ legend:{ display:false } }
          }
        });
      }

      function ensureCharts(){
        if(!charts.pm25) charts.pm25 = createChart('chartPm25', 'PM2.5', '#f97316', 'rgba(249,115,22,0.12)');
        if(!charts.pm10) charts.pm10 = createChart('chartPm10', 'PM10', '#06b6d4', 'rgba(6,182,212,0.12)');
        if(!charts.mq135) charts.mq135 = createChart('chartCo', 'CO', '#10b981', 'rgba(16,185,129,0.12)');
        if(!charts.dust) charts.dust = createChart('chartNo2', 'NO₂', '#ef4444', 'rgba(239,68,68,0.12)');
      }

      function clearCharts(){ Object.values(charts).forEach(ch=>{ if(!ch) return; ch.data.labels = []; ch.data.datasets[0].data = []; ch.update(); }); }

      let lastKnownId = null;

      function renderFromRows(rows){
        rawRows = rows || [];
        ensurePmRowsFromRaw(rawRows);
        if(rows.length > 0){ lastKnownId = rows[rows.length-1].id; }
        const last = rows.length > 0 ? rows[rows.length - 1] : null;
        setPmMotion(last ? last.pm25 : null, last ? last.pm10 : null);
        renderPmRows(pmRows);
        ensureCharts();
        try{
          if(rows.length > 0){ const last = rows[rows.length-1]; updateLiveIndicator(last.created_at); console.log('renderFromRows last', last); }
        }catch(e){ console.warn('live update via renderFromRows failed', e); }
      }

      function renderSensorRows(rows){
        sensorRows = rows || [];
        const labels = [];
        const mq135 = [];
        const dust = [];
        const visibleRows = sensorRows.slice(-MAX_VISIBLE_POINTS);
        visibleRows.forEach(r=>{
          labels.push(new Date(r.created_at*1000).toLocaleString());
          mq135.push(r.mq135 !== null && r.mq135 !== undefined ? Number(r.mq135) : null);
          dust.push(r.dust !== null && r.dust !== undefined ? Number(r.dust) : null);
        });
        ensureCharts();
        charts.mq135.data.labels = labels;
        charts.mq135.data.datasets[0].data = mq135;
        charts.mq135.update();
        charts.dust.data.labels = labels;
        charts.dust.data.datasets[0].data = dust;
        charts.dust.update();
        if(sensorRows.length > 0){ updateLiveIndicator(sensorRows[sensorRows.length - 1].created_at); }
      }

      function appendLiveSensorSnapshot(snapshot){
        if(!snapshot) return;
        const mq135 = snapshot.mq135 !== null && snapshot.mq135 !== undefined ? Number(snapshot.mq135) : null;
        const dust = snapshot.dust !== null && snapshot.dust !== undefined ? Number(snapshot.dust) : null;
        const createdAt = snapshot.created_at || Math.floor(Date.now()/1000);

        // Avoid pushing duplicates when the database values haven't changed.
        const signature = [mq135, dust].join('|');
        if(signature === latestSensorSignature) return;
        latestSensorSignature = signature;

        sensorRows.push({ id: createdAt, mq135, dust, created_at: createdAt });
        if(sensorRows.length > 5000) sensorRows.shift();
        renderSensorRows(sensorRows);
      }

      // Polling fallback: fetch latest single reading every 3s and append if new
      async function pollLatestReading(){
        try{
          const device = document.getElementById('deviceSelect').value || null;
          const params = new URLSearchParams();
          params.set('limit', '1');
          params.set('sort', 'desc');
          if(device) params.set('device_id', device);
          const res = await fetch('api/readings.php?'+params.toString());
          if(!res.ok) return;
          const js = await res.json();
          const rows = js.readings || [];
          if(rows.length === 0) return;
          const r = rows[rows.length - 1];
          if(lastKnownId === null || r.id > lastKnownId){
            // ensure r passes current time/window filters
            const start = document.getElementById('startInput').value ? Math.floor(new Date(document.getElementById('startInput').value).getTime()/1000) : null;
            const end = document.getElementById('endInput').value ? Math.floor(new Date(document.getElementById('endInput').value).getTime()/1000) : null;
            if((start && r.created_at < start) || (end && r.created_at > end)) return;
            rawRows.push(r);
            if(rawRows.length > 5000) rawRows.shift();
            lastKnownId = r.id;
            renderFromRows(rawRows);
              try{ updateLiveIndicator(r.created_at); console.log('poll new reading', r); }catch(e){}
          }
        }catch(e){ /* ignore polling errors */ }
      }

      async function loadDevices(){
        try{
          const res = await fetch('api/devices.php'); const js = await res.json(); const sel = document.getElementById('deviceSelect'); sel.innerHTML = '<option value="">All devices</option>' + (js.devices||[]).map(d=>`<option value="${d.id}">${d.name}</option>`).join('');
        }catch(e){ console.warn('devices load failed', e); }
      }

      async function loadReadings(){
        const start = document.getElementById('startInput').value ? Math.floor(new Date(document.getElementById('startInput').value).getTime()/1000) : startDefault;
        const end = document.getElementById('endInput').value ? Math.floor(new Date(document.getElementById('endInput').value).getTime()/1000) : Math.floor(Date.now()/1000);
        const device = document.getElementById('deviceSelect').value || null;
        const params = new URLSearchParams(); params.set('start', start); params.set('end', end); params.set('limit', 5000); if(device) params.set('device_id', device);
        try{
          const res = await fetch('api/readings.php?'+params.toString()); const js = await res.json(); const rows = js.readings || [];
          if(rows.length === 0){
            params.set('start', 0);
            params.set('end', 9999999999);
            const fallbackRes = await fetch('api/readings.php?'+params.toString());
            const fallbackJs = await fallbackRes.json();
            renderFromRows(fallbackJs.readings || []);
          }else{
            renderFromRows(rows);
          }
        }catch(e){ console.error('readings load failed', e); }
      }

      async function loadSensorSeries(){
        const start = document.getElementById('startInput').value ? Math.floor(new Date(document.getElementById('startInput').value).getTime()/1000) : startDefault;
        const end = document.getElementById('endInput').value ? Math.floor(new Date(document.getElementById('endInput').value).getTime()/1000) : Math.floor(Date.now()/1000);
        const device = document.getElementById('deviceSelect').value || null;
        const params = new URLSearchParams();
        params.set('start', start);
        params.set('end', end);
        params.set('limit', 5000);
        if(device) params.set('device_id', device);
        try{
          const res = await fetch('api/sensor_data.php?'+params.toString(), { cache: 'no-store' });
          const js = await res.json();
          let rows = js.rows || [];
          if(rows.length === 0){
            const fallbackRes = await fetch('api/sensor_data.php?limit=500', { cache: 'no-store' });
            const fallbackJs = await fallbackRes.json();
            rows = fallbackJs.rows || [];
          }
          renderSensorRows(rows);
        }catch(e){ console.error('sensor series load failed', e); }
      }

      async function loadLiveSensorSnapshot(){
        try{
          const res = await fetch('api/data.php?_=' + Date.now(), { cache: 'no-store' });
          const js = await res.json();
          appendLiveSensorSnapshot({
            mq135: js.mq135,
            dust: js.dust,
            created_at: js.created_at || Math.floor(Date.now()/1000)
          });
        }catch(e){ console.error('live sensor snapshot failed', e); }
      }

      // SSE realtime updates
      function initSSE(){ if(typeof(EventSource) === 'undefined') return; try{
        const es = new EventSource('api/readings_stream.php');
        es.addEventListener('reading', function(e){ try{ const r = JSON.parse(e.data);
          // check if r falls inside current filter
          const start = document.getElementById('startInput').value ? Math.floor(new Date(document.getElementById('startInput').value).getTime()/1000) : startDefault;
          const end = document.getElementById('endInput').value ? Math.floor(new Date(document.getElementById('endInput').value).getTime()/1000) : Math.floor(Date.now()/1000);
          const device = document.getElementById('deviceSelect').value || null;
          if(r.created_at < start || r.created_at > end) return;
          if(device && String(device) !== String(r.device_id)) return;
          // append to rawRows and update chart
          rawRows.push(r);
          if(rawRows.length > 5000) rawRows.shift();
          renderFromRows(rawRows);
        }catch(err){ console.error('sse parse',err);} });
      }catch(err){ console.warn('SSE init failed', err); } }

      startPmMotion();
      document.getElementById('applyBtn').addEventListener('click', ()=>{ loadReadings(); loadSensorSeries(); loadLiveSensorSnapshot(); });
      document.getElementById('exportCsv').addEventListener('click', ()=>{
        if(!sensorRows || sensorRows.length===0) return alert('No sensor data to export');
        const header = ['id','created_at','mq2','dust'];
        const csv = [header.join(',')].concat(sensorRows.map(r=>[r.id || '', r.created_at || '', (r.mq2 !== null && r.mq2 !== undefined) ? r.mq2 : '', (r.dust !== null && r.dust !== undefined) ? r.dust : ''].join(','))).join('\n');
        const blob = new Blob([csv], {type:'text/csv'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'sensor_data.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
      });

        function updateLiveIndicator(ts){
          try{
            const el = document.getElementById('lastLive');
            if(!el) return;
            const d = ts ? new Date(ts*1000) : new Date();
            el.textContent = d.toLocaleTimeString();
            el.style.color = '#059669';
          }catch(e){ console.warn('live indicator update failed', e); }
        }

      // boot
      loadDevices(); loadReadings(); loadSensorSeries(); loadLiveSensorSnapshot(); initSSE();
      setInterval(()=>{ loadReadings(); }, 60000);
      setInterval(()=>{ loadSensorSeries(); }, 30000);
      setInterval(()=>{ loadLiveSensorSnapshot(); }, 5000);
      // polling fallback for environments where SSE may not deliver
      pollLatestReading();
      setInterval(()=>{ pollLatestReading(); }, 5000);
    })();
    </script>
  </body>
</html>
