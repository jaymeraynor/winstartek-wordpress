<?php
/**
 * 一次性修正:Element Pack Counter widget 的 "suffix":"年" 被 DeepL 誤譯為姓氏。
 * 直接改 _elementor_data 中對應 post 的 suffix 字串。
 *
 * 用法:
 *   php -c _cli_php.ini _ws_fix_year_suffix.php          # dry run
 *   php -c _cli_php.ini _ws_fix_year_suffix.php --apply  # 實際寫入
 */

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST', '127.0.0.1:10004');
require __DIR__ . '/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;
mysqli_query($mysqli, "SET NAMES utf8mb4");
$prefix = $wpdb->prefix;

// (post_id, wrong_suffix, right_suffix)
$FIX = [
    // ar
    [24584, 'اللقب نيان',           'سنة'],
    [24585, 'اللقب نيان',           'سنة'],
    // de
    [24356, 'Nachname Nian',        'Jahre'],
    [24357, 'Nachname Nian',        'Jahre'],
    // en
    [21401, 'surname Nian',         'Years'],
    [21404, 'surname Nian',         'Years'],
    // es
    [24413, 'apellido Nian',        'Años'],
    [24414, 'apellido Nian',        'Años'],
    // id (about: bad translation; home: untranslated)
    [24698, 'nama keluarga Nian',   'Tahun'],
    [24699, '年',                    'Tahun'],
    // it
    [24470, 'cognome Nian',         'Anni'],
    [24471, 'cognome Nian',         'Anni'],
    // ja (苗字 = surname)
    [24242, '苗字',                  '年'],
    [24243, '苗字',                  '年'],
    // ko
    [24299, '성 니안',               '년'],
    [24300, '성 니안',               '년'],
    // pt-br
    [24527, 'sobrenome Nian',       'Anos'],
    [24528, 'sobrenome Nian',       'Anos'],
    // th (home untranslated)
    [24756, '年',                    'ปี'],
    // vi (home untranslated)
    [24813, '年',                    'Năm'],
];

echo $apply ? "=== APPLY MODE ===\n" : "=== DRY RUN (use --apply to write) ===\n";

$ok = 0; $skip = 0; $missing = 0;
foreach ($FIX as [$post_id, $wrong, $right]) {
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT meta_id, meta_value FROM {$prefix}postmeta WHERE post_id=%d AND meta_key='_elementor_data' LIMIT 1",
        $post_id
    ));
    $title = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$prefix}posts WHERE ID=%d", $post_id));
    $lang  = $wpdb->get_var($wpdb->prepare("SELECT language_code FROM {$prefix}icl_translations WHERE element_id=%d AND element_type LIKE 'post_%%' LIMIT 1", $post_id));

    if (!$row) {
        echo "  MISSING post=$post_id ($lang $title): no _elementor_data\n";
        $missing++;
        continue;
    }

    $needle  = '"suffix":"' . $wrong . '"';
    $replace = '"suffix":"' . $right . '"';
    $count   = 0;
    $new     = str_replace($needle, $replace, $row->meta_value, $count);

    if ($count === 0) {
        echo "  SKIP  post=$post_id ($lang $title): suffix \"$wrong\" not found\n";
        $skip++;
        continue;
    }

    echo "  " . ($apply ? 'FIX  ' : 'WOULD') . " post=$post_id ($lang $title): \"$wrong\" -> \"$right\" ($count occurrences)\n";

    if ($apply) {
        $payload = mysqli_real_escape_string($mysqli, $new);
        $r = mysqli_query($mysqli, "UPDATE {$prefix}postmeta SET meta_value='$payload' WHERE meta_id={$row->meta_id}");
        if (!$r) {
            fwrite(STDERR, "    UPDATE FAILED: " . mysqli_error($mysqli) . "\n");
            continue;
        }
        // bump Elementor's CSS rebuild marker so the front-end picks up the change
        $css_key = '_elementor_css';
        $wpdb->delete($prefix . 'postmeta', ['post_id' => $post_id, 'meta_key' => $css_key]);
    }
    $ok++;
}

echo "\nSummary: " . ($apply ? 'fixed' : 'would-fix') . "=$ok skip=$skip missing=$missing\n";

if ($apply) {
    // Clear Elementor + page caches
    echo "\nClearing Elementor CSS cache table option (elementor_remote_info_*) and breeze cache (if present)...\n";
    $wpdb->query("DELETE FROM {$prefix}options WHERE option_name LIKE '_transient_elementor_%' OR option_name LIKE '_transient_timeout_elementor_%'");
    echo "Done. Manual step: in WP admin, run Elementor > Tools > Regenerate CSS, and clear Breeze cache.\n";
}
