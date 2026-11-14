<?php
// Flip 主题：机场翻牌（split-flap）信息板
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Flip';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$flHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($flHost === '') {
    $flHost = $displayUrl;
}
$flText = strtoupper($flHost);
if (function_exists('mb_substr')) {
    $flText = mb_substr($flText, 0, 16, 'UTF-8');
} else {
    $flText = substr($flText, 0, 16);
}
$flChars = preg_split('//u', $flText, -1, PREG_SPLIT_NO_EMPTY);
if (!is_array($flChars)) {
    $flChars = str_split($flText);
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#0B0B0B">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#0B0B0B', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="fl">
        <section class="fl-board" role="group" aria-labelledby="jump-title">
            <header class="fl-head">
                <span class="fl-brand">
                    <?php if (!empty($showLogo)): ?>
                    <img class="fl-brand__logo" src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="" onerror="this.style.display='none'">
                    <?php endif; ?>
                    DEPARTURES · 出发
                </span>
                <span class="fl-live"><i class="fl-live__dot"></i>LIVE</span>
            </header>

            <div class="fl-row">
                <span class="fl-key">TO</span>
                <div class="fl-tiles" role="text" aria-label="<?php echo htmlspecialchars($flText, ENT_QUOTES); ?>">
                    <?php foreach ($flChars as $flIndex => $flChar): ?>
                    <span class="fl-tile" style="animation-delay:<?php echo (int)$flIndex * 45; ?>ms"><?php echo htmlspecialchars($flChar, ENT_QUOTES); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="fl-row fl-row--plain">
                <span class="fl-key">FROM</span>
                <span class="fl-value" id="jump-title"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
            </div>

            <div class="fl-row fl-row--plain">
                <span class="fl-key">STATUS</span>
                <span class="fl-value fl-value--warn">即将离开本站 · 请确认链接可信后继续</span>
            </div>

            <div class="fl-row fl-row--plain">
                <span class="fl-key">GATE</span>
                <span class="fl-value">LG-01 · 外部链接</span>
            </div>

            <p class="fl-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></p>

            <div class="fl-actions">
                <a id="continueBtn" class="fl-btn fl-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续前往</a>
                <a class="fl-btn" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">留在本站</a>
            </div>

            <p class="fl-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
        </section>
    </main>
</body>
</html>
