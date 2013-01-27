<?php
/*
Plugin Name: WP Developer Info
Plugin URI: http://wordpress.org/extend/plugins/developer-info
Description: This plugin provides easy access to the WordPress.org Plugin & Theme Info APIs.
Author: Dan Rossiter
Version: 0.2
Author URI: http://danrossiter.org
 */

define( 'DI_COMMENT', PHP_EOL.'<!-- Generated Using WP Developer Info: http://wordpress.org/extend/plugins/developer-info -->'.PHP_EOL );
define( 'DI_PLUGIN_INFO', 'http://api.wordpress.org/plugins/info/1.0/' );
define( 'DI_PLUGIN_STATS', 'http://api.wordpress.org/stats/plugin/1.0/' ); // [plugin-slug]?callback=[js func wrapper]
define( 'DI_PLUGIN_DOWNLOADS', 'http://api.wordpress.org/stats/plugin/1.0/downloads.php' ); // ?slug=[plugin-slug]&limit=[num]&callback=[js func wrapper]
//define( 'DI_THEME_INFO', 'http://api.wordpress.org/themes/info/1.0/' );

function di_do_shortcode( $args ){
	extract( shortcode_atts( array(
		'slug' => NULL,
		'field' => NULL
	), $args ) );
	
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

function di_information( $slug, $fields=NULL ){
	$args = array( 'slug' => $slug );
	if( $fields !== NULL && is_array($fields) )
		$args['fields'] = $fields;

	return di_send_request( 'plugin_information', $args );
}

// types:
// 1 => plugins
// 2 => themes
//
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
function di_query( $args, $fields=NULL ){
	if( $fields !== NULL && is_array($fields) && !isset($args['fields']) )
		$args['fields'] = $fields;

	return di_send_request( 'query_plugins', $args );
}

// Returns: array of objects
// - 'name' - tag name
// - 'slug' - tag slug
// - 'count' - number of plugins
function di_hot_tags( $number=100 ){
	return di_send_request( 'hot_tags', $number );
}

function di_send_request( $action, $args ){
	$body = array(
		'action'	=> $action,
		'request'	=> serialize( (object)$args )
	);

	$response = wp_remote_post( DI_PLUGIN_INFO, 
		array( 'body' => $body )
	);

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
