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
    <title>Admin — Real-Time Monitoring</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>html{scroll-behavior:smooth}html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roberto,"Helvetica Neue",Arial}.bg-ambient{background: linear-gradient(135deg,#dff8ee 0%, #f5fffb 40%, #ffffff 100%);} .glass{background: rgba(255,255,255,0.72); backdrop-filter: blur(8px);} .page-enter{animation:fadeIn 360ms ease both}@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}</style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased page-enter">
    <div class="flex">
      <?php include __DIR__ . '/partials/sidebar.php'; ?>

      <main class="flex-1 p-6">
        <div class="max-w-7xl mx-auto px-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl font-semibold">Real-Time Monitoring (Admin)</h2>
              <p class="text-sm text-slate-600">Admin view — live telemetry and device map.</p>
            </div>
            <div class="text-sm text-slate-500">Last updated: <span id="lastUpdated">—</span></div>
          </div>

          <div id="alertModal" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-slate-950/85 backdrop-blur-sm px-4">
            <div class="relative z-[100000] w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-rose-200 overflow-hidden ring-8 ring-rose-300/40">
              <div class="bg-rose-600 px-5 py-4 text-white flex items-start justify-between gap-4">
                <div>
                  <div class="text-sm font-semibold uppercase tracking-wide">Critical CO alert</div>
                  <div class="text-2xl font-bold mt-1">Threshold exceeded</div>
                </div>
                <button id="closeAlertModalTop" class="text-white/90 hover:text-white text-2xl leading-none">&times;</button>
              </div>
              <div class="p-5">
                <div id="alertModalText" class="text-sm text-slate-700">CO crossed the unhealthy limit.</div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                  <div class="rounded-lg bg-slate-50 p-3 border border-slate-100">
                    <div class="text-slate-500 text-xs">Sensor</div>
                    <div class="font-semibold text-slate-900">CO</div>
                  </div>
                  <div class="rounded-lg bg-slate-50 p-3 border border-slate-100">
                    <div class="text-slate-500 text-xs">Limit</div>
                    <div class="font-semibold text-slate-900">Above 2000</div>
                  </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                  <button id="closeAlertModal" class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-medium hover:bg-rose-700 transition">Dismiss</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Map on top -->
          <section id="monitoringSection" class="mt-4 bg-white rounded-lg p-4 shadow scroll-mt-6">
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

          <section class="mt-6 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
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

          <!-- Overview / Visualization section (responsive, centered image) -->
          <section class="mt-6 bg-white rounded-lg p-4 shadow">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-semibold">Air Quality Legend</h3>
              <div class="text-sm text-slate-500">Levels</div>
            </div>
            <div class="flex justify-center">
              <div class="w-full max-w-4xl">
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 items-center justify-items-center py-4">
                  <?php
                    // Dynamically map available images in assets/image/ to legend levels.
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

          <!-- Alerts moved to Alerts Management page to keep monitoring focused -->
        </div>
      </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
    (function(){
      const CENTER = [7.3003, 125.6804];
      const map = L.map('monitorMap', {scrollWheelZoom:false}).setView(CENTER, 14);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
      const centerMarker = L.circleMarker(CENTER,{radius:9,weight:1,color:'#10b981',fillColor:'#34d399',fillOpacity:0.9}).addTo(map).bindPopup('Panabo City');
      const alertModal = document.getElementById('alertModal');
      const alertModalText = document.getElementById('alertModalText');
      const closeAlertModal = document.getElementById('closeAlertModal');
      const closeAlertModalTop = document.getElementById('closeAlertModalTop');
      let deviceMarkers = {};
      let deviceRadiusLayers = {};
      let lastUnhealthyAlertId = 0;
      let lastCriticalMq135Updated = 0;
      let lastShownCriticalMq135Updated = 0;
      let alertModalVisible = false;
      let criticalAlertToastTimer = null;
      const SENSOR_DETECTION_RADIUS_METERS = 100;
      function clearDeviceMarkers(){ Object.keys(deviceMarkers).forEach(k=>{ map.removeLayer(deviceMarkers[k]); delete deviceMarkers[k]; }); }
      function clearDeviceRadiusLayers(){ Object.keys(deviceRadiusLayers).forEach(k=>{ map.removeLayer(deviceRadiusLayers[k]); delete deviceRadiusLayers[k]; }); }
      function setBadge(elId, info){ const el = document.getElementById(elId); if(!el) return; if(!info){ el.className = 'text-sm font-medium px-2 py-1 rounded text-white bg-slate-300'; el.textContent = '—'; return; } el.style.backgroundColor = info.color || '#94a3b8'; el.textContent = info.label || info.level || '—'; try{ const iconId = elId.replace('badge-','icon-'); const iconEl = document.getElementById(iconId); if(iconEl) iconEl.textContent = info.icon || '👤'; }catch(e){}
      }
      function syncDetectionRadius(dev, color){
        const key = String(dev.id);
        const lat = parseFloat(dev.lat);
        const lng = parseFloat(dev.lng);
        if(!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        const ringStyle = {
          radius: SENSOR_DETECTION_RADIUS_METERS,
          color: color || '#ef4444',
          weight: 2,
          opacity: 0.85,
          dashArray: '6 8',
          fillColor: color || '#ef4444',
          fillOpacity: 0.08
        };
        const existingRing = deviceRadiusLayers[key];
        if(existingRing){
          existingRing.setLatLng([lat, lng]);
          existingRing.setStyle(ringStyle);
          return;
        }
        deviceRadiusLayers[key] = L.circle([lat, lng], ringStyle).addTo(map);
      }
      function isCriticalMq2Alert(a){
        if(!a) return false;
        if(String(a.pollutant || '').toLowerCase() !== 'mq135') return false;
        const value = Number(a.value);
        return Number.isFinite(value) && value > 2000;
      }
      function alertSummary(a){
        const label = 'CO';
        const value = a && a.value !== null && a.value !== undefined ? Number(a.value).toFixed(1) : '--';
        const unit = a && a.unit ? a.unit : '';
        const message = a && a.message ? a.message : `${label} is above 2000`;
        return `${label} ${value}${unit ? ' ' + unit : ''} • ${message}`;
      }
      function criticalReadingSummary(value){
        const num = Number(value);
        const safe = Number.isFinite(num) ? num.toFixed(1) : '--';
        return `CO ${safe} • Threshold exceeded (above 2000)`;
      }
      function showCriticalAlert(a, source){
        if(!isCriticalMq2Alert(a)) return;
        const latestId = Number(a.id || 0);
        if(latestId && latestId <= lastUnhealthyAlertId) return;
        if(alertModalText) alertModalText.textContent = alertSummary(a);
        if(alertModal) alertModal.classList.remove('hidden');
        if(alertModal) alertModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        alertModalVisible = true;
        if(criticalAlertToastTimer){ clearTimeout(criticalAlertToastTimer); criticalAlertToastTimer = null; }
        if('Notification' in window && Notification.permission === 'granted'){
          try{ new Notification('Critical CO alert', { body: alertSummary(a) }); }catch(e){}
        }
        lastUnhealthyAlertId = Math.max(lastUnhealthyAlertId, latestId);
      }
      function showCriticalReadingAlert(value, updatedAt){
        const numeric = Number(value);
        if(!Number.isFinite(numeric) || numeric <= 2000) return;
        if(updatedAt && updatedAt <= lastShownCriticalMq135Updated) return;
        if(alertModalText) alertModalText.textContent = criticalReadingSummary(numeric);
        if(alertModal) alertModal.classList.remove('hidden');
        if(alertModal) alertModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        alertModalVisible = true;
        lastShownCriticalMq135Updated = updatedAt || Math.floor(Date.now() / 1000);
        if('Notification' in window && Notification.permission === 'granted'){
          try{ new Notification('Critical CO reading', { body: criticalReadingSummary(numeric) }); }catch(e){}
        }
      }
      function hideAlertModal(){
        if(!alertModal) return;
        alertModal.classList.add('hidden');
        alertModal.classList.remove('flex');
        document.body.style.overflow = '';
        alertModalVisible = false;
      }
      if(closeAlertModal) closeAlertModal.addEventListener('click', hideAlertModal);
      if(closeAlertModalTop) closeAlertModalTop.addEventListener('click', hideAlertModal);
      if(alertModal) alertModal.addEventListener('click', (e)=>{ if(e.target === alertModal) hideAlertModal(); });
      function classifyPM25Obj(v){ if(v===null) return null; if(v<=12) return {level:'good',label:'Good',icon:'😊',color:'#10b981',severity:0}; if(v<=35) return {level:'fair',label:'Fair',icon:'🙂',color:'#f59e0b',severity:1}; if(v<=55) return {level:'unhealthy',label:'Unhealthy',icon:'😷',color:'#f97316',severity:2}; if(v<=150) return {level:'very_unhealthy',label:'Very Unhealthy',icon:'🤒',color:'#ef4444',severity:3}; return {level:'emergency',label:'Emergency',icon:'🆘',color:'#6b021d',severity:4}; }

      function escapeHtml(s){ if(s===null || s===undefined) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

      function fade(el, txt){ if(!el) return; el.style.transition='opacity .18s'; el.style.opacity = 0; setTimeout(()=>{ el.textContent = txt; el.style.opacity = 1; }, 160); }

      const pmMotion = {
        basePm25: null,
        basePm10: null,
        currentPm25: null,
        currentPm10: null,
        lastUpdateAt: 0,
        timer: null
      };

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

      let lastTempSample = { pm25: null, pm10: null };
      function renderTempSample(pm25, pm10){
        if(pm25 !== null){ fade(document.getElementById('val-pm25'), pm25 + ' µg/m³'); setBadge('badge-pm25', classifyPM25Obj(pm25)); }
        if(pm10 !== null){ fade(document.getElementById('val-pm10'), pm10 + ' µg/m³'); setBadge('badge-pm10', classifyPM25Obj(pm10)); }
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

      let demoDataSeeded = false;
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

      async function loadPredictions(cycleSeconds){
        try{
          const res = await fetch(`api/predictions.php?cycle_seconds=${cycleSeconds}&_=` + Date.now(), { cache: 'no-store' });
          const js = await res.json();
          if(!js.latest){
            const seeded = await ensureDemoData();
            if(seeded){
              const retry = await fetch(`api/predictions.php?cycle_seconds=${cycleSeconds}&_=` + Date.now(), { cache: 'no-store' });
              const retryJs = await retry.json();
              renderPredictionBox(retryJs);
              return;
            }
          }
          renderPredictionBox(js);
        }catch(err){ console.error('prediction load failed', err); }
      }

      let predictionCycleSeconds = 3600;
      let predictionTimer = null;

      function updateDeviceMarkers(devices){
        if(!devices || !Array.isArray(devices)) return;
        const keepIds = new Set(devices.map(d=>String(d.id)));
        Object.keys(deviceMarkers).forEach(k=>{ if(!keepIds.has(k)){ map.removeLayer(deviceMarkers[k]); delete deviceMarkers[k]; } });
        Object.keys(deviceRadiusLayers).forEach(k=>{ if(!keepIds.has(k)){ map.removeLayer(deviceRadiusLayers[k]); delete deviceRadiusLayers[k]; } });
        devices.forEach(dev=>{
          if(dev.lat === null || dev.lng === null) return;
          const statusInfo = classifyPM25Obj(dev.pm25);
          const iconEmoji = statusInfo ? statusInfo.icon : '👤';
          const color = statusInfo ? statusInfo.color : (dev.status === 'online' ? '#10b981' : '#94a3b8');
          syncDetectionRadius(dev, color);
          const html = `<div style="font-size:18px;width:36px;height:36px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:${color};color:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.25)">${iconEmoji}</div>`;
          const icon = L.divIcon({className:'', html:html, iconSize:[36,36], iconAnchor:[18,18]});
          const existing = deviceMarkers[String(dev.id)];
          if(existing){ existing.setLatLng([parseFloat(dev.lat), parseFloat(dev.lng)]); existing.setIcon(icon); existing._aqInfo = statusInfo; existing.bindPopup(`<strong>${escapeHtml(dev.name)}</strong><br/>Status: ${statusInfo?escapeHtml(statusInfo.label):escapeHtml(dev.status)}<br/>PM2.5: ${dev.pm25!==null?dev.pm25+' µg/m³':'No Data'}<br/>Last: ${dev.last_reading_at? new Date(dev.last_reading_at*1000).toLocaleString() : 'Never'}`);
          }else{
            const m = L.marker([parseFloat(dev.lat), parseFloat(dev.lng)], {icon: icon}).addTo(map);
            m._deviceId = dev.id;
            m._aqInfo = statusInfo;
            m.bindPopup(`<strong>${escapeHtml(dev.name)}</strong><br/>Status: ${statusInfo?escapeHtml(statusInfo.label):escapeHtml(dev.status)}<br/>PM2.5: ${dev.pm25!==null?dev.pm25+' µg/m³':'No Data'}<br/>Last: ${dev.last_reading_at? new Date(dev.last_reading_at*1000).toLocaleString() : 'Never'}`);
            deviceMarkers[String(dev.id)] = m;
          }
        });
      }
      async function loadOnce(){
        try{
          const [readingResp, dResp, devResp] = await Promise.all([fetch('api/readings.php?limit=1&sort=desc&start=0&end=9999999999', { cache: 'no-store' }), fetch('api/data.php'), fetch('api/devices.php')]);
          const readingJs = await readingResp.json();
          const data = await dResp.json();
          const devs = await devResp.json();
          const latest = (readingJs.readings || []).length ? readingJs.readings[(readingJs.readings || []).length - 1] : null;
          const pm25 = latest ? latest.pm25 : null;
          const pm10 = latest ? latest.pm10 : null;
          setPmMotion(pm25, pm10);
          const mq135 = data.mq135 ?? null;
          const dust = data.dust ?? null;
          const dataUpdated = Number(data.updated || data.created_at || 0) || 0;
          if(mq135 !== null && mq135 !== undefined){
            const mq135Value = Number(mq135);
            if(Number.isFinite(mq135Value) && mq135Value > 2000 && dataUpdated > lastCriticalMq135Updated){
              showCriticalReadingAlert(mq135Value, dataUpdated);
            }
            lastCriticalMq135Updated = Math.max(lastCriticalMq135Updated, dataUpdated);
          }
            if(pm25 !== null || lastTempSample.pm25 !== null){
              renderTempSample(pm25 !== null ? pm25 : lastTempSample.pm25, pm10 !== null ? pm10 : lastTempSample.pm10);
            }else{
              fade(document.getElementById('val-pm25'), '--');
              fade(document.getElementById('val-pm10'), '--');
            }
              fade(document.getElementById('val-mq135'), mq135 !== null ? mq135 : '--');
              fade(document.getElementById('val-dust'), dust !== null ? dust : '--');
            setBadge('badge-pm25', classifyPM25Obj(pm25 !== null ? pm25 : lastTempSample.pm25));
            setBadge('badge-pm10', classifyPM25Obj(pm10 !== null ? pm10 : lastTempSample.pm10));

          // last updated (use API timestamp if available)
          if(latest && latest.created_at){
            document.getElementById('lastUpdated').textContent = new Date(latest.created_at*1000).toLocaleString();
          }else if(data.updated){
            document.getElementById('lastUpdated').textContent = new Date(data.updated*1000).toLocaleString();
          }else{
            document.getElementById('lastUpdated').textContent = '';
          }
          updateDeviceMarkers(devs.devices);
        }catch(err){ console.error('Realtime load error',err); }
      }

      startPmMotion();
      loadOnce();
      setInterval(loadOnce,5000);
      loadPredictions(predictionCycleSeconds);
      if(predictionTimer) clearInterval(predictionTimer);
      predictionTimer = setInterval(()=>loadPredictions(predictionCycleSeconds), 60000);

      // realtime updates via Server-Sent Events (devices, readings, alerts)
      if(typeof(EventSource) !== 'undefined'){
        try{
          const devEs = new EventSource('api/devices_stream.php');
          devEs.addEventListener('devices', function(e){ try{ const payload = JSON.parse(e.data); updateDeviceMarkers(payload.devices || payload); }catch(err){ console.error('SSE parse',err); } });

          const rEs = new EventSource('api/readings_stream.php');
          rEs.addEventListener('reading', function(e){
            try{
              const r = JSON.parse(e.data);
              if(r.pm25 !== null || r.pm10 !== null) setPmMotion(r.pm25, r.pm10);
              renderPmMotion();
              document.getElementById('lastUpdated').textContent = new Date(r.created_at*1000).toLocaleString();
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
                existing.bindPopup(`<strong>${escapeHtml(existing.options.title || 'Device')}</strong><br/>Status: ${statusInfo?escapeHtml(statusInfo.label):'—'}<br/>PM2.5: ${r.pm25!==null?r.pm25+' µg/m³':'No Data'}<br/>Last: ${new Date(r.created_at*1000).toLocaleString()}`);

                // No alert creation on monitoring page; alerts managed server-side and in Alerts Management.
              }
            }catch(err){ console.error('reading SSE parse', err); }
          });


          // Alerts are handled in Alerts Management page; monitoring stays focused on telemetry.

                    const alertEs = new EventSource('api/alerts_stream.php');
                    alertEs.addEventListener('alert', function(e){
                      try{
                        const a = JSON.parse(e.data);
                        if(isCriticalMq2Alert(a) && Number(a.id || 0) > lastUnhealthyAlertId){
                          showCriticalAlert(a, 'sse');
                        }
                      }catch(err){ console.error('alert SSE parse', err); }
                    });
        }catch(err){ console.warn('SSE init failed', err); }
      }

      // Alerts are moved to Alerts Management page; monitoring code keeps telemetry only.

      document.addEventListener('DOMContentLoaded', ()=>{
        // Monitoring page initialized. Alerts are managed in Alerts Management.
        const predictionBtn = document.getElementById('predictionDemoBtn');
        if(predictionBtn){
          predictionBtn.addEventListener('click', ()=>{
            predictionCycleSeconds = predictionCycleSeconds === 3600 ? 30 : 3600;
            document.getElementById('predictionRefreshLabel').textContent = `Auto-refresh: ${predictionCycleSeconds}s`;
            loadPredictions(predictionCycleSeconds);
            if(predictionTimer) clearInterval(predictionTimer);
            predictionTimer = setInterval(()=>loadPredictions(predictionCycleSeconds), predictionCycleSeconds === 30 ? 30000 : 60000);
          });
        }
      });
    })();
    </script>
</body>
</html>
