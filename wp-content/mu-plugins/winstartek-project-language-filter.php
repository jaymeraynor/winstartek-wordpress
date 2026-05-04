<?php
/**
 * Plugin Name: Winstartek Project Language Filter
 * Description: Keep project loops in the current WPML language instead of falling back to Chinese posts.
 * Version: 1.0
 */

if (!defined('ABSPATH')) exit;

function ws_local_request_base_url() {
    if (empty($_SERVER['HTTP_HOST'])) return null;

    $host = strtolower($_SERVER['HTTP_HOST']);
    if (!in_array($host, ['localhost:10005', 'winstartek.local'], true)) return null;

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
}

function ws_localize_url_to_request_host($url) {
    $base = ws_local_request_base_url();
    if (!$base || !is_string($url) || $url === '') return $url;

    return str_replace(
        ['http://localhost:10005', 'http://winstartek.local', 'http:\/\/localhost:10005', 'http:\/\/winstartek.local'],
        [$base, $base, str_replace('/', '\/', $base), str_replace('/', '\/', $base)],
        $url
    );
}

add_action('send_headers', function() {
    if (!ws_local_request_base_url()) return;

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
});

function ws_project_case_label_for_current_language() {
    $lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    $labels = [
        'en' => 'Real Cases',
        'ja' => 'プロジェクト事例',
        'ko' => '프로젝트 사례',
        'de' => 'Projektbeispiele',
        'es' => 'Casos reales',
        'it' => 'Casi reali',
        'pt-br' => 'Casos reais',
        'ar' => 'أمثلة على المشاريع',
        'hi' => 'परियोजना उदाहरण',
        'id' => 'Contoh Proyek',
        'th' => 'ตัวอย่างโครงการ',
        'vi' => 'Dự án tiêu biểu',
    ];

    return $labels[$lang] ?? '';
}

function ws_solution_label_for_current_language() {
    $lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    $labels = [
        'ja' => 'ソリューション',
        'ko' => '솔루션',
        'de' => 'Lösungen',
        'es' => 'Soluciones',
        'it' => 'Soluzioni',
        'pt-br' => 'Soluções',
        'ar' => 'الحلول',
        'hi' => 'समाधान',
        'id' => 'Solusi',
        'th' => 'โซลูชัน',
        'vi' => 'Giải pháp',
    ];

    return $labels[$lang] ?? '';
}

function ws_case_link_label_for_current_language() {
    $lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    $labels = [
        'en' => 'View Case',
        'ja' => '事例を見る',
        'ko' => '사례 보기',
        'de' => 'Fallstudie ansehen',
        'es' => 'Ver caso',
        'it' => 'Vedi caso',
        'pt-br' => 'Ver caso',
        'ar' => 'عرض الحالة',
        'hi' => 'केस देखें',
        'id' => 'Lihat kasus',
        'th' => 'ดูกรณีศึกษา',
        'vi' => 'Xem dự án',
    ];

    return $labels[$lang] ?? '';
}

function ws_product_menu_labels_for_current_language() {
    $lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    $labels = [
        'en' => ['Interior & Commercial Lighting', 'Outdoor Lighting', 'Industrial Lighting', 'Light Source', 'Engineering Project', 'Energy Storage System', 'Special Applications', 'Energy Saving Label'],
        'ja' => ['屋内・商業照明', '屋外景観照明', '工場・産業照明', '光源シリーズ', 'エンジニアリングプロジェクト', '蓄電システム', '特殊用途', '省エネラベルシリーズ'],
        'ko' => ['실내 및 상업 조명', '실외 경관 조명', '공장 및 산업 조명', '광원 시리즈', '엔지니어링 프로젝트', '에너지 저장 시스템', '특수 응용 분야', '에너지 절약 라벨 시리즈'],
        'de' => ['Innen- & Gewerbebeleuchtung', 'Außenbeleuchtung', 'Industriebeleuchtung', 'Lichtquellen', 'Engineering-Projekte', 'Energiespeichersystem', 'Spezielle Anwendungen', 'Energiesparlabel'],
        'es' => ['Iluminación interior y comercial', 'Iluminación exterior', 'Iluminación industrial', 'Fuentes de luz', 'Proyectos de ingeniería', 'Sistema de almacenamiento de energía', 'Aplicaciones especiales', 'Etiqueta de ahorro energético'],
        'it' => ['Illuminazione interna e commerciale', 'Illuminazione esterna', 'Illuminazione industriale', 'Sorgenti luminose', 'Progetti di ingegneria', 'Sistema di accumulo energia', 'Applicazioni speciali', 'Etichetta risparmio energetico'],
        'pt-br' => ['Iluminação interna e comercial', 'Iluminação externa', 'Iluminação industrial', 'Fontes de luz', 'Projetos de engenharia', 'Sistema de armazenamento de energia', 'Aplicações especiais', 'Selo de economia de energia'],
        'ar' => ['إضاءة داخلية وتجارية', 'إضاءة خارجية', 'إضاءة صناعية', 'مصادر الضوء', 'مشاريع هندسية', 'نظام تخزين الطاقة', 'تطبيقات خاصة', 'ملصق توفير الطاقة'],
        'hi' => ['इनडोर और वाणिज्यिक लाइटिंग', 'आउटडोर लाइटिंग', 'औद्योगिक लाइटिंग', 'लाइट सोर्स', 'इंजीनियरिंग प्रोजेक्ट', 'ऊर्जा भंडारण प्रणाली', 'विशेष अनुप्रयोग', 'ऊर्जा बचत लेबल'],
        'id' => ['Pencahayaan interior & komersial', 'Pencahayaan luar ruangan', 'Pencahayaan industri', 'Sumber cahaya', 'Proyek teknik', 'Sistem penyimpanan energi', 'Aplikasi khusus', 'Label hemat energi'],
        'th' => ['ไฟภายในและเชิงพาณิชย์', 'ไฟภายนอก', 'ไฟอุตสาหกรรม', 'แหล่งกำเนิดแสง', 'โครงการวิศวกรรม', 'ระบบกักเก็บพลังงาน', 'การใช้งานพิเศษ', 'ฉลากประหยัดพลังงาน'],
        'vi' => ['Chiếu sáng nội thất & thương mại', 'Chiếu sáng ngoài trời', 'Chiếu sáng công nghiệp', 'Nguồn sáng', 'Dự án kỹ thuật', 'Hệ thống lưu trữ năng lượng', 'Ứng dụng đặc biệt', 'Nhãn tiết kiệm năng lượng'],
    ];

    return $labels[$lang] ?? [];
}

function ws_product_menu_submenu_html() {
    $labels = ws_product_menu_labels_for_current_language();
    if (!$labels) return '';

    $base = ws_local_request_base_url();
    if (!$base) return '';

    $lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    $prefix = in_array($lang, ['', 'zh-hant'], true) ? '' : '/' . trim($lang, '/');
    $slugs = [
        'indoor-and-commercial-lighting',
        'outdoor-lighting',
        'industrial-lighting',
        'light-source',
        'engineering-project',
        'energy-storage-system',
        'special-application',
        'energy-saving-label',
    ];

    $html = "\n<ul class=\"sub-menu elementor-nav-menu--dropdown\">\n";
    foreach ($slugs as $index => $slug) {
        $url = $base . $prefix . '/products/' . $slug . '/';
        $html .= "\t<li class=\"menu-item menu-item-type-post_type menu-item-object-page ws-repaired-product-subitem\"><a href=\"" . esc_url($url) . "\" class=\"elementor-sub-item menu-link\">" . esc_html($labels[$index]) . "</a></li>\n";
    }
    $html .= "</ul>\n";

    return $html;
}

function ws_repair_product_menu_submenu($html) {
    $current_lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    if (in_array($current_lang, ['', 'zh-hant'], true)) return $html;
    if (strpos($html, '/' . $current_lang . '/products/') === false) return $html;

    $submenu = ws_product_menu_submenu_html();
    if ($submenu === '') return $html;

    return preg_replace_callback(
        '#<li class="([^"]*\bmenu-item\b(?![^"]*\bmenu-item-has-children\b)[^"]*)">(\s*<a\b[^>]*href="[^"]*/' . preg_quote($current_lang, '#') . '/products/"[^>]*>.*?</a>\s*)</li>#su',
        function($matches) use ($submenu) {
            return '<li class="' . $matches[1] . ' menu-item-has-children">' . $matches[2] . $submenu . '</li>';
        },
        $html
    );
}

function ws_tag_translations_for_current_language() {
    $lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    $translations = [
        'en' => [
            '紫外線殺菌' => 'UV Sterilization',
            '辦公室燈具' => 'Office Lighting Fixtures',
            '平板燈' => 'Panel Lights',
            '抗震' => 'Shock Resistant',
            '工業用' => 'Industrial Use',
            '戶外' => 'Outdoor',
            'CNS安規認證' => 'CNS Safety Certification',
            'BSMI認證' => 'BSMI Certification',
            '公司' => 'Company',
            '辦公室' => 'Office',
            '室內' => 'Indoor',
            '燈具' => 'Lighting Fixtures',
            '照明' => 'Lighting',
        ],
        'ja' => [
            '紫外線殺菌' => '紫外線殺菌',
            '辦公室燈具' => 'オフィス照明器具',
            '平板燈' => 'パネルライト',
            '抗震' => '耐震',
            '工業用' => '産業用',
            '戶外' => '屋外',
            'CNS安規認證' => 'CNS安全認証',
            'BSMI認證' => 'BSMI認証',
            '公司' => '会社',
            '辦公室' => 'オフィス',
            '室內' => '屋内',
            '燈具' => '照明器具',
            '照明' => '照明',
        ],
        'ko' => [
            '紫外線殺菌' => '자외선 살균',
            '辦公室燈具' => '사무실 조명기구',
            '平板燈' => '패널 조명',
            '抗震' => '내진',
            '工業用' => '산업용',
            '戶外' => '실외',
            'CNS安規認證' => 'CNS 안전 인증',
            'BSMI認證' => 'BSMI 인증',
            '公司' => '회사',
            '辦公室' => '사무실',
            '室內' => '실내',
            '燈具' => '조명기구',
            '照明' => '조명',
        ],
        'de' => [
            '紫外線殺菌' => 'UV-Desinfektion',
            '辦公室燈具' => 'Büroleuchten',
            '平板燈' => 'Panelleuchten',
            '抗震' => 'Stoßfest',
            '工業用' => 'Industrie',
            '戶外' => 'Außenbereich',
            'CNS安規認證' => 'CNS-Sicherheitszertifizierung',
            'BSMI認證' => 'BSMI-Zertifizierung',
            '公司' => 'Unternehmen',
            '辦公室' => 'Büro',
            '室內' => 'Innenbereich',
            '燈具' => 'Leuchten',
            '照明' => 'Beleuchtung',
        ],
        'es' => [
            '紫外線殺菌' => 'Esterilización UV',
            '辦公室燈具' => 'Luminarias de oficina',
            '平板燈' => 'Paneles LED',
            '抗震' => 'Resistente a impactos',
            '工業用' => 'Uso industrial',
            '戶外' => 'Exterior',
            'CNS安規認證' => 'Certificación de seguridad CNS',
            'BSMI認證' => 'Certificación BSMI',
            '公司' => 'Empresa',
            '辦公室' => 'Oficina',
            '室內' => 'Interior',
            '燈具' => 'Luminarias',
            '照明' => 'Iluminación',
        ],
        'it' => [
            '紫外線殺菌' => 'Sterilizzazione UV',
            '辦公室燈具' => 'Lampade per ufficio',
            '平板燈' => 'Pannelli luminosi',
            '抗震' => 'Resistente agli urti',
            '工業用' => 'Uso industriale',
            '戶外' => 'Esterno',
            'CNS安規認證' => 'Certificazione di sicurezza CNS',
            'BSMI認證' => 'Certificazione BSMI',
            '公司' => 'Azienda',
            '辦公室' => 'Ufficio',
            '室內' => 'Interno',
            '燈具' => 'Apparecchi di illuminazione',
            '照明' => 'Illuminazione',
        ],
        'pt-br' => [
            '紫外線殺菌' => 'Esterilização UV',
            '辦公室燈具' => 'Luminárias de escritório',
            '平板燈' => 'Painéis de LED',
            '抗震' => 'Resistente a impactos',
            '工業用' => 'Uso industrial',
            '戶外' => 'Área externa',
            'CNS安規認證' => 'Certificação de segurança CNS',
            'BSMI認證' => 'Certificação BSMI',
            '公司' => 'Empresa',
            '辦公室' => 'Escritório',
            '室內' => 'Interior',
            '燈具' => 'Luminárias',
            '照明' => 'Iluminação',
        ],
        'ar' => [
            '紫外線殺菌' => 'تعقيم بالأشعة فوق البنفسجية',
            '辦公室燈具' => 'مصابيح مكتبية',
            '平板燈' => 'مصابيح لوحية',
            '抗震' => 'مقاوم للصدمات',
            '工業用' => 'استخدام صناعي',
            '戶外' => 'خارجي',
            'CNS安規認證' => 'شهادة السلامة CNS',
            'BSMI認證' => 'شهادة BSMI',
            '公司' => 'الشركة',
            '辦公室' => 'مكتب',
            '室內' => 'داخلي',
            '燈具' => 'مصابيح',
            '照明' => 'إضاءة',
        ],
        'hi' => [
            '紫外線殺菌' => 'यूवी कीटाणुशोधन',
            '辦公室燈具' => 'कार्यालय लाइट फिटिंग',
            '平板燈' => 'पैनल लाइट',
            '抗震' => 'झटका प्रतिरोधी',
            '工業用' => 'औद्योगिक उपयोग',
            '戶外' => 'आउटडोर',
            'CNS安規認證' => 'CNS सुरक्षा प्रमाणन',
            'BSMI認證' => 'BSMI प्रमाणन',
            '公司' => 'कंपनी',
            '辦公室' => 'कार्यालय',
            '室內' => 'इनडोर',
            '燈具' => 'लाइट फिटिंग',
            '照明' => 'लाइटिंग',
        ],
        'id' => [
            '紫外線殺菌' => 'Sterilisasi UV',
            '辦公室燈具' => 'Lampu kantor',
            '平板燈' => 'Lampu panel',
            '抗震' => 'Tahan guncangan',
            '工業用' => 'Penggunaan industri',
            '戶外' => 'Luar ruangan',
            'CNS安規認證' => 'Sertifikasi keselamatan CNS',
            'BSMI認證' => 'Sertifikasi BSMI',
            '公司' => 'Perusahaan',
            '辦公室' => 'Kantor',
            '室內' => 'Dalam ruangan',
            '燈具' => 'Perlengkapan lampu',
            '照明' => 'Pencahayaan',
        ],
        'th' => [
            '紫外線殺菌' => 'ฆ่าเชื้อด้วยรังสี UV',
            '辦公室燈具' => 'โคมไฟสำนักงาน',
            '平板燈' => 'ไฟพาเนล',
            '抗震' => 'ทนแรงกระแทก',
            '工業用' => 'ใช้ในอุตสาหกรรม',
            '戶外' => 'กลางแจ้ง',
            'CNS安規認證' => 'การรับรองความปลอดภัย CNS',
            'BSMI認證' => 'การรับรอง BSMI',
            '公司' => 'บริษัท',
            '辦公室' => 'สำนักงาน',
            '室內' => 'ในอาคาร',
            '燈具' => 'โคมไฟ',
            '照明' => 'แสงสว่าง',
        ],
        'vi' => [
            '紫外線殺菌' => 'Khử trùng UV',
            '辦公室燈具' => 'Đèn văn phòng',
            '平板燈' => 'Đèn panel',
            '抗震' => 'Chống va đập',
            '工業用' => 'Dùng trong công nghiệp',
            '戶外' => 'Ngoài trời',
            'CNS安規認證' => 'Chứng nhận an toàn CNS',
            'BSMI認證' => 'Chứng nhận BSMI',
            '公司' => 'Công ty',
            '辦公室' => 'Văn phòng',
            '室內' => 'Trong nhà',
            '燈具' => 'Đèn chiếu sáng',
            '照明' => 'Chiếu sáng',
        ],
    ];

    return $translations[$lang] ?? [];
}

function ws_translate_known_term_text_nodes($html, $translations) {
    if (!$translations) return $html;

    return preg_replace_callback('/>([^<>]*[\x{4e00}-\x{9fff}][^<>]*)</u', function($matches) use ($translations) {
        $text = $matches[1];
        $trimmed = trim($text);

        if (!isset($translations[$trimmed])) {
            return $matches[0];
        }

        $leading = substr($text, 0, strlen($text) - strlen(ltrim($text)));
        $trailing = substr($text, strlen(rtrim($text)));

        return '>' . $leading . $translations[$trimmed] . $trailing . '<';
    }, $html);
}

function ws_translate_known_term_attributes($html, $translations) {
    if (!$translations) return $html;

    return preg_replace_callback('/\b(aria-label|title|alt)="([^"]*[\x{4e00}-\x{9fff}][^"]*)"/u', function($matches) use ($translations) {
        $value = $matches[2];

        foreach ($translations as $source => $target) {
            $value = preg_replace('/^' . preg_quote($source, '/') . '(\s*\([^)]+\))?$/u', $target . '$1', $value);
        }

        return $matches[1] . '="' . esc_attr($value) . '"';
    }, $html);
}

function ws_frontend_output_repair($html) {
    $html = ws_localize_url_to_request_host($html);
    $html = ws_repair_product_menu_submenu($html);

    $html = str_replace(
        '<span class="wpml-ls-native" lang="zh-hant">繁體中文</span>',
        '<span class="wpml-ls-native" lang="zh-hant">中文</span>',
        $html
    );

    $project_case_label = ws_project_case_label_for_current_language();
    if ($project_case_label !== '') {
        $html = str_replace('>專案實例<', '>' . $project_case_label . '<', $html);
    }

    $solution_label = ws_solution_label_for_current_language();
    if ($solution_label !== '') {
        $html = str_replace('>Solution<', '>' . $solution_label . '<', $html);
    }

    $case_link_label = ws_case_link_label_for_current_language();
    if ($case_link_label !== '') {
        $html = str_replace('查看案例 »', $case_link_label . ' »', $html);
        $html = str_replace('查看案例 &raquo;', $case_link_label . ' &raquo;', $html);
        $html = str_replace('>查看案例 »<', '>' . $case_link_label . ' »<', $html);
        $html = str_replace('>查看案例 &raquo;<', '>' . $case_link_label . ' &raquo;<', $html);
    }

    $tag_translations = ws_tag_translations_for_current_language();
    if ($tag_translations) {
        $html = ws_translate_known_term_text_nodes($html, $tag_translations);
        $html = ws_translate_known_term_attributes($html, $tag_translations);
    }

    return $html;
}

foreach ([
    'option_home',
    'option_siteurl',
    'home_url',
    'site_url',
    'network_site_url',
    'admin_url',
    'includes_url',
    'content_url',
    'plugins_url',
    'stylesheet_uri',
    'stylesheet_directory_uri',
    'template_directory_uri',
    'theme_file_uri',
    'style_loader_src',
    'script_loader_src',
    'wp_get_attachment_url',
] as $filter_name) {
    add_filter($filter_name, 'ws_localize_url_to_request_host', 9999);
}

add_filter('wp_get_attachment_image_src', function($image) {
    if (is_array($image) && isset($image[0])) {
        $image[0] = ws_localize_url_to_request_host($image[0]);
    }

    return $image;
}, 9999);

add_filter('wp_calculate_image_srcset', function($sources) {
    if (!is_array($sources)) return $sources;

    foreach ($sources as &$source) {
        if (isset($source['url'])) {
            $source['url'] = ws_localize_url_to_request_host($source['url']);
        }
    }

    return $sources;
}, 9999);

add_filter('nav_menu_css_class', function($classes, $item) {
    $current_lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    if (in_array($current_lang, ['', 'zh-hant', 'en'], true)) {
        return $classes;
    }

    $url = isset($item->url) ? (string) $item->url : '';
    if (strpos($url, '/energy-storage-system/') !== false || strpos($url, '/smart-lighting/') !== false) {
        $classes[] = 'ws-desktop-solution-subitem';
    }

    return $classes;
}, 20, 2);

add_action('template_redirect', function() {
    if (is_admin() || wp_doing_ajax() || !ws_local_request_base_url()) return;

    ob_start('ws_frontend_output_repair');
}, 0);

add_action('pre_get_posts', function($query) {
    if (is_admin() || !$query instanceof WP_Query) return;

    $post_type = $query->get('post_type');
    $types = is_array($post_type) ? $post_type : [$post_type];

    if (!in_array('project', $types, true)) return;
    if (!function_exists('icl_object_id') && !has_filter('wpml_current_language')) return;

    $lang = apply_filters('wpml_current_language', null);
    if (!$lang) return;

    $query->set('suppress_filters', false);
    $query->set('ws_project_current_language_only', $lang);
});

add_filter('posts_join', function($join, $query) {
    if (!$query instanceof WP_Query) return $join;
    if (!$query->get('ws_project_current_language_only')) return $join;
    if (strpos($join, 'ws_icl_project_lang') !== false) return $join;

    global $wpdb;
    $icl_table = $wpdb->prefix . 'icl_translations';
    return $join . " INNER JOIN {$icl_table} ws_icl_project_lang ON ws_icl_project_lang.element_id = {$wpdb->posts}.ID";
}, 20, 2);

add_filter('posts_where', function($where, $query) {
    if (!$query instanceof WP_Query) return $where;

    $lang = $query->get('ws_project_current_language_only');
    if (!$lang) return $where;

    global $wpdb;
    $where .= $wpdb->prepare(
        " AND ws_icl_project_lang.element_type = %s AND ws_icl_project_lang.language_code = %s",
        'post_project',
        $lang
    );

    return $where;
}, 20, 2);

function ws_current_language_has_project_posts() {
    static $cache = [];

    $lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    $cache_key = $lang ?: 'default';

    if (array_key_exists($cache_key, $cache)) {
        return $cache[$cache_key];
    }

    $project_query = new WP_Query([
        'post_type' => 'project',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ]);

    $cache[$cache_key] = $project_query->have_posts();
    wp_reset_postdata();

    return $cache[$cache_key];
}

add_action('wp_head', function() {
    $current_lang = has_filter('wpml_current_language') ? apply_filters('wpml_current_language', null) : '';
    ?>
    <style id="winstartek-front-end-repair">
        .elementor-nav-menu .sub-arrow .fa,
        .elementor-nav-menu .sub-arrow .fas,
        .elementor-nav-menu .sub-arrow .far,
        .elementor-nav-menu .sub-arrow .fab {
            display: none !important;
        }

        .elementor-nav-menu .sub-arrow::before {
            content: "";
            display: inline-block;
            width: 0;
            height: 0;
            margin-left: 8px;
            vertical-align: middle;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 6px solid currentColor;
        }

        <?php if (!in_array($current_lang, ['', 'zh-hant', 'en'], true)) : ?>
        @media (min-width: 1025px) {
            #masthead,
            .elementor-location-header {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                width: 100%;
                z-index: 99;
                margin: 0 !important;
            }

            #masthead .elementor-element-11f603b,
            .elementor-location-header .elementor-element-11f603b {
                background: transparent !important;
                box-shadow: none !important;
            }

            #masthead .elementor-element-69f080e {
                display: none !important;
            }

            #masthead .elementor-element-6bfa4d9 {
                display: block !important;
            }

            #masthead .elementor-element-c88893d,
            #masthead .elementor-element-0210219,
            .elementor-location-header .elementor-element-c88893d,
            .elementor-location-header .elementor-element-0210219 {
                --e-nav-menu-horizontal-menu-item-margin: calc(6px / 2) !important;
            }

            #masthead .elementor-nav-menu--main > .elementor-nav-menu,
            .elementor-location-header .elementor-nav-menu--main > .elementor-nav-menu {
                flex-wrap: nowrap !important;
                align-items: center !important;
            }

            #masthead .elementor-element-c88893d .elementor-nav-menu > li > a,
            #masthead .elementor-element-0210219 .elementor-nav-menu > li > a,
            .elementor-location-header .elementor-element-c88893d .elementor-nav-menu > li > a,
            .elementor-location-header .elementor-element-0210219 .elementor-nav-menu > li > a {
                font-size: 15px !important;
                line-height: 1.35em !important;
                letter-spacing: 0 !important;
                white-space: nowrap !important;
            }

            #masthead .elementor-nav-menu--main > .elementor-nav-menu > li.ws-desktop-solution-subitem,
            .elementor-location-header .elementor-nav-menu--main > .elementor-nav-menu > li.ws-desktop-solution-subitem {
                display: none !important;
            }

            body:not(.home) #content,
            body:not(.home) .site-content,
            body:not(.home) #primary {
                margin-top: 0 !important;
                padding-top: 0 !important;
            }

            .elementor-element-371e27f,
            .elementor-element-725caeb9,
            .elementor-element-755ecc0,
            .elementor-element-3b1d60c {
                margin-top: -90px !important;
            }
        }
        <?php endif; ?>

        .wpml-ls-menu-item .wpml-ls-display,
        .wpml-ls-menu-item .wpml-ls-bracket {
            display: none !important;
        }

        .wpml-ls-menu-item > a {
            display: inline-flex !important;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
        }

        <?php if (in_array($current_lang, ['', 'zh-hant'], true)) : ?>
        .elementor-nav-menu--main > .elementor-nav-menu > li.wpml-ls-current-language > a .wpml-ls-native {
            font-size: 0 !important;
        }

        .elementor-nav-menu--main > .elementor-nav-menu > li.wpml-ls-current-language > a .wpml-ls-native::after {
            content: "Language";
            font-size: 16px !important;
        }
        <?php endif; ?>

        .elementor-nav-menu--main .wpml-ls-current-language > a.elementor-item::before {
            display: none !important;
            content: none !important;
            width: 0 !important;
            height: 0 !important;
            opacity: 0 !important;
            border: 0 !important;
            background: transparent !important;
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item > a::before {
            content: "";
            display: inline-block;
            flex: 0 0 auto;
            position: static !important;
            width: 12px;
            height: 8px;
            border: 1px solid rgba(0, 0, 0, 0.18);
            box-sizing: border-box;
            background: #f5f5f5;
            vertical-align: middle;
            opacity: 1 !important;
            transform: none !important;
        }

        .elementor-nav-menu .wpml-ls-menu-item > a::after,
        .elementor-nav-menu .wpml-ls-menu-item.current-menu-item > a::after,
        .elementor-nav-menu .wpml-ls-menu-item.current-menu-ancestor > a::after,
        .elementor-nav-menu .wpml-ls-menu-item:hover > a::after,
        .elementor-nav-menu .wpml-ls-menu-item:focus-within > a::after {
            display: none !important;
            content: none !important;
            width: 0 !important;
            height: 0 !important;
            opacity: 0 !important;
            border: 0 !important;
            background: transparent !important;
        }

        .elementor-nav-menu .wpml-ls-menu-item > a,
        .elementor-nav-menu .wpml-ls-menu-item > a:hover,
        .elementor-nav-menu .wpml-ls-menu-item > a:focus,
        .elementor-nav-menu .wpml-ls-menu-item.current-menu-item > a,
        .elementor-nav-menu .wpml-ls-menu-item.current-menu-ancestor > a {
            border-bottom: 0 !important;
            box-shadow: none !important;
            text-decoration: none !important;
        }

        .wpml-ls-menu-item .sub-menu {
            min-width: 150px !important;
            border-top: 0 !important;
        }

        .wpml-ls-menu-item .sub-menu a {
            width: 100%;
            justify-content: flex-start;
            padding: 7px 12px !important;
            font-size: 13px !important;
            line-height: 1.2 !important;
            color: #555 !important;
            background: #fff !important;
            white-space: nowrap;
            border-bottom: 0 !important;
            box-shadow: none !important;
            text-decoration: none !important;
        }

        .wpml-ls-menu-item .sub-menu a:hover,
        .wpml-ls-menu-item .sub-menu a:focus {
            color: #ed3f42 !important;
            background: #f7f7f7 !important;
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-zh-hant > a::before {
            background:
                radial-gradient(circle at 4px 4px, #fff 0 1.5px, transparent 1.6px),
                #fe0000;
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-en > a::before {
            background:
                linear-gradient(90deg, #3c3b6e 0 43%, transparent 43%),
                repeating-linear-gradient(180deg, #b22234 0 1px, #fff 1px 2px);
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-ja > a::before {
            background:
                radial-gradient(circle at center, #bc002d 0 2.5px, transparent 2.6px),
                #fff;
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-ko > a::before {
            background:
                radial-gradient(circle at center, #c60c30 0 2px, #003478 2.1px 3px, transparent 3.1px),
                #fff;
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-de > a::before {
            background: linear-gradient(180deg, #000 0 33%, #dd0000 33% 66%, #ffce00 66%);
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-es > a::before {
            background: linear-gradient(180deg, #aa151b 0 25%, #f1bf00 25% 75%, #aa151b 75%);
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-it > a::before {
            background: linear-gradient(90deg, #009246 0 33%, #fff 33% 66%, #ce2b37 66%);
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-pt-br > a::before {
            background:
                radial-gradient(circle at center, #002776 0 2.5px, transparent 2.6px),
                linear-gradient(135deg, transparent 30%, #ffdf00 30% 70%, transparent 70%),
                #009c3b;
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-ar > a::before {
            background: #006c35;
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-hi > a::before {
            background:
                radial-gradient(circle at center, #06038d 0 1.4px, transparent 1.5px),
                linear-gradient(180deg, #ff9933 0 33%, #fff 33% 66%, #138808 66%);
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-id > a::before {
            background: linear-gradient(180deg, #ff0000 0 50%, #fff 50%);
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-th > a::before {
            background: linear-gradient(180deg, #a51931 0 16%, #fff 16% 28%, #2d2a4a 28% 72%, #fff 72% 84%, #a51931 84%);
        }

        .wpml-ls-menu-item .sub-menu .wpml-ls-item-vi > a::before {
            background:
                radial-gradient(circle at center, #ffeb00 0 2px, transparent 2.1px),
                #da251d;
        }

        .elementor-element-68ab2b5 .elementor-swiper-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            color: #777;
            z-index: 20;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .elementor-element-68ab2b5 .elementor-swiper-button-prev {
            left: 22px;
        }

        .elementor-element-68ab2b5 .elementor-swiper-button-next {
            right: 22px;
        }

        .elementor-element-68ab2b5 .elementor-swiper-button::before {
            content: "";
            display: block;
            width: 18px;
            height: 18px;
            border-top: 2px solid currentColor;
            border-right: 2px solid currentColor;
        }

        .elementor-element-68ab2b5 .elementor-swiper-button-prev::before {
            transform: rotate(-135deg);
        }

        .elementor-element-68ab2b5 .elementor-swiper-button-next::before {
            transform: rotate(45deg);
        }

        .elementor-element-68ab2b5,
        .elementor-element-68ab2b5 > .elementor-widget-container,
        .elementor-element-68ab2b5 .swiper,
        .elementor-element-68ab2b5 .elementor-loop-container {
            overflow: hidden !important;
        }

        .elementor-element-68ab2b5 .swiper-wrapper {
            align-items: stretch;
        }

        .elementor-element-68ab2b5 .swiper-slide {
            overflow: hidden !important;
            background: transparent;
        }

        .elementor-element-68ab2b5 .swiper-slide:not(.swiper-slide-active) {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .elementor-element-68ab2b5 .swiper-slide:not(.swiper-slide-active) * {
            visibility: hidden !important;
            opacity: 0 !important;
        }

        .elementor-element-68ab2b5 .swiper-slide.swiper-slide-active {
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }

        .elementor-element-68ab2b5 .swiper-slide.swiper-slide-active .elementor-element-2779358,
        .elementor-element-68ab2b5 .swiper-slide.swiper-slide-active .elementor-element-2779358 .elementor-widget-container,
        .elementor-element-68ab2b5 .swiper-slide.swiper-slide-active .elementor-element-2779358 img {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .elementor-element-68ab2b5 .swiper-slide.swiper-slide-active .elementor-element-2779358 img {
            width: 100% !important;
            min-height: 600px;
            object-fit: cover;
        }

        .elementor-element-68ab2b5 .swiper-slide .elementor-element-8576e82 {
            position: relative;
            z-index: 3;
        }

        .elementor-element-68ab2b5 .elementor-swiper-button > * {
            display: none !important;
        }

        html[dir="rtl"] .elementor-element-68ab2b5 .elementor-container {
            direction: ltr;
        }

        html[dir="rtl"] .elementor-element-68ab2b5 .elementor-element-8576e82 {
            direction: rtl;
            text-align: right;
        }

        html[dir="rtl"] .elementor-element-68ab2b5 .elementor-element-4abbd53 {
            direction: ltr;
        }

        .ws-floating-line {
            position: fixed;
            right: 28px;
            bottom: 88px;
            z-index: 2147483000;
            display: block;
            width: 58px;
            height: 58px;
            border-radius: 999px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.22);
            text-decoration: none;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto;
        }

        .ws-floating-line svg {
            display: block;
            width: 58px;
            height: 58px;
        }

        <?php if (is_front_page()) : ?>
        .elementor-element-c6a553d:not(.elementor-motion-effects-element-type-background) > .elementor-widget-wrap,
        .elementor-element-c6a553d > .elementor-widget-wrap > .elementor-motion-effects-container > .elementor-motion-effects-layer {
            background-image: url("/wp-content/uploads/2024/03/company-profile-1-scaled.jpg") !important;
        }
        <?php endif; ?>

        @media (max-width: 767px) {
            .ws-floating-line {
                right: 18px;
                bottom: 76px;
                width: 58px;
                height: 58px;
            }

            .elementor-element-68ab2b5 .swiper-slide.swiper-slide-active .elementor-element-2779358 img {
                min-height: 360px;
            }
        }
    </style>
    <?php
}, 30);

add_action('wp_footer', function() {
    if (is_admin()) return;

    $line_url = 'https://line.me/R/ti/p/@927ekwel';
    ?>
    <a class="ws-floating-line" href="<?php echo esc_url($line_url); ?>" target="_blank" rel="noopener" aria-label="LINE">
        <svg xmlns="http://www.w3.org/2000/svg" width="58" height="58" viewBox="0 0 58 58" aria-hidden="true" focusable="false">
            <circle cx="29" cy="29" r="29" fill="#ED3F42"></circle>
            <path d="M46.6398 26.1579C46.6398 18.1899 38.6909 11.7305 28.89 11.7305C19.089 11.7305 11.1401 18.1899 11.1401 26.1579C11.1401 33.3135 17.4683 39.2702 25.996 40.3919C26.5748 40.5079 27.3465 40.7786 27.578 41.2815C27.7709 41.7456 27.6938 42.4419 27.6166 42.906C27.6166 42.906 27.4237 44.1437 27.3465 44.4145C27.2693 44.8787 26.9992 46.1551 28.89 45.3815C30.7807 44.5692 39.1154 39.3475 42.8197 35.0541C45.405 32.1918 46.6398 29.3296 46.6398 26.1579Z" fill="#FFFFFF"></path>
            <path d="M25.2632 22.29H24.0284C23.8355 22.29 23.6812 22.4448 23.6812 22.6382V30.374C23.6812 30.5674 23.8355 30.7221 24.0284 30.7221H25.2632C25.4561 30.7221 25.6105 30.5674 25.6105 30.374V22.6382C25.6105 22.4448 25.4561 22.29 25.2632 22.29Z" fill="#ED3F42"></path>
            <path d="M33.8294 22.29H32.5946C32.4017 22.29 32.2473 22.4448 32.2473 22.6382V27.241L28.6973 22.4448C28.6973 22.4448 28.6973 22.4061 28.6588 22.4061C28.6588 22.4061 28.6588 22.4061 28.6202 22.3674C28.6202 22.3674 28.6202 22.3674 28.5816 22.3674C28.5816 22.3674 28.5816 22.3674 28.543 22.3674C28.543 22.3674 28.543 22.3674 28.5044 22.3674H27.2696C27.0767 22.3674 26.9224 22.5221 26.9224 22.7155V30.4514C26.9224 30.6448 27.0767 30.7995 27.2696 30.7995H28.5044C28.6973 30.7995 28.8517 30.6448 28.8517 30.4514V25.8485L32.4017 30.6448C32.4402 30.6835 32.4402 30.7221 32.4788 30.7221C32.4788 30.7221 32.4788 30.7221 32.5174 30.7221C32.5174 30.7221 32.5174 30.7221 32.556 30.7221C32.5946 30.7221 32.5946 30.7221 32.6332 30.7221H33.8679C34.0609 30.7221 34.2152 30.5674 34.2152 30.374V22.6382C34.1766 22.4448 34.0223 22.29 33.8294 22.29Z" fill="#ED3F42"></path>
            <path d="M22.2922 28.8265H18.8966V22.6765C18.8966 22.4831 18.7423 22.3284 18.5493 22.3284H17.3146C17.1216 22.3284 16.9673 22.4831 16.9673 22.6765V30.4123C16.9673 30.4897 17.0059 30.6057 17.083 30.6444C17.1602 30.7218 17.2374 30.7218 17.3146 30.7218H22.2922C22.4852 30.7218 22.6395 30.5671 22.6395 30.3737V29.1359C22.6395 28.9812 22.4852 28.8265 22.2922 28.8265Z" fill="#ED3F42"></path>
            <path d="M40.7361 24.2623C40.929 24.2623 41.0833 24.1076 41.0833 23.9142V22.6765C41.0833 22.4831 40.929 22.3284 40.7361 22.3284H35.7584C35.6812 22.3284 35.5655 22.367 35.5269 22.4444C35.4497 22.5218 35.4497 22.5991 35.4497 22.6765V30.4123C35.4497 30.4897 35.4883 30.6057 35.5655 30.6444C35.6426 30.7218 35.7198 30.7605 35.797 30.7605H40.7361C40.929 30.7605 41.0833 30.6057 41.0833 30.4123V29.1746C41.0833 28.9812 40.929 28.8265 40.7361 28.8265H37.3404V27.5114H40.7361C40.929 27.5114 41.0833 27.3567 41.0833 27.1633V25.9255C41.0833 25.7322 40.929 25.5774 40.7361 25.5774H37.3404V24.2623H40.7361Z" fill="#ED3F42"></path>
        </svg>
    </a>
    <?php
}, 40);
