<?php
// Bootstrap-Default 主题模板：仿照 Default，但使用 Bootstrap 组件并包含内容与可取消的自动跳转
// 期望变量：$siteUrl, $themeName, $title, $siteTitle, $logoUrl, $url, $displayYear
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$bootstrapCssFile = __DIR__ . '/vendor/bootstrap.min.css';
$bootstrapCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/vendor/bootstrap.min.css?v=' . (is_file($bootstrapCssFile) ? filemtime($bootstrapCssFile) : '1');

// 跳转页展示设置（未提供时使用主题默认）
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = $showFullUrl ? $url : (isset($display_url) && $display_url !== '' ? $display_url : $url);
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
  <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle ?? '', ENT_QUOTES); ?></title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($bootstrapCssUrl, ENT_QUOTES); ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
  <?php if (!empty($themeColor)): ?>
  <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
  <?php endif; ?>
</head>
<body>
  <nav class="navbar navbar-expand" aria-label="site navigation">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="#">
        <?php if (!empty($showLogo)): ?>
        <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'" class="me-2"/>
        <?php endif; ?>
        </a>
        <span class="navbar-text mb-3"><?php echo htmlspecialchars($siteTitle ?? '', ENT_QUOTES); ?></span>
    </div>
  </nav>

  <main class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title mb-3">安全跳转 — 请确认</h5>
            <p class="card-text mb-3">您即将离开本站并访问下面的目标链接。我们会在转向前为您展示该链接信息并允许取消跳转。</p>

            <div class="alert d-flex align-items-start gap-3 mt-4">
              <div class="fs-3"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 5 6v5c0 4.6 3 8.4 7 10 4-1.6 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg></div>
              <div class="flex-grow-1">
                <div class="fw-semibold mb-1">目标链接</div>
                <div class="url-box" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></div>
                <div class="small mt-2">本站无法完全保证外部站点的安全性，请谨慎打开未知链接。</div>
              </div>
            </div>

            <div class="d-flex align-items-center gap-3 mt-4">
              <a id="continueBtn" class="btn btn-primary d-inline-flex align-items-center" href="<?php echo htmlspecialchars(isset($ctaHref) ? $ctaHref : $url, ENT_QUOTES); ?>" rel="nofollow noopener">
                <svg class="me-2" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg> 继续访问
              </a>
              <button id="cancelBtn" class="btn btn-secondary d-inline-flex align-items-center">取消</button>
              <div class="ms-auto text-end small">自动跳转：<span id="countdown">5</span>s</div>
            </div>

            <div class="progress mt-3" style="height:8px;border-radius:8px;overflow:hidden">
              <div id="prog" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>

          </div>
        </div>

        <div class="site-footer text-center mt-3">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></div>
      </div>
    </div>
  </main>

<script>
(function(){
  var target = <?php echo json_encode($url); ?>;
  var duration = 5; // 秒
  var remained = duration;
  var countdownEl = document.getElementById('countdown');
  var prog = document.getElementById('prog');
  var cont = document.getElementById('continueBtn'); 
  var cancel = document.getElementById('cancelBtn');

<?php if (!empty($previewMode)): ?>
  // 预览模式：不自动跳转，也不让示例链接真正离开预览
  if (countdownEl && countdownEl.parentNode) { countdownEl.parentNode.style.display = 'none'; }
  if (prog) { prog.style.width = '38%'; prog.style.opacity = '.45'; }
  if (cont) { cont.addEventListener('click', function(e){ e.preventDefault(); }); }
  if (cancel) { cancel.addEventListener('click', function(e){ e.preventDefault(); }); }
  return;
<?php endif; ?>

  var interval = setInterval(function(){
    remained -= 0.1;
    if(remained <= 0){
      clearInterval(interval);
      window.location.href = target;
      return;
    }
    var pct = ( (duration - remained) / duration) * 100;
    if(prog) prog.style.width = pct + '%';
    if(countdownEl) countdownEl.textContent = Math.ceil(remained);
  },100);

  if (cancel) {
    cancel.addEventListener('click', function(){
      clearInterval(interval);
      if(prog) prog.style.width = '0%';
      if(countdownEl) countdownEl.textContent = '已取消';
    });
  }

})();
</script>
</body>
</html>
