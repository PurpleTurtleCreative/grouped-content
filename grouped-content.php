<?php
/**
 * Grouped Content
 *
 * @package           PTC_Grouped_Content
 * @author            Michelle Blanchette
 * @copyright         2022 Purple Turtle Creative, LLC
 * @license           GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Grouped Content
 * Plugin URI:        https://purpleturtlecreative.com/grouped-content/
 * Description:       Provides easy access and insight into hierarchical posts' parent page, sibling pages, and child pages in your admin area.
 * Version:           2.0.0
 * Requires PHP:      7.0
 * Requires at least: 4.7.1
 * Author:            Purple Turtle Creative
 * Author URI:        https://purpleturtlecreative.com/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 */

/*
Grouped Content is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, version 3 of the License.

Grouped Content is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Grouped Content. If not, see https://www.gnu.org/licenses/gpl-3.0.txt.
*/

namespace PTC_Grouped_Content;

defined( 'ABSPATH' ) || die();

/**
 * The full file path to this plugin's main file.
 *
 * @since 2.0.0
 */
define( __NAMESPACE__ . '\PLUGIN_FILE', __FILE__ );

/**
 * The full file path to this plugin's directory ending with a slash.
 *
 * @since 2.0.0
 */
define( __NAMESPACE__ . '\PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * This plugin's current version.
 *
 * @since 2.0.0
 */
define( __NAMESPACE__ . '\PLUGIN_VERSION', get_file_data( __FILE__, [ 'Version' => 'Version' ], 'plugin' )['Version'] ?? '0.0.0' );

/**
 * This plugin's basename.
 *
 * @since 2.0.0
 */
define( __NAMESPACE__ . '\PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The full url to this plugin's directory, ending with a slash.
 *
 * @since 2.0.0
 */
define( __NAMESPACE__ . '\PLUGIN_URL', plugins_url( '/', __FILE__ ) );

/* CODE REGISTRATION */

/**
 * Requires a class file and calls its static "register" method.
 *
 * Class file names and class names must follow WordPress naming conventions.
 * For example, /path/to/class-my-class.php should contain the declaration of
 * class My_Class.
 *
 * Intentionally does not check if a "register" method exists in the class
 * or if the class has been properly included. Doing so would hide ACTUAL errors
 * due to formatting mistakes that should, indeed, be noticed and fixed!
 *
 * @link https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/#naming-conventions
 *
 * @param string $file The full class filename.
 */
function register_class_from_file( string $file ) {

	$class_name = str_replace(
		[ 'class-', '-' ],
		[ '', '_' ],
		basename( $file, '.php' )
	);
	$class_name = __NAMESPACE__ . '\\' . ucwords( $class_name, '_' );

	if ( ! class_exists( $class_name ) ) {
		require_once $file;
		$class_name::register();
	}
}

/* Register Public Functionality */
foreach ( glob( PLUGIN_PATH . '/src/public/class-*.php' ) as $file ) {
	register_class_from_file( $file );
}

if ( is_admin() ) {
	/* Register Admin-Only Functionality */
	foreach ( glob( PLUGIN_PATH . '/src/admin/class-*.php' ) as $file ) {
		register_class_from_file( $file );
	}
} else {
	/* Register Frontend-Only Functionality */
	foreach ( glob( PLUGIN_PATH . '/src/public/frontend/class-*.php' ) as $file ) {
		register_class_from_file( $file );
	}
}
