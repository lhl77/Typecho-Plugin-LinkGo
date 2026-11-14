<?php
// Neon 主题：霓虹灯招牌风
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Neon';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$neHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($neHost === '') {
    $neHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#050507">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#0A0A0F', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="ne">
        <section class="ne-sign" role="group" aria-labelledby="jump-title">
            <div class="ne-top">
                <span class="ne-open"><span class="ne-dot" aria-hidden="true"></span>OPEN&nbsp;LINK</span>
                <span class="ne-no">NO. <?php echo strtoupper(substr(sha1($displayUrl), 0, 6)); ?></span>
            </div>

            <h1 id="jump-title" class="ne-title">即将离开</h1>
            <p class="ne-host"><?php echo htmlspecialchars($neHost, ENT_QUOTES); ?></p>

            <p class="ne-desc">你正在离开 <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?>，即将前往外部站点。请确认地址可信后继续。</p>

            <p class="ne-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></p>

            <div class="ne-actions">
                <a id="continueBtn" class="ne-btn ne-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                <a class="ne-btn ne-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            </div>

            <div class="ne-bottom">
                <span>LINKGO</span>
                <span>&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
            </div>
        </section>
    </main>
</body>
</html>
