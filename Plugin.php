<?php
/**
 * 外部链接自动跳转插件
 * 
 * @package LinkGo
 * @author LHL
 * @version 1.0.1
 * @link https://github.com/lhl77/Typecho-Plugin-LinkGo
 */
class LinkGo_Plugin implements Typecho_Plugin_Interface
{
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
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('LinkGo_Plugin', 'convertLinks');

        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('LinkGo_Plugin', 'convertCommentLinks');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->content = array('LinkGo_Plugin', 'convertCommentLinks');
        // 尝试修改评论者链接字段（部分主题会读取 comment.url）
        Typecho_Plugin::factory('Widget_Abstract_Comments')->filter = array('LinkGo_Plugin', 'convertAuthorUrl');

        // Namespaced 注册（Typecho 新版/文档中常见写法）
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->contentEx = ['LinkGo_Plugin', 'convertLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->excerptEx = ['LinkGo_Plugin', 'convertLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Contents')->content = ['LinkGo_Plugin', 'convertLinks'];

        \Typecho\Plugin::factory('Widget\\Base\\Comments')->contentEx = ['LinkGo_Plugin', 'convertCommentLinks'];
        \Typecho\Plugin::factory('Widget\\Base\\Comments')->content = ['LinkGo_Plugin', 'convertCommentLinks'];
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
        // 注入简洁的 Material Design 3 风格样式（非破坏性，仅覆盖少数控件样式以改善外观）
        echo '<style>';
        // 使用中性偏蓝的主色，避免黄色强调
        echo ':root{--lg-primary:#3b82c4;--lg-on-primary:#ffffff;--lg-surface:#fff;--lg-muted:#6b7280;--lg-text:#0f172a}';
        echo '.typecho-page-main .linkgo-md3{max-width:820px;margin:18px auto;padding:18px;border-radius:14px;position:relative}';
        echo '.typecho-page-main .linkgo-md3 .typecho-label{font-weight:600;color:var(--lg-text);display:block;margin-bottom:6px}';
        // 卡片头部样式（flex 布局，适配示例图）
        echo '.typecho-page-main .linkgo-card-header{background:linear-gradient(135deg,#7c3aed 0%,#3b82c4 100%);border-radius:12px;padding:18px;color:#ffffff;margin-bottom:12px;box-shadow:0 10px 30px rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:space-between;gap:12px}';
        echo '.typecho-page-main .linkgo-card-header .left{display:flex;align-items:center;gap:14px}';
        echo '.typecho-page-main .linkgo-card-header .logo{width:64px;height:64px;border-radius:16px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px}';
        echo '.typecho-page-main .linkgo-card-header .title{font-size:22px;font-weight:800;margin-bottom:2px}';
        echo '.typecho-page-main .linkgo-card-header .subtitle{font-size:13px;opacity:0.95}';
    // header 内显示 actions（放入卡片内部）
    echo '.typecho-page-main .linkgo-card-header .actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:8px}';
    // actions 单独行样式（浅背景，圆角，支持换行）
    echo '.typecho-page-main .linkgo-actions-row{margin-top:12px;padding:10px;border-radius:12px;background:#f6fbff;border:1px solid #e6f4ff;display:flex;gap:8px;flex-wrap:wrap;align-items:center}';
    // chips 在浅色行上的样式（浅蓝色背景与深蓝文字），并保持单行显示
    echo '.typecho-page-main .linkgo-actions-row .linkgo-chip{background:#e6f4ff;color:#0366d6;padding:6px 10px;border-radius:999px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(3,102,214,0.08);white-space:nowrap}';
    // 保留卡片主题下的深色 chip（如果被其他区域使用）
    echo '.typecho-page-main .linkgo-card-header .linkgo-chip{background:rgba(255,255,255,0.12);color:#fff;padding:6px 10px;border-radius:999px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,0.06)}';
        echo '.typecho-page-main .linkgo-info{margin-top:12px;padding:14px;border-radius:10px;background:#f8fbff;border:1px solid #e6f1ff;color:#0f172a}';
        echo '.typecho-page-main .linkgo-info .title{font-weight:700;margin-bottom:8px}';
        echo '.typecho-page-main .linkgo-success{margin-top:12px;padding:12px;border-radius:10px;background:linear-gradient(90deg,#10b981,#059669);color:#fff;font-weight:700}';
        echo '.typecho-page-main .linkgo-md3 .description{color:var(--lg-muted);margin-bottom:8px;font-size:13px;line-height:1.6}';
        echo '.typecho-page-main .linkgo-md3 input[type=text], .typecho-page-main .linkgo-md3 select{height: auto;width:100%;padding:10px 12px;border-radius:10px;border:1px solid #e6eef8;background:var(--lg-surface);box-shadow:0 2px 6px rgba(59,130,246,0.06);margin-bottom:12px}';
        echo '.typecho-page-main .linkgo-md3 .typecho-submit{background:var(--lg-primary);color:var(--lg-on-primary);border-radius:10px;padding:10px 18px;border:0;font-weight:700}';
        echo '.typecho-page-main .linkgo-md3 .btn{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;text-decoration:none;border:1px solid transparent;font-weight:600}';
        echo '.typecho-page-main .linkgo-md3 .btn--primary, .typecho-page-main .linkgo-md3 .btn.btn--primary, .typecho-page-main .linkgo-md3 .linkgo-btn--primary{background:linear-gradient(90deg,var(--lg-primary),#2563eb);color:var(--lg-on-primary);border-color:rgba(37,99,235,0.12);box-shadow:0 8px 24px rgba(59,130,246,0.12)}';
        echo '.typecho-page-main .linkgo-md3 .btn--primary:hover{filter:brightness(0.96)}';
        echo '.typecho-page-main .linkgo-md3 .typecho-radio{display:flex;gap:12px;align-items:center;margin-bottom:12px}';
        echo '.typecho-page-main .linkgo-md3 .typecho-radio label{margin-right:8px}';
        // MD3 风格的 Textarea（用于白名单配置）
        echo '.typecho-page-main .linkgo-md3 textarea{display:block;box-sizing:border-box;width:100%;min-height:180px;padding:16px;border-radius:12px;border:1px solid #cbd5e1;background:#fff;color:#334155;font-family:"JetBrains Mono",Consolas,Monaco,monospace;font-size:13px;line-height:1.5;resize:vertical;transition:all .2s;box-shadow:0 1px 2px 0 rgba(0,0,0,0.05)}';
        echo '.typecho-page-main .linkgo-md3 textarea:hover{border-color:#94a3b8}';
        echo '.typecho-page-main .linkgo-md3 textarea:focus{outline:0;border-color:var(--lg-primary);box-shadow:0 0 0 4px rgba(59,130,246,0.1)}';
    // 白名单示例区块
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples{margin-top:10px;margin-bottom:16px;padding:12px;border-radius:12px;background:#f8fbff;border:1px solid #e6f1ff}';
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples .ex-title{font-weight:800;color:var(--lg-text);margin-bottom:8px}';
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples .ex-desc{color:var(--lg-muted);font-size:13px;line-height:1.6;margin-bottom:10px}';
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples .ex-actions{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 8px}';
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples .ex-btn{appearance:none;border:1px solid rgba(37,99,235,0.18);background:#fff;color:#1d4ed8;border-radius:999px;padding:7px 12px;font-weight:700;font-size:13px;cursor:pointer}';
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples .ex-btn:hover{background:#eff6ff}';
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples pre{margin:0;padding:12px;border-radius:12px;background:#0b1220;color:#e5e7eb;overflow:auto;line-height:1.55;font-size:12.5px}';
    echo '.typecho-page-main .linkgo-md3 .linkgo-examples code{font-family:"JetBrains Mono",Consolas,Monaco,monospace}';
        // 优化描述文字排版
        echo '.typecho-page-main .linkgo-md3 .description{color:var(--lg-muted);margin-bottom:8px;font-size:13px;line-height:1.6}';
        echo '</style>';

        echo <<<'LG_PLUGIN_CONFIG_SCRIPT'
<script>
document.addEventListener("DOMContentLoaded", function(){
    var f = document.querySelector(".typecho-page-main form");
    if (f && !f.classList.contains("linkgo-md3")) { f.classList.add("linkgo-md3"); }
        if (f && !document.querySelector(".linkgo-card-header")) {
        var header = document.createElement("div");
        header.className = "linkgo-card-header";
        header.innerHTML = '<div class="left"><div class="logo">🔗</div><div><div class="title">LinkGo</div><div class="subtitle">外部链接中间跳转插件 · 安全提示页</div></div></div>';
        f.parentNode.insertBefore(header, f);

    // 在卡片 header 内插入 actions（包含同步按钮）
    var actions = document.createElement('div');
    actions.className = 'actions';
    actions.innerHTML = '<a class="linkgo-chip" href="https://github.com/lhl77/Typecho-Plugin-LinkGo" target="_blank" rel="noopener noreferrer">GitHub 仓库</a><a class="linkgo-chip" href="https://blog.lhl.one/artical/949.html#主题开发" target="_blank" rel="noopener noreferrer">主题开发文档</a><a class="linkgo-chip" href="https://blog.lhl.one/artical/949.html#主题" target="_blank" rel="noopener noreferrer">更多主题</a>';
    header.appendChild(actions);
    }

    // 在“网址白名单”文本框下方插入常见规则示例 + 复制/追加按钮
    var whitelist = document.querySelector('textarea[name="urlWhitelist"], textarea#urlWhitelist');
    if(whitelist && !document.querySelector('.linkgo-examples')){
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

        function copyText(text){
            if(navigator.clipboard && navigator.clipboard.writeText){
                return navigator.clipboard.writeText(text);
            }
            return new Promise(function(resolve, reject){
                try{
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    document.body.appendChild(ta);
                    ta.select();
                    var ok = document.execCommand('copy');
                    document.body.removeChild(ta);
                    ok ? resolve() : reject(new Error('copy failed'));
                }catch(e){ reject(e); }
            });
        }

        function toast(msg){
            try{
                var t = document.createElement('div');
                t.textContent = msg;
                t.style.position='fixed';
                t.style.right='18px';
                t.style.bottom='18px';
                t.style.zIndex='99999';
                t.style.padding='10px 12px';
                t.style.borderRadius='12px';
                t.style.background='rgba(15,23,42,.92)';
                t.style.color='#fff';
                t.style.fontSize='13px';
                t.style.boxShadow='0 10px 30px rgba(0,0,0,.25)';
                document.body.appendChild(t);
                setTimeout(function(){ t.style.opacity='0'; t.style.transition='opacity .25s'; }, 1100);
                setTimeout(function(){ if(t && t.parentNode) t.parentNode.removeChild(t); }, 1500);
            }catch(e){}
        }

        var box = document.createElement('div');
        box.className = 'linkgo-examples';
        box.innerHTML = ''
            + '<div class="ex-title">常见需求示例</div>'
            + '<div class="ex-desc">这些规则会让命中的外链保持原样（不改写成 /go/xxx）。你可以先“复制”，或直接“追加到白名单”。</div>'
            + '<div class="ex-actions">'
            +   '<button type="button" class="ex-btn" data-act="copy">复制示例</button>'
            +   '<button type="button" class="ex-btn" data-act="append">追加到白名单</button>'
            + '</div>'
            + '<pre><code></code></pre>';
        box.querySelector('code').textContent = sample;
        whitelist.insertAdjacentElement('afterend', box);

        box.addEventListener('click', function(e){
            var btn = e.target && e.target.closest ? e.target.closest('button[data-act]') : null;
            if(!btn) return;

            var act = btn.getAttribute('data-act');
            if(act === 'copy'){
                copyText(sample).then(function(){ toast('已复制示例规则'); }).catch(function(){ toast('复制失败，请手动复制'); });
                return;
            }

            if(act === 'append'){
                var cur = String(whitelist.value || '').replace(/\s+$/,'');
                var next = cur ? (cur + '\n\n' + sample) : sample;
                whitelist.value = next;
                whitelist.dispatchEvent(new Event('input', {bubbles:true}));
                toast('已追加到白名单');
                return;
            }
        });
    }
});
</script>
LG_PLUGIN_CONFIG_SCRIPT;

        // 站点显示标题（用于跳转页）
        $siteTitle = new Typecho_Widget_Helper_Form_Element_Text('siteTitle', null, '', _t('跳转页面站点标题'));
        $form->addInput($siteTitle);

        // Logo 图片 URL
        $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', null, '', _t('Logo 图片 URL'));
        $form->addInput($logoUrl);

        // 起始年份
        $startYear = new Typecho_Widget_Helper_Form_Element_Text('startYear', null, '2026', _t('起始年份（页脚）'));
        $form->addInput($startYear);

        // 主题选择：扫描插件目录下的 page/themes 子目录作为可选主题
        $themeOptions = array();
        try {
            $themeDir = __DIR__ . '/page/themes';
            if (is_dir($themeDir)) {
                $items = scandir($themeDir);
                foreach ($items as $it) {
                    if ($it === '.' || $it === '..')
                        continue;
                    if (is_dir($themeDir . DIRECTORY_SEPARATOR . $it)) {
                        $themeOptions[$it] = $it;
                    }
                }
            }
        } catch (Exception $e) {
            $themeOptions = array();
        }
        if (empty($themeOptions)) {
            $themeOptions = array('Default' => 'Default');
        }
        $themeName = new Typecho_Widget_Helper_Form_Element_Select('themeName', $themeOptions, 'Default', _t('跳转页主题'));
        $form->addInput($themeName);

        // 外部链接是否在新标签打开
        $openNew = new Typecho_Widget_Helper_Form_Element_Radio(
            'openInNewTab',
            array('1' => '是（_blank）', '0' => '否（当前窗口）'),
            '1',
            _t('外部链接打开方式')
        );
        $form->addInput($openNew);

        // 重写监控开关：当主题使用 AJAX/客户端渲染时推荐开启
        $enableClient = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableClientRewrite',
            array('1' => '是（推荐）', '0' => '否'),
            '1',
            _t('AJAX兼容（当主题使用 AJAX 时推荐开启）')
        );
        $form->addInput($enableClient);
        
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
        if (method_exists($whitelist, 'setAttribute')) {
            $whitelist->setAttribute('placeholder', "# 示例：每行一条\nexample.com\n*.example.com\nexample.com/path/\nhttps://icp.redcha.cn/beian/\n# 精准（含 query）\nhttps://example.com/page?id=1");
        } elseif (property_exists($whitelist, 'input') && is_array($whitelist->input)) {
            $whitelist->input['placeholder'] = "# 示例：每行一条\nexample.com\n*.example.com\nexample.com/path/\nhttps://icp.redcha.cn/beian/\n# 精准（含 query）\nhttps://example.com/page?id=1";
        }
        $form->addInput($whitelist);

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

        // 调试：如果需要验证钩子是否被调用，取消下一行注释以把信息写入 PHP 错误日志
        // error_log('[LinkGo] convertLinks called for widget: ' . (is_object($widget) ? get_class($widget) : 'unknown'));

        // 支持属性顺序任意，href 单双引号
        return preg_replace_callback(
            '/<a\s+([^>]*?)href=("|\')(.*?)\2([^>]*)>/i',
            function ($matches) use ($siteHost, $siteUrl) {
                $beforeAttrs = $matches[1];
                $href = $matches[3];
                $afterAttrs = $matches[4];

                // 如果 href 为空，直接返回原始标签
                if (empty($href)) {
                    return $matches[0];
                }

                $targetHost = parse_url($href, PHP_URL_HOST);
                $isExternal = $targetHost && strcasecmp($targetHost, $siteHost) !== 0;

                if ($isExternal) {
                    // 读取插件设置（如果可用）
                    $pluginOptions = null;
                    try {
                        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                    } catch (Exception $e) {
                        $pluginOptions = null;
                    }

                    $openNew = isset($pluginOptions->openInNewTab) ? ($pluginOptions->openInNewTab === '1') : true;

                    // 白名单：命中则不改写为 /go
                    if (self::isWhitelistedUrl($href, $pluginOptions)) {
                        return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . ($openNew ? ' target="_blank"' : '') . ' rel="nofollow noopener noreferrer">';
                    }

                    // 使用 URL-safe base64（替换 +/ 为 -_ 并移除尾部 =），放在路径中
                    $encodedUrl = rtrim(strtr(base64_encode($href), '+/', '-_'), '=');
                    // 使用路径形式 /go/<encoded>
                    $newHref = rtrim($siteUrl, '/') . '/go/' . $encodedUrl;

                    // rel 一律加上安全项
                    $rel = 'nofollow noopener noreferrer';
                    $targetAttr = $openNew ? ' target="_blank"' : '';
                    // 保持原始其他属性
                    return '<a ' . $beforeAttrs . 'href="' . $newHref . '"' . $afterAttrs . $targetAttr . ' rel="' . $rel . '">';
                } else {
                    // 内部链接，保持不变
                    return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . '>';
                }
            },
            $content
        );
    }

    public static function convertCommentLinks($content, $widget, $lastResult)
    {
        $content = empty($lastResult) ? $content : $lastResult;
        $siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);

        // 调试日志（取消注释以启用）
        // error_log('[LinkGo] convertCommentLinks called for widget: ' . (is_object($widget) ? get_class($widget) : 'unknown'));

        return preg_replace_callback(
            '/<a\s+([^>]*?)href=("|\')(.*?)\2([^>]*)>/i',
            function ($matches) use ($siteHost, $siteUrl) {
                $beforeAttrs = $matches[1];
                $href = $matches[3];
                $afterAttrs = $matches[4];

                if (empty($href))
                    return $matches[0];

                $targetHost = parse_url($href, PHP_URL_HOST);
                $isExternal = $targetHost && strcasecmp($targetHost, $siteHost) !== 0;

                if ($isExternal) {
                    $pluginOptions = null;
                    try {
                        $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                    } catch (Exception $e) {
                        $pluginOptions = null;
                    }
                    $openNew = isset($pluginOptions->openInNewTab) ? ($pluginOptions->openInNewTab === '1') : true;

                    // 白名单：命中则不改写为 /go
                    if (self::isWhitelistedUrl($href, $pluginOptions)) {
                        return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . ($openNew ? ' target="_blank"' : '') . ' rel="nofollow noopener noreferrer">';
                    }

                    $encodedUrl = rtrim(strtr(base64_encode($href), '+/', '-_'), '=');
                    $newHref = rtrim($siteUrl, '/') . '/go/' . $encodedUrl;
                    $rel = 'nofollow noopener noreferrer';
                    $targetAttr = $openNew ? ' target="_blank"' : '';
                    return '<a ' . $beforeAttrs . 'href="' . $newHref . '"' . $afterAttrs . $targetAttr . ' rel="' . $rel . '">';
                } else {
                    return '<a ' . $beforeAttrs . 'href="' . $href . '"' . $afterAttrs . '>';
                }
            },
            $content
        );
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
                $pluginOptions = null;
                try {
                    $pluginOptions = Typecho_Widget::widget('Widget_Options')->plugin('LinkGo');
                } catch (Exception $e) {
                    $pluginOptions = null;
                }
                $openNew = isset($pluginOptions->openInNewTab) ? ($pluginOptions->openInNewTab === '1') : true;

                // 白名单：命中则保持原 url 不改写
                if (self::isWhitelistedUrl($url, $pluginOptions)) {
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
                // 使用 base64 注入，避免 JS 字符串出现未转义换行导致语法错误
                $whitelistB64 = base64_encode($rawWhitelist);

                // 使用 nowdoc（不做 PHP 转义/插值），通过占位符注入变量
                $script = <<<'LINKGO_JS'
<script>
(function(){
    var siteBase = "__LINKGO_SITE__" || (window.location.origin || '');
    var rawWhitelist = '';
    var rawWhitelistB64 = "__LINKGO_WL_B64__";
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
        var href = a.getAttribute('href') || a.href;
        if(!isExternalHref(href)) return;
    if(isWhitelistedHref(href)) return;
        var enc = urlSafeBase64Encode(href);
        if(!enc) return;
        a.setAttribute('href', siteBase.replace(/\/$/, '') + '/go/' + enc);
        var rel = (a.getAttribute('rel')||'').split(/\s+/).filter(Boolean);
        ['nofollow','noopener','noreferrer'].forEach(function(r){ if(rel.indexOf(r)===-1) rel.push(r); });
        a.setAttribute('rel', rel.join(' '));
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
                    array('__LINKGO_SITE__', '__LINKGO_WL_B64__'),
                    array(addslashes($siteBaseVal), addslashes($whitelistB64)),
                    $script
                );

                $processed .= $script;
            }

            echo $processed;
        }
    }
}