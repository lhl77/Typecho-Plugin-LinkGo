<?php
// Ticket 主题：登机牌 / 车票样式的跳转提示
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Ticket';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$tkHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($tkHost === '') {
    $tkHost = $displayUrl;
}
$tkCode = strtoupper(substr(sha1($displayUrl . $displayYear), 0, 10));
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#F4F1EC" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#14120F" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="tk-wrap">
        <article class="tk-ticket" role="group" aria-labelledby="jump-title">
            <div class="tk-main">
                <div class="tk-head">
                    <span class="tk-brand">BOARDING&nbsp;PASS</span>
                    <span class="tk-chip">安全跳转</span>
                </div>

                <div class="tk-route">
                    <div class="tk-point">
                        <span class="tk-k">FROM</span>
                        <strong class="tk-v"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></strong>
                    </div>
                    <span class="tk-arrow" aria-hidden="true">→</span>
                    <div class="tk-point tk-point--to">
                        <span class="tk-k">TO</span>
                        <strong class="tk-v tk-v--lg"><?php echo htmlspecialchars($tkHost, ENT_QUOTES); ?></strong>
                    </div>
                </div>

                <h1 id="jump-title" class="tk-title">即将离开本站</h1>
                <p class="tk-desc">你正在离开 <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?>，即将前往外部站点，请确认链接可信后继续。</p>

                <div class="tk-meta">
                    <div class="tk-meta__item"><span class="tk-k">GATE</span><b>LG-01</b></div>
                    <div class="tk-meta__item"><span class="tk-k">SEAT</span><b>01A</b></div>
                    <div class="tk-meta__item"><span class="tk-k">DATE</span><b><?php echo date('Y-m-d'); ?></b></div>
                </div>

                <div class="tk-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></div>

                <div class="tk-actions">
                    <a id="continueBtn" class="tk-btn tk-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                    <a class="tk-btn" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
                </div>
            </div>

            <aside class="tk-stub" aria-hidden="true">
                <span class="tk-stub__label">LINKGO</span>
                <span class="tk-barcode"></span>
                <span class="tk-stub__code"><?php echo htmlspecialchars($tkCode, ENT_QUOTES); ?></span>
            </aside>
        </article>
        <p class="tk-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
    </main>
</body>
</html>
