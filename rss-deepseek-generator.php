<?php
/**
 * Plugin Name: 6arshid RSS Generator Pro
 * Plugin URI: https://github.com/6arshid/
 * Description: RSS + DeepSeek + Pexels Images + YouTube Video + Full SEO Control
 * Version: 22.0.0
 * Author: 6arshid
 * Author URI: https://github.com/6arshid/
 * License: GPL v2 or later
 * Text Domain: rss-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

class RSS_Generator_6arshid {
    
    private $options;
    private $log_file;
    
    public function __construct() {
        $this->options = get_option('rss_6arshid_options', array());
        $this->log_file = WP_CONTENT_DIR . '/rss-6arshid.log';
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        add_action('wp_ajax_rss_test', array($this, 'ajax_test'));
        add_action('wp_ajax_rss_fetch', array($this, 'ajax_fetch'));
        add_action('wp_ajax_rss_generate', array($this, 'ajax_generate'));
        add_action('wp_ajax_rss_cron_run', array($this, 'ajax_cron_run'));
        add_action('wp_ajax_rss_view_log', array($this, 'ajax_view_log'));
        
        // Cron URL
        add_action('init', array($this, 'add_cron_rewrite_rule'));
        add_filter('query_vars', array($this, 'add_cron_query_var'));
        add_action('template_redirect', array($this, 'handle_cron_request'));
        
        add_action('rss_6arshid_cron_hook', array($this, 'run_cron'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
    
    public function activate() {
        if (!wp_next_scheduled('rss_6arshid_cron_hook')) {
            wp_schedule_event(time(), 'hourly', 'rss_6arshid_cron_hook');
        }
        
        $this->add_cron_rewrite_rule();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('rss_6arshid_cron_hook');
        flush_rewrite_rules();
    }
    
    // ========== Cron URL Functions ==========
    
    public function add_cron_rewrite_rule() {
        add_rewrite_rule(
            '^6arshid-cron/?$',
            'index.php?rss_cron=1',
            'top'
        );
    }
    
    public function add_cron_query_var($vars) {
        $vars[] = 'rss_cron';
        return $vars;
    }
    
    public function handle_cron_request() {
        if (get_query_var('rss_cron') == 1) {
            $this->run_cron_from_url();
            exit;
        }
    }
    
    public function run_cron_from_url() {
        $key = isset($_GET['key']) ? $_GET['key'] : '';
        $secret = isset($this->options['cron_secret']) ? $this->options['cron_secret'] : '';
        
        if (empty($secret)) {
            $secret = wp_hash('rss_cron_' . time());
            $this->options['cron_secret'] = $secret;
            update_option('rss_6arshid_options', $this->options);
        }
        
        if ($key !== $secret) {
            $this->log("Unauthorized cron attempt with key: $key");
            header('HTTP/1.0 403 Forbidden');
            echo '⚠️ Unauthorized access';
            exit;
        }
        
        $this->log("Cron executed via beautiful URL");
        $count = $this->run_cron();
        
        header('Content-Type: text/plain; charset=utf-8');
        echo "✅ 6arshid RSS Generator Cron Job\n";
        echo "========================\n";
        echo "Execution time: " . date('Y-m-d H:i:s') . "\n";
        echo "Posts created: $count\n";
        echo "Each post: 2 images + 1 YouTube video\n";
        echo "========================\n";
        echo "GitHub: https://github.com/6arshid/\n";
        exit;
    }
    
    // ========== Admin Page ==========
    
    public function add_admin_menu() {
        add_menu_page(
            '6arshid RSS Generator',
            '6arshid RSS',
            'manage_options',
            'rss-6arshid',
            array($this, 'admin_page'),
            'dashicons-rss',
            30
        );
    }
    
    public function admin_page() {
        $next_cron = wp_next_scheduled('rss_6arshid_cron_hook');
        $next_time = $next_cron ? date('Y-m-d H:i:s', $next_cron) : 'Not scheduled';
        
        $secret = isset($this->options['cron_secret']) ? $this->options['cron_secret'] : '';
        if (empty($secret)) {
            $secret = wp_hash('rss_cron_' . time());
            $this->options['cron_secret'] = $secret;
            update_option('rss_6arshid_options', $this->options);
        }
        
        $cron_url = home_url('/6arshid-cron/?key=' . $secret);
        ?>
        <div class="wrap">
            <h1>🚀 6arshid RSS Generator Pro (2 Images + Video in Middle)</h1>
            
            <div class="notice notice-info">
                <p><strong>⏰ Internal Cron:</strong> Next run: <?php echo $next_time; ?></p>
                <p><strong>🔗 Cron URL (for Hostinger hPanel):</strong></p>
                <p><input type="text" value="<?php echo esc_url($cron_url); ?>" class="large-text" readonly onclick="this.select()" style="direction:ltr;"></p>
                <p><strong>📁 Log file:</strong> <?php echo $this->log_file; ?></p>
                <p><strong>🎬 YouTube + 2 Images:</strong> Each post has 2 images and 1 video</p>
                <p><strong>🔗 GitHub:</strong> <a href="https://github.com/6arshid/" target="_blank">https://github.com/6arshid/</a></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('rss_6arshid_options');
                do_settings_sections('rss-6arshid');
                submit_button('💾 Save Settings');
                ?>
            </form>
            
            <hr>
            
            <div style="margin:20px 0;">
                <button id="rss-test-btn" class="button button-secondary">🔌 Test Connection</button>
                <button id="rss-fetch-btn" class="button button-primary">📥 Fetch RSS</button>
                <button id="rss-cron-btn" class="button button-secondary">⚡ Run Cron Manually</button>
                <button id="rss-log-btn" class="button button-secondary">📋 View Log</button>
            </div>
            
            <div id="rss-items-container"></div>
            
            <div id="rss-progress" style="display:none; margin-top:20px; padding:15px; background:#fff; border:1px solid #ccc;">
                <div class="progress-bar" style="height:30px; background:#4CAF50; width:0%; text-align:center; color:white; line-height:30px;"></div>
                <p id="rss-status"></p>
            </div>
            
            <div id="rss-log-viewer" style="display:none; margin-top:20px; padding:15px; background:#fff; border:1px solid #ccc;">
                <h3>📋 Log Content</h3>
                <pre id="rss-log-content" style="background:#f1f1f1; padding:10px; max-height:400px; overflow:auto;"></pre>
                <button id="rss-close-log" class="button">Close</button>
            </div>
        </div>
        
        <style>
        .item-row { border-bottom:1px solid #ddd; }
        .item-row:hover { background:#f9f9f9; }
        .item-title { font-weight:bold; }
        .generate-btn { background:#0073aa; color:white; border:none; padding:5px 10px; border-radius:3px; cursor:pointer; }
        .generate-btn:hover { background:#005a87; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            $('#rss-test-btn').click(function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'rss_test' },
                    success: function(res) {
                        if (res.success) {
                            alert('✅ DeepSeek: ' + res.data.deepseek + '\n✅ Pexels: ' + res.data.pexels + '\n✅ YouTube: ' + res.data.youtube);
                        }
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('🔌 Test Connection');
                    }
                });
            });
            
            $('#rss-fetch-btn').click(function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Fetching...');
                
                $('#rss-items-container').html('<p>⏳ Fetching RSS items...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'rss_fetch' },
                    success: function(res) {
                        if (res.success) {
                            displayItems(res.data);
                        } else {
                            $('#rss-items-container').html('<p style="color:red;">❌ ' + res.data + '</p>');
                        }
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('📥 Fetch RSS');
                    }
                });
            });
            
            function displayItems(items) {
                var html = '<h3>📰 Items (' + items.length + ')</h3>';
                html += '<table class="wp-list-table widefat fixed">';
                html += '<thead><tr><th>Title</th><th>Date</th><th>Action</th></tr></thead><tbody>';
                
                $.each(items, function(i, item) {
                    html += '<tr class="item-row" data-index="' + i + '">';
                    html += '<td class="item-title">' + escapeHtml(item.title) + '</td>';
                    html += '<td>' + (item.date || 'Unknown') + '</td>';
                    html += '<td><button class="generate-btn" data-index="' + i + '">⚡ Generate Post (2 Images + Video)</button></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#rss-items-container').html(html);
                window.rssItems = items;
            }
            
            $(document).on('click', '.generate-btn', function() {
                var btn = $(this);
                var index = btn.data('index');
                var item = window.rssItems[index];
                
                btn.prop('disabled', true).text('⏳ Generating...');
                
                $('#rss-progress').show();
                $('.progress-bar').css('width', '30%').text('30%');
                $('#rss-status').text('Generating content with 2 images and video...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'rss_generate',
                        item: item
                    },
                    success: function(res) {
                        if (res.success) {
                            $('.progress-bar').css('width', '100%').text('100%');
                            $('#rss-status').html('✅ Post with 2 images and video created!');
                            btn.closest('tr').css('background', '#dff0d8');
                            btn.text('✅ Done');
                        } else {
                            $('.progress-bar').css('width', '100%').text('Error');
                            $('#rss-status').html('❌ ' + res.data);
                            btn.prop('disabled', false).text('⚡ Try Again');
                        }
                    }
                });
            });
            
            $('#rss-cron-btn').click(function() {
                var btn = $(this);
                btn.prop('disabled', true).text('Running...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'rss_cron_run' },
                    success: function(res) {
                        alert(res.data);
                    },
                    complete: function() {
                        btn.prop('disabled', false).text('⚡ Run Cron Manually');
                    }
                });
            });
            
            $('#rss-log-btn').click(function() {
                $('#rss-log-viewer').show();
                $('#rss-log-content').text('⏳ Loading...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'rss_view_log' },
                    success: function(res) {
                        $('#rss-log-content').text(res.data.content || 'Log is empty');
                    }
                });
            });
            
            $('#rss-close-log').click(function() {
                $('#rss-log-viewer').hide();
            });
            
            function escapeHtml(text) {
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }
        });
        </script>
        <?php
    }
    
    public function register_settings() {
        register_setting('rss_6arshid_options', 'rss_6arshid_options');
        
        add_settings_section('main', 'Main Settings', null, 'rss-6arshid');
        
        // DeepSeek API Key
        add_settings_field('deepseek_api', 'DeepSeek API Key', function() {
            $value = isset($this->options['deepseek_api']) ? $this->options['deepseek_api'] : '';
            echo '<input type="text" name="rss_6arshid_options[deepseek_api]" value="' . esc_attr($value) . '" class="regular-text" style="direction:ltr;">';
        }, 'rss-6arshid', 'main');
        
        // YouTube API Key
        add_settings_field('youtube_api', 'YouTube API Key', function() {
            $value = isset($this->options['youtube_api']) ? $this->options['youtube_api'] : '';
            echo '<input type="text" name="rss_6arshid_options[youtube_api]" value="' . esc_attr($value) . '" class="regular-text" style="direction:ltr;">';
            echo '<p class="description">Get from Google Cloud Console</p>';
        }, 'rss-6arshid', 'main');
        
        // Pexels API Key
        add_settings_field('pexels_api', 'Pexels API Key', function() {
            $value = isset($this->options['pexels_api']) ? $this->options['pexels_api'] : '';
            echo '<input type="text" name="rss_6arshid_options[pexels_api]" value="' . esc_attr($value) . '" class="regular-text" style="direction:ltr;">';
            echo '<p class="description">Get from pexels.com/api</p>';
        }, 'rss-6arshid', 'main');
        
        // RSS URLs
        add_settings_field('rss_urls', 'RSS URLs (one per line)', function() {
            $value = isset($this->options['rss_urls']) ? $this->options['rss_urls'] : '';
            echo '<textarea name="rss_6arshid_options[rss_urls]" rows="5" class="large-text" style="direction:ltr;">' . esc_textarea($value) . '</textarea>';
        }, 'rss-6arshid', 'main');
        
        // Site Name
        add_settings_field('site_name', 'Site Name (for SEO)', function() {
            $value = isset($this->options['site_name']) ? $this->options['site_name'] : get_bloginfo('name');
            echo '<input type="text" name="rss_6arshid_options[site_name]" value="' . esc_attr($value) . '" class="regular-text">';
        }, 'rss-6arshid', 'main');
        
        // Title Prompt
        add_settings_field('title_prompt', 'Title Generator (Prompt)', function() {
            $default = "Original title: {title}\n\nPlease write an attractive and SEO-friendly title based on the original title above.";
            $value = isset($this->options['title_prompt']) ? $this->options['title_prompt'] : $default;
            echo '<textarea name="rss_6arshid_options[title_prompt]" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        }, 'rss-6arshid', 'main');
        
        // SEO Title Template
        add_settings_field('seo_title_template', 'SEO Title Tag Template', function() {
            $default = "{title} - {site_name}";
            $value = isset($this->options['seo_title_template']) ? $this->options['seo_title_template'] : $default;
            echo '<input type="text" name="rss_6arshid_options[seo_title_template]" value="' . esc_attr($value) . '" class="large-text">';
        }, 'rss-6arshid', 'main');
        
        // Meta Description Prompt
        add_settings_field('meta_desc_prompt', 'Meta Description (Prompt)', function() {
            $default = "Based on the article below, write an attractive and SEO-friendly meta description. Max 155 characters:\n\n{content}";
            $value = isset($this->options['meta_desc_prompt']) ? $this->options['meta_desc_prompt'] : $default;
            echo '<textarea name="rss_6arshid_options[meta_desc_prompt]" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
        }, 'rss-6arshid', 'main');
        
        // Content Prompt
        add_settings_field('content_prompt', 'Content Template (10+ paragraphs)', function() {
            $default = "Title: {title}\n\nDescription: {description}\n\nPlease write a complete SEO-friendly article in Persian with the following:\n1. Attractive title with keywords\n2. Engaging introduction\n3. At least 10 paragraphs with relevant subheadings\n4. Conclusion\n5. Relevant keywords\n6. At least 1500 words\n7. Use formal and professional tone\n8. Number your paragraphs";
            $value = isset($this->options['content_prompt']) ? $this->options['content_prompt'] : $default;
            echo '<textarea name="rss_6arshid_options[content_prompt]" rows="10" class="large-text">' . esc_textarea($value) . '</textarea>';
        }, 'rss-6arshid', 'main');
        
        // YouTube Settings
        add_settings_field('enable_youtube', 'Add YouTube Video', function() {
            $value = isset($this->options['enable_youtube']) ? $this->options['enable_youtube'] : 1;
            echo '<label><input type="checkbox" name="rss_6arshid_options[enable_youtube]" value="1" ' . checked($value, 1, false) . '> Yes, add related YouTube video</label>';
        }, 'rss-6arshid', 'main');
        
        // Post Status
        add_settings_field('post_status', 'Post Status', function() {
            $value = isset($this->options['post_status']) ? $this->options['post_status'] : 'draft';
            ?>
            <select name="rss_6arshid_options[post_status]">
                <option value="draft" <?php selected($value, 'draft'); ?>>Draft</option>
                <option value="publish" <?php selected($value, 'publish'); ?>>Published</option>
            </select>
            <?php
        }, 'rss-6arshid', 'main');
        
        // Enable Image
        add_settings_field('enable_image', 'Add Images (2 images)', function() {
            $value = isset($this->options['enable_image']) ? $this->options['enable_image'] : 1;
            echo '<label><input type="checkbox" name="rss_6arshid_options[enable_image]" value="1" ' . checked($value, 1, false) . '> Yes, add 2 images from Pexels</label>';
        }, 'rss-6arshid', 'main');
        
        // Cron Max Posts
        add_settings_field('cron_max', 'Max posts per cron run', function() {
            $value = isset($this->options['cron_max']) ? $this->options['cron_max'] : 3;
            echo '<input type="number" name="rss_6arshid_options[cron_max]" value="' . $value . '" min="1" max="20">';
        }, 'rss-6arshid', 'main');
        
        // Cron Secret Key
        add_settings_field('cron_secret', 'Cron Security Key', function() {
            $value = isset($this->options['cron_secret']) ? $this->options['cron_secret'] : wp_hash('rss_cron_' . time());
            echo '<input type="text" value="' . esc_attr($value) . '" class="regular-text" readonly>';
        }, 'rss-6arshid', 'main');
    }
    
    public function enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_rss-6arshid') return;
    }
    
    public function ajax_test() {
        $results = array();
        
        // Test DeepSeek
        if (!empty($this->options['deepseek_api'])) {
            $response = wp_remote_post('https://api.deepseek.com/chat/completions', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->options['deepseek_api'],
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'model' => 'deepseek-chat',
                    'messages' => array(array('role' => 'user', 'content' => 'Hello')),
                    'max_tokens' => 5
                )),
                'timeout' => 20
            ));
            
            $results['deepseek'] = (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) ? '✅ Active' : '❌ Error';
        } else {
            $results['deepseek'] = '⚠️ Not set';
        }
        
        // Test Pexels
        $test_image = $this->search_pexels_image('test');
        $results['pexels'] = $test_image ? '✅ Active' : '⚠️ Check Pexels API Key';
        
        // Test YouTube
        if (!empty($this->options['youtube_api'])) {
            $test_video = $this->search_youtube_video('test');
            $results['youtube'] = $test_video ? '✅ Active' : '⚠️ Check YouTube API Key';
        } else {
            $results['youtube'] = '⚠️ Not set';
        }
        
        wp_send_json_success($results);
    }
    
    public function ajax_fetch() {
        $urls = explode("\n", $this->options['rss_urls']);
        $urls = array_filter(array_map('trim', $urls));
        
        if (empty($urls)) {
            wp_send_json_error('Please enter RSS URLs in settings');
        }
        
        $all_items = array();
        
        foreach ($urls as $url) {
            $rss = fetch_feed($url);
            if (is_wp_error($rss)) continue;
            
            $items = $rss->get_items(0, 10);
            
            foreach ($items as $item) {
                $all_items[] = array(
                    'title' => $item->get_title(),
                    'desc' => wp_trim_words(strip_tags($item->get_description()), 50),
                    'link' => $item->get_permalink(),
                    'date' => $item->get_date('Y-m-d H:i:s')
                );
            }
        }
        
        if (empty($all_items)) {
            wp_send_json_error('No items found');
        }
        
        wp_send_json_success($all_items);
    }
    
    public function ajax_generate() {
        $item = $_POST['item'];
        
        // Generate new title
        $new_title = $this->generate_new_title($item['title']);
        if (empty($new_title)) {
            $new_title = $item['title'];
        }
        
        // Generate main content
        $content = $this->generate_content($item['title'], $item['desc']);
        if (!$content) {
            $content = "<p>Default content for " . $item['title'] . "</p>";
        }
        
        // Split into paragraphs
        $paragraphs = $this->split_into_paragraphs($content);
        
        // Search YouTube video
        $youtube_id = '';
        if (!empty($this->options['enable_youtube']) && !empty($this->options['youtube_api'])) {
            $youtube_id = $this->search_youtube_video($item['title']);
            if ($youtube_id) {
                $this->log("YouTube video found: " . $youtube_id);
            }
        }
        
        // Search 2 images from Pexels
        $image_urls = array();
        if (!empty($this->options['enable_image']) && !empty($this->options['pexels_api'])) {
            $image_urls = $this->search_pexels_images($item['title'], 2);
            $this->log(count($image_urls) . " images found");
        }
        
        // Final content structure
        $final_html = '';
        $total_paragraphs = count($paragraphs);
        
        // Ensure at least 10 paragraphs
        if ($total_paragraphs < 10) {
            for ($i = $total_paragraphs; $i < 10; $i++) {
                $paragraphs[] = "<p>Continuing the discussion about " . $item['title'] . ", there are other important points to consider for a better understanding of the topic.</p>";
            }
        }
        
        for ($i = 0; $i < count($paragraphs); $i++) {
            $para_num = $i + 1;
            
            // First image in paragraph 3
            if ($para_num == 3 && !empty($image_urls[0])) {
                $final_html .= $this->get_image_html($image_urls[0], 'Image related to ' . $item['title']);
            }
            
            // Video in paragraph 6
            if ($para_num == 6 && !empty($youtube_id)) {
                $final_html .= $this->get_youtube_embed_html($youtube_id);
            }
            
            // Second image in paragraph 9
            if ($para_num == 9 && !empty($image_urls[1])) {
                $final_html .= $this->get_image_html($image_urls[1], 'Image related to ' . $item['title']);
            }
            
            // Add paragraph
            $final_html .= $paragraphs[$i];
        }
        
        // Generate meta description
        $meta_description = $this->generate_meta_description($content, $item['title']);
        
        // Generate SEO title
        $seo_title = $this->generate_seo_title($new_title);
        
        // Post slug
        $post_slug = sanitize_title($new_title);
        
        // Create post
        $post_id = wp_insert_post(array(
            'post_title' => $new_title,
            'post_content' => $final_html,
            'post_status' => isset($this->options['post_status']) ? $this->options['post_status'] : 'draft',
            'post_type' => 'post',
            'post_name' => $post_slug,
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                '_rss_link' => $item['link'],
                '_rss_date' => $item['date'],
                '_original_title' => $item['title'],
                '_generated_by' => '6arshid_RSS_Generator',
                '_youtube_video_id' => $youtube_id,
                '_image_count' => count($image_urls),
                '_yoast_wpseo_title' => $seo_title,
                '_yoast_wpseo_metadesc' => $meta_description,
                '_aioseo_title' => $seo_title,
                '_aioseo_description' => $meta_description,
                '_rank_math_title' => $seo_title,
                '_rank_math_description' => $meta_description,
                'seo_title' => $seo_title,
                'seo_description' => $meta_description
            )
        ));
        
        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }
        
        // Add featured images
        if ($post_id && !empty($image_urls)) {
            foreach ($image_urls as $index => $image_url) {
                $this->add_image($post_id, $image_url, $index);
            }
        }
        
        wp_send_json_success(array(
            'link' => get_edit_post_link($post_id),
            'title' => $new_title,
            'youtube' => $youtube_id ? '✅' : '❌',
            'images' => count($image_urls)
        ));
    }
    
    public function ajax_cron_run() {
        $count = $this->run_cron();
        wp_send_json_success("✅ $count posts with 2 images and 1 video created");
    }
    
    public function ajax_view_log() {
        $content = file_exists($this->log_file) ? file_get_contents($this->log_file) : 'Log is empty';
        wp_send_json_success(array('content' => $content));
    }
    
    public function run_cron() {
        $this->log('Starting cron');
        
        $urls = explode("\n", $this->options['rss_urls']);
        $urls = array_filter(array_map('trim', $urls));
        $max = isset($this->options['cron_max']) ? $this->options['cron_max'] : 3;
        $created = 0;
        
        foreach ($urls as $url) {
            if ($created >= $max) break;
            
            $rss = fetch_feed($url);
            if (is_wp_error($rss)) continue;
            
            $items = $rss->get_items(0, 10);
            
            foreach ($items as $item) {
                if ($created >= $max) break;
                
                $exists = get_posts(array(
                    'meta_key' => '_rss_link',
                    'meta_value' => $item->get_permalink(),
                    'post_type' => 'post',
                    'numberposts' => 1
                ));
                
                if (empty($exists)) {
                    $post_data = array(
                        'title' => $item->get_title(),
                        'desc' => wp_trim_words(strip_tags($item->get_description()), 50),
                        'link' => $item->get_permalink(),
                        'date' => $item->get_date('Y-m-d H:i:s')
                    );
                    
                    $post_id = $this->create_post_cron($post_data);
                    if ($post_id) $created++;
                }
            }
        }
        
        $this->log("Cron finished - $created posts created");
        return $created;
    }
    
    private function create_post_cron($data) {
        // Generate new title
        $new_title = $this->generate_new_title($data['title']);
        if (empty($new_title)) {
            $new_title = $data['title'];
        }
        
        // Generate content
        $content = $this->generate_content($data['title'], $data['desc']);
        
        // Split into paragraphs
        $paragraphs = $this->split_into_paragraphs($content);
        
        // Search YouTube video
        $youtube_id = '';
        if (!empty($this->options['enable_youtube']) && !empty($this->options['youtube_api'])) {
            $youtube_id = $this->search_youtube_video($data['title']);
        }
        
        // Search 2 images
        $image_urls = array();
        if (!empty($this->options['enable_image']) && !empty($this->options['pexels_api'])) {
            $image_urls = $this->search_pexels_images($data['title'], 2);
        }
        
        // Build final content
        $final_html = '';
        $total_paragraphs = count($paragraphs);
        
        if ($total_paragraphs < 10) {
            for ($i = $total_paragraphs; $i < 10; $i++) {
                $paragraphs[] = "<p>Continuing the discussion about " . $data['title'] . ", there are other important points to consider.</p>";
            }
        }
        
        for ($i = 0; $i < count($paragraphs); $i++) {
            $para_num = $i + 1;
            
            if ($para_num == 3 && !empty($image_urls[0])) {
                $final_html .= $this->get_image_html($image_urls[0], 'Image related to ' . $data['title']);
            }
            
            if ($para_num == 6 && !empty($youtube_id)) {
                $final_html .= $this->get_youtube_embed_html($youtube_id);
            }
            
            if ($para_num == 9 && !empty($image_urls[1])) {
                $final_html .= $this->get_image_html($image_urls[1], 'Image related to ' . $data['title']);
            }
            
            $final_html .= $paragraphs[$i];
        }
        
        // Meta description
        $meta_description = $this->generate_meta_description($content, $data['title']);
        
        // SEO title
        $seo_title = $this->generate_seo_title($new_title);
        
        // Post slug
        $post_slug = sanitize_title($new_title);
        
        // Create post
        $post_id = wp_insert_post(array(
            'post_title' => $new_title,
            'post_content' => $final_html,
            'post_status' => isset($this->options['post_status']) ? $this->options['post_status'] : 'draft',
            'post_type' => 'post',
            'post_name' => $post_slug,
            'meta_input' => array(
                '_rss_link' => $data['link'],
                '_rss_date' => $data['date'],
                '_original_title' => $data['title'],
                '_generated_by' => '6arshid_RSS_Cron',
                '_youtube_video_id' => $youtube_id,
                '_image_count' => count($image_urls),
                '_yoast_wpseo_title' => $seo_title,
                '_yoast_wpseo_metadesc' => $meta_description,
                '_aioseo_title' => $seo_title,
                '_aioseo_description' => $meta_description,
                '_rank_math_title' => $seo_title,
                '_rank_math_description' => $meta_description,
                'seo_title' => $seo_title,
                'seo_description' => $meta_description
            )
        ));
        
        if ($post_id && !empty($image_urls)) {
            foreach ($image_urls as $index => $image_url) {
                $this->add_image($post_id, $image_url, $index);
            }
        }
        
        return $post_id;
    }
    
    /**
     * Split content into paragraphs
     */
    private function split_into_paragraphs($content) {
        $text = strip_tags($content);
        $lines = explode("\n", $text);
        
        $paragraphs = array();
        $current = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                if (!empty($current)) {
                    $paragraphs[] = "<p>" . $current . "</p>";
                    $current = '';
                }
            } else {
                $current .= ($current ? ' ' : '') . $line;
            }
        }
        
        if (!empty($current)) {
            $paragraphs[] = "<p>" . $current . "</p>";
        }
        
        return $paragraphs;
    }
    
    /**
     * Search multiple images from Pexels
     */
    private function search_pexels_images($query, $count = 2) {
        if (empty($this->options['pexels_api'])) return array();
        
        $response = wp_remote_get("https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=" . $count, array(
            'headers' => array(
                'Authorization' => $this->options['pexels_api']
            ),
            'timeout' => 20
        ));
        
        if (is_wp_error($response)) {
            $this->log("Pexels Error: " . $response->get_error_message());
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $images = array();
        
        if (isset($data['photos']) && is_array($data['photos'])) {
            foreach ($data['photos'] as $photo) {
                if (isset($photo['src']['large'])) {
                    $images[] = $photo['src']['large'];
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Search single image from Pexels (for backward compatibility)
     */
    private function search_pexels_image($query) {
        $images = $this->search_pexels_images($query, 1);
        return !empty($images) ? $images[0] : false;
    }
    
    /**
     * Image HTML with styling
     */
    private function get_image_html($url, $alt = '') {
        return '<div style="margin: 30px 0; text-align: center;">
            <img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        </div>';
    }
    
    /**
     * Search YouTube video
     */
    private function search_youtube_video($query) {
        if (empty($this->options['youtube_api'])) return false;
        
        $url = "https://www.googleapis.com/youtube/v3/search";
        $params = array(
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => 1,
            'key' => $this->options['youtube_api'],
            'relevanceLanguage' => 'fa'
        );
        
        $response = wp_remote_get($url . '?' . http_build_query($params), array('timeout' => 30));
        
        if (is_wp_error($response)) return false;
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['items'][0]['id']['videoId'])) {
            return $body['items'][0]['id']['videoId'];
        }
        
        return false;
    }
    
    /**
     * YouTube embed HTML
     */
    private function get_youtube_embed_html($video_id) {
        return '<div style="margin: 30px 0; position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <iframe style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" src="https://www.youtube.com/embed/' . $video_id . '" frameborder="0" allowfullscreen></iframe>
        </div>';
    }
    
    /**
     * Generate new title using Title Prompt
     */
    private function generate_new_title($original_title) {
        if (empty($this->options['deepseek_api'])) return false;
        
        $title_prompt = isset($this->options['title_prompt']) ? $this->options['title_prompt'] : '';
        
        if (empty($title_prompt)) {
            $title_prompt = "Original title: {title}\n\nPlease write an attractive and SEO-friendly title based on the original title above.";
        }
        
        $prompt = str_replace('{title}', $original_title, $title_prompt);
        
        $response = wp_remote_post('https://api.deepseek.com/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->options['deepseek_api'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'deepseek-chat',
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are an SEO and title generation expert. Return only the title.'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.8,
                'max_tokens' => 100
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) return false;
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            return trim($body['choices'][0]['message']['content'], '"\'');
        }
        
        return false;
    }
    
    /**
     * Generate meta description
     */
    private function generate_meta_description($content, $title) {
        if (empty($this->options['deepseek_api'])) return '';
        
        $meta_prompt = isset($this->options['meta_desc_prompt']) ? $this->options['meta_desc_prompt'] : '';
        
        if (empty($meta_prompt)) {
            $meta_prompt = "Based on the article below, write an attractive and SEO-friendly meta description. Max 155 characters:\n\n{content}";
        }
        
        $content_preview = wp_trim_words(strip_tags($content), 100);
        $prompt = str_replace('{content}', $content_preview, $meta_prompt);
        $prompt = str_replace('{title}', $title, $prompt);
        
        $response = wp_remote_post('https://api.deepseek.com/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->options['deepseek_api'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'deepseek-chat',
                'messages' => array(
                    array('role' => 'system', 'content' => 'Write a short and attractive meta description.'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.7,
                'max_tokens' => 200
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) return '';
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            $meta = trim($body['choices'][0]['message']['content']);
            if (strlen($meta) > 155) {
                $meta = substr($meta, 0, 152) . '...';
            }
            return $meta;
        }
        
        return '';
    }
    
    /**
     * Generate SEO title tag
     */
    private function generate_seo_title($title) {
        $template = isset($this->options['seo_title_template']) ? $this->options['seo_title_template'] : '{title} - {site_name}';
        $site_name = isset($this->options['site_name']) ? $this->options['site_name'] : get_bloginfo('name');
        
        $seo_title = str_replace('{title}', $title, $template);
        $seo_title = str_replace('{site_name}', $site_name, $seo_title);
        
        return $seo_title;
    }
    
    /**
     * Generate main content
     */
    private function generate_content($title, $desc) {
        if (empty($this->options['deepseek_api'])) return false;
        
        $content_template = isset($this->options['content_prompt']) ? $this->options['content_prompt'] : '';
        
        if (empty($content_template)) {
            $content_template = "Title: {title}\n\nDescription: {description}\n\nPlease write a complete 1500-word article in Persian with 10 paragraphs.";
        }
        
        $prompt = str_replace('{title}', $title, $content_template);
        $prompt = str_replace('{description}', $desc, $prompt);
        
        $response = wp_remote_post('https://api.deepseek.com/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->options['deepseek_api'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => 'deepseek-chat',
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are a professional content writer.'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.8,
                'max_tokens' => 3000
            )),
            'timeout' => 90
        ));
        
        if (is_wp_error($response)) {
            $this->log("DeepSeek Error: " . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            return $body['choices'][0]['message']['content'];
        }
        
        return false;
    }
    
    /**
     * Add image to post
     */
    private function add_image($post_id, $url, $index = 0) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($url, 60);
        
        if (is_wp_error($tmp)) {
            $this->log("Error downloading image: " . $tmp->get_error_message());
            return false;
        }
        
        $file = array(
            'name' => sanitize_title(basename($url)) . '-' . $index . '.jpg',
            'tmp_name' => $tmp
        );
        
        $attachment_id = media_handle_sideload($file, $post_id);
        @unlink($tmp);
        
        if (!is_wp_error($attachment_id)) {
            if ($index == 0) {
                // First image as featured image
                set_post_thumbnail($post_id, $attachment_id);
            }
            return true;
        }
        
        return false;
    }
}

new RSS_Generator_6arshid();