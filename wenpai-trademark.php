<?php
/**
 * Plugin Name: 文派商标插件
 * Plugin URI: https://wenpai.org/
 * Description: 自动为指定术语添加商标符号（™、®、©）的WordPress插件
 * Version: 1.0.0
 * Author: 文派
 * Author URI: https://wenpai.org/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wenpai-trademark
 * Domain Path: /languages
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 检查WordPress环境
if (!function_exists('plugin_dir_url') || !function_exists('plugin_dir_path') || !function_exists('plugin_basename')) {
    return;
}

// 定义插件常量
define('WENPAI_TRADEMARK_VERSION', '1.0.0');
define('WENPAI_TRADEMARK_PLUGIN_URL', function_exists('plugin_dir_url') ? plugin_dir_url(__FILE__) : '');
define('WENPAI_TRADEMARK_PLUGIN_PATH', function_exists('plugin_dir_path') ? plugin_dir_path(__FILE__) : dirname(__FILE__) . '/');
define('WENPAI_TRADEMARK_PLUGIN_BASENAME', function_exists('plugin_basename') ? plugin_basename(__FILE__) : basename(__FILE__));
define('WENPAI_TRADEMARK_PLUGIN_DIR', dirname(__FILE__));
define('WENPAI_TRADEMARK_ASSETS_URL', WENPAI_TRADEMARK_PLUGIN_URL . 'assets/');
define('WENPAI_TRADEMARK_INCLUDES_PATH', WENPAI_TRADEMARK_PLUGIN_PATH . 'includes/');
define('WENPAI_TRADEMARK_TEMPLATES_PATH', WENPAI_TRADEMARK_PLUGIN_PATH . 'templates/');
define('WENPAI_TRADEMARK_FILE', __FILE__);

// 插件主类
class WenpaiTrademark {
    
    private static $instance = null;
    private static $cached_terms = null;
    private $replacing = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // 检查WordPress函数是否存在
        if (!function_exists('register_activation_hook') || !function_exists('add_action')) {
            return;
        }
        
        // 注册激活和停用钩子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // 初始化钩子
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        
        // AJAX钩子
        add_action('wp_ajax_wenpai_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_wenpai_import_terms', array($this, 'ajax_import_terms'));
        add_action('wp_ajax_wenpai_export_terms', array($this, 'ajax_export_terms'));
        add_action('wp_ajax_wenpai_edit_term', array($this, 'ajax_edit_term'));
        add_action('wp_ajax_wenpai_delete_term', array($this, 'ajax_delete_term'));
        
        // 应用内容过滤器
        $this->apply_filters();
    }
    
    public function load_textdomain() {
        if (function_exists('load_plugin_textdomain') && function_exists('plugin_basename')) {
            load_plugin_textdomain('wenpai-trademark', false, plugin_basename(dirname(__FILE__)) . '/languages/');
        }
    }
    
    // ========== ACTIVATION/DEACTIVATION ==========
    
    public function activate() {
        $default_terms = [
            'WordPress' => [
                'symbol' => '®',
                'position' => 'after',
                'density' => 1,
                'case_sensitive' => true,
                'whole_word' => true
            ]
        ];
        
        if (function_exists('add_option')) {
            add_option('wenpai_trademark_terms', $default_terms);
            add_option('wenpai_trademark_scopes', [
                'content' => true,
                'title' => true,
                'widgets' => false,
                'comments' => false
            ]);
            add_option('wenpai_trademark_excluded_post_ids', '');
            add_option('wenpai_trademark_excluded_tags', 'a,code,pre');
        }
        
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules();
        }
    }
    
    public function deactivate() {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
    
    // ========== SETTINGS MANAGEMENT ==========
    
    public function register_settings() {
        if (function_exists('register_setting')) {
            register_setting('wenpai_trademark_settings', 'wenpai_trademark_terms', [
                'sanitize_callback' => [$this, 'sanitize_terms'],
                'default' => []
            ]);
            
            register_setting('wenpai_trademark_settings', 'wenpai_trademark_scopes', [
                'sanitize_callback' => [$this, 'sanitize_scopes'],
                'default' => [
                    'content' => true,
                    'title' => true,
                    'widgets' => true,
                    'comments' => true,
                ]
            ]);
            
            register_setting('wenpai_trademark_settings', 'wenpai_trademark_excluded_post_ids', [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            ]);
            
            register_setting('wenpai_trademark_settings', 'wenpai_trademark_excluded_tags', [
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'a,code,pre'
            ]);
        }
    }
    
    public function sanitize_terms($terms) {
        if (!is_array($terms)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($terms as $term => $data) {
            $clean_term = function_exists('sanitize_text_field') ? sanitize_text_field($term) : trim(strip_tags($term));
            if (empty($clean_term)) continue;
            
            $sanitized[$clean_term] = [
                'symbol' => function_exists('sanitize_text_field') ? sanitize_text_field($data['symbol'] ?? '™') : trim(strip_tags($data['symbol'] ?? '™')),
                'position' => in_array($data['position'] ?? 'after', ['before', 'after']) ? $data['position'] : 'after',
                'density' => max(1, min(10, intval($data['density'] ?? 1))),
                'case_sensitive' => !empty($data['case_sensitive']),
                'whole_word' => !empty($data['whole_word'])
            ];
        }
        
        return $sanitized;
    }
    
    public function sanitize_scopes($scopes) {
        if (!is_array($scopes)) {
            return ['content' => true];
        }
        
        $valid_scopes = ['content', 'title', 'widgets', 'comments'];
        $sanitized = [];
        
        foreach ($valid_scopes as $scope) {
            $sanitized[$scope] = !empty($scopes[$scope]);
        }
        
        return $sanitized;
    }
    
    public function get_terms() {
        if (null === self::$cached_terms) {
            self::$cached_terms = function_exists('get_option') ? get_option('wenpai_trademark_terms', []) : [];
        }
        return self::$cached_terms;
    }
    
    public function get_scopes() {
        return function_exists('get_option') ? get_option('wenpai_trademark_scopes', ['content' => true]) : ['content' => true];
    }
    
    // ========== TEXT REPLACEMENT ENGINE ==========
    
    public function apply_filters() {
        $scopes = $this->get_scopes();
        
        if (function_exists('add_filter')) {
            if (!empty($scopes['content'])) {
                add_filter('the_content', [$this, 'replace_terms'], 20);
            }
            if (!empty($scopes['title'])) {
                add_filter('the_title', [$this, 'replace_terms'], 20);
            }
            if (!empty($scopes['widgets'])) {
                add_filter('widget_text', [$this, 'replace_terms'], 20);
            }
            if (!empty($scopes['comments'])) {
                add_filter('comment_text', [$this, 'replace_terms'], 20);
            }
        }
    }
    
    public function replace_terms($text) {
        if ($this->replacing || empty($text) || !is_string($text)) {
            return $text;
        }
        
        if ($this->should_skip_replacement()) {
            return $text;
        }
        
        $this->replacing = true;
        
        $terms = $this->get_terms();
        if (empty($terms)) {
            $this->replacing = false;
            return $text;
        }
        
        $excluded_tags = function_exists('get_option') ? get_option('wenpai_trademark_excluded_tags', 'a,code,pre') : 'a,code,pre';
        $tags_array = array_map('trim', explode(',', $excluded_tags));
        
        $processed_text = $this->process_html_content($text, $terms, $tags_array);
        
        $this->replacing = false;
        return $processed_text;
    }
    
    private function should_skip_replacement() {
        if (function_exists('is_singular') && !is_singular()) {
            return false;
        }
        
        $excluded_ids = function_exists('get_option') ? get_option('wenpai_trademark_excluded_post_ids', '') : '';
        if (empty($excluded_ids)) {
            return false;
        }
        
        $excluded_array = array_map('trim', explode(',', $excluded_ids));
        $current_id = function_exists('get_the_ID') ? get_the_ID() : 0;
        return in_array((string)$current_id, $excluded_array);
    }
    
    private function process_html_content($content, $terms, $excluded_tags) {
        if (empty($excluded_tags)) {
            return $this->replace_text_content($content, $terms);
        }
        
        // Build pattern for excluded tags without backreferences
        $tag_names = implode('|', array_map('preg_quote', $excluded_tags));
        $pattern = '/(<(' . $tag_names . ')\b[^>]*>.*?<\/\2>)|([^<]+)/is';
        
        return preg_replace_callback(
            $pattern,
            function($matches) use ($terms) {
                if (!empty($matches[1])) {
                    // This is an excluded tag, return as-is
                    return $matches[1];
                }
                // This is text content, process it
                return $this->replace_text_content($matches[3], $terms);
            },
            $content
        );
    }
    
    private function replace_text_content($text, $terms) {
        foreach ($terms as $term => $config) {
            $pattern = $this->build_pattern($term, $config);
            $replacement = $this->build_replacement($term, $config);
            
            $limit = max(1, intval($config['density']));
            $text = preg_replace($pattern, $replacement, $text, $limit);
        }
        
        return $text;
    }
    
    private function build_pattern($term, $config) {
        $escaped_term = preg_quote($term, '/');
        
        if (!empty($config['whole_word'])) {
            // 检查术语是否包含中文字符
            if (preg_match('/[\x{4e00}-\x{9fff}]/u', $term)) {
                // 对于中文字符，使用更灵活的边界检测
                // 确保术语前后不是字母、数字或中文字符
                $pattern = '/(?<![\p{L}\p{N}])' . $escaped_term . '(?![\p{L}\p{N}])/u';
            } else {
                // 对于英文，使用传统的单词边界
                $pattern = '/\\b' . $escaped_term . '\\b/';
            }
        } else {
            $pattern = '/' . $escaped_term . '/';
        }
        
        if (empty($config['case_sensitive'])) {
            $pattern .= 'i';
        }
        
        // 确保使用Unicode模式
        if (strpos($pattern, '/u') === false && strpos($pattern, 'u') === false) {
            $pattern .= 'u';
        }
        
        return $pattern;
    }
    
    private function build_replacement($term, $config) {
        $symbol = $config['symbol'] ?? '™';
        $position = $config['position'] ?? 'after';
        
        if ($position === 'before') {
            return $symbol . '$0';
        }
        
        return '$0' . $symbol;
    }
    
    // ========== ADMIN INTERFACE ==========
    
    public function add_admin_menu() {
        if (function_exists('add_options_page')) {
            add_options_page(
                'WenPai Trademark Symbol Settings',
                'Trademark Symbol',
                'manage_options',
                'wenpai-trademark-settings',
                [$this, 'render_admin_page']
            );
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_wenpai-trademark-settings' !== $hook) {
            return;
        }
        
        // Enqueue admin styles
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style(
                'wenpai-admin-styles',
                WENPAI_TRADEMARK_PLUGIN_URL . 'admin-styles.css',
                [],
                WENPAI_TRADEMARK_VERSION
            );
        }
        
        // Enqueue jQuery and localize script
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('jquery');
        }
        
        if (function_exists('wp_localize_script')) {
            wp_localize_script('jquery', 'wenpaiAdmin', [
                'ajaxUrl' => function_exists('admin_url') ? admin_url('admin-ajax.php') : '',
                'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wenpai_trademark_nonce') : '',
                'strings' => [
                    'confirmDelete' => function_exists('__') ? __('Are you sure you want to delete this term?', 'wenpai-trademark') : 'Are you sure you want to delete this term?',
                    'yes' => function_exists('__') ? __('Yes', 'wenpai-trademark') : 'Yes',
                    'no' => function_exists('__') ? __('No', 'wenpai-trademark') : 'No',
                    'edit' => function_exists('__') ? __('Edit', 'wenpai-trademark') : 'Edit',
                    'delete' => function_exists('__') ? __('Delete', 'wenpai-trademark') : 'Delete',
                    'termAdded' => function_exists('__') ? __('Term added successfully!', 'wenpai-trademark') : 'Term added successfully!',
                    'termUpdated' => function_exists('__') ? __('Term updated successfully!', 'wenpai-trademark') : 'Term updated successfully!',
                    'termDeleted' => function_exists('__') ? __('Term deleted successfully!', 'wenpai-trademark') : 'Term deleted successfully!',
                    'pleaseEnterCustomSymbol' => function_exists('__') ? __('Please enter a custom symbol.', 'wenpai-trademark') : 'Please enter a custom symbol.',
                    'pleaseSelectFile' => function_exists('__') ? __('Please select a CSV file.', 'wenpai-trademark') : 'Please select a CSV file.',
                    'addTerm' => function_exists('__') ? __('Add Term', 'wenpai-trademark') : 'Add Term',
                    'updateTerm' => function_exists('__') ? __('Update Term', 'wenpai-trademark') : 'Update Term',
                    'importSuccess' => function_exists('__') ? __('Terms imported successfully!', 'wenpai-trademark') : 'Terms imported successfully!',
                    'exportError' => function_exists('__') ? __('Export failed. Please try again.', 'wenpai-trademark') : 'Export failed. Please try again.',
                    'saveSuccess' => function_exists('__') ? __('Settings saved successfully!', 'wenpai-trademark') : 'Settings saved successfully!',
                    'saveError' => function_exists('__') ? __('Failed to save settings. Please try again.', 'wenpai-trademark') : 'Failed to save settings. Please try again.'
                ]
            ]);
        }
    }
    
    public function enqueue_frontend_scripts() {
        // 前端脚本加载方法 - 目前不需要加载任何前端脚本
        // 如果将来需要前端功能，可以在这里添加
    }
    
    public function render_admin_page() {
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            return;
        }
        
        $template_path = WENPAI_TRADEMARK_PLUGIN_PATH . 'admin-template.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }
    
    // ========== AJAX HANDLERS ==========
    
    private function verify_nonce() {
        if (function_exists('wp_verify_nonce') && !wp_verify_nonce($_POST['nonce'] ?? '', 'wenpai_trademark_nonce')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Security check failed']);
            }
        }
    }
    
    public function ajax_save_settings() {
        $this->verify_nonce();
        
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => function_exists('__') ? __('Permission denied', 'wenpai-trademark') : 'Permission denied']);
            }
        }
        
        try {
            if (isset($_POST['terms'])) {
                $new_terms = $this->sanitize_terms($_POST['terms']);
                
                // Check if this is adding a single new term or updating all terms
                if (count($new_terms) === 1 && !isset($_POST['replace_all'])) {
                    // This is adding a single new term, merge with existing
                    $existing_terms = $this->get_terms();
                    $merged_terms = array_merge($existing_terms, $new_terms);
                    if (function_exists('update_option')) {
                        update_option('wenpai_trademark_terms', $merged_terms);
                    }
                } else {
                    // This is replacing all terms
                    if (function_exists('update_option')) {
                        update_option('wenpai_trademark_terms', $new_terms);
                    }
                }
                self::$cached_terms = null;
            }
            
            if (isset($_POST['scopes'])) {
                $scopes = $this->sanitize_scopes($_POST['scopes']);
                if (function_exists('update_option')) {
                    update_option('wenpai_trademark_scopes', $scopes);
                }
            }
            
            if (isset($_POST['exclude_post_ids'])) {
                $sanitized_ids = function_exists('sanitize_text_field') ? sanitize_text_field($_POST['exclude_post_ids']) : trim(strip_tags($_POST['exclude_post_ids']));
                if (function_exists('update_option')) {
                    update_option('wenpai_trademark_excluded_post_ids', $sanitized_ids);
                }
            }
            
            if (isset($_POST['exclude_html_tags'])) {
                $sanitized_tags = function_exists('sanitize_text_field') ? sanitize_text_field($_POST['exclude_html_tags']) : trim(strip_tags($_POST['exclude_html_tags']));
                if (function_exists('update_option')) {
                    update_option('wenpai_trademark_excluded_tags', $sanitized_tags);
                }
            }
            
            if (function_exists('wp_send_json_success')) {
                wp_send_json_success(['message' => 'Settings saved successfully']);
            }
            
        } catch (Exception $e) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Failed to save settings: ' . $e->getMessage()]);
            }
        }
    }
    
    public function ajax_import_terms() {
        $this->verify_nonce();
        
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }
        }
        
        if (!isset($_FILES['csv_file'])) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'No file uploaded']);
            }
        }
        
        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'File upload error']);
            }
        }
        
        $csv_data = file_get_contents($file['tmp_name']);
        $lines = str_getcsv($csv_data, "\n");
        
        $imported_terms = [];
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (count($data) >= 2) {
                $imported_terms[trim($data[0])] = [
                    'symbol' => trim($data[1]),
                    'position' => isset($data[2]) ? trim($data[2]) : 'after',
                    'density' => isset($data[3]) ? max(1, intval($data[3])) : 1,
                    'case_sensitive' => isset($data[4]) ? !empty($data[4]) : false,
                    'whole_word' => isset($data[5]) ? !empty($data[5]) : true
                ];
            }
        }
        
        if (empty($imported_terms)) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'No valid terms found in CSV']);
            }
        }
        
        $current_terms = $this->get_terms();
        $merged_terms = array_merge($current_terms, $imported_terms);
        
        if (function_exists('update_option')) {
            update_option('wenpai_trademark_terms', $merged_terms);
        }
        self::$cached_terms = null;
        
        if (function_exists('wp_send_json_success')) {
            wp_send_json_success([
                'message' => sprintf('Successfully imported %d terms', count($imported_terms)),
                'terms' => $merged_terms
            ]);
        }
    }
    
    public function ajax_export_terms() {
        $this->verify_nonce();
        
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }
        }
        
        $terms = $this->get_terms();
        if (empty($terms)) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'No terms to export']);
            }
        }
        
        $csv_content = "Term,Symbol,Position,Density,Case Sensitive,Whole Word\n";
        foreach ($terms as $term => $config) {
            $csv_content .= sprintf(
                "\"%s\",\"%s\",\"%s\",%d,%s,%s\n",
                $term,
                $config['symbol'],
                $config['position'],
                $config['density'],
                $config['case_sensitive'] ? 'true' : 'false',
                $config['whole_word'] ? 'true' : 'false'
            );
        }
        
        if (function_exists('wp_send_json_success')) {
            wp_send_json_success(['csv' => $csv_content]);
        }
    }
    
    public function ajax_delete_term() {
        $this->verify_nonce();
        
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }
        }
        
        $term = function_exists('sanitize_text_field') ? sanitize_text_field($_POST['term'] ?? '') : trim(strip_tags($_POST['term'] ?? ''));
        if (empty($term)) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => function_exists('__') ? __('Term not specified', 'wenpai-trademark') : 'Term not specified']);
            }
        }
        
        $terms = $this->get_terms();
        if (!isset($terms[$term])) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => function_exists('__') ? __('Term not found', 'wenpai-trademark') : 'Term not found']);
            }
        }
        
        unset($terms[$term]);
        if (function_exists('update_option')) {
            update_option('wenpai_trademark_terms', $terms);
        }
        self::$cached_terms = null;
        
        if (function_exists('wp_send_json_success')) {
            wp_send_json_success(['message' => 'Term deleted successfully']);
        }
    }
    
    public function ajax_edit_term() {
        $this->verify_nonce();
        
        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }
        }
        
        $old_term = function_exists('sanitize_text_field') ? sanitize_text_field($_POST['old_term'] ?? '') : trim(strip_tags($_POST['old_term'] ?? ''));
        $new_term = function_exists('sanitize_text_field') ? sanitize_text_field($_POST['new_term'] ?? '') : trim(strip_tags($_POST['new_term'] ?? ''));
        $symbol = function_exists('sanitize_text_field') ? sanitize_text_field($_POST['symbol'] ?? '™') : trim(strip_tags($_POST['symbol'] ?? '™'));
        $position = in_array($_POST['position'] ?? 'after', ['before', 'after']) ? $_POST['position'] : 'after';
        $density = max(1, min(10, intval($_POST['density'] ?? 1)));
        $case_sensitive = !empty($_POST['case_sensitive']);
        $whole_word = !empty($_POST['whole_word']);
        
        if (empty($old_term) || empty($new_term)) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => function_exists('__') ? __('Term names cannot be empty', 'wenpai-trademark') : 'Term names cannot be empty']);
            }
        }
        
        $terms = $this->get_terms();
        
        // Debug: Log the terms and search key
        error_log('Edit Term Debug - Old term: "' . $old_term . '" (length: ' . strlen($old_term) . ')');
        error_log('Edit Term Debug - Available terms: ' . print_r(array_keys($terms), true));
        
        if (!isset($terms[$old_term])) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error([
                    'message' => function_exists('__') ? __('Original term not found', 'wenpai-trademark') : 'Original term not found',
                    'debug' => [
                        'old_term' => $old_term,
                        'old_term_length' => strlen($old_term),
                        'old_term_bytes' => bin2hex($old_term),
                        'available_terms' => array_keys($terms)
                    ]
                ]);
            }
        }
        
        // If term name changed, check if new name already exists
        if ($old_term !== $new_term && isset($terms[$new_term])) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => function_exists('__') ? __('A term with this name already exists', 'wenpai-trademark') : 'A term with this name already exists']);
            }
        }
        
        // Remove old term if name changed
        if ($old_term !== $new_term) {
            unset($terms[$old_term]);
        }
        
        // Add/update term with new configuration
        $terms[$new_term] = [
            'symbol' => $symbol,
            'position' => $position,
            'density' => $density,
            'case_sensitive' => $case_sensitive,
            'whole_word' => $whole_word
        ];
        
        if (function_exists('update_option')) {
            update_option('wenpai_trademark_terms', $terms);
        }
        $this->cached_terms = null;
        
        if (function_exists('wp_send_json_success')) {
            wp_send_json_success([
                'message' => function_exists('__') ? __('Term updated successfully!', 'wenpai-trademark') : 'Term updated successfully!',
                'term' => $new_term,
                'config' => $terms[$new_term]
            ]);
        }
    }
}

// Initialize the plugin
WenpaiTrademark::get_instance();
