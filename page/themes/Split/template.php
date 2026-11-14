<?php
// Split 主题：左右分屏布局
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Split';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$splHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($splHost === '') {
    $splHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0F1115" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="spl">
        <section class="spl-left">
            <span class="spl-blob" aria-hidden="true"></span>
            <div class="spl-brand">
                <?php if (!empty($showLogo)): ?>
                <span class="spl-logo" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </span>
                <?php endif; ?>
                <span class="spl-site"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
            </div>
            <h2 class="spl-headline">即将前往<br><span class="spl-host"><?php echo htmlspecialchars($splHost, ENT_QUOTES); ?></span></h2>
            <p class="spl-copy">本站不再承载这个链接的内容，跳转后你将离开 <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?>。</p>
            <p class="spl-year">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?></p>
        </section>

        <section class="spl-right" role="group" aria-labelledby="jump-title">
            <span class="spl-eyebrow">外部链接 · EXTERNAL</span>
            <h1 id="jump-title" class="spl-title">确认要离开本站吗？</h1>
            <p class="spl-desc">目标站点由第三方提供，其内容与隐私策略不受本站控制，请确认地址可信后再继续。</p>

            <div class="spl-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></div>

            <div class="spl-actions">
                <a id="continueBtn" class="spl-btn spl-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                <a class="spl-btn spl-btn--ghost" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">留在本站</a>
            </div>

            <p class="spl-note">提示：不要在陌生页面输入账号密码。</p>
        </section>
    </main>
</body>
</html>
