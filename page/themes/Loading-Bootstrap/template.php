<?php
// Loading-1 主题模板：支持系统黑暗模式和亮模式
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
  <title>加载中...</title>
  <?php
    $bootstrapCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/vendor/bootstrap.min.css';
    // 主题样式表，优先从插件目录的 page/themes/<theme>/style.css 引用
    $themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$bootstrapCssFile = __DIR__ . '/vendor/bootstrap.min.css';
$bootstrapCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/vendor/bootstrap.min.css?v=' . (is_file($bootstrapCssFile) ? filemtime($bootstrapCssFile) : '1');
    ?>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($bootstrapCssUrl, ENT_QUOTES); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
  <?php if (!empty($themeColor)): ?>
  <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>}</style>
  <?php endif; ?>
  <style>
    body,html {
      background-color: #F7F8FA; /* 浅色 */
      color: #000000;
    }
    @media (prefers-color-scheme: dark) {
      body,html {
        background-color: #0F0F0F; /* 深色 */
        color: #ffffff;
      }
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
  <div class="spinner-border text-primary" role="status" style="width: 5rem; height: 5rem;">
    <span class="visually-hidden">Loading...</span>
  </div>

<script>
(function(){
<?php if (empty($previewMode)): ?>
  var target = <?php echo json_encode($url); ?>;
  setTimeout(function(){
    try{ window.location.href = target; }catch(e){ window.location = target; }
  }, 3000);
<?php endif; ?>
})();
</script>
</body>
</html>
