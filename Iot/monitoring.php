<?php
session_start();
// Monitoring page can be viewed without login; if you want protection, uncomment below
// if(!isset($_SESSION['user_id'])){ header('Location: login.php'); exit; }
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Guest');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Monitoring — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
      html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roberto,"Helvetica Neue",Arial}
      .bg-ambient{background: linear-gradient(120deg,#eef2ff 0%, #ecfdf5 50%, #f0f9ff 100%);} 
      .glass{background: rgba(255,255,255,0.6); backdrop-filter: blur(6px);} 
      @keyframes fadeIn{from{opacity:0; transform:translateY(6px)}to{opacity:1; transform:none}}
      .page-enter{animation:fadeIn 360ms ease both}
      /* Nav link entrance and hover underline animation */
      @keyframes navFadeIn {from{opacity:0; transform:translateY(-6px)} to{opacity:1; transform:none}}
      .nav-entrance{animation:navFadeIn 600ms ease both}
      .govph-link{position:relative; transition:transform .18s ease, box-shadow .18s ease, color .18s ease}
      .govph-link:hover{transform:translateY(-3px) scale(1.02)}
      .govph-link::after{content:''; position:absolute; left:0; bottom:-5px; height:2px; width:0; background:linear-gradient(90deg,#10b981,#06b6d4); transition:width .28s ease}
      .govph-link:hover::after{width:100%}
    </style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased page-enter">
    <?php include __DIR__ . '/partials/header-landing.php'; ?>

    <main class="max-w-7xl mx-auto px-6 p-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl font-semibold">Real-Time Monitoring</h2>
              <p class="text-sm text-slate-600">Live telemetry from deployed sensors — auto-refreshing.</p>
            </div>
            <div class="text-sm text-slate-500">Last updated: <span id="lastUpdated">—</span> <button id="openAnalyticsBtn" class="ml-3 text-sm px-3 py-1 rounded bg-emerald-600 text-white">Open Analytics</button></div>
          </div>

          <!-- Map on top -->
          <section class="mt-2 bg-white rounded-lg p-4 shadow">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold">Geo-spatial Map</h3>
              <div class="text-sm text-slate-500">Sensors & last reported values</div>
            </div>
            <div id="monitorMap" class="mt-4 h-96 rounded border border-slate-100"></div>
          </section>

          <!-- Pollutant icons directly below the map -->
          <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div id="card-pm25" class="rounded-lg p-4 shadow bg-white flex items-center justify-between">
              <div>
                <div class="text-xs text-slate-500">PM2.5</div>
                <div id="val-pm25" class="text-3xl font-bold"></div>
              </div>
              <div class="flex items-center gap-2">
                <div id="icon-pm25" class="text-2xl">👤</div>
                <div id="badge-pm25" class="text-sm font-medium px-2 py-1 rounded text-white bg-slate-300"></div>
              </div>
            </div>

            <div id="card-pm10" class="rounded-lg p-4 shadow bg-white flex items-center justify-between">
              <div>
                <div class="text-xs text-slate-500">PM10</div>
                <div id="val-pm10" class="text-3xl font-bold"></div>
              </div>
              <div class="flex items-center gap-2">
                <div id="icon-pm10" class="text-2xl">👤</div>
                <div id="badge-pm10" class="text-sm font-medium px-2 py-1 rounded text-white bg-slate-300"></div>
              </div>
            </div>

            <div id="card-co" class="rounded-lg p-4 shadow bg-white flex items-center justify-between">
              <div>
                <div class="text-xs text-slate-500">CO</div>
                <div id="val-mq135" class="text-3xl font-bold"></div>
              </div>
              <div class="text-sm text-slate-500">&nbsp;</div>
            </div>

            <div id="card-no2" class="rounded-lg p-4 shadow bg-white flex items-center justify-between">
              <div>
                <div class="text-xs text-slate-500">NO₂</div>
                <div id="val-dust" class="text-3xl font-bold"></div>
              </div>
              <div class="text-sm text-slate-500">&nbsp;</div>
            </div>
          </div>

          <!-- Air Quality Legend -->
          <section class="mt-6 bg-white rounded-lg p-4 shadow">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold">Air Quality Legend</h3>
              <div class="text-sm text-slate-500">Levels</div>
            </div>
            <div class="flex justify-center">
              <div class="w-full max-w-4xl">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 items-center justify-items-center py-4">
                  <?php
                    // Reuse the same dynamic image-matching logic as admin view
                    $imageDir = __DIR__ . '/assets/image';
                    $files = [];
                    if(is_dir($imageDir)){
                      $glob = glob($imageDir . '/*.{png,jpg,jpeg,gif,svg}', GLOB_BRACE);
                      if($glob !== false) $files = $glob;
                    }

                    $levels = [
                      ['label' => 'Good', 'keys' => ['good']],
                      ['label' => 'Fair', 'keys' => ['fair']],
                      ['label' => 'Unhealthy', 'keys' => ['unhealthy']],
                      ['label' => 'Very Unhealthy', 'keys' => ['veryunhealthy','very','ver']],
                      ['label' => 'Emergency', 'keys' => ['emerg','emergency']],
                    ];

                    $normalizeName = function($str){
                      $s = strtolower(pathinfo($str, PATHINFO_FILENAME));
                      $s = preg_replace('/[^a-z]/', '', $s);
                      $s = preg_replace('/(.)\\1+/', '$1', $s);
                      return $s;
                    };

                    foreach($levels as $level){
                      $foundFile = null;
                      foreach($files as $f){
                        $n = $normalizeName($f);
                        foreach($level['keys'] as $k){
                          $kn = $normalizeName($k);
                          if($kn !== '' && strpos($n, $kn) !== false){
                            $foundFile = basename($f);
                            break 2;
                          }
                        }
                      }

                      if($foundFile){
                        $rel = 'assets/image/' . $foundFile;
                        echo '<div class="flex flex-col items-center gap-2">';
                        echo '<img src="'.htmlspecialchars($rel).'" alt="'.htmlspecialchars($level['label']).'" class="w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 object-contain rounded-md border border-slate-100 shadow-sm" />';
                        echo '<div class="text-xs font-medium text-slate-600">'.htmlspecialchars($level['label']).'</div>';
                        echo '</div>';
                      } else {
                        echo '<div class="flex flex-col items-center gap-2">';
                        echo '<div class="w-20 h-20 md:w-24 md:h-24 lg:w-28 lg:h-28 flex items-center justify-center rounded-md border border-dashed border-slate-200 bg-slate-50 text-slate-400">No Image</div>';
                        echo '<div class="text-xs font-medium text-slate-600">'.htmlspecialchars($level['label']).'</div>';
                        echo '</div>';
                      }
                    }
                  ?>
                </div>
              </div>
            </div>
            <div class="mt-5 rounded-xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-emerald-50/40 p-4 shadow-sm">
              <div class="flex items-center justify-between gap-3 mb-3">
                <div>
                  <div class="text-sm font-semibold text-slate-800">CO / NO₂ Thresholds</div>
                  <div class="text-xs text-slate-500">Project-defined bands used for realtime monitoring.</div>
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

        </div>
    </main>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
    <script>
      // Realtime monitoring: fetches api/data.php and api/devices.php
      (function(){
        const CENTER = [7.3003, 125.6804];
        const map = L.map('monitorMap', {scrollWheelZoom:false}).setView(CENTER, 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors'}).addTo(map);

        const centerMarker = L.circleMarker(CENTER,{radius:9,weight:1,color:'#10b981',fillColor:'#34d399',fillOpacity:0.9}).addTo(map).bindPopup('Panabo City');
        let deviceMarkers = {}; // keyed by device id
        let monitorHeatMap = null;
        let monitorHeatLayer = null;
        let monitorHeatRows = [];
        let liveSignature = null;
        let animationPhase = 0;
        let heatmapPaused = false;
        let demoDataSeeded = false;

        function getDemoMonitoringDevices(){
          return [
            { id: 1, name: 'Device Alpha', lat: CENTER[0] + 0.0045, lng: CENTER[1] - 0.0040, status: 'online', pm25: 26, last_reading_at: Math.floor(Date.now()/1000) - 120 },
            { id: 2, name: 'Device Beta', lat: CENTER[0] - 0.0038, lng: CENTER[1] + 0.0032, status: 'offline', pm25: 49, last_reading_at: Math.floor(Date.now()/1000) - 240 },
            { id: 3, name: 'Device Gamma', lat: CENTER[0] + 0.0022, lng: CENTER[1] + 0.0052, status: 'online', pm25: 72, last_reading_at: Math.floor(Date.now()/1000) - 60 }
          ];
        }

        function getDemoHeatRows(count){
          const rows = [];
          for(let index = 0; index < count; index += 1){
            const phase = index / Math.max(1, count - 1);
            const angle = phase * Math.PI * 2;
            rows.push({
              mq135: 420 + (Math.sin(angle * 1.6) * 220) + (phase * 360),
              dust: 310 + (Math.cos(angle * 1.3) * 160) + ((1 - phase) * 250),
              created_at: Math.floor(Date.now()/1000) - ((count - index) * 75)
            });
          }
          return rows;
        }

        function getDemoSnapshot(){
          return {
            reading: { pm25: 34, pm10: 68, created_at: Math.floor(Date.now()/1000) },
            sensor: { mq135: 420, dust: 310, updated: Math.floor(Date.now()/1000) },
            devices: getDemoMonitoringDevices()
          };
        }

        function clearDeviceMarkers(){ Object.keys(deviceMarkers).forEach(k=>{ map.removeLayer(deviceMarkers[k]); delete deviceMarkers[k]; }); }

        function setBadge(elId, info){
          const el = document.getElementById(elId);
          if(!el) return;
          if(!info){ el.className = 'text-sm font-medium px-2 py-1 rounded text-white bg-slate-300'; el.textContent = '—'; return; }
          el.style.backgroundColor = info.color || '#94a3b8';
          el.textContent = info.label || info.level || '—';
          // update paired icon if exists (icon-pm25, icon-pm10)
          try{
            const iconId = elId.replace('badge-','icon-');
            const iconEl = document.getElementById(iconId);
            if(iconEl) iconEl.textContent = info.icon || '👤';
          }catch(e){}
        }

        function classifyPM25Obj(v){ if(v===null) return null; if(v<=12) return {level:'good',label:'Good',icon:'😊',color:'#10b981',severity:0}; if(v<=35) return {level:'fair',label:'Fair',icon:'🙂',color:'#f59e0b',severity:1}; if(v<=55) return {level:'unhealthy',label:'Unhealthy',icon:'😷',color:'#f97316',severity:2}; if(v<=150) return {level:'very_unhealthy',label:'Very Unhealthy',icon:'🤒',color:'#ef4444',severity:3}; return {level:'emergency',label:'Emergency',icon:'🆘',color:'#6b021d',severity:4}; }

        function clamp(value, min, max, precision){
          const factor = Math.pow(10, precision);
          return Math.round(Math.max(min, Math.min(max, value)) * factor) / factor;
        }

        function driftValue(base, spread){
          return base + ((Math.random() * 2) - 1) * spread;
        }

        function fade(el, txt){
          if(!el) return;
          el.style.transition = 'opacity .18s';
          el.style.opacity = 0;
          setTimeout(()=>{ el.textContent = txt; el.style.opacity = 1; }, 160);
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
          if(!monitorHeatMap) return;
          const points = buildHeatPoints(monitorHeatRows, animationPhase);
          if(!monitorHeatLayer){
            monitorHeatLayer = L.heatLayer(points, { radius: 14, blur: 10, maxZoom: 16, gradient: {0.10:'#16a34a', 0.25:'#4ade80', 0.45:'#a3e635', 0.70:'#eab308', 1.0:'#f97316'} }).addTo(monitorHeatMap);
          }else{
            monitorHeatLayer.setLatLngs(points);
          }
        }

        function animateHeatmap(){
          if(heatmapPaused) return;
          animationPhase += 0.04;
          renderHeatmap();
          requestAnimationFrame(()=>{});
        }

        function initMap(){
          monitorHeatMap = map;
          monitorHeatMap.on('movestart zoomstart dragstart', ()=>{ heatmapPaused = true; });
          monitorHeatMap.on('moveend zoomend dragend', ()=>{
            heatmapPaused = false;
            renderHeatmap();
          });
          renderHeatmap();
        }

        async function loadHeatHistory(){
          try{
            const res = await fetch('api/sensor_data.php?limit=80&_=' + Date.now(), { cache: 'no-store' });
            const js = await res.json();
            monitorHeatRows = Array.isArray(js.rows) ? js.rows : [];
            if(monitorHeatRows.length === 0){
              monitorHeatRows = getDemoHeatRows(64);
            }
            renderHeatmap();
          }catch(err){
            console.error('heat history failed', err);
            monitorHeatRows = getDemoHeatRows(64);
            renderHeatmap();
          }
        }

        async function loadLiveSnapshot(){
          try{
            const res = await fetch('api/data.php?_=' + Date.now(), { cache: 'no-store' });
            const js = await res.json();
            const signature = [js.mq135 ?? '', js.dust ?? ''].join('|');
            if(signature === liveSignature) return;
            liveSignature = signature;
            monitorHeatRows = monitorHeatRows.concat([{ mq135: js.mq135, dust: js.dust, created_at: Math.floor(Date.now()/1000) }]);
            if(monitorHeatRows.length > 80) monitorHeatRows = monitorHeatRows.slice(-80);
            renderHeatmap();
          }catch(err){
            console.error('live snapshot failed', err);
            if(monitorHeatRows.length === 0) monitorHeatRows = getDemoHeatRows(64);
            monitorHeatRows = monitorHeatRows.concat([{ mq135: 420, dust: 310, created_at: Math.floor(Date.now()/1000) }]);
            if(monitorHeatRows.length > 80) monitorHeatRows = monitorHeatRows.slice(-80);
            renderHeatmap();
          }
        }

        function setMotionValue(elId, value, suffix){
          const el = document.getElementById(elId);
          if(!el) return;
          el.style.transition = 'opacity .18s';
          el.style.opacity = 0;
          setTimeout(()=>{
            el.textContent = value !== null && value !== undefined ? `${Number(value).toFixed(1)}${suffix || ''}` : '--';
            el.style.opacity = 1;
          }, 160);
        }

        function renderLiveCards(reading, sensor){
          const pm25 = reading && reading.pm25 !== null ? Number(reading.pm25) : null;
          const pm10 = reading && reading.pm10 !== null ? Number(reading.pm10) : null;
          const mq135 = sensor && sensor.mq135 !== null ? Number(sensor.mq135) : null;
          const dust = sensor && sensor.dust !== null ? Number(sensor.dust) : null;

          setMotionValue('val-pm25', pm25, ' µg/m³');
          setMotionValue('val-pm10', pm10, ' µg/m³');
          setMotionValue('val-mq135', mq135, '');
          setMotionValue('val-dust', dust, '');

          if(pm25 !== null) setBadge('badge-pm25', classifyPM25Obj(pm25));
          if(pm10 !== null) setBadge('badge-pm10', classifyPM25Obj(pm10));
        }

        const pmMotion = {
          basePm25: null,
          basePm10: null,
          currentPm25: null,
          currentPm10: null,
          lastUpdateAt: 0,
          timer: null
        };

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

        function renderPmMotion(){
          if(pmMotion.currentPm25 !== null){
            fade(document.getElementById('val-pm25'), pmMotion.currentPm25.toFixed(1) + ' µg/m³');
            setBadge('badge-pm25', classifyPM25Obj(pmMotion.currentPm25));
          }
          if(pmMotion.currentPm10 !== null){
            fade(document.getElementById('val-pm10'), pmMotion.currentPm10.toFixed(1) + ' µg/m³');
            setBadge('badge-pm10', classifyPM25Obj(pmMotion.currentPm10));
          }
        }

        function renderDemoMonitoringState(){
          const demo = getDemoSnapshot();
          setPmMotion(demo.reading.pm25, demo.reading.pm10);
          renderLiveCards(demo.reading, demo.sensor);
          document.getElementById('lastUpdated').textContent = new Date(demo.reading.created_at*1000).toLocaleString();
          updateDeviceMarkers(demo.devices);
          if(monitorHeatRows.length === 0) monitorHeatRows = getDemoHeatRows(64);
          renderHeatmap();
        }

        async function ensureDemoData(){
          if(demoDataSeeded) return false;
          demoDataSeeded = true;
          try{
            await fetch('api/simulate_reading.php', {
              method: 'POST',
              credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({})
            });
            return true;
          }catch(err){
            console.error('demo seed failed', err);
            return false;
          }
        }

        function startPmMotion(){
          if(pmMotion.timer) return;
          pmMotion.timer = setInterval(()=>{
            const now = Date.now();
            const idleMs = now - pmMotion.lastUpdateAt;

            if(pmMotion.basePm25 !== null){
              const spread25 = idleMs > 15000 ? 1.4 : 0.55;
              const next25 = driftValue(pmMotion.currentPm25 ?? pmMotion.basePm25, spread25);
              pmMotion.currentPm25 = clamp(next25, 0, 2000, 1);
            }

            if(pmMotion.basePm10 !== null){
              const spread10 = idleMs > 15000 ? 2.0 : 0.8;
              const next10 = driftValue(pmMotion.currentPm10 ?? pmMotion.basePm10, spread10);
              pmMotion.currentPm10 = clamp(next10, 0, 2000, 1);
            }

            renderPmMotion();
          }, 5000);
        }

        function escapeHtml(s){ if(s===null || s===undefined) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

        function updateDeviceMarkers(devices){
          // devices: array of device objects (with possible pm25 etc)
          if(!devices || !Array.isArray(devices)) return;
          // remove markers that no longer exist
          const keepIds = new Set(devices.map(d=>String(d.id)));
          Object.keys(deviceMarkers).forEach(k=>{ if(!keepIds.has(k)){ map.removeLayer(deviceMarkers[k]); delete deviceMarkers[k]; } });

          devices.forEach(dev=>{
            if(dev.lat === null || dev.lng === null) return;
            const statusInfo = classifyPM25Obj(dev.pm25);
            const iconEmoji = statusInfo ? statusInfo.icon : '👤';
            const color = statusInfo ? statusInfo.color : (dev.status === 'online' ? '#10b981' : '#94a3b8');
            const html = `<div style="font-size:18px;width:36px;height:36px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:${color};color:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25)">${iconEmoji}</div>`;
            const icon = L.divIcon({className:'', html:html, iconSize:[36,36], iconAnchor:[18,18]});

            const existing = deviceMarkers[String(dev.id)];
            if(existing){ existing.setLatLng([parseFloat(dev.lat), parseFloat(dev.lng)]); existing.setIcon(icon); existing._aqInfo = statusInfo; existing.bindPopup(`<strong>${escapeHtml(dev.name)}</strong><br/>Status: ${statusInfo?escapeHtml(statusInfo.label):escapeHtml(dev.status)}<br/>PM2.5: ${dev.pm25!==null?dev.pm25+' µg/m³':'No Data'}<br/>Last: ${dev.last_reading_at? new Date(dev.last_reading_at*1000).toLocaleString() : 'Never'}<br/><a href="analytics.php?device_id=${dev.id}" class="text-xs text-slate-700 underline">View Analytics</a>`);
            }else{
              const m = L.marker([parseFloat(dev.lat), parseFloat(dev.lng)], {icon: icon}).addTo(map);
              m._deviceId = dev.id;
              m._aqInfo = statusInfo;
              m.bindPopup(`<strong>${escapeHtml(dev.name)}</strong><br/>Status: ${statusInfo?escapeHtml(statusInfo.label):escapeHtml(dev.status)}<br/>PM2.5: ${dev.pm25!==null?dev.pm25+' µg/m³':'No Data'}<br/>Last: ${dev.last_reading_at? new Date(dev.last_reading_at*1000).toLocaleString() : 'Never'}<br/><a href="analytics.php?device_id=${dev.id}" class="text-xs text-slate-700 underline">View Analytics</a>`);
              deviceMarkers[String(dev.id)] = m;
            }
          });
        }

        async function loadOnce(){
          try{
            const [readingResp, sensorResp, devResp] = await Promise.all([
              fetch('api/readings.php?limit=1&sort=desc&start=0&end=9999999999'),
              fetch('api/data.php'),
              fetch('api/devices.php')
            ]);
            const readingJs = await readingResp.json();
            const sensorJs = await sensorResp.json();
            const devs = await devResp.json();

            const latest = (readingJs.readings || []).length ? readingJs.readings[(readingJs.readings || []).length - 1] : null;
            const pm25 = latest ? latest.pm25 : null;
            const pm10 = latest ? latest.pm10 : null;
            const mq135 = sensorJs.mq135 ?? null;
            const dust = sensorJs.dust ?? null;

            if(!latest && mq135 === null && dust === null && (!devs.devices || !devs.devices.length)){
              renderDemoMonitoringState();
              return;
            }

            if(!latest && mq135 === null && dust === null){
              const seeded = await ensureDemoData();
              if(seeded){
                return loadOnce();
              }
            }

            setPmMotion(pm25, pm10);
            renderLiveCards(latest, sensorJs);
            if(latest && latest.created_at){ document.getElementById('lastUpdated').textContent = new Date(latest.created_at*1000).toLocaleString(); }
            else if(sensorJs.updated){ document.getElementById('lastUpdated').textContent = new Date(sensorJs.updated*1000).toLocaleString(); }
            else{ document.getElementById('lastUpdated').textContent = ''; }

            // update device markers from devs.devices
            updateDeviceMarkers(devs.devices);

            // history + live snapshot for moving heat map
            await loadHeatHistory();
            await loadLiveSnapshot();

          }catch(err){
            console.error('Realtime load error',err);
            renderDemoMonitoringState();
          }
        }

        // initial load + periodic refresh
        initMap();
        startPmMotion();
        loadOnce();
        setInterval(loadOnce, 5000);
        setInterval(loadHeatHistory, 30000);
        setInterval(loadLiveSnapshot, 5000);
        setInterval(animateHeatmap, 300);

        // realtime updates via Server-Sent Events
        if(typeof(EventSource) !== 'undefined'){
          try{
            const devEs = new EventSource('api/devices_stream.php');
            devEs.addEventListener('devices', function(e){
              try{ const payload = JSON.parse(e.data); updateDeviceMarkers(payload.devices || payload); }catch(err){ console.error('SSE parse',err); }
            });
            devEs.addEventListener('error', function(err){ console.warn('Device SSE error', err); });

            // readings stream: update per-device marker and summary cards
            const rEs = new EventSource('api/readings_stream.php');
            rEs.addEventListener('reading', function(e){
              try{
                const r = JSON.parse(e.data);
                // update summary cards if relevant
                if(r.pm25 !== null) document.getElementById('val-pm25').textContent = r.pm25 + ' µg/m³';
                if(r.pm10 !== null) document.getElementById('val-pm10').textContent = r.pm10 + ' µg/m³';
                document.getElementById('lastUpdated').textContent = new Date(r.created_at*1000).toLocaleString();

                // update one device marker if present
                const devId = String(r.device_id);
                const existing = deviceMarkers[devId];
                const statusInfo = classifyPM25Obj(r.pm25);
                if(existing){
                  const iconEmoji = statusInfo ? statusInfo.icon : '👤';
                  const color = statusInfo ? statusInfo.color : '#94a3b8';
                  const html = `<div style="font-size:18px;width:36px;height:36px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:${color};color:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25)">${iconEmoji}</div>`;
                  const icon = L.divIcon({className:'', html:html, iconSize:[36,36], iconAnchor:[18,18]});
                  existing.setIcon(icon);
                  existing._aqInfo = statusInfo;
                  const popup = existing.getPopup();
                  const content = `<strong>${escapeHtml(existing.options.title || 'Device')}</strong><br/>Status: ${statusInfo?escapeHtml(statusInfo.label):'—'}<br/>PM2.5: ${r.pm25!==null?r.pm25+' µg/m³':'No Data'}<br/>Last: ${new Date(r.created_at*1000).toLocaleString()}<br/><a href="analytics.php?device_id=${encodeURIComponent(r.device_id)}" class="text-xs text-slate-700 underline">View Analytics</a>`;
                  existing.bindPopup(content);
                }
              }catch(err){ console.error('reading SSE parse', err); }
            });

          }catch(err){ console.warn('SSE init failed', err); }
        }

        document.addEventListener('DOMContentLoaded', ()=>{
          const btn = document.getElementById('openAnalyticsBtn');
          if(btn){
            btn.addEventListener('click', ()=>{
              const end = Math.floor(Date.now()/1000);
              const start = end - 24*3600;
              window.location = `analytics.php?start=${start}&end=${end}`;
            });
          }
        });
        startPmMotion();
      })();
    </script>
    <!-- Dropdown handled centrally by header script (portal) -->
  </body>
</html>
