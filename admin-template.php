<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$plugin = WenpaiTrademark::get_instance();
$terms = $plugin->get_terms();
$scopes = $plugin->get_scopes();
$exclude_post_ids = function_exists('get_option') ? get_option('wenpai_trademark_excluded_post_ids', '') : '';
$exclude_html_tags = function_exists('get_option') ? get_option('wenpai_trademark_excluded_tags', 'a,code,pre') : 'a,code,pre';
?>

<div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?>
        <span style="font-size: 13px; padding-left: 10px;">
            <?php printf( esc_html__( 'Version: %s', 'wenpai-trademark' ), esc_html( WENPAI_TRADEMARK_VERSION ) ); ?>
        </span>
        <a href="https://sharecms.com/document" target="_blank" class="button button-secondary" style="margin-left: 10px;">
            <?php esc_html_e( 'Documentation', 'wenpai-trademark' ); ?>
        </a>
        <a href="https://meta.cyberforums.com/c/wenpai-org/" target="_blank" class="button button-secondary">
            <?php esc_html_e( 'Support', 'wenpai-trademark' ); ?>
        </a>
    </h1>
    
    <div id="wenpai-admin-notices"></div>
 <div class="wenpai-card">   
    <form id="wenpai-settings-form" method="post" action="options.php">
        <?php if (function_exists('settings_fields')) settings_fields('wenpai_trademark_settings'); ?>
        
        <!-- Tab Navigation -->
        <div class="wenpai-tabs">
            <button type="button" class="wenpai-tab active" data-tab="terms"><?php _e('Trademark Terms', 'wenpai-trademark'); ?></button>
            <button type="button" class="wenpai-tab" data-tab="scope"><?php _e('Application Scope', 'wenpai-trademark'); ?></button>
            <button type="button" class="wenpai-tab" data-tab="exclusion"><?php _e('Exclusion Settings', 'wenpai-trademark'); ?></button>
            <button type="button" class="wenpai-tab" data-tab="import-export"><?php _e('Import/Export', 'wenpai-trademark'); ?></button>
        </div>
        
        <div class="wenpai-content">
            <!-- Terms Management Tab -->
            <div class="wenpai-section" data-section="terms">
                <h2><?php _e('Trademark Terms Management', 'wenpai-trademark'); ?></h2>
                <p class="description"><?php _e('Manage the terms that will automatically receive trademark symbols. Configure each term with its specific symbol, position, and matching rules.', 'wenpai-trademark'); ?></p>
                <div class="inside">
            <table class="wp-list-table widefat fixed striped" id="terms-table">
                <thead>
                    <tr>
                        <th><?php _e('Term', 'wenpai-trademark'); ?></th>
                        <th><?php _e('Symbol', 'wenpai-trademark'); ?></th>
                        <th><?php _e('Position', 'wenpai-trademark'); ?></th>
                        <th><?php _e('Density', 'wenpai-trademark'); ?></th>
                        <th><?php _e('Case Sensitive', 'wenpai-trademark'); ?></th>
                        <th><?php _e('Whole Word', 'wenpai-trademark'); ?></th>
                        <th><?php _e('Actions', 'wenpai-trademark'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($terms as $term => $config): ?>
                    <tr>
                        <td><?php echo function_exists('esc_html') ? esc_html($term) : htmlspecialchars($term); ?></td>
                        <td><?php echo function_exists('esc_html') ? esc_html($config['symbol']) : htmlspecialchars($config['symbol']); ?></td>
                        <td><?php echo function_exists('esc_html') ? esc_html($config['position']) : htmlspecialchars($config['position']); ?></td>
                        <td><?php echo function_exists('esc_html') ? esc_html($config['density']) : htmlspecialchars($config['density']); ?></td>
                        <td><?php echo $config['case_sensitive'] ? (function_exists('__') ? __('Yes', 'wenpai-trademark') : 'Yes') : (function_exists('__') ? __('No', 'wenpai-trademark') : 'No'); ?></td>
                        <td><?php echo $config['whole_word'] ? (function_exists('__') ? __('Yes', 'wenpai-trademark') : 'Yes') : (function_exists('__') ? __('No', 'wenpai-trademark') : 'No'); ?></td>
                        <td>
                            <button type="button" class="button edit-term" 
                                    data-term="<?php echo function_exists('esc_attr') ? esc_attr($term) : htmlspecialchars($term); ?>"
                                    data-symbol="<?php echo function_exists('esc_attr') ? esc_attr($config['symbol']) : htmlspecialchars($config['symbol']); ?>"
                                    data-position="<?php echo function_exists('esc_attr') ? esc_attr($config['position']) : htmlspecialchars($config['position']); ?>"
                                    data-density="<?php echo function_exists('esc_attr') ? esc_attr($config['density']) : htmlspecialchars($config['density']); ?>"
                                    data-case-sensitive="<?php echo $config['case_sensitive'] ? '1' : '0'; ?>"
                                    data-whole-word="<?php echo $config['whole_word'] ? '1' : '0'; ?>"><?php if (function_exists('_e')) _e('Edit', 'wenpai-trademark'); else echo 'Edit'; ?></button>
                            <button type="button" class="button delete-term" data-term="<?php echo function_exists('esc_attr') ? esc_attr($term) : htmlspecialchars($term); ?>"><?php if (function_exists('_e')) _e('Delete', 'wenpai-trademark'); else echo 'Delete'; ?></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h3><?php _e('Add New Term', 'wenpai-trademark'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="new-term"><?php _e('Term', 'wenpai-trademark'); ?></label></th>
                    <td>
                        <input type="text" id="new-term" class="regular-text" placeholder="<?php esc_attr_e('Enter the term to be marked', 'wenpai-trademark'); ?>" />
                        <p class="description"><?php _e('The word or phrase that will receive the trademark symbol.', 'wenpai-trademark'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="new-symbol"><?php _e('Symbol', 'wenpai-trademark'); ?></label></th>
                    <td>
                        <select id="new-symbol">
                            <option value="™"><?php _e('™ (Trademark)', 'wenpai-trademark'); ?></option>
                            <option value="®"><?php _e('® (Registered)', 'wenpai-trademark'); ?></option>
                            <option value="©"><?php _e('© (Copyright)', 'wenpai-trademark'); ?></option>
                            <option value="℠"><?php _e('℠ (Service Mark)', 'wenpai-trademark'); ?></option>
                            <option value="℗"><?php _e('℗ (Sound Recording)', 'wenpai-trademark'); ?></option>
                            <option value="custom"><?php _e('Custom Symbol', 'wenpai-trademark'); ?></option>
                        </select>
                        <input type="text" id="custom-symbol" placeholder="<?php esc_attr_e('Enter custom symbol', 'wenpai-trademark'); ?>" style="display:none; margin-left: 10px; width: 100px;" />
                        <p class="description"><?php _e('Choose the trademark symbol to be added to the term.', 'wenpai-trademark'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="new-position"><?php _e('Position', 'wenpai-trademark'); ?></label></th>
                    <td>
                        <select id="new-position">
                            <option value="after"><?php _e('After term', 'wenpai-trademark'); ?></option>
                            <option value="before"><?php _e('Before term', 'wenpai-trademark'); ?></option>
                        </select>
                        <p class="description"><?php _e('Where to place the symbol relative to the term.', 'wenpai-trademark'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="new-density"><?php _e('Density', 'wenpai-trademark'); ?></label></th>
                    <td>
                        <input type="number" id="new-density" min="1" max="10" value="1" />
                        <p class="description"><?php _e('Maximum number of times to apply the symbol per page (1-10).', 'wenpai-trademark'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="new-case-sensitive"><?php _e('Case Sensitive', 'wenpai-trademark'); ?></label></th>
                    <td>
                        <input type="checkbox" id="new-case-sensitive" />
                        <p class="description"><?php _e('Whether the term matching should be case sensitive.', 'wenpai-trademark'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="new-whole-word"><?php _e('Whole Word Only', 'wenpai-trademark'); ?></label></th>
                    <td>
                        <input type="checkbox" id="new-whole-word" checked />
                        <p class="description"><?php _e('Only match complete words, not partial matches within other words.', 'wenpai-trademark'); ?></p>
                    </td>
                </tr>
            </table>
                    <button type="button" id="add-term" class="button button-primary"><?php _e('Add Term', 'wenpai-trademark'); ?></button>
                </div>
            </div>
            
            <!-- Scope Settings Tab -->
            <div class="wenpai-section" data-section="scope" style="display: none;">
                <h2><?php _e('Application Scope Settings', 'wenpai-trademark'); ?></h2>
                <p class="description"><?php _e('Choose where the trademark symbols should be automatically applied on your website.', 'wenpai-trademark'); ?></p>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Apply To', 'wenpai-trademark'); ?></th>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <label><input type="checkbox" name="scopes[content]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['content']), true, false) : (!empty($scopes['content']) ? 'checked' : ''); ?> /> <?php _e('Post Content', 'wenpai-trademark'); ?></label>
                                    <label><input type="checkbox" name="scopes[title]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['title']), true, false) : (!empty($scopes['title']) ? 'checked' : ''); ?> /> <?php _e('Post Titles', 'wenpai-trademark'); ?></label>
                                    <label><input type="checkbox" name="scopes[widgets]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['widgets']), true, false) : (!empty($scopes['widgets']) ? 'checked' : ''); ?> /> <?php _e('Widgets', 'wenpai-trademark'); ?></label>
                                    <label><input type="checkbox" name="scopes[comments]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['comments']), true, false) : (!empty($scopes['comments']) ? 'checked' : ''); ?> /> <?php _e('Comments', 'wenpai-trademark'); ?></label>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Exclusion Settings Tab -->
            <div class="wenpai-section" data-section="exclusion" style="display: none;">
                <h2><?php _e('Exclusion Settings', 'wenpai-trademark'); ?></h2>
                <p class="description"><?php _e('Configure which content should be excluded from automatic trademark symbol application.', 'wenpai-trademark'); ?></p>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><label for="exclude-post-ids"><?php _e('Exclude Post IDs', 'wenpai-trademark'); ?></label></th>
                            <td>
                                <input type="text" id="exclude-post-ids" name="exclude_post_ids" value="<?php echo function_exists('esc_attr') ? esc_attr($exclude_post_ids) : htmlspecialchars($exclude_post_ids); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., 123, 456, 789', 'wenpai-trademark'); ?>" />
                                <p class="description"><?php _e('Comma-separated list of post IDs to exclude from trademark symbol replacement. Useful for specific posts where you don\'t want automatic symbols.', 'wenpai-trademark'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="exclude-html-tags"><?php _e('Exclude HTML Tags', 'wenpai-trademark'); ?></label></th>
                            <td>
                                <input type="text" id="exclude-html-tags" name="exclude_html_tags" value="<?php echo function_exists('esc_attr') ? esc_attr($exclude_html_tags) : htmlspecialchars($exclude_html_tags); ?>" class="regular-text" placeholder="<?php esc_attr_e('e.g., a, code, pre', 'wenpai-trademark'); ?>" />
                                <p class="description"><?php _e('Comma-separated list of HTML tags where trademark symbols should not be applied. Default excludes links (a), code blocks (code), and preformatted text (pre).', 'wenpai-trademark'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Import/Export Tab -->
            <div class="wenpai-section" data-section="import-export" style="display: none;">
                <h2><?php _e('Import/Export', 'wenpai-trademark'); ?></h2>
                <p class="description"><?php _e('Backup and restore your trademark terms using CSV files.', 'wenpai-trademark'); ?></p>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th><label for="csv-file"><?php _e('Import Terms', 'wenpai-trademark'); ?></label></th>
                            <td>
                                <input type="file" id="csv-file" accept=".csv" />
                                <br><br>
                                <label>
                                    <input type="checkbox" id="skip-header" checked="checked" />
                                    <?php _e('Skip first row (header)', 'wenpai-trademark'); ?>
                                </label>
                                <br><br>
                                <button type="button" id="import-terms" class="button"><?php _e('Import CSV', 'wenpai-trademark'); ?></button>
                                <p class="description"><?php _e('Import trademark terms from a CSV file. The CSV should contain columns: term, symbol, position, density, case_sensitive, whole_word.', 'wenpai-trademark'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Export Terms', 'wenpai-trademark'); ?></th>
                            <td>
                                <button type="button" id="export-terms" class="button"><?php _e('Export Terms as CSV', 'wenpai-trademark'); ?></button>
                                <p class="description"><?php _e('Export all current trademark terms to a CSV file for backup or transfer to another site.', 'wenpai-trademark'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if (function_exists('submit_button')) { submit_button(__('Save Settings', 'wenpai-trademark')); } else { echo '<input type="submit" id="submit" class="button button-primary" value="' . __('Save Settings', 'wenpai-trademark') . '" />'; } ?>
    </form>
</div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    max-width: unset;
    margin-top: 20px;
    padding: 20px;
}
.card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
#wenpai-admin-notices {
    margin: 15px 0;
}
.notice {
    background: #fff;
    border-left: 4px solid #00a0d2;
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    margin: 5px 0 15px;
    padding: 1px 12px;
}
.notice.notice-success {
    border-left-color: #46b450;
}
.notice.notice-error {
    border-left-color: #dc3232;
}
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Tab Styles */
.wenpai-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    border-bottom: 1px solid #c3c4c7;
    margin-bottom: 20px;
}
.wenpai-tab {
    padding: 8px 16px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 14px;
    border-bottom: 2px solid transparent;
}
.wenpai-tab.active {
    border-bottom: 2px solid #007cba;
    font-weight: 600;
    background: #f0f0f1;
}
.wenpai-tab:hover:not(.active) {
    background: #f0f0f1;
    border-bottom-color: #dcdcde;
}
.wenpai-content {
    flex: 1;
}
.wenpai-section {
    border: 0px solid #c3c4c7;
}
.inside {
    padding: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    var wenpaiAdmin = window.wenpaiAdmin || {};
    
    // 获取当前活跃标签（从URL hash或localStorage）
    function getCurrentTab() {
        var hash = window.location.hash.replace('#', '');
        if (hash && $('.wenpai-tab[data-tab="' + hash + '"]').length) {
            return hash;
        }
        var saved = localStorage.getItem('wenpai_active_tab');
        if (saved && $('.wenpai-tab[data-tab="' + saved + '"]').length) {
            return saved;
        }
        return 'terms'; // 默认标签
    }
    
    // 设置活跃标签
    function setActiveTab(tabName) {
        // 移除所有活跃状态
        $('.wenpai-tab').removeClass('active');
        $('.wenpai-section').hide();
        
        // 设置新的活跃状态
        $('.wenpai-tab[data-tab="' + tabName + '"]').addClass('active');
        $('.wenpai-section[data-section="' + tabName + '"]').show();
        
        // 保存到localStorage和URL hash
        localStorage.setItem('wenpai_active_tab', tabName);
        window.location.hash = tabName;
    }
    
    // 页面加载时恢复活跃标签
    var currentTab = getCurrentTab();
    setActiveTab(currentTab);
    
    // Tab switching functionality
    $('.wenpai-tab').click(function(e) {
        e.preventDefault();
        var target = $(this).data('tab');
        setActiveTab(target);
    });
    
    // 监听浏览器前进后退按钮
    $(window).on('hashchange', function() {
        var newTab = getCurrentTab();
        setActiveTab(newTab);
    });
    
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
        $('#wenpai-admin-notices').html(notice);
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    // Define text variables globally
    var yesText = wenpaiAdmin.strings && wenpaiAdmin.strings.yes ? wenpaiAdmin.strings.yes : '<?php _e('Yes', 'wenpai-trademark'); ?>';
    var noText = wenpaiAdmin.strings && wenpaiAdmin.strings.no ? wenpaiAdmin.strings.no : '<?php _e('No', 'wenpai-trademark'); ?>';
    var editText = wenpaiAdmin.strings && wenpaiAdmin.strings.edit ? wenpaiAdmin.strings.edit : '<?php _e('Edit', 'wenpai-trademark'); ?>';
    var deleteText = wenpaiAdmin.strings && wenpaiAdmin.strings.delete ? wenpaiAdmin.strings.delete : '<?php _e('Delete', 'wenpai-trademark'); ?>';
    var pleaseEnterCustomSymbol = '<?php _e('Please enter a custom symbol.', 'wenpai-trademark'); ?>';
    var pleaseEnterTerm = '<?php _e('Please enter a term.', 'wenpai-trademark'); ?>';
    var addTermText = '<?php _e('Add Term', 'wenpai-trademark'); ?>';
    var updateTermText = '<?php _e('Update Term', 'wenpai-trademark'); ?>';
    var termAddedText = '<?php _e('Term added successfully!', 'wenpai-trademark'); ?>';
    var termUpdatedText = '<?php _e('Term updated successfully!', 'wenpai-trademark'); ?>';
    var confirmDeleteText = '<?php _e('Are you sure you want to delete this term?', 'wenpai-trademark'); ?>';
    var termDeletedText = '<?php _e('Term deleted successfully!', 'wenpai-trademark'); ?>';
    var pleaseSelectFileText = '<?php _e('Please select a CSV file.', 'wenpai-trademark'); ?>';
    var settingsSavedText = '<?php _e('Settings saved successfully!', 'wenpai-trademark'); ?>';
    var importSuccessText = '<?php _e('Terms imported successfully!', 'wenpai-trademark'); ?>';
    var exportSuccessText = '<?php _e('Terms exported successfully!', 'wenpai-trademark'); ?>';
    
    function addTermToTable(term, config) {
        
        var row = '<tr>' +
            '<td>' + $('<div>').text(term).html() + '</td>' +
            '<td>' + $('<div>').text(config.symbol).html() + '</td>' +
            '<td>' + $('<div>').text(config.position).html() + '</td>' +
            '<td>' + $('<div>').text(config.density).html() + '</td>' +
            '<td>' + (config.case_sensitive ? yesText : noText) + '</td>' +
            '<td>' + (config.whole_word ? yesText : noText) + '</td>' +
            '<td>' +
                '<button type="button" class="button edit-term" ' +
                    'data-term="' + $('<div>').text(term).html() + '" ' +
                    'data-symbol="' + $('<div>').text(config.symbol).html() + '" ' +
                    'data-position="' + $('<div>').text(config.position).html() + '" ' +
                    'data-density="' + config.density + '" ' +
                    'data-case-sensitive="' + (config.case_sensitive ? '1' : '0') + '" ' +
                    'data-whole-word="' + (config.whole_word ? '1' : '0') + '">' + editText + '</button> ' +
                '<button type="button" class="button delete-term" data-term="' + $('<div>').text(term).html() + '">' + deleteText + '</button>' +
            '</td>' +
        '</tr>';
        $('#terms-table tbody').append(row);
    }
    
    // Handle custom symbol selection
    $('#new-symbol').change(function() {
        if ($(this).val() === 'custom') {
            $('#custom-symbol').show().focus();
        } else {
            $('#custom-symbol').hide().val('');
        }
    });
    
    // Add new term
    $('#add-term').click(function() {
        var term = $('#new-term').val().trim();
        var symbol = $('#new-symbol').val();
        if (symbol === 'custom') {
            symbol = $('#custom-symbol').val().trim();
            if (!symbol) {
                showNotice(pleaseEnterCustomSymbol, 'error');
                return;
            }
        }
        var position = $('#new-position').val();
        var density = parseInt($('#new-density').val());
        var caseSensitive = $('#new-case-sensitive').is(':checked');
        var wholeWord = $('#new-whole-word').is(':checked');
        
        if (!term) {
            showNotice(pleaseEnterTerm, 'error');
            return;
        }
        
        var isEditing = $('#add-term').data('editing');
        
        if (isEditing) {
            // Use dedicated edit endpoint
            var editData = {
                action: 'wenpai_edit_term',
                nonce: wenpaiAdmin.nonce,
                old_term: isEditing,
                new_term: term,
                symbol: symbol,
                position: position,
                density: density,
                case_sensitive: caseSensitive,
                whole_word: wholeWord
            };
            
            $.post(wenpaiAdmin.ajaxUrl, editData, function(response) {
                 if (response.success) {
                     showNotice(termUpdatedText, 'success');
                     // 延迟1.5秒后刷新页面
                     setTimeout(function() {
                         location.reload();
                     }, 1500);
                 } else {
                     showNotice(response.data.message || 'Failed to update term.', 'error');
                 }
             });
        } else {
            // Add new term
            var termData = {
                action: 'wenpai_save_settings',
                nonce: wenpaiAdmin.nonce,
                terms: {}
            };
            termData.terms[term] = {
                symbol: symbol,
                position: position,
                density: density,
                case_sensitive: caseSensitive,
                whole_word: wholeWord
            };
            
            $.post(wenpaiAdmin.ajaxUrl, termData, function(response) {
                if (response.success) {
                    addTermToTable(term, termData.terms[term]);
                    $('#new-term').val('');
                     $('#new-symbol').val('™');
                     $('#custom-symbol').hide().val('');
                     $('#new-density').val(1);
                     $('#new-case-sensitive').prop('checked', false);
                     $('#new-whole-word').prop('checked', true);
                     $('#add-term').text(addTermText).removeData('editing');
                     showNotice(termAddedText, 'success');
                } else {
                    showNotice(response.data.message || 'Failed to add term.', 'error');
                }
            });
        }
    });
    
    // Edit term
    $(document).on('click', '.edit-term', function() {
        var $button = $(this);
        var term = $button.data('term');
        var currentSymbol = $button.data('symbol');
        var currentPosition = $button.data('position');
        var currentDensity = $button.data('density');
        var currentCaseSensitive = $button.data('case-sensitive') === 1;
        var currentWholeWord = $button.data('whole-word') === 1;
        
        // Fill edit form
        $('#new-term').val(term);
        
        // Handle symbol selection
        var predefinedSymbols = ['™', '®', '©', '℠', '℗'];
        if (predefinedSymbols.includes(currentSymbol)) {
            $('#new-symbol').val(currentSymbol);
            $('#custom-symbol').hide().val('');
        } else {
            $('#new-symbol').val('custom');
            $('#custom-symbol').show().val(currentSymbol);
        }
        
        $('#new-position').val(currentPosition);
        $('#new-density').val(currentDensity);
        $('#new-case-sensitive').prop('checked', currentCaseSensitive);
        $('#new-whole-word').prop('checked', currentWholeWord);
        
        // Change button text and add data attribute
        $('#add-term').text(updateTermText).data('editing', term);
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#new-term').offset().top - 100
        }, 500);
    });
    
    // Reset form when term input changes during edit
    $('#new-term').on('input', function() {
        if ($('#add-term').data('editing')) {
            $('#add-term').text(addTermText).removeData('editing');
        }
    });
    
    // Delete term
    $(document).on('click', '.delete-term', function() {
        var term = $(this).data('term');
        var row = $(this).closest('tr');
        
        if (!confirm(confirmDeleteText)) {
            return;
        }
        
        $.post(wenpaiAdmin.ajaxUrl, {
            action: 'wenpai_delete_term',
            nonce: wenpaiAdmin.nonce,
            term: term
        }, function(response) {
            if (response.success) {
                row.fadeOut(function() {
                    row.remove();
                });
                showNotice(termDeletedText, 'success');
            } else {
                showNotice(response.data.message || 'Failed to delete term.', 'error');
            }
        });
    });
    
    // Import terms
    $('#import-terms').click(function() {
        var fileInput = $('#csv-file')[0];
        if (!fileInput.files.length) {
            showNotice(pleaseSelectFileText, 'error');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'wenpai_import_terms');
        formData.append('nonce', wenpaiAdmin.nonce);
        formData.append('csv_file', fileInput.files[0]);
        formData.append('skip_header', $('#skip-header').is(':checked') ? '1' : '0');
        
        $.ajax({
            url: wenpaiAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showNotice(response.data.message || 'Import failed.', 'error');
                }
            }
        });
    });
    
    // Export terms
    $('#export-terms').click(function() {
        $.post(wenpaiAdmin.ajaxUrl, {
            action: 'wenpai_export_terms',
            nonce: wenpaiAdmin.nonce
        }, function(response) {
            if (response.success) {
                var blob = new Blob([response.data.csv], { type: 'text/csv' });
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'wenpai-trademark-terms.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            } else {
                showNotice(response.data.message || 'Export failed.', 'error');
            }
        });
    });
    
    // Save settings
    $('#submit').click(function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'wenpai_save_settings',
            nonce: wenpaiAdmin.nonce,
            scopes: {},
            exclude_post_ids: $('#exclude-post-ids').val(),
            exclude_html_tags: $('#exclude-html-tags').val(),
            replace_all: true  // This is a full settings save, replace all
        };
        
        $('input[name^="scopes["]').each(function() {
            var name = $(this).attr('name').match(/scopes\[(.+)\]/)[1];
            formData.scopes[name] = $(this).is(':checked');
        });
        
        console.log('Saving scopes:', formData.scopes); // 调试信息
        
        $.post(wenpaiAdmin.ajaxUrl, formData, function(response) {
            if (response.success) {
                showNotice(settingsSavedText, 'success');
            } else {
                showNotice(response.data.message || 'Failed to save settings.', 'error');
            }
        });
    });
});
</script>