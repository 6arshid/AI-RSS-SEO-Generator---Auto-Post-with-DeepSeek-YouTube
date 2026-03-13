/**
 * 6arshid RSS Generator Pro - Admin JavaScript
 * Version: 22.0.0
 * Author: 6arshid
 * GitHub: https://github.com/6arshid/
 */

jQuery(document).ready(function($) {
    
    /**
     * Test API Connections
     */
    $('#rss-test-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { 
                action: 'rss_test' 
            },
            success: function(res) {
                if (res.success) {
                    var message = '✅ API Connection Results:\n\n';
                    message += 'DeepSeek: ' + res.data.deepseek + '\n';
                    message += 'Pexels: ' + res.data.pexels + '\n';
                    message += 'YouTube: ' + res.data.youtube;
                    alert(message);
                } else {
                    alert('❌ Error: ' + res.data);
                }
            },
            error: function(xhr, status, error) {
                alert('❌ Connection Error: ' + error);
            },
            complete: function() {
                btn.prop('disabled', false).text('🔌 Test Connection');
            }
        });
    });
    
    /**
     * Fetch RSS Items
     */
    $('#rss-fetch-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Fetching...');
        
        $('#rss-items-container').html('<p style="text-align:center;">⏳ Fetching RSS items from your feeds...</p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { 
                action: 'rss_fetch' 
            },
            success: function(res) {
                if (res.success) {
                    displayRssItems(res.data);
                } else {
                    $('#rss-items-container').html('<p style="color:red;">❌ ' + res.data + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#rss-items-container').html('<p style="color:red;">❌ Error: ' + error + '</p>');
            },
            complete: function() {
                btn.prop('disabled', false).text('📥 Fetch RSS');
            }
        });
    });
    
    /**
     * Display RSS Items in Table
     */
    function displayRssItems(items) {
        if (!items || items.length === 0) {
            $('#rss-items-container').html('<p>No items found</p>');
            return;
        }
        
        var html = '<h3>📰 RSS Items Found (' + items.length + ')</h3>';
        html += '<table class="wp-list-table widefat fixed striped">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>Title</th>';
        html += '<th>Date</th>';
        html += '<th>Actions</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        $.each(items, function(index, item) {
            html += '<tr class="item-row" data-index="' + index + '">';
            html += '<td class="item-title"><strong>' + escapeHtml(item.title) + '</strong></td>';
            html += '<td>' + (item.date || 'Unknown') + '</td>';
            html += '<td>';
            html += '<button class="generate-btn button button-primary" data-index="' + index + '">';
            html += '⚡ Generate Full Post (2 Images + Video)';
            html += '</button>';
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        
        $('#rss-items-container').html(html);
        window.rssItems = items;
    }
    
    /**
     * Generate Single Post
     */
    $(document).on('click', '.generate-btn', function() {
        var btn = $(this);
        var index = btn.data('index');
        var item = window.rssItems[index];
        
        if (!item) {
            alert('Error: Item not found');
            return;
        }
        
        btn.prop('disabled', true).text('⏳ Generating...');
        
        // Show progress bar
        $('#rss-progress').show();
        updateProgress(10, 'Initializing content generation...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'rss_generate',
                item: item
            },
            success: function(res) {
                if (res.success) {
                    updateProgress(100, '✅ Post created successfully!');
                    
                    // Add edit link
                    var editLink = '<br><br>';
                    editLink += '<a href="' + res.data.link + '" target="_blank" class="button button-primary">';
                    editLink += '✏️ Edit Generated Post';
                    editLink += '</a>';
                    
                    $('#rss-status').append(editLink);
                    
                    // Highlight the row
                    btn.closest('tr').css('background-color', '#dff0d8');
                    btn.text('✅ Done');
                } else {
                    updateProgress(100, '❌ Error: ' + res.data);
                    btn.prop('disabled', false).text('⚡ Try Again');
                }
            },
            error: function(xhr, status, error) {
                updateProgress(100, '❌ Connection Error: ' + error);
                btn.prop('disabled', false).text('⚡ Try Again');
            }
        });
    });
    
    /**
     * Update Progress Bar
     */
    function updateProgress(percent, message) {
        $('.progress-bar').css('width', percent + '%').text(percent + '%');
        $('#rss-status').text(message);
    }
    
    /**
     * Run Cron Manually
     */
    $('#rss-cron-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Running Cron...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { 
                action: 'rss_cron_run' 
            },
            success: function(res) {
                if (res.success) {
                    alert('✅ ' + res.data);
                } else {
                    alert('❌ ' + res.data);
                }
            },
            error: function() {
                alert('❌ Error running cron');
            },
            complete: function() {
                btn.prop('disabled', false).text('⚡ Run Cron Manually');
            }
        });
    });
    
    /**
     * View Log File
     */
    $('#rss-log-btn').on('click', function() {
        $('#rss-log-viewer').show();
        $('#rss-log-content').text('⏳ Loading log file...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { 
                action: 'rss_view_log' 
            },
            success: function(res) {
                if (res.success) {
                    $('#rss-log-content').text(res.data.content || 'Log file is empty');
                } else {
                    $('#rss-log-content').text('Error: ' + res.data);
                }
            },
            error: function() {
                $('#rss-log-content').text('Error loading log file');
            }
        });
    });
    
    /**
     * Close Log Viewer
     */
    $('#rss-close-log').on('click', function() {
        $('#rss-log-viewer').hide();
    });
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
    
    /**
     * Refresh Log Content
     */
    $('#rss-refresh-log').on('click', function() {
        $('#rss-log-btn').click();
    });
    
    /**
     * Copy Cron URL to Clipboard
     */
    $('#rss-copy-cron').on('click', function() {
        var cronUrl = $('.cron-url-input').val();
        navigator.clipboard.writeText(cronUrl).then(function() {
            alert('✅ Cron URL copied to clipboard!');
        }, function() {
            alert('❌ Could not copy URL');
        });
    });
    
    console.log('6arshid RSS Generator Pro - Admin Ready');
});