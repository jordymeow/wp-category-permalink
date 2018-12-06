<?php
/*
Plugin Name: WP Category Permalink
Plugin URI: https://meowapps.com
Description: Allows manual selection of a 'main' category for each post for better permalinks and SEO.
Version: 3.4.0
Author: Jordy Meow
Author URI: https://meowapps.com

Originally developed for two of my websites:
- Jordy Meow (https://offbeatjapan.org)
- Haikyo (https://haikyo.org)
*/

/**
*s
* Post Edit CSS/JS + Update
*
*/

require_once 'wpcp_admin.php';

global $MWCP_Admin;
$MWCP_Admin = new MWCP_Admin();

require_once 'wpcp_post.php';
require_once 'wpcp_ui.php';

// // Setup the UI
add_action( 'admin_enqueue_scripts', array('MWCPUI', 'enqueue_script') );
add_action( 'admin_print_styles-post.php', array('MWCPUI', 'post_css') );
add_action( 'admin_print_styles-post-new.php', array('MWCPUI', 'post_css') );
add_action( 'admin_footer-post.php', array('MWCPUI', 'post_js'));
add_action( 'admin_footer-post-new.php', array('MWCPUI', 'post_js') );
add_filter( 'post_row_actions', array('MWCPUI', 'post_row_actions'), 10, 2 );
add_filter( 'manage_posts_columns' , array('MWCPUI', 'manage_posts_columns') );
add_action( 'manage_posts_custom_column' , array('MWCPUI', 'manage_posts_custom_column'), 10, 2 );

// Handle post data
add_action( 'transition_post_status', array('MWCPPost', 'transition_post_status'), 0, 3 );

// Set the %category% value for permalinks (normal posts)
add_filter( 'post_link_category', array('MWCPPost', 'post_link_category'), 10, 3 );

// Pro only, handle custom post types and their custom taxonomies
add_filter( 'post_type_link', array('MWCPPost', 'post_type_link'), 10, 4 );

// Disable the WPSEO v3.1+ Primary Category feature.
add_filter( 'wpseo_primary_term_taxonomies', '__return_empty_array' );
