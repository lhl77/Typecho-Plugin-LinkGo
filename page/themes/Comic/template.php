<?php
// Comic 主题：美式漫画分格（粗描边 + 网点 + 对话气泡）
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Comic';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$cmHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($cmHost === '') {
    $cmHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#FFFBEA" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1B1B1B" media="(prefers-color-scheme: dark)">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#111111', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="cm">
        <span class="cm-rays" aria-hidden="true"></span>

        <section class="cm-panel" role="group" aria-labelledby="jump-title">
            <?php if (!empty($showLogo)): ?>
            <span class="cm-logo" aria-hidden="true">
                <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="logo" onerror="this.style.display='none'">
            </span>
            <?php endif; ?>

            <span class="cm-tag">外部链接!!</span>

            <div class="cm-bubble">
                <h1 id="jump-title" class="cm-title">即将离开<br><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?>！</h1>
                <p class="cm-desc">目标站点由第三方提供，内容和隐私都不归本站管——确认可信再按「继续访问」！</p>
            </div>

            <div class="cm-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>">
                <span class="cm-url__label">TO →</span>
                <span class="cm-url__value"><?php echo htmlspecialchars($cmHost, ENT_QUOTES); ?></span>
            </div>

            <div class="cm-actions">
                <a id="continueBtn" class="cm-btn cm-btn--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问 →</a>
                <a class="cm-btn" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
            </div>

            <p class="cm-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
        </section>
    </main>
</body>
</html>
