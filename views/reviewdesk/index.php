<?php
$base = '/' . $org['slug'];
require __DIR__ . '/../layouts/header.php';
$staff = (new User())->allForOrg((int) $org['id']);
$inp = 'w-full rounded border border-slate-300 px-3 py-2 text-sm';
?>
<div class="max-w-4xl space-y-6">
  <div>
    <h1 class="text-xl font-semibold text-slate-900">Google Reviews <span class="text-xs font-medium text-indigo-700 bg-indigo-50 rounded px-2 py-0.5 align-middle">ReviewDesk</span></h1>
    <p class="text-sm text-slate-500 mt-1">Customers scan your QR, tap stars and what they liked, get a short suggested review in their own language, edit it, and post it on Google themselves.</p>
  </div>

  <div class="grid grid-cols-3 gap-3">
    <?php foreach ([['Page opens', $stats['scan']], ['Suggestions made', $stats['draft']], ['Tapped “Post on Google”', $stats['post_click']]] as [$l, $v]): ?>
      <div class="bg-white rounded-lg border border-slate-200 p-4"><div class="text-[11px] font-semibold text-slate-500 tracking-wide uppercase"><?= h($l) ?></div><div class="text-2xl font-bold"><?= (int) $v ?></div><div class="text-[11px] text-slate-400">last 30 days</div></div>
    <?php endforeach; ?>
  </div>

  <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm text-emerald-900">
    <div class="font-semibold mb-1">Built to follow Google’s review rules</div>
    <ul class="list-disc pl-5 space-y-0.5">
      <li>Every rating, 1 to 5 stars, can go to Google. Nothing is hidden or redirected away from it.</li>
      <li>The suggestion uses only what the customer tapped or typed. The customer edits it and posts from their own Google account — nothing is ever posted for them.</li>
      <li>Do not offer a discount, gift or any reward for reviews. The page refuses wording like that.</li>
    </ul>
  </div>

  <!-- Branches -->
  <section class="bg-white rounded-lg border border-slate-200 p-5">
    <h2 class="font-semibold text-slate-900">Your Google link</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Add the <b>Place ID</b> of your Google Business Profile. Find it with Google’s
      <a class="text-indigo-700 underline" href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" rel="noopener">Place ID Finder</a>
      (it starts with “ChIJ…”). Pasting a full “write a review” link also works. One Place ID per branch.</p>
    <div class="space-y-4">
      <?php foreach ($branches as $b): $link = $host . '/review/' . $b['slug']; ?>
      <div class="border border-slate-200 rounded-lg p-4">
        <form method="POST" class="grid sm:grid-cols-6 gap-3 items-end">
          <?= CsrfHelper::getField() ?><input type="hidden" name="form" value="branch"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
          <label class="sm:col-span-2 text-xs font-medium text-slate-600">Branch name<input name="name" required maxlength="100" value="<?= h($b['name']) ?>" class="mt-1 <?= $inp ?>"></label>
          <label class="sm:col-span-2 text-xs font-medium text-slate-600">Google Place ID<input name="place_id" value="<?= h($b['place_id'] ?? '') ?>" placeholder="ChIJ…" class="mt-1 <?= $inp ?> font-mono"></label>
          <label class="sm:col-span-2 text-xs font-medium text-slate-600">WhatsApp for alerts <span class="text-slate-400">(coming soon)</span><input name="alert_whatsapp" value="<?= h($b['alert_whatsapp'] ?? '') ?>" inputmode="numeric" placeholder="98300 11223" class="mt-1 <?= $inp ?>"></label>
          <div class="sm:col-span-6 flex flex-wrap items-center gap-3">
            <label class="text-sm text-slate-700 flex items-center gap-2"><input type="checkbox" name="active" value="1" <?= $b['active'] ? 'checked' : '' ?>> Page is live</label>
            <button class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save</button>
            <span class="text-xs <?= !empty($b['place_id']) ? 'text-emerald-700' : 'text-amber-700' ?>"><?= !empty($b['place_id']) ? '✓ Google link ready' : 'Add the Place ID to switch on the Google button' ?></span>
          </div>
        </form>
        <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2 text-sm">
          <span class="font-mono text-xs bg-slate-50 border border-slate-200 rounded px-2 py-1 break-all"><?= h($link) ?></span>
          <button type="button" class="text-indigo-700 font-medium" onclick="navigator.clipboard.writeText('<?= h($link) ?>').then(()=>{this.textContent='Copied ✓';setTimeout(()=>this.textContent='Copy link',1500)})">Copy link</button>
          <a class="text-slate-700 underline" href="<?= h($link) ?>?preview=1" target="_blank" rel="noopener">Preview page</a>
          <a class="text-slate-700 underline" href="<?= $base ?>/reviewdesk/qr?branch=<?= (int) $b['id'] ?>&kind=stand" target="_blank" rel="noopener">QR counter stand</a>
          <a class="text-slate-700 underline" href="<?= $base ?>/reviewdesk/qr?branch=<?= (int) $b['id'] ?>&kind=sticker" target="_blank" rel="noopener">QR stickers</a>
          <?php if (count($branches) > 1): ?>
          <form method="POST" class="ml-auto" onsubmit="return confirm('Remove this branch? Its QR code will stop working.')"><?= CsrfHelper::getField() ?><input type="hidden" name="form" value="branch_delete"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button class="text-red-600 text-sm">Remove</button></form>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <form method="POST" class="mt-4 grid sm:grid-cols-6 gap-3 items-end bg-slate-50 rounded-lg p-4">
      <?= CsrfHelper::getField() ?><input type="hidden" name="form" value="branch_add">
      <label class="sm:col-span-2 text-xs font-medium text-slate-600">Add another branch<input name="name" required maxlength="100" placeholder="e.g. Salt Lake branch" class="mt-1 <?= $inp ?>"></label>
      <label class="sm:col-span-3 text-xs font-medium text-slate-600">Its Google Place ID<input name="place_id" placeholder="ChIJ…" class="mt-1 <?= $inp ?> font-mono"></label>
      <button class="sm:col-span-1 bg-slate-800 text-white text-sm font-medium px-4 py-2 rounded hover:bg-slate-900">+ Add</button>
    </form>
  </section>

  <!-- Customer page -->
  <section class="bg-white rounded-lg border border-slate-200 p-5">
    <h2 class="font-semibold text-slate-900">What the customer page asks</h2>
    <form method="POST" class="mt-3 space-y-4">
      <?= CsrfHelper::getField() ?><input type="hidden" name="form" value="page">
      <label class="block text-sm font-medium text-slate-700">Greeting line <span class="font-normal text-slate-400">(leave blank for “How was your visit?” in their language)</span>
        <input name="intro" maxlength="160" value="<?= h($settings['intro']) ?>" class="mt-1 <?= $inp ?>"></label>
      <div class="grid sm:grid-cols-2 gap-4">
        <label class="block text-sm font-medium text-slate-700">“What did you like?” options <span class="font-normal text-slate-400">— one per line, shown for 3★ and above</span>
          <textarea name="chips_pos" rows="8" class="mt-1 <?= $inp ?>"><?= h(implode("\n", $settings['chips_pos'])) ?></textarea></label>
        <label class="block text-sm font-medium text-slate-700">“What could be better?” options <span class="font-normal text-slate-400">— shown for 3★ and below</span>
          <textarea name="chips_neg" rows="8" class="mt-1 <?= $inp ?>"><?= h(implode("\n", $settings['chips_neg'])) ?></textarea></label>
      </div>
      <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
        <div class="flex items-center gap-4"><span class="font-medium text-slate-700">Languages</span>
          <?php foreach (['en' => 'English', 'hi' => 'हिन्दी', 'bn' => 'বাংলা'] as $c => $n): ?>
            <label class="flex items-center gap-1.5"><input type="checkbox" name="langs[]" value="<?= $c ?>" <?= in_array($c, $settings['langs'], true) ? 'checked' : '' ?>> <?= h($n) ?></label>
          <?php endforeach; ?></div>
        <label class="flex items-center gap-2"><span class="font-medium text-slate-700">Opens in</span>
          <select name="default_lang" class="rounded border border-slate-300 px-2 py-1.5 bg-white"><?php foreach (['en' => 'English', 'hi' => 'हिन्दी', 'bn' => 'বাংলা'] as $c => $n): ?><option value="<?= $c ?>" <?= $settings['default_lang'] === $c ? 'selected' : '' ?>><?= h($n) ?></option><?php endforeach; ?></select></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="ai_enabled" value="1" <?= $settings['ai_enabled'] ? 'checked' : '' ?>> <span class="font-medium text-slate-700">Suggest review wording</span></label>
      </div>
      <button class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save customer page</button>
    </form>
    <div class="mt-5 pt-4 border-t border-slate-100">
      <div class="text-sm font-medium text-slate-700 mb-2">Logo</div>
      <form method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
        <?= CsrfHelper::getField() ?><input type="hidden" name="form" value="logo">
        <?php if ($settings['logo'] !== ''): ?><img src="/uploads/reviewdesk/<?= h($settings['logo']) ?>" alt="" class="w-12 h-12 rounded-lg object-cover border border-slate-200"><?php endif; ?>
        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="text-sm">
        <button class="bg-slate-800 text-white text-sm font-medium px-3 py-1.5 rounded">Upload</button>
        <?php if ($settings['logo'] !== ''): ?><button name="remove_logo" value="1" class="text-red-600 text-sm">Remove</button><?php endif; ?>
      </form>
    </div>
  </section>

  <!-- QR per staff -->
  <section class="bg-white rounded-lg border border-slate-200 p-5">
    <h2 class="font-semibold text-slate-900">Print your QR</h2>
    <p class="text-sm text-slate-500 mt-1 mb-3">An A6 counter stand and a sheet of stickers — open one, then press <b>Print</b> and choose <b>Save as PDF</b> or your printer. Pick a team member to make a QR just for them (their scans show up under their name later).</p>
    <form method="GET" action="<?= $base ?>/reviewdesk/qr" target="_blank" class="flex flex-wrap items-end gap-3">
      <label class="text-xs font-medium text-slate-600">Branch<select name="branch" class="mt-1 <?= $inp ?> bg-white"><?php foreach ($branches as $b): ?><option value="<?= (int) $b['id'] ?>"><?= h($b['name']) ?></option><?php endforeach; ?></select></label>
      <label class="text-xs font-medium text-slate-600">For<select name="staff" class="mt-1 <?= $inp ?> bg-white"><option value="">Whole shop</option><?php foreach ($staff as $u): ?><option value="<?= (int) $u['id'] ?>"><?= h($u['name']) ?></option><?php endforeach; ?></select></label>
      <label class="text-xs font-medium text-slate-600">Layout<select name="kind" class="mt-1 <?= $inp ?> bg-white"><option value="stand">A6 counter stand</option><option value="sticker">Sticker sheet</option></select></label>
      <button class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Open printable QR</button>
    </form>
  </section>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
