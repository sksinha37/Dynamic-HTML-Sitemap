<?php
/*
Plugin Name: Dynamic HTML Sitemap
Plugin URI: https://techarchers.in/dynamic-html-sitemap
Description: A comprehensive WordPress plugin that generates a dynamic, customizable HTML sitemap, including pages, posts, custom post types, and categories, with easy exclusion options from the frontend.
Version: 1.0
Author: Suraj Kumar Sinha
Author URI: https://techarchers.in
License: GPL-2.0-or-later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: dynamic-html-sitemap
Domain Path: /languages
*/

/*  Copyright 2024 Suraj Kumar Sinha (email: suraj@techarchers.in)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2,
    as published by the Free Software Foundation.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
*/


// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define constants.
define('DYNAMIC_SITEMAP_VERSION', '1.0');
define('DYNAMIC_SITEMAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DYNAMIC_SITEMAP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files.
require_once DYNAMIC_SITEMAP_PLUGIN_DIR . 'includes/class-settings.php';
require_once DYNAMIC_SITEMAP_PLUGIN_DIR . 'includes/class-sitemap-render.php';

// Initialize the settings and rendering classes.
add_action('plugins_loaded', function () {
    new Dynamic_Sitemap_Settings();
    new Dynamic_Sitemap_Render();
});
