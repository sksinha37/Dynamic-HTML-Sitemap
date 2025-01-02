<?php

if (!defined('ABSPATH')) {
    exit;
}

class Dynamic_Sitemap_Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_color_picker']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_custom_styles']);

        // Load text domain for translations
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('dynamic-html-sitemap', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_settings_menu()
    {
        $icon_url = plugin_dir_url(__FILE__) . '../assets/images/icon.svg';  // Path to your custom icon
        add_menu_page(
            __('Dynamic Sitemap Settings', 'dynamic-html-sitemap'),  // Translatable title
            __('Dynamic Sitemap', 'dynamic-html-sitemap'),           // Translatable menu item
            'manage_options',
            'dynamic-html-sitemap',
            [$this, 'render_settings_page'],
            $icon_url,
            80
        );
    }
    public function enqueue_custom_styles()
    {
        // Ensure the CSS is only added for the plugin settings page
        // if (isset($_GET['page']) && $_GET['page'] === 'dynamic-html-sitemap') {
        echo '<style>
                #toplevel_page_dynamic-html-sitemap .wp-menu-image img {
                    width: 16px !important;  
                    height: 16px;
                }
            </style>';
        // }
    }

    public function render_settings_page()
    {
?>
        <div class="wrap">
            <h1><?php esc_html('Dynamic Sitemap Settings', 'dynamic-html-sitemap'); ?></h1> <!-- Translatable heading -->
            <form method="post" action="options.php">
                <?php
                settings_fields('dynamic_sitemap_settings_group');
                wp_nonce_field('dynamic_sitemap_save_settings', 'dynamic_sitemap_nonce');
                do_settings_sections('dynamic-html-sitemap');
                submit_button(__('Save Settings', 'dynamic-html-sitemap'));
                ?>
            </form>

        </div>
    <?php
    }

    public function register_settings()
    {
        // Register the setting to store excluded items (pages, posts, custom post types)
        register_setting(
            'dynamic_sitemap_settings_group',
            'dynamic_sitemap_exclude_items',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_exclude_items'],
                'default' => [],
            ]
        );

        // Register background color setting
        register_setting(
            'dynamic_sitemap_settings_group',
            'dynamic_sitemap_background_color',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color',
                'default' => '#ff615f',  // Default color
            ]
        );

        // Create a settings section
        add_settings_section(
            'dynamic_sitemap_main_section',
            __('General Settings', 'dynamic-html-sitemap'),  // Translatable section title
            null,
            'dynamic-html-sitemap'
        );

        // Exclude items field
        add_settings_field(
            'dynamic_sitemap_exclude_items',
            __('Exclude Pages or Posts', 'dynamic-html-sitemap'),  // Translatable field label
            [$this, 'exclude_items_field_callback'],
            'dynamic-html-sitemap',
            'dynamic_sitemap_main_section'
        );

        // Background color field
        add_settings_field(
            'dynamic_sitemap_background_color',
            __('Sitemap Background Color', 'dynamic-html-sitemap'),  // Translatable field label
            [$this, 'background_color_field_callback'],
            'dynamic-html-sitemap',
            'dynamic_sitemap_main_section'
        );
    }

    public function exclude_items_field_callback()
    {
        $exclude_items = get_option('dynamic_sitemap_exclude_items', []);

        // Get all pages
        $all_pages = get_pages();

        // Get all posts (default post type)
        $all_posts = get_posts(['numberposts' => -1]);

        // Get all custom post types
        $args = [
            'public'   => true,
            '_builtin' => false,  // Exclude built-in post types (like posts, pages)
        ];
        $custom_post_types = get_post_types($args, 'objects');

    ?>
        <select name="dynamic_sitemap_exclude_items[]" multiple style="width: 100%; height: 200px;">
            <optgroup label="<?php esc_attr_e('Pages', 'dynamic-html-sitemap'); ?>"> <!-- Translatable optgroup label -->
                <?php foreach ($all_pages as $page) : ?>
                    <option value="page-<?php echo esc_attr($page->ID); ?>"
                        <?php echo in_array("page-{$page->ID}", $exclude_items) ? 'selected' : ''; ?>>
                        <?php echo esc_html($page->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>

            <optgroup label="<?php esc_attr_e('Posts', 'dynamic-html-sitemap'); ?>"> <!-- Translatable optgroup label -->
                <?php foreach ($all_posts as $post) : ?>
                    <option value="post-<?php echo esc_attr($post->ID); ?>"
                        <?php echo in_array("post-{$post->ID}", $exclude_items) ? 'selected' : ''; ?>>
                        <?php echo esc_html($post->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>

            <optgroup label="<?php esc_attr_e('Custom Post Types', 'dynamic-html-sitemap'); ?>"> <!-- Translatable optgroup label -->
                <?php foreach ($custom_post_types as $cpt) : ?>
                    <?php
                    $posts_of_cpt = get_posts([
                        'numberposts' => -1,
                        'post_type'   => $cpt->name,
                    ]);
                    ?>
            <optgroup label="<?php echo esc_html($cpt->label); ?>">
                <?php foreach ($posts_of_cpt as $post) : ?>
                    <option value="<?php echo esc_attr($cpt->name . '-' . $post->ID); ?>"
                        <?php echo in_array("{$cpt->name}-{$post->ID}", $exclude_items) ? 'selected' : ''; ?>>
                        <?php echo esc_html($post->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </optgroup>
        <?php endforeach; ?>
        </optgroup>
        </select>
        <p class="description"><?php esc_html('Select the pages, posts, or custom post types you want to exclude from the sitemap.', 'dynamic-html-sitemap'); ?></p> <!-- Translatable description -->
    <?php
    }

    public function background_color_field_callback()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD']) {
            // Retrieve and sanitize the nonce
            $nonce = isset($_POST['dynamic_sitemap_nonce']) ? sanitize_text_field(wp_unslash($_POST['dynamic_sitemap_nonce'])) : '';

            // Verify the nonce
            if (empty($nonce) || ! wp_verify_nonce($nonce, 'dynamic_sitemap_save_settings')) {
                wp_die(esc_html__('Nonce verification failed. Please refresh the page and try again.', 'dynamic-html-sitemap'));
            }
        }




        // Get the background color from the settings or set a default
        $background_color = get_option('dynamic_sitemap_background_color', '#ff615f');
    ?>
        <input type="text" name="dynamic_sitemap_background_color" value="<?php echo esc_attr($background_color); ?>" class="color-picker" data-alpha="true" />
        <p class="description"><?php esc_html('Select the background color for the sitemap container.', 'dynamic-html-sitemap'); ?></p> <!-- Translatable description -->
<?php
    }

    public function sanitize_exclude_items($value)
    {
        // Verify the nonce
        if (wp_unslash(!isset($_POST['dynamic_sitemap_nonce'])) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['dynamic_sitemap_nonce'])), 'dynamic_sitemap_save_settings')) {
            wp_die(esc_html_e('Nonce verification failed. Please refresh the page and try again.', 'dynamic-html-sitemap'));
        }
        // Ensure the value is always an array
        return is_array($value) ? array_map('sanitize_text_field', $value) : [];
    }


    public function enqueue_color_picker()
    {
        // Ensure the color picker scripts and styles are only enqueued in the admin area
        if (isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'dynamic-html-sitemap') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('dynamic-html-sitemap-color-picker', plugin_dir_url(__FILE__) . '../assets/js/color-picker.js', ['wp-color-picker'], DYNAMIC_SITEMAP_VERSION, true);
            
        }
    }
}
?>