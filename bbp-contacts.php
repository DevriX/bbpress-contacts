<?php
/*
Plugin Name:    bbPress Contacts
Plugin URI:     https://samelh.com/wordpress-plugins/
Description:    bbPress Contacts Tool
Author:         Samuel Elh
Version:        0.1
Author URI:     https://samelh.com
*/

// prevent direct access
defined('ABSPATH') || exit('No direct access allowed' . PHP_EOL);

if ( !defined('BBPC_FILE') ) {
    define( 'BBPC_FILE', __FILE__ );
}

if ( !defined('BBPC_PATH') ) {
    define( 'BBPC_PATH', plugin_dir_path(__FILE__) );
}

if ( !defined('BBPC_INC_PATH') ) {
    define( 'BBPC_INC_PATH', BBPC_PATH . "Includes/" );
}

// require loder class file
require BBPC_INC_PATH . "Core/Loader.php";

/**
  * Class instance $BBPC_Loader
  * this var is accessible in the theme functions
  * can be used to remove actions, filter and much
  * more
  */

$BBPC_Loader = new BBPC\Includes\Core\Loader;

/**
  * Here's a thing, you can choose to load the entire plugin
  * occasionally when you want (e.g for specific users) doing this:
  * 1. define BBPC_CUSTOM_LOAD in your wp-config.php file ( define('BBPC_CUSTOM_LOAD',1); )
  * 2. Now add this code https://gist.github.com/elhardoum/2e9ba2ee4c5d24bf06045dc8c4414388
  * to your child theme's functions file and from there you can load it conditionally
  * 
  * Important: using this method, you'll have to manually process the BBPC\Includes\Core\Activation
  * class, so it is important you load the plugin for the first time without this method..
  * ..
  */

if ( !defined('BBPC_CUSTOM_LOAD') ) {
    $BBPC_Loader->init();
}

if ( is_admin() ) {
    // load admin
    require BBPC_INC_PATH . "Admin/Loader.php";
    $BBPC_Admin_Loader = new BBPC\Includes\Admin\Loader;
}