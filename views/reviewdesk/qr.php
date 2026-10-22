<?php
/** Printable QR page. Expects $org, $branch, $url, $kind ('stand'|'sticker'), $logo, $staffName, $base. Standalone (no app chrome) so it prints cleanly. */
$shop = (string) $branch['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review QR · <?= h($shop) ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; background: #e2e8f0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", "Noto Sans Devanagari", "Noto Sans Bengali", sans-serif; color: #0f172a; }
  .bar { position: sticky; top: 0; background: #0f172a; color: #fff; padding: 10px 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; z-index: 5; }
  .bar a, .bar button { color: #fff; background: #4f46e5; border: 0; border-radius: 8px; padding: 8px 14px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }
  .bar a.plain { background: transparent; border: 1px solid #475569; }
  .bar .hint { font-size: 12px; color: #94a3b8; margin-left: auto; }
  .stage { padding: 24px 12px 40px; display: flex; justify-content: center; }

  /* A6 counter stand: 105 x 148 mm */
  .stand { width: 105mm; height: 148mm; background: #fff; padding: 9mm 8mm; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 10px 30px rgba(15,23,42,.25); }
  .stand .logo { width: 17mm; height: 17mm; object-fit: cover; border-radius: 4mm; }
  .stand .logo.ph { display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6366f1, #10b981); color: #fff; font-size: 9mm; font-weight: 700; }
  .stand h1 { font-size: 6.2mm; margin: 3mm 0 0; line-height: 1.15; }
  .stand .ask { font-size: 4.2mm; margin-top: 3mm; color: #334155; line-height: 1.3; }
  .stand .q { margin: 5mm 0 3mm; padding: 3mm; border: .6mm solid #0f172a; border-radius: 4mm; line-height: 0; }
  .stand .q svg { width: 52mm; height: 52mm; }
  .stand .step { font-size: 3.6mm; color: #475569; margin-top: 1mm; }
  .stand .stars { font-size: 6mm; color: #f59e0b; letter-spacing: 1mm; margin-top: 2mm; }
  .stand .foot { margin-top: auto; font-size: 2.8mm; color: #94a3b8; }
  .stand .url { margin-top: 2mm; font-size: 3.4mm; font-weight: 600; color: #4338ca; word-break: break-all; }
  .sticker .url { font-size: 2.4mm; color: #4338ca; margin-top: .6mm; word-break: break-all; }
  .stage.pages { flex-direction: column; align-items: center; gap: 22px; }
  @media screen and (max-width: 440px) { .stand { zoom: .86; } .sheet { zoom: .45; } }   /* fit a phone screen; print sizes are untouched */

  /* Back of the stand: the steps, in three languages */
  .back { justify-content: flex-start; padding: 8mm 8mm 7mm; }
  .back h2 { font-size: 5.2mm; margin: 0; line-height: 1.2; }
  .back .sub { font-size: 3mm; color: #64748b; margin: 1mm 0 3.2mm; }
  .back ol { list-style: none; margin: 0; padding: 0; width: 100%; text-align: left; display: flex; flex-direction: column; gap: 2.4mm; }
  .back li { display: flex; gap: 2.6mm; align-items: flex-start; }
  .back .n { flex: none; width: 6.4mm; height: 6.4mm; border-radius: 50%; background: #4f46e5; color: #fff; font-weight: 700; font-size: 3.6mm; display: flex; align-items: center; justify-content: center; margin-top: .4mm; }
  .back .t { font-size: 2.95mm; line-height: 1.32; color: #475569; }
  .back .t b { display: block; font-size: 3.4mm; color: #0f172a; margin-bottom: .2mm; }
  .back .note { margin-top: auto; width: 100%; border-top: .3mm dashed #cbd5e1; padding-top: 2.2mm; font-size: 2.7mm; color: #64748b; text-align: center; line-height: 1.35; }
  .back .url { margin-top: 1.2mm; }
  .tip { background: #1e293b; color: #cbd5e1; font-size: 12px; padding: 7px 16px; text-align: center; }

  /* Sticker sheet: A4, 3 x 4 stickers of 60 x 60 mm */
  .sheet { width: 210mm; min-height: 297mm; background: #fff; padding: 10mm 8mm; display: grid; grid-template-columns: repeat(3, 60mm); grid-auto-rows: 66mm; gap: 3mm 6mm; justify-content: center; align-content: start; box-shadow: 0 10px 30px rgba(15,23,42,.25); }
  .sticker { width: 60mm; height: 64mm; border: .3mm dashed #94a3b8; border-radius: 3mm; padding: 3mm; display: flex; flex-direction: column; align-items: center; text-align: center; }
  .sticker .q { line-height: 0; }
  .sticker .q svg { width: 38mm; height: 38mm; }
  .sticker b { font-size: 3.6mm; margin-top: 1.5mm; line-height: 1.15; }
  .sticker span { font-size: 3mm; color: #475569; margin-top: .8mm; }

  @page { size: <?= $kind === 'sticker' ? 'A4' : 'A6' ?>; margin: 0; }
  @media print { body { background: #fff; } .bar, .tip { display: none; } .stage { padding: 0; } .stage.pages { display: block; } .stand, .sheet { box-shadow: none; } .stand { break-after: page; page-break-after: always; } .stand:last-child { break-after: auto; page-break-after: auto; } .sticker { border-color: #cbd5e1; } }
</style>
</head>
<body>
<div class="bar">
  <button onclick="window.print()">Print / Save as PDF</button>
  <button id="share">Share on WhatsApp</button>
  <button id="copy">Copy link</button>
  <button id="png">Download QR image</button>
  <a class="plain" href="<?= h($base) ?>/reviewdesk">← Back</a>
  <span class="hint"><?= h($url) ?><?= $staffName !== '' ? ' · for ' . h($staffName) : '' ?></span>
</div>
<div class="tip">Sending it to someone on their phone? Use <b>Share on WhatsApp</b> — a QR cannot be scanned from the same phone it is shown on, so they get the tappable link too.</div>
<?php
  // The steps shown on the back of the counter stand. Kept short; the three languages match the customer page.
  $backSteps = [
      ['Scan the QR', 'अपने फ़ोन के कैमरे से QR स्कैन करें।', 'ফোনের ক্যামেরা দিয়ে QR স্ক্যান করুন।'],
      ['Tap the stars', 'अपने अनुभव के लिए स्टार चुनें।', 'আপনার অভিজ্ঞতার জন্য স্টার বেছে নিন।'],
      ['Tap what you liked', 'जो पसंद आया (या जो बेहतर हो सकता है) उस पर टैप करें; चाहें तो दो शब्द लिखें।', 'যা ভালো লেগেছে (বা যা আরও ভালো হতে পারত) তাতে ট্যাপ করুন; চাইলে কয়েকটি কথা লিখুন।'],
      ['Check the suggested review and edit it', 'सुझाए गए रिव्यू को पढ़ें और अपने असली अनुभव के अनुसार बदलें।', 'প্রস্তাবিত রিভিউ পড়ে নিজের আসল অভিজ্ঞতা অনুযায়ী বদলে নিন।'],
      ['Copy, open Google, paste and Post', '“कॉपी करें और Google पर पोस्ट करें” दबाएँ, Google में पेस्ट करें, फिर Post करें।', '“কপি করে Google-এ পোস্ট করুন” চাপুন, Google-এ পেস্ট করে Post করুন।'],
  ];
?>
<div class="stage<?= $kind === 'sticker' ? '' : ' pages' ?>">
<?php if ($kind === 'stand' || $kind === 'back'): ?>
<?php if ($kind === 'stand'): ?>
  <div class="stand">
    <?php if ($logo !== ''): ?><img class="logo" src="<?= h($logo) ?>" alt=""><?php else: ?><div class="logo ph"><?= h(mb_strtoupper(mb_substr($shop, 0, 1))) ?></div><?php endif; ?>
    <h1><?= h($shop) ?></h1>
    <div class="ask">Share your experience on Google<br><small style="color:#64748b">गूगल पर अपना अनुभव बताइए · গুগলে আপনার অভিজ্ঞতা জানান</small></div>
    <div class="q" id="qr"></div>
    <div class="stars">★★★★★</div>
    <div class="step">Scan · tap stars · edit · post</div>
    <div class="url"><?= h(preg_replace('#^https?://#', '', $url)) ?></div>
    <div class="foot">Your honest review helps other customers</div>
  </div>
<?php endif; ?>
  <div class="stand back">
    <h2>Share your review in 5 easy steps</h2>
    <div class="sub">5 आसान स्टेप में रिव्यू दें · ৫টি সহজ ধাপে রিভিউ দিন</div>
    <ol>
      <?php foreach ($backSteps as $i => $s): ?>
        <li><span class="n"><?= $i + 1 ?></span><span class="t"><b><?= h($s[0]) ?></b><?= h($s[1]) ?><br><?= h($s[2]) ?></span></li>
      <?php endforeach; ?>
    </ol>
    <div class="note">Write what you really felt — it is your own review, posted from your own Google account. We never give anything in return for a review.<br>आपका रिव्यू आपके अपने अनुभव पर आधारित हो · আপনার রিভিউ আপনার নিজের অভিজ্ঞতার কথা হোক
      <div class="url"><?= h(preg_replace('#^https?://#', '', $url)) ?></div></div>
  </div>
<?php else: ?>
  <div class="sheet">
    <?php for ($i = 0; $i < 12; $i++): ?>
      <div class="sticker"><div class="q qr-copy"></div><b><?= h($shop) ?></b><span>Scan to share your experience</span><span class="url"><?= h(preg_replace('#^https?://#', '', $url)) ?></span></div>
    <?php endfor; ?>
  </div>
<?php endif; ?>
</div>
<script>
  const URL_TO_ENCODE = <?= json_encode($url) ?>;
  function makeSvg() {
    const qr = qrcode(0, 'M'); qr.addData(URL_TO_ENCODE); qr.make();
    return qr.createSvgTag({ scalable: true, margin: 0 });
  }
  const svg = makeSvg();
  const one = document.getElementById('qr'); if (one) one.innerHTML = svg;
  document.querySelectorAll('.qr-copy').forEach(el => { el.innerHTML = svg; });
  const SHOP = <?= json_encode($shop) ?>, FILE = 'review-qr-<?= h($branch['slug']) ?><?= $staffName !== '' ? '-staff' : '' ?>.png';
  function qrCanvas() {
    const qr = qrcode(0, 'M'); qr.addData(URL_TO_ENCODE); qr.make();
    const n = qr.getModuleCount(), cell = 16, pad = 32, c = document.createElement('canvas');
    c.width = c.height = n * cell + pad * 2; const x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.fillStyle = '#000';
    for (let r = 0; r < n; r++) for (let k = 0; k < n; k++) if (qr.isDark(r, k)) x.fillRect(pad + k * cell, pad + r * cell, cell, cell);
    return c;
  }
  document.getElementById('png').addEventListener('click', () => {
    const a = document.createElement('a'); a.href = qrCanvas().toDataURL('image/png'); a.download = FILE; a.click();
  });
  const shareText = SHOP + ' — please share your experience on Google: ' + URL_TO_ENCODE;
  document.getElementById('copy').addEventListener('click', async (e) => {
    const b = e.currentTarget; let ok = false;
    try { await navigator.clipboard.writeText(URL_TO_ENCODE); ok = true; } catch (err) { window.prompt('Copy this link:', URL_TO_ENCODE); }
    if (ok) { b.textContent = 'Copied ✓'; setTimeout(() => b.textContent = 'Copy link', 1500); }
  });
  document.getElementById('share').addEventListener('click', () => {
    // Phones: the share sheet with the QR picture AND the tappable link text. Desktop: a WhatsApp message with the link.
    qrCanvas().toBlob(async blob => {
      try {
        const file = new File([blob], FILE, { type: 'image/png' });
        if (navigator.canShare && navigator.canShare({ files: [file] })) { await navigator.share({ files: [file], text: shareText }); return; }
      } catch (err) { if (err && err.name === 'AbortError') return; }
      window.open('https://wa.me/?text=' + encodeURIComponent(shareText), '_blank', 'noopener');
    }, 'image/png');
  });
</script>
</body>
</html>
