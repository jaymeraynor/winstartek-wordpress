<?php
/**
 * 1) 為 11 個語言複製 3 個 category(News / Knowledge / Latest)
 * 2) 把所有翻譯 post 依 ZH 原 post 的分類,tag 到對應語言 category
 */
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
$apply = in_array('--apply', $argv, true);

define('SHORTINIT', true);
define('DB_HOST','127.0.0.1:10004');
require __DIR__.'/app/public/wp-load.php';
global $wpdb;
$mysqli = $wpdb->dbh;
mysqli_query($mysqli, "SET NAMES utf8mb4");
$prefix = $wpdb->prefix;

$LANGS = ['ar','de','es','hi','id','it','ja','ko','pt-br','th','vi'];

// 3 source category trids and their EN reference (we'll clone EN structure naming)
$CATS = [
    'knowledge' => ['trid'=>85720, 'zh_tt'=>129, 'en_tt'=>194, 'name'=>'Knowledge Articles', 'slug_base'=>'knowledge-articles'],
    'news'      => ['trid'=>85721, 'zh_tt'=>130, 'en_tt'=>196, 'name'=>'News',                'slug_base'=>'news'],
    'latest'    => ['trid'=>85790, 'zh_tt'=>133, 'en_tt'=>195, 'name'=>'Latest Article',      'slug_base'=>'latest-article'],
];

echo $apply ? "=== APPLY ===\n" : "=== DRY RUN (use --apply) ===\n\n";

// STEP 1 — clone categories per missing language
echo "STEP 1: clone 3 categories × 11 langs = 33 term_taxonomy rows\n\n";
$new_tt = []; // [lang][cat_key] = new tt_id
foreach ($LANGS as $lang) {
    foreach ($CATS as $key => $c) {
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code=%s AND element_type='tax_category' LIMIT 1",
            $c['trid'], $lang
        ));
        if ($existing) {
            echo "  [$lang/$key] already exists tt_id=$existing → skip\n";
            $new_tt[$lang][$key] = $existing;
            continue;
        }
        if (!$apply) {
            echo "  [$lang/$key] WOULD create term name=\"{$c['name']}\" slug=\"{$c['slug_base']}-$lang\"\n";
            continue;
        }
        // create term
        $wpdb->insert($prefix.'terms', [
            'name' => $c['name'],
            'slug' => $c['slug_base'] . '-' . $lang,
            'term_group' => 0,
        ]);
        $new_term_id = (int)$wpdb->insert_id;
        // create term_taxonomy
        $wpdb->insert($prefix.'term_taxonomy', [
            'term_id' => $new_term_id, 'taxonomy' => 'category', 'description' => '', 'parent' => 0, 'count' => 0
        ]);
        $new_tt_id = (int)$wpdb->insert_id;
        // link to WPML trid
        $wpdb->insert($prefix.'icl_translations', [
            'element_type' => 'tax_category',
            'element_id'   => $new_tt_id,
            'trid'         => $c['trid'],
            'language_code'=> $lang,
            'source_language_code' => 'zh-hant',
        ]);
        $new_tt[$lang][$key] = $new_tt_id;
        echo "  [$lang/$key] created tt_id=$new_tt_id\n";
    }
}

// STEP 2 — assign translated posts to corresponding language categories
echo "\nSTEP 2: tag 99 translated posts with language categories\n\n";

// build ZH cat → cat_key mapping
$ZH_CAT_MAP = [];
foreach ($CATS as $key => $c) $ZH_CAT_MAP[$c['zh_tt']] = $key;

$tagged = 0;
foreach ($LANGS as $lang) {
    // get all translated posts in this lang
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT t.element_id AS pid, t.trid
        FROM {$prefix}icl_translations t
        WHERE t.element_type='post_post' AND t.language_code=%s
    ", $lang));
    foreach ($rows as $r) {
        // find ZH source post
        $zh_id = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT element_id FROM {$prefix}icl_translations WHERE trid=%d AND language_code='zh-hant' AND element_type='post_post' LIMIT 1",
            $r->trid
        ));
        if (!$zh_id) continue;
        // get ZH post categories
        $zh_cats = $wpdb->get_col($wpdb->prepare("
            SELECT tr.term_taxonomy_id
            FROM {$prefix}term_relationships tr
            JOIN {$prefix}term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
            WHERE tr.object_id=%d AND tt.taxonomy='category'
        ", $zh_id));
        // map each ZH cat → lang cat
        foreach ($zh_cats as $zh_cat) {
            $key = $ZH_CAT_MAP[$zh_cat] ?? null;
            if (!$key) continue;
            $lang_cat_tt = $new_tt[$lang][$key] ?? null;
            if (!$lang_cat_tt) {
                if (!$apply) { /* dry run, may not have new_tt */ continue; }
                continue;
            }
            // check if relationship exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT 1 FROM {$prefix}term_relationships WHERE object_id=%d AND term_taxonomy_id=%d",
                $r->pid, $lang_cat_tt
            ));
            if ($exists) continue;
            if ($apply) {
                $wpdb->insert($prefix.'term_relationships', [
                    'object_id' => $r->pid, 'term_taxonomy_id' => $lang_cat_tt, 'term_order' => 0
                ]);
            }
            $tagged++;
        }
    }
    echo "  [$lang] processed " . count($rows) . " posts\n";
}
echo "\nTotal tag operations: $tagged\n";

if ($apply) {
    // recount each affected category
    echo "\nUpdating term_taxonomy counts...\n";
    foreach ($new_tt as $lang => $cats) {
        foreach ($cats as $key => $tt_id) {
            $cnt = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}term_relationships WHERE term_taxonomy_id=%d", $tt_id));
            $wpdb->update($prefix.'term_taxonomy', ['count' => $cnt], ['term_taxonomy_id' => $tt_id]);
        }
    }
    echo "Done.\n";
}
