<?php

if (!defined('ABSPATH')) {
    exit;
}

class Dynamic_Sitemap_Render
{
    public function __construct()
    {
        add_shortcode('dynamic_sitemap', [$this, 'render_sitemap']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            'dynamic-html-sitemap-css',
            DYNAMIC_SITEMAP_PLUGIN_URL . 'assets/css/dynamic-html-sitemap.css',
            [],
            DYNAMIC_SITEMAP_VERSION
        );
    }

    public function render_sitemap($atts)
    {
        // Get the excluded items (pages, posts, and custom post types) from the admin settings
        $exclude_items = get_option('dynamic_sitemap_exclude_items', []);
        $exclude_pages = [];
        $exclude_posts = [];
        $exclude_custom_posts = [];
        // Get the background color from the options
        $background_color = get_option('dynamic_sitemap_background_color', '#ff615f');

        // Separate the items by type
        foreach ($exclude_items as $item) {
            list($type, $id) = explode('-', $item);
            if ($type === 'page') {
                $exclude_pages[] = $id;
            } elseif ($type === 'post') {
                $exclude_posts[] = $id;
            } else {
                $exclude_custom_posts[] = $id;
            }
        }

        ob_start(); ?>

        <div class="container sitemap-container" style="background-color: <?php echo esc_attr($background_color); ?>;">
            <h1 class="sitemap-title"><?php esc_html('📄 HTML Sitemap', 'dynamic-html-sitemap'); ?></h1> <!-- Translatable heading -->

            <?php
            // Pages Section
            $pages = get_pages(['exclude' => $exclude_pages]);
            if (!empty($pages)) : ?>
                <div class="sitemap-section">
                    <h2><?php echo esc_html('🌐 Pages', 'dynamic-html-sitemap'); ?></h2> <!-- Translatable section title -->
                    <ul class="sitemap-list">
                        <?php wp_list_pages(['title_li' => '', 'exclude' => implode(',', $exclude_pages)]); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php
            // Posts Section
            $all_posts = new WP_Query([
                'posts_per_page' => -1,
                'post_type' => 'post',
                'orderby' => 'date',
                'order' => 'DESC',
                'post__not_in' =>  $exclude_posts,
            ]);
            if ($all_posts->have_posts()) : ?>
                <div class="sitemap-section">
                    <h2><?php echo esc_html('📰 Posts', 'dynamic-html-sitemap'); ?></h2> <!-- Translatable section title -->
                    <ul class="sitemap-list">
                        <?php while ($all_posts->have_posts()) : $all_posts->the_post(); ?>
                            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php
            // Custom Post Types Section
            $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
            foreach ($custom_post_types as $post_type) :
                $custom_posts = new WP_Query([
                    'posts_per_page' => -1,
                    'post_type' => $post_type->name,
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'post__not_in' => $exclude_custom_posts,
                ]);
                if ($custom_posts->have_posts()) : ?>
                    <div class="sitemap-section">
                        <h2>📂 <?php echo esc_html($post_type->labels->name); ?></h2>
                        <ul class="sitemap-list">
                            <?php while ($custom_posts->have_posts()) : $custom_posts->the_post(); ?>
                                <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                            <?php endwhile;
                            wp_reset_postdata(); ?>
                        </ul>
                    </div>
            <?php endif;
            endforeach; ?>

            <?php
            // Categories Section
            $categories = get_categories();
            if (!empty($categories)) : ?>
                <div class="sitemap-section">
                    <h2><?php echo esc_html('📂 Categories', 'dynamic-html-sitemap'); ?></h2> <!-- Translatable section title -->
                    <ul class="sitemap-list">
                        <?php wp_list_categories(['title_li' => '']); ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

<?php
        return ob_get_clean();
    }
}
?>
