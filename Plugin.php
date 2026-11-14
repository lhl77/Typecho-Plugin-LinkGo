<?php
/**
 * LinkGo 外部链接自动跳转插件
 * 
 * @package LinkGo
 * @author LHL
 * @version 1.2.0
 * @link https://github.com/lhl77/Typecho-Plugin-LinkGo
 */
class LinkGo_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 插件版本（跳转页署名/控制台信息与设置页共用）
     */
    const VERSION = '1.2.0';

    /**
     * PHP 7/7.4 兼容：startsWith / endsWith（PHP8 才有 str_starts_with/str_ends_with）。
     */
    private static function startsWith($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    private static function endsWith($haystack, $needle)
    {
        $haystack = (string)$haystack;
        $needle = (string)$needle;
        if ($needle === '') return true;
        $len = strlen($needle);
        return $len === 0 ? true : substr($haystack, -$len) === $needle;
    }

    public static function activate()
    {
        // 旧式/兼容注册（适配老版本或部分主题）
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('LinkGo_Plugin', 'convertLinks');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('LinkGo_Plugin', 'convertLinks');

        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('LinkGo_Plugin', 'convertCommentLinks');
        // 尝试修改评论者链接字段（部分主题会读取 comment.url）
        Typecho_Plugin::factory('Widget_Abstract_Comments')->filter = array('LinkGo_Plugin', 'convertAuthorUrl');

        // Namespaced 注册（Typecho 新版/文档中常见写法）
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->contentEx = ['LinkGo_Plugin', 'convertLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->excerptEx = ['LinkGo_Plugin', 'convertLinks'];

        \Typecho\Plugin::factory('Widget\\Base\\Comments')->contentEx = ['LinkGo_Plugin', 'convertCommentLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Comments')->filter = ['LinkGo_Plugin', 'convertAuthorUrl'];
        // 兜底：在 Archive 渲染后再运行一次替换，覆盖绕开过滤器的主题实现
        \Typecho\Plugin::factory('Widget\\Archive')->afterRender = ['LinkGo_Plugin', 'applyToArchive'];

        // 输出缓冲：尝试在 Archive 的 header/footer 阶段捕获全部输出并处理
        \Typecho\Plugin::factory('Widget\\Archive')->header = ['LinkGo_Plugin', 'startBuffer'];
        \Typecho\Plugin::factory('Widget\\Archive')->footer = ['LinkGo_Plugin', 'endBuffer'];

        // 在插件激活时注册路由，让 /go 能够由 Typecho 路由到插件 Action
        try {
            if (class_exists('Typecho\Widget\Helper')) {
                \Typecho\Widget::widget('Widget_Options')->plugin('LinkGo');
            }
        } catch (Exception $e) {
            // ignore
        }

        // 使用 Helper::addRoute 注册路由（带参数目标），优先使用常见的命名空间实现
        $routePath = '/go/[target]';
        if (class_exists('\Typecho\\Helper') && method_exists('\Typecho\\Helper', 'addRoute')) {
            \Typecho\Helper::addRoute('linkgo', $routePath, 'LinkGo_Action', 'index');
        } elseif (class_exists('Helper') && method_exists('Helper', 'addRoute')) {
            Helper::addRoute('linkgo', $routePath, 'LinkGo_Action', 'index');
        } elseif (class_exists('Utils\\Helper') && method_exists('Utils\\Helper', 'addRoute')) {
            \Utils\Helper::addRoute('linkgo', $routePath, 'LinkGo_Action', 'index');
        }

        return '插件已激活';
    }

    public static function deactivate()
    {
        // 注销前面可能添加的路由
        if (class_exists('\Typecho\\Helper') && method_exists('\Typecho\\Helper', 'removeRoute')) {
            \Typecho\Helper::removeRoute('linkgo');
        } elseif (class_exists('Helper') && method_exists('Helper', 'removeRoute')) {
            Helper::removeRoute('linkgo');
        } elseif (class_exists('Utils\\Helper') && method_exists('Utils\\Helper', 'removeRoute')) {
            \Utils\Helper::removeRoute('linkgo');
        }

        return '插件已禁用';
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 输出 Material Design 3 设置页样式与交互（含跳转页主题预览）
        self::renderConfigAssets(self::scanThemes());

        // 站点显示标题（用于跳转页）
        $siteTitle = new Typecho_Widget_Helper_Form_Element_Text(
            'siteTitle',
            null,
            '',
            _t('跳转页面站点标题'),
            _t('显示在跳转提示页的站点名称；留空时使用站点标题。')
        );
        $form->addInput($siteTitle);

        // Logo 图片 URL
        $logoUrl = new Typecho_Widget_Helper_Form_Element_Text(
            'logoUrl',
            null,
            '',
            _t('Logo 图片 URL'),
            _t('推荐使用正方形图片（PNG/SVG），留空时使用插件内置图标。')
        );
        $form->addInput($logoUrl);

        // 起始年份
        $startYear = new Typecho_Widget_Helper_Form_Element_Text(
            'startYear',
            null,
            '2026',
            _t('起始年份（页脚）'),
            _t('页脚显示为「起始年份 - 当前年份」，与当前年份相同时只显示一个年份。')
        );
        $form->addInput($startYear);

        // 主题选择：扫描插件目录下的 page/themes 子目录作为可选主题
        $themeOptions = self::scanThemes();
        $themeName = new Typecho_Widget_Helper_Form_Element_Select(
            'themeName',
            $themeOptions,
            'Default',
            _t('跳转页主题'),
            _t('跳转提示页使用的外观模板；可在右侧「主题预览」中实时查看效果。')
        );
        $form->addInput($themeName);

        // 主题色（十六进制；留空跟随主题默认）
        $themeColor = new Typecho_Widget_Helper_Form_Element_Text(
            'themeColor',
            null,
            '',
            _t('主题色'),
            _t('十六进制颜色，留空则保留主题默认主题色。')
        );
        if (method_exists($themeColor, 'setInputsAttribute')) {
            $themeColor->setInputsAttribute('maxlength', '7');
        }
        $form->addInput($themeColor);

        // 外部链接是否在新标签打开
        $openNew = new Typecho_Widget_Helper_Form_Element_Radio(
            'openInNewTab',
            array('1' => '是（_blank）', '0' => '否（当前窗口）'),
            '1',
            _t('外部链接打开方式'),
            _t('控制站点内被改写的外链是否在新标签页中打开。')
        );
        $form->addInput($openNew);

        // 重写监控开关：当主题使用 AJAX/客户端渲染时推荐开启
        $enableClient = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableClientRewrite',
            array('1' => '是（推荐）', '0' => '否'),
            '1',
            _t('AJAX 兼容'),
            _t('主题使用 AJAX / 客户端渲染时，由前端脚本继续改写动态插入的外链，推荐保持开启。')
        );
        $form->addInput($enableClient);

        $skipLinksPluginUrls = new Typecho_Widget_Helper_Form_Element_Radio(
            'skipLinksPluginUrls',
            array('1' => '是（不接管）', '0' => '否'),
            '0',
            _t('不接管Links友链中的url'),
            _t('启用后，如果外链 URL 与 Links 插件中已启用的友链 URL 相同，LinkGo 将保持原链接，不改写成 /go/xxx。')
        );
        $form->addInput($skipLinksPluginUrls);

        // —— <a> 标签高级设置 ——
        $linkRelMode = new Typecho_Widget_Helper_Form_Element_Select(
            'linkRelMode',
            array(
                'noopener noreferrer' => 'noopener noreferrer（默认）',
                'nofollow noopener noreferrer' => 'nofollow noopener noreferrer',
                'sponsored nofollow noopener noreferrer' => 'sponsored nofollow noopener noreferrer',
                'noopener' => 'noopener',
                'none' => '不添加 rel',
            ),
            'noopener noreferrer',
            _t('外链 rel 属性'),
            _t('改写后的外链会合并该 rel 值（保留作者原有的 rel）。nofollow / sponsored 会影响 SEO 权重传递，请按站点策略选择。')
        );
        $form->addInput($linkRelMode);

        $linkTitleMode = new Typecho_Widget_Helper_Form_Element_Select(
            'linkTitleMode',
            array(
                'host' => '目标域名（默认）',
                'full' => '完整链接',
                'none' => '不添加',
            ),
            'host',
            _t('外链 title 提示'),
            _t('为改写后的外链补充 title 属性（鼠标悬停提示）；作者已写 title 时不覆盖。')
        );
        $form->addInput($linkTitleMode);

        $linkClassName = new Typecho_Widget_Helper_Form_Element_Text(
            'linkClassName',
            null,
            '',
            _t('附加 CSS 类名'),
            _t('为改写后的外链追加 class，便于主题命中样式或做统计；留空不添加，多个类名用空格分隔。')
        );
        if (method_exists($linkClassName, 'setInputsAttribute')) {
            $linkClassName->setInputsAttribute('placeholder', '例如：external-link linkgo-out');
        }
        $form->addInput($linkClassName);

        $addDataAttr = new Typecho_Widget_Helper_Form_Element_Radio(
            'addDataAttr',
            array('1' => '是（默认）', '0' => '否'),
            '1',
            _t('添加 data-linkgo 标记'),
            _t('为改写后的外链添加 data-linkgo="1"，便于前端脚本 / 统计脚本识别。')
        );
        $form->addInput($addDataAttr);

        // 目标链接显示方式
        $showFullUrl = new Typecho_Widget_Helper_Form_Element_Radio(
            'showFullUrl',
            array('1' => '完整链接', '0' => '仅域名'),
            '1',
            _t('目标链接显示方式'),
            _t('跳转页展示目标地址时，是显示完整 URL 还是只显示域名。')
        );
        $form->addInput($showFullUrl);

        // 是否显示站点 Logo
        $showLogo = new Typecho_Widget_Helper_Form_Element_Radio(
            'showLogo',
            array('1' => '显示', '0' => '隐藏'),
            '1',
            _t('显示站点 Logo'),
            _t('关闭后跳转页不再显示站点 Logo（部分主题本身不含 Logo，此项对其无效）。')
        );
        $form->addInput($showLogo);

        // —— SEO 设置 ——
        $robotsPolicy = new Typecho_Widget_Helper_Form_Element_Select(
            'robotsPolicy',
            array(
                'noindex, nofollow' => 'noindex, nofollow（默认）',
                'noindex, follow' => 'noindex, follow',
                'noindex' => 'noindex',
                'none' => '不输出 robots meta',
            ),
            'noindex, nofollow',
            _t('跳转页 robots'),
            _t('控制跳转提示页的 robots meta（防止中间页被搜索引擎收录）。')
        );
        $form->addInput($robotsPolicy);

        $sendNoindexHeader = new Typecho_Widget_Helper_Form_Element_Radio(
            'sendNoindexHeader',
            array('1' => '是（默认）', '0' => '否'),
            '1',
            _t('发送 X-Robots-Tag 响应头'),
            _t('跳转页会额外发送 X-Robots-Tag: noindex, nofollow，与 meta 双保险。')
        );
        $form->addInput($sendNoindexHeader);
        
        // 网址白名单
        // Typecho 的表单 textarea 元素在不同版本/提示文件中可能存在命名差异，这里做一个兼容兜底
        $textareaClass = class_exists('Typecho_Widget_Helper_Form_Element_Textarea')
            ? 'Typecho_Widget_Helper_Form_Element_Textarea'
            : (class_exists('Typecho_Widget_Helper_Form_Element_TextArea') ? 'Typecho_Widget_Helper_Form_Element_TextArea' : 'Typecho_Widget_Helper_Form_Element_Textarea');
        $whitelist = new $textareaClass(
            'urlWhitelist',
            null,
            "",
            _t('网址白名单（命中则不改写外链）'),
            _t(
                "每行一条规则，命中后该外链将保持原样（不会改写成 /go/xxx）。<br/><br/>" .
                "<b>✅ 推荐写法（最常用）</b><br/>" .
                "• <code>example.com</code>：白名单整个域名（含所有路径）<br/>" .
                "• <code>*.example.com</code>：白名单主域名及所有子域名（如 a.example.com）<br/>" .
                "• <code>example.com/path/</code>：白名单路径前缀（该路径下所有页面）<br/><br/>" .
                "<b>📌 也支持写完整 URL（含 http/https）</b><br/>" .
                "• <code>https://a.com</code> / <code>https://a.com/</code>：按“域名规则”处理（等同 <code>a.com</code>）<br/>" .
                "• <code>https://a.com/path/</code>：按“路径前缀规则”处理（等同 <code>a.com/path/</code>）<br/>" .
                "• <code>https://a.com/page?id=1</code>：带 query/hash 时按“精准 URL”匹配<br/><br/>" .
                "<b>⚠ 注意</b><br/>" .
                "• 只匹配 http/https；忽略大小写；域名不含端口（:8080 会被忽略）<br/>" .
                "• 可用 <code>#</code> 或 <code>//</code> 开头写注释；行尾也可用空格 + # 注释<br/>"
            )
        );
        // 尝试为 textarea 增加 placeholder（Typecho 不同版本的元素实现差异较大，这里做兼容兜底）
        $placeholder = "# 示例：每行一条\nexample.com\n*.example.com\nexample.com/path/\nhttps://icp.redcha.cn/beian/\n# 精准（含 query）\nhttps://example.com/page?id=1";
        if (method_exists($whitelist, 'setInputsAttribute')) {
            $whitelist->setInputsAttribute('placeholder', $placeholder);
        } elseif (!empty($whitelist->input) && method_exists($whitelist->input, 'setAttribute')) {
            $whitelist->input->setAttribute('placeholder', $placeholder);
        }
        $form->addInput($whitelist);

    }

    /**
     * 扫描 page/themes 下的可用主题（要求存在 template.php）。
     *
     * @return array<string, string>
     */
    private static function scanThemes()
    {
        $themes = array();
        $dir = __DIR__ . '/page/themes';

        if (is_dir($dir)) {
            $items = @scandir($dir);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }
                    if (is_dir($dir . '/' . $item) && is_file($dir . '/' . $item . '/template.php')) {
                        $themes[$item] = $item;
                    }
                }
            }
        }

        if (isset($themes['Default'])) {
            $default = $themes['Default'];
            unset($themes['Default']);
            $themes = array('Default' => $default) + $themes;
        }

        if (empty($themes)) {
            $themes = array('Default' => 'Default');
        }

        return $themes;
    }

    /**
     * 主题强调色（用于设置页预览选择器上的色点）。
     */
    private static function getThemeAccent($name)
    {
        $mapFile = __DIR__ . '/page/theme-accents.php';
        $map = is_file($mapFile) ? include $mapFile : array();
        if (!is_array($map)) {
            $map = array();
        }

        if (isset($map[$name])) {
            return $map[$name];
        }

        $hue = abs(crc32((string)$name)) % 360;
        return 'hsl(' . $hue . ', 62%, 48%)';
    }

    /**
     * 站点根地址（不含末尾斜杠），用于生成主题预览地址。
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

        return '';
    }

    /**
     * 输出设置页的 Material Design 3 样式与交互脚本（含跳转页主题预览）。
     *
     * @param array<string, string> $themeOptions
     */
    private static function renderConfigAssets(array $themeOptions)
    {
        $themes = array();
        foreach ($themeOptions as $name => $label) {
            $themes[] = array(
                'name' => (string)$name,
                'accent' => self::getThemeAccent((string)$name),
            );
        }

        $themesJson = json_encode($themes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (!is_string($themesJson) || $themesJson === '') {
            $themesJson = '[]';
        }

        $siteBase = self::getSiteBaseUrl();
        $previewBase = $siteBase !== '' ? $siteBase . '/go/preview' : '';

        echo <<<'LG_CSS'
<style id="linkgo-md3-style">
/* ============================================================
   LinkGo 设置页 · Material Design 3
   颜色令牌全部复用 AdminBeautify 的 --md-*；
   AB 未启用时回退到 MD3 亮色基线（不跟随操作系统暗色）。
   ============================================================ */
.linkgo-page{
    --lg-primary:var(--md-primary,#6750A4);
    --lg-on-primary:var(--md-on-primary,#FFFFFF);
    --lg-primary-container:var(--md-primary-container,#EADDFF);
    --lg-on-primary-container:var(--md-on-primary-container,#21005D);
    --lg-secondary-container:var(--md-secondary-container,#E8DEF8);
    --lg-on-secondary-container:var(--md-on-secondary-container,#1D192B);
    --lg-surface:var(--md-surface,#FEF7FF);
    --lg-surface-lowest:var(--md-surface-container-lowest,#FFFFFF);
    --lg-surface-low:var(--md-surface-container-low,#F7F2FA);
    --lg-surface-container:var(--md-surface-container,#F3EDF7);
    --lg-surface-high:var(--md-surface-container-high,#ECE6F0);
    --lg-surface-highest:var(--md-surface-container-highest,#E6E0E9);
    --lg-on-surface:var(--md-on-surface,#1D1B20);
    --lg-on-surface-variant:var(--md-on-surface-variant,#49454F);
    --lg-outline:var(--md-outline,#79747E);
    --lg-outline-variant:var(--md-outline-variant,#CAC4D0);
    --lg-error:var(--md-error,#B3261E);
    --lg-r-sm:var(--md-radius-md,12px);
    --lg-r-md:var(--md-radius-lg,16px);
    --lg-r-lg:var(--md-radius-xl,24px);
    --lg-r-xl:var(--md-radius-xl,28px);
    --lg-elev-1:var(--md-elevation-1,0 1px 2px rgba(0,0,0,.10),0 1px 3px 1px rgba(0,0,0,.06));
    --lg-elev-2:var(--md-elevation-2,0 2px 6px 2px rgba(0,0,0,.10),0 1px 2px rgba(0,0,0,.12));
    font-family:inherit;
}

/* Typecho 1.3 的栅格是 flex：必须同时覆盖 flex-basis 与 max-width，否则只到 8 列 */
.linkgo-page>[class*="col-"]{
    flex:0 0 100%!important;
    max-width:100%!important;
    width:100%!important;
    margin-left:0!important;
    float:none!important;
}
.linkgo-page .lg-shell{max-width:1160px;margin:8px auto 40px;display:flex;flex-direction:column;gap:20px}
.linkgo-page .lg-card h1,.linkgo-page .lg-card h2,.linkgo-page .lg-card h3{font-family:inherit}

/* --- Hero --- */
.linkgo-page .lg-hero{
    position:relative;overflow:hidden;
    display:flex;align-items:center;gap:18px;flex-wrap:wrap;
    padding:22px 24px;border-radius:var(--lg-r-xl);
    background:
        radial-gradient(120% 180% at 0% 0%,color-mix(in srgb,var(--lg-primary) 20%,transparent) 0%,transparent 55%),
        var(--lg-surface-low);
    border:1px solid var(--lg-outline-variant);
}
.linkgo-page .lg-hero__logo{
    flex:0 0 62px;width:62px;height:62px;border-radius:18px;
    display:flex;align-items:center;justify-content:center;
    background:var(--lg-primary-container);color:var(--lg-on-primary-container);
    box-shadow:var(--lg-elev-1);
}
.linkgo-page .lg-hero__logo svg{width:32px;height:32px}
.linkgo-page .lg-hero__text{flex:1 1 200px;min-width:0}
.linkgo-page .lg-hero__title{display:flex;align-items:center;gap:10px;margin:0;font-size:22px;font-weight:700;letter-spacing:.2px;color:var(--lg-on-surface)}
.linkgo-page .lg-hero__version{
    font-size:11px;font-weight:600;line-height:1;letter-spacing:.4px;
    padding:5px 9px;border-radius:100px;
    background:var(--lg-secondary-container);color:var(--lg-on-secondary-container);
}
.linkgo-page .lg-hero__sub{margin:6px 0 0;font-size:13px;line-height:1.6;color:var(--lg-on-surface-variant)}
.linkgo-page .lg-hero__actions{display:flex;gap:8px;flex-wrap:wrap}
.linkgo-page .lg-chip{
    display:inline-flex;align-items:center;gap:7px;
    height:34px;padding:0 14px;border-radius:100px;
    border:1px solid var(--lg-outline);
    background:transparent;color:var(--lg-on-surface);
    font-size:13px;font-weight:500;text-decoration:none!important;
    transition:background .18s ease;
}
.linkgo-page .lg-chip:hover{background:color-mix(in srgb,var(--lg-on-surface) 8%,transparent);color:var(--lg-on-surface);text-decoration:none!important}
.linkgo-page .lg-chip svg{width:16px;height:16px;opacity:.8}

/* --- Layout --- */
.linkgo-page .lg-layout{display:grid;grid-template-columns:minmax(0,.9fr) minmax(420px,1.1fr);gap:20px;align-items:start}
@media (max-width:1120px){.linkgo-page .lg-layout{grid-template-columns:minmax(0,1fr)}}
.linkgo-page .lg-main{min-width:0}
.linkgo-page .lg-aside{min-width:0}

/* --- Cards --- */
.linkgo-page .lg-card{
    position:relative;
    padding:20px 22px;
    border-radius:var(--lg-r-lg);
    background:var(--lg-surface-low);
    border:1px solid var(--lg-outline-variant);
    box-shadow:var(--lg-elev-1);
}
.linkgo-page .lg-card+.lg-card{margin-top:16px}
.linkgo-page .lg-card__head{display:flex;align-items:flex-start;gap:14px;margin-bottom:6px}
.linkgo-page .lg-card__icon{
    flex:0 0 40px;width:40px;height:40px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    background:var(--lg-secondary-container);color:var(--lg-on-secondary-container);
}
.linkgo-page .lg-card__icon svg{width:20px;height:20px}
.linkgo-page .lg-card__title{font-size:15px;font-weight:600;line-height:1.4;color:var(--lg-on-surface)}
.linkgo-page .lg-card__desc{margin-top:3px;font-size:12.5px;line-height:1.55;color:var(--lg-on-surface-variant)}
.linkgo-page .lg-card__body{margin-top:14px}

/* --- Fields --- */
.linkgo-page .lg-main ul.typecho-option{
    margin:0!important;padding:0!important;list-style:none!important;
    background:transparent!important;border:0!important;box-shadow:none!important;
}
.linkgo-page .lg-main ul.typecho-option li{margin:0 0 26px!important;padding:0!important}
.linkgo-page .lg-main .lg-card__body>ul.typecho-option:last-of-type li{margin-bottom:0!important}
.linkgo-page .lg-main .typecho-label{
    display:block!important;margin:0 0 8px!important;
    font-size:13px!important;font-weight:600!important;color:var(--lg-on-surface-variant)!important;
}
.linkgo-page .lg-main ul.typecho-option .description{
    margin:8px 0 0!important;padding:0!important;
    font-size:12.5px!important;line-height:1.65!important;color:var(--lg-on-surface-variant)!important;
}
.linkgo-page .lg-main ul.typecho-option .description code{
    padding:1px 5px;border-radius:6px;font-size:12px;
    background:var(--lg-surface-high);color:var(--lg-on-surface);
    font-family:"JetBrains Mono",Consolas,Monaco,monospace;
}

/* 文本 / 下拉：MD3 filled field */
.linkgo-page .lg-main input[type=text],
.linkgo-page .lg-main input[type=url],
.linkgo-page .lg-main select{
    display:block!important;box-sizing:border-box!important;width:100%!important;
    min-height:56px!important;height:auto!important;margin:0!important;
    padding:16px 16px 15px!important;
    border:0!important;border-bottom:1px solid var(--lg-on-surface-variant)!important;
    border-radius:var(--lg-r-sm) var(--lg-r-sm) 0 0!important;
    background:var(--lg-surface-highest)!important;
    color:var(--lg-on-surface)!important;
    font-size:14px!important;line-height:1.5!important;
    box-shadow:none!important;
    transition:background .18s ease,border-color .18s ease,box-shadow .18s ease;
}
.linkgo-page .lg-main input[type=text]:hover,
.linkgo-page .lg-main input[type=url]:hover,
.linkgo-page .lg-main select:hover{
    background:color-mix(in srgb,var(--lg-on-surface) 7%,var(--lg-surface-highest))!important;
}
.linkgo-page .lg-main input[type=text]:focus,
.linkgo-page .lg-main input[type=url]:focus,
.linkgo-page .lg-main select:focus{
    outline:none!important;
    border-bottom:2px solid var(--lg-primary)!important;
    padding-bottom:14px!important;
    box-shadow:none!important;
}
.linkgo-page .lg-main select{
    appearance:auto;-webkit-appearance:auto;
    padding-right:40px!important;
}

/* 主题色：文本 + 原生取色器 */
.linkgo-page .lg-color-field{display:flex;align-items:center;gap:10px}
.linkgo-page .lg-color-field input[type=text]{flex:1 1 auto;min-width:0}
.linkgo-page .lg-color-picker{
    flex:0 0 56px;width:56px!important;height:56px!important;padding:5px!important;
    border:1px solid var(--lg-outline-variant)!important;border-radius:12px!important;
    background:var(--lg-surface-highest)!important;cursor:pointer;
}
.linkgo-page .lg-color-picker::-webkit-color-swatch-wrapper{padding:0}
.linkgo-page .lg-color-picker::-webkit-color-swatch{border:0;border-radius:8px}
.linkgo-page .lg-color-picker::-moz-color-swatch{border:0;border-radius:8px}

/* 白名单：代码风的大输入框 */
.linkgo-page .lg-main textarea{
    display:block!important;box-sizing:border-box!important;width:100%!important;
    min-height:250px!important;margin:0!important;padding:16px!important;
    border:1px solid var(--lg-outline-variant)!important;
    border-radius:var(--lg-r-md)!important;
    background:var(--lg-surface-lowest)!important;
    color:var(--lg-on-surface)!important;
    font-family:"JetBrains Mono",Consolas,Monaco,"Courier New",monospace!important;
    font-size:12.5px!important;line-height:1.7!important;
    box-shadow:none!important;resize:vertical;
    transition:border-color .18s ease,box-shadow .18s ease;
}
.linkgo-page .lg-main textarea:hover{border-color:var(--lg-outline)!important}
.linkgo-page .lg-main textarea:focus{
    outline:none!important;
    border-color:var(--lg-primary)!important;
    box-shadow:0 0 0 2px color-mix(in srgb,var(--lg-primary) 22%,transparent)!important;
}
.linkgo-page .lg-main textarea::placeholder{color:var(--lg-on-surface-variant);opacity:.7}

/* --- 单选：MD3 choice chip --- */
.linkgo-page .lg-choice{
    display:inline-flex!important;align-items:center;gap:10px;
    margin:0 8px 10px 0!important;padding:10px 16px 10px 12px!important;
    border:1px solid var(--lg-outline)!important;
    border-radius:100px!important;
    background:transparent!important;
    color:var(--lg-on-surface)!important;
    line-height:1.2!important;cursor:pointer;
    transition:background .18s ease,border-color .18s ease,color .18s ease;
}
.linkgo-page .lg-choice:hover{background:color-mix(in srgb,var(--lg-on-surface) 8%,transparent)!important}
.linkgo-page .lg-choice.is-checked{
    background:var(--lg-secondary-container)!important;
    border-color:transparent!important;
    color:var(--lg-on-secondary-container)!important;
}
.linkgo-page .lg-choice input[type=radio]{
    appearance:none;-webkit-appearance:none;
    flex:0 0 20px;width:20px!important;height:20px!important;
    margin:0!important;padding:0!important;
    border:2px solid var(--lg-on-surface-variant)!important;
    border-radius:50%!important;background:transparent!important;
    position:relative;cursor:pointer;
    box-shadow:none!important;
}
.linkgo-page .lg-choice input[type=radio]:checked{border-color:var(--lg-primary)!important}
.linkgo-page .lg-choice.is-checked input[type=radio]{border-color:var(--lg-primary)!important}
.linkgo-page .lg-choice input[type=radio]:checked::after{
    content:"";position:absolute;inset:3px;border-radius:50%;background:var(--lg-primary);
}
.linkgo-page .lg-choice label{
    margin:0!important;font-size:13.5px!important;font-weight:500!important;
    color:inherit!important;cursor:pointer;
}

/* --- 白名单示例 --- */
.linkgo-page .lg-examples{
    margin-top:14px;padding:16px;
    border-radius:var(--lg-r-md);
    background:var(--lg-surface-container);
    border:1px dashed var(--lg-outline-variant);
}
.linkgo-page .lg-examples .ex-title{font-size:13.5px;font-weight:600;color:var(--lg-on-surface)}
.linkgo-page .lg-examples .ex-desc{margin-top:4px;font-size:12.5px;line-height:1.6;color:var(--lg-on-surface-variant)}
.linkgo-page .lg-examples .ex-actions{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 10px}
.linkgo-page .lg-examples .ex-btn{
    appearance:none;cursor:pointer;
    height:32px;padding:0 14px;border-radius:100px;
    border:1px solid var(--lg-outline);
    background:transparent;color:var(--lg-primary);
    font-size:12.5px;font-weight:600;
    transition:background .18s ease;
}
.linkgo-page .lg-examples .ex-btn:hover{background:color-mix(in srgb,var(--lg-primary) 10%,transparent)}
.linkgo-page .lg-examples pre{
    margin:0;padding:14px;border-radius:var(--lg-r-sm);
    background:var(--lg-surface-lowest);border:1px solid var(--lg-outline-variant);
    color:var(--lg-on-surface);overflow:auto;
    font-size:12px;line-height:1.7;
}
.linkgo-page .lg-examples code{font-family:"JetBrains Mono",Consolas,Monaco,monospace;background:transparent;padding:0}

/* --- 规则说明折叠面板 --- */
.linkgo-page .lg-help{
    margin-top:12px;border:1px solid var(--lg-outline-variant);
    border-radius:var(--lg-r-sm);background:var(--lg-surface-container);
    overflow:hidden;
}
.linkgo-page .lg-help>summary{
    display:flex;align-items:center;gap:8px;cursor:pointer;list-style:none;
    padding:12px 14px;font-size:12.5px;font-weight:600;color:var(--lg-on-surface);
}
.linkgo-page .lg-help>summary::-webkit-details-marker{display:none}
.linkgo-page .lg-help>summary::before{
    content:"";width:6px;height:6px;margin-right:2px;
    border-right:1.6px solid currentColor;border-bottom:1.6px solid currentColor;
    transform:rotate(-45deg);opacity:.7;transition:transform .2s ease;
}
.linkgo-page .lg-help[open]>summary::before{transform:rotate(45deg)}
.linkgo-page .lg-help__body{
    padding:0 14px 14px;font-size:12.5px;line-height:1.75;
    color:var(--lg-on-surface-variant);
}
.linkgo-page .lg-help__body b{color:var(--lg-on-surface)}
.linkgo-page .lg-help__body code{
    padding:1px 5px;border-radius:6px;font-size:12px;
    background:var(--lg-surface-high);color:var(--lg-on-surface);
    font-family:"JetBrains Mono",Consolas,Monaco,monospace;
}

/* --- 保存动作条 --- */
.linkgo-page .lg-actions{
    display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
    margin-top:18px;padding:12px 16px;
    border-radius:20px;
    background:color-mix(in srgb,var(--lg-surface-high) 86%,transparent);
    border:1px solid var(--lg-outline-variant);
    box-shadow:var(--lg-elev-1);
}
.linkgo-page .lg-actions__hint{display:inline-flex;align-items:center;gap:8px;font-size:12.5px;color:var(--lg-on-surface-variant)}
.linkgo-page .lg-actions__hint svg{width:16px;height:16px;opacity:.75}
.linkgo-page .lg-actions ul.typecho-option{margin:0!important;padding:0!important;background:transparent!important;border:0!important}
.linkgo-page .lg-actions ul.typecho-option li{margin:0!important;padding:0!important}
.linkgo-page .lg-actions input[type=submit],
.linkgo-page .lg-actions button[type=submit],
.linkgo-page .lg-actions .btn.primary{
    appearance:none!important;cursor:pointer;
    height:44px!important;padding:0 26px!important;
    border:0!important;border-radius:100px!important;
    background:var(--lg-primary)!important;color:var(--lg-on-primary)!important;
    font-size:14px!important;font-weight:600!important;letter-spacing:.2px;
    box-shadow:var(--lg-elev-1);
    transition:box-shadow .18s ease,transform .18s ease;
}
.linkgo-page .lg-actions input[type=submit]:hover,
.linkgo-page .lg-actions button[type=submit]:hover,
.linkgo-page .lg-actions .btn.primary:hover{
    box-shadow:var(--lg-elev-2);transform:translateY(-1px);
}

/* --- 主题预览 --- */
.linkgo-page .lg-preview-card{position:sticky;top:76px}
@media (max-width:1023px){.linkgo-page .lg-preview-card{position:static}}
.linkgo-page .lg-themes{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.linkgo-page .lg-theme{
    appearance:none;cursor:pointer;
    display:inline-flex;align-items:center;gap:7px;
    height:32px;padding:0 13px;border-radius:100px;
    border:1px solid var(--lg-outline-variant);
    background:var(--lg-surface-lowest);color:var(--lg-on-surface);
    font-size:12.5px;font-weight:500;
    transition:background .18s ease,border-color .18s ease;
}
.linkgo-page .lg-theme:hover{background:color-mix(in srgb,var(--lg-on-surface) 6%,var(--lg-surface-lowest))}
.linkgo-page .lg-theme.is-active{
    background:var(--lg-secondary-container);border-color:transparent;
    color:var(--lg-on-secondary-container);font-weight:600;
}
.linkgo-page .lg-theme__dot{width:9px;height:9px;border-radius:50%;box-shadow:0 0 0 2px color-mix(in srgb,#fff 60%,transparent)}
.linkgo-page .lg-preview-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;flex-wrap:wrap}
.linkgo-page .lg-preview-name{font-size:12.5px;color:var(--lg-on-surface-variant)}
.linkgo-page .lg-seg{display:inline-flex;padding:3px;border-radius:100px;background:var(--lg-surface-high);gap:2px}
.linkgo-page .lg-seg__btn{
    appearance:none;cursor:pointer;border:0;background:transparent;
    height:28px;padding:0 13px;border-radius:100px;
    font-size:12px;font-weight:600;color:var(--lg-on-surface-variant);
    transition:background .18s ease,color .18s ease;
}
.linkgo-page .lg-seg__btn.is-active{background:var(--lg-surface-lowest);color:var(--lg-on-surface);box-shadow:var(--lg-elev-1)}
.linkgo-page .lg-frame{
    --lg-preview-scale:.36;
    position:relative;
    aspect-ratio:1280/800;
    border-radius:20px;overflow:hidden;
    border:1px solid var(--lg-outline-variant);
    background:var(--lg-surface-container);
    transition:background .2s ease;
}
.linkgo-page .lg-frame__stage{
    position:absolute;left:0;top:0;
    width:1280px;height:800px;
    transform:scale(var(--lg-preview-scale));
    transform-origin:top left;
    will-change:transform;
}
.linkgo-page .lg-frame__iframe{display:block;width:1280px;height:800px;border:0;background:#fff}
.linkgo-page .lg-frame.is-mobile{
    aspect-ratio:auto;height:600px;
    display:flex;justify-content:center;padding:14px;
    background:var(--lg-surface-high);
}
.linkgo-page .lg-frame.is-mobile .lg-frame__stage{position:static;width:auto;height:100%;transform:none}
.linkgo-page .lg-frame.is-mobile .lg-frame__iframe{
    width:390px;max-width:100%;height:100%;border-radius:24px;
    border:1px solid var(--lg-outline);
    box-shadow:0 10px 28px rgba(0,0,0,.16);
}
.linkgo-page .lg-frame__loading{
    position:absolute;inset:0;z-index:2;
    display:flex;align-items:center;justify-content:center;gap:10px;
    background:var(--lg-surface-container);
    color:var(--lg-on-surface-variant);font-size:13px;
}
.linkgo-page .lg-spinner{
    width:18px;height:18px;border-radius:50%;
    border:2px solid color-mix(in srgb,var(--lg-primary) 25%,transparent);
    border-top-color:var(--lg-primary);
    animation:lg-spin .8s linear infinite;
}
@keyframes lg-spin{to{transform:rotate(360deg)}}
.linkgo-page .lg-preview-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:10px}
.linkgo-page .lg-preview-tip{font-size:12px;color:var(--lg-on-surface-variant)}
.linkgo-page .lg-text-btn{
    display:inline-flex;align-items:center;gap:6px;
    font-size:12.5px;font-weight:600;color:var(--lg-primary);
    text-decoration:none!important;border-radius:8px;padding:4px 6px;
}
.linkgo-page .lg-text-btn:hover{background:color-mix(in srgb,var(--lg-primary) 10%,transparent);color:var(--lg-primary)}
.linkgo-page .lg-text-btn svg{width:15px;height:15px}

/* 小屏 */
@media (max-width:640px){
    .linkgo-page .lg-hero{padding:18px;border-radius:22px}
    .linkgo-page .lg-card{padding:16px;border-radius:20px}
    .linkgo-page .lg-frame{height:480px}
}
@media (prefers-reduced-motion:reduce){
    .linkgo-page *{animation-duration:.001ms!important;transition-duration:.001ms!important}
}
</style>
LG_CSS;

        $script = <<<'LG_JS'
<script>
(function () {
    var THEMES = __LINKGO_THEMES_JSON__;
    var PREVIEW_BASE = __LINKGO_PREVIEW_BASE__;

    function ico(paths, size) {
        return '<svg viewBox="0 0 24 24" width="' + (size || 20) + '" height="' + (size || 20) + '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + paths + '</svg>';
    }

    var ICONS = {
        link: ico('<path d="M10 13a5 5 0 0 0 7.07 0l1.66-1.66a5 5 0 0 0-7.07-7.07L10.5 5.43"/><path d="M14 11a5 5 0 0 0-7.07 0L5.27 12.66a5 5 0 0 0 7.07 7.07l1.16-1.16"/>', 32),
        palette: ico('<path d="M9.5 14.5 3 21l6.5-1.5"/><path d="M14 6.5 17.5 3 21 6.5 17.5 10"/><path d="m14 6.5 3.5 3.5L9.5 18a3.5 3.5 0 0 1-4.95-4.95Z"/>'),
        tune: ico('<path d="M4 6h9"/><path d="M17 6h3"/><circle cx="15" cy="6" r="2"/><path d="M4 12h3"/><path d="M11 12h9"/><circle cx="9" cy="12" r="2"/><path d="M4 18h9"/><path d="M17 18h3"/><circle cx="15" cy="18" r="2"/>'),
        rule: ico('<path d="M9 6h11"/><path d="M4 6h1.5"/><path d="M9 12h11"/><path d="M4 12h1.5"/><path d="M9 18h11"/><path d="M4 18h1.5"/>'),
        monitor: ico('<rect x="3" y="4" width="18" height="13" rx="2.5"/><path d="M8 21h8"/><path d="M12 17v4"/>'),
        external: ico('<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>', 15),
        shield: ico('<path d="M12 3 5 6v5c0 4.6 3 8.4 7 10 4-1.6 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>', 16),
        github: ico('<path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 4.6-1.9 4.6-5.1a4 4 0 0 0-1.1-2.8 3.7 3.7 0 0 0-.1-2.8s-1.2-.4-3.9 1.5a13 13 0 0 0-6.8 0C4.1 3.4 2.9 3.8 2.9 3.8a3.7 3.7 0 0 0-.1 2.8A4 4 0 0 0 1.7 9.4c0 3.2 1.6 5.1 4.6 5.1a4.8 4.8 0 0 0-1 3.5v4"/><path d="M9 19c-3 .8-3-1.5-4-2"/>', 16),
        book: ico('<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5V5.5Z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20v3H6.5"/>', 16)
    };

    var SECTIONS = [
        {icon: ICONS.palette, title: '跳转页外观', desc: '设置提示页的站点标题、Logo、页脚年份、主题与主题色。', fields: ['siteTitle', 'logoUrl', 'startYear', 'themeName', 'themeColor']},
        {icon: ICONS.tune, title: '跳转行为', desc: '控制外链改写方式、&lt;a&gt; 标签属性与跳转页 SEO。', fields: ['openInNewTab', 'enableClientRewrite', 'skipLinksPluginUrls', 'linkRelMode', 'linkTitleMode', 'linkClassName', 'addDataAttr', 'showFullUrl', 'showLogo', 'robotsPolicy', 'sendNoindexHeader']},
        {icon: ICONS.rule, title: '网址白名单', desc: '命中规则的外链保持原样，不会被改写成 /go/ 跳转地址。', fields: ['urlWhitelist']}
    ];

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var page = document.querySelector('.typecho-page-main');
        if (!page) { return; }

        var form = page.querySelector('form');
        if (!form) { return; }

        page.classList.add('linkgo-page');
        form.classList.add('lg-form');

        // 让设置页占满内容区（Typecho 栅格是 flex，必须内联 !important 才能盖过 8 列限制）
        Array.prototype.forEach.call(page.children, function (child) {
            if (!child || !child.classList) { return; }
            if (/(^|\s)col-/.test(child.className)) {
                child.style.setProperty('width', '100%', 'important');
                child.style.setProperty('max-width', '100%', 'important');
                child.style.setProperty('flex', '0 0 100%', 'important');
                child.style.setProperty('margin-left', '0', 'important');
                child.style.setProperty('float', 'none', 'important');
            }
        });

        /* 1) 收集字段 */
        var fields = {};
        var submitUl = null;
        Array.prototype.forEach.call(form.querySelectorAll('ul.typecho-option'), function (ul) {
            if (ul.querySelector('input[type=submit],button[type=submit]')) {
                submitUl = ul;
                return;
            }
            var named = ul.querySelector('[name]');
            if (!named) { return; }
            fields[named.name] = ul;
            ul.classList.add('lg-field');
            ul.setAttribute('data-field', named.name);
        });

        /* 2) 外壳与页头 */
        var shell = document.createElement('div');
        shell.className = 'lg-shell';
        form.parentNode.insertBefore(shell, form);
        shell.appendChild(form);

        var hero = document.createElement('div');
        hero.className = 'lg-hero';
        hero.innerHTML =
            '<div class="lg-hero__logo">' + ICONS.link + '</div>' +
            '<div class="lg-hero__text">' +
                '<h1 class="lg-hero__title">LinkGo <span class="lg-hero__version">v__LINKGO_VERSION__</span></h1>' +
                '<p class="lg-hero__sub">外部链接安全跳转 · 中间提示页 · 支持自定义主题与白名单</p>' +
            '</div>' +
            '<div class="lg-hero__actions">' +
                '<a class="lg-chip" href="https://github.com/lhl77/Typecho-Plugin-LinkGo" target="_blank" rel="noopener noreferrer">' + ICONS.github + 'GitHub 仓库</a>' +
                '<a class="lg-chip" href="https://blog.lhl.one/artical/949.html" target="_blank" rel="noopener noreferrer">' + ICONS.book + '主题开发文档</a>' +
            '</div>';
        shell.insertBefore(hero, form);

        /* 3) 两栏布局 */
        var layout = document.createElement('div');
        layout.className = 'lg-layout';
        var main = document.createElement('div');
        main.className = 'lg-main';
        var aside = document.createElement('aside');
        aside.className = 'lg-aside';
        layout.appendChild(main);
        layout.appendChild(aside);
        form.appendChild(layout);

        function cardHead(icon, title, desc) {
            return '<div class="lg-card__head"><span class="lg-card__icon">' + icon + '</span>' +
                '<div><div class="lg-card__title">' + title + '</div>' +
                '<div class="lg-card__desc">' + desc + '</div></div></div>';
        }

        var used = {};
        SECTIONS.forEach(function (sec) {
            var card = document.createElement('section');
            card.className = 'lg-card';
            card.innerHTML = cardHead(sec.icon, sec.title, sec.desc) + '<div class="lg-card__body"></div>';
            var body = card.querySelector('.lg-card__body');
            var count = 0;

            sec.fields.forEach(function (name) {
                if (fields[name]) {
                    body.appendChild(fields[name]);
                    used[name] = true;
                    count++;
                }
            });

            if (count > 0) {
                main.appendChild(card);
            }
        });

        /* 未归类的字段（兼容未来新增） */
        var rest = [];
        Object.keys(fields).forEach(function (name) {
            if (!used[name]) { rest.push(fields[name]); }
        });
        if (rest.length) {
            var more = document.createElement('section');
            more.className = 'lg-card';
            more.innerHTML = cardHead(ICONS.tune, '更多设置', '暂未归类的插件配置项。') + '<div class="lg-card__body"></div>';
            var moreBody = more.querySelector('.lg-card__body');
            rest.forEach(function (ul) { moreBody.appendChild(ul); });
            main.appendChild(more);
        }

        /* 4) 保存动作条 */
        if (submitUl) {
            var actions = document.createElement('div');
            actions.className = 'lg-actions';
            actions.innerHTML = '<div class="lg-actions__hint">' + ICONS.shield + '<span>修改后请保存设置</span></div>';
            actions.appendChild(submitUl);
            main.appendChild(actions);
        }

        /* 5) 单选按钮 → MD3 选择胶囊 */
        function syncRadios() {
            Array.prototype.forEach.call(main.querySelectorAll('.lg-choice'), function (item) {
                var radio = item.querySelector('input[type=radio]');
                if (radio) { item.classList.toggle('is-checked', radio.checked); }
            });
        }

        Array.prototype.forEach.call(main.querySelectorAll('ul.typecho-option li > span'), function (item) {
            if (item.querySelector('input[type=radio]')) { item.classList.add('lg-choice'); }
        });
        syncRadios();
        form.addEventListener('change', function (e) {
            if (e.target && e.target.type === 'radio') { syncRadios(); }
        });

        /* 主题色：文本输入 + 原生取色器 */
        var colorInput = form.querySelector('input[name="themeColor"]');
        if (colorInput && !colorInput.closest('.lg-color-field')) {
            var isHexColor = function (value) {
                return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value || '');
            };
            var normalizeHex = function (value) {
                value = String(value || '');
                if (!isHexColor(value)) { return ''; }
                if (value.length === 4) {
                    value = '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
                }
                return value.toUpperCase();
            };
            var colorField = document.createElement('div');
            colorField.className = 'lg-color-field';
            colorInput.parentNode.insertBefore(colorField, colorInput);
            colorField.appendChild(colorInput);

            var colorPicker = document.createElement('input');
            colorPicker.type = 'color';
            colorPicker.className = 'lg-color-picker';
            colorPicker.setAttribute('aria-label', '选择主题色');
            var pickerSeed = '#6750A4';
            var currentThemeSelect = form.querySelector('select[name="themeName"]');
            var currentThemeName = currentThemeSelect ? currentThemeSelect.value : '';
            for (var ti = 0; ti < THEMES.length; ti++) {
                if (THEMES[ti].name === currentThemeName && /^#[0-9a-f]{3,6}$/i.test(THEMES[ti].accent || '')) {
                    pickerSeed = THEMES[ti].accent;
                    break;
                }
            }
            colorPicker.value = normalizeHex(colorInput.value) || normalizeHex(pickerSeed) || '#6750A4';
            colorField.appendChild(colorPicker);

            colorPicker.addEventListener('input', function () {
                colorInput.value = colorPicker.value.toUpperCase();
                colorInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
            colorInput.addEventListener('input', function () {
                var hex = normalizeHex(colorInput.value);
                if (hex) { colorPicker.value = hex; }
            });
        }

        /* 6) 白名单示例 */
        var whitelist = form.querySelector('textarea[name="urlWhitelist"], textarea#urlWhitelist');
        if (whitelist && !document.querySelector('.lg-examples')) {
            var sample = [
                '# 常见规则',
                'github.com',
                'gitee.com',
                '',
                '# 页脚常用',
                'beian.miit.gov.cn',
                'ipw.cn/ipv6webcheck',
                'typecho.org',
                '',
                '# 通配符示例',
                '*.lhl.one'
            ].join('\n');

            function toast(msg) {
                var t = document.createElement('div');
                t.textContent = msg;
                t.style.cssText = 'position:fixed;right:18px;bottom:18px;z-index:99999;padding:12px 16px;border-radius:14px;background:#1D1B20;color:#F5EFF7;font-size:13px;box-shadow:0 8px 24px rgba(0,0,0,.28);opacity:0;transform:translateY(8px);transition:opacity .2s ease,transform .2s ease';
                document.body.appendChild(t);
                setTimeout(function () { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; }, 20);
                setTimeout(function () { t.style.opacity = '0'; t.style.transform = 'translateY(8px)'; }, 1600);
                setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 1900);
            }

            function copyText(text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text);
                }
                return new Promise(function (resolve, reject) {
                    try {
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.setAttribute('readonly', '');
                        ta.style.cssText = 'position:fixed;left:-9999px;top:0';
                        document.body.appendChild(ta);
                        ta.select();
                        var ok = document.execCommand('copy');
                        document.body.removeChild(ta);
                        ok ? resolve() : reject(new Error('copy failed'));
                    } catch (err) {
                        reject(err);
                    }
                });
            }

            var box = document.createElement('div');
            box.className = 'lg-examples';
            box.innerHTML =
                '<div class="ex-title">常见需求示例</div>' +
                '<div class="ex-desc">这些规则会让命中的外链保持原样（不改写成 /go/xxx），可直接复制或追加到白名单。</div>' +
                '<div class="ex-actions">' +
                    '<button type="button" class="ex-btn" data-act="copy">复制示例</button>' +
                    '<button type="button" class="ex-btn" data-act="append">追加到白名单</button>' +
                '</div>' +
                '<pre><code></code></pre>';

            box.querySelector('code').textContent = sample;
            var whitelistField = whitelist.closest ? whitelist.closest('ul.typecho-option') : null;
            if (whitelistField) {
                whitelistField.insertAdjacentElement('afterend', box);
            } else {
                whitelist.insertAdjacentElement('afterend', box);
            }

            // 白名单的规则说明较长，折叠为「规则说明」面板
            var desc = whitelistField ? whitelistField.querySelector('.description') : null;
            if (desc && desc.innerHTML && desc.innerHTML.replace(/<[^>]+>/g, '').length > 160) {
                var details = document.createElement('details');
                details.className = 'lg-help';
                var summary = document.createElement('summary');
                summary.textContent = '规则说明与示例写法';
                var helpBody = document.createElement('div');
                helpBody.className = 'lg-help__body';
                helpBody.innerHTML = desc.innerHTML;
                details.appendChild(summary);
                details.appendChild(helpBody);
                desc.parentNode.replaceChild(details, desc);
            }

            box.addEventListener('click', function (e) {
                var btn = e.target && e.target.closest ? e.target.closest('button[data-act]') : null;
                if (!btn) { return; }

                if (btn.getAttribute('data-act') === 'copy') {
                    copyText(sample).then(function () {
                        toast('已复制示例规则');
                    }).catch(function () {
                        toast('复制失败，请手动选择复制');
                    });
                    return;
                }

                var current = String(whitelist.value || '').replace(/\s+$/, '');
                whitelist.value = current ? (current + '\n\n' + sample) : sample;
                whitelist.dispatchEvent(new Event('input', { bubbles: true }));
                toast('已追加到白名单');
            });
        }

        /* 7) 主题预览 */
        var themeSelect = form.querySelector('select[name="themeName"]');
        var themesWrap = document.createElement('section');
        themesWrap.className = 'lg-card lg-preview-card';
        themesWrap.innerHTML =
            cardHead(ICONS.monitor, '跳转页主题预览', '实时渲染所选主题的跳转提示页，示例链接不会真的跳转。') +
            '<div class="lg-card__body">' +
                '<div class="lg-themes" role="group" aria-label="选择主题"></div>' +
                '<div class="lg-preview-bar">' +
                    '<span class="lg-preview-name"></span>' +
                    '<span class="lg-seg" role="group" aria-label="预览尺寸">' +
                        '<button type="button" class="lg-seg__btn is-active" data-mode="desktop">桌面</button>' +
                        '<button type="button" class="lg-seg__btn" data-mode="mobile">手机</button>' +
                    '</span>' +
                '</div>' +
                '<div class="lg-frame">' +
                    '<div class="lg-frame__loading"><span class="lg-spinner"></span><span>正在加载预览…</span></div>' +
                    '<div class="lg-frame__stage"><iframe class="lg-frame__iframe" title="跳转页主题预览" loading="lazy" referrerpolicy="no-referrer"></iframe></div>' +
                '</div>' +
                '<div class="lg-preview-foot">' +
                    '<span class="lg-preview-tip">预览使用示例链接</span>' +
                    '<a class="lg-text-btn" target="_blank" rel="noopener noreferrer">新窗口打开' + ICONS.external + '</a>' +
                '</div>' +
            '</div>';
        aside.appendChild(themesWrap);

        var chips = themesWrap.querySelector('.lg-themes');
        var frame = themesWrap.querySelector('.lg-frame');
        var iframe = themesWrap.querySelector('.lg-frame__iframe');
        var loading = themesWrap.querySelector('.lg-frame__loading');
        var nameEl = themesWrap.querySelector('.lg-preview-name');
        var openLink = themesWrap.querySelector('.lg-text-btn');

        // 桌面预览：iframe 按 1280×800 渲染，再等比缩放塞进卡片，才能在小区域里看到真正的桌面布局
        var PREVIEW_STAGE_W = 1280;
        function fitPreview() {
            if (!frame) { return; }
            var w = frame.clientWidth || 0;
            if (w <= 0) { return; }
            var scale = w / PREVIEW_STAGE_W;
            if (scale > 1) { scale = 1; }
            frame.style.setProperty('--lg-preview-scale', scale.toFixed(4));
        }
        window.addEventListener('resize', fitPreview);
        if (typeof ResizeObserver === 'function') {
            try { new ResizeObserver(fitPreview).observe(frame); } catch (e) { /* ignore */ }
        }
        fitPreview();

        var list = (THEMES && THEMES.length) ? THEMES : [];
        if (!list.length && themeSelect) {
            Array.prototype.forEach.call(themeSelect.options, function (opt) {
                list.push({ name: opt.value, accent: '#CAC4D0' });
            });
        }

        list.forEach(function (theme) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lg-theme';
            btn.setAttribute('data-theme', theme.name);
            var dot = document.createElement('span');
            dot.className = 'lg-theme__dot';
            dot.style.background = theme.accent || '#CAC4D0';
            btn.appendChild(dot);
            btn.appendChild(document.createTextNode(theme.name));
            btn.addEventListener('click', function () {
                applyTheme(theme.name, true);
            });
            chips.appendChild(btn);
        });

        var PREVIEW_SYNC_FIELDS = ['siteTitle', 'logoUrl', 'startYear', 'showFullUrl', 'showLogo', 'robotsPolicy', 'themeColor'];

        function fieldValue(name) {
            var el = form.querySelector('[name="' + name + '"]');
            if (!el) { return ''; }
            if (el.type === 'radio') {
                var checked = form.querySelector('[name="' + name + '"]:checked');
                return checked ? checked.value : '';
            }
            return el.value || '';
        }

        function previewUrl(name) {
            if (!PREVIEW_BASE) { return ''; }
            var parts = ['linkgo_preview=' + encodeURIComponent(name)];
            PREVIEW_SYNC_FIELDS.forEach(function (field) {
                var value = fieldValue(field);
                // 防止 URL 过长（预览请求上限 4096）
                if (field === 'logoUrl' && value.length > 600) { return; }
                if (field === 'siteTitle' && value.length > 200) { value = value.slice(0, 200); }
                // 主题色始终上传（空值表示恢复主题默认）
                if (field === 'themeColor') {
                    parts.push('lg_themeColor=' + encodeURIComponent(value));
                    return;
                }
                if (value !== '') {
                    parts.push('lg_' + field + '=' + encodeURIComponent(value));
                }
            });
            return PREVIEW_BASE + '?' + parts.join('&');
        }

        function applyTheme(name, load) {
            name = name || (themeSelect && themeSelect.value) || (list[0] && list[0].name) || 'Default';

            if (themeSelect && themeSelect.value !== name) {
                themeSelect.value = name;
            }

            Array.prototype.forEach.call(chips.children, function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-theme') === name);
            });

            nameEl.textContent = '当前主题 · ' + name;

            var url = previewUrl(name);
            if (openLink) {
                if (url) {
                    openLink.href = url;
                    openLink.style.display = '';
                } else {
                    openLink.style.display = 'none';
                }
            }

            if (load && iframe) {
                if (!url) {
                    iframe.removeAttribute('src');
                    loading.textContent = '未获取到站点地址，暂时无法加载预览';
                    return;
                }

                loading.style.display = 'flex';
                loading.innerHTML = '<span class="lg-spinner"></span><span>正在加载预览…</span>';

                iframe.addEventListener('load', function onLoad() {
                    iframe.removeEventListener('load', onLoad);
                    loading.style.display = 'none';
                });

                iframe.setAttribute('src', url);
            }
        }

        Array.prototype.forEach.call(themesWrap.querySelectorAll('.lg-seg__btn'), function (btn) {
            btn.addEventListener('click', function () {
                Array.prototype.forEach.call(themesWrap.querySelectorAll('.lg-seg__btn'), function (b) {
                    b.classList.toggle('is-active', b === btn);
                });
                frame.classList.toggle('is-mobile', btn.getAttribute('data-mode') === 'mobile');
                fitPreview();
            });
        });

        if (themeSelect) {
            themeSelect.addEventListener('change', function () {
                applyTheme(themeSelect.value, true);
            });
        }

        // 表单当前值变化时实时刷新预览（无需先保存）
        var previewTimer = null;
        function schedulePreview() {
            clearTimeout(previewTimer);
            previewTimer = setTimeout(function () {
                applyTheme(themeSelect ? themeSelect.value : '', true);
            }, 450);
        }

        function onPreviewFieldChange(e) {
            if (!e.target || !e.target.name) { return; }
            if (PREVIEW_SYNC_FIELDS.indexOf(e.target.name) !== -1) {
                schedulePreview();
            }
        }

        form.addEventListener('input', onPreviewFieldChange);
        form.addEventListener('change', onPreviewFieldChange);

        applyTheme(themeSelect ? themeSelect.value : '', true);
        fitPreview();
    });
})();
</script>
LG_JS;

        $script = str_replace(
            array('__LINKGO_THEMES_JSON__', '__LINKGO_PREVIEW_BASE__', '__LINKGO_VERSION__'),
            array($themesJson, json_encode($previewBase), self::VERSION),
            $script
        );

        echo $script;
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 解析白名单规则（逐行）。
     * 支持：
     * - 精准 URL：https://example.com/a?b=1
     * - 域名：example.com
     * - 域名通配：*.example.com（匹配 example.com 及其子域）
     * - 路径前缀：example.com/path/ 或 *.example.com/path
     */
    private static function parseWhitelistRules($raw)
    {
        $out = ['exact' => [], 'host' => [], 'hostPath' => []];
        if (!is_string($raw) || trim($raw) === '') {
            return $out;
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '' || self::startsWith($line, '#') || self::startsWith($line, '//')) {
                continue;
            }

            // 移除行尾注释
            $line = preg_replace('/\s+(#|\/\/).*$/', '', $line);
            $line = trim((string)$line);
            if ($line === '') continue;

            // 含 scheme 的规则：先解析判断是域名级还是精确 URL
            if (preg_match('#^https?://#i', $line)) {
                $parsed = parse_url($line);
                $pHost = isset($parsed['host']) ? strtolower(rtrim($parsed['host'], '.')) : '';
                $pPath = isset($parsed['path']) ? $parsed['path'] : '';
                $hasQuery = isset($parsed['query']);
                $hasFrag  = isset($parsed['fragment']);

                // 路径为空或仅"/"且无 query/fragment → 当作域名匹配
                if ($pHost !== '' && ($pPath === '' || $pPath === '/') && !$hasQuery && !$hasFrag) {
                    $out['host'][] = $pHost;
                    continue;
                }

                // 有具体路径但无 query/fragment → 当作 hostPath 前缀匹配
                if ($pHost !== '' && $pPath !== '' && $pPath !== '/' && !$hasQuery && !$hasFrag) {
                    $out['hostPath'][] = [$pHost, strtolower($pPath)];
                    continue;
                }

                // 其余（含 query/fragment）→ 精确匹配，规范化尾部斜杠
                $out['exact'][] = strtolower(rtrim($line, '/'));
                continue;
            }

            // host 或 host/path 前缀（无 scheme）
            $line = preg_replace('#^//#', '', $line);
            $line = strtolower($line);

            $slashPos = strpos($line, '/');
            if ($slashPos === false) {
                $out['host'][] = rtrim($line, '.');
            } else {
                $host = substr($line, 0, $slashPos);
                $path = substr($line, $slashPos);
                if ($host !== '' && $path !== '') {
                    $out['hostPath'][] = [rtrim($host, '.'), $path];
                }
            }
        }

        return $out;
    }

    /**
     * host 匹配：支持 *.example.com / .example.com 与精确 example.com
     */
    private static function hostMatch($host, $ruleHost)
    {
        $host = strtolower((string)$host);
        $ruleHost = strtolower((string)$ruleHost);
        if ($host === '' || $ruleHost === '') return false;

        if (self::startsWith($ruleHost, '*.')) {
            $base = substr($ruleHost, 2);
            return $base !== '' && ($host === $base || self::endsWith($host, '.' . $base));
        }

        if (self::startsWith($ruleHost, '.')) {
            $base = substr($ruleHost, 1);
            return $base !== '' && ($host === $base || self::endsWith($host, '.' . $base));
        }

        return $host === $ruleHost;
    }

    private static function getPluginOptions()
    {
        try {
            return Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
        } catch (Exception $e) {
            return null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function isEnabledOption($pluginOptions, $key, $default = false)
    {
        if (!$pluginOptions || !isset($pluginOptions->{$key})) {
            return (bool)$default;
        }

        return (string)$pluginOptions->{$key} === '1';
    }

    private static function normalizeComparableUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower((string)$parts['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return '';
        }

        $host = strtolower(rtrim((string)$parts['host'], '.'));
        if ($host === '') {
            return '';
        }

        $port = isset($parts['port']) ? (int)$parts['port'] : 0;
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = 0;
        }

        $path = isset($parts['path']) ? (string)$parts['path'] : '/';
        if ($path === '') {
            $path = '/';
        }

        $query = isset($parts['query']) ? ('?' . (string)$parts['query']) : '';
        $fragment = isset($parts['fragment']) ? ('#' . (string)$parts['fragment']) : '';

        if ($query === '' && $fragment === '') {
            $path = rtrim($path, '/');
            if ($path === '') {
                $path = '/';
            }
        }

        return $scheme . '://' . $host . ($port > 0 ? ':' . $port : '') . $path . $query . $fragment;
    }

    private static function getLinksPluginUrlMap()
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = array();

        try {
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            $rows = $db->fetchAll(
                $db->select('url')
                    ->from($prefix . 'links')
                    ->where('state = ?', 1)
            );

            if (is_array($rows)) {
                foreach ($rows as $row) {
                    $normalized = self::normalizeComparableUrl(isset($row['url']) ? $row['url'] : '');
                    if ($normalized !== '') {
                        $cache[$normalized] = true;
                    }
                }
            }
        } catch (Exception $e) {
            $cache = array();
        } catch (Throwable $e) {
            $cache = array();
        }

        return $cache;
    }

    private static function isLinksPluginManagedUrl($url, $pluginOptions = null)
    {
        if ($pluginOptions === null) {
            $pluginOptions = self::getPluginOptions();
        }

        if (!self::isEnabledOption($pluginOptions, 'skipLinksPluginUrls', false)) {
            return false;
        }

        $normalized = self::normalizeComparableUrl($url);
        if ($normalized === '') {
            return false;
        }

        $urlMap = self::getLinksPluginUrlMap();
        return isset($urlMap[$normalized]);
    }

    private static function shouldBypassExternalUrl($url, $pluginOptions = null)
    {
        return self::isWhitelistedUrl($url, $pluginOptions) || self::isLinksPluginManagedUrl($url, $pluginOptions);
    }

    private static function protectSkipZones($content, array &$protected)
    {
        $protected = array();
        $patterns = array(
            '/<pre\b[^>]*>.*?<\/pre>/is',
            '/<code\b[^>]*>.*?<\/code>/is',
        );

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback(
                $pattern,
                function ($matches) use (&$protected) {
                    $token = '<!--LINKGO_SKIP_' . count($protected) . '-->';
                    $protected[$token] = $matches[0];
                    return $token;
                },
                $content
            );
        }

        return $content;
    }

    private static function restoreSkipZones($content, array $protected)
    {
        if (empty($protected)) {
            return $content;
        }

        return strtr($content, $protected);
    }

    /**
     * 读取「外链属性」相关设置（rel / title / class / data 标记）。
     */
    private static function getLinkAttributeSettings($pluginOptions)
    {
        $relMode = isset($pluginOptions->linkRelMode) ? (string)$pluginOptions->linkRelMode : 'noopener noreferrer';
        $relMap = array(
            'none' => array(),
            'noopener' => array('noopener'),
            'noopener noreferrer' => array('noopener', 'noreferrer'),
            'nofollow noopener noreferrer' => array('nofollow', 'noopener', 'noreferrer'),
            'sponsored nofollow noopener noreferrer' => array('sponsored', 'nofollow', 'noopener', 'noreferrer'),
        );

        $titleMode = isset($pluginOptions->linkTitleMode) ? (string)$pluginOptions->linkTitleMode : 'host';
        if (!in_array($titleMode, array('none', 'host', 'full'), true)) {
            $titleMode = 'host';
        }

        return array(
            'rel' => isset($relMap[$relMode]) ? $relMap[$relMode] : $relMap['noopener noreferrer'],
            'title' => $titleMode,
            'class' => self::normalizeLinkClassName(isset($pluginOptions->linkClassName) ? $pluginOptions->linkClassName : ''),
            'data' => self::isEnabledOption($pluginOptions, 'addDataAttr', true),
        );
    }

    /**
     * 清洗自定义 class（最多 5 个，只允许字母/数字/下划线/中划线）。
     */
    private static function normalizeLinkClassName($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return '';
        }

        $out = array();
        foreach (preg_split('/\s+/', $raw) as $part) {
            if (preg_match('/^[A-Za-z0-9_\-]{1,40}$/', $part)) {
                $out[] = $part;
            }
            if (count($out) >= 5) {
                break;
            }
        }

        return implode(' ', $out);
    }

    /**
     * 给改写后的 <a> 追加 rel / title / class / data 属性。
     */
    private static function applyLinkAttributes($attrs, $href, array $settings)
    {
        // rel：与作者原有的 rel 合并去重
        $existingRel = array();
        if (preg_match('/\s+rel\s*=\s*("|\')([^"\']*)\1/i', $attrs, $m)) {
            $existingRel = array_filter(preg_split('/\s+/', strtolower(trim($m[2]))));
        }
        $attrs = preg_replace('/\s+rel\s*=\s*("|\')[^"\']*\1/i', '', $attrs);
        $rel = array_values(array_unique(array_merge($existingRel, $settings['rel'])));
        if (!empty($rel)) {
            $attrs .= ' rel="' . htmlspecialchars(implode(' ', $rel), ENT_QUOTES) . '"';
        }

        // title：作者已写 title 时不覆盖
        if ($settings['title'] !== 'none' && !preg_match('/\s+title\s*=/i', $attrs)) {
            $host = parse_url($href, PHP_URL_HOST);
            $titleValue = $settings['title'] === 'full' ? $href : ($host ?: $href);
            if ($titleValue !== '') {
                $attrs .= ' title="' . htmlspecialchars('即将离开本站，前往 ' . $titleValue, ENT_QUOTES) . '"';
            }
        }

        // class：追加到已有 class
        if ($settings['class'] !== '') {
            $existingClass = '';
            if (preg_match('/\s+class\s*=\s*("|\')([^"\']*)\1/i', $attrs, $cm)) {
                $existingClass = trim($cm[2]);
                $attrs = preg_replace('/\s+class\s*=\s*("|\')[^"\']*\1/i', '', $attrs);
            }
            $classValue = trim($existingClass . ' ' . $settings['class']);
            if ($classValue !== '') {
                $attrs .= ' class="' . htmlspecialchars($classValue, ENT_QUOTES) . '"';
            }
        }

        // data-linkgo="1"
        if ($settings['data'] && !preg_match('/\s+data-linkgo\s*=/i', $attrs)) {
            $attrs .= ' data-linkgo="1"';
        }

        return $attrs;
    }

    private static function rewriteHtmlAnchors($content, $siteHost, $siteUrl, $pluginOptions)
    {
        if (!is_string($content) || $content === '') {
            return $content;
        }

        $protected = array();
        $content = self::protectSkipZones($content, $protected);

        // 「外部链接打开方式」设置：改写后的外链是否在新标签页打开
        $openNewTab = self::isEnabledOption($pluginOptions, 'openInNewTab', true);
        $linkSettings = self::getLinkAttributeSettings($pluginOptions);

        $content = preg_replace_callback(
            '/<a\s+([^>]*?)href=("|\')(.*?)\2([^>]*)>/i',
            function ($matches) use ($siteHost, $siteUrl, $pluginOptions, $openNewTab, $linkSettings) {
                $beforeAttrs = $matches[1];
                $href = $matches[3];
                $afterAttrs = $matches[4];

                if (empty($href)) {
                    return $matches[0];
                }

                $targetHost = parse_url($href, PHP_URL_HOST);
                $isExternal = $targetHost && strcasecmp($targetHost, $siteHost) !== 0;

                if (!$isExternal) {
                    return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . '>';
                }

                if (self::shouldBypassExternalUrl($href, $pluginOptions)) {
                    return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . '>';
                }

                $encodedUrl = rtrim(strtr(base64_encode($href), '+/', '-_'), '=');
                $newHref = rtrim($siteUrl, '/') . '/go/' . $encodedUrl;

                if ($openNewTab) {
                    $afterAttrs = preg_replace('/\s+target\s*=\s*("|\')[^"\']*\1/i', '', $afterAttrs);
                    $afterAttrs .= ' target="_blank"';
                }

                $afterAttrs = self::applyLinkAttributes($afterAttrs, $href, $linkSettings);

                return '<a ' . $beforeAttrs . 'href="' . $newHref . '"' . $afterAttrs . '>';
            },
            $content
        );

        return self::restoreSkipZones($content, $protected);
    }

    /**
     * 判断 URL 是否命中白名单。
     */
    public static function isWhitelistedUrl($url, $pluginOptions = null)
    {
        if (!is_string($url) || $url === '') return false;
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;

        $p = parse_url($url);
        if (!$p || empty($p['scheme']) || empty($p['host'])) return false;

        $scheme = strtolower($p['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') return false;

        $host = strtolower($p['host']);
        $path = isset($p['path']) ? strtolower((string)$p['path']) : '/';
        $fullLower = strtolower($url);

        if ($pluginOptions === null) {
            try {
                $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
            } catch (Exception $e) {
                $pluginOptions = null;
            }
        }

        $raw = ($pluginOptions && isset($pluginOptions->urlWhitelist)) ? (string)$pluginOptions->urlWhitelist : '';
        $rules = self::parseWhitelistRules($raw);

        $fullNorm = rtrim($fullLower, '/');
        foreach ($rules['exact'] as $exact) {
            if ($exact !== '' && $fullNorm === $exact) return true;
        }

        foreach ($rules['hostPath'] as $hp) {
            [$ruleHost, $rulePath] = $hp;
            if ($ruleHost !== '' && $rulePath !== '' && self::hostMatch($host, $ruleHost) && self::startsWith($path, $rulePath)) {
                return true;
            }
        }

        foreach ($rules['host'] as $ruleHost) {
            if ($ruleHost !== '' && self::hostMatch($host, $ruleHost)) return true;
        }

        return false;
    }

    public static function convertLinks($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);
        $pluginOptions = self::getPluginOptions();

        // 调试：如果需要验证钩子是否被调用，取消下一行注释以把信息写入 PHP 错误日志
        // error_log('[LinkGo] convertLinks called for widget: ' . (is_object($widget) ? get_class($widget) : 'unknown'));

        return self::rewriteHtmlAnchors($content, $siteHost, $siteUrl, $pluginOptions);
    }

    public static function convertCommentLinks($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);
        $pluginOptions = self::getPluginOptions();

        // 调试日志（取消注释以启用）
        // error_log('[LinkGo] convertCommentLinks called for widget: ' . (is_object($widget) ? get_class($widget) : 'unknown'));

        return self::rewriteHtmlAnchors($content, $siteHost, $siteUrl, $pluginOptions);
    }

    public static function convertAuthorUrl($comment, $widget)
    {
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);

        // 调试：取消注释以记录评论数组，以确认钩子被触发
        // error_log('[LinkGo] convertAuthorUrl comment url: ' . (isset($comment['url']) ? $comment['url'] : ''));

        $url = isset($comment['url']) ? $comment['url'] : '';
        if (!empty($url)) {
            $targetHost = parse_url($url, PHP_URL_HOST);
            if ($targetHost && strcasecmp($targetHost, $siteHost) !== 0) {
                $pluginOptions = self::getPluginOptions();
                $openNew = self::isEnabledOption($pluginOptions, 'openInNewTab', true);

                if (self::shouldBypassExternalUrl($url, $pluginOptions)) {
                    return $comment;
                }

                $encodedUrl = rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
                // 仅把 URL 字段改为中间跳转地址（路径格式）
                $comment['url'] = rtrim($siteUrl, '/') . '/go/' . $encodedUrl;
            }
        }

        return $comment;
    }

    /**
     * 兜底：在 Archive 渲染后处理已渲染的内容
     * 许多主题会在渲染阶段做自定义输出，afterRender 是最后阶段的补充
     */
    public static function applyToArchive($archive)
    {
        if (isset($archive->content) && !empty($archive->content)) {
            $archive->content = self::convertLinks($archive->content, $archive, null);
        }

        if (isset($archive->excerpt) && !empty($archive->excerpt)) {
            $archive->excerpt = self::convertLinks($archive->excerpt, $archive, null);
        }

        return $archive;
    }

    // 开始输出缓冲
    public static function startBuffer()
    {
        if (!headers_sent() && !in_array('ob_active', get_defined_vars())) {
            ob_start();
        }
    }

    // 结束缓冲并处理输出 HTML
    public static function endBuffer()
    {
        if (ob_get_level() > 0) {
            $html = ob_get_clean();
            // 运行替换
            $processed = self::convertLinks($html, null, null);

            // 根据配置决定是否注入前端重写脚本（用于 AJAX/客户端渲染场景）
            $injectClient = false;
            try {
                $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                if (isset($pluginOptions->enableClientRewrite) && $pluginOptions->enableClientRewrite === '1') {
                    $injectClient = true;
                }
            } catch (Exception $e) {
                $injectClient = false;
            }

            if ($injectClient) {
                // 站点 URL（注入到脚本中以避免脚本自行猜测）
                try {
                    $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
                } catch (Exception $e) {
                    $siteUrl = '';
                }
                $siteJson = json_encode(rtrim($siteUrl, '/'));

                // 注入白名单规则（前端用于决定是否改写外链）
                $rawWhitelist = '';
                try {
                    $pluginOptions2 = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                    if (isset($pluginOptions2->urlWhitelist)) {
                        $rawWhitelist = (string)$pluginOptions2->urlWhitelist;
                    }
                } catch (Exception $e) {
                    $rawWhitelist = '';
                }
                $skipLinksPluginUrls = self::isEnabledOption(isset($pluginOptions2) ? $pluginOptions2 : null, 'skipLinksPluginUrls', false);
                $openNewTab = self::isEnabledOption(isset($pluginOptions2) ? $pluginOptions2 : null, 'openInNewTab', true);
                $linkAttrSettings = self::getLinkAttributeSettings(isset($pluginOptions2) ? $pluginOptions2 : null);
                $linkRelTokens = implode(' ', $linkAttrSettings['rel']);
                $linkTitleMode = $linkAttrSettings['title'];
                $linkExtraClass = $linkAttrSettings['class'];
                $linkAddData = $linkAttrSettings['data'] ? '1' : '0';
                $linksPluginUrlsJson = '[]';
                if ($skipLinksPluginUrls) {
                    $linksPluginUrlsJson = json_encode(array_values(array_keys(self::getLinksPluginUrlMap())));
                    if (!is_string($linksPluginUrlsJson) || $linksPluginUrlsJson === '') {
                        $linksPluginUrlsJson = '[]';
                    }
                }
                // 使用 base64 注入，避免 JS 字符串出现未转义换行导致语法错误
                $whitelistB64 = base64_encode($rawWhitelist);

                // 使用 nowdoc（不做 PHP 转义/插值），通过占位符注入变量
                $script = <<<'LINKGO_JS'
<script>
(function(){
    var siteBase = "__LINKGO_SITE__" || (window.location.origin || '');
    var rawWhitelist = '';
    var rawWhitelistB64 = "__LINKGO_WL_B64__";
    var skipLinksPluginUrls = "__LINKGO_SKIP_LINKS__" === '1';
    var openNewTab = "__LINKGO_OPEN_NEW__" === '1';
    var relTokens = "__LINKGO_REL_TOKENS__";
    var titleMode = "__LINKGO_TITLE_MODE__";
    var extraClass = "__LINKGO_EXTRA_CLASS__";
    var addDataAttr = "__LINKGO_ADD_DATA__" === '1';
    var linksPluginUrls = __LINKGO_LINKS_URLS__;
    try{
        rawWhitelist = rawWhitelistB64 ? atob(String(rawWhitelistB64)) : '';
    }catch(e){
        rawWhitelist = '';
    }

    rawWhitelist = String(rawWhitelist)
        .replace(/<br\s*\/?\s*>/gi, '\n')
        .replace(/&nbsp;/gi, ' ');

    function parseWhitelist(raw){
        var out = { exact: [], host: [], hostPath: [] };
        if(!raw) return out;
        var lines = String(raw).split(/\r\n|\r|\n/);
        for(var i=0;i<lines.length;i++){
            var line = String(lines[i]||'').trim();
            if(!line) continue;
            if(line.indexOf('#')===0 || line.indexOf('//')===0) continue;
            line = line.replace(/\s+(#|\/\/).*$/, '').trim();
            if(!line) continue;
            if(/^https?:\/\//i.test(line)){
                try{
                    var pu = new URL(line);
                    var pHost = (pu.hostname||'').toLowerCase().replace(/\.$/,'');
                    var pPath = pu.pathname || '';
                    var hasQF = pu.search || pu.hash;
                    if(pHost && (pPath===''||pPath==='/') && !hasQF){
                        out.host.push(pHost);
                        continue;
                    }
                    if(pHost && pPath && pPath!=='/' && !hasQF){
                        out.hostPath.push([pHost, pPath.toLowerCase()]);
                        continue;
                    }
                    out.exact.push(line.toLowerCase().replace(/\/$/,''));
                }catch(e){
                    out.exact.push(line.toLowerCase().replace(/\/$/,''));
                }
                continue;
            }
            line = line.replace(/^\/\//,'').toLowerCase();
            var slash = line.indexOf('/');
            if(slash === -1){
                out.host.push(line.replace(/\.+$/,'').replace(/\.$/,''));
            }else{
                var h = line.slice(0, slash).replace(/\.$/, '');
                var p = line.slice(slash);
                if(h && p) out.hostPath.push([h,p]);
            }
        }
        return out;
    }

    function hostMatch(host, ruleHost){
        host = (host||'').toLowerCase();
        ruleHost = (ruleHost||'').toLowerCase();
        if(!host || !ruleHost) return false;
        if(ruleHost.indexOf('*.') === 0){
            var base = ruleHost.slice(2);
            return host === base || host.slice(-1-base.length) === '.' + base || host.endsWith('.'+base);
        }
        if(ruleHost.indexOf('.') === 0){
            var base2 = ruleHost.slice(1);
            return host === base2 || host.endsWith('.'+base2);
        }
        return host === ruleHost;
    }

    var WL = parseWhitelist(rawWhitelist);
    var linksUrlMap = {};

    function normalizeComparableHref(href){
        if(!href) return '';
        try{
            var u = new URL(href, location.href);
            if(u.protocol !== 'http:' && u.protocol !== 'https:') return '';
            var scheme = (u.protocol || '').toLowerCase();
            var host = (u.hostname || '').toLowerCase().replace(/\.$/, '');
            var port = u.port || '';
            if((scheme === 'http:' && port === '80') || (scheme === 'https:' && port === '443')){
                port = '';
            }
            var path = u.pathname || '/';
            if(!u.search && !u.hash){
                path = path.replace(/\/+$/, '');
                if(path === '') path = '/';
            }
            return scheme + '//' + host + (port ? ':' + port : '') + path + (u.search || '') + (u.hash || '');
        }catch(e){
            return '';
        }
    }

    if(skipLinksPluginUrls && linksPluginUrls && linksPluginUrls.length){
        for(var li=0; li<linksPluginUrls.length; li++){
            var normalizedLink = normalizeComparableHref(linksPluginUrls[li]);
            if(normalizedLink){
                linksUrlMap[normalizedLink] = true;
            }
        }
    }

    function isWhitelistedHref(href){
        if(!href) return false;
    // 没有任何规则时，直接视为不在白名单
    if(!WL.exact.length && !WL.host.length && !WL.hostPath.length) return false;
        try{
            var u = new URL(href, location.href);
            if(u.protocol !== 'http:' && u.protocol !== 'https:') return false;
            var full = u.href.toLowerCase().replace(/\/$/,'');
            for(var i=0;i<WL.exact.length;i++){ if(full === WL.exact[i]) return true; }
            for(var j=0;j<WL.hostPath.length;j++){
                var hp = WL.hostPath[j];
                if(hostMatch(u.hostname, hp[0]) && u.pathname.toLowerCase().indexOf(hp[1]) === 0) return true;
            }
            for(var k=0;k<WL.host.length;k++){ if(hostMatch(u.hostname, WL.host[k])) return true; }
        }catch(e){ return false; }
        return false;
    }

    function isLinksPluginHref(href){
        if(!skipLinksPluginUrls) return false;
        var normalized = normalizeComparableHref(href);
        return !!(normalized && linksUrlMap[normalized]);
    }

    function isInsideCodeLike(node){
        return !!(node && node.closest && node.closest('code, pre'));
    }

    function urlSafeBase64Encode(str){
        try{var b64 = btoa(unescape(encodeURIComponent(str)));return b64.replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');}catch(e){return null}
    }

    function isExternalHref(href){
        if(!href) return false;
        if(/^(mailto|tel|javascript|#)/i.test(href)) return false;
        try{var u=new URL(href, location.href);return u.protocol.indexOf('http')===0 && u.host !== location.host;}catch(e){return false}
    }

    function rewriteAnchor(a){
        if(!a || !a.getAttribute) return;
        if(a.dataset && a.dataset.linkgoRewritten==='1') return;
        if(isInsideCodeLike(a)) return;
        var href = a.getAttribute('href') || a.href;
        if(!isExternalHref(href)) return;
    if(isWhitelistedHref(href)) return;
        if(isLinksPluginHref(href)) return;
        var enc = urlSafeBase64Encode(href);
        if(!enc) return;
        a.setAttribute('href', siteBase.replace(/\/$/, '') + '/go/' + enc);
        if(openNewTab){
            a.setAttribute('target', '_blank');
        }
        var relList = (a.getAttribute('rel') || '').toLowerCase().split(/\s+/);
        var addRel = relTokens ? relTokens.split(/\s+/) : [];
        for(var ri=0; ri<addRel.length; ri++){
            if(addRel[ri] && relList.indexOf(addRel[ri]) === -1) relList.push(addRel[ri]);
        }
        relList = relList.filter(function(x){ return !!x; });
        if(relList.length){ a.setAttribute('rel', relList.join(' ')); }
        if(titleMode !== 'none' && !a.getAttribute('title')){
            var titleValue = href;
            if(titleMode !== 'full'){
                try{ titleValue = new URL(href, location.href).host; }catch(e){}
            }
            if(titleValue){ a.setAttribute('title', '即将离开本站，前往 ' + titleValue); }
        }
        if(extraClass){
            var cls = a.getAttribute('class') || '';
            a.setAttribute('class', (cls ? cls + ' ' : '') + extraClass);
        }
        if(addDataAttr){ a.setAttribute('data-linkgo', '1'); }
        if(a.dataset) a.dataset.linkgoRewritten='1';
    }

    function rewriteWithin(root){
        if(!root) return; var nodes = root.querySelectorAll ? root.querySelectorAll('a[href]') : [];
        for(var i=0;i<nodes.length;i++){ try{ rewriteAnchor(nodes[i]); }catch(e){} }
        if(root.nodeName==='A' && root.getAttribute && root.getAttribute('href')) rewriteAnchor(root);
    }

    // 初次运行
    try{ rewriteWithin(document); }catch(e){}

    // 监听动态插入
    try{
        var mo = new MutationObserver(function(muts){ for(var m=0;m<muts.length;m++){ var add = muts[m].addedNodes; if(!add) continue; for(var n=0;n<add.length;n++){ var node = add[n]; if(node.nodeType===1) rewriteWithin(node); } } });
        mo.observe(document.documentElement||document.body, { childList:true, subtree:true });
    }catch(e){}

    // jQuery Ajax 补充
    if(window.jQuery) (function($){ $(document).ajaxComplete(function(){ try{ rewriteWithin(document); }catch(e){} }); })(window.jQuery);

    window.LinkGoRewrite = rewriteWithin;
})();
</script>
LINKGO_JS;

                // 注入 PHP 变量到 nowdoc 生成的脚本中
                $siteBaseVal = rtrim($siteUrl, '/');
                $script = str_replace(
                    array('__LINKGO_SITE__', '__LINKGO_WL_B64__', '__LINKGO_SKIP_LINKS__', '__LINKGO_OPEN_NEW__', '__LINKGO_REL_TOKENS__', '__LINKGO_TITLE_MODE__', '__LINKGO_EXTRA_CLASS__', '__LINKGO_ADD_DATA__', '__LINKGO_LINKS_URLS__'),
                    array(addslashes($siteBaseVal), addslashes($whitelistB64), $skipLinksPluginUrls ? '1' : '0', $openNewTab ? '1' : '0', addslashes($linkRelTokens), addslashes($linkTitleMode), addslashes($linkExtraClass), $linkAddData, $linksPluginUrlsJson),
                    $script
                );

                $processed .= $script;
            }

            echo $processed;
        }
    }
}