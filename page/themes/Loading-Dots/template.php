<?php
// Loading-Dots：极简三点跳动加载动画（3 秒后跳转）
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$themeName = isset($themeName) ? $themeName : 'Loading-Dots';
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#101014" media="(prefers-color-scheme: dark)">
    <title>正在跳转…<?php echo $siteTitle !== '' ? ' - ' . htmlspecialchars($siteTitle, ENT_QUOTES) : ''; ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>}</style>
    <?php endif; ?>
</head>
<body>
    <main class="ld-wrap">
        <div class="ld-dots" aria-hidden="true">
            <span></span><span></span><span></span>
        </div>
        <p class="ld-text">正在离开本站…</p>
        <?php if ($siteTitle !== ''): ?><p class="ld-sub"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p><?php endif; ?>
    </main>
<script>
(function(){
<?php if (empty($previewMode)): ?>
    var target = <?php echo json_encode((string)$url); ?>;
    setTimeout(function(){ try { window.location.href = target; } catch (e) { window.location = target; } }, 3000);
<?php endif; ?>
})();
</script>
</body>
</html>
