<?php
$org = Tenant::current(); $base = '/' . $org['slug']; require __DIR__ . '/../layouts/header.php';
$campaign = $campaign ?? [];
$isEdit = !empty($campaign['id']);
$bothType = ($org['business_type'] ?? 'both') === 'both';

$templates = [
    'REVIEW' => "Hi {name}! 🙏 Thank you for choosing {business}. We'd really appreciate it if you could take a moment to share a quick review — it helps us a lot and helps other customers find us too.",
    'PROMOTION' => "Hi {name}! 🎉 {business} has something special for you right now. Drop by or reply to this message to know more — thank you for being a valued customer!",
];
?>

<h1 class="text-xl font-semibold text-slate-900 mb-4"><?= $isEdit ? 'Edit Outreach Campaign' : 'New Outreach Campaign' ?></h1>

<?php if (!empty($error)): ?>
  <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><?= h($error) ?></div>
<?php endif; ?>

<div x-data="outreachForm(<?= h(json_encode($templates)) ?>)" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-xl">
  <form method="POST" class="space-y-4">
    <?= CsrfHelper::getField() ?>

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Campaign Name *</label>
      <input type="text" name="name" required value="<?= h($campaign['name'] ?? '') ?>" placeholder="e.g. Monthly Review Request" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
      <select name="campaign_type" x-model="type" @change="applyTemplate()" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
        <option value="REVIEW" <?= ($campaign['campaign_type'] ?? '') === 'REVIEW' ? 'selected' : '' ?>>⭐ Review Request</option>
        <option value="PROMOTION" <?= ($campaign['campaign_type'] ?? '') === 'PROMOTION' ? 'selected' : '' ?>>📣 Promotion</option>
        <option value="CUSTOM" <?= ($campaign['campaign_type'] ?? 'CUSTOM') === 'CUSTOM' ? 'selected' : '' ?>>✏️ Custom</option>
      </select>
      <p class="text-xs text-slate-400 mt-1" x-show="type !== 'CUSTOM'">Picking Review or Promotion fills in a starter message below — edit it however you like.</p>
    </div>

    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">Message *</label>
      <textarea name="message" required rows="5" x-model="message" class="w-full rounded border border-slate-300 px-3 py-2 text-sm"><?= h($campaign['message'] ?? '') ?></textarea>
      <p class="text-xs text-slate-400 mt-1">Use <code class="bg-slate-100 px-1 rounded">{name}</code> for the customer's name and <code class="bg-slate-100 px-1 rounded">{business}</code> for your business name — both get filled in automatically when sending.</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Send Again Every</label>
        <select name="recurrence" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
          <option value="WEEKLY" <?= ($campaign['recurrence'] ?? '') === 'WEEKLY' ? 'selected' : '' ?>>Week</option>
          <option value="EVERY_15_DAYS" <?= ($campaign['recurrence'] ?? '') === 'EVERY_15_DAYS' ? 'selected' : '' ?>>15 Days</option>
          <option value="MONTHLY" <?= ($campaign['recurrence'] ?? 'MONTHLY') === 'MONTHLY' ? 'selected' : '' ?>>Month</option>
          <option value="ONE_TIME" <?= ($campaign['recurrence'] ?? '') === 'ONE_TIME' ? 'selected' : '' ?>>One-time only</option>
        </select>
        <p class="text-xs text-slate-400 mt-1">Controls who the Run screen flags as "due again" — nothing sends automatically.</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Audience</label>
        <?php if ($bothType): ?>
          <select name="audience" class="w-full rounded border border-slate-300 px-3 py-2 text-sm">
            <option value="TRADE" <?= ($campaign['audience'] ?? 'TRADE') === 'TRADE' ? 'selected' : '' ?>>Trade Customers</option>
            <option value="SERVICE" <?= ($campaign['audience'] ?? '') === 'SERVICE' ? 'selected' : '' ?>>Service Customers</option>
          </select>
        <?php else: ?>
          <?php $fixedAudience = ($org['business_type'] ?? '') === 'service' ? 'SERVICE' : 'TRADE'; ?>
          <input type="hidden" name="audience" value="<?= $fixedAudience ?>">
          <div class="w-full rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"><?= $fixedAudience === 'SERVICE' ? 'Service Customers' : 'Trade Customers' ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700"><?= $isEdit ? 'Save Changes' : 'Create Campaign' ?></button>
      <a href="<?= $base ?>/outreach" class="text-sm text-slate-500 px-4 py-2">Cancel</a>
    </div>
  </form>
</div>

<script>
function outreachForm(templates) {
  return {
    type: '<?= h($campaign['campaign_type'] ?? 'CUSTOM') ?>',
    message: <?= json_encode($campaign['message'] ?? '') ?>,
    templates: templates,
    applyTemplate() {
      if (this.templates[this.type] && !this.message.trim()) {
        this.message = this.templates[this.type];
      }
    },
  };
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
