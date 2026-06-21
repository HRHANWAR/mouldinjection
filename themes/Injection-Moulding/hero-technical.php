<?php
/**
 * Technical Hero (Direction B)
 * Self-contained section: markup + scoped CSS + interactive cycle-donut JS.
 * Included from front-page.php via get_template_part('hero-technical').
 * All classes are prefixed `imt-` and CSS is scoped under `.imt-hero` to avoid collisions.
 */
$imt_register_url = esc_url( home_url( '/register' ) );
$imt_machines_url = esc_url( home_url( '/machines' ) );
?>

<section class="imt-hero" aria-label="UK injection moulding platform">
  <div class="imt-hero__grid" aria-hidden="true"></div>
  <div class="imt-hero__glow" aria-hidden="true"></div>

  <div class="imt-hero__inner">

    <!-- LEFT COLUMN -->
    <div class="imt-hero__left">
      <span class="imt-badge"><span class="imt-badge__dot"></span> UK'S LEADING INJECTION MOULDING PLATFORM</span>

      <h1 class="imt-title">Match your mould tool to <span class="imt-title__accent">the right injection press.</span></h1>

      <p class="imt-sub">List your existing tool once. We route it to vetted UK and overseas injection moulding partners &mdash; matched on clamp tonnage, shot weight, cavity count and cycle time.</p>

      <div class="imt-cta">
        <a class="imt-btn imt-btn--primary" href="<?php echo $imt_register_url; ?>">
          List Your Tool
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true"><path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <a class="imt-btn imt-btn--ghost" href="<?php echo $imt_machines_url; ?>">Browse Machines</a>
      </div>

      <div class="imt-trust">
        <span class="imt-trust__stars" aria-hidden="true">
          <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
        </span>
        <span class="imt-trust__rating">5.0</span>
        <span class="imt-trust__meta">from <strong>800+</strong> reviews</span>
        <span class="imt-trust__sep"></span>
        <span class="imt-trust__meta"><strong>500+</strong> verified partners</span>
      </div>

      <div class="imt-specs">
        <div class="imt-specs__item"><span class="imt-specs__num">20&ndash;1700T</span><span class="imt-specs__lbl">CLAMP RANGE</span></div>
        <div class="imt-specs__div"></div>
        <div class="imt-specs__item"><span class="imt-specs__num">1&ndash;48</span><span class="imt-specs__lbl">CAVITIES</span></div>
        <div class="imt-specs__div"></div>
        <div class="imt-specs__item"><span class="imt-specs__num">&plusmn;0.01mm</span><span class="imt-specs__lbl">TOLERANCE</span></div>
        <div class="imt-specs__div"></div>
        <div class="imt-specs__item"><span class="imt-specs__num">24h</span><span class="imt-specs__lbl">QUOTE TURNAROUND</span></div>
      </div>
    </div>

    <!-- RIGHT: CYCLE PANEL -->
    <div class="imt-panel">
      <div class="imt-panel__head">
        <span class="imt-panel__title"><span class="imt-badge__dot"></span> INJECTION CYCLE</span>
        <span class="imt-chip">MCH-04417</span>
      </div>

      <div class="imt-cycle">
        <div class="imt-donutwrap">
          <svg class="imt-donut" viewBox="0 0 240 240" role="img" aria-label="Injection moulding cycle breakdown">
            <circle class="imt-donut__track" cx="120" cy="120" r="84"></circle>
            <g class="imt-donut__ring"></g>
            <path class="imt-donut__marker" d="M120 6 l8 13 h-16 z" aria-hidden="true"></path>
          </svg>
          <div class="imt-center">
            <span class="imt-center__big" data-default="32.0">32.0</span>
            <span class="imt-center__lbl" data-default="SEC &middot; EST. CYCLE">SEC &middot; EST. CYCLE</span>
          </div>
        </div>

        <ul class="imt-legend" aria-label="Per-phase timing">
          <li class="imt-legend__cap">PER-PHASE TIMING</li>
          <li data-i="0"><span class="sw" style="background:#2C5B57"></span><span class="nm">Clamp</span><span class="tm">2.0s</span></li>
          <li data-i="1"><span class="sw" style="background:#3F8E6E"></span><span class="nm">Inject</span><span class="tm">2.5s</span></li>
          <li data-i="2"><span class="sw" style="background:#8FD14F"></span><span class="nm">Pack / Hold</span><span class="tm">5.5s</span></li>
          <li data-i="3" class="is-key"><span class="sw" style="background:#C8FF00"></span><span class="nm">Cool</span><span class="tm">19.0s</span></li>
          <li data-i="4"><span class="sw" style="background:#5F7E33"></span><span class="nm">Eject</span><span class="tm">3.0s</span></li>
        </ul>
      </div>

      <div class="imt-panel__div"></div>

      <div class="imt-matched">
        <div class="imt-matched__head">
          <span class="imt-panel__title"><span class="imt-badge__dot"></span> MACHINE MATCHED</span>
          <span class="imt-chip imt-chip--lime">96% FIT</span>
        </div>
        <div class="imt-matched__grid">
          <div class="imt-matched__cell"><span class="lbl">CLAMP FORCE</span><span class="val">450 T</span></div>
          <div class="imt-matched__cell"><span class="lbl">SCREW DIA</span><span class="val">&Oslash;70 mm</span></div>
          <div class="imt-matched__cell"><span class="lbl">TIE-BAR</span><span class="val">760&times;760 mm</span></div>
          <div class="imt-matched__cell"><span class="lbl">MATERIAL</span><span class="val">ABS / PP / PC</span></div>
        </div>
      </div>
    </div>

  </div>
</section>

<style>
@import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap');

.imt-hero{
  --imt-bg:#071E22; --imt-surface:#0C2A2E; --imt-lime:#C8FF00; --imt-lime-soft:#8FD14F;
  --imt-text:#ffffff; --imt-grey:#A7B2B4; --imt-muted:#6F7E84; --imt-line:rgba(255,255,255,.08);
  --imt-mono:'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
  --imt-sans:'Montserrat', system-ui, sans-serif;
  position:relative; overflow:hidden; isolation:isolate;
  background:var(--imt-bg); color:var(--imt-text);
  padding:148px 24px 84px;
}
.imt-hero *{ box-sizing:border-box; }
.imt-hero__grid{
  position:absolute; inset:0; z-index:0; pointer-events:none;
  background-image:linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
                   linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
  background-size:120px 120px;
  -webkit-mask-image:linear-gradient(180deg,#000,rgba(0,0,0,.4));
  mask-image:linear-gradient(180deg,#000,rgba(0,0,0,.4));
}
.imt-hero__glow{
  position:absolute; top:-220px; right:-140px; width:780px; height:580px; z-index:0; pointer-events:none;
  background:radial-gradient(closest-side, rgba(200,255,0,.18), rgba(200,255,0,0)); filter:blur(20px);
}
.imt-hero__inner{
  position:relative; z-index:1; max-width:1240px; margin:0 auto;
  display:grid; grid-template-columns:1.04fr .96fr; gap:56px; align-items:center;
}

/* LEFT */
.imt-hero__left{ display:flex; flex-direction:column; align-items:flex-start; gap:24px; }
.imt-badge{
  display:inline-flex; align-items:center; gap:8px;
  font-family:var(--imt-mono); font-size:12px; letter-spacing:1.2px; color:#DCEBC4;
  padding:8px 16px 8px 14px; border:1px solid rgba(255,255,255,.14); border-radius:999px;
  background:rgba(255,255,255,.04);
}
.imt-badge__dot{ width:8px; height:8px; border-radius:50%; background:var(--imt-lime); box-shadow:0 0 7px rgba(200,255,0,.85); }
.imt-title{
  font-family:var(--imt-sans); font-weight:700; font-size:clamp(34px,4.4vw,56px); line-height:1.06;
  letter-spacing:-.01em; color:#fff; margin:0; max-width:620px;
}
.imt-title__accent{ color:var(--imt-lime); }
.imt-sub{ font-family:'Poppins',sans-serif; font-size:17px; line-height:1.6; color:var(--imt-grey); max-width:520px; margin:0; }
.imt-cta{ display:flex; flex-wrap:wrap; gap:14px; align-items:center; }
.imt-btn{
  display:inline-flex; align-items:center; gap:8px; border-radius:999px;
  font-family:var(--imt-sans); font-size:16px; padding:14px 24px; text-decoration:none;
  transition:transform .15s ease, background .2s ease, box-shadow .2s ease; cursor:pointer;
}
.imt-btn--primary{ background:var(--imt-lime); color:#082A2E; font-weight:600; padding-right:22px; box-shadow:0 6px 22px -4px rgba(200,255,0,.35); }
.imt-btn--primary:hover{ transform:translateY(-2px); box-shadow:0 10px 30px -6px rgba(200,255,0,.5); }
.imt-btn--ghost{ background:transparent; color:#fff; font-weight:500; border:1px solid rgba(255,255,255,.22); }
.imt-btn--ghost:hover{ background:rgba(255,255,255,.06); }
.imt-trust{ display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-family:'Poppins',sans-serif; font-size:13px; color:var(--imt-muted); }
.imt-trust__stars{ color:var(--imt-lime); font-size:13px; letter-spacing:1px; }
.imt-trust__rating{ font-family:var(--imt-sans); font-weight:600; color:#fff; font-size:14px; }
.imt-trust__meta strong{ color:#fff; font-weight:600; }
.imt-trust__sep{ width:3px; height:3px; border-radius:50%; background:#5A6468; }
.imt-specs{ display:flex; flex-wrap:wrap; align-items:center; gap:22px; padding-top:20px; border-top:1px solid rgba(255,255,255,.10); width:100%; max-width:520px; }
.imt-specs__item{ display:flex; flex-direction:column; gap:4px; }
.imt-specs__num{ font-family:var(--imt-mono); font-weight:700; font-size:20px; color:#fff; }
.imt-specs__lbl{ font-family:'Poppins',sans-serif; font-size:11px; letter-spacing:.8px; color:var(--imt-muted); }
.imt-specs__div{ width:1px; height:38px; background:rgba(255,255,255,.12); }

/* PANEL */
.imt-panel{
  background:var(--imt-surface); border:1px solid var(--imt-line); border-radius:22px;
  padding:26px 28px; display:flex; flex-direction:column; gap:22px;
  box-shadow:0 18px 40px -12px rgba(0,0,0,.45);
}
.imt-panel__head{ display:flex; align-items:center; justify-content:space-between; }
.imt-panel__title{ display:inline-flex; align-items:center; gap:8px; font-family:var(--imt-mono); font-size:12px; letter-spacing:1px; color:#DCEBC4; }
.imt-chip{ font-family:var(--imt-mono); font-size:11px; letter-spacing:.5px; color:#9FB0A6; padding:4px 10px; border-radius:6px; background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.10); }
.imt-chip--lime{ background:var(--imt-lime); color:#082A2E; border:none; font-weight:500; padding:3px 9px; }

.imt-cycle{ display:flex; align-items:center; gap:26px; }
.imt-donutwrap{ position:relative; width:248px; height:248px; flex:0 0 248px; }
.imt-donut{ width:248px; height:248px; display:block; }
.imt-donut__track{ fill:none; stroke:rgba(255,255,255,.05); stroke-width:28; }
.imt-donut__ring{ transform-box:fill-box; transform-origin:center; transition:transform .9s cubic-bezier(.65,.02,.3,1); }
.imt-donut__ring circle{ fill:none; stroke-width:28; transition:opacity .35s ease, stroke-width .35s ease, filter .35s ease; }
.imt-donut__ring circle.is-active{ stroke-width:32; filter:drop-shadow(0 0 8px rgba(200,255,0,.45)); }
.imt-donut__ring circle.is-dim{ opacity:.28; }
.imt-donut__marker{ fill:var(--imt-lime); filter:drop-shadow(0 0 5px rgba(200,255,0,.7)); }
.imt-center{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; pointer-events:none; }
.imt-center__big{ font-family:var(--imt-mono); font-weight:700; font-size:44px; color:#fff; line-height:1; transition:color .3s ease; }
.imt-center__lbl{ font-family:var(--imt-mono); font-weight:500; font-size:10.5px; letter-spacing:1.2px; color:#8FA0A6; }

.imt-legend{ list-style:none; margin:0; padding:0; flex:1; display:flex; flex-direction:column; gap:13px; min-width:0; }
.imt-legend__cap{ font-family:var(--imt-mono); font-weight:500; font-size:10.5px; letter-spacing:1.2px; color:#6F7E84; }
.imt-legend li[data-i]{ display:flex; align-items:center; gap:10px; cursor:pointer; border-radius:8px; padding:2px 6px; margin:-2px -6px; transition:background .2s ease; }
.imt-legend li[data-i]:hover, .imt-legend li.is-active{ background:rgba(255,255,255,.05); }
.imt-legend .sw{ width:11px; height:11px; border-radius:3px; flex:0 0 11px; transition:transform .2s ease; }
.imt-legend li.is-active .sw{ transform:scale(1.25); }
.imt-legend .nm{ font-family:var(--imt-mono); font-weight:500; font-size:13px; color:#D7DEE0; flex:1; }
.imt-legend .tm{ font-family:var(--imt-mono); font-size:13px; color:#9FB0A6; }
.imt-legend li.is-key .tm{ color:var(--imt-lime); }

.imt-panel__div{ height:1px; background:rgba(255,255,255,.08); }
.imt-matched{ display:flex; flex-direction:column; gap:16px; }
.imt-matched__head{ display:flex; align-items:center; justify-content:space-between; }
.imt-matched__grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px 16px; }
.imt-matched__cell{ display:flex; flex-direction:column; gap:3px; }
.imt-matched__cell .lbl{ font-family:var(--imt-mono); font-size:10px; letter-spacing:.8px; color:#6F7E84; }
.imt-matched__cell .val{ font-family:var(--imt-mono); font-weight:500; font-size:14px; color:#fff; }

/* RESPONSIVE */
@media (max-width:1280px){
  .imt-hero__inner{ gap:40px; }
}
@media (max-width:1024px){
  .imt-hero{ padding:128px 24px 72px; }
  .imt-hero__inner{ grid-template-columns:1fr; gap:40px; }
  .imt-panel{ max-width:560px; width:100%; margin:0 auto; }
}
@media (max-width:768px){
  .imt-hero{ padding:120px 20px 64px; }
  .imt-sub{ font-size:16px; }
}
@media (max-width:640px){
  .imt-hero{ padding:112px 18px 56px; }
  .imt-cycle{ flex-direction:column; align-items:center; gap:22px; }
  .imt-legend{ width:100%; }
  .imt-specs{ gap:16px; }
  .imt-specs__div{ display:none; }
  .imt-specs__item{ flex:1 0 40%; }
  .imt-matched__grid{ grid-template-columns:1fr 1fr; }
  .imt-panel{ padding:22px 18px; }
}
@media (max-width:400px){
  .imt-hero{ padding:104px 14px 48px; }
  .imt-donutwrap{ width:208px; height:208px; flex:0 0 208px; }
  .imt-donut{ width:208px; height:208px; }
  .imt-center__big{ font-size:36px; }
  .imt-btn{ width:100%; justify-content:center; }
  .imt-cta{ width:100%; }
  .imt-matched__grid{ grid-template-columns:1fr; gap:12px; }
}
@media (prefers-reduced-motion: reduce){
  .imt-donut__ring{ transition:none; }
  .imt-donut__ring circle{ transition:none; }
}
</style>

<script>
(function(){
  var hero = document.currentScript ? document.currentScript.previousElementSibling : null;
  // currentScript points at this <script>; previousElementSibling is <style>; the hero is before that.
  var root = document.querySelector('.imt-hero');
  if(!root) return;
  var ring = root.querySelector('.imt-donut__ring');
  var centerBig = root.querySelector('.imt-center__big');
  var centerLbl = root.querySelector('.imt-center__lbl');
  var legendItems = Array.prototype.slice.call(root.querySelectorAll('.imt-legend li[data-i]'));
  if(!ring) return;

  var R = 84, C = 2 * Math.PI * R; // circumference
  var GAP = 6; // px gap between segments
  var phases = [
    { name:'Clamp',       t:'2.0s',  color:'#2C5B57', frac:0.0625    },
    { name:'Inject',      t:'2.5s',  color:'#3F8E6E', frac:0.078125  },
    { name:'Pack / Hold', t:'5.5s',  color:'#8FD14F', frac:0.171875  },
    { name:'Cool',        t:'19.0s', color:'#C8FF00', frac:0.59375   },
    { name:'Eject',       t:'3.0s',  color:'#5F7E33', frac:0.09375   }
  ];

  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var SVGNS = 'http://www.w3.org/2000/svg';
  var circles = [];
  var cum = 0;

  phases.forEach(function(p, i){
    var seg = p.frac * C;
    var draw = Math.max(2, seg - GAP);
    var c = document.createElementNS(SVGNS, 'circle');
    c.setAttribute('cx', 120); c.setAttribute('cy', 120); c.setAttribute('r', R);
    c.setAttribute('stroke', p.color);
    c.setAttribute('stroke-linecap', 'butt');
    c.setAttribute('stroke-dasharray', draw + ' ' + (C - draw));
    // start each segment at top (-90deg) + its cumulative start
    var startDeg = -90 + cum * 360;
    c.setAttribute('transform', 'rotate(' + startDeg + ' 120 120)');
    // draw-in: hide then reveal
    if(!reduce){ c.setAttribute('stroke-dashoffset', draw); c.style.transition = 'stroke-dashoffset .9s ease ' + (i*0.12) + 's, opacity .35s ease, stroke-width .35s ease, filter .35s ease'; }
    p._mid = cum + p.frac/2;            // mid position as fraction from top
    ring.appendChild(c);
    circles.push(c);
    cum += p.frac;
  });

  // reveal segments
  if(!reduce){ requestAnimationFrame(function(){ requestAnimationFrame(function(){ circles.forEach(function(c){ c.setAttribute('stroke-dashoffset', 0); }); }); }); }

  function focus(i, spin){
    phases.forEach(function(p, idx){
      var active = idx === i;
      circles[idx].classList.toggle('is-active', active);
      circles[idx].classList.toggle('is-dim', i !== null && !active);
      if(legendItems[idx]) legendItems[idx].classList.toggle('is-active', active);
    });
    if(i === null){
      centerBig.textContent = centerBig.getAttribute('data-default');
      centerLbl.innerHTML = centerLbl.getAttribute('data-default');
      if(spin && !reduce) ring.style.transform = 'rotate(0deg)';
      return;
    }
    var p = phases[i];
    centerBig.textContent = p.t.replace('s','');
    centerLbl.textContent = 'SEC \u00B7 ' + p.name.toUpperCase();
    if(spin && !reduce){
      // rotate ring so this phase mid sits under the top marker
      ring.style.transform = 'rotate(' + (-p._mid * 360) + 'deg)';
    }
  }

  // Auto-cycle
  var auto = null, idx = 3; // start showcasing "Cool"
  function start(){
    if(reduce || auto) return;
    auto = setInterval(function(){ idx = (idx + 1) % phases.length; focus(idx, true); }, 2400);
  }
  function stop(){ if(auto){ clearInterval(auto); auto = null; } }

  legendItems.forEach(function(li){
    var i = parseInt(li.getAttribute('data-i'), 10);
    li.addEventListener('mouseenter', function(){ stop(); focus(i, true); });
    li.addEventListener('click', function(){ stop(); focus(i, true); });
  });
  root.querySelector('.imt-donutwrap').addEventListener('mouseenter', stop);
  root.querySelector('.imt-cycle').addEventListener('mouseleave', function(){ start(); });

  // Kick off: draw in, focus the key phase, then auto-cycle
  if(reduce){ focus(null, false); }
  else {
    setTimeout(function(){ focus(3, true); }, 700);
    setTimeout(start, 2600);
  }
})();
</script>
