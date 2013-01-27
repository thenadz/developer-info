<?php
/*
Plugin Name: WP Developer Info
Plugin URI: http://wordpress.org/extend/plugins/developer-info
Description: This plugin provides easy access to the WordPress.org Plugin & Theme Info APIs.
Author: Dan Rossiter
Version: 0.2
Author URI: http://danrossiter.org
 */

define( 'DI_PLUGIN', 0 );
define( 'DI_THEME', 1 );

define( 'DI_COMMENT', PHP_EOL.'<!-- Generated Using WP Developer Info: http://wordpress.org/extend/plugins/developer-info -->'.PHP_EOL );

define( 'DI_PLUGIN_INFO', 'http://api.wordpress.org/plugins/info/1.0/' );
define( 'DI_PLUGIN_STATS', 'http://api.wordpress.org/stats/plugin/1.0/' ); // [plugin-slug]?callback=[js func wrapper]
define( 'DI_PLUGIN_DOWNLOADS', 'http://api.wordpress.org/stats/plugin/1.0/downloads.php' ); // ?slug=[plugin-slug]&limit=[num]&callback=[js func wrapper]

define( 'DI_THEME_INFO', 'http://api.wordpress.org/themes/info/1.0/' );
define( 'DI_THEME_DOWNLOADS', 'http://api.wordpress.org/stats/themes/1.0/downloads.php' ); // ?slug=[plugin-slug]&limit=[num]&callback=[js func wrapper]);
// why is "theme" in ^ path plural when it's singular in the plugin equiv..? This API is GHETO!!!


function di_get_downloads( $slug, $limit=365, $cb=NULL, $type=DI_PLUGIN ){
	if( $type === DI_PLUGIN )
		$url = DI_PLUGIN_DOWNLOADS;
	else
		$url = DI_THEME_DOWNLOADS;
	$url .= "?slug=$slug&limit=$limit";

	if( $cb ) $url .= "&callback=$cb";
	
	$resp = wp_remote_get( $url, array( 'user-agent' => $_SERVER['HTTP_USER_AGENT'] ) );

	if( is_wp_error( $resp ) )
		return 0;
	elseif( $resp['response']['code'] > 299 || 
					$resp['response']['code'] < 200 )
		return 0;

	return $resp['body'];
}

// there doesn't appear to be a STATS url for themes
function di_get_stats( $slug, $cb=NULL ){//, $type=DI_PLUGIN ){
	$url = DI_PLUGIN_STATS.$slug;
	if( $cb ) $url .= "?callback=$cb";

	$resp = wp_remote_get( $url, array( 'user-agent' => $_SERVER['HTTP_USER_AGENT'] ) );

	if( is_wp_error( $resp ) )
		return 0;
	elseif( $resp['response']['code'] > 299 || 
					$resp['response']['code'] < 200 )
		return 0;

	return $resp['body'];
}

function di_information( $slug, $fields=NULL, $type=DI_PLUGIN ){
	$args = array( 'slug' => $slug );
	if( $fields !== NULL && is_array($fields) )
		$args['fields'] = $fields;

	return di_send_info_request( 'plugin_information', $args, $type );
}

// (array)args may include...
// browse – A bbPress View to “browse”, eg, “popular” = http://wordpress.org/extend/plugins/browse/popular/
// search – The term to search for
// tag – Browse by a tag
// author – Browse by an author (Note: .org has a few plugins to extend the author search to include contributors/etc)
//
// (array)fields may include...
// ‘description’, ‘sections’, ‘tested’ ,’requires’, ‘rating’, ‘downloaded’, ‘downloadlink’, ‘last_updated’ , ‘homepage’, ‘tags’
//
// Returns: array of plugin objects (like pi_information)
function di_query( $args, $fields=NULL, $type=DI_PLUGIN ){
	if( $fields !== NULL && is_array($fields) && !isset($args['fields']) )
		$args['fields'] = $fields;

	return di_send_info_request( 'query_plugins', $args, $type );
}

// Returns: array of objects
// - 'name' - tag name
// - 'slug' - tag slug
// - 'count' - number of plugins
function di_hot_tags( $number=100, $type=DI_PLUGIN ){
	return di_send_request( 'hot_tags', $number, $type );
}

function di_send_info_request( $action, $args, $type ){
	$body = array(
		'action'	=> $action,
		'request'	=> serialize( (object)$args )
	);
	
	if( $type === DI_PLUGIN )
		$url = DI_PLUGIN_INFO;
	else
		$url = DI_THEME_INFO;

	$response = wp_remote_post( $url, array( 'body' => $body ) );

	if( is_wp_error( $response ) ){
		return 0;
	}

	$response = unserialize( $response['body'] );
	// hot_tags returns Array which will cause issues
	if( is_object( $response ) && di_is_error( $response ) ) {
		return 0;
	}

	return $response;
}

function di_is_error( $obj ){
	return property_exists( $obj, 'error' );
}

/* ADD SHORTCODE */
function di_do_shortcode( $args ){
	extract( shortcode_atts( array(
		'slug'				=> NULL,
		'query_type'	=> NULL, // browse, search, tag, author
		'query_value'	=> NULL, // term to query for
		'field'				=> NULL, // value to return
		'type'				=> 'plugin',
		'cache'				=> true			// not supported yet
	), $args ) );

	if( $query_type ^ $query_value ) // TODO: handle error
		return 0; // both or neither must be defined
	switch( $query_type ){
		case 'browse':
		case 'search':
		case 'tag':
		case 'author':
			break;
		default: // TODO: handle error
			return 0; // unsupported type
	}

	if( $type == 'plugin' ){
		$type = DI_PLUGIN;
	elseif( $type == 'theme' )
		$type = DI_THEME;
	else // TODO: Handle error
		return 0;
	
	$fields = array(
		'description' => false,
		'sections'		=> false,
		'tested'			=> false,
		'requires'		=> false,
		'rating'			=> false,
		'downloaded'	=> false,
		'downloadlink'=> false,
		'last_updated'=> false,
		'homepage'		=> false,
		'tags'				=> false,
		'name'				=> false
	);
	// user failed
	if( $slug == NULL || !isset( $fields[$field] ) )
		return '[Invalid User Input]';

	$fields = array( 'field' => $field );
	if( $ret = di_information( $slug, $fields ) ){
		return DI_COMMENT.$ret->{$field}; // success
	}

	// API failed
	return '[An Error Occured]';
}
add_shortcode( 'dinfo', 'di_do_shortcode' );
