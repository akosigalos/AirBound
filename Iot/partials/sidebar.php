<?php
// Shared sidebar partial with collapse behavior and active highlighting
if(session_status() !== PHP_SESSION_ACTIVE) session_start();
$current = basename($_SERVER['PHP_SELF']);
?>
<style>
  :root {
    --ab-sidebar-width: 18rem;
    --ab-sidebar-collapsed-width: 5.25rem;
    --ab-transition: 280ms;

    /* Color system tuned to match the soft green dashboard */
    --ab-primary: #34d399;
    --ab-accent: #10b981;

    /* Sidebar surface and text */
    --ab-sidebar-bg: linear-gradient(180deg, rgba(255,255,255,0.90), rgba(236,253,245,0.84));
    --ab-sidebar-text: #0f172a;
    --ab-sidebar-subtext: #64748b;

    /* Icon / surface tones */
    --ab-icon-bg: rgba(16,185,129,0.08);
    --ab-icon-color: #0f172a;

    /* Interaction states */
    --ab-hover-bg: rgba(16,185,129,0.08);
    --ab-active-bg: linear-gradient(90deg, rgba(16,185,129,0.14), rgba(52,211,153,0.08));
  }

  .ab-sidebar {
    width: var(--ab-sidebar-width) !important;
    min-width: var(--ab-sidebar-width) !important;
    overflow: hidden;
    transition:
      width var(--ab-transition) ease-in-out,
      min-width var(--ab-transition) ease-in-out,
      box-shadow var(--ab-transition) ease-in-out;
    background: var(--ab-sidebar-bg);
    color: var(--ab-sidebar-text);
    border-right: 1px solid rgba(15,23,42,0.05);
    box-shadow: 0 12px 32px rgba(16,185,129,0.08);
  }

  body.ab-sidebar-collapsed .ab-sidebar {
    width: var(--ab-sidebar-collapsed-width) !important;
    min-width: var(--ab-sidebar-collapsed-width) !important;
  }

  .ab-sidebar .brand-copy,
  .ab-sidebar .label {
    display: inline-block;
    white-space: nowrap;
    transform-origin: left center;
    transition: opacity 180ms ease-in-out, transform 180ms ease-in-out;
  }

  body.ab-sidebar-collapsed .ab-sidebar .brand-copy,
  body.ab-sidebar-collapsed .ab-sidebar .label {
    opacity: 0;
    transform: translateX(-6px);
    pointer-events: none;
  }

  body.ab-sidebar-collapsed .ab-sidebar .brand-copy {
    flex: 0 0 0 !important;
    width: 0;
    max-width: 0;
    overflow: hidden;
  }

  .ab-sidebar .ab-sidebar-head {
    transition: padding 180ms ease-in-out, gap 180ms ease-in-out;
    border-bottom: 1px solid rgba(15,23,42,0.05);
  }

  body.ab-sidebar-collapsed .ab-sidebar .ab-sidebar-head {
    justify-content: center;
    gap: 0.3rem;
    padding-left: 0.35rem;
    padding-right: 0.35rem;
  }

  body.ab-sidebar-collapsed .ab-sidebar .ab-brand-mark {
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 0.55rem;
    font-size: 0.7rem;
  }

  body.ab-sidebar-collapsed .ab-sidebar #sidebar-toggle {
    width: 1.9rem;
    height: 1.9rem;
    min-width: 1.9rem;
    margin-left: 0 !important;
  }

  body.ab-sidebar-collapsed .ab-sidebar #sidebar-toggle-icon {
    width: 1rem;
    height: 1rem;
  }

  .ab-sidebar .nav-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0.875rem;
    border-radius: 0.75rem;
    transition: background var(--ab-transition) ease-in-out, transform var(--ab-transition) ease-in-out, box-shadow var(--ab-transition) ease-in-out, color var(--ab-transition) ease-in-out;
  }

  .ab-sidebar .nav-item .nav-copy {
    min-width: 0;
    flex: 1;
  }

  body.ab-sidebar-collapsed .ab-sidebar .nav-item {
    justify-content: center;
    gap: 0;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }

  body.ab-sidebar-collapsed .ab-sidebar .nav-item .nav-copy {
    display: none;
  }

  .ab-sidebar .nav-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.6rem;
    background: var(--ab-icon-bg);
    color: var(--ab-icon-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background var(--ab-transition) ease-in-out, color var(--ab-transition) ease-in-out;
  }

  .ab-sidebar .nav-item:hover {
    background: var(--ab-hover-bg);
    transform: translateX(3px);
    box-shadow: 0 10px 24px rgba(2, 6, 23, 0.06);
  }

  .ab-sidebar .nav-item:hover .nav-icon {
    background: linear-gradient(135deg, #34d399, #5eead4);
    color: #ffffff;
  }

  .ab-sidebar .nav-item.active {
    background: var(--ab-active-bg);
    color: var(--ab-sidebar-text);
    box-shadow: 0 10px 24px rgba(16,185,129,0.10);
  }

  .ab-sidebar .nav-item.active::before {
    content: "";
    position: absolute;
    left: -0.25rem;
    top: 18%;
    height: 64%;
    width: 0.18rem;
    border-radius: 999px;
    background: var(--ab-accent);
  }

  body.ab-sidebar-collapsed .ab-sidebar .nav-item.active::before {
    display: none;
  }

  .ab-sidebar .nav-item.active .nav-icon {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: #ffffff;
  }

  #sidebar-toggle-icon {
    transition: transform 220ms ease-in-out;
  }

  body.ab-sidebar-collapsed #sidebar-toggle-icon {
    transform: rotate(180deg);
  }

  .ab-sidebar .ab-footer-copy {
    transition: opacity var(--ab-transition) ease-in-out;
    color: var(--ab-sidebar-subtext);
  }

  body.ab-sidebar-collapsed .ab-sidebar .ab-footer-copy {
    opacity: 0;
  }

  .ab-sidebar .ab-logout-wrap {
    position: absolute;
    left: 0.75rem;
    right: 0.75rem;
    bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .ab-sidebar .ab-logout-user {
    max-width: 6.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .ab-sidebar .ab-logout {
    flex: 1;
    justify-content: center;
    transition: transform 180ms ease-in-out, box-shadow 180ms ease-in-out;
  }

  .ab-sidebar .ab-logout:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.18);
  }

  body.ab-sidebar-collapsed .ab-sidebar .ab-logout-wrap {
    left: 0.4rem;
    right: 0.4rem;
    justify-content: center;
  }

  body.ab-sidebar-collapsed .ab-sidebar .ab-logout-user {
    display: none;
  }

  body.ab-sidebar-collapsed .ab-sidebar .ab-logout {
    width: 3rem;
    min-width: 3rem;
    padding: 0.55rem;
    gap: 0;
  }

  @media (max-width: 768px) {
    .ab-sidebar {
      position: fixed;
      left: calc(-1 * var(--ab-sidebar-width));
      top: 0;
      height: 100vh;
      z-index: 40;
      transition: left var(--ab-transition) ease-in-out, box-shadow var(--ab-transition) ease-in-out;
      width: var(--ab-sidebar-width) !important;
      min-width: var(--ab-sidebar-width) !important;
    }

    body.ab-sidebar-open .ab-sidebar {
      left: 0;
      box-shadow: 0 16px 48px rgba(2, 6, 23, 0.24);
    }

    body.ab-sidebar-collapsed .ab-sidebar .brand-copy,
    body.ab-sidebar-collapsed .ab-sidebar .label {
      opacity: 1;
      transform: none;
      pointer-events: auto;
    }

    body.ab-sidebar-collapsed .ab-sidebar .brand-copy {
      flex: 1 1 auto !important;
      width: auto;
      max-width: none;
      overflow: visible;
    }

    body.ab-sidebar-collapsed .ab-sidebar .nav-item .nav-copy {
      display: block;
    }
  }

</style>

<aside id="ab-sidebar" class="ab-sidebar relative z-20 w-72 max-w-[18rem] min-h-screen text-slate-900 shadow-md" aria-label="Primary navigation">
  <div class="ab-sidebar-head flex items-center gap-3 px-5 py-4 border-b border-slate-200">
    <div class="ab-brand-mark w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-300 to-teal-400 flex items-center justify-center text-white font-extrabold shadow-sm">AB</div>
    <div class="brand-copy flex-1">
      <div class="text-sm font-semibold leading-tight">AIR-BOUND</div>
    </div>

      <button id="sidebar-toggle" aria-expanded="true" class="ml-3 inline-flex items-center justify-center w-9 h-9 rounded-md bg-white hover:bg-emerald-50 text-slate-600 focus:outline-none border border-slate-200" title="Toggle menu">
        <svg id="sidebar-toggle-icon" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
  </div>

  <nav class="px-2 py-4 space-y-1" aria-label="Main">
    <?php $homeActive = ($current==='home.php'); ?>
    <a href="home.php" class="nav-item group <?php echo $homeActive ? 'active' : ''; ?>" <?php echo $homeActive ? 'aria-current="page"' : ''; ?>>
      <span class="nav-icon inline-flex items-center justify-center">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 11.5L12 4l9 7.5" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 10.5V20h12v-9.5" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="nav-copy flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <span class="text-sm font-medium label">Home</span>
        </div>
      </div>
    </a>

    <a href="admin_monitoring.php#monitoringSection" class="nav-item group <?php echo $current==='admin_monitoring.php' ? 'active' : ''; ?>" <?php echo $current==='admin_monitoring.php' ? 'aria-current="page"' : ''; ?>>
      <span class="nav-icon inline-flex items-center justify-center">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3v6l4-2" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="nav-copy flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <span class="text-sm font-medium label">Real-Time Monitoring</span>
        </div>
      </div>
    </a>

    <!-- Geo-spatial Mapping removed per request -->

    <?php $alertsActive = ($current==='alerts.php'); ?>
    <a href="alerts.php" class="nav-item group <?php echo $alertsActive ? 'active' : ''; ?>" <?php echo $alertsActive ? 'aria-current="page"' : ''; ?>>
      <span class="nav-icon inline-flex items-center justify-center">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="nav-copy flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <span class="text-sm font-medium label">Alert System</span>
        </div>
      </div>
    </a>

    <a href="device_hub.php" class="nav-item group <?php echo $current==='device_hub.php' ? 'active' : ''; ?>" <?php echo $current==='device_hub.php' ? 'aria-current="page"' : ''; ?>>
      <span class="nav-icon inline-flex items-center justify-center">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 3v4M8 3v4" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="nav-copy flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <span class="text-sm font-medium label">Device Hub</span>
        </div>
      </div>
    </a>

    
    <a href="admin_analytics.php" class="nav-item group <?php echo $current==='admin_analytics.php' ? 'active' : ''; ?>" <?php echo $current==='admin_analytics.php' ? 'aria-current="page"' : ''; ?>>
      <span class="nav-icon inline-flex items-center justify-center">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 3v18M4 8h14M4 16h14" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <div class="nav-copy flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <span class="text-sm font-medium label">Admin Analytics</span>
        </div>
      </div>
    </a>
  </nav>

  <div class="ab-footer-copy mt-auto px-4 py-6 border-t border-slate-200 text-slate-500 text-xs">© AIR-BOUND</div>

  <!-- Logout placed at the absolute bottom so it remains anchored when sidebar grows/shrinks -->
  <div class="ab-logout-wrap">
    <button id="ab-logout-btn" class="ab-logout flex items-center gap-3 px-3 py-2 rounded-md bg-red-600 text-white hover:bg-red-700 focus:outline-none" title="Logout">
      <span class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-red-700/20">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 17l5-5-5-5M21 12H9" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span class="label text-sm font-medium">Logout</span>
    </button>
  </div>
</aside>

<script>
(function(){
  const sidebar = document.getElementById('ab-sidebar');
  const toggle = document.getElementById('sidebar-toggle');
  const storageKey = 'ab_sidebar_collapsed';

  // Backdrop for mobile overlay
  let backdrop = document.getElementById('ab-sidebar-backdrop');
  function ensureBackdrop(){
    if(backdrop) return backdrop;
    backdrop = document.createElement('div');
    backdrop.id = 'ab-sidebar-backdrop';
    backdrop.style.position = 'fixed';
    backdrop.style.inset = '0';
    backdrop.style.background = 'rgba(2,6,23,0.4)';
    backdrop.style.zIndex = '30';
    backdrop.style.opacity = '0';
    backdrop.style.transition = 'opacity 180ms ease';
    backdrop.style.pointerEvents = 'none';
    document.body.appendChild(backdrop);
    backdrop.addEventListener('click', ()=>{ closeOverlay(); });
    return backdrop;
  }

  function applyCollapsed(collapsed){
    document.body.classList.toggle('ab-sidebar-collapsed', collapsed);
    // ensure overlay isn't left open
    document.body.classList.remove('ab-sidebar-open');
    toggle.setAttribute('aria-expanded', String(!collapsed));
    try{ localStorage.setItem(storageKey, collapsed ? '1' : '0'); }catch(e){}
  }

  function openOverlay(){
    ensureBackdrop();
    document.body.classList.add('ab-sidebar-open');
    document.body.classList.remove('ab-sidebar-collapsed');
    backdrop.style.pointerEvents = 'auto';
    requestAnimationFrame(()=> backdrop.style.opacity = '1');
    toggle.setAttribute('aria-expanded','true');
  }

  function closeOverlay(){
    document.body.classList.remove('ab-sidebar-open');
    if(backdrop){ backdrop.style.opacity = '0'; backdrop.style.pointerEvents = 'none'; }
    toggle.setAttribute('aria-expanded','false');
  }

  // init from storage for desktop
  try{
    const stored = localStorage.getItem(storageKey);
    if(stored === '1') applyCollapsed(true);
  }catch(e){}

  toggle.addEventListener('click', ()=>{
    const isMobile = window.matchMedia('(max-width: 768px)').matches;
    if(isMobile){
      const open = document.body.classList.contains('ab-sidebar-open');
      if(open) closeOverlay(); else openOverlay();
      return;
    }
    const collapsed = document.body.classList.contains('ab-sidebar-collapsed');
    applyCollapsed(!collapsed);
  });

  // keyboard accessibility
  toggle.addEventListener('keydown',(e)=>{ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); toggle.click(); } });

  // close overlay on escape
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape'){ closeOverlay(); } });

  // close mobile overlay after a menu click
  sidebar.querySelectorAll('.nav-item').forEach((item)=>{
    item.addEventListener('click', ()=>{
      if(window.matchMedia('(max-width: 768px)').matches){ closeOverlay(); }
    });
  });

  // ensure overlay state resets when resizing
  window.addEventListener('resize', ()=>{
    if(window.matchMedia('(min-width: 769px)').matches){ closeOverlay(); }
  });
})();
</script>

<script>
// Logout handler: POST to logout endpoint then redirect to login
document.getElementById('ab-logout-btn')?.addEventListener('click', function(e){
  e.preventDefault();
  fetch('api/logout.php', { method: 'POST', credentials: 'same-origin' })
    .then(()=> { window.location = 'login.php'; })
    .catch(()=> { window.location = 'login.php'; });
});
</script>


