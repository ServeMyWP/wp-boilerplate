<?php
/**
 * Overrides
 *
 * The override functions for this theme.
 *
 * @author Stephen Sabatini <info@stephensabatini.com>
 * @package Boilerplate\Overrides
 * @version 1.0.0
 * @license GPL, or GNU General Public License, version 2
 */

namespace Boilerplate\Overrides;

/**
 * Registers instances where we will override default WP Core behavior.
 *
 * @link https://developer.wordpress.org/reference/functions/print_emoji_detection_script/
 * @link https://developer.wordpress.org/reference/functions/print_emoji_styles/
 * @link https://developer.wordpress.org/reference/functions/wp_staticize_emoji/
 * @link https://developer.wordpress.org/reference/functions/wp_staticize_emoji_for_email/
 * @link https://developer.wordpress.org/reference/functions/wp_generator/
 * @link https://developer.wordpress.org/reference/functions/wlwmanifest_link/
 * @link https://developer.wordpress.org/reference/functions/rsd_link/
 *
 * @return void
 */
function setup() {

	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	// Remove the Emoji detection script.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

	// Remove inline Emoji detection script.
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

	// Remove Emoji-related styles from front end and back end.
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );

	// Remove Emoji-to-static-img conversion.
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	add_filter( 'tiny_mce_plugins', $n( 'disable_emojis_tinymce' ) );
	add_filter( 'wp_resource_hints', $n( 'disable_emoji_dns_prefetch' ), 10, 2 );

	// Remove WP version from the head and RSS feeds.
	remove_action( 'wp_head', 'wp_generator' );

	// Remove Windows Live Writer manifest link.
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// Remove the link to Really Simple Discovery service endpoint.
	remove_action( 'wp_head', 'rsd_link' );

	// Disable XML-RPC by default for security reasons.
	add_filter( 'xmlrpc_enabled', '__return_false' );

	// Remove WP version from styles and scripts for security reasons.
	add_filter( 'style_loader_src', $n( 'remove_asset_versions' ), 9999 );
	add_filter( 'script_loader_src', $n( 'remove_asset_versions' ), 9999 );

	// Add all of the favicon sizes that WordPress doesn't include by default.
	// TODO: Setup this API to generate the manifest.json and browserconfig.xml dynamically.
	add_filter( 'site_icon_image_sizes', $n( 'add_site_icon_image_sizes' ) );
	add_filter( 'site_icon_meta_tags', $n( 'add_site_icon_meta_tags' ) );

	add_action( 'wp_head', $n( 'js_detection' ), 0 );
	add_action( 'wp_head', $n( 'add_browserconfig' ), 10 );

	/**
	 * Filter the "read more" excerpt string link to the post.
	 *
	 * @param string $more "Read more" excerpt string.
	 * @return string (Maybe) modified "read more" excerpt string.
	 */
	add_filter( 'excerpt_more', $n( 'custom_excerpt' ) );
}

/**
 * Filter function used to remove the TinyMCE emoji plugin.
 *
 * @link https://developer.wordpress.org/reference/hooks/tiny_mce_plugins/
 *
 * @param  array $plugins An array of default TinyMCE plugins.
 * @return array          An array of TinyMCE plugins, without wpemoji.
 */
function disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) && in_array( 'wpemoji', $plugins, true ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}
	return $plugins;
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @link https://developer.wordpress.org/reference/hooks/emoji_svg_url/
 *
 * @param  array  $urls          URLs to print for resource hints.
 * @param  string $relation_type The relation type the URLs are printed for.
 * @return array                 Difference betwen the two arrays.
 */
function disable_emoji_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		/** This filter is documented in wp-includes/formatting.php */
		$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );

		$urls = array_values( array_diff( $urls, array( $emoji_svg_url ) ) );
	}
	return $urls;
}

function remove_asset_versions( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}

function add_site_icon_image_sizes( $sizes ) {
	$sizes[] = 64;
	return $sizes;
}

function add_site_icon_meta_tags( $meta_tags ) {
	$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="64x64" />', esc_url( get_site_icon_url( 64 ) ) );
	$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="512x512" />', esc_url( get_site_icon_url( 512 ) ) );
	return $meta_tags;
}

function custom_excerpt( $more ) {
	if ( ! is_single() ) {
		$more = sprintf(
			'…',
			get_permalink( get_the_ID() ),
			__( 'Read More', 'wp-boilerplate' )
		);
	}
	return $more;
}

/**
 * Handles JavaScript detection.
 *
 * Adds a `js` class to the root `<html>` element when JavaScript is detected.
 *
 * @return void
 */
function js_detection() {
	echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>" . PHP_EOL;
}

/**
 * Appends a meta tag used to add a browserconfig.xml to the head for IE11.
 *
 * TODO: Once IE11 reaches EOL and WordPress drops support, this will disapear.
 *
 * @return void
 */
function add_browserconfig() {
	echo '<meta name="msapplication-config" content="' . esc_url( BOILERPLATE_TEMPLATE_URL . '/browserconfig.xml' ) . '" />' . PHP_EOL;
}
