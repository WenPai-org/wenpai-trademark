<?php
/**
 * Plugin Name: WenPai Trademark Symbol
 * Description: Automatically adds trademark, registered, or copyright symbols to specified terms with enhanced controls.
 * Version: 2.0.0
 * Author: WenPai.org
 * License: GPLv3 or later
 * Text Domain: wenpai-trademark
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WENPAI_TRADEMARK_VERSION', '2.0.0');
define('WENPAI_TRADEMARK_PATH', \plugin_dir_path(__FILE__));
define('WENPAI_TRADEMARK_URL', \plugin_dir_url(__FILE__));
define('WENPAI_TRADEMARK_FILE', __FILE__);

/**
 * Main plugin class with all core functionality
 */
class WenpaiTrademarkPlugin {
    
    private static $instance = null;
    private $replacing = false;
    private $cached_terms = null;
    
    // Option constants
    const OPTION_TERMS = 'wenpai_trademark_terms';
    const OPTION_SCOPES = 'wenpai_trademark_scopes';
    const OPTION_EXCLUDED_POST_IDS = 'wenpai_trademark_excluded_post_ids';
    const OPTION_EXCLUDED_TAGS = 'wenpai_trademark_excluded_tags';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        \register_activation_hook(WENPAI_TRADEMARK_FILE, [$this, 'activate']);
        \register_deactivation_hook(WENPAI_TRADEMARK_FILE, [$this, 'deactivate']);
        
        \add_action('plugins_loaded', [$this, 'init']);
        \add_action('admin_init', [$this, 'register_settings']);
        \add_action('wp_loaded', [$this, 'apply_filters']);
        \add_action('admin_menu', [$this, 'register_admin_page']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX hooks
        \add_action('wp_ajax_wenpai_save_settings', [$this, 'ajax_save_settings']);
        \add_action('wp_ajax_wenpai_import_terms', [$this, 'ajax_import_terms']);
        \add_action('wp_ajax_wenpai_export_terms', [$this, 'ajax_export_terms']);
        \add_action('wp_ajax_wenpai_delete_term', [$this, 'ajax_delete_term']);
    }
    
    public function init() {
        \load_plugin_textdomain('wenpai-trademark', false, dirname(\plugin_basename(WENPAI_TRADEMARK_FILE)) . '/languages/');
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
        
        \add_option(self::OPTION_TERMS, $default_terms);
        \add_option(self::OPTION_SCOPES, [
            'content' => true,
            'title' => true,
            'widgets' => false,
            'comments' => false
        ]);
        \add_option(self::OPTION_EXCLUDED_POST_IDS, '');
        \add_option(self::OPTION_EXCLUDED_TAGS, 'a,code,pre');
        
        \flush_rewrite_rules();
    }
    
    public function deactivate() {
        \wp_cache_flush();
    }
    
    // ========== SETTINGS MANAGEMENT ==========
    
    public function register_settings() {
        \register_setting('wenpai_trademark_settings', self::OPTION_TERMS, [
            'sanitize_callback' => [$this, 'sanitize_terms'],
            'default' => []
        ]);
        
        \register_setting('wenpai_trademark_settings', self::OPTION_SCOPES, [
            'sanitize_callback' => [$this, 'sanitize_scopes'],
            'default' => [
                'content' => true,
                'title' => true,
                'widgets' => true,
                'comments' => true,
            ]
        ]);
        
        \register_setting('wenpai_trademark_settings', self::OPTION_EXCLUDED_POST_IDS, [
            'sanitize_callback' => '\sanitize_text_field',
            'default' => ''
        ]);
        
        \register_setting('wenpai_trademark_settings', self::OPTION_EXCLUDED_TAGS, [
            'sanitize_callback' => '\sanitize_text_field',
            'default' => 'a,code,pre'
        ]);
    }
    
    public function sanitize_terms($terms) {
        if (!is_array($terms)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($terms as $term => $data) {
            $clean_term = \sanitize_text_field($term);
            if (empty($clean_term)) continue;
            
            $sanitized[$clean_term] = [
                'symbol' => \sanitize_text_field($data['symbol'] ?? '™'),
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
        if (null === $this->cached_terms) {
            $this->cached_terms = \get_option(self::OPTION_TERMS, []);
        }
        return $this->cached_terms;
    }
    
    public function get_scopes() {
        return \get_option(self::OPTION_SCOPES, ['content' => true]);
    }
    
    // ========== TEXT REPLACEMENT ENGINE ==========
    
    public function apply_filters() {
        $scopes = $this->get_scopes();
        
        if (!empty($scopes['content'])) {
            \add_filter('the_content', [$this, 'replace_terms'], 20);
        }
        if (!empty($scopes['title'])) {
            \add_filter('the_title', [$this, 'replace_terms'], 20);
        }
        if (!empty($scopes['widgets'])) {
            \add_filter('widget_text', [$this, 'replace_terms'], 20);
        }
        if (!empty($scopes['comments'])) {
            \add_filter('comment_text', [$this, 'replace_terms'], 20);
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
        
        $excluded_tags = \get_option(self::OPTION_EXCLUDED_TAGS, 'a,code,pre');
        $tags_array = array_map('trim', explode(',', $excluded_tags));
        
        $processed_text = $this->process_html_content($text, $terms, $tags_array);
        
        $this->replacing = false;
        return $processed_text;
    }
    
    private function should_skip_replacement() {
        if (!\is_singular()) {
            return false;
        }
        
        $excluded_ids = \get_option(self::OPTION_EXCLUDED_POST_IDS, '');
        if (empty($excluded_ids)) {
            return false;
        }
        
        $excluded_array = array_map('trim', explode(',', $excluded_ids));
        return in_array((string)\get_the_ID(), $excluded_array);
    }
    
    private function process_html_content($content, $terms, $excluded_tags) {
        if (empty($excluded_tags)) {
            return $this->replace_text_content($content, $terms);
        }
        
        $tag_pattern = '<(' . implode('|', array_map('preg_quote', $excluded_tags)) . ')\b[^>]*>.*?</\\1>';
        
        return preg_replace_callback(
            '/(' . $tag_pattern . ')|([^<]+)/is',
            function($matches) use ($terms) {
                if (!empty($matches[1])) {
                    return $matches[1];
                }
                return $this->replace_text_content($matches[2], $terms);
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
            $pattern = '/\\b' . $escaped_term . '\\b/';
        } else {
            $pattern = '/' . $escaped_term . '/';
        }
        
        if (empty($config['case_sensitive'])) {
            $pattern .= 'i';
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
    
    public function register_admin_page() {
        \add_options_page(
            'WenPai Trademark Symbol Settings',
            'Trademark Symbol',
            'manage_options',
            'wenpai-trademark-settings',
            [$this, 'render_admin_page']
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_wenpai-trademark-settings' !== $hook) {
            return;
        }
        
        // Enqueue admin styles
        \wp_enqueue_style(
            'wenpai-admin-styles',
            WENPAI_TRADEMARK_URL . 'admin-styles.css',
            [],
            WENPAI_TRADEMARK_VERSION
        );
        
        // Enqueue jQuery and localize script
        \wp_enqueue_script('jquery');
        \wp_localize_script('jquery', 'wenpaiAdmin', [
            'ajaxUrl' => \admin_url('admin-ajax.php'),
            'nonce' => \wp_create_nonce('wenpai_trademark_nonce'),
            'strings' => [
                'confirmDelete' => 'Are you sure you want to delete this term?',
                'importSuccess' => 'Terms imported successfully!',
                'exportError' => 'Export failed. Please try again.',
                'saveSuccess' => 'Settings saved successfully!',
                'saveError' => 'Failed to save settings. Please try again.',
            ]
        ]);
    }
    
    public function render_admin_page() {
        if (!\current_user_can('manage_options')) {
            return;
        }
        
        include WENPAI_TRADEMARK_PATH . 'admin-template.php';
    }
    
    // ========== AJAX HANDLERS ==========
    
    private function verify_nonce() {
        if (!\wp_verify_nonce($_POST['nonce'] ?? '', 'wenpai_trademark_nonce')) {
            \wp_send_json_error(['message' => 'Security check failed']);
        }
    }
    
    public function ajax_save_settings() {
        $this->verify_nonce();
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => 'Permission denied']);
        }
        
        try {
            if (isset($_POST['terms'])) {
                $terms = $this->sanitize_terms($_POST['terms']);
                \update_option(self::OPTION_TERMS, $terms);
                $this->cached_terms = null;
            }
            
            if (isset($_POST['scopes'])) {
                $scopes = $this->sanitize_scopes($_POST['scopes']);
                \update_option(self::OPTION_SCOPES, $scopes);
            }
            
            if (isset($_POST['exclude_post_ids'])) {
                \update_option(self::OPTION_EXCLUDED_POST_IDS, \sanitize_text_field($_POST['exclude_post_ids']));
            }
            
            if (isset($_POST['exclude_html_tags'])) {
                \update_option(self::OPTION_EXCLUDED_TAGS, \sanitize_text_field($_POST['exclude_html_tags']));
            }
            
            \wp_send_json_success(['message' => 'Settings saved successfully']);
            
        } catch (Exception $e) {
            \wp_send_json_error(['message' => 'Failed to save settings: ' . $e->getMessage()]);
        }
    }
    
    public function ajax_import_terms() {
        $this->verify_nonce();
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => 'Permission denied']);
        }
        
        if (!isset($_FILES['csv_file'])) {
            \wp_send_json_error(['message' => 'No file uploaded']);
        }
        
        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            \wp_send_json_error(['message' => 'File upload error']);
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
            \wp_send_json_error(['message' => 'No valid terms found in CSV']);
        }
        
        $current_terms = $this->get_terms();
        $merged_terms = array_merge($current_terms, $imported_terms);
        
        \update_option(self::OPTION_TERMS, $merged_terms);
        $this->cached_terms = null;
        
        \wp_send_json_success([
            'message' => sprintf('Successfully imported %d terms', count($imported_terms)),
            'terms' => $merged_terms
        ]);
    }
    
    public function ajax_export_terms() {
        $this->verify_nonce();
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $terms = $this->get_terms();
        if (empty($terms)) {
            \wp_send_json_error(['message' => 'No terms to export']);
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
        
        \wp_send_json_success(['csv' => $csv_content]);
    }
    
    public function ajax_delete_term() {
        $this->verify_nonce();
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $term = \sanitize_text_field($_POST['term'] ?? '');
        if (empty($term)) {
            \wp_send_json_error(['message' => 'Term not specified']);
        }
        
        $terms = $this->get_terms();
        if (!isset($terms[$term])) {
            \wp_send_json_error(['message' => 'Term not found']);
        }
        
        unset($terms[$term]);
        \update_option(self::OPTION_TERMS, $terms);
        $this->cached_terms = null;
        
        \wp_send_json_success(['message' => 'Term deleted successfully']);
    }
}

// Initialize the plugin
WenpaiTrademarkPlugin::get_instance();
