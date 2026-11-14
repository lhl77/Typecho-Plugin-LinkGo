<?php
// Terminal 主题：仿终端窗口的跳转提示
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Terminal';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
$tmHost = (string)parse_url($displayUrl, PHP_URL_HOST);
if ($tmHost === '') {
    $tmHost = $displayUrl;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#0B0E14">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#0B0E14', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="tm-wrap">
        <div class="tm-window">
            <div class="tm-bar">
                <span class="tm-dot tm-dot--r"></span>
                <span class="tm-dot tm-dot--y"></span>
                <span class="tm-dot tm-dot--g"></span>
                <span class="tm-bar__title">linkgo — zsh — 80×24</span>
            </div>
            <div class="tm-body">
                <p class="tm-line"><span class="tm-user"><?php echo htmlspecialchars($siteTitle !== '' ? $siteTitle : 'guest', ENT_QUOTES); ?></span><span class="tm-at">@</span><span class="tm-host">localhost</span><span class="tm-prompt"> $ </span>linkgo open <span class="tm-str">"<?php echo htmlspecialchars($tmHost, ENT_QUOTES); ?>"</span></p>
                <p class="tm-line tm-comment"># 即将离开本站，目标地址：</p>
                <p class="tm-line tm-url" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></p>
                <p class="tm-line tm-comment"># 该站点不属于本站，请确认链接可信后继续</p>
                <p class="tm-line"><span class="tm-prompt">></span> 等待确认<span class="tm-caret"></span></p>
                <div class="tm-actions">
                    <a id="continueBtn" class="tm-key tm-key--primary" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                    <a class="tm-key" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">返回首页</a>
                </div>
            </div>
        </div>
        <p class="tm-foot">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></p>
    </main>
</body>
</html>
