<?php /** Customer-facing review page. Expects $cfg (see ReviewController::showAction). No login, no framework — kept light for phones on mobile data. */ ?>
<!DOCTYPE html>
<html lang="<?= h($cfg['lang']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#4f46e5">
<?php if ($cfg['writeUrl'] !== ''): ?><link rel="preconnect" href="https://search.google.com"><link rel="dns-prefetch" href="https://www.google.com"><?php endif; ?>
<title><?= h($cfg['shop']) ?> · Share your experience</title>
<style>
  :root { --brand: #4f46e5; --brand-dark: #4338ca; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --bg: #f1f5f9; --star: #f59e0b; --ok: #16a34a; }
  * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  body { margin: 0; background: var(--bg); color: var(--ink); font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Noto Sans Bengali", sans-serif; line-height: 1.5; }
  .wrap { max-width: 460px; margin: 0 auto; padding: 18px 16px 90px; }
  .card { background: #fff; border-radius: 18px; padding: 20px 18px; box-shadow: 0 1px 2px rgba(15,23,42,.06), 0 8px 24px rgba(15,23,42,.06); }
  .head { text-align: center; padding-bottom: 6px; }
  .logo { width: 72px; height: 72px; border-radius: 18px; object-fit: cover; background: #e2e8f0; margin: 0 auto 10px; display: block; }
  .logo.ph { display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 700; color: #fff; background: linear-gradient(135deg, #6366f1, #10b981); }
  h1 { font-size: 21px; margin: 0; line-height: 1.25; }
  .sub { color: var(--muted); font-size: 15px; margin: 4px 0 0; }
  .langs { display: flex; justify-content: center; gap: 6px; margin: 14px 0 4px; flex-wrap: wrap; }
  .pill { border: 1px solid var(--line); background: #fff; color: var(--muted); font: inherit; font-size: 13px; font-weight: 600; padding: 6px 14px; border-radius: 999px; cursor: pointer; }
  .pill[aria-pressed="true"] { background: var(--brand); border-color: var(--brand); color: #fff; }
  .stars { display: flex; justify-content: center; gap: 4px; margin: 18px 0 6px; }
  .star { background: none; border: 0; padding: 4px; cursor: pointer; line-height: 0; }
  .star svg { width: 46px; height: 46px; fill: #e2e8f0; stroke: #cbd5e1; stroke-width: 1; transition: transform .12s, fill .12s; }
  .star.on svg { fill: var(--star); stroke: #d97706; }
  .star:active svg { transform: scale(1.12); }
  .label { font-weight: 650; font-size: 15px; margin: 18px 0 8px; }
  .chips { display: flex; flex-wrap: wrap; gap: 8px; }
  .chip { border: 1.5px solid var(--line); background: #fff; color: var(--ink); font: inherit; font-size: 14px; padding: 8px 14px; border-radius: 999px; cursor: pointer; }
  .chip[aria-pressed="true"] { border-color: var(--brand); background: #eef2ff; color: var(--brand-dark); font-weight: 600; }
  textarea { width: 100%; border: 1.5px solid var(--line); border-radius: 12px; padding: 11px 12px; font: inherit; font-size: 15px; resize: vertical; background: #fff; color: var(--ink); }
  textarea:focus { outline: 2px solid #c7d2fe; border-color: var(--brand); }
  .count { text-align: right; font-size: 12px; color: var(--muted); margin-top: 2px; }
  .btn { display: block; width: 100%; border: 0; border-radius: 14px; padding: 15px 16px; font: inherit; font-size: 16px; font-weight: 700; cursor: pointer; text-align: center; text-decoration: none; }
  .btn.primary { background: var(--brand); color: #fff; }
  .btn.primary:disabled { background: #a5b4fc; cursor: not-allowed; }
  .btn.google { background: #1a73e8; color: #fff; margin-top: 12px; }
  .btn.ghost { background: #fff; color: var(--ink); border: 1.5px solid var(--line); margin-top: 10px; font-weight: 600; }
  .opts { display: flex; gap: 6px; margin: 4px 0 10px; }
  .opts .pill { flex: 1; }
  .note { background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 11px 12px; font-size: 13px; color: var(--muted); margin-top: 14px; }
  .err { color: #b91c1c; font-size: 14px; margin: 10px 0 0; }
  .banner { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 12px; padding: 10px 12px; font-size: 13px; margin-bottom: 12px; }
  .foot { text-align: center; color: #94a3b8; font-size: 12px; margin-top: 18px; }
  .toast { position: fixed; left: 50%; bottom: 22px; transform: translateX(-50%) translateY(20px); background: #0f172a; color: #fff; padding: 11px 18px; border-radius: 12px; font-size: 14px; opacity: 0; transition: .25s; pointer-events: none; max-width: 90vw; text-align: center; }
  .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
  [hidden] { display: none !important; }
</style>
</head>
<body>
<div class="wrap">
  <?php if ($cfg['preview']): ?><div class="banner">Preview — this is how customers see the page. Nothing you do here is counted<?= $cfg['writeUrl'] === '' ? ', and the Google button is off until you add the Google Place ID' : '' ?>.</div><?php endif; ?>
  <div class="card">
    <div class="head">
      <?php if ($cfg['logo'] !== ''): ?><img class="logo" src="<?= h($cfg['logo']) ?>" alt="">
      <?php else: ?><div class="logo ph" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($cfg['shop'], 0, 1))) ?></div><?php endif; ?>
      <h1><?= h($cfg['shop']) ?></h1>
      <p class="sub" id="intro"></p>
    </div>
    <div class="langs" id="langs" role="group" aria-label="Language"></div>

    <div class="stars" id="stars" role="radiogroup" aria-label="Rating"></div>

    <div id="step2" hidden>
      <div id="posBox" hidden><div class="label" id="posLabel"></div><div class="chips" id="posChips"></div></div>
      <div id="negBox" hidden><div class="label" id="negLabel"></div><div class="chips" id="negChips"></div></div>
      <div class="label" id="noteLabel"></div>
      <textarea id="note" rows="2" maxlength="140"></textarea>
      <div class="count"><span id="noteCount">0</span>/140</div>
      <p class="err" id="err" hidden></p>
      <button class="btn primary" id="suggest" type="button" style="margin-top:12px"></button>
    </div>

    <div id="step3" hidden>
      <div class="label" id="pickLabel"></div>
      <div class="opts" id="opts"></div>
      <textarea id="draft" rows="6" maxlength="600"></textarea>
      <div class="note" id="disclose"></div>
      <button class="btn google" id="post" type="button"></button>
      <button class="btn ghost" id="again" type="button"></button>
    </div>

    <div id="step4" hidden>
      <div class="label" id="goTitle" style="margin-top:4px;font-size:18px"></div>
      <p class="sub" id="goHelp" style="margin-bottom:10px"></p>
      <textarea id="goText" rows="5" readonly></textarea>
      <button class="btn ghost" id="goCopy" type="button" style="margin-top:8px"></button>
      <p class="err" id="goManual" hidden style="color:#92400e"></p>
      <ol id="goSteps" style="padding-left:20px;margin:14px 0;line-height:1.7;font-size:15px"></ol>
      <a class="btn google" id="goOpen" href="#" target="_blank" rel="noopener"></a>
      <p class="note" id="goSlow" style="margin-top:12px"></p>
      <a class="btn ghost" id="goMaps" href="#" target="_blank" rel="noopener" hidden></a>
      <button class="btn ghost" id="goBack" type="button"></button>
    </div>

    <a class="btn ghost" id="own" href="#" target="_blank" rel="noopener" style="margin-top:18px" hidden></a>
  </div>
  <p class="foot">ReviewDesk · TradePOSX</p>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
const RD = <?= json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
const T = {
  en: { intro: 'How was your visit?', rate: ['Poor', 'Fair', 'Okay', 'Good', 'Excellent'], pos: 'What did you like?', neg: 'What could be better?', note: 'Anything to add? (optional)', ph: 'A few words in your own style…', suggest: 'Suggest a review', working: 'Writing your suggestions…', pick: 'Pick a suggestion and edit it so it says what you really felt:', opt: 'Option',
        disclose: 'These suggestions are written by AI from the choices you tapped. Please edit them so they match your real experience. You post the review yourself, from your own Google account.', post: 'Copy & Post on Google', again: 'Start again', own: 'Write it myself on Google',
        copied: 'Copied! Paste it in the Google review box.', needOne: 'Please pick at least one option or write a short note.', fail: 'Could not get suggestions. You can still write your own review on Google.', noLink: 'The Google link is not set up yet.', lang: 'Language',
        goTitle: 'Your review is copied', goHelp: 'Google cannot fill the text in for you, so you paste it yourself:', goSteps: ['Tap “Open Google” below.', 'Choose your stars and tap the review box.', 'Press and hold in the box, then tap “Paste”.', 'Tap “Post”.'], goOpen: 'Open Google', goSlow: 'Google page blank or slow? Wait a few seconds, or open it in Chrome / the Google Maps app.', goMaps: 'Open in Google Maps instead', goCopy: 'Copy text again', goBack: '← Edit my review', goCopiedNow: 'Copied ✓', goManual: 'Press and hold the text above, choose Select all, then Copy.' },
  hi: { intro: 'आपका अनुभव कैसा रहा?', rate: ['ख़राब', 'ठीक-ठाक', 'औसत', 'अच्छा', 'बहुत बढ़िया'], pos: 'आपको क्या अच्छा लगा?', neg: 'क्या बेहतर हो सकता है?', note: 'कुछ और जोड़ना है? (वैकल्पिक)', ph: 'अपने शब्दों में कुछ पंक्तियाँ…', suggest: 'रिव्यू सुझाएँ', working: 'आपके लिए सुझाव लिख रहे हैं…', pick: 'एक सुझाव चुनें और उसे अपने असली अनुभव के अनुसार बदलें:', opt: 'विकल्प',
        disclose: 'ये सुझाव आपके चुने विकल्पों के आधार पर AI ने लिखे हैं। कृपया इन्हें अपने असली अनुभव के अनुसार बदलें। रिव्यू आप खुद, अपने Google अकाउंट से पोस्ट करेंगे।', post: 'कॉपी करें और Google पर पोस्ट करें', again: 'फिर से शुरू करें', own: 'मैं खुद Google पर लिखूँगा/लिखूँगी',
        copied: 'कॉपी हो गया! इसे Google रिव्यू बॉक्स में पेस्ट करें।', needOne: 'कृपया कम से कम एक विकल्प चुनें या छोटा नोट लिखें।', fail: 'सुझाव नहीं मिल पाए। आप फिर भी Google पर अपना रिव्यू लिख सकते हैं।', noLink: 'Google लिंक अभी सेट नहीं है।', lang: 'भाषा',
        goTitle: 'आपका रिव्यू कॉपी हो गया', goHelp: 'Google टेक्स्ट अपने-आप नहीं भर सकता, इसलिए आप इसे खुद पेस्ट करें:', goSteps: ['नीचे “Google खोलें” दबाएँ।', 'स्टार चुनें और रिव्यू बॉक्स में टैप करें।', 'बॉक्स में उँगली दबाए रखें और “Paste / चिपकाएँ” दबाएँ।', '“Post / पोस्ट” दबाएँ।'], goOpen: 'Google खोलें', goSlow: 'Google का पेज खाली या धीमा है? कुछ सेकंड रुकें, या उसे Chrome / Google Maps ऐप में खोलें।', goMaps: 'इसके बजाय Google Maps में खोलें', goCopy: 'टेक्स्ट फिर से कॉपी करें', goBack: '← रिव्यू बदलें', goCopiedNow: 'कॉपी हो गया ✓', goManual: 'ऊपर के टेक्स्ट पर उँगली दबाए रखें, “सभी चुनें” और फिर “कॉपी” दबाएँ।' },
  bn: { intro: 'আপনার অভিজ্ঞতা কেমন ছিল?', rate: ['খারাপ', 'মোটামুটি', 'ঠিকঠাক', 'ভালো', 'দারুণ'], pos: 'আপনার কী ভালো লেগেছে?', neg: 'কী আরও ভালো হতে পারত?', note: 'আর কিছু বলবেন? (ঐচ্ছিক)', ph: 'নিজের ভাষায় কয়েকটি কথা…', suggest: 'রিভিউ প্রস্তাব করুন', working: 'আপনার জন্য প্রস্তাব লেখা হচ্ছে…', pick: 'একটি প্রস্তাব বেছে নিন এবং নিজের আসল অভিজ্ঞতা অনুযায়ী বদলে নিন:', opt: 'বিকল্প',
        disclose: 'আপনার বেছে নেওয়া বিকল্পের ভিত্তিতে AI এই প্রস্তাবগুলি লিখেছে। অনুগ্রহ করে নিজের আসল অভিজ্ঞতা অনুযায়ী বদলে নিন। রিভিউ আপনি নিজেই, নিজের Google অ্যাকাউন্ট থেকে পোস্ট করবেন।', post: 'কপি করে Google-এ পোস্ট করুন', again: 'আবার শুরু করুন', own: 'আমি নিজে Google-এ লিখব',
        copied: 'কপি হয়েছে! Google রিভিউ বক্সে পেস্ট করুন।', needOne: 'অন্তত একটি বিকল্প বেছে নিন বা ছোট একটি নোট লিখুন।', fail: 'প্রস্তাব পাওয়া যায়নি। আপনি তবুও Google-এ নিজের রিভিউ লিখতে পারেন।', noLink: 'Google লিংক এখনও সেট করা হয়নি।', lang: 'ভাষা',
        goTitle: 'আপনার রিভিউ কপি হয়েছে', goHelp: 'Google নিজে থেকে লেখা বসাতে পারে না, তাই আপনি নিজে পেস্ট করুন:', goSteps: ['নিচে “Google খুলুন” চাপুন।', 'স্টার বেছে রিভিউ বক্সে ট্যাপ করুন।', 'বক্সে চেপে ধরে রেখে “Paste / পেস্ট” চাপুন।', '“Post / পোস্ট” চাপুন।'], goOpen: 'Google খুলুন', goSlow: 'Google-এর পেজ ফাঁকা বা ধীর? কয়েক সেকেন্ড অপেক্ষা করুন, অথবা Chrome / Google Maps অ্যাপে খুলুন।', goMaps: 'বদলে Google Maps-এ খুলুন', goCopy: 'লেখা আবার কপি করুন', goBack: '← রিভিউ বদলান', goCopiedNow: 'কপি হয়েছে ✓', goManual: 'উপরের লেখায় চেপে ধরে “সব বেছে নিন”, তারপর “কপি” চাপুন।' }
};
const LANG_NAME = { en: 'English', hi: 'हिन्दी', bn: 'বাংলা' };
const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const st = { rating: 0, chips: new Set(), lang: RD.lang, drafts: [], edits: [], active: 0, busy: false };
let toastTimer;
function toast(msg) { const t = $('toast'); t.textContent = msg; t.classList.add('show'); clearTimeout(toastTimer); toastTimer = setTimeout(() => t.classList.remove('show'), 4200); }
function tx(k) { return (T[st.lang] || T.en)[k]; }

function renderStatic() {
  document.documentElement.lang = st.lang;
  $('intro').textContent = RD.intro || tx('intro');
  $('posLabel').textContent = tx('pos'); $('negLabel').textContent = tx('neg'); $('noteLabel').textContent = tx('note');
  $('note').placeholder = tx('ph'); $('suggest').textContent = st.busy ? tx('working') : tx('suggest');
  $('pickLabel').textContent = tx('pick'); $('disclose').textContent = tx('disclose'); $('post').textContent = tx('post'); $('again').textContent = tx('again');
  $('own').textContent = tx('own');
  $('goTitle').textContent = tx('goTitle'); $('goHelp').textContent = tx('goHelp'); $('goOpen').textContent = tx('goOpen'); $('goCopy').textContent = tx('goCopy'); $('goBack').textContent = tx('goBack'); $('goManual').textContent = tx('goManual');
  $('goSlow').textContent = tx('goSlow'); $('goMaps').textContent = tx('goMaps'); $('goMaps').hidden = !RD.mapsUrl; if (RD.mapsUrl) $('goMaps').href = RD.mapsUrl;
  $('goSteps').innerHTML = tx('goSteps').map(s => `<li>${esc(s)}</li>`).join('');
  $('langs').innerHTML = RD.langs.length > 1 ? RD.langs.map(l => `<button type="button" class="pill" data-lang="${l}" aria-pressed="${l === st.lang}">${LANG_NAME[l]}</button>`).join('') : '';
  $('stars').innerHTML = [1, 2, 3, 4, 5].map(n => `<button type="button" class="star${n <= st.rating ? ' on' : ''}" data-n="${n}" role="radio" aria-checked="${n === st.rating}" aria-label="${n} — ${tx('rate')[n - 1]}"><svg viewBox="0 0 24 24"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3 6.1 20.6l1.3-6.6L2.5 9.4l6.6-.8z" stroke-linejoin="round"/></svg></button>`).join('');
  renderChips();
  if (st.drafts.length) renderOptions();
}
function renderChips() {
  const showPos = st.rating >= 3, showNeg = st.rating > 0 && st.rating <= 3;
  $('posBox').hidden = !showPos; $('negBox').hidden = !showNeg;
  const mk = list => list.map(c => `<button type="button" class="chip" data-chip="${esc(c)}" aria-pressed="${st.chips.has(c)}">${esc(c)}</button>`).join('');
  $('posChips').innerHTML = mk(RD.chipsPos); $('negChips').innerHTML = mk(RD.chipsNeg);
}
function renderOptions() {
  $('opts').innerHTML = st.drafts.map((d, i) => `<button type="button" class="pill" data-opt="${i}" aria-pressed="${i === st.active}">${tx('opt')} ${i + 1}</button>`).join('');
  $('draft').value = st.edits[st.active] ?? st.drafts[st.active] ?? '';
}
function api(path, body) {
  return fetch('/review/' + encodeURIComponent(RD.slug) + '/' + path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-RD-Token': RD.token }, body: JSON.stringify(Object.assign({ staff: RD.staff, preview: RD.preview }, body)) })
    .then(r => r.json().catch(() => ({ ok: false, error: '' })));
}
function showErr(msg) { $('err').textContent = msg; $('err').hidden = !msg; }
function setOwnLink() {
  const a = $('own');
  if (RD.writeUrl) { a.href = RD.writeUrl; a.hidden = false; } else { a.hidden = !RD.preview; a.removeAttribute('href'); }
}

document.addEventListener('click', async e => {
  const t = e.target.closest('button, a'); if (!t) return;
  if (t.dataset.lang) { st.lang = t.dataset.lang; st.drafts = []; st.edits = []; $('step3').hidden = true; $('step4').hidden = true; setOwnLink(); renderStatic(); return; }
  if (t.classList.contains('star')) { st.rating = +t.dataset.n; st.chips.clear(); st.drafts = []; $('step3').hidden = true; $('step4').hidden = true; setOwnLink(); $('step2').hidden = false; showErr(''); renderStatic(); return; }
  if (t.classList.contains('chip')) { const c = t.dataset.chip; st.chips.has(c) ? st.chips.delete(c) : st.chips.add(c); t.setAttribute('aria-pressed', st.chips.has(c)); return; }
  if (t.dataset.opt !== undefined) { st.edits[st.active] = $('draft').value; st.active = +t.dataset.opt; renderOptions(); return; }
  if (t.id === 'again') { st.rating = 0; st.chips.clear(); st.drafts = []; st.edits = []; $('note').value = ''; $('noteCount').textContent = '0'; $('step2').hidden = true; $('step3').hidden = true; $('step4').hidden = true; setOwnLink(); renderStatic(); window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
  if (t.id === 'own') {   // writing their own review is always possible, for every rating
    if (!RD.writeUrl) { e.preventDefault(); toast(tx('noLink')); return; }
    api('click', { rating: st.rating || null, lang: st.lang, mode: 'own' }); return;
  }
  if (t.id === 'suggest') {
    if (st.busy) return;
    const note = $('note').value.trim();
    if (!st.chips.size && !note) { showErr(tx('needOne')); return; }
    showErr(''); st.busy = true; renderStatic();
    try {
      const r = await api('draft', { rating: st.rating, chips: [...st.chips], note, lang: st.lang });
      if (!r.ok) { showErr(r.error || tx('fail')); }
      else { st.drafts = r.drafts; st.edits = []; st.active = 0; renderOptions(); $('step3').hidden = false; $('step3').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    } catch (err) { showErr(tx('fail')); }
    st.busy = false; renderStatic(); return;
  }
  if (t.id === 'post') {
    const text = $('draft').value.trim(); if (!text) return;
    st.edits[st.active] = text;
    if (!RD.writeUrl) { toast(tx('noLink')); return; }
    // Google cannot be pre-filled from a link, so: copy, then show a clear "paste it there" step with a separate Open Google tap.
    $('goText').value = text; $('goManual').hidden = true; $('goTitle').hidden = false; $('goCopy').textContent = tx('goCopy');
    $('goOpen').href = RD.writeUrl;
    $('step2').hidden = true; $('step3').hidden = true; $('step4').hidden = false; $('own').hidden = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    copyText(text).then(showCopyResult);
    return;
  }
  if (t.id === 'goCopy') { showCopyResult(await copyText($('goText').value), true); return; }
  if (t.id === 'goBack') { $('step4').hidden = true; $('step2').hidden = false; $('step3').hidden = false; setOwnLink(); return; }
  if (t.id === 'goOpen') {   // copy once more right before leaving, in case the phone cleared it, and count the click
    copyText($('goText').value); api('click', { rating: st.rating, lang: st.lang, mode: 'draft' }); return;
  }
});
function showCopyResult(ok, again) {
  $('goTitle').hidden = !ok; $('goManual').hidden = ok;
  if (again) $('goCopy').textContent = ok ? tx('goCopiedNow') : tx('goCopy');
  if (!ok) { $('goText').focus(); $('goText').select(); }
}
function copyText(text) {
  if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text).then(() => true, () => fallbackCopy(text));
  return Promise.resolve(fallbackCopy(text));
}
function fallbackCopy(text) { const ta = document.createElement('textarea'); ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0'; document.body.appendChild(ta); ta.focus(); ta.select(); let ok = false; try { ok = document.execCommand('copy'); } catch (e) {} ta.remove(); return ok; }
$('note').addEventListener('input', () => { $('noteCount').textContent = $('note').value.length; });
$('draft').addEventListener('input', () => { st.edits[st.active] = $('draft').value; });
renderStatic(); setOwnLink();
</script>
</body>
</html>
