<?php
// Telegram 风格：浅灰底 + 蓝色主色、10px 圆角卡片
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Telegram';
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
    <meta name="theme-color" content="#17212B" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="tg-shell">
        <section class="tg-box" role="group" aria-labelledby="jump-title">
            <div class="tg-head">
                <?php if (!empty($showLogo)): ?>
                <div class="tg-logo" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </div>
                <?php endif; ?>
                <span class="tg-label">安全跳转</span>
            </div>

            <h1 id="jump-title" class="tg-title">即将离开 <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></h1>
            <p class="tg-desc">你正在离开本站，即将前往外部站点。请确认目标地址可信后再继续。</p>

            <div class="tg-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></div>

            <div class="tg-actions">
                <a id="continueBtn" class="tg-btn tg-btn--primary" href="<?php echo htmlspecialchars(isset($ctaHref) ? $ctaHref : $url, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                <a class="tg-btn tg-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            </div>
        </section>
        <footer class="tg-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></footer>
    </main>
</body>
</html>
