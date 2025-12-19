<?php
/**
 * Plugin Name: Simple Post Form
 * Plugin URI: https://code045.nl/projects/simple-post-form
 * Description: A drag-and-drop form builder with customizable fields and styling options.
 * Author: Code045
 * Author URI: https://code045.nl/
 * Version: 1.5.2
 * Requires at least: 6.0
 * Tested up to: 6.7
 *
 * Text Domain: simple-post-form
 * Domain Path: /languages/
 *
 * @package Code045\simple-post-form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
if ( ! defined( 'SPF_PLUGIN_FILE' ) ) {
	define( 'SPF_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SPF_PLUGIN_DIR' ) ) {
	define( 'SPF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SPF_PLUGIN_URL' ) ) {
	define( 'SPF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SPF_VERSION' ) ) {
	define( 'SPF_VERSION', '1.0.0' );
}

// Include the main class.
require_once SPF_PLUGIN_DIR . 'includes/class-simple-post-form.php';
require_once SPF_PLUGIN_DIR . 'includes/class-simple-post-form-admin.php';
require_once SPF_PLUGIN_DIR . 'includes/class-simple-post-form-frontend.php';
require_once SPF_PLUGIN_DIR . 'includes/class-simple-post-form-ajax.php';

/**
 * Returns the main instance of Simple_Post_Form.
 *
 * @since  1.0.0
 * @return Simple_Post_Form
 */
function simple_post_form() {
	return Simple_Post_Form::instance();
}

// Initialize the plugin.
simple_post_form();