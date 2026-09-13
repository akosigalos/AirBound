<?php
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      body{font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      .bg-env{background: linear-gradient(180deg, #f3fbf7 0%, #eef7ff 50%, #f8fff7 100%);} 
      .glass{background: rgba(255,255,255,0.85); backdrop-filter: blur(6px);} 
      @keyframes float{0%{transform:translateY(0)}50%{transform:translateY(-8px)}100%{transform:translateY(0)}}
    </style>
  </head>
  <body class="min-h-screen bg-env flex items-center justify-center px-4">
    <div class="absolute inset-0 pointer-events-none opacity-40">
      <svg viewBox="0 0 1440 320" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
        <path fill="#e6fbf3" d="M0,96L48,80C96,64,192,32,288,21.3C384,11,480,21,576,42.7C672,64,768,96,864,96C960,96,1056,64,1152,64C1248,64,1344,96,1392,112L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
      </svg>
    </div>

    <div class="relative z-10 w-full max-w-4xl">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
        <!-- Info panel -->
        <div class="hidden md:flex flex-col justify-center bg-gradient-to-br from-white/80 to-emerald-50 rounded-3xl p-8 shadow-xl border border-white/60">
          <div class="mb-4">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-sky-500 flex items-center justify-center text-white font-extrabold shadow-lg">AB</div>
          </div>
          <h3 class="text-3xl font-extrabold leading-tight">Create your account</h3>
          <p class="mt-3 text-slate-700">Join AIR-BOUND to start monitoring air quality, receive alerts, and access analytics tailored to your environment.</p>
          <div class="mt-6 grid gap-3">
            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
              <div class="text-2xl">📡</div>
              <div>
                <div class="text-xs text-slate-600">Connectivity</div>
                <div class="font-semibold">Device integrations</div>
              </div>
            </div>
            <div class="flex items-center gap-3 p-3 bg-white rounded-lg shadow-sm">
              <div class="text-2xl">📊</div>
              <div>
                <div class="text-xs text-slate-600">Insights</div>
                <div class="font-semibold">Analytics & exports</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Signup card -->
        <div class="glass bg-white/90 rounded-3xl p-8 md:p-10 shadow-2xl border border-white/60 min-h-[420px] md:min-h-[480px]">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-2xl font-extrabold">Sign up for AIR-BOUND</h2>
              <p class="text-sm text-slate-600 mt-1">Create a secure account to access monitoring tools.</p>
            </div>
            <!-- link moved below form for clearer flow on small screens -->
          </div>

          <form id="signupForm" class="space-y-4">
            <div>
              <label class="text-sm font-medium text-slate-700">Full name</label>
              <input id="name" name="name" required class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" placeholder="Your full name" />
              <p id="nameErr" class="text-xs text-red-600 mt-1 hidden">Please enter your full name.</p>
            </div>

            <div>
              <label class="text-sm font-medium text-slate-700">Email</label>
              <input id="email" name="email" type="email" required class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" placeholder="you@domain.com" />
              <p id="emailErr" class="text-xs text-red-600 mt-1 hidden">Enter a valid email address.</p>
            </div>

            <div>
              <label class="text-sm font-medium text-slate-700">Password</label>
              <div class="mt-1 relative">
                <input id="password" name="password" type="password" required minlength="6" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-20 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" placeholder="Create a secure password" />
                <button type="button" id="togglePw" class="absolute right-3 top-3 text-slate-500 hover:text-slate-700">Show</button>
              </div>
              <div class="mt-2 h-2 bg-slate-100 rounded overflow-hidden">
                <div id="pwStrength" class="h-2 bg-emerald-500 w-0 transition-all"></div>
              </div>
              <p id="passwordErr" class="text-xs text-red-600 mt-1 hidden">Password must be at least 6 characters.</p>
            </div>

            <div>
              <label class="text-sm font-medium text-slate-700">Confirm password</label>
              <input id="confirm" name="confirm" type="password" required class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-emerald-300 transition" placeholder="Repeat your password" />
              <p id="confirmErr" class="text-xs text-red-600 mt-1 hidden">Passwords do not match.</p>
            </div>

            <div>
              <button id="submitBtn" type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-emerald-600 to-sky-500 text-white rounded-xl shadow-lg hover:scale-[1.01] transform transition">Create account</button>
            </div>
          </form>

          <div id="message" class="mt-4 text-sm"></div>

          <div class="mt-4 text-center">
            <span class="text-sm text-slate-600">Already have an account? <a href="login.php" class="text-emerald-600 font-medium hover:underline">Sign in</a></span>
          </div>
        </div>
      </div>
    </div>

    <script>
      const form = document.getElementById('signupForm');
      const nameEl = document.getElementById('name');
      const emailEl = document.getElementById('email');
      const pwEl = document.getElementById('password');
      const confirmEl = document.getElementById('confirm');
      const submitBtn = document.getElementById('submitBtn');
      const msg = document.getElementById('message');
      const toggle = document.getElementById('togglePw');

      function setErr(id, show){
        const el = document.getElementById(id);
        if(el) el.classList.toggle('hidden', !show);
      }

      function strengthScore(pw){
        let score = 0;
        if(pw.length >= 6) score += 1;
        if(/[A-Z]/.test(pw)) score += 1;
        if(/[0-9]/.test(pw)) score += 1;
        if(/[^A-Za-z0-9]/.test(pw)) score += 1;
        return score; // 0-4
      }

      pwEl.addEventListener('input', ()=>{
        const s = strengthScore(pwEl.value);
        const bar = document.getElementById('pwStrength');
        const pct = (s/4)*100;
        bar.style.width = pct + '%';
        if(s<=1) bar.className = 'h-2 bg-red-500 w-0 transition-all';
        else if(s===2) bar.className = 'h-2 bg-yellow-400 w-0 transition-all';
        else bar.className = 'h-2 bg-emerald-500 w-0 transition-all';
      });

      toggle.addEventListener('click', ()=>{
        if(pwEl.type === 'password'){ pwEl.type = 'text'; toggle.textContent = 'Hide'; }
        else { pwEl.type = 'password'; toggle.textContent = 'Show'; }
      });

      form.addEventListener('submit', async (e)=>{
        e.preventDefault();
        msg.textContent='';
        // client-side validation
        let valid = true;
        if(!nameEl.value.trim()){ setErr('nameErr', true); valid=false; } else setErr('nameErr', false);
        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailEl.value)){ setErr('emailErr', true); valid=false; } else setErr('emailErr', false);
        if(pwEl.value.length < 6){ setErr('passwordErr', true); valid=false; } else setErr('passwordErr', false);
        if(pwEl.value !== confirmEl.value){ setErr('confirmErr', true); valid=false; } else setErr('confirmErr', false);
        if(!valid) return;

        submitBtn.disabled = true;
        try{
          const res = await fetch('api/register.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({name: nameEl.value, email: emailEl.value, password: pwEl.value})});
          const j = await res.json();
          if(j.success){ msg.className='text-sm text-green-600 mt-2'; msg.textContent='Registration successful — redirecting to login...'; setTimeout(()=>location.href='login.php',1200); }
          else { msg.className='text-sm text-red-600 mt-2'; msg.textContent = j.error || 'Registration failed'; }
        }catch(err){ msg.className='text-sm text-red-600 mt-2'; msg.textContent='Server error'; }
        submitBtn.disabled = false;
      });
    </script>
  </body>
</html>
