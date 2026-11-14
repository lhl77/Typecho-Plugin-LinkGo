<?php
// Retro95 主题：Windows 95 风格的复古系统窗口
$showFullUrl = !isset($showFullUrl) ? true : (bool)$showFullUrl;
$showLogo = !isset($showLogo) ? true : (bool)$showLogo;
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';
$displayUrl = isset($displayUrl) && $displayUrl !== '' ? $displayUrl : (!empty($showFullUrl) ? $url : (isset($display_url) ? $display_url : $url));
$siteTitle = isset($siteTitle) ? $siteTitle : '';
$displayYear = isset($displayYear) ? $displayYear : date('Y');
$themeName = isset($themeName) ? $themeName : 'Retro95';
$ctaHref = isset($ctaHref) ? $ctaHref : $url;
$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#008080">
    <title><?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?> - <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>;--lg-accent-soft:color-mix(in srgb,<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?> 16%,transparent)}</style>
    <?php endif; ?>
</head>
<body>
    <main class="r95">
        <section class="r95-win" role="group" aria-labelledby="jump-title">
            <div class="r95-title">
                <?php if (!empty($showLogo)): ?>
                <span class="r95-title__icon" aria-hidden="true">
                    <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES); ?>" alt="" onerror="this.style.display='none'">
                </span>
                <?php endif; ?>
                <span class="r95-title__text">即将离开 — <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
                <span class="r95-title__btn" aria-hidden="true">×</span>
            </div>

            <div class="r95-menu">
                <span><u>文</u>件</span><span><u>编</u>辑</span><span>查<u>看</u></span><span><u>帮</u>助</span>
            </div>

            <div class="r95-body">
                <div class="r95-panel">
                    <p class="r95-warn"><b id="jump-title">注意</b></p>
                    <p class="r95-text">您正在离开 <b><?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></b>，即将前往一个外部站点。该站点不受本站控制，请确认地址可信后继续。</p>
                    <div class="r95-field">
                        <span class="r95-field__label">目标地址:</span>
                        <span class="r95-field__value" title="<?php echo htmlspecialchars($url, ENT_QUOTES); ?>"><?php echo htmlspecialchars($displayUrl, ENT_QUOTES); ?></span>
                    </div>
                </div>

                <div class="r95-actions">
                    <a id="continueBtn" class="r95-btn r95-btn--default" href="<?php echo htmlspecialchars($ctaHref, ENT_QUOTES); ?>" rel="nofollow noopener">继续访问</a>
                    <a class="r95-btn" href="<?php echo htmlspecialchars(rtrim($siteUrl, '/'), ENT_QUOTES); ?>">取消</a>
                </div>
            </div>

            <div class="r95-status">
                <span class="r95-status__cell">&copy; <?php echo htmlspecialchars($displayYear, ENT_QUOTES); ?> <?php echo htmlspecialchars($siteTitle, ENT_QUOTES); ?></span>
                <span class="r95-status__cell r95-status__cell--right">LinkGo</span>
            </div>
        </section>
    </main>
</body>
</html>
