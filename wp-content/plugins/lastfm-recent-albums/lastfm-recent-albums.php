<?php
/**
 * Plugin Name: Last.fm Recent Albums
 * Plugin URI: https://serhii.blog
 * Description: Display your recent albums from Last.fm with live preview in the block editor
 * Version: 1.0.0
 * Author: Serhii
 * Author URI: https://serhii.blog
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lastfm-albums
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LASTFM_ALBUMS_VERSION', '1.0.0');
define('LASTFM_ALBUMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LASTFM_ALBUMS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class LastFM_Albums_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register_block']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_lastfm_clear_cache', [$this, 'handle_clear_cache']);
    }
    
    /**
     * Register the Gutenberg block
     */
    public function register_block() {
        // Register block script
        wp_register_script(
            'lastfm-albums-block',
            LASTFM_ALBUMS_PLUGIN_URL . 'build/index.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-api-fetch'],
            LASTFM_ALBUMS_VERSION
        );
        
        // Register editor style
        wp_register_style(
            'lastfm-albums-editor-style',
            LASTFM_ALBUMS_PLUGIN_URL . 'build/index.css',
            ['wp-edit-blocks'],
            LASTFM_ALBUMS_VERSION
        );
        
        // Register frontend style
        wp_register_style(
            'lastfm-albums-style',
            LASTFM_ALBUMS_PLUGIN_URL . 'build/style-index.css',
            [],
            LASTFM_ALBUMS_VERSION
        );
        
        // Register the block
        register_block_type('lastfm-albums/recent-albums', [
            'editor_script' => 'lastfm-albums-block',
            'editor_style' => 'lastfm-albums-editor-style',
            'style' => 'lastfm-albums-style',
            'render_callback' => [$this, 'render_block'],
            'attributes' => [
                'albumCount' => [
                    'type' => 'number',
                    'default' => 4
                ]
            ]
        ]);
    }
    
    /**
     * Render the block on the frontend
     */
    public function render_block($attributes) {
        $album_count = isset($attributes['albumCount']) ? intval($attributes['albumCount']) : 4;
        $albums = $this->fetch_albums($album_count);
        
        if (is_wp_error($albums)) {
            return '<div class="lastfm-error">' . esc_html($albums->get_error_message()) . '</div>';
        }
        
        if (empty($albums)) {
            return '<div class="lastfm-empty">No albums found</div>';
        }
        
        ob_start();
        include LASTFM_ALBUMS_PLUGIN_DIR . 'templates/albums-grid.php';
        return ob_get_clean();
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('lastfm-albums/v1', '/albums', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_albums'],
            'permission_callback' => '__return_true',
            'args' => [
                'limit' => [
                    'default' => 4,
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
    }
    
    /**
     * REST API callback to get albums
     */
    public function rest_get_albums($request) {
        $limit = $request->get_param('limit');
        $albums = $this->fetch_albums($limit);
        
        if (is_wp_error($albums)) {
            return new WP_Error(
                'fetch_error',
                $albums->get_error_message(),
                ['status' => 500]
            );
        }
        
        return rest_ensure_response($albums);
    }
    
    /**
     * Fetch albums from Last.fm API
     */
    private function fetch_albums($limit = 4) {
        $api_key = get_option('lastfm_albums_api_key');
        $username = get_option('lastfm_albums_username');
        
        if (empty($api_key) || empty($username)) {
            return new WP_Error('not_configured', 'Last.fm API key or username not configured');
        }
        
        // Check cache
        $transient_key = 'lastfm_albums_' . $username . '_' . $limit;
        $cached = get_transient($transient_key);
        if ($cached !== false) {
            return $cached;
        }
        
        // Fetch from API
        $api_url = sprintf(
            'https://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=%s&api_key=%s&format=json&limit=50',
            urlencode($username),
            $api_key
        );
        
        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['recenttracks']['track'])) {
            return new WP_Error('no_data', 'No tracks found from Last.fm');
        }
        
        $tracks = $data['recenttracks']['track'];
        $unique_albums = [];
        $seen_albums = [];
        
        foreach ($tracks as $track) {
            $album_name = isset($track['album']['#text']) ? $track['album']['#text'] : '';
            $artist_name = isset($track['artist']['#text']) ? $track['artist']['#text'] : '';
            
            if (empty($album_name)) {
                continue;
            }
            
            $album_key = $artist_name . '-' . $album_name;
            
            if (!in_array($album_key, $seen_albums) && count($unique_albums) < $limit) {
                $seen_albums[] = $album_key;
                
                $image_url = '';
                if (isset($track['image']) && is_array($track['image'])) {
                    foreach (array_reverse($track['image']) as $img) {
                        if (!empty($img['#text'])) {
                            $image_url = $img['#text'];
                            break;
                        }
                    }
                }
                
                $unique_albums[] = [
                    'name' => $album_name,
                    'artist' => $artist_name,
                    'image' => $image_url,
                    'url' => isset($track['url']) ? $track['url'] : '#'
                ];
            }
            
            if (count($unique_albums) >= $limit) {
                break;
            }
        }
        
        // Cache for 10 minutes
        set_transient($transient_key, $unique_albums, 10 * MINUTE_IN_SECONDS);
        
        return $unique_albums;
    }
    
    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_options_page(
            'Last.fm Albums Settings',
            'Last.fm Albums',
            'manage_options',
            'lastfm-albums-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('lastfm_albums_settings', 'lastfm_albums_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        
        register_setting('lastfm_albums_settings', 'lastfm_albums_username', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ]);
        
        add_settings_section(
            'lastfm_albums_main_section',
            'Last.fm API Configuration',
            [$this, 'settings_section_callback'],
            'lastfm-albums-settings'
        );
        
        add_settings_field(
            'lastfm_albums_api_key',
            'API Key',
            [$this, 'api_key_field_callback'],
            'lastfm-albums-settings',
            'lastfm_albums_main_section'
        );
        
        add_settings_field(
            'lastfm_albums_username',
            'Username',
            [$this, 'username_field_callback'],
            'lastfm-albums-settings',
            'lastfm_albums_main_section'
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $username = get_option('lastfm_albums_username', '');
        $cache_cleared = isset($_GET['cache_cleared']) ? true : false;
        ?>
        <div class="wrap">
            <h1>Last.fm Albums Settings</h1>
            
            <?php if ($cache_cleared) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>Cache cleared successfully! Your albums will refresh on next load.</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lastfm_albums_settings');
                do_settings_sections('lastfm-albums-settings');
                submit_button();
                ?>
            </form>
            
            <?php if (!empty($username)) : ?>
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>Clear Cache</h2>
                <p>If your albums aren't updating, you can manually clear the cache.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="lastfm_clear_cache">
                    <?php wp_nonce_field('lastfm_clear_cache_action', 'lastfm_clear_cache_nonce'); ?>
                    <?php submit_button('Clear Cache Now', 'secondary', 'submit', false); ?>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 600px; margin-top: 20px;">
                <h2>How to get your Last.fm API Key</h2>
                <ol>
                    <li>Go to <a href="https://www.last.fm/api/account/create" target="_blank">Last.fm API Account Creation</a></li>
                    <li>Fill out the application form</li>
                    <li>Copy your API key and paste it above</li>
                    <li>Enter your Last.fm username (from your profile URL)</li>
                    <li>Save changes</li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    public function settings_section_callback() {
        echo '<p>Configure your Last.fm API credentials to display your recent albums.</p>';
    }
    
    public function api_key_field_callback() {
        $api_key = get_option('lastfm_albums_api_key', '');
        ?>
        <input type="text" 
               name="lastfm_albums_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text" 
               placeholder="Your Last.fm API key">
        <?php
    }
    
    public function username_field_callback() {
        $username = get_option('lastfm_albums_username', '');
        ?>
        <input type="text" 
               name="lastfm_albums_username" 
               value="<?php echo esc_attr($username); ?>" 
               class="regular-text" 
               placeholder="Your Last.fm username">
        <?php
    }
    
    /**
     * Handle cache clearing
     */
    public function handle_clear_cache() {
        // Verify nonce
        if (!isset($_POST['lastfm_clear_cache_nonce']) || 
            !wp_verify_nonce($_POST['lastfm_clear_cache_nonce'], 'lastfm_clear_cache_action')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Clear all Last.fm transients
        $username = get_option('lastfm_albums_username', '');
        if (!empty($username)) {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_lastfm_albums_' . $username . '_%'
            ));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
                '_transient_timeout_lastfm_albums_' . $username . '_%'
            ));
        }
        
        // Redirect back with success message
        wp_redirect(add_query_arg('cache_cleared', '1', admin_url('options-general.php?page=lastfm-albums-settings')));
        exit;
    }
}

// Initialize the plugin
LastFM_Albums_Plugin::get_instance();