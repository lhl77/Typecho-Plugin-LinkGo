<?php
// Bento 主题：不对称卡片网格布局
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Bento';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$bnHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($bnHost === '') {
    $bnHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#F4F4F6" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0B0B0F" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 14%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="bn">
        <div class="bn-grid" role="group" aria-labelledby="jump-title">
            <section class="bn-card bn-card--brand">
                <?php if (!empty($showLogo)): ?>
                <span class="bn-logo" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </span>
                <?php endif; ?>
                <span class="bn-site"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
                <span class="bn-chip">安全跳转</span>
            </section>

            <section class="bn-card bn-card--host">
                <span class="bn-k">外部链接</span>
                <strong class="bn-host"><?php echo htmlspecialchars($bnHost, ENT_QUOTES); ?></strong>
                <span class="bn-hint">即将离开本站前往该站点</span>
            </section>

            <section class="bn-card bn-card--note">
                <span class="bn-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                </span>
                <p class="bn-note">目标站点不属于本站，内容与隐私策略由第三方负责。</p>
            </section>

            <section class="bn-card bn-card--url">
                <span class="bn-k">目标地址</span>
                <h1 id="jump-title" class="bn-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></h1>
            </section>

            <section class="bn-card bn-card--actions">
                <a id="continueBtn" class="bn-btn bn-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                <a class="bn-btn bn-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            </section>

            <section class="bn-card bn-card--meta">
                <span class="bn-k">© <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?></span>
                <span class="bn-site bn-site--sm"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
            </section>
        </div>
    </main>
</body>
</html>
