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
    <h1>WenPai Trademark Symbol Settings</h1>
    
    <div id="wenpai-admin-notices"></div>
    
    <form id="wenpai-settings-form" method="post" action="options.php">
        <?php if (function_exists('settings_fields')) settings_fields('wenpai_trademark_settings'); ?>
        
        <!-- Terms Management -->
        <div class="card">
            <h2>Trademark Terms</h2>
            <table class="wp-list-table widefat fixed striped" id="terms-table">
                <thead>
                    <tr>
                        <th>Term</th>
                        <th>Symbol</th>
                        <th>Position</th>
                        <th>Density</th>
                        <th>Case Sensitive</th>
                        <th>Whole Word</th>
                        <th>Actions</th>
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
            
            <h3>Add New Term</h3>
            <table class="form-table">
                <tr>
                    <th><label for="new-term">Term</label></th>
                    <td><input type="text" id="new-term" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="new-symbol">Symbol</label></th>
                    <td>
                        <select id="new-symbol">
                            <option value="™">™ (Trademark)</option>
                            <option value="®">® (Registered)</option>
                            <option value="©">© (Copyright)</option>
                            <option value="℠">℠ (Service Mark)</option>
                            <option value="℗">℗ (Sound Recording)</option>
                            <option value="custom">Custom Symbol</option>
                        </select>
                        <input type="text" id="custom-symbol" placeholder="Enter custom symbol" style="display:none; margin-left: 10px; width: 100px;" />
                    </td>
                </tr>
                <tr>
                    <th><label for="new-position">Position</label></th>
                    <td>
                        <select id="new-position">
                            <option value="after">After term</option>
                            <option value="before">Before term</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="new-density">Density</label></th>
                    <td><input type="number" id="new-density" min="1" max="10" value="1" /></td>
                </tr>
                <tr>
                    <th><label for="new-case-sensitive">Case Sensitive</label></th>
                    <td><input type="checkbox" id="new-case-sensitive" /></td>
                </tr>
                <tr>
                    <th><label for="new-whole-word">Whole Word Only</label></th>
                    <td><input type="checkbox" id="new-whole-word" checked /></td>
                </tr>
            </table>
            <button type="button" id="add-term" class="button button-primary">Add Term</button>
        </div>
        
        <!-- Scope Settings -->
        <div class="card">
            <h2>Application Scope</h2>
            <table class="form-table">
                <tr>
                    <th>Apply To</th>
                    <td>
                        <label><input type="checkbox" name="scopes[content]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['content']), true, false) : (!empty($scopes['content']) ? 'checked' : ''); ?> /> Post Content</label><br>
                        <label><input type="checkbox" name="scopes[title]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['title']), true, false) : (!empty($scopes['title']) ? 'checked' : ''); ?> /> Post Titles</label><br>
                        <label><input type="checkbox" name="scopes[widgets]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['widgets']), true, false) : (!empty($scopes['widgets']) ? 'checked' : ''); ?> /> Widgets</label><br>
                        <label><input type="checkbox" name="scopes[comments]" value="1" <?php echo function_exists('checked') ? checked(!empty($scopes['comments']), true, false) : (!empty($scopes['comments']) ? 'checked' : ''); ?> /> Comments</label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Exclusion Settings -->
        <div class="card">
            <h2>Exclusion Settings</h2>
            <table class="form-table">
                <tr>
                    <th><label for="exclude-post-ids">Exclude Post IDs</label></th>
                    <td>
                        <input type="text" id="exclude-post-ids" name="exclude_post_ids" value="<?php echo function_exists('esc_attr') ? esc_attr($exclude_post_ids) : htmlspecialchars($exclude_post_ids); ?>" class="regular-text" />
                        <p class="description">Comma-separated list of post IDs to exclude from replacement.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="exclude-html-tags">Exclude HTML Tags</label></th>
                    <td>
                        <input type="text" id="exclude-html-tags" name="exclude_html_tags" value="<?php echo function_exists('esc_attr') ? esc_attr($exclude_html_tags) : htmlspecialchars($exclude_html_tags); ?>" class="regular-text" />
                        <p class="description">Comma-separated list of HTML tags to exclude from replacement (e.g., a,code,pre).</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Import/Export -->
        <div class="card">
            <h2>Import/Export</h2>
            <table class="form-table">
                <tr>
                    <th><label for="csv-file">Import CSV</label></th>
                    <td>
                        <input type="file" id="csv-file" accept=".csv" />
                        <button type="button" id="import-terms" class="button">Import Terms</button>
                        <p class="description">CSV format: Term,Symbol,Position,Density,Case Sensitive,Whole Word</p>
                    </td>
                </tr>
                <tr>
                    <th>Export</th>
                    <td>
                        <button type="button" id="export-terms" class="button">Export Terms as CSV</button>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php if (function_exists('submit_button')) { submit_button('Save Settings'); } else { echo '<input type="submit" id="submit" class="button button-primary" value="Save Settings" />'; } ?>
    </form>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
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
</style>

<script>
jQuery(document).ready(function($) {
    var wenpaiAdmin = window.wenpaiAdmin || {};
    
    function showNotice(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
        $('#wenpai-admin-notices').html(notice);
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    function addTermToTable(term, config) {
        var yesText = wenpaiAdmin.strings && wenpaiAdmin.strings.yes ? wenpaiAdmin.strings.yes : 'Yes';
        var noText = wenpaiAdmin.strings && wenpaiAdmin.strings.no ? wenpaiAdmin.strings.no : 'No';
        var editText = wenpaiAdmin.strings && wenpaiAdmin.strings.edit ? wenpaiAdmin.strings.edit : 'Edit';
        var deleteText = wenpaiAdmin.strings && wenpaiAdmin.strings.delete ? wenpaiAdmin.strings.delete : 'Delete';
        
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
                showNotice(wenpaiAdmin.strings.pleaseEnterCustomSymbol, 'error');
                return;
            }
        }
        var position = $('#new-position').val();
        var density = parseInt($('#new-density').val());
        var caseSensitive = $('#new-case-sensitive').is(':checked');
        var wholeWord = $('#new-whole-word').is(':checked');
        
        if (!term) {
            showNotice('Please enter a term.', 'error');
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
                     // Reset form before reload
                     $('#new-term').val('');
                     $('#new-symbol').val('™');
                     $('#custom-symbol').hide().val('');
                     $('#new-density').val(1);
                     $('#new-case-sensitive').prop('checked', false);
                     $('#new-whole-word').prop('checked', true);
                     $('#add-term').text(wenpaiAdmin.strings.addTerm).removeData('editing');
                     
                     // Reload the page to refresh the table
                     location.reload();
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
                     $('#add-term').text(wenpaiAdmin.strings.addTerm).removeData('editing');
                     showNotice(wenpaiAdmin.strings.termAdded, 'success');
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
        $('#add-term').text(wenpaiAdmin.strings.updateTerm).data('editing', term);
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#new-term').offset().top - 100
        }, 500);
    });
    
    // Reset form when term input changes during edit
    $('#new-term').on('input', function() {
        if ($('#add-term').data('editing')) {
            $('#add-term').text(wenpaiAdmin.strings.addTerm).removeData('editing');
        }
    });
    
    // Delete term
    $(document).on('click', '.delete-term', function() {
        var term = $(this).data('term');
        var row = $(this).closest('tr');
        
        if (!confirm(wenpaiAdmin.strings.confirmDelete)) {
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
                showNotice(wenpaiAdmin.strings.termDeleted, 'success');
            } else {
                showNotice(response.data.message || 'Failed to delete term.', 'error');
            }
        });
    });
    
    // Import terms
    $('#import-terms').click(function() {
        var fileInput = $('#csv-file')[0];
        if (!fileInput.files.length) {
            showNotice(wenpaiAdmin.strings.pleaseSelectFile, 'error');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'wenpai_import_terms');
        formData.append('nonce', wenpaiAdmin.nonce);
        formData.append('csv_file', fileInput.files[0]);
        
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
        
        $.post(wenpaiAdmin.ajaxUrl, formData, function(response) {
            if (response.success) {
                showNotice('Settings saved successfully!', 'success');
            } else {
                showNotice(response.data.message || 'Failed to save settings.', 'error');
            }
        });
    });
});
</script>