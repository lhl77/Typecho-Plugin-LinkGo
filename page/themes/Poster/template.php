<?php
// Poster 主题：杂志海报式编辑排版
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Poster';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$psHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($psHost === '') {
    $psHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0E0E0E" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="ps">
        <span class="ps-spine" aria-hidden="true">LINKGO · 外部链接</span>

        <header class="ps-top">
            <span class="ps-meta">NOTICE / EXTERNAL LINK</span>
            <span class="ps-meta"><?php echo date('Y.m.d'); ?></span>
        </header>

        <h1 id="jump-title" class="ps-h1">即将离开<br>本站</h1>

        <div class="ps-hostline">
            <span class="ps-bar" aria-hidden="true"></span>
            <strong class="ps-host"><?php echo htmlspecialchars($psHost, ENT_QUOTES); ?></strong>
        </div>

        <p class="ps-lede">
            你正在离开 <b><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></b>。目标站点由第三方提供，
            其内容与隐私策略不受本站控制，请确认地址可信后再继续。
        </p>

        <p class="ps-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></p>

        <footer class="ps-actions">
            <a id="continueBtn" class="ps-link" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">
                <span class="ps-link__text">继续访问</span>
                <span class="ps-link__arrow" aria-hidden="true">→</span>
            </a>
            <a class="ps-back" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            <span class="ps-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
        </footer>
    </main>
</body>
</html>
