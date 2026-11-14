<?php
// Blueprint 主题：工程图纸风
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Blueprint';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$bpHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($bpHost === '') {
    $bpHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#071726">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#071726', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="bp">
        <section class="bp-sheet" role="group" aria-labelledby="jump-title">
            <span class="bp-corner bp-corner--tl" aria-hidden="true"></span>
            <span class="bp-corner bp-corner--tr" aria-hidden="true"></span>
            <span class="bp-corner bp-corner--bl" aria-hidden="true"></span>
            <span class="bp-corner bp-corner--br" aria-hidden="true"></span>

            <header class="bp-head">
                <span class="bp-tag">LINKGO / REDIRECT SHEET</span>
                <span class="bp-tag bp-tag--dim">SCALE 1:1 · SHEET 01/01</span>
            </header>

            <div class="bp-body">
                <div class="bp-left">
                    <span class="bp-k">DESTINATION / 目标主机</span>
                    <strong class="bp-host"><?php echo htmlspecialchars($bpHost, ENT_QUOTES); ?></strong>

                    <span class="bp-k">SOURCE / 来源站点</span>
                    <span class="bp-source"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>

                    <span class="bp-stamp" aria-hidden="true">VERIFIED<br>EXTERNAL</span>
                </div>

                <div class="bp-right">
                    <h1 id="jump-title" class="bp-title">即将离开本站</h1>
                    <p class="bp-desc">你正在前往外部站点，详见下方标注的目标地址。该站点不受本站控制，请确认可信后再继续。</p>

                    <div class="bp-urlbox">
                        <span class="bp-urlbox__label">TARGET URL</span>
                        <span class="bp-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></span>
                    </div>

                    <div class="bp-actions">
                        <a id="continueBtn" class="bp-btn bp-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                        <a class="bp-btn bp-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
                    </div>
                </div>
            </div>

            <footer class="bp-foot">
                <span>REF · LINKGO-GO</span>
                <span>DATE · <?php echo date('Y-m-d'); ?></span>
                <span>&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
            </footer>
        </section>
    </main>
</body>
</html>
