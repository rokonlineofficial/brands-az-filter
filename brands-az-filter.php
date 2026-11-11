<?php
/**
 * Plugin Name:       Rok Brands A-Z Filter
 * Description:       "Shop By Brand" page with A-Z letter filter and brand search. Add the shortcode [brands_az] to any page.
 * Version:           3.0.0
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Rok Online
 * Author URI:        https://rokonline.com/
 * Plugin URI:        https://rokonline.com/
 * License:           GPL-2.0-or-later
 * Text Domain:       brands-az
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Do nothing if another copy of this plugin is already loaded.
if ( defined( 'BRANDSAZ_VERSION' ) ) {
	return;
}

define( 'BRANDSAZ_VERSION', '1.0.0' );
define( 'BRANDSAZ_FILE', __FILE__ );
define( 'BRANDSAZ_PATH', plugin_dir_path( __FILE__ ) );
define( 'BRANDSAZ_URL', plugin_dir_url( __FILE__ ) );

require_once BRANDSAZ_PATH . 'includes/class-brandsaz-settings.php';
require_once BRANDSAZ_PATH . 'includes/class-brandsaz-shortcode.php';

add_action(
	'plugins_loaded',
	function () {
		BrandsAZ_Settings::init();
		BrandsAZ_Shortcode::init();
	}
);
