<?php /** Shown when a review page does not exist or is not set up yet. Expects $message. */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Review page</title>
<style>
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", sans-serif; color: #0f172a; padding: 24px; box-sizing: border-box; }
  .box { max-width: 380px; text-align: center; }
  .icon { width: 56px; height: 56px; border-radius: 16px; background: #e2e8f0; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
  p { color: #475569; line-height: 1.55; }
</style>
</head>
<body>
  <div class="box">
    <div class="icon">⭐</div>
    <h1 style="font-size:20px;margin:0 0 8px">Review page</h1>
    <p><?= h($message ?? 'This page is not available.') ?></p>
  </div>
</body>
</html>
