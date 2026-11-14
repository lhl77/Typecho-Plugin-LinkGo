<?php
// Linear 风格：深色渐变背景 + 玻璃卡片 + 紫蓝强调色
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Linear';
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#08090A">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="stage">
        <div class="glow" aria-hidden="true"></div>
        <section class="card" role="group" aria-labelledby="jump-title">
            <div class="card__top">
                <?php if (!empty($showLogo)): ?>
                <div class="avatar" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
                </div>
                <?php endif; ?>
                <div class="card__meta">
                    <span class="pill">安全跳转</span>
                    <span class="domain"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
                </div>
            </div>

            <h1 id="jump-title" class="card__title">即将前往外部站点</h1>
            <p class="card__desc">该链接将带你离开本站。我们无法验证目标站点的内容与安全性，请确认后再继续。</p>

            <div class="target">
                <span class="target__dot" aria-hidden="true"></span>
                <code class="target__url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></code>
            </div>

            <a id="continueBtn" class="cta" href="<?php echo htmlspecialchars(isset($ctaHref) ? $ctaHref : $url, ENT_QUOTES); ?>" rel="nofollow noopener">
                继续访问
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </a>
            <p class="card__foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
        </section>
    </main>
</body>
</html>
