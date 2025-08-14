<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$plugin = WenpaiTrademarkPlugin::get_instance();
$terms = $plugin->get_terms();
$scopes = $plugin->get_scopes();
$exclude_post_ids = \get_option(WenpaiTrademarkPlugin::OPTION_EXCLUDED_POST_IDS, '');
$exclude_html_tags = \get_option(WenpaiTrademarkPlugin::OPTION_EXCLUDED_TAGS, 'a,code,pre');
?>

<div class="wrap">
    <h1>WenPai Trademark Symbol Settings</h1>
    
    <div id="wenpai-admin-notices"></div>
    
    <form id="wenpai-settings-form" method="post" action="options.php">
        <?php \settings_fields('wenpai_trademark_settings'); ?>
        
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
                        <td><?php echo \esc_html($term); ?></td>
                        <td><?php echo \esc_html($config['symbol']); ?></td>
                        <td><?php echo \esc_html($config['position']); ?></td>
                        <td><?php echo \esc_html($config['density']); ?></td>
                        <td><?php echo $config['case_sensitive'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $config['whole_word'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <button type="button" class="button edit-term" data-term="<?php echo \esc_attr($term); ?>">Edit</button>
                            <button type="button" class="button delete-term" data-term="<?php echo \esc_attr($term); ?>">Delete</button>
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
                        </select>
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
                        <label><input type="checkbox" name="scopes[content]" value="1" <?php \checked(!empty($scopes['content'])); ?> /> Post Content</label><br>
                        <label><input type="checkbox" name="scopes[title]" value="1" <?php \checked(!empty($scopes['title'])); ?> /> Post Titles</label><br>
                        <label><input type="checkbox" name="scopes[widgets]" value="1" <?php \checked(!empty($scopes['widgets'])); ?> /> Widgets</label><br>
                        <label><input type="checkbox" name="scopes[comments]" value="1" <?php \checked(!empty($scopes['comments'])); ?> /> Comments</label>
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
                        <input type="text" id="exclude-post-ids" name="exclude_post_ids" value="<?php echo \esc_attr($exclude_post_ids); ?>" class="regular-text" />
                        <p class="description">Comma-separated list of post IDs to exclude from replacement.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="exclude-html-tags">Exclude HTML Tags</label></th>
                    <td>
                        <input type="text" id="exclude-html-tags" name="exclude_html_tags" value="<?php echo \esc_attr($exclude_html_tags); ?>" class="regular-text" />
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
        
        <?php \submit_button('Save Settings'); ?>
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
        var row = '<tr>' +
            '<td>' + $('<div>').text(term).html() + '</td>' +
            '<td>' + $('<div>').text(config.symbol).html() + '</td>' +
            '<td>' + $('<div>').text(config.position).html() + '</td>' +
            '<td>' + $('<div>').text(config.density).html() + '</td>' +
            '<td>' + (config.case_sensitive ? 'Yes' : 'No') + '</td>' +
            '<td>' + (config.whole_word ? 'Yes' : 'No') + '</td>' +
            '<td>' +
                '<button type="button" class="button edit-term" data-term="' + $('<div>').text(term).html() + '">Edit</button> ' +
                '<button type="button" class="button delete-term" data-term="' + $('<div>').text(term).html() + '">Delete</button>' +
            '</td>' +
        '</tr>';
        $('#terms-table tbody').append(row);
    }
    
    // Add new term
    $('#add-term').click(function() {
        var term = $('#new-term').val().trim();
        var symbol = $('#new-symbol').val();
        var position = $('#new-position').val();
        var density = parseInt($('#new-density').val());
        var caseSensitive = $('#new-case-sensitive').is(':checked');
        var wholeWord = $('#new-whole-word').is(':checked');
        
        if (!term) {
            showNotice('Please enter a term.', 'error');
            return;
        }
        
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
                $('#new-density').val(1);
                $('#new-case-sensitive').prop('checked', false);
                $('#new-whole-word').prop('checked', true);
                showNotice('Term added successfully!', 'success');
            } else {
                showNotice(response.data.message || 'Failed to add term.', 'error');
            }
        });
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
                showNotice('Term deleted successfully!', 'success');
            } else {
                showNotice(response.data.message || 'Failed to delete term.', 'error');
            }
        });
    });
    
    // Import terms
    $('#import-terms').click(function() {
        var fileInput = $('#csv-file')[0];
        if (!fileInput.files.length) {
            showNotice('Please select a CSV file.', 'error');
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
            exclude_html_tags: $('#exclude-html-tags').val()
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