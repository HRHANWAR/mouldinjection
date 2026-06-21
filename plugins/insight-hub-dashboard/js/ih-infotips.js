/* ============================================================
   ih-infotips.js — field info-popovers for machine & tool owners
   Vanilla JS, no deps. Enqueue on listing detail, browse, and
   the add-machine / add-tool forms.

   USAGE
   -----
   Mark any label/element with a key:
       <span class="ih-spec-label" data-tip="tiebar">Tie-bar</span>
   …or attach by input name on the forms (auto-mapped below).
   The script injects an (i) button after the label and shows an
   accessible popover on hover / focus / tap. One popover at a time.

   To add a new field: add an entry to IH_TIPS keyed by the same
   string you put in data-tip. Nothing else required.
   ============================================================ */
(function () {
  'use strict';

  /* ---- 1. the knowledge base ------------------------------------------ */
  /* who = which owner the note is aimed at (machine | tool | both)         */
  var IH_TIPS = {
    /* ---- shared press / machine specs ---- */
    clamp: {
      title: 'Clamping force', unit: 'tonnes (T)', who: 'both',
      what: 'The force the machine squeezes the mould halves shut with during injection.',
      why: 'Must beat the force trying to blow the mould open (projected area × cavity pressure), or you get flash. The tool needs a press rated at or above this number.',
      example: 'A 769 cm² PP lid needs ≈200 T, so list a press of 200 T+.'
    },
    shot: {
      title: 'Shot weight', unit: 'grams (g)', who: 'both',
      what: 'The mass of plastic injected in one cycle — all cavities plus the runner.',
      why: 'The part’s shot should sit between ~20% and ~80% of the machine’s max shot capacity for a stable, repeatable process.',
      example: '36 g part on a 150 g machine = 24% capacity — comfortable.'
    },
    shotmm: {
      title: 'Shot size (stroke)', unit: 'millimetres (mm)', who: 'machine',
      what: 'The injection stroke — how far the screw travels forward, in mm. Multiply by screw area for volume.',
      why: 'We list shot as stroke (mm) because that is what the machine plate shows; an optional shot weight (g) can be added for quick comparison.',
      example: '120 mm stroke on a 40 mm screw ≈ 150 cm³.'
    },
    tiebar: {
      title: 'Tie-bar spacing', unit: 'mm (H × V)', who: 'both',
      what: 'The clear gap between the four guide bars on the clamp — the window the mould must pass through.',
      why: 'The mould’s footprint (length × width) must fit inside this window, or it physically won’t mount.',
      example: '460 × 460 mm tie-bars accept a mould up to ~450 × 450 mm.'
    },
    daylight: {
      title: 'Daylight / mould open', unit: 'mm', who: 'both',
      what: 'The maximum distance the platens open — max mould height plus the opening stroke.',
      why: 'You need roughly twice the part depth of opening to eject cleanly; the shut mould height should be ≤ ⅓ of max daylight.',
      example: 'A 42 mm-deep lid needs ≥ ~466 mm daylight on this tool.'
    },
    screw: {
      title: 'Screw diameter', unit: 'mm', who: 'machine',
      what: 'The diameter of the reciprocating screw that melts and injects the polymer.',
      why: 'Sets injection pressure and shot volume. Smaller screws give higher pressure but lower volume.',
      example: '40 mm screw is typical for a 150–250 T press.'
    },
    pressure: {
      title: 'Max injection pressure', unit: 'bar', who: 'machine',
      what: 'The peak pressure the machine can push melt into the cavity with.',
      why: 'Thin walls and long flow paths need more pressure; under-spec presses short-shoot.',
      example: 'Thin-wall PP packaging often needs 1,400 bar+.'
    },
    clampdrive: {
      title: 'Clamp & drive type', who: 'machine',
      what: 'How the clamp is actuated: hydraulic (direct), toggle, hydromechanical, all-electric or two-platen.',
      why: 'Affects tonnage build-up, repeatability and energy use. Toggles are fast; all-electric is precise and clean.',
      example: 'All-electric suits medical/cleanroom; toggle suits fast packaging.'
    },
    toggletype: {
      title: 'Toggle clamp type', who: 'machine',
      what: 'The linkage geometry: single toggle, or double toggle (4-point / 5-point).',
      why: 'Five-point double toggles spread platen load more evenly — better for large, thin parts.',
      example: 'Double 5-point is common on larger packaging presses.'
    },

    /* ---- tool / mould specs ---- */
    cavities: {
      title: 'Number of cavities', who: 'tool',
      what: 'How many identical parts the mould produces per shot.',
      why: 'More cavities = more parts per cycle, but more clamp force, bigger shot and a larger machine.',
      example: '1 cavity = 1 part/shot; an 8-cavity tool needs ~8× the shot.'
    },
    mouldtype: {
      title: 'Mould type', who: 'tool',
      what: 'Construction class: single-cavity, multi-cavity, family, 2-shot, stack, etc.',
      why: 'Tells a buyer the output pattern and complexity at a glance.',
      example: 'Single-cavity = simplest; family = different parts in one shot.'
    },
    runner: {
      title: 'Runner type', who: 'tool',
      what: 'How melt reaches the cavity: cold runner (solidifies, becomes scrap/regrind) or hot runner (kept molten).',
      why: 'Hot runners cut waste and cycle time but cost more and add controller zones. Cold runner adds runner mass to every shot.',
      example: 'Cold runner = include runner grams in shot weight.'
    },
    gate: {
      title: 'Gate type', who: 'tool',
      what: 'The entry point into the cavity: direct/sprue, edge, sub, pin, hot-tip, etc.',
      why: 'Drives the vestige mark, fill balance and whether de-gating is automatic.',
      example: 'Direct sprue leaves a visible mark; sub-gate self-trims.'
    },
    ejector: {
      title: 'Ejection method', who: 'tool',
      what: 'How the part is pushed off the core: ejector pins, stripper plate, sleeves, air.',
      why: 'Stripper plates suit thin-wall round parts; pins can mark cosmetic faces.',
      example: 'A pot lid typically uses a stripper plate.'
    },
    nozzle: {
      title: 'Nozzle type', who: 'tool',
      what: 'The interface between machine barrel and mould sprue: open, shut-off, etc.',
      why: 'Shut-off nozzles stop drool on hot/low-viscosity materials.',
      example: 'Shut-off nozzle for runny PP/PA.'
    },
    condition: {
      title: 'Tool condition', who: 'tool',
      what: 'Production-ready, refurbished, needs work, or for spares.',
      why: 'Sets buyer expectation on how soon it can run and what re-validation is needed.',
      example: '“Used — production ready” = can run today.'
    },
    mouldmaterial: {
      title: 'Mould steel / material', who: 'tool',
      what: 'The cavity/core material: P20, H13, 420 SS, beryllium-copper, aluminium.',
      why: 'Determines tool life and which resins/volumes it suits. Hardened steel = long runs; aluminium = prototype/low volume.',
      example: 'H13 hardened steel for millions of shots.'
    },

    /* ---- part / production ---- */
    partweight: {
      title: 'Part weight', unit: 'g', who: 'tool',
      what: 'The finished mass of one moulded part (excludes runner).',
      why: 'Feeds shot weight, material cost per part and cooling time.',
      example: '20 g lid → material cost ≈ £0.03 at £1.50/kg PP.'
    },
    cycle: {
      title: 'Cycle time', unit: 'seconds', who: 'both',
      what: 'Time for one full shot: inject → pack → cool → open → eject → close. Cooling usually dominates.',
      why: 'Cooling time scales with wall thickness squared, so thick parts are slow. Drives parts/hour and processing cost.',
      example: '32 s cycle on 1 cavity = ~113 parts/hour.'
    },
    annualvolume: {
      title: 'Annual volume', who: 'both',
      what: 'Expected parts per year for the job.',
      why: 'Decides whether the tool steel, cavitation and automation are economical.',
      example: '500k/yr usually justifies a multi-cavity hardened tool.'
    },
    material: {
      title: 'Material / grade', who: 'both',
      what: 'The polymer and specific grade the tool was built for (e.g. PP, HDPE, ABS, PC).',
      why: 'Sets shrinkage, clamp factor, melt temp and price — and whether the tool transfers to another resin.',
      example: 'PP shrinks more than ABS; a PP tool may not size correctly in PC.'
    },
    projarea: {
      title: 'Projected area', unit: 'cm²', who: 'both',
      what: 'The shadow the part casts on the parting line, summed over all cavities.',
      why: 'Multiply by cavity pressure to get the clamp tonnage the press must resist.',
      example: 'Ø313 lid ≈ 769 cm².'
    }
  };
  window.IH_TIPS = Object.assign(IH_TIPS, window.IH_TIPS || {});

  /* map form input names → tip keys (so the add-* forms get tips too) ---- */
  var NAME_MAP = {
    clamping_force: 'clamp', clamp_force: 'clamp', shot_size: 'shotmm', shot_weight: 'shot',
    tie_bar_spacing: 'tiebar', tie_bar: 'tiebar', opening_stroke: 'daylight', screw_diameter: 'screw',
    clamp_drive_type: 'clampdrive', toggle_clamp_type: 'toggletype', avg_cycle_time: 'cycle',
    cycle_time: 'cycle', num_cavities: 'cavities', num_cavities_spec: 'cavities', mould_type: 'mouldtype',
    runner_type: 'runner', gate_type: 'gate', ejector_type: 'ejector', nozzle_type: 'nozzle',
    mould_condition: 'condition', mould_material: 'mouldmaterial', part_weight: 'partweight',
    annual_volume: 'annualvolume', material_grade: 'material'
  };

  /* ---- 2. one shared popover ------------------------------------------ */
  var pop = document.createElement('div');
  pop.className = 'ih-tip-pop';
  pop.setAttribute('role', 'tooltip');
  pop.innerHTML =
    '<div class="ih-tip-arrow"></div>' +
    '<div class="ih-tip-head"><span class="ih-tip-title"></span><span class="ih-tip-unit"></span></div>' +
    '<p class="ih-tip-what"></p>' +
    '<p class="ih-tip-why"></p>' +
    '<div class="ih-tip-eg"><span>e.g.</span> <em></em></div>' +
    '<button type="button" class="ih-tip-close" aria-label="Close">×</button>';
  var built = false;
  function mount() { if (!built) { document.body.appendChild(pop); built = true; } }

  var current = null;
  function fill(key) {
    var g = IH_TIPS[key]; if (!g) return false;
    pop.querySelector('.ih-tip-title').textContent = g.title || '';
    var u = pop.querySelector('.ih-tip-unit'); u.textContent = g.unit ? '· ' + g.unit : '';
    pop.querySelector('.ih-tip-what').textContent = g.what || '';
    var why = pop.querySelector('.ih-tip-why'); why.textContent = g.why || ''; why.style.display = g.why ? '' : 'none';
    var eg = pop.querySelector('.ih-tip-eg'); var em = eg.querySelector('em');
    if (g.example) { em.textContent = g.example; eg.style.display = ''; } else { eg.style.display = 'none'; }
    pop.setAttribute('data-who', g.who || 'both');
    return true;
  }

  function place(anchor) {
    var isMobile = window.matchMedia('(max-width:760px)').matches;
    pop.classList.toggle('is-sheet', isMobile);
    if (isMobile) { pop.style.left = ''; pop.style.top = ''; return; }   // CSS pins it as a bottom sheet
    var r = anchor.getBoundingClientRect();
    pop.style.visibility = 'hidden'; pop.classList.add('open');
    var pw = pop.offsetWidth, ph = pop.offsetHeight;
    var left = r.left + r.width / 2 - pw / 2 + window.scrollX;
    var top = r.top - ph - 12 + window.scrollY;
    var below = false;
    if (top < window.scrollY + 8) { top = r.bottom + 12 + window.scrollY; below = true; }
    left = Math.max(window.scrollX + 8, Math.min(left, window.scrollX + document.documentElement.clientWidth - pw - 8));
    pop.style.left = left + 'px'; pop.style.top = top + 'px';
    var arrow = pop.querySelector('.ih-tip-arrow');
    arrow.style.left = (r.left + r.width / 2 + window.scrollX - left) + 'px';
    arrow.className = 'ih-tip-arrow' + (below ? ' is-up' : '');
    pop.style.visibility = '';
  }

  function open(anchor, key) {
    mount();
    if (!fill(key)) return;
    current = anchor;
    place(anchor);
    pop.classList.add('open');
  }
  function close() { pop.classList.remove('open', 'is-sheet'); current = null; }

  pop.querySelector('.ih-tip-close').addEventListener('click', close);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  document.addEventListener('click', function (e) {
    if (current && !pop.contains(e.target) && !current.contains(e.target) && e.target.__ihTipBtn !== 1) close();
  });
  window.addEventListener('resize', function () { if (current) place(current); });

  /* ---- 3. inject (i) buttons + wire events ----------------------------- */
  function makeBtn(key) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'ih-tip-btn';
    b.setAttribute('aria-label', 'What is this?');
    b.innerHTML =
      '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.4"/>' +
      '<circle cx="8" cy="4.6" r="0.95" fill="currentColor"/><rect x="7.1" y="6.6" width="1.8" height="5.2" rx="0.9" fill="currentColor"/></svg>';
    b.__ihTipBtn = 1;
    var show = function (e) { e.stopPropagation(); (pop.classList.contains('open') && current === b) ? close() : open(b, key); };
    b.addEventListener('click', show);
    b.addEventListener('mouseenter', function () { if (!window.matchMedia('(max-width:760px)').matches) open(b, key); });
    b.addEventListener('focus', function () { open(b, key); });
    return b;
  }

  function attach(el, key) {
    if (!key || !IH_TIPS[key] || el.__ihTipped) return;
    el.__ihTipped = 1;
    el.appendChild(makeBtn(key));
  }

  function scan(root) {
    (root || document).querySelectorAll('[data-tip]').forEach(function (el) {
      attach(el, el.getAttribute('data-tip'));
    });
    /* forms: attach to the field's label by input name */
    (root || document).querySelectorAll('input[name],select[name],textarea[name]').forEach(function (inp) {
      var key = NAME_MAP[(inp.name || '').replace('[]', '')];
      if (!key) return;
      var label = inp.closest('.ihl-field, .field, label');
      var lbl = label && (label.querySelector('.ihl-label, label, .label') || label);
      if (lbl && !lbl.__ihTipped) attach(lbl, key);
    });
  }

  if (document.readyState !== 'loading') scan();
  else document.addEventListener('DOMContentLoaded', function () { scan(); });

  /* expose for dynamically-added content (e.g. live preview) */
  window.ihInfotipsScan = scan;
})();
