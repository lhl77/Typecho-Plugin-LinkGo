<?php
// Vercel 风格：黑白极简、细描边、等宽地址、无阴影
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Vercel';
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
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="shell">
        <section class="panel" role="group" aria-labelledby="jump-title">
            <div class="panel__head">
                <?php if (!empty($showLogo)): ?>
                <div class="mark" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </div>
                <?php endif; ?>
                <span class="eyebrow">Redirecting</span>
            </div>

            <h1 id="jump-title" class="panel__title">即将离开 <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></h1>
            <p class="panel__desc">你正在离开本站，即将前往以下地址。请确认目标站点可信后再继续。</p>

            <div class="target">
                <div class="target__label">目标地址</div>
                <code class="target__url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></code>
            </div>

            <div class="panel__actions">
                <a id="continueBtn" class="geo-btn" href="<?php echo htmlspecialchars(isset($ctaHref) ? $ctaHref : $url, ENT_QUOTES); ?>" rel="nofollow noopener">
                    继续访问
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>
                <a class="geo-btn geo-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            </div>
        </section>

        <footer class="foot">
            <span>LinkGo</span>
            <span class="dot"></span>
            <span>&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
        </footer>
    </main>
</body>
</html>
