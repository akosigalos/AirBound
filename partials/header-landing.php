<?php
// Landing-style top navigation (public)
?>
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
      // Header dropdown: portal-based clone to avoid stacking/clipping issues across pages
      document.addEventListener('DOMContentLoaded', function(){
        const btn = document.getElementById('aq-dropdown-btn');
        const menu = document.getElementById('aq-dropdown-menu');
        if(!btn || !menu) return;

        let portal = null;

        function setAria(open){ btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }

        function repositionPortal(){
          if(!portal) return;
          const rect = btn.getBoundingClientRect();
          portal.style.left = rect.left + 'px';
          portal.style.top = rect.bottom + 'px';
        }

        function openMenu(){
          if(portal) return;
          portal = menu.cloneNode(true);
          portal.id = menu.id + '-portal';
          portal.classList.remove('hidden');
          portal.style.position = 'fixed';
          portal.style.zIndex = '2147483647';
          portal.style.minWidth = menu.style.minWidth || '14rem';
          const rect = btn.getBoundingClientRect();
          portal.style.left = rect.left + 'px';
          portal.style.top = rect.bottom + 'px';

          // Close when a link is clicked
          portal.querySelectorAll('a').forEach(a => a.addEventListener('click', function(){ closeMenu(); }));
          document.body.appendChild(portal);
          setAria(true);
          window.addEventListener('resize', repositionPortal);
          window.addEventListener('scroll', repositionPortal, true);
        }

        function closeMenu(){
          if(!portal) return;
          portal.remove(); portal = null;
          setAria(false);
          window.removeEventListener('resize', repositionPortal);
          window.removeEventListener('scroll', repositionPortal, true);
        }

        btn.addEventListener('click', function(e){
          e.preventDefault(); e.stopPropagation();
          if(portal) closeMenu(); else openMenu();
        });

        document.addEventListener('click', function(e){ if(portal && !btn.contains(e.target) && !portal.contains(e.target)) closeMenu(); });
        document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && portal) closeMenu(); });
      });

      // Navigate to Laws & Policies page (exposed globally for inline onclick)
      window.gotoLawPolicies = function(e){ if(e && e.preventDefault) e.preventDefault(); window.location.href = 'lawpolicies.php'; };
    </script>
  </div>
  <div class="flex items-center">
    <form action="#" method="GET" class="flex items-center">
      <label for="site-search" class="sr-only">Search site</label>
      <input id="site-search" name="q" type="search" placeholder="Search..." class="px-3 py-2 rounded-l-md border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-300" />
      <button type="submit" class="px-3 py-2 bg-emerald-600 text-white rounded-r-md text-sm">Search</button>
    </form>
  </div>
</header>
