<?php
// Loading-1 主题模板：支持系统黑暗模式和亮模式
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>加载中...</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php
    // 主题样式表，优先从插件目录的 page/themes/<theme>/style.css 引用
    $themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css';
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
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
  var target = <?php echo json_encode($url); ?>;
  setTimeout(function(){
    try{ window.location.href = target; }catch(e){ window.location = target; }
  }, 3000);
})();
</script>
</body>
</html>
