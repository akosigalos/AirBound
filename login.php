<?php
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      .bg-env{background: linear-gradient(180deg, #f3fbf7 0%, #eef7ff 50%, #f8fff7 100%);} 
      .wave { position:absolute; inset:0; overflow:hidden; opacity:0.5; }
      .decor-dot{animation:float 6s ease-in-out infinite}
      @keyframes float{0%{transform:translateY(0)}50%{transform:translateY(-8px)}100%{transform:translateY(0)}}
    </style>
  </head>
  <body class="min-h-screen bg-env flex items-center justify-center px-4">
    <div class="absolute inset-0 pointer-events-none">
      <svg class="wave" viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path fill="#e6fbf3" d="M0,96L48,80C96,64,192,32,288,21.3C384,11,480,21,576,42.7C672,64,768,96,864,96C960,96,1056,64,1152,64C1248,64,1344,96,1392,112L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
      </svg>
    </div>

    <div class="relative z-10 w-full max-w-4xl">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
        <!-- Decorative panel -->
        <div class="hidden md:flex flex-col justify-center bg-gradient-to-br from-white/80 to-emerald-50 rounded-3xl p-8 shadow-xl border border-white/60">
          <div class="mb-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-sky-500 flex items-center justify-center text-white font-extrabold shadow-lg">AB</div>
          </div>
          <h3 class="text-3xl font-extrabold leading-tight">Welcome back</h3>
          <p class="mt-3 text-slate-700">Sign in to monitor air quality, receive alerts and access environmental insights.</p>
          <div class="mt-6 grid gap-3">
            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
              <div class="text-2xl">🛰️</div>
              <div>
                <div class="text-xs text-slate-600">Live</div>
                <div class="font-semibold">Realtime Telemetry</div>
              </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
              <div class="text-2xl">🗺️</div>
              <div>
                <div class="text-xs text-slate-600">Spatial</div>
                <div class="font-semibold">Geo Mapping</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Login card -->
        <div class="glass bg-white/90 rounded-3xl p-8 md:p-10 shadow-2xl border border-white/60 min-h-[420px] md:min-h-[480px]">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-2xl font-extrabold">Sign in to AIR-BOUND</h2>
              <p class="text-sm text-slate-600 mt-1">Secure access to dashboards and alerts</p>
            </div>
            <!-- link moved below form for clearer flow on small screens -->
          </div>

          <form id="loginForm" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Email</label>
              <div class="mt-1 relative">
                <input id="email" name="email" type="email" required class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-12 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" placeholder="you@domain.com" />
                <svg class="w-5 h-5 text-slate-400 absolute right-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H8m8-4H8m12 8V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2"/></svg>
              </div>
              <p id="emailErr" class="text-xs text-red-600 mt-1 hidden">Enter a valid email address.</p>
            </div>

            <div>
              <label class="text-sm font-medium text-slate-700">Password</label>
              <div class="mt-1 relative">
                <input id="password" name="password" type="password" required class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-12 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" placeholder="••••••••" />
                <button type="button" id="togglePw" class="absolute right-3 top-2.5 text-slate-500 hover:text-slate-700">Show</button>
              </div>
              <p id="passwordErr" class="text-xs text-red-600 mt-1 hidden">Please enter your password.</p>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <input id="remember" type="checkbox" class="w-4 h-4 text-emerald-600 rounded border-slate-200" />
                <label for="remember" class="text-sm text-slate-600">Remember me</label>
              </div>
              <a href="#" class="text-sm text-slate-600 hover:underline">Forgot password?</a>
            </div>

            <div>
              <button id="submitBtn" type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-emerald-600 to-sky-500 text-white rounded-xl shadow-lg hover:scale-[1.01] transform transition">Login</button>
            </div>
          </form>

          <div id="message" class="mt-4 text-sm"></div>

          <div class="mt-4 text-center">
            <span class="text-sm text-slate-600">Don't have an account? <a href="signup.php" class="text-emerald-600 font-medium hover:underline">Create account</a></span>
          </div>
        </div>
      </div>
    </div>

    <script>
      const form = document.getElementById('loginForm');
      const emailEl = document.getElementById('email');
      const pwEl = document.getElementById('password');
      const msg = document.getElementById('message');
      const toggle = document.getElementById('togglePw');

      toggle.addEventListener('click', ()=>{
        if(pwEl.type === 'password'){ pwEl.type = 'text'; toggle.textContent = 'Hide'; }
        else { pwEl.type = 'password'; toggle.textContent = 'Show'; }
      });

      function setErr(id, show){
        const el = document.getElementById(id);
        if(el) el.classList.toggle('hidden', !show);
      }

      form.addEventListener('submit', async (e)=>{
        e.preventDefault();
        msg.textContent='';
        let valid = true;
        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)){ setErr('emailErr', true); valid=false; } else setErr('emailErr', false);
        if(!pwEl.value){ setErr('passwordErr', true); valid=false; } else setErr('passwordErr', false);
        if(!valid) return;

        try{
          const res = await fetch('api/login.php', {method:'POST', credentials: 'same-origin', headers:{'Content-Type':'application/json'}, body: JSON.stringify({email: emailEl.value, password: pwEl.value})});
          const j = await res.json();
          if(j.success){ msg.className='text-sm text-green-600 mt-2'; msg.textContent='Login successful — redirecting...'; setTimeout(()=>location.href='home.php',300); }
          else { msg.className='text-sm text-red-600 mt-2'; msg.textContent=j.error || 'Login failed'; }
        }catch(err){ msg.className='text-sm text-red-600 mt-2'; msg.textContent='Server error'; }
      });
    </script>
  </body>
</html>
