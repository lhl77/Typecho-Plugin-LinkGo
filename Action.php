<?php
/**
 * LinkGo 路由 Action
 * 负责处理 /go 路径并渲染跳转页面
 */
use Typecho\Widget\Helper as TypechoHelper;

class LinkGo_Action extends Typecho_Widget
{
    public function index()
    {
        // 把 page/index.php 的逻辑内联到这里，避免重复文件依赖
        // 安全性检查（预览请求允许更长 URL：实时预览会把表单值带在查询串里）
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
        $isPreviewRequest = isset($_GET['linkgo_preview']) && is_string($_GET['linkgo_preview']);
        $uriLimit = $isPreviewRequest ? 4096 : 255;
        if (strlen($requestUri) > $uriLimit ||
            strpos($requestUri, "eval(") !== false ||
            strpos($requestUri, "base64") !== false) {
            @header("HTTP/1.1 414 Request-URI Too Long");
            @header("Status: 414 Request-URI Too Long");
            @header("Connection: Close");
            exit;
        }

        // 站点根地址：优先使用 Typecho 配置的 siteUrl，避免直接信任 Host 头
        $siteBase = self::getSiteBaseUrl();

        // 缓存：跳转页 / 预览页都是动态中间页，禁止 CDN 与浏览器缓存 HTML，
        // 否则部署后仍引用旧版 style.css（?v= 版本号来自缓存中的旧 HTML）导致样式丢失。
        if (!headers_sent()) {
            @header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            @header('Pragma: no-cache');
            @header('Expires: 0');
        }

        // SEO：可选的 X-Robots-Tag 响应头（跳转中间页不被收录）
        if (self::sendNoindexHeader() && !headers_sent()) {
            @header('X-Robots-Tag: noindex, nofollow');
        }

        // 主题预览模式：/go/preview?linkgo_preview=<ThemeName>
        $previewTheme = isset($_GET['linkgo_preview'])
            ? preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$_GET['linkgo_preview'])
            : '';
        if ($previewTheme === 'Apple' || $previewTheme === 'Default2') {
            $previewTheme = 'Glass'; // 兼容旧主题名
        }
        if ($previewTheme !== '' && is_file(__DIR__ . '/page/themes/' . $previewTheme . '/template.php')) {
            $previewMode = true;
            $themeName = $previewTheme;
            $url = 'https://github.com/lhl77/Typecho-Plugin-LinkGo';
            $title = '跳转页主题预览';
            $display_url = 'github.com';
            $siteUrl = $siteBase;

            $display = self::getDisplaySettings();

            // 设置页实时预览：用表单当前值覆盖已保存配置
            if (isset($_GET['lg_siteTitle']) && is_string($_GET['lg_siteTitle'])) {
                $previewSiteTitle = trim(strip_tags((string)$_GET['lg_siteTitle']));
                $previewTitleLen = function_exists('mb_strlen') ? mb_strlen($previewSiteTitle) : strlen($previewSiteTitle);
                if ($previewSiteTitle !== '' && $previewTitleLen <= 120) {
                    $display['siteTitle'] = $previewSiteTitle;
                }
            }
            if (isset($_GET['lg_logoUrl']) && is_string($_GET['lg_logoUrl'])) {
                $previewLogo = trim((string)$_GET['lg_logoUrl']);
                if ($previewLogo !== '' && filter_var($previewLogo, FILTER_VALIDATE_URL)) {
                    $display['logoUrl'] = $previewLogo;
                }
            }
            if (isset($_GET['lg_startYear']) && is_string($_GET['lg_startYear'])) {
                $previewYear = trim((string)$_GET['lg_startYear']);
                if (ctype_digit($previewYear) && strlen($previewYear) === 4) {
                    $display['startYear'] = (int)$previewYear;
                    $currentYear = (int)date('Y');
                    $display['displayYear'] = ($currentYear > (int)$previewYear) ? ($previewYear . ' - ' . $currentYear) : (string)$currentYear;
                }
            }
            foreach (array('showFullUrl', 'showLogo') as $previewToggle) {
                if (isset($_GET['lg_' . $previewToggle])) {
                    $display[$previewToggle] = (string)$_GET['lg_' . $previewToggle] !== '0';
                }
            }
            if (isset($_GET['lg_robotsPolicy']) && is_string($_GET['lg_robotsPolicy'])) {
                $previewRobots = (string)$_GET['lg_robotsPolicy'];
                if (in_array($previewRobots, array('noindex, nofollow', 'noindex, follow', 'noindex', 'none'), true)) {
                    $display['robotsPolicy'] = $previewRobots;
                }
            }
            if (isset($_GET['lg_themeColor']) && is_string($_GET['lg_themeColor'])) {
                $display['themeColor'] = self::normalizeThemeColor($_GET['lg_themeColor']);
            }

            $siteTitle = $display['siteTitle'];
            $logoUrl = $display['logoUrl'];
            $startYear = $display['startYear'];
            $displayYear = $display['displayYear'];
            $showFullUrl = $display['showFullUrl'];
            $showLogo = $display['showLogo'];
            $themeColor = $display['themeColor'];
            $robotsContent = $display['robotsPolicy'] === 'none' ? '' : $display['robotsPolicy'];

            $template = __DIR__ . '/page/index.php';
            if (is_file($template)) {
                include $template;
            }
            return;
        }

        // 获取 Base64 编码的目标链接：优先从 Typecho 请求对象或路径中提取（URL-safe base64），否则回退到 ?target=
        $encodedTarget = '';

        // 1) 优先尝试 Typecho 的请求对象（Action 中通常可用）
        if (isset($this->request) && method_exists($this->request, 'get')) {
            try {
                $requestedTarget = $this->request->get('target', '');
                $encodedTarget = is_string($requestedTarget) ? $requestedTarget : '';
            } catch (Exception $e) {
                $encodedTarget = '';
            }
        }

        // 2) PATH_INFO
        if (empty($encodedTarget) && !empty($_SERVER['PATH_INFO'])) {
            $parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if (isset($parts[0]) && $parts[0] === 'go' && isset($parts[1])) {
                $encodedTarget = $parts[1];
            }
        }

        // 3) REQUEST_URI
        if (empty($encodedTarget) && !empty($_SERVER['REQUEST_URI'])) {
            if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REQUEST_URI'], $m)) {
                $encodedTarget = $m[1];
            }
        }

        // 4) REDIRECT_URL
        if (empty($encodedTarget) && !empty($_SERVER['REDIRECT_URL'])) {
            if (preg_match('#/go/([A-Za-z0-9\-_]+)#', $_SERVER['REDIRECT_URL'], $m)) {
                $encodedTarget = $m[1];
            }
        }

        // 5) 最后回退到 GET
        if (empty($encodedTarget)) {
            $rawTarget = isset($_GET['target']) ? $_GET['target'] : '';
            $encodedTarget = is_string($rawTarget) ? $rawTarget : '';
        }

        // 调试日志（在 config.inc.php 中 define('LINKGO_DEBUG', true) 可开启）
        if (defined('LINKGO_DEBUG') && LINKGO_DEBUG && function_exists('error_log')) {
            error_log('[LinkGo] debug: GET=' . json_encode($_GET, JSON_UNESCAPED_UNICODE) . ' PATH_INFO=' . ($_SERVER['PATH_INFO'] ?? '') . ' REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '') . ' encoded=' . $encodedTarget);
        }

        $target = '';
        if (!empty($encodedTarget)) {
            $b64 = strtr($encodedTarget, '-_', '+/');
            $pad = strlen($b64) % 4;
            if ($pad > 0) {
                $b64 .= str_repeat('=', 4 - $pad);
            }
            $target = base64_decode($b64);
        }

        if (!empty($target) && filter_var($target, FILTER_VALIDATE_URL)) {
            $parsed_url = parse_url($target);
            if (isset($parsed_url['scheme']) && in_array($parsed_url['scheme'], ['http', 'https'])) {
                $url = $target;
                $title = '安全跳转';
            } else {
                $url = $siteBase;
                $title = '非法 URL';
            }
        } else {
            $title = '非法 URL';
            $url = $siteBase;
        }

        // 注意：白名单仅用于“不改写外链”。/go 路由仍按原逻辑渲染提示页。

        $parsed_url = parse_url($url);
        $display_url = $parsed_url['host'] ?? $url;

        // 渲染视图：复用 page/index.php 的模板（直接 include 以便保留样式）
        $siteUrl = $siteBase;

        // 读取插件设置（站点标题 / Logo / 起始年份），让设置页真正生效
        $display = self::getDisplaySettings();
        $siteTitle = $display['siteTitle'];
        $logoUrl = $display['logoUrl'];
        $startYear = $display['startYear'];
        $displayYear = $display['displayYear'];
        $showFullUrl = $display['showFullUrl'];
        $showLogo = $display['showLogo'];
        $themeColor = $display['themeColor'];
        $robotsContent = $display['robotsPolicy'] === 'none' ? '' : $display['robotsPolicy'];

        $template = __DIR__ . '/page/index.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<p>跳转页面模板未找到。</p>';
        }
    }

    /**
     * 规范化十六进制主题色（支持 #RGB / #RRGGBB，可省略 #）。
     */
    private static function normalizeThemeColor($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return '';
        }
        if ($raw[0] !== '#') {
            $raw = '#' . $raw;
        }
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $raw)) {
            return '';
        }
        if (strlen($raw) === 4) {
            $raw = '#' . $raw[1] . $raw[1] . $raw[2] . $raw[2] . $raw[3] . $raw[3];
        }
        return strtoupper($raw);
    }

    /**
     * 是否发送 X-Robots-Tag 响应头（默认开启）。
     */
    private static function sendNoindexHeader()
    {
        try {
            $options = Typecho_Widget::widget('Widget_Options');
            $plugin = $options->plugin('LinkGo');
            if (isset($plugin->sendNoindexHeader)) {
                return (string)$plugin->sendNoindexHeader !== '0';
            }
        } catch (Exception $e) {
            // ignore
        } catch (Throwable $e) {
            // ignore
        }

        return true;
    }

    /**
     * 站点根地址（不含末尾斜杠）。
     */
    private static function getSiteBaseUrl()
    {
        try {
            $options = Typecho_Widget::widget('Widget_Options');
            $siteUrl = rtrim((string)$options->siteUrl, '/');
            if ($siteUrl !== '') {
                return $siteUrl;
            }
        } catch (Exception $e) {
            // ignore
        } catch (Throwable $e) {
            // ignore
        }

        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : 'localhost';
        return $scheme . '://' . $host;
    }

    /**
     * 跳转页显示相关设置（带默认值）。
     *
     * @return array
     */
    private static function getDisplaySettings()
    {
        $settings = array(
            'siteTitle' => '',
            'logoUrl' => '',
            'startYear' => 2026,
            'showFullUrl' => true,
            'showLogo' => true,
            'themeColor' => '',
            'robotsPolicy' => 'noindex, nofollow',
        );

        try {
            $options = Typecho_Widget::widget('Widget_Options');
            $plugin = $options->plugin('LinkGo');
            if (!empty($plugin->siteTitle)) {
                $settings['siteTitle'] = (string)$plugin->siteTitle;
            }
            if (!empty($plugin->logoUrl)) {
                $settings['logoUrl'] = (string)$plugin->logoUrl;
            }
            if (!empty($plugin->startYear) && ctype_digit((string)$plugin->startYear)) {
                $settings['startYear'] = (int)$plugin->startYear;
            }
            if (isset($plugin->showFullUrl)) {
                $settings['showFullUrl'] = (string)$plugin->showFullUrl !== '0';
            }
            if (isset($plugin->showLogo)) {
                $settings['showLogo'] = (string)$plugin->showLogo !== '0';
            }
            if (isset($plugin->themeColor)) {
                $settings['themeColor'] = self::normalizeThemeColor($plugin->themeColor);
            }
            if (isset($plugin->robotsPolicy)) {
                $robots = (string)$plugin->robotsPolicy;
                if (in_array($robots, array('noindex, nofollow', 'noindex, follow', 'noindex', 'none'), true)) {
                    $settings['robotsPolicy'] = $robots;
                }
            }
        } catch (Exception $e) {
            // ignore
        } catch (Throwable $e) {
            // ignore
        }

        if ($settings['siteTitle'] === '') {
            try {
                $settings['siteTitle'] = (string)Typecho_Widget::widget('Widget_Options')->title;
            } catch (Exception $e) {
                $settings['siteTitle'] = 'LinkGo';
            } catch (Throwable $e) {
                $settings['siteTitle'] = 'LinkGo';
            }
        }
        if ($settings['siteTitle'] === '') {
            $settings['siteTitle'] = 'LinkGo';
        }
        if ($settings['logoUrl'] === '') {
            $settings['logoUrl'] = self::getSiteBaseUrl() . '/usr/plugins/LinkGo/page/logo.svg';
        }

        $currentYear = (int)date('Y');
        $startYear = (int)$settings['startYear'];
        $settings['displayYear'] = ($currentYear > $startYear) ? ($startYear . ' - ' . $currentYear) : (string)$currentYear;

        return $settings;
    }
}
