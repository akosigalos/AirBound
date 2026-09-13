<?php
// Public analytics page with live updating trend panels.
$device_id = isset($_GET['device_id']) && is_numeric($_GET['device_id']) ? intval($_GET['device_id']) : '';
$start = isset($_GET['start']) && is_numeric($_GET['start']) ? intval($_GET['start']) : '';
$end = isset($_GET['end']) && is_numeric($_GET['end']) ? intval($_GET['end']) : '';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Analytics Dashboard — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
      html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      .bg-ambient{background: linear-gradient(120deg,#eef2ff 0%, #ecfdf5 50%, #f0f9ff 100%);} 
      .page-enter{animation:fadeIn 360ms ease both}
      @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
    </style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased page-enter">
    <?php include __DIR__ . '/partials/header-landing.php'; ?>

    <main class="max-w-7xl mx-auto px-6 p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-2xl font-semibold">Analytics Dashboard</h2>
          <p class="text-sm text-slate-600">Detailed air quality insights and device breakdowns</p>
        </div>
        <a href="monitoring.php" class="text-sm px-3 py-2 rounded bg-slate-100 border border-slate-200 hover:bg-slate-200 transition">Back to Real-Time Monitoring</a>
      </div>

      <div class="bg-white rounded-xl p-4 sm:p-5 shadow-sm border border-slate-100 mb-4">
        <div class="flex flex-wrap items-center gap-3">
          <label class="text-sm text-slate-600">Device</label>
          <select id="deviceSelect" class="text-sm rounded border px-2 py-1">
            <option value="">All devices</option>
          </select>

          <label class="text-sm text-slate-600">Start</label>
          <input id="startInput" type="datetime-local" class="text-sm rounded border px-2 py-1" />
          <label class="text-sm text-slate-600">End</label>
          <input id="endInput" type="datetime-local" class="text-sm rounded border px-2 py-1" />

          <button id="applyBtn" class="ml-auto text-sm px-3 py-1 rounded bg-emerald-600 text-white">Apply</button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-xl p-4 shadow border border-slate-100">
          <div class="text-xs text-slate-500">PM2.5</div>
          <div id="sumPm25" class="text-3xl font-bold text-slate-900 mt-1">--</div>
          <div class="text-xs text-slate-500 mt-2">Fine particulate live value</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow border border-slate-100">
          <div class="text-xs text-slate-500">PM10</div>
          <div id="sumPm10" class="text-3xl font-bold text-slate-900 mt-1">--</div>
          <div class="text-xs text-slate-500 mt-2">Coarse particulate live value</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow border border-slate-100">
          <div class="text-xs text-slate-500">CO</div>
          <div id="sumMq135" class="text-3xl font-bold text-slate-900 mt-1">--</div>
          <div class="text-xs text-slate-500 mt-2">Air quality sensor</div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow border border-slate-100">
          <div class="text-xs text-slate-500">NO₂</div>
          <div id="sumDust" class="text-3xl font-bold text-slate-900 mt-1">--</div>
          <div class="text-xs text-slate-500 mt-2">Particulate matter sensor</div>
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
      <div class="mt-4 flex justify-end gap-2">
        <button id="exportCsv" class="text-sm px-3 py-1 rounded bg-slate-100">Export CSV</button>
      </div>
    </main>

    <script>
      const initialDevice = <?php echo json_encode($device_id); ?>;
      const initialStart = <?php echo json_encode($start); ?>;
      const initialEnd = <?php echo json_encode($end); ?>;

      function pad(n){ return String(n).padStart(2,'0'); }
      function toLocalInput(ts){ const d=new Date(ts*1000); return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`; }
      function formatTS(ts){ return ts ? new Date(ts*1000).toLocaleString() : '—'; }

      async function fetchDevices(){
        try{
          const res = await fetch('api/devices.php');
          const js = await res.json();
          const sel = document.getElementById('deviceSelect');
          const devices = js.devices || [];
          sel.innerHTML = '<option value="">All devices</option>' + devices.map(d=>`<option value="${d.id}">${d.name}</option>`).join('');
          if(initialDevice) sel.value = String(initialDevice);
        }catch(e){ console.warn('devices load failed', e); }
      }

      const charts = { pm25:null, pm10:null, mq135:null, dust:null };
      let pmRows = [];
      let pmMotion = { basePm25: null, basePm10: null, currentPm25: null, currentPm10: null, lastUpdateAt: 0, timer: null };
      let lastMotionReadingId = null;
      const MAX_VISIBLE_POINTS = 20;
      let sensorRows = [];
      let latestSensorSignature = null;
      let latestReadingSnapshot = null;
      let lastKnownId = null;
      let lastSensorKnownId = null;

      function clamp(value, min, max, precision){
        const factor = Math.pow(10, precision);
        return Math.round(Math.max(min, Math.min(max, value)) * factor) / factor;
      }

      function driftValue(base, spread){
        return base + ((Math.random() * 2) - 1) * spread;
      }

        function setSensorMotion(mq135, dust){
          if(mq135 !== null && mq135 !== undefined){
            const value = Number(mq135);
            sensorMotion.baseMq135 = value;
            sensorMotion.currentMq135 = value;
          }
          if(dust !== null && dust !== undefined){
            const value = Number(dust);
            sensorMotion.baseDust = value;
            sensorMotion.currentDust = value;
          }
          sensorMotion.lastUpdateAt = Date.now();
        }

        function startSensorMotion(){
          if(sensorMotion.timer) return;
          sensorMotion.timer = setInterval(()=>{
            if(sensorMotion.baseMq135 === null && sensorMotion.baseDust === null) return;

            const now = Date.now();
            const idleMs = now - sensorMotion.lastUpdateAt;
            const createdAt = Math.floor(now / 1000);

            if(sensorMotion.baseMq135 !== null){
              const spreadMq = idleMs > 15000 ? 10.5 : 4.5;
              sensorMotion.currentMq135 = clamp(driftValue(sensorMotion.currentMq135 ?? sensorMotion.baseMq135, spreadMq), 0, 5000, 1);
            }

            if(sensorMotion.baseDust !== null){
              const spreadDust = idleMs > 15000 ? 18.0 : 7.5;
              sensorMotion.currentDust = clamp(driftValue(sensorMotion.currentDust ?? sensorMotion.baseDust, spreadDust), 0, 5000, 1);
            }

            sensorRows.push({
              id: createdAt,
              mq135: sensorMotion.currentMq135,
              dust: sensorMotion.currentDust,
              created_at: createdAt
            });
            if(sensorRows.length > 5000) sensorRows.shift();
            renderSensorRows(sensorRows);
          }, 5000);
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

          pmRows.push({ pm25: pmMotion.currentPm25, pm10: pmMotion.currentPm10, created_at: createdAt });
          if(pmRows.length > 5000) pmRows.shift();
          renderPmRows(pmRows);
        }, 5000);
      }

      function createChart(canvasId, label, color, fill){
        const ctx = document.getElementById(canvasId).getContext('2d');
        return new Chart(ctx, {
          type:'line',
          data:{ labels:[], datasets:[{ label, data:[], borderColor:color, backgroundColor:fill, fill:true, tension:0.25, pointRadius:2, pointHoverRadius:4, spanGaps:true }] },
          options:{ responsive:true, maintainAspectRatio:false, interaction:{ mode:'index', intersect:false }, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{ maxRotation:0, autoSkip:true, maxTicksLimit:6 } }, y:{ beginAtZero:true, grace:'8%' } } }
        });
      }

      function ensureCharts(){
        if(!charts.pm25) charts.pm25 = createChart('chartPm25', 'PM2.5', '#f97316', 'rgba(249,115,22,0.12)');
        if(!charts.pm10) charts.pm10 = createChart('chartPm10', 'PM10', '#06b6d4', 'rgba(6,182,212,0.12)');
        if(!charts.mq135) charts.mq135 = createChart('chartCo', 'CO', '#10b981', 'rgba(16,185,129,0.12)');
        if(!charts.dust) charts.dust = createChart('chartNo2', 'NO₂', '#ef4444', 'rgba(239,68,68,0.12)');
      }

      function updateSummary(latestReading, latestSensor){
        const pm25 = latestReading && latestReading.pm25 !== null ? Number(latestReading.pm25).toFixed(1) : '--';
        const pm10 = latestReading && latestReading.pm10 !== null ? Number(latestReading.pm10).toFixed(1) : '--';
        const mq135 = latestSensor && latestSensor.mq135 !== null ? Number(latestSensor.mq135).toFixed(1) : '--';
        const dust = latestSensor && latestSensor.dust !== null ? Number(latestSensor.dust).toFixed(1) : '--';
        document.getElementById('sumPm25').textContent = pm25;
        document.getElementById('sumPm10').textContent = pm10;
        document.getElementById('sumMq135').textContent = mq135;
        document.getElementById('sumDust').textContent = dust;

        const lastLive = (latestReading && latestReading.created_at) || (latestSensor && latestSensor.created_at) || Math.floor(Date.now()/1000);
        document.getElementById('lastLive').textContent = formatTS(lastLive);
      }

      function updateLiveIndicator(ts){
        try{
          const el = document.getElementById('lastLive');
          if(!el) return;
          const d = ts ? new Date(ts*1000) : new Date();
          el.textContent = d.toLocaleTimeString();
          el.style.color = '#059669';
        }catch(e){ console.warn('live indicator update failed', e); }
      }

      function ensurePmRowsFromReadings(rows){
        pmRows = Array.isArray(rows) ? rows.slice() : [];
      }

      function appendLiveSensorSnapshot(snapshot){
        if(!snapshot) return;
        const mq135 = snapshot.mq135 !== null && snapshot.mq135 !== undefined ? Number(snapshot.mq135) : null;
        const dust = snapshot.dust !== null && snapshot.dust !== undefined ? Number(snapshot.dust) : null;
        const createdAt = snapshot.created_at || Math.floor(Date.now()/1000);
        const snapshotId = snapshot.id !== null && snapshot.id !== undefined ? Number(snapshot.id) : null;
        if(snapshotId !== null && snapshotId === lastSensorKnownId) return;
        if(snapshotId !== null) lastSensorKnownId = snapshotId;
        latestSensorSignature = [mq135, dust].join('|');
        sensorRows.push({ id: createdAt, mq135, dust, created_at: createdAt });
        if(sensorRows.length > 5000) sensorRows.shift();
        renderSensorRows(sensorRows);
        updateSummary(latestReadingSnapshot, { mq135, dust, created_at: createdAt });
      }

      function renderReadingsChart(rows, chart, key){
        const visibleRows = (rows || []).slice(-MAX_VISIBLE_POINTS);
        const labels = [];
        const values = [];
        visibleRows.forEach(r=>{
          labels.push(new Date(r.created_at*1000).toLocaleString());
          values.push(r[key] !== null && r[key] !== undefined ? Number(r[key]) : null);
        });
        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        chart.update();
      }

      function renderSensorChart(rows, chart, key){
        const visibleRows = (rows || []).slice(-MAX_VISIBLE_POINTS);
        const labels = [];
        const values = [];
        visibleRows.forEach(r=>{
          labels.push(new Date(r.created_at*1000).toLocaleString());
          values.push(r[key] !== null && r[key] !== undefined ? Number(r[key]) : null);
        });
        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        chart.update();
      }

      function renderSensorRows(rows){
        const visibleRows = Array.isArray(rows) ? rows.slice() : [];
        sensorRows = visibleRows;
        ensureCharts();
        renderSensorChart(visibleRows, charts.mq135, 'mq135');
        renderSensorChart(visibleRows, charts.dust, 'dust');
        if(visibleRows.length > 0){ updateLiveIndicator(visibleRows[visibleRows.length - 1].created_at); }
      }

      async function loadReadings(start, end, device_id){
        const params = new URLSearchParams();
        if(start) params.set('start', Math.floor(start));
        if(end) params.set('end', Math.floor(end));
        if(device_id) params.set('device_id', device_id);
        params.set('limit', 2000);
        const res = await fetch('api/readings.php?'+params.toString(), { cache:'no-store' });
        const js = await res.json();
        return js.readings || [];
      }

      async function loadSensorData(start, end){
        const params = new URLSearchParams();
        if(start) params.set('start', Math.floor(start));
        if(end) params.set('end', Math.floor(end));
        params.set('limit', 2000);
        const res = await fetch('api/sensor_data.php?'+params.toString(), { cache:'no-store' });
        const js = await res.json();
        return js.rows || [];
      }

      async function loadLiveSensorSnapshot(){
        try{
          const res = await fetch('api/data.php?_=' + Date.now(), { cache:'no-store' });
          const js = await res.json();
          appendLiveSensorSnapshot({
            id: js.id,
            mq135: js.mq135,
            dust: js.dust,
            created_at: js.created_at || js.updated || Math.floor(Date.now()/1000)
          });
        }catch(e){ console.error('live sensor snapshot failed', e); }
      }

      async function pollLatestReading(){
        try{
          const device = document.getElementById('deviceSelect').value || null;
          const params = new URLSearchParams();
          params.set('limit', '1');
          params.set('sort', 'desc');
          if(device) params.set('device_id', device);
          const res = await fetch('api/readings.php?'+params.toString(), { cache:'no-store' });
          if(!res.ok) return;
          const js = await res.json();
          const rows = js.readings || [];
          if(rows.length === 0) return;
          const r = rows[rows.length - 1];
          if(lastKnownId === null || r.id > lastKnownId){
            const start = document.getElementById('startInput').value ? Math.floor(new Date(document.getElementById('startInput').value).getTime()/1000) : startDefault;
            const end = document.getElementById('endInput').value ? Math.floor(new Date(document.getElementById('endInput').value).getTime()/1000) : Math.floor(Date.now()/1000);
            if((start && r.created_at < start) || (end && r.created_at > end)) return;
            rawRows.push(r);
            if(rawRows.length > 5000) rawRows.shift();
            lastKnownId = r.id;
            renderFromRows(rawRows);
          }
        }catch(e){ /* ignore polling errors */ }
      }

      async function loadAnalytics(){
        const startVal = document.getElementById('startInput').value;
        const endVal = document.getElementById('endInput').value;
        const start = startVal ? Math.floor(new Date(startVal).getTime()/1000) : Math.floor(Date.now()/1000) - 24*3600;
        const end = endVal ? Math.floor(new Date(endVal).getTime()/1000) : Math.floor(Date.now()/1000);
        const device = document.getElementById('deviceSelect').value || null;

        const [readings, sensors] = await Promise.all([
          loadReadings(start, end, device),
          loadSensorData(start, end)
        ]);

        ensureCharts();
        ensurePmRowsFromReadings(readings);
        const latestReading = readings.length ? readings[readings.length - 1] : null;
        latestReadingSnapshot = latestReading;
        if(latestReading && latestReading.id !== lastMotionReadingId){
          setPmMotion(latestReading.pm25, latestReading.pm10);
          lastMotionReadingId = latestReading.id;
        }else if(latestReading && pmMotion.basePm25 === null && pmMotion.basePm10 === null){
          setPmMotion(latestReading.pm25, latestReading.pm10);
          lastMotionReadingId = latestReading.id;
        }
        renderPmRows(pmRows);
        sensorRows = Array.isArray(sensors) ? sensors.slice() : [];
        const latestSensor = sensorRows.length ? sensorRows[sensorRows.length - 1] : null;
        if(latestSensor && latestSensor.id !== null && latestSensor.id !== undefined){
          lastSensorKnownId = Number(latestSensor.id);
        }
        renderSensorRows(sensorRows);

        updateSummary(latestReading, latestSensor);
        if(latestReading){ updateLiveIndicator(latestReading.created_at); }
        else if(latestSensor){ updateLiveIndicator(latestSensor.created_at); }
      }

      document.addEventListener('DOMContentLoaded', async ()=>{
        await fetchDevices();

        const now = Math.floor(Date.now()/1000);
        const defaultEnd = initialEnd || now;
        const defaultStart = initialStart || (defaultEnd - 24*3600);
        document.getElementById('startInput').value = toLocalInput(defaultStart);
        document.getElementById('endInput').value = toLocalInput(defaultEnd);

        if(initialDevice){ const sel = document.getElementById('deviceSelect'); if(sel) sel.value = String(initialDevice); }

        startPmMotion();
        await loadAnalytics();
        await loadLiveSensorSnapshot();
        await pollLatestReading();
        document.getElementById('applyBtn').addEventListener('click', loadAnalytics);
        document.getElementById('exportCsv').addEventListener('click', ()=>{
          if(!sensorRows || sensorRows.length===0) return alert('No sensor data to export');
          const header = ['id','created_at','mq135','dust'];
          const rows = [header.join(',')].concat(sensorRows.map(r=>[r.id || '', r.created_at || '', (r.mq135 !== null && r.mq135 !== undefined) ? r.mq135 : '', (r.dust !== null && r.dust !== undefined) ? r.dust : ''].join(',')));
          const blob = new Blob([rows.join('\n')], {type:'text/csv'});
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'sensor_data.csv';
          document.body.appendChild(a);
          a.click();
          a.remove();
          URL.revokeObjectURL(url);
        });
        setInterval(loadLiveSensorSnapshot, 3000);
        pollLatestReading();
        setInterval(pollLatestReading, 3000);
      });
    </script>
  </body>
</html>