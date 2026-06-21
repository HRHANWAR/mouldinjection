/* tests/test-badge.js — Node test for the client badge mapping in
   js/material-pricing.js. Mirrors the PHP get_price_badge() assertions, with the
   critical rule: "Live" ONLY for a verified licensed feed.

   Run:  node tests/test-badge.js
   No DOM / network — we stub just enough of window/document/fetch/localStorage
   to let the IIFE initialise and expose window.IHCostCalc.badgeForSource. */

const fs = require('fs');
const path = require('path');

// --- minimal browser-env stubs -------------------------------------------
const noopEl = { querySelector: () => null, addEventListener: () => {}, appendChild: () => {}, setAttribute: () => {}, removeAttribute: () => {}, hasAttribute: () => false, style: {}, value: '', innerHTML: '' };
global.window = {};
global.document = {
  querySelector: (sel) => (sel === '[data-ih-cost-calc]' ? noopEl : null),
  createElement: () => ({ })
};
global.localStorage = { getItem: () => null, setItem: () => {}, removeItem: () => {} };
global.fetch = () => Promise.resolve({ ok: false, json: () => Promise.resolve([]) });

// --- load the module under test ------------------------------------------
const code = fs.readFileSync(path.join(__dirname, '..', 'js', 'material-pricing.js'), 'utf8');
eval(code); // executes the IIFE, attaching window.IHCostCalc

const badge = global.window.IHCostCalc && global.window.IHCostCalc.badgeForSource;

let tests = 0, passed = 0; const fails = [];
function check(cond, label) {
  tests++;
  if (cond) { passed++; console.log('  PASS  ' + label); }
  else { fails.push(label); console.log('  FAIL  ' + label); }
}

console.log('\n== JS badge mapping ==');
check(typeof badge === 'function', 'badgeForSource is exported');

if (typeof badge === 'function') {
  let b = badge('live_feed', true);
  check(b && b.state === 'live' && b.label === 'Live', 'live_feed + verified → Live');

  b = badge('live_feed', false);
  check(b && b.state !== 'live' && b.label !== 'Live', 'live_feed NOT verified → NOT Live (critical)');

  b = badge('public_market_reference', true);
  check(b && b.state === 'public' && b.label !== 'Live', 'public reference never Live');

  const map = {
    csv_imported: ['csv', 'CSV imported'],
    manual_override: ['override', 'Manual override'],
    monthly_index: ['index', 'Monthly index'],
    delayed_public_reference: ['delayed', 'Delayed reference'],
    default_estimate: ['estimate', 'Default estimate'],
    manual_required: ['manual', 'Manual required'],
    news_reference: ['news', 'News reference']
  };
  Object.keys(map).forEach((k) => {
    const r = badge(k, false);
    check(r && r.state === map[k][0] && r.label === map[k][1], 'badge ' + k + ' → ' + map[k][1]);
  });

  check(badge('totally_unknown', false) === null, 'unknown source_type → null (falls back to server badge)');
}

console.log('\n' + '='.repeat(48));
console.log('Results: ' + passed + '/' + tests + ' passed');
if (fails.length) {
  console.log('FAILURES:');
  fails.forEach((f) => console.log('  - ' + f));
  process.exit(1);
}
console.log('ALL TESTS PASSED');
process.exit(0);
