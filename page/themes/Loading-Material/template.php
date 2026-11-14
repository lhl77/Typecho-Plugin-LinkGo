<?php
// Loading-Material：Material 3 Expressive「Loading indicator」
// 官方实现（androidx.compose.material3.LoadingIndicatorDefaults.IndeterminateIndicatorPolygons）
// 在 7 个 Material Shape 之间循环形变：
//   SoftBurst → Cookie9Sided → Pentagon → Pill → Sunny → Cookie4Sided → Oval
// 节奏：每 650ms 形变一次（MorphIntervalMillis），每步额外转 90°；
//       另有 4666ms 一轮的匀速整圈自转（GlobalRotationDurationMillis）；
// 尺寸：容器 48dp、活动形状 38dp（ActiveIndicatorScale = 38/48）；默认变体只用 primary 色。
$robotsContent = isset($robotsContent) ? $robotsContent : 'noindex, nofollow';

if (!function_exists('lg_m3_pt')) {
    function lg_m3_pt(array $p)
    {
        $out = array();
        foreach ($p as $v) {
            $s = number_format($v, 2, '.', '');
            $s = rtrim(rtrim($s, '0'), '.');
            $out[] = ($s === '' || $s === '-0') ? '0' : $s;
        }
        return $out[0] . ' ' . $out[1];
    }

    /** 把解析式半径函数采样成闭合 path（样本数固定，才能用 SMIL 在形状间插值） */
    function lg_m3_shape_path($radial, $steps = 120, $cx = 24, $cy = 24, $radius = 19)
    {
        $radii = array();
        $max = 0.0;
        for ($i = 0; $i < $steps; $i++) {
            $a = -M_PI / 2 + 2 * M_PI * $i / $steps;
            $r = (float)$radial($a);
            $radii[] = $r;
            if ($r > $max) {
                $max = $r;
            }
        }
        if ($max <= 0) {
            $max = 1.0;
        }
        // 两轮平滑，模拟 Material Shape 的圆角
        foreach (array(array(1, 2, 1), array(1, 1, 2, 1, 1)) as $kernel) {
            $n = count($kernel);
            $half = intdiv($n, 2);
            $next = array();
            for ($i = 0; $i < $steps; $i++) {
                $sum = 0.0;
                $wsum = 0;
                for ($k = 0; $k < $n; $k++) {
                    $idx = ($i + $k - $half + $steps) % $steps;
                    $sum += $radii[$idx] * $kernel[$k];
                    $wsum += $kernel[$k];
                }
                $next[] = $sum / $wsum;
            }
            $radii = $next;
        }
        $pts = array();
        for ($i = 0; $i < $steps; $i++) {
            $a = -M_PI / 2 + 2 * M_PI * $i / $steps;
            $rr = $radii[$i] / $max * $radius;
            $pts[] = array($cx + $rr * cos($a), $cy + $rr * sin($a));
        }
        $d = 'M ' . lg_m3_pt($pts[0]);
        for ($i = 1; $i < $steps; $i++) {
            $d .= ' L ' . lg_m3_pt($pts[$i]);
        }
        return $d . ' Z';
    }
}

// 7 个 Material Shape 的解析式（角度 → 相对半径，后面会归一化到最大半径）
$m3Radials = array(
    // SoftBurst：10 向柔和爆炸，内外半径比约为 0.69
    function ($a) {
        return 0.845 + 0.155 * cos(10 * $a);
    },
    // Cookie9Sided：9 向饼干（内半径 0.8、外圆角 50%）
    function ($a) {
        return 0.9 + 0.1 * cos(9 * $a);
    },
    // Pentagon：正五边形 + 圆角
    function ($a) {
        $seg = 2 * M_PI / 5;
        $t = fmod($a + M_PI / 5, $seg);
        if ($t < 0) {
            $t += $seg;
        }
        return 0.86 * (cos(M_PI / 5) / cos($t - M_PI / 5)) + 0.14;
    },
    // Pill：胶囊（半长 0.45 + 圆角 0.55）
    function ($a) {
        $half = 0.45;
        $rad = 0.55;
        $s = abs(sin($a));
        $c = abs(cos($a));
        $inside = $rad * $rad - $half * $half * $s * $s;
        return $half * $c + ($inside > 0 ? sqrt($inside) : 0);
    },
    // Sunny：8 向小太阳（内半径 0.8、外圆角 15%）
    function ($a) {
        return 0.9 + 0.1 * cos(8 * $a);
    },
    // Cookie4Sided：圆角方形（超椭圆 n=4）
    function ($a) {
        return pow(pow(abs(cos($a)), 4) + pow(abs(sin($a)), 4), -0.25);
    },
    // Oval：1 : 0.72 椭圆
    function ($a) {
        $b = 0.72;
        return $b / sqrt(pow($b * cos($a), 2) + pow(sin($a), 2));
    },
);

$m3Paths = array();
foreach ($m3Radials as $m3Radial) {
    $m3Paths[] = lg_m3_shape_path($m3Radial);
}
$m3Paths[] = $m3Paths[0]; // 闭环：回到第一个形状

$m3StepCount = count($m3Paths);
$m3KeyTimes = array();
$m3KeySplines = array();
for ($m3i = 0; $m3i < $m3StepCount; $m3i++) {
    $m3KeyTimes[] = $m3i === 0 ? '0' : rtrim(rtrim(number_format($m3i / ($m3StepCount - 1), 4, '.', ''), '0'), '.');
    if ($m3i < $m3StepCount - 1) {
        $m3KeySplines[] = '0.34 0 0.2 1';
    }
}
$m3Duration = '4.55'; // 7 段 × 650ms

$themeCssFile = __DIR__ . '/style.css';
$themeCssUrl = rtrim($siteUrl, '/') . '/usr/plugins/LinkGo/page/themes/' . rawurlencode($themeName) . '/style.css?v=' . (is_file($themeCssFile) ? filemtime($themeCssFile) : '1');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php if ($robotsContent !== ''): ?><meta name="robots" content="<?php echo htmlspecialchars($robotsContent, ENT_QUOTES); ?>"><?php endif; ?>
    <meta name="theme-color" content="#FFFFFF" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#121212" media="(prefers-color-scheme: dark)">
    <title>加载中…</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($themeCssUrl, ENT_QUOTES); ?>">
    <?php if (!empty($themeColor)): ?>
    <style>:root{--lg-accent:<?php echo htmlspecialchars($themeColor, ENT_QUOTES); ?>;--lg-on-accent:<?php echo htmlspecialchars(isset($themeColorOn) ? $themeColorOn : '#FFFFFF', ENT_QUOTES); ?>}</style>
    <?php endif; ?>
</head>
<body>
    <div class="m3-loader" role="status" aria-label="加载中">
        <span class="m3-spin" aria-hidden="true"><span class="m3-step">
            <svg class="m3-blob" viewBox="0 0 48 48" width="96" height="96" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="<?php echo $m3Paths[0]; ?>">
                    <animate attributeName="d"
                             dur="<?php echo $m3Duration; ?>s"
                             repeatCount="indefinite"
                             calcMode="spline"
                             keyTimes="<?php echo implode(';', $m3KeyTimes); ?>"
                             keySplines="<?php echo implode(';', $m3KeySplines); ?>"
                             values="<?php echo implode(';', $m3Paths); ?>"></animate>
                </path>
            </svg>
        </span></span>
    </div>

<script>
(function(){
<?php if (empty($previewMode)): ?>
    var targetUrl = <?php echo json_encode((string)$url); ?>;
    setTimeout(function(){
        try {
            window.location.href = targetUrl;
        } catch (error) {
            window.location = targetUrl;
        }
    }, 3000);
<?php endif; ?>
})();
</script>
</body>
</html>

