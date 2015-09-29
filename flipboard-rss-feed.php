<?php
/**
 * Flipboard RSS Feed
 *
 * This plugin alters WordPress's built-in RSS feeds to make it compatible with Flipboard RSS specification
 *
 * @package   Flipboard_RSS_Feed
 * @author    Jonathan Harris <jonathan.harris@timeinc.com>
 * @license   GPL-2.0+
 * @link      http://www.jonathandavidharris.co.uk/
 * @copyright 2014 Time Inc. (UK) Ltd
 *
 * @wordpress-plugin
 * Plugin Name:       Flipboard RSS Feed
 * Plugin URI:        http://www.timeincuk.com/
 * Description:       Generate a flipboard RSS Feed
 * Version:           1.0.9
 * Author:            Jonathan Harris
 * Author URI:        http://www.jonathandavidharris.co.uk/
 * Text Domain:       flipboard-rss-feed
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/TimeIncUK/flipboard-rss-feed
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-flipboard-rss-feed.php' );

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 *
 */
register_activation_hook( __FILE__, array( 'Flipboard_RSS_Feed', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Flipboard_RSS_Feed', 'deactivate' ) );


add_action( 'plugins_loaded', array( 'Flipboard_RSS_Feed', 'get_instance' ) );
