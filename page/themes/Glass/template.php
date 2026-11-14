<?php
// Glass 风格（iOS / macOS HIG）：毛玻璃卡片 + 系统字体 + 蓝色主按钮
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Glass';
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#F2F2F7" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="screen">
        <section class="sheet" role="group" aria-labelledby="jump-title">
            <div class="sheet__top">
                <?php if (!empty($showLogo)): ?>
                <div class="app-icon" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </div>
                <?php endif; ?>
                <h1 id="jump-title" class="sheet__title"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></h1>
                <p class="sheet__sub">您即将离开本站</p>
            </div>

            <div class="group">
                <div class="group__label">目标链接</div>
                <div class="group__row">
                    <span class="link-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.07 0l1.66-1.66a5 5 0 0 0-7.07-7.07L10.5 5.43"/><path d="M14 11a5 5 0 0 0-7.07 0L5.27 12.66a5 5 0 0 0 7.07 7.07l1.16-1.16"/></svg>
                    </span>
                    <span class="group__value" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></span>
                </div>
                <p class="group__note">该网页不属于本站页面。我们无法确认该网页是否安全，请注意保护个人信息。</p>
            </div>

            <div class="actions">
                <a class="btn" id="continueBtn" href="<?php echo htmlspecialchars(isset($ctaHref) ? $ctaHref : $url, ENT_QUOTES); ?>" rel="nofollow noopener">
                    继续访问
                </a>
                <a class="btn btn--secondary" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            </div>
        </section>
        <p class="copyright">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
    </main>
</body>
</html>
