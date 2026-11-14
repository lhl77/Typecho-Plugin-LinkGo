<?php
// 安全性检查（预览请求允许更长 URL：实时预览会把表单值带在查询串里）
$lgRequestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
$lgIsPreviewRequest = isset($_GET['linkgo_preview']) && is_string($_GET['linkgo_preview']);
$lgUriLimit = $lgIsPreviewRequest ? 4096 : 255;
if (strlen($lgRequestUri) > $lgUriLimit ||
    strpos($lgRequestUri, "eval(") !== false ||
    strpos($lgRequestUri, "base64") !== false) {
    @header("HTTP/1.1 414 Request-URI Too Long");
    @header("Status: 414 Request-URI Too Long");
    @header("Connection: Close");
    exit;
}

// 获取 Base64 编码的目标链接：优先从路径 /go/<encoded> 提取（URL-safe base64），否则回退到 ?target=
// 如果是从 Action include 的情况下，Action 可能已经设置了 $encodedTarget，避免覆盖它
if (!isset($encodedTarget)) {
    $encodedTarget = '';
}

// 1) 尝试从 PATH_INFO / REQUEST_URI 中提取形如 /go/<encoded>
if (!empty($_SERVER['PATH_INFO'])) {
    $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    if (isset($parts[0]) && $parts[0] === 'go' && isset($parts[1])) {
        $encodedTarget = $parts[1];
    }
}

// 另一个常见位置：REQUEST_URI 或 REDIRECT_URL（例如 Nginx/重写）
if (empty($encodedTarget) && !empty($_SERVER['REQUEST_URI'])) {
    // 匹配 /go/xxxx 或 /go/xxxx?...
    if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REQUEST_URI'], $m)) {
        $encodedTarget = $m[1];
    }
}

if (empty($encodedTarget) && !empty($_SERVER['REDIRECT_URL'])) {
    if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REDIRECT_URL'], $m)) {
        $encodedTarget = $m[1];
    }
}

// 2) 回退到旧的 query 参数
if (empty($encodedTarget)) {
    $rawTarget = isset($_GET['target']) ? $_GET['target'] : '';
    $encodedTarget = is_string($rawTarget) ? $rawTarget : '';

    // 额外尝试：有些托管/重写规则会导致 PHP 的 $_GET 为空，但 QUERY_STRING 或 REQUEST_URI 中仍包含 target
    if (empty($encodedTarget) && !empty($_SERVER['QUERY_STRING'])) {
        if (preg_match('/(?:^|&)target=([^&]+)/', $_SERVER['QUERY_STRING'], $qm)) {
            $encodedTarget = urldecode($qm[1]);
        }
    }

    if (empty($encodedTarget) && !empty($_SERVER['REQUEST_URI'])) {
        if (preg_match('/[?&]target=([^&]+)/', $_SERVER['REQUEST_URI'], $rm)) {
            $encodedTarget = urldecode($rm[1]);
        }
    }
}

// 预览模式不需要解析目标链接（URL 中的 preview 只是路由占位）
if (!empty($previewMode)) {
    $encodedTarget = '';
}

// 验证 encodedTarget 是否看起来像是 URL-safe base64（只包含 A-Za-z0-9-_ 且有合理长度）
if (!empty($encodedTarget) && !preg_match('/^[A-Za-z0-9\-_]{8,}$/', $encodedTarget)) {
    if (function_exists('error_log')) {
        error_log('[LinkGo:index] invalid encodedTarget captured and ignored: ' . $encodedTarget);
    }
    $encodedTarget = '';
}

// 解码目标链接：支持 URL-safe base64（-_, 无 =）与标准 base64
$target = '';
if (!empty($encodedTarget)) {
    // 把 URL-safe base64 恢复为标准 base64
    $b64 = strtr($encodedTarget, '-_', '+/');
    // 补回被移除的 '='
    $pad = strlen($b64) % 4;
    if ($pad > 0) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $target = base64_decode($b64);
}

// 站点根地址：优先使用 Action 传入的 $siteUrl，其次读 Typecho 设置，最后回退到当前 Host
$siteUrl = isset($siteUrl) && $siteUrl !== '' ? rtrim($siteUrl, '/') : '';
if ($siteUrl === '') {
    try {
        $siteUrl = rtrim((string)Typecho_Widget::widget('Widget_Options')->siteUrl, '/');
    } catch (Exception $e) {
        $siteUrl = '';
    }
}
if ($siteUrl === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
    $siteUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

// 处理目标链接
if (!empty($previewMode)) {
    // 预览模式：目标链接由 Action 提供（示例地址），跳过 target 解析
    if (empty($url)) {
        $url = 'https://github.com/lhl77/Typecho-Plugin-LinkGo';
    }
    if (empty($title)) {
        $title = '跳转页主题预览';
    }
} elseif (!empty($target) && filter_var($target, FILTER_VALIDATE_URL)) {
    $parsed_url = parse_url($target);
    if (isset($parsed_url['scheme']) && in_array($parsed_url['scheme'], ['http', 'https'])) {
        $url = $target;
        $title = '安全跳转';
    } else {
        $url = $siteUrl;
        $title = '非法 URL，正在返回首页...';
    }
} else {
    $title = '非法 URL，正在返回首页...';
    $url = $siteUrl;
}

// 调试信息：记录请求相关字段，便于排查 target 是否被传递（define('LINKGO_DEBUG', true) 开启）
if (defined('LINKGO_DEBUG') && LINKGO_DEBUG && function_exists('error_log')) {
    error_log('[LinkGo:index] debug: GET=' . json_encode($_GET, JSON_UNESCAPED_UNICODE) . ' QUERY_STRING=' . ($_SERVER['QUERY_STRING'] ?? '') . ' REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '') . ' encoded=' . ($encodedTarget ?? ''));
}

// 提取目标域名用于显示
$parsed_url = parse_url($url);
$display_url = $parsed_url['host'] ?? $url;

// 动态内容：优先使用外部（例如 Action.php）传入的变量；若不存在则尝试读取 Typecho 插件设置；最后使用默认值
// 默认值
$defaultTitle = 'LHL\'s Blog';
$defaultLogo = 'https://cdn.sa.net/2025/04/18/KXpf8u5SQYNPkA3.jpg';
$defaultStartYear = 2021;

// 如果 Action 已经设置了这些变量（例如 LinkGo_Action 会在 include 前设置），优先保留
$siteTitle = (isset($siteTitle) && !empty($siteTitle)) ? $siteTitle : $defaultTitle;
$logoUrl = (isset($logoUrl) && !empty($logoUrl)) ? $logoUrl : $defaultLogo;
$startYear = (isset($startYear) && !empty($startYear)) ? (int)$startYear : $defaultStartYear;

// 尝试从 Typecho 插件配置中读取（仅当模板没有被外部覆盖时，会替换默认值）
if (class_exists('Typecho_Widget')) {
    try {
        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
        if ((!isset($siteTitle) || $siteTitle === $defaultTitle) && !empty($pluginOptions->siteTitle)) {
            $siteTitle = $pluginOptions->siteTitle;
        }
        if ((!isset($logoUrl) || $logoUrl === $defaultLogo) && !empty($pluginOptions->logoUrl)) {
            $logoUrl = $pluginOptions->logoUrl;
        }
        if ((!isset($startYear) || $startYear === $defaultStartYear) && !empty($pluginOptions->startYear)) {
            $startYear = (int)$pluginOptions->startYear;
        }
    } catch (Exception $e) {
        // 忽略无法读取配置的情况（例如在不在 Typecho 环境中直接打开模板时）
    }
}

$currentYear = date('Y'); // 当前年份
$displayYear = ($currentYear > $startYear) ? ($startYear . ' - ' . $currentYear) : $currentYear;

// 跳转页展示参数（与设置页默认值保持一致）
if (!isset($showFullUrl)) {
    $showFullUrl = true;
}
if (!isset($showLogo)) {
    $showLogo = true;
}
if (!isset($robotsContent)) {
    $robotsContent = 'noindex, nofollow';
}
$displayUrl = $showFullUrl ? $url : (isset($display_url) && $display_url !== '' ? $display_url : $url);

// 主题预览：继续访问按钮只作展示、不可跳转（地址文本仍显示真实目标）
$ctaHref = !empty($previewMode) ? '#' : $url;

// 主题色（#RRGGBB）；留空使用各主题默认主色
$themeColor = isset($themeColor) ? trim((string)$themeColor) : '';
if ($themeColor !== '') {
    if ($themeColor[0] !== '#') {
        $themeColor = '#' . $themeColor;
    }
    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $themeColor)) {
        if (strlen($themeColor) === 4) {
            $themeColor = '#' . $themeColor[1] . $themeColor[1] . $themeColor[2] . $themeColor[2] . $themeColor[3] . $themeColor[3];
        }
        $themeColor = strtoupper($themeColor);
    } else {
        $themeColor = '';
    }
}
$themeColorOn = '#FFFFFF';
if ($themeColor !== '') {
    $colorR = hexdec(substr($themeColor, 1, 2));
    $colorG = hexdec(substr($themeColor, 3, 2));
    $colorB = hexdec(substr($themeColor, 5, 2));
    $luma = 0.299 * $colorR + 0.587 * $colorG + 0.114 * $colorB;
    $themeColorOn = $luma > 150 ? '#000000' : '#FFFFFF';
}
?>
<?php
// 主题名：优先使用由 Action 传入的 $themeName，其次从插件配置读取，最后回退为 'Default'
$themeName = isset($themeName) && !empty($themeName) ? basename(str_replace('\\', '/', (string)$themeName)) : 'Default';
if ($themeName === 'Apple' || $themeName === 'Default2') {
    $themeName = 'Glass'; // 兼容旧主题名
}
try {
    if (empty($previewMode) && class_exists('Typecho_Widget')) {
        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
        if ((!isset($themeName) || $themeName === 'Default') && !empty($pluginOptions->themeName)) {
            $themeName = basename(str_replace('\\', '/', (string)$pluginOptions->themeName));
        }
    }
} catch (Exception $e) {
    // ignore
}
if ($themeName === 'Apple' || $themeName === 'Default2') {
    $themeName = 'Glass'; // 兼容旧配置中的主题名
}

// 默认 Logo：内联 SVG，背景色跟随当前主题强调色（自定义 Logo 不受影响）
$lgAccentMap = is_file(__DIR__ . '/theme-accents.php') ? include __DIR__ . '/theme-accents.php' : array();
$lgAccent = ($themeColor !== '') ? $themeColor : (isset($lgAccentMap[$themeName]) ? $lgAccentMap[$themeName] : '#6750A4');
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $lgAccent)) {
    $lgAccent = '#6750A4';
}
$lgAccentLuma = 0.299 * hexdec(substr($lgAccent, 1, 2)) + 0.587 * hexdec(substr($lgAccent, 3, 2)) + 0.114 * hexdec(substr($lgAccent, 5, 2));
$lgAccentOn = $lgAccentLuma > 150 ? '#1D1B20' : '#FFFFFF';
$lgLogoPath = (string)parse_url((string)$logoUrl, PHP_URL_PATH);
if ($logoUrl === '' || $logoUrl === $defaultLogo || preg_match('#(^|/)logo\.svg$#', $lgLogoPath)) {
    $lgLogoSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" role="img" aria-label="LinkGo">'
        . '<rect width="64" height="64" rx="18" fill="' . $lgAccent . '"/>'
        . '<g fill="none" stroke="' . $lgAccentOn . '" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M27 37l10-10"/>'
        . '<path d="M21.5 33.5l-2.7 2.7a7.6 7.6 0 0 0 10.8 10.8l6-6a7.6 7.6 0 0 0 0-10.8"/>'
        . '<path d="M42.5 30.5l2.7-2.7a7.6 7.6 0 0 0-10.8-10.8l-6 6"/>'
        . '</g></svg>';
    $logoUrl = 'data:image/svg+xml;charset=utf-8,' . rawurlencode($lgLogoSvg);
}

// 包含主题模板（先缓冲，统一注入 LinkGo 署名与版本信息）
if (!defined('LINKGO_VERSION')) {
    define('LINKGO_VERSION', (class_exists('LinkGo_Plugin') && defined('LinkGo_Plugin::VERSION'))
        ? LinkGo_Plugin::VERSION
        : '1.2.0');
}

$themeTemplate = __DIR__ . '/themes/' . $themeName . '/template.php';

ob_start();
if (file_exists($themeTemplate)) {
    include $themeTemplate;
} else {
    // 兜底：如果主题不存在，回退到 Default 模板（已存在）
    $fallback = __DIR__ . '/themes/Default/template.php';
    if (file_exists($fallback)) {
        include $fallback;
    } else {
        echo '<p>跳转页面模板未找到。</p>';
    }
}
$lgHtml = ob_get_clean();

// 角落署名：不抢视觉，鼠标悬停才明显
$lgCredit = '<a class="linkgo-credit" href="https://github.com/lhl77/Typecho-Plugin-LinkGo"'
    . ' target="_blank" rel="noopener noreferrer"'
    . ' aria-label="LinkGo v' . htmlspecialchars(LINKGO_VERSION, ENT_QUOTES) . ' · Typecho 跳转中间页插件"'
    . ' style="position:fixed;right:10px;bottom:10px;z-index:2147483000;display:inline-flex;align-items:center;'
    . ' gap:4px;padding:4px 9px;border-radius:999px;font-family:-apple-system,BlinkMacSystemFont,'
    . "'Segoe UI',sans-serif;font-size:10.5px;font-weight:500;line-height:1;letter-spacing:.03em;"
    . ' color:rgba(127,127,127,.95);background:rgba(127,127,127,.13);text-decoration:none;opacity:.5;'
    . ' transition:opacity .18s ease,background .18s ease"'
    . ' onmouseover="this.style.opacity=\'1\';this.style.background=\'rgba(127,127,127,.24)\'"'
    . ' onmouseout="this.style.opacity=\'.5\';this.style.background=\'rgba(127,127,127,.13)\'"'
    . '>LinkGo</a>';

// 控制台输出版本信息
$lgConsole = '<script>(function(){try{'
    . 'var v=' . json_encode(LINKGO_VERSION) . ',t=' . json_encode($themeName) . ',p=' . (!empty($previewMode) ? 'true' : 'false') . ';'
    . 'if(!window.console||!console.log){return;}'
    . 'console.log("%cLinkGo%c v"+v+" ","background:#6750A4;color:#fff;padding:2px 6px;border-radius:4px 0 0 4px;font-weight:700",'
    . '"background:#2B2930;color:#fff;padding:2px 6px;border-radius:0 4px 4px 0",'
    . '"\\nTypecho 跳转中间页插件 · 主题："+t+(p?"（预览模式）":"")'
    . '+"\\nhttps://github.com/lhl77/Typecho-Plugin-LinkGo");'
    . 'console.log("%c docs ","background:#EADDFF;color:#21005D;border-radius:4px"," https://github.com/lhl77/Typecho-Plugin-LinkGo#readme");'
    . '}catch(e){}})();</script>';

if (stripos($lgHtml, '</body>') !== false) {
    $lgHtml = preg_replace('#</body>#i', $lgCredit . $lgConsole . '</body>', $lgHtml, 1);
} else {
    $lgHtml .= $lgCredit . $lgConsole;
}
echo $lgHtml;
