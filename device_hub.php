<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Location: login.php'); exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Device Hub — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      html,body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Arial,sans-serif}
      .bg-ambient{background:radial-gradient(circle at top left,#dff8ee 0,#f6fffb 35%,#ffffff 72%) fixed}
      .glass{background:rgba(255,255,255,0.78);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,0.6)}
      .device-card{transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease}
      .device-card:hover{transform:translateY(-1px)}
      .device-card.selected{border-color:#059669;box-shadow:0 18px 40px rgba(5,150,105,.14)}
      #deviceHubMap{height:560px;min-height:460px}
      .leaflet-container{border-radius:1rem}
      .device-dot{width:34px;height:34px;border-radius:9999px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:700;box-shadow:0 6px 18px rgba(0,0,0,.18);border:2px solid rgba(255,255,255,.92)}
      .device-dot.selected{transform:scale(1.12);box-shadow:0 10px 24px rgba(0,0,0,.22)}
      .device-mini-label{font-size:11px;letter-spacing:.08em;text-transform:uppercase}
    </style>
  </head>
  <body class="bg-ambient text-slate-900 antialiased min-h-screen">
    <div class="flex">
      <?php include __DIR__ . '/partials/sidebar.php'; ?>

      <main class="flex-1 p-6 lg:p-8">
        <div class="max-w-7xl mx-auto space-y-6">
          <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p class="text-xs font-semibold tracking-[0.22em] uppercase text-emerald-700">Device Hub</p>
              <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 mt-1">Three devices, one live map</h1>
              <p class="text-sm text-slate-600 mt-2 max-w-2xl">Select a device, drag its marker, or click a new spot on the map to update location. Use New device to start another record after saving.</p>
            </div>
            <div class="glass rounded-2xl px-4 py-3 shadow-sm text-sm text-slate-600">
              Map edits are saved to the device record immediately after moving a marker.
            </div>
          </div>

          <div class="grid grid-cols-1 xl:grid-cols-[1.3fr_0.7fr] gap-6">
            <section class="glass rounded-3xl shadow-lg p-4 lg:p-5">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h2 class="text-lg font-semibold text-slate-900">Map view</h2>
                  <p class="text-sm text-slate-500">Drag a marker or click on the map after selecting a device.</p>
                </div>
                <div id="deviceCountBadge" class="text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">Loading devices...</div>
              </div>
              <div id="deviceHubMap" class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-100"></div>
            </section>

            <aside class="space-y-6">
              <section class="glass rounded-3xl shadow-lg p-5">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <h2 class="text-lg font-semibold text-slate-900">Devices</h2>
                    <p class="text-sm text-slate-500">Choose a device, or create another one after saving a location.</p>
                  </div>
                </div>
                <div id="deviceCards" class="space-y-3"></div>
              </section>

              <section class="glass rounded-3xl shadow-lg p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                  <div>
                    <h2 class="text-lg font-semibold text-slate-900">Edit device</h2>
                    <p class="text-sm text-slate-500">Update details or move the marker location.</p>
                  </div>
                  <span id="selectedDeviceChip" class="device-mini-label px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200">New device</span>
                </div>

                <form id="deviceForm" class="space-y-4">
                  <input type="hidden" id="deviceId" value="" />
                  <div>
                    <label class="block text-sm font-medium text-slate-700">Device name</label>
                    <input id="deviceName" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-400" placeholder="Device name" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select id="deviceStatus" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                      <option value="online">Online</option>
                      <option value="offline">Offline</option>
                    </select>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Latitude</label>
                      <input id="deviceLat" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-400" placeholder="7.300300" />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-slate-700">Longitude</label>
                      <input id="deviceLng" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-400" placeholder="125.680400" />
                    </div>
                  </div>
                  <p class="text-xs text-slate-500">Tip: drag the marker or click the map to change the location, then press save. Use New device to start another record.</p>
                  <div class="flex flex-wrap gap-2">
                    <button type="button" id="saveDeviceBtn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow-sm hover:bg-emerald-700">Save device</button>
                    <button type="button" id="newDeviceBtn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 text-white font-semibold shadow-sm hover:bg-sky-700">New device</button>
                    <button type="button" id="focusMapBtn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-semibold border border-slate-200 hover:bg-slate-200">Focus on map</button>
                  </div>
                </form>
              </section>
            </aside>
          </div>
        </div>
      </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
      (function(){
        const CENTER = [7.3003, 125.6804];
        const DEFAULT_DEVICES = [
          { name: 'Device Alpha', lat: 7.3003, lng: 125.6804, status: 'online' },
          { name: 'Device Beta', lat: 7.3032, lng: 125.6842, status: 'offline' },
          { name: 'Device Gamma', lat: 7.2976, lng: 125.6768, status: 'online' }
        ];

        const active = document.querySelector('#ab-sidebar nav a[data-name="devices"]');
        if(active){ active.classList.add('bg-gradient-to-r','from-emerald-600','to-sky-500','text-black'); }

        const map = L.map('deviceHubMap', { scrollWheelZoom: false }).setView(CENTER, 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.circleMarker(CENTER, {
          radius: 8,
          weight: 1,
          color: '#0f766e',
          fillColor: '#34d399',
          fillOpacity: 0.95
        }).addTo(map).bindPopup('Center point');

        function escapeHtml(s){
          if(s === null || s === undefined) return '';
          return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
        }

        function formatStatus(status){
          return String(status || 'offline').toLowerCase() === 'online' ? 'online' : 'offline';
        }

        function statusMeta(status){
          const normalized = formatStatus(status);
          return normalized === 'online'
            ? { label: 'Online', className: 'bg-emerald-100 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' }
            : { label: 'Offline', className: 'bg-slate-100 text-slate-600 border-slate-200', dot: 'bg-slate-400' };
        }

        function toNumber(value){
          const number = Number(value);
          return Number.isFinite(number) ? number : null;
        }

        function normalizeDevice(device){
          return {
            id: Number(device.id),
            name: device.name || 'Device',
            lat: device.lat !== null && device.lat !== undefined ? Number(device.lat) : null,
            lng: device.lng !== null && device.lng !== undefined ? Number(device.lng) : null,
            status: formatStatus(device.status),
            last_updated: device.last_updated ? Number(device.last_updated) : null
          };
        }

        function sortDevicesCache(){
          devicesCache.sort((left, right) => Number(left.id) - Number(right.id));
        }

        let devicesCache = [];
        let markers = {};
        let selectedDeviceId = null;
        let isSyncingFromMap = false;
        let isCreatingNewDevice = false;

        function getSelectedDevice(){
          return devicesCache.find(device => String(device.id) === String(selectedDeviceId)) || null;
        }

        function setCountBadge(text, tone){
          const el = document.getElementById('deviceCountBadge');
          if(!el) return;
          el.textContent = text;
          el.className = tone || 'text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100';
        }

        function syncForm(device){
          const formId = document.getElementById('deviceId');
          const formName = document.getElementById('deviceName');
          const formStatus = document.getElementById('deviceStatus');
          const formLat = document.getElementById('deviceLat');
          const formLng = document.getElementById('deviceLng');
          const chip = document.getElementById('selectedDeviceChip');

          if(!device){
            formId.value = '';
            formName.value = '';
            formStatus.value = 'offline';
            formLat.value = '';
            formLng.value = '';
            chip.textContent = 'New device';
            chip.className = 'device-mini-label px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200';
            return;
          }

          formId.value = device.id;
          formName.value = device.name || '';
          formStatus.value = device.status || 'offline';
          formLat.value = device.lat !== null ? Number(device.lat).toFixed(6) : '';
          formLng.value = device.lng !== null ? Number(device.lng).toFixed(6) : '';
          const meta = statusMeta(device.status);
          chip.textContent = device.name;
          chip.className = 'device-mini-label px-3 py-1 rounded-full border ' + meta.className;
        }

        function startNewDevice(){
          isCreatingNewDevice = true;
          selectedDeviceId = null;
          syncForm(null);
          renderCards();
          renderMarkers();
          document.getElementById('deviceName').focus();
        }

        function makeIcon(device, selected){
          const background = device.status === 'online' ? '#059669' : '#64748b';
          const label = selected ? '•' : escapeHtml(String(device.name || '?').trim().charAt(0).toUpperCase());
          const html = `<div class="device-dot ${selected ? 'selected' : ''}" style="background:${background}">${label}</div>`;
          return L.divIcon({ className: '', html: html, iconSize: [34, 34], iconAnchor: [17, 17] });
        }

        function renderCards(){
          const container = document.getElementById('deviceCards');
          container.innerHTML = '';

          devicesCache.forEach(device => {
            const meta = statusMeta(device.status);
            const latText = device.lat !== null ? Number(device.lat).toFixed(6) : 'Not set';
            const lngText = device.lng !== null ? Number(device.lng).toFixed(6) : 'Not set';
            const lastSeen = device.last_updated ? new Date(device.last_updated * 1000).toLocaleString() : 'Never';
            const card = document.createElement('button');
            card.type = 'button';
            card.dataset.id = device.id;
            card.className = 'device-card w-full text-left rounded-2xl border p-4 ' + (String(selectedDeviceId) === String(device.id) ? 'selected border-emerald-300 bg-white' : 'border-slate-200 bg-white/90');
            card.innerHTML = `
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full ${meta.dot}"></span>
                    <h3 class="font-semibold text-slate-900">${escapeHtml(device.name)}</h3>
                  </div>
                  <p class="text-xs text-slate-500 mt-1">Device ID ${escapeHtml(device.id)}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full border ${meta.className}">${meta.label}</span>
              </div>
              <div class="grid grid-cols-2 gap-3 mt-4 text-sm">
                <div class="rounded-xl bg-slate-50 p-3">
                  <div class="text-xs text-slate-500">Latitude</div>
                  <div class="font-medium text-slate-800 mt-1">${escapeHtml(latText)}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                  <div class="text-xs text-slate-500">Longitude</div>
                  <div class="font-medium text-slate-800 mt-1">${escapeHtml(lngText)}</div>
                </div>
              </div>
              <div class="mt-3 text-xs text-slate-500">Last updated: ${escapeHtml(lastSeen)}</div>
            `;
            container.appendChild(card);
          });
        }

        function renderMarkers(){
          const keepIds = new Set(devicesCache.map(device => String(device.id)));
          Object.keys(markers).forEach(id => {
            if(!keepIds.has(id)){
              map.removeLayer(markers[id]);
              delete markers[id];
            }
          });

          devicesCache.forEach(device => {
            if(device.lat === null || device.lng === null) return;
            const icon = makeIcon(device, String(selectedDeviceId) === String(device.id));
            const position = [Number(device.lat), Number(device.lng)];
            const popup = `<strong>${escapeHtml(device.name)}</strong><br>Status: ${escapeHtml(statusMeta(device.status).label)}<br>Lat: ${Number(device.lat).toFixed(6)}<br>Lng: ${Number(device.lng).toFixed(6)}`;
            const existing = markers[String(device.id)];

            if(existing){
              existing.setLatLng(position);
              existing.setIcon(icon);
              existing.bindPopup(popup);
              return;
            }

            const marker = L.marker(position, { icon: icon, draggable: true }).addTo(map);
            marker.bindPopup(popup);
            marker.on('click', () => selectDevice(device.id, true));
            marker.on('dragstart', () => selectDevice(device.id, false));
            marker.on('dragend', async (event) => {
              const latlng = event.target.getLatLng();
              await updateDeviceLocation(device.id, latlng.lat, latlng.lng, true);
            });
            markers[String(device.id)] = marker;
          });
        }

        function selectDevice(id, focusMap){
          isCreatingNewDevice = false;
          selectedDeviceId = String(id);
          const device = getSelectedDevice();
          syncForm(device);
          renderCards();
          renderMarkers();
          if(focusMap && device && device.lat !== null && device.lng !== null){
            map.setView([device.lat, device.lng], Math.max(map.getZoom(), 15), { animate: true });
          }
        }

        async function updateDeviceLocation(id, lat, lng, persist){
          const device = devicesCache.find(item => String(item.id) === String(id));
          if(!device) return;
          device.lat = lat;
          device.lng = lng;
          syncForm(device);
          renderCards();
          renderMarkers();

          if(persist){
            await saveDevice(device.id, { lat, lng }, true);
          }
        }

        async function fetchDevices(){
          const response = await fetch('api/devices.php', { cache: 'no-store', credentials: 'same-origin' });
          const json = await response.json();
          return Array.isArray(json.devices) ? json.devices.map(normalizeDevice) : [];
        }

        async function createDefaultDevices(){
          for(let index = 0; index < DEFAULT_DEVICES.length; index += 1){
            const device = DEFAULT_DEVICES[index];
            await fetch('api/devices.php', {
              method: 'POST',
              credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(device)
            });
          }
        }

        async function loadDevices(){
          try{
            let devices = await fetchDevices();
            if(devices.length === 0){
              await createDefaultDevices();
              devices = await fetchDevices();
            }

            devicesCache = devices;
            sortDevicesCache();
            setCountBadge(devicesCache.length + ' devices active');

            if(!selectedDeviceId && devicesCache.length && !isCreatingNewDevice){
              selectedDeviceId = String(devicesCache[0].id);
            }else if(selectedDeviceId && !devicesCache.some(device => String(device.id) === String(selectedDeviceId))){
              selectedDeviceId = devicesCache.length ? String(devicesCache[0].id) : null;
            }

            syncForm(isCreatingNewDevice ? null : getSelectedDevice());
            renderCards();
            renderMarkers();
          }catch(error){
            console.error('Failed to load devices', error);
            setCountBadge('Failed to load', 'text-xs font-semibold px-3 py-1 rounded-full bg-red-50 text-red-700 border border-red-100');
            const container = document.getElementById('deviceCards');
            container.innerHTML = '<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">Failed to load devices.</div>';
          }
        }

        async function saveDevice(id, forcedFields, silent){
          const current = id ? devicesCache.find(device => String(device.id) === String(id)) : null;
          const name = forcedFields && Object.prototype.hasOwnProperty.call(forcedFields, 'name') ? String(forcedFields.name || '').trim() : document.getElementById('deviceName').value.trim();
          const status = forcedFields && Object.prototype.hasOwnProperty.call(forcedFields, 'status') ? formatStatus(forcedFields.status) : formatStatus(document.getElementById('deviceStatus').value);
          const latValue = forcedFields && Object.prototype.hasOwnProperty.call(forcedFields, 'lat') ? forcedFields.lat : document.getElementById('deviceLat').value.trim();
          const lngValue = forcedFields && Object.prototype.hasOwnProperty.call(forcedFields, 'lng') ? forcedFields.lng : document.getElementById('deviceLng').value.trim();

          if(!name){
            alert('Device name is required');
            return;
          }

          const payload = {
            name,
            status,
            lat: latValue === '' || latValue === null || latValue === undefined ? null : toNumber(latValue),
            lng: lngValue === '' || lngValue === null || lngValue === undefined ? null : toNumber(lngValue)
          };

          if(current){
            payload.id = current.id;
          }

          try{
            const response = await fetch('api/devices.php', {
              method: current ? 'PUT' : 'POST',
              credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload)
            });
            const json = await response.json();
            if(!json.success){
              throw new Error(json.error || 'Save failed');
            }

            const updated = json.device ? normalizeDevice(json.device) : null;
            if(updated){
              const index = devicesCache.findIndex(device => String(device.id) === String(updated.id));
              if(index !== -1){
                devicesCache[index] = updated;
              }else{
                devicesCache.push(updated);
              }
              sortDevicesCache();
              selectedDeviceId = String(updated.id);
              isCreatingNewDevice = false;
            }

            syncForm(getSelectedDevice());
            renderCards();
            renderMarkers();
            if(!silent){
              setCountBadge('Saved ' + (getSelectedDevice() ? getSelectedDevice().name : 'device'));
            }
          }catch(error){
            console.error(error);
            if(!silent) alert('Save failed');
          }
        }

        document.getElementById('deviceCards').addEventListener('click', function(event){
          const card = event.target.closest('[data-id]');
          if(!card) return;
          selectDevice(card.dataset.id, true);
        });

        document.getElementById('saveDeviceBtn').addEventListener('click', function(){
          const id = document.getElementById('deviceId').value;
          saveDevice(id, null, false);
        });

        document.getElementById('newDeviceBtn').addEventListener('click', function(){
          startNewDevice();
        });

        document.getElementById('focusMapBtn').addEventListener('click', function(){
          const device = getSelectedDevice();
          if(!device || device.lat === null || device.lng === null) return alert('Select a device with a location first');
          map.setView([device.lat, device.lng], Math.max(map.getZoom(), 15), { animate: true });
        });

        map.on('click', async function(event){
          const device = getSelectedDevice();
          if(!device){
            document.getElementById('deviceLat').value = Number(event.latlng.lat).toFixed(6);
            document.getElementById('deviceLng').value = Number(event.latlng.lng).toFixed(6);
            return;
          }
          isSyncingFromMap = true;
          document.getElementById('deviceLat').value = Number(event.latlng.lat).toFixed(6);
          document.getElementById('deviceLng').value = Number(event.latlng.lng).toFixed(6);
          await saveDevice(device.id, { lat: event.latlng.lat, lng: event.latlng.lng }, true);
          isSyncingFromMap = false;
        });

        document.getElementById('deviceName').addEventListener('change', function(){
          const device = getSelectedDevice();
          if(!device) return;
          device.name = this.value.trim();
          renderCards();
          renderMarkers();
        });

        document.getElementById('deviceStatus').addEventListener('change', function(){
          const device = getSelectedDevice();
          if(!device) return;
          device.status = formatStatus(this.value);
          renderCards();
          renderMarkers();
        });

        document.getElementById('deviceLat').addEventListener('change', function(){
          const device = getSelectedDevice();
          if(!device) return;
          const lat = toNumber(this.value.trim());
          const lng = toNumber(document.getElementById('deviceLng').value.trim());
          if(lat === null) return;
          device.lat = lat;
          if(device.lng !== null){
            renderCards();
            renderMarkers();
          }
          if(!isSyncingFromMap && lng !== null){
            saveDevice(device.id, { lat, lng }, true);
          }
        });

        document.getElementById('deviceLng').addEventListener('change', function(){
          const device = getSelectedDevice();
          if(!device) return;
          const lng = toNumber(this.value.trim());
          const lat = toNumber(document.getElementById('deviceLat').value.trim());
          if(lng === null) return;
          device.lng = lng;
          if(device.lat !== null){
            renderCards();
            renderMarkers();
          }
          if(!isSyncingFromMap && lat !== null){
            saveDevice(device.id, { lat, lng }, true);
          }
        });

        loadDevices();
        setInterval(loadDevices, 15000);
      })();
    </script>
  </body>
</html>
