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
    <title>Home — AIR-BOUND Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
      html{scroll-behavior:smooth}
      html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      .bg-ambient{background: linear-gradient(120deg,#eef2ff 0%, #ecfdf5 50%, #f0f9ff 100%);} 
      .page-enter{animation:fadeIn 360ms ease both}
      @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
    </style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased page-enter">
    <div class="flex">
      <?php include __DIR__ . '/partials/sidebar.php'; ?>

      <main class="flex-1 p-6">
        <div class="max-w-7xl mx-auto px-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl font-semibold">Home</h2>
              <p class="text-sm text-slate-600">Panabo City air quality heatmap from stored sensor data.</p>
            </div>
            <div class="text-sm text-slate-500">User: <?php echo $userName; ?></div>
          </div>

          <section id="homeSection" class="bg-white rounded-lg p-4 shadow">
            <div class="flex items-center justify-between mb-2">
              <div>
                <h3 class="font-semibold">Panabo City Heatmap</h3>
                <div class="text-sm text-slate-500">Based on CO and NO₂ levels from stored sensor data.</div>
              </div>
              <div class="text-xs text-slate-500">Auto-refresh: 5 seconds</div>
            </div>
            <div id="homeHeatMap" class="mt-4 h-[34rem] rounded border border-slate-100"></div>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
              <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Cool / Low</span>
              <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-yellow-50 text-yellow-700 border border-yellow-100"><span class="w-2 h-2 rounded-full bg-yellow-500"></span>Moderate</span>
              <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-100"><span class="w-2 h-2 rounded-full bg-orange-500"></span>High</span>
              <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100"><span class="w-2 h-2 rounded-full bg-red-500"></span>Very High</span>
              <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-slate-50 text-slate-700 border border-slate-200"><span class="w-2 h-2 rounded-full bg-slate-700"></span>Sensor hub</span>
            </div>
            <div class="mt-4 rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-emerald-50/40 p-4 shadow-sm">
              <div class="flex items-center justify-between gap-3 mb-3">
                <div>
                  <div class="text-sm font-semibold text-slate-800">CO / NO₂ Thresholds</div>
                  <div class="text-xs text-slate-500">Project-defined bands used for the heatmap color scale.</div>
                </div>
                <div class="hidden sm:flex items-center gap-2 text-[11px] text-slate-500">
                  <span class="px-2 py-1 rounded-full bg-white border border-slate-200">Low</span>
                  <span class="px-2 py-1 rounded-full bg-white border border-slate-200">Mid</span>
                  <span class="px-2 py-1 rounded-full bg-white border border-slate-200">High</span>
                </div>
              </div>
              <div class="grid gap-3 md:grid-cols-3 text-xs">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 shadow-sm">
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-emerald-800">Good</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Low</span>
                  </div>
                  <div class="mt-2 text-emerald-900/90">NO₂ 0–1500</div>
                  <div class="text-emerald-900/90">CO 0–1000</div>
                </div>
                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 shadow-sm">
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-yellow-800">Fair</span>
                    <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">Moderate</span>
                  </div>
                  <div class="mt-2 text-yellow-900/90">NO₂ 1501–2500</div>
                  <div class="text-yellow-900/90">CO 1001–2000</div>
                </div>
                <div class="rounded-lg border border-orange-200 bg-orange-50 p-3 shadow-sm">
                  <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-orange-800">Unhealthy</span>
                    <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700">Warning</span>
                  </div>
                  <div class="mt-2 text-orange-900/90">NO₂ above 2500</div>
                  <div class="text-orange-900/90">CO above 2000</div>
                </div>
              </div>
            </div>
          </section>

          <section class="mt-4 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="rounded-lg bg-white p-4 shadow border border-slate-100">
              <div class="flex items-center justify-between gap-3 mb-3">
                <div>
                  <h3 class="font-semibold">Predictive Analytics</h3>
                  <div class="text-sm text-slate-500">Latest forecast from the predictions table with a live fallback from recent sensor history.</div>
                </div>
                <div class="flex items-center gap-2">
                  <button id="predictionDemoBtn" class="text-xs px-3 py-1 rounded-full border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition">Demo: 30s</button>
                  <div id="predictionRefreshLabel" class="text-xs text-slate-500">Auto-refresh: 60 seconds</div>
                </div>
              </div>
              <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div class="text-xs uppercase tracking-wide text-slate-500">Predicted AQI</div>
                  <div id="predictionAqi" class="mt-2 text-2xl font-semibold text-slate-900">Loading…</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div class="text-xs uppercase tracking-wide text-slate-500">Confidence</div>
                  <div id="predictionConfidence" class="mt-2 text-2xl font-semibold text-slate-900">—</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                  <div class="text-xs uppercase tracking-wide text-slate-500">Forecast window</div>
                  <div id="predictionWindow" class="mt-2 text-2xl font-semibold text-slate-900">—</div>
                </div>
              </div>
              <div id="predictionSummary" class="mt-4 rounded-xl border border-dashed border-slate-200 bg-white p-4 text-sm text-slate-600">Fetching prediction data…</div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow border border-slate-100">
              <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">Near-term Outlook</h3>
                <div id="predictionSource" class="text-xs text-slate-500">—</div>
              </div>
              <div id="predictionForecastList" class="space-y-3 text-sm"></div>
            </div>
          </section>
        </div>
      </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
    <script>
    (function(){
      const CENTER = [7.3003, 125.6804];
      const HEAT_GRADIENT = {0.10:'#16a34a', 0.25:'#4ade80', 0.45:'#a3e635', 0.70:'#eab308', 1.0:'#f97316'};
      let homeHeatMap = null;
      let homeHeatLayer = null;
      let homeDeviceLayer = null;
      let homeHeatRows = [];
      let homeDevices = [];
      let liveSignature = null;
      let animationPhase = 0;
      let heatmapPaused = false;
      let predictionSignature = null;
      let predictionCycleSeconds = 3600;
      let predictionTimer = null;
      let demoDataSeeded = false;

      function generateDemoHeatRows(count){
        const rows = [];
        for(let index = 0; index < count; index += 1){
          const phase = index / Math.max(1, count - 1);
          const angle = phase * Math.PI * 2;
          rows.push({
            mq135: 180 + (Math.sin(angle * 1.7) * 120) + (phase * 420),
            dust: 160 + (Math.cos(angle * 1.3) * 110) + ((1 - phase) * 280),
            created_at: Math.floor(Date.now()/1000) - ((count - index) * 90)
          });
        }
        return rows;
      }

      function formatConfidence(value){
        if(value === null || value === undefined || Number.isNaN(Number(value))) return '—';
        return `${Math.round(Number(value))}%`;
      }

      function renderPredictionBox(data){
        const latest = data && data.latest ? data.latest : null;
        const forecast = data && Array.isArray(data.forecast) ? data.forecast : [];
        const trend = data && data.trend ? data.trend : null;

        const predictionAqi = document.getElementById('predictionAqi');
        const predictionConfidence = document.getElementById('predictionConfidence');
        const predictionWindow = document.getElementById('predictionWindow');
        const predictionSummary = document.getElementById('predictionSummary');
        const predictionForecastList = document.getElementById('predictionForecastList');
        const predictionSource = document.getElementById('predictionSource');

        if(!predictionAqi || !predictionConfidence || !predictionWindow || !predictionSummary || !predictionForecastList || !predictionSource) return;

        if(!latest){
          predictionAqi.textContent = 'No forecast yet';
          predictionConfidence.textContent = '—';
          predictionWindow.textContent = '—';
          predictionSummary.textContent = 'The prediction table is empty and no recent sensor history is available to build a fallback forecast.';
          predictionForecastList.innerHTML = '';
          predictionSource.textContent = 'No data';
          return;
        }

        const color = latest.color || '#64748b';
        predictionAqi.textContent = latest.predicted_aqi || 'Unknown';
        predictionAqi.style.color = color;
        predictionConfidence.textContent = formatConfidence(latest.confidence);
        // Use unix timestamp when available for consistent local time rendering.
        if(latest.prediction_timestamp && Number.isFinite(Number(latest.prediction_timestamp))){
          predictionWindow.textContent = new Date(Number(latest.prediction_timestamp) * 1000).toLocaleString();
        }else{
          predictionWindow.textContent = latest.prediction_time ? new Date(String(latest.prediction_time).replace(' ', 'T')).toLocaleString() : 'Next hour';
        }
        predictionSummary.innerHTML = `
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium" style="background:${color}15;color:${color};border:1px solid ${color}30;">${latest.predicted_aqi}</span>
            <span class="text-slate-500">Trend: <strong class="text-slate-800">${trend && trend.direction ? trend.direction : 'Stable'}</strong></span>
            <span class="text-slate-500">Source: <strong class="text-slate-800">${data && data.trend && data.trend.source ? data.trend.source : 'predictions table'}</strong></span>
          </div>
          <p class="mt-3">This panel shows the latest stored forecast when available. If the table is empty, the app uses recent CO and NO₂ readings to estimate the next few hours.</p>
        `;

        predictionForecastList.innerHTML = forecast.map(item => {
          const itemColor = item.color || '#64748b';
          return `
            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-3 py-3">
              <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">${item.horizon}</div>
                <div class="font-semibold text-slate-900">${item.label}</div>
              </div>
              <div class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-medium" style="background:${itemColor}15;color:${itemColor};border:1px solid ${itemColor}30;">${Math.round((Number(item.score) || 0) * 100)}%</div>
            </div>
          `;
        }).join('');

        predictionSource.textContent = latest.source === 'derived' ? 'Derived from sensor_data' : 'Stored prediction';
      }

      async function ensureDemoData(){
        if(demoDataSeeded) return;
        demoDataSeeded = true;
        try{
          await fetch('api/simulate_reading.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
          });
        }catch(err){
          console.error('demo seed failed', err);
        }
      }

        function percentile(sortedValues, p){
          if(!sortedValues.length) return null;
          const index = (sortedValues.length - 1) * p;
          const lower = Math.floor(index);
          const upper = Math.ceil(index);
          if(lower === upper) return sortedValues[lower];
          return sortedValues[lower] + (sortedValues[upper] - sortedValues[lower]) * (index - lower);
        }

        function combinedHeatValue(mq135, dust){
          const mq = mq135 !== null && mq135 !== undefined ? Number(mq135) : null;
          const ds = dust !== null && dust !== undefined ? Number(dust) : null;
          const mqScore = mq !== null && !Number.isNaN(mq) ? Math.log1p(Math.max(0, mq)) / Math.log1p(5000) : null;
          const dustScore = ds !== null && !Number.isNaN(ds) ? Math.log1p(Math.max(0, ds)) / Math.log1p(5000) : null;
          const values = [mqScore, dustScore].filter(v => v !== null);
          if(values.length === 0) return null;
          return values.reduce((sum, value) => sum + value, 0) / values.length;
      }

        function absoluteHeatValue(mq135, dust){
          const mq = mq135 !== null && mq135 !== undefined ? Number(mq135) : null;
          const ds = dust !== null && dust !== undefined ? Number(dust) : null;
          const mqBand = mq === null || Number.isNaN(mq) ? null : (mq <= 50 ? 0.10 : mq <= 120 ? 0.20 : mq <= 250 ? 0.35 : mq <= 500 ? 0.55 : mq <= 1000 ? 0.78 : 1.0);
          const dustBand = ds === null || Number.isNaN(ds) ? null : (ds <= 25 ? 0.10 : ds <= 60 ? 0.20 : ds <= 120 ? 0.35 : ds <= 250 ? 0.55 : ds <= 500 ? 0.78 : 1.0);
          const values = [mqBand, dustBand].filter(v => v !== null);
          if(values.length === 0) return null;
          return values.reduce((sum, value) => sum + value, 0) / values.length;
        }

        function heatScore(row, distribution){
          const absoluteValue = absoluteHeatValue(row.mq135, row.dust);
          const relativeValue = combinedHeatValue(row.mq135, row.dust);
          if(absoluteValue === null && relativeValue === null) return 0.15;

          let score = absoluteValue !== null ? absoluteValue : 0.15;
          if(relativeValue !== null && distribution){
            let relativeBand = 0.15;
            if(relativeValue <= distribution.q20) relativeBand = 0.15;
            else if(relativeValue <= distribution.q45) relativeBand = 0.35;
            else if(relativeValue <= distribution.q70) relativeBand = 0.60;
            else if(relativeValue <= distribution.q90) relativeBand = 0.80;
            else relativeBand = 1.0;
            score = (score * 0.9) + (relativeBand * 0.1);
          }
          return Math.max(0.12, Math.min(score, 1));
        }

        function buildDistribution(rows){
          const values = (rows || []).map(row => combinedHeatValue(row.mq135, row.dust)).filter(value => value !== null).sort((a, b) => a - b);
          if(values.length === 0){
            return { q20: 0.20, q45: 0.40, q70: 0.65, q90: 0.85 };
          }
          const q20 = percentile(values, 0.20) ?? 0.20;
          const q45 = percentile(values, 0.45) ?? 0.40;
          const q70 = percentile(values, 0.70) ?? 0.65;
          const q90 = percentile(values, 0.90) ?? 0.85;
          return { q20, q45, q70, q90 };
      }

      function buildHeatPoints(rows, phase){
        const points = [];
        const recentRows = (rows || []).slice(-80);
          const distribution = buildDistribution(recentRows);
        const globalDriftLat = Math.sin(phase * 0.18) * 0.0022;
        const globalDriftLng = Math.cos(phase * 0.15) * 0.0022;
        const pulse = 0.75 + (Math.sin(phase * 0.55) * 0.18);
        recentRows.forEach((row, index) => {
            const score = heatScore(row, distribution);
          const spread = 0.0007 + (score * 0.0036);
          const rowPhase = phase + (index * 0.21);
          const rowDriftLat = Math.sin(rowPhase) * (0.0006 + score * 0.0014);
          const rowDriftLng = Math.cos(rowPhase * 0.93) * (0.0006 + score * 0.0014);
          const offsets = [
            [0, 0, 1.0],
            [spread, 0, 0.9],
            [-spread, 0, 0.9],
            [0, spread, 0.9],
            [0, -spread, 0.9],
            [spread * 0.7, spread * 0.7, 0.72],
            [-spread * 0.7, -spread * 0.7, 0.72],
            [spread * 0.7, -spread * 0.7, 0.72],
            [-spread * 0.7, spread * 0.7, 0.72],
          ];
          offsets.forEach(([dLat, dLng, multiplier], idx) => {
            const trail = idx === 0 ? 1 : 0.78;
            const jitter = (Math.sin(rowPhase + idx) * 0.00025);
            points.push([
              CENTER[0] + globalDriftLat + rowDriftLat + dLat + jitter,
              CENTER[1] + globalDriftLng + rowDriftLng + dLng - jitter,
              Math.max(0.12, Math.min(score * multiplier * pulse * trail, 1))
            ]);
          });
        });
        if(points.length === 0){
          points.push([CENTER[0] + globalDriftLat, CENTER[1] + globalDriftLng, 0.12]);
        }
        return points;
      }

      function renderHeatmap(){
        if(!homeHeatMap) return;
        const points = buildHeatPoints(homeHeatRows, animationPhase);
        if(!homeHeatLayer){
          homeHeatLayer = L.heatLayer(points, { radius: 14, blur: 10, maxZoom: 16, gradient: HEAT_GRADIENT }).addTo(homeHeatMap);
        }else{
          homeHeatLayer.setLatLngs(points);
        }
      }

      function clearDeviceLayer(){
        if(homeDeviceLayer && homeHeatMap){
          homeHeatMap.removeLayer(homeDeviceLayer);
        }
        homeDeviceLayer = null;
      }

      function renderDeviceMarkers(devices){
        if(!homeHeatMap) return;
        clearDeviceLayer();
        const mappedDevices = (devices || []).filter(device => {
          const lat = Number(device.lat);
          const lng = Number(device.lng);
          return Number.isFinite(lat) && Number.isFinite(lng);
        });

        const deviceLayers = [];
        mappedDevices.forEach(device => {
          const lat = Number(device.lat);
          const lng = Number(device.lng);
          const label = device.name ? device.name : 'Device';
          deviceLayers.push(
            L.circleMarker([lat, lng], {
            radius: 10,
            color: '#0f172a',
            weight: 2,
            fillColor: '#334155',
            fillOpacity: 0.95,
            }).bindPopup(`<strong>${label}</strong><br/>Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}`)
          );
        });

        homeDeviceLayer = L.layerGroup(deviceLayers);
        homeDeviceLayer.addTo(homeHeatMap);
      }

      function animateHeatmap(){
        if(heatmapPaused) return;
        animationPhase += 0.04;
        renderHeatmap();
        requestAnimationFrame(()=>{ /* keep animation smooth when tab is active */ });
      }

      function initMap(){
        homeHeatMap = L.map('homeHeatMap', {scrollWheelZoom:true}).setView(CENTER, 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap contributors' }).addTo(homeHeatMap);
        homeHeatMap.on('movestart zoomstart dragstart', ()=>{ heatmapPaused = true; });
        homeHeatMap.on('moveend zoomend dragend', ()=>{
          heatmapPaused = false;
          renderHeatmap();
        });
        renderHeatmap();
      }

      async function loadHeatHistory(){
        try{
          // Prefer dataset-based heat if available (Data sets/ folder). Falls back to sensor_data.
          let res = await fetch('api/dataset_heat.php?limit=80&_=' + Date.now(), { cache: 'no-store' });
          if(!res.ok){
            res = await fetch('api/sensor_data.php?limit=80&_=' + Date.now(), { cache: 'no-store' });
          }
          const js = await res.json();
          homeHeatRows = Array.isArray(js.rows) ? js.rows : [];
          if(homeHeatRows.length === 0){
            homeHeatRows = generateDemoHeatRows(72);
          }
          renderHeatmap();
        }catch(err){
          console.error('heat history failed', err);
          homeHeatRows = generateDemoHeatRows(72);
          renderHeatmap();
        }
      }

      async function loadDevices(){
        try{
          const res = await fetch('api/devices.php?_=' + Date.now(), { cache: 'no-store' });
          const js = await res.json();
          homeDevices = Array.isArray(js.devices) ? js.devices : [];
          renderDeviceMarkers(homeDevices);
        }catch(err){ console.error('device load failed', err); }
      }

      async function loadLiveSnapshot(){
        try{
          const res = await fetch('api/data.php?_=' + Date.now(), { cache: 'no-store' });
          const js = await res.json();
          const signature = [js.mq135 ?? '', js.dust ?? ''].join('|');
          if(signature === liveSignature) return;
          liveSignature = signature;
          homeHeatRows = homeHeatRows.concat([{ mq135: js.mq135, dust: js.dust, created_at: Math.floor(Date.now()/1000) }]);
          if(homeHeatRows.length > 80) homeHeatRows = homeHeatRows.slice(-80);
          renderHeatmap();
        }catch(err){
          console.error('live snapshot failed', err);
          if(homeHeatRows.length === 0) homeHeatRows = generateDemoHeatRows(72);
          homeHeatRows = homeHeatRows.concat([{ mq135: 280, dust: 220, created_at: Math.floor(Date.now()/1000) }]);
          if(homeHeatRows.length > 80) homeHeatRows = homeHeatRows.slice(-80);
          renderHeatmap();
        }
      }

      async function loadPrediction(){
        try{
          const res = await fetch(`api/predictions.php?cycle_seconds=${predictionCycleSeconds}&_=` + Date.now(), { cache: 'no-store' });
          const js = await res.json();
          const signature = JSON.stringify({ latest: js.latest || null, forecast: js.forecast || [], trend: js.trend || null });
          if(!js.latest && !demoDataSeeded){
            await ensureDemoData();
            const retry = await fetch(`api/predictions.php?cycle_seconds=${predictionCycleSeconds}&_=` + Date.now(), { cache: 'no-store' });
            const retryJs = await retry.json();
            renderPredictionBox(retryJs);
            predictionSignature = JSON.stringify({ latest: retryJs.latest || null, forecast: retryJs.forecast || [], trend: retryJs.trend || null });
            const refreshLabel = document.getElementById('predictionRefreshLabel');
            if(refreshLabel){
              refreshLabel.textContent = predictionCycleSeconds === 30 ? 'Auto-refresh: 30 seconds' : 'Auto-refresh: 60 seconds';
            }
            return;
          }
          if(signature === predictionSignature) return;
          predictionSignature = signature;
          renderPredictionBox(js);
          const refreshLabel = document.getElementById('predictionRefreshLabel');
          if(refreshLabel){
            refreshLabel.textContent = predictionCycleSeconds === 30 ? 'Auto-refresh: 30 seconds' : 'Auto-refresh: 60 seconds';
          }
        }catch(err){
          console.error('prediction load failed', err);
          renderPredictionBox({ latest: null, forecast: [], trend: null });
        }
      }

      function startPredictionTimer(){
        if(predictionTimer) clearInterval(predictionTimer);
        // Use the configured cycle (seconds) converted to milliseconds so
        // Demo = 30s and Live = 3600s behave correctly.
        predictionTimer = setInterval(loadPrediction, predictionCycleSeconds * 1000);
      }

      function setPredictionDemoMode(enabled){
        predictionCycleSeconds = enabled ? 30 : 3600;
        const btn = document.getElementById('predictionDemoBtn');
        if(btn){
          btn.textContent = enabled ? 'Demo: 30s' : 'Live: 1h';
          btn.className = enabled
            ? 'text-xs px-3 py-1 rounded-full border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 transition'
            : 'text-xs px-3 py-1 rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition';
        }
        const refreshLabel = document.getElementById('predictionRefreshLabel');
        if(refreshLabel){
          refreshLabel.textContent = enabled ? 'Auto-refresh: 30 seconds' : 'Auto-refresh: 60 seconds';
        }
        predictionSignature = null;
        loadPrediction();
        startPredictionTimer();
      }

      document.addEventListener('DOMContentLoaded', ()=>{
        initMap();
        loadHeatHistory();
        loadDevices();
        loadLiveSnapshot();
        loadPrediction();
        startPredictionTimer();
        const demoBtn = document.getElementById('predictionDemoBtn');
        if(demoBtn){
          demoBtn.addEventListener('click', ()=>{ setPredictionDemoMode(predictionCycleSeconds !== 30); });
        }
        setInterval(loadHeatHistory, 30000);
        setInterval(loadDevices, 30000);
        setInterval(loadLiveSnapshot, 5000);
        setInterval(animateHeatmap, 300);
      });
    })();
    </script>
  </body>
</html>
