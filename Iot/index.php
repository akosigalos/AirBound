<?php
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AIR-BOUND — Intelligent Air Quality Monitoring</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
      #landingHeatMap .leaflet-container{background:#f8fafc}
    </style>
    <style>
      html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roberto,"Helvetica Neue",Arial}
      /* subtle animated gradient */
      .bg-ambient{background: linear-gradient(120deg,#eef2ff 0%, #ecfdf5 50%, #f0f9ff 100%);}
      .glass{background: rgba(255,255,255,0.6); backdrop-filter: blur(6px);}
      @keyframes floaty {0%{transform:translateY(0)}50%{transform:translateY(-6px)}100%{transform:translateY(0)}}
      /* Nav link entrance and hover underline animation */
      @keyframes navFadeIn {from{opacity:0; transform:translateY(-6px)} to{opacity:1; transform:none}}
      .nav-entrance{animation:navFadeIn 600ms ease both}
      .govph-link{position:relative; transition:transform .18s ease, box-shadow .18s ease, color .18s ease}
      .govph-link:hover{transform:translateY(-3px) scale(1.02)}
      .govph-link::after{content:''; position:absolute; left:0; bottom:-5px; height:2px; width:0; background:linear-gradient(90deg,#10b981,#06b6d4); transition:width .28s ease}
      .govph-link:hover::after{width:100%}
    </style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased">
    <header class="max-w-7xl mx-auto p-6 flex items-center justify-between relative z-50">
      <div class="flex items-center gap-6">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-sky-500 flex items-center justify-center text-white font-extrabold shadow-lg">AB</div>
        <div>
          <h1 class="text-xl font-semibold tracking-tight">AIR-BOUND</h1>
          <p class="text-xs text-slate-600">Air quality monitoring platform</p>
        </div>

        <nav class="hidden sm:flex items-center gap-4 ml-6">
          <a href="index.php" class="text-sm text-slate-700 nav-entrance govph-link">Home</a>

          <div class="relative nav-entrance">
            <button id="aq-dropdown-btn" type="button" aria-expanded="false" aria-controls="aq-dropdown-menu" class="text-sm text-slate-700 govph-link inline-flex items-center gap-2 focus:outline-none">
              Air Quality
              <svg class="w-3 h-3 opacity-80" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 9l6 6 6-6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

               <div id="aq-dropdown-menu" class="hidden absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50" style="min-width:14rem; pointer-events:auto; z-index:99999;">
                 <a href="monitoring.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Air Quality Monitoring</a>
                 <a href="lawpolicies.php" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Laws & Policies</a>
               </div>
          </div>

          <a href="https://www.gov.ph" target="_blank" rel="noopener noreferrer" class="text-sm text-slate-700 nav-entrance govph-link">GovPH</a>
        </nav>

        <script>
          // Header dropdown toggle for Air Quality menu (index page)
          document.addEventListener('DOMContentLoaded', function(){
            const btn = document.getElementById('aq-dropdown-btn');
            const menu = document.getElementById('aq-dropdown-menu');
            if(!btn || !menu) return;
            btn.addEventListener('click', function(e){
              e.preventDefault();
              e.stopPropagation();
              const open = !menu.classList.contains('hidden');
              if(open){ menu.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); }
              else { menu.classList.remove('hidden'); btn.setAttribute('aria-expanded','true'); }
            });
            document.addEventListener('click', function(e){
                  if(!menu.classList.contains('hidden') && !btn.contains(e.target) && !menu.contains(e.target)){
                    menu.classList.add('hidden'); btn.setAttribute('aria-expanded','false');
                  }
                });
              });
            // Navigate to Laws & Policies page from landing nav
            function gotoLawPolicies(e){ if(e && e.preventDefault) e.preventDefault(); window.location.href = 'lawpolicies.php'; }

        </script>
      </div>
      <!-- Right side: search moved here to sit at the far right -->
      <div class="flex items-center">
        <form action="#" method="GET" class="flex items-center">
          <label for="site-search" class="sr-only">Search site</label>
          <input id="site-search" name="q" type="search" placeholder="Search..." class="px-3 py-2 rounded-l-md border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300" />
          <button type="submit" class="px-3 py-2 bg-emerald-600 text-white rounded-r-md text-sm">Search</button>
        </form>
      </div>
    </header>

    <!-- Hero -->
    <main class="max-w-7xl mx-auto px-6">
      <section class="relative overflow-hidden rounded-2xl p-8 md:p-12 grid gap-8 md:grid-cols-2 items-center">
        <div class="space-y-6">
          <h2 class="text-4xl md:text-5xl font-extrabold leading-tight">Protecting cities, campuses, and communities with precise air intelligence</h2>
          <p class="text-lg text-slate-700 max-w-xl">AIR-BOUND provides real-time sensor telemetry, geo-spatial mapping, alerts, and analytics to help organizations monitor and respond to air quality events.</p>
          <!-- Hero CTA removed (Login / Sign Up omitted) -->

          <div class="mt-4 flex flex-wrap gap-3">
            <a href="monitoring.php" class="inline-flex items-center gap-3 px-6 py-3 bg-emerald-600 text-white rounded-lg shadow-lg hover:scale-105 transform transition">Watch real time Air monitoring</a>
            <a href="analytics.php" class="inline-flex items-center gap-3 px-6 py-3 bg-white text-slate-900 rounded-lg shadow border border-slate-200 hover:scale-105 transform transition">Open Analytics</a>
          </div>
        </div>

        <!-- Live preview mock -->
       <div class="relative">
          <div  class="relative bg-white rounded-2xl shadow-xl p-4 md:p-6" style="animation:floaty 6s ease-in-out infinite;">
            <div class="flex items-center justify-between mb-4">
              <div class="text-sm text-slate-600">Live Preview</div>
              <div class="text-xs text-slate-500">Updated moments ago</div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
              <div class="rounded-lg p-3 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100">
                <div class="text-xs text-slate-600">CO</div>
                <div class="text-2xl font-bold" id="preview-mq135">--</div>
                <div class="text-xs text-slate-500 mt-2">Air quality sensor</div>
              </div>
              <div class="rounded-lg p-3 bg-gradient-to-br from-red-50 to-orange-50 border border-red-100">
                <div class="text-xs text-slate-600">NO₂</div>
                <div class="text-2xl font-bold" id="preview-dust">--</div>
                <div class="text-xs text-slate-500 mt-2">Particulate matter sensor</div>
              </div>
              <div class="col-span-2 h-36 md:h-28 rounded-lg bg-slate-50 border border-dashed border-slate-100 flex items-center justify-center text-slate-400">Map preview</div>
            </div>
          </div>
          <div class="absolute -right-8 -bottom-8 w-40 h-40 rounded-full bg-gradient-to-tr from-emerald-200 to-sky-200 opacity-60 filter blur-3xl"></div>
        </div>
      </section>

      <!-- Features -->
      <section class="mt-12">
        <h3 class="text-2xl font-semibold">Capabilities</h3>
        <p class="text-slate-600 mt-2">Built for operators and researchers who need timely, actionable air quality data.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-1">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">🛰️</div>
              <div>
                <div class="font-semibold">Real-Time Monitoring</div>
                <div class="text-sm text-slate-500">Continuous telemetry from distributed sensors.</div>
              </div>
            </div>
          </div>

          <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-1">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-sky-50 rounded-lg text-sky-600">🗺️</div>
              <div>
                <div class="font-semibold">Geo-spatial Mapping</div>
                <div class="text-sm text-slate-500">Visualize hotspots, trends, and coverage.</div>
              </div>
            </div>
          </div>

          <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-1">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-yellow-50 rounded-lg text-yellow-600">⚠️</div>
              <div>
                <div class="font-semibold">Alert System</div>
                <div class="text-sm text-slate-500">Threshold alerts and notifications for rapid response.</div>
              </div>
            </div>
          </div>

          <div class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition transform hover:-translate-y-1">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">📈</div>
              <div>
                <div class="font-semibold">Analytics Dashboard</div>
                <div class="text-sm text-slate-500">Trends, exports, and customizable reports.</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Interactive Map -->
      <section class="mt-12">
        <h3 class="text-2xl font-semibold">Air Quality Map – Panabo Area</h3>
        <p class="text-slate-600 mt-2">Live heatmap centered on Panabo City, connected to the same CO and NO₂ data used in the admin home map.</p>

        <div class="mt-6 bg-white rounded-2xl p-4 shadow-sm">
          <div id="landingHeatMap" class="w-full h-64 md:h-96 rounded-lg overflow-hidden border border-slate-100"></div>
          <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Cool / Low</span>
            <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-yellow-50 text-yellow-700 border border-yellow-100"><span class="w-2 h-2 rounded-full bg-yellow-500"></span>Moderate</span>
            <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-orange-50 text-orange-700 border border-orange-100"><span class="w-2 h-2 rounded-full bg-orange-500"></span>High</span>
            <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full bg-red-50 text-red-700 border border-red-100"><span class="w-2 h-2 rounded-full bg-red-500"></span>Very High</span>
          </div>
        </div>
      </section>

      <!-- About -->
      <section class="mt-12 grid md:grid-cols-2 gap-8 items-center">
        <div>
          <h3 class="text-2xl font-semibold">Purpose — Cleaner air through data</h3>
          <p class="mt-3 text-slate-700">AIR-BOUND was created to provide accessible, high-fidelity air quality information so communities can make informed decisions. From pinpointing pollution sources to measuring exposure over time, our platform connects sensors, maps and analytics into a single powerful toolkit.</p>
          <ul class="mt-4 space-y-2 text-slate-600">
            <li>• Data-driven decision making for public health</li>
            <li>• Scalable monitoring and centralized management</li>
            <li>• Actionable alerts and long-term insights</li>
          </ul>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow-sm">
          <div class="h-48 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400">Analytics snapshot</div>
        </div>
      </section>  
      <!-- CTA -->
      <section class="mt-12 mb-12 bg-gradient-to-r from-emerald-50 to-sky-50 rounded-2xl p-8 flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
          <h4 class="text-xl font-semibold">Ready to monitor air quality with AIR-BOUND?</h4>
          <p class="text-slate-600 mt-1">Create an account and start collecting environmental insights today.</p>
        </div>
        <div class="flex gap-3">
          <!-- CTA auth links removed per request -->
        </div>
      </section>
    </main>

    <footer class="max-w-7xl mx-auto p-6 text-center text-sm text-slate-500">© AIR-BOUND — Designed for clean air monitoring</footer>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
    <script>
      // Initialize Leaflet map centered on Panabo City
      (function(){
        const CENTER = [7.3003, 125.6804]; // Panabo City (user-provided)
        const HEAT_GRADIENT = {0.10:'#16a34a', 0.25:'#4ade80', 0.45:'#a3e635', 0.70:'#eab308', 1.0:'#f97316'};
        let heatMap = null;
        let heatLayer = null;
        let heatRows = [];
        let liveSignature = null;
        let animationPhase = 0;
        let heatmapPaused = false;

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
          return {
            q20: percentile(values, 0.20) ?? 0.20,
            q45: percentile(values, 0.45) ?? 0.40,
            q70: percentile(values, 0.70) ?? 0.65,
            q90: percentile(values, 0.90) ?? 0.85
          };
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
              [-spread * 0.7, spread * 0.7, 0.72]
            ];
            offsets.forEach(([dLat, dLng, multiplier], idx) => {
              const trail = idx === 0 ? 1 : 0.78;
              const jitter = Math.sin(rowPhase + idx) * 0.00025;
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
          if(!heatMap) return;
          const points = buildHeatPoints(heatRows, animationPhase);
          if(!heatLayer){
            heatLayer = L.heatLayer(points, { radius: 14, blur: 10, maxZoom: 16, gradient: HEAT_GRADIENT }).addTo(heatMap);
          }else{
            heatLayer.setLatLngs(points);
          }
        }

        function animateHeatmap(){
          if(heatmapPaused) return;
          animationPhase += 0.04;
          renderHeatmap();
          requestAnimationFrame(()=>{});
        }

        function initMap(){
          const mapEl = document.getElementById('landingHeatMap');
          if(!mapEl) return;
          heatMap = L.map('landingHeatMap', {scrollWheelZoom:true}).setView(CENTER, 14);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap contributors' }).addTo(heatMap);
          heatMap.on('movestart zoomstart dragstart', ()=>{ heatmapPaused = true; });
          heatMap.on('moveend zoomend dragend', ()=>{ heatmapPaused = false; renderHeatmap(); });
          renderHeatmap();
        }

        async function loadHeatHistory(){
          try{
            const res = await fetch('api/sensor_data.php?limit=80&_=' + Date.now(), { cache: 'no-store' });
            const js = await res.json();
            heatRows = Array.isArray(js.rows) ? js.rows : [];
            renderHeatmap();
          }catch(err){ console.error('heat history failed', err); }
        }

        async function loadLiveSnapshot(){
          try{
            const res = await fetch('api/data.php?_=' + Date.now(), { cache: 'no-store' });
            const js = await res.json();
            const signature = [js.mq135 ?? '', js.dust ?? ''].join('|');
            if(signature === liveSignature) return;
            liveSignature = signature;
            heatRows = heatRows.concat([{ mq135: js.mq135, dust: js.dust, created_at: Math.floor(Date.now()/1000) }]);
            if(heatRows.length > 80) heatRows = heatRows.slice(-80);
            renderHeatmap();
          }catch(err){ console.error('live snapshot failed', err); }
        }

        if(document.readyState === 'loading'){
          document.addEventListener('DOMContentLoaded', ()=>{
            initMap();
            loadHeatHistory();
            loadLiveSnapshot();
            setInterval(loadHeatHistory, 30000);
            setInterval(loadLiveSnapshot, 5000);
            setInterval(animateHeatmap, 300);
          });
        }else{
          initMap();
          loadHeatHistory();
          loadLiveSnapshot();
          setInterval(loadHeatHistory, 30000);
          setInterval(loadLiveSnapshot, 5000);
          setInterval(animateHeatmap, 300);
        }

        async function loadPreviewValues(){
          try{
            const res = await fetch('api/data.php?_=' + Date.now(), { cache: 'no-store' });
            const js = await res.json();
            const mq135El = document.getElementById('preview-mq135');
            const dustEl = document.getElementById('preview-dust');
            if(mq135El) mq135El.textContent = js.mq135 !== null && js.mq135 !== undefined ? Number(js.mq135).toFixed(1) : '--';
            if(dustEl) dustEl.textContent = js.dust !== null && js.dust !== undefined ? Number(js.dust).toFixed(1) : '--';
          }catch(err){ console.error('preview values failed', err); }
        }

        loadPreviewValues();
        setInterval(loadPreviewValues, 5000);
      })();
    </script>
  </body>
</html>
