<?php
// 优化后的 Loading-Material 主题模板：支持系统黑暗模式和亮模式

// 生成主题样式表的 URL
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css';
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>加载中...</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
  <style>
    html,body {
      background-color: #F7F8FA; /* 浅色 */
      color: #000000;
    }
    @media (prefers-color-scheme: dark) {
      html,body {
        background-color: #0F0F0F; /* 深色 */
        color: #ffffff;
      }
    }
  </style>
</head>
<body>
  <div class="loading-container">
    <div class="spinner" aria-hidden="true">
      <svg class="circular" viewBox="0 0 48 48" width="96" height="96" aria-hidden="true">
        <defs>
          <linearGradient id="g" x1="0%" x2="100%" y1="0%" y2="100%">
            <stop offset="0%" stop-color="#7c3aed" />
            <stop offset="100%" stop-color="#3b82c4" />
          </linearGradient>
        </defs>
        <circle class="path" cx="24" cy="24" r="18" fill="none" stroke="url(#g)" stroke-width="4" stroke-linecap="round"></circle>
      </svg>
    </div>
  </div>

<script>
(function(){
  var targetUrl = <?php echo json_encode($url); ?>;
  setTimeout(function(){
    try {
      window.location.href = targetUrl;
    } catch (error) {
      window.location = targetUrl;
    }
  }, 3000);
})();
</script>
</body>
</html>
