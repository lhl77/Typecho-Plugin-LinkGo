<?php
// Radar 主题：雷达扫描 + 军事终端
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Radar';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$rdHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($rdHost === '') {
    $rdHost = $displayUrl;
}
$rdScheme = (string)parse_url($displayUrl, PHP_URL_SCHEME);
if ($rdScheme === '') {
    $rdScheme = (string)parse_url($url, PHP_URL_SCHEME);
}
if ($rdScheme === '') {
    $rdScheme = 'https';
}
$rdCode = strtoupper(substr(sha1($displayUrl), 0, 4));
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#05130D">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#04140D', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="rd">
        <div class="rd-scope" aria-hidden="true">
            <span class="rd-ring rd-ring--1"></span>
            <span class="rd-ring rd-ring--2"></span>
            <span class="rd-ring rd-ring--3"></span>
            <span class="rd-cross rd-cross--x"></span>
            <span class="rd-cross rd-cross--y"></span>
            <span class="rd-sweep"></span>
            <span class="rd-blip"></span>
        </div>

        <section class="rd-panel" role="group" aria-labelledby="jump-title">
            <p class="rd-kicker">
                <?php if (!empty($showLogo)): ?>
                <span class="rd-logo"><img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="" onerror="this.style.display='none'"></span>
                <?php endif; ?>
                SIGNAL&nbsp;ACQUIRED · 外部链接 #<?php echo htmlspecialchars($rdCode, ENT_QUOTES); ?>
            </p>

            <h1 id="jump-title" class="rd-title">即将离开本站</h1>
            <p class="rd-host"><?php echo htmlspecialchars($rdHost, ENT_QUOTES); ?></p>

            <dl class="rd-meta">
                <div><dt>TARGET</dt><dd><?php echo htmlspecialchars($rdHost, ENT_QUOTES); ?></dd></div>
                <div><dt>PROTOCOL</dt><dd><?php echo htmlspecialchars(strtoupper($rdScheme), ENT_QUOTES); ?></dd></div>
                <div><dt>SOURCE</dt><dd><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></dd></div>
            </dl>

            <p class="rd-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><span class="rd-url__prompt">&gt;</span> <?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></p>

            <div class="rd-actions">
                <a id="continueBtn" class="rd-btn rd-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                <a class="rd-btn" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回本舰</a>
            </div>

            <p class="rd-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
        </section>
    </main>
</body>
</html>
