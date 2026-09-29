<?php $org = Tenant::current(); $base = '/' . $org['slug']; $currentUser = Auth::user(); require __DIR__ . '/../layouts/header.php'; ?>

<div class="flex items-center justify-between mb-4">
  <h1 class="text-xl font-semibold text-slate-900">Team</h1>
  <a href="<?= $base ?>/team/new" class="bg-indigo-600 text-white text-sm font-medium px-4 py-2 rounded hover:bg-indigo-700">+ Add Team Member</a>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
      <tr>
        <th class="text-left px-4 py-2">Name</th>
        <th class="text-left px-4 py-2">Email</th>
        <th class="text-left px-4 py-2">Role</th>
        <th class="text-center px-4 py-2">Status</th>
        <th class="px-4 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
      <?php foreach ($members as $m): $isSelf = (int)$m['id'] === (int)$currentUser['id']; ?>
      <tr>
        <td class="px-4 py-2 font-medium text-slate-900"><?= h($m['name']) ?><?= $isSelf ? ' <span class="text-xs text-slate-400">(you)</span>' : '' ?></td>
        <td class="px-4 py-2 text-slate-600"><?= h($m['email'] ?? $m['username'] ?? '') ?></td>
        <td class="px-4 py-2">
          <span class="text-xs px-2 py-0.5 rounded-full <?= $m['role'] === 'owner' ? 'bg-indigo-100 text-indigo-700' : ($m['role'] === 'admin' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600') ?>">
            <?= h(ucfirst($m['role'])) ?>
          </span>
        </td>
        <td class="px-4 py-2 text-center">
          <span class="text-xs px-2 py-0.5 rounded-full <?= $m['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"><?= h(ucfirst($m['status'])) ?></span>
        </td>
        <td class="px-4 py-2 text-right whitespace-nowrap">
          <?php if ($m['role'] !== 'owner'): ?>
            <a href="<?= $base ?>/team/edit/<?= (int)$m['id'] ?>" class="text-indigo-600 hover:underline text-xs">Edit</a>
            <?php if (!$isSelf): ?>
              <form method="POST" action="<?= $base ?>/team/toggleStatus/<?= (int)$m['id'] ?>" class="inline"
                    onsubmit="return confirm('<?= $m['status'] === 'active' ? 'Disable' : 'Re-activate' ?> this account?');">
                <?= CsrfHelper::getField() ?>
                <button class="text-xs ml-2 <?= $m['status'] === 'active' ? 'text-red-600' : 'text-green-600' ?> hover:underline">
                  <?= $m['status'] === 'active' ? 'Disable' : 'Activate' ?>
                </button>
              </form>
            <?php endif; ?>
          <?php else: ?>
            <span class="text-xs text-slate-300">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
