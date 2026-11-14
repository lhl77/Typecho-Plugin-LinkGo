<?php
// Wave 主题：海浪分层 + 玻璃卡片
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Wave';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$wvHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($wvHost === '') {
    $wvHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#E8F8FF" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#06202B" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="wv">
        <div class="wv-ocean" aria-hidden="true">
            <svg class="wv-wave wv-wave--3" viewBox="0 0 2880 200" preserveAspectRatio="none">
                <path d="M0,110 C240,50 480,170 720,110 C960,50 1200,170 1440,110 C1680,50 1920,170 2160,110 C2400,50 2640,170 2880,110 L2880,200 L0,200 Z"/>
            </svg>
            <svg class="wv-wave wv-wave--2" viewBox="0 0 2880 200" preserveAspectRatio="none">
                <path d="M0,130 C300,70 540,190 780,130 C1020,70 1260,190 1500,130 C1740,70 1980,190 2220,130 C2460,70 2700,190 2880,130 L2880,200 L0,200 Z"/>
            </svg>
            <svg class="wv-wave wv-wave--1" viewBox="0 0 2880 200" preserveAspectRatio="none">
                <path d="M0,150 C360,100 600,205 900,150 C1200,95 1440,205 1740,150 C2040,95 2280,205 2580,150 C2700,128 2790,140 2880,150 L2880,200 L0,200 Z"/>
            </svg>
        </div>

        <section class="wv-card" role="group" aria-labelledby="jump-title">
            <div class="wv-head">
                <?php if (!empty($showLogo)): ?>
                <span class="wv-logo" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </span>
                <?php endif; ?>
                <span class="wv-chip">安全跳转</span>
            </div>

            <h1 id="jump-title" class="wv-title">即将离开本站</h1>
            <p class="wv-host"><?php echo htmlspecialchars($wvHost, ENT_QUOTES); ?></p>
            <p class="wv-desc">你正在离开 <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?>，即将前往外部站点。请确认链接可信后再继续。</p>

            <div class="wv-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></div>

            <div class="wv-actions">
                <a id="continueBtn" class="wv-btn wv-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                <a class="wv-btn wv-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            </div>

            <p class="wv-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
        </section>
    </main>
</body>
</html>
