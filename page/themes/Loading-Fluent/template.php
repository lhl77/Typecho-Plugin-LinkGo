<?php
// FluentLoading 主题模板：支持系统黑暗模式和亮模式
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
    // 主题样式表，优先从插件目录的 page/themes/<theme>/style.css 引用
    $themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
    ?>
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
<body>
  <div class="fluent-loading-container">
    <div class="fluent-spinner">
      <div class="fluent-circle"></div>
      <div class="fluent-circle"></div>
      <div class="fluent-circle"></div>
      <div class="fluent-circle"></div>
    </div>
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
