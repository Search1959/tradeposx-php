<?php
/** Generic "edit details" screen for finished documents (sales invoice, purchase, service invoice).
 * Expects: $docTitle, $docNo, $backUrl, $fields[], $values[], $error, $note. Amounts and line items are
 * deliberately not editable here — correcting those means voiding/cancelling and re-billing so stock,
 * Udhaar and tax stay consistent. */
$org = Tenant::current(); $base = '/' . $org['slug'];
require __DIR__ . '/../layouts/header.php';
?>
<div class="max-w-xl mx-auto">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-900"><?= h($docTitle) ?> <span class="text-slate-400 font-normal"><?= h($docNo) ?></span></h1>
    <a href="<?= h($backUrl) ?>" class="text-sm text-slate-500">&larr; Back</a>
  </div>
  <?php if (!empty($error)): ?><div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div><?php endif; ?>
  <div class="mb-4 text-xs text-slate-600 bg-slate-100 border border-slate-200 rounded px-3 py-2"><?= h($note) ?></div>
  <form method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-5 space-y-4">
    <?= CsrfHelper::getField() ?>
    <?php foreach ($fields as $f):
      $name = $f['name']; $val = $values[$name] ?? ''; $ro = !empty($f['readonly']); ?>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1"><?= h($f['label']) ?></label>
        <?php if (($f['type'] ?? 'text') === 'select'): ?>
          <select name="<?= h($name) ?>" <?= $ro ? 'disabled' : '' ?> class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <?php foreach ($f['options'] as $o): ?><option value="<?= h($o) ?>" <?= $o === $val ? 'selected' : '' ?>><?= h($o) ?></option><?php endforeach; ?>
          </select>
        <?php elseif (($f['type'] ?? 'text') === 'textarea'): ?>
          <textarea name="<?= h($name) ?>" rows="3" <?= $ro ? 'readonly' : '' ?> class="w-full rounded border border-slate-300 px-3 py-2 text-sm"><?= h($val) ?></textarea>
        <?php else: ?>
          <input type="<?= h($f['type'] ?? 'text') ?>" name="<?= h($name) ?>" value="<?= h($val) ?>" <?= $ro ? 'readonly' : '' ?>
                 class="w-full rounded border border-slate-300 px-3 py-2 text-sm <?= $ro ? 'bg-slate-50 text-slate-500' : '' ?>">
        <?php endif; ?>
        <?php if (!empty($f['help'])): ?><p class="text-xs text-slate-400 mt-1"><?= h($f['help']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <div class="flex gap-2 pt-2">
      <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">Save changes</button>
      <a href="<?= h($backUrl) ?>" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
