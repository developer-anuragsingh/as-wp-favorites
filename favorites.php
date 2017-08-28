<?php
/*
 * Plugin Name: As Favorites
 * Version: 1.0
 * Plugin URI: http://www.anuragsingh.me/
 * Description: Add any Post, Page, Image or any other post type in user's favorite list.
 * Author: Anurag Singh
 * Author URI: http://www.anuragsingh.me/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: favorites
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Anurag Singh
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-favorites.php' );
require_once( 'includes/class-favorites-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-favorites-admin-api.php' );
require_once( 'includes/lib/class-favorites-post-type.php' );
require_once( 'includes/lib/class-favorites-taxonomy.php' );

/**
 * Returns the main instance of Favorites to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Favorite
 */
function Favorites () {
	$instance = Favorites::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Favorites_Settings::instance( $instance );
	}

	return $instance;
}

Favorites();