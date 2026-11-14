<?php
// Vinyl 主题：黑胶唱片 + 唱机
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Vinyl';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$vlHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($vlHost === '') {
    $vlHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#14100F">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#1A1210', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="vl" role="group" aria-labelledby="jump-title">
        <div class="vl-deck" aria-hidden="true">
            <div class="vl-disc">
                <div class="vl-disc__sheen"></div>
                <div class="vl-label">
                    <?php if (!empty($showLogo)): ?>
                    <img class="vl-label__img" src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="" onerror="this.style.display='none'">
                    <?php endif; ?>
                    <span class="vl-label__hole"></span>
                </div>
            </div>
            <div class="vl-arm">
                <span class="vl-arm__pivot"></span>
                <span class="vl-arm__bar"></span>
                <span class="vl-arm__head"></span>
            </div>
        </div>

        <section class="vl-info">
            <p class="vl-kicker"><span class="vl-dot"></span>NOW LEAVING · 外部链接</p>
            <h1 id="jump-title" class="vl-title"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></h1>
            <p class="vl-host"><?php echo htmlspecialchars($vlHost, ENT_QUOTES); ?></p>

            <p class="vl-desc">这张唱片不在本站的曲库中。即将跳转到第三方站点，请确认链接可信后再继续播放。</p>

            <div class="vl-track" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>">
                <span class="vl-track__no">A1</span>
                <span class="vl-track__url"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></span>
            </div>

            <div class="vl-actions">
                <a id="continueBtn" class="vl-btn vl-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                    继续播放
                </a>
                <a class="vl-btn vl-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">停在 A 面</a>
            </div>

            <p class="vl-foot">SIDE A · &copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
        </section>
    </main>
</body>
</html>
