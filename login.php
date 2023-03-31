<?php
/**
 * @package RedRokk
 * @version 0.01
 * 
 * Plugin Name: WordPress Login :: Red Rokk Widget Collection
 * Description: Place the fully functional WordPress login box anywhere on your website using a widget, or a shortcode.
 * Author: RedRokk Interactive Media
 * Version: 0.01
 * Author URI: http://redrokk.com/2012/07/05/wordpress-login-red-rokk-widget-collection/
 */

/**
 * Protection 
 * 
 * This string of code will prevent hacks from accessing the file directly.
 */
defined('ABSPATH') or die("Cannot access pages directly.");

/**
 * Initializing 
 * 
 * The directory separator is different between linux and microsoft servers.
 * Thankfully php sets the DIRECTORY_SEPARATOR constant so that we know what
 * to use.
 */
defined("DS") or define("DS", DIRECTORY_SEPARATOR);

/**
 * Loading the widget class
 * 
 */
require_once dirname(__file__).DS.'lib'.DS.'metabox.class.php';
require_once dirname(__file__).DS.'lib'.DS.'admin.class.php';
require_once dirname(__file__).DS.'lib'.DS.'login.class.php';
require_once dirname(__file__).DS.'lib'.DS.'widget.class.php';
require_once dirname(__file__).DS.'lib'.DS.'facebook.sdk.php';
require_once dirname(__file__).DS.'lib'.DS.'facebook.class.php';

require_once dirname(__file__).DS.'widgets.php';

// hooks
register_nav_menu( 'redrokk-login-widget', 'Built In User Menu' );

add_filter( 'get_avatar', 'facebook_get_avatar', 20, 3 );
add_filter( 'wp_nav_menu_items', 'red_activate_menu_register', 10, 2 );
add_filter( 'wp_page_menu', 'red_activate_pages_register', 10, 2 );
add_filter( 'register', 'red_change_register', 100, 1 );
add_filter( 'login_url', 'red_change_register_url', 100, 2 );
add_filter( 'site_url', 'red_change_register_site_url', 100, 2 );

add_filter( 'wp_nav_menu_items', 'red_activate_menu_profile', 10, 2 );
add_filter( 'wp_page_menu', 'red_activate_pages_profile', 10, 2 );
add_filter( 'admin_url', 'red_change_profile_url', 100, 1 );

add_filter( 'wp_nav_menu_items', 'red_activate_menu_login', 10, 2 );
add_filter( 'wp_page_menu', 'red_activate_pages_login', 10, 2 );
add_filter( 'login_url', 'red_change_login_url', 100, 2 );
add_filter( 'login', 'red_change_login_url', 2000 );

add_action( 'init', 'red_login_popup_script' );
add_action( 'wp_footer', 'red_footer' );
add_action( 'wp_ajax_nopriv_registration', 'red_login_registration');
add_action( 'wp_ajax_nopriv_lostpassword_ajax', 'red_lostpassword');
add_action( 'wp_ajax_nopriv_login_ajax', 'red_login_ajax');

add_filter( 'wp_nav_menu_items', 'red_activate_menu_logout', 10, 2 );
add_filter( 'wp_page_menu', 'red_activate_pages_logout', 10, 2 );
add_filter( 'logout_url', 'red_change_logout_url', 100, 2 );

add_filter( 'wp_redirect', 'red_redirect_logout_url', 100, 1 );
add_filter( 'redrokk_login_class::redirect', 'red_redirect_on_login' );

/**
 * Administrative Settings
 * 
 * Creating admin pages, is easy as that. There's a ton of work already completed for you, which will
 * allow you to offer seamless and stable administrative options pages to your users.
 */
$loginadminpage = redrokk_admin_class::getInstance('loginadminpage', array(
	'page_title'	=> 'Login and Registration Settings',
	'menu_title'	=> 'Login/Registration',
	'parent_menu'	=> 'appearance',
	'screen_icon'	=> 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAABGdBTUEAALGPC/xhBQAAAAZiS0dEAP8A/wD/oL2nkwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAAd0SU1FB9wHBRM2Gd/BfkoAAAMhSURBVDjLrZRLb1tVFIW/fX2u48Rx8zSiOFESgWiU0ioDqsgSD5UiUYWH6Kg/g0ErMUBC9IcwADFggMQUJowYJA2iaVQ1DJLYSZoHzcNNnPi+fM5mcG0rrcoksCd3cHS+vdbe61z4n0t++/Mv/0J37m2T8boBPS+naV1wHIR/mChKPs50d/0s6s5PAzIoUZTcMknTzuzsPUMVbZ2dp1QEaYrMGAWJmsp/gIGkdz0jYrJ+hulLo3RlfVT/xU9bhlUQRRVU0y8KipIklkcbuxhByPfkKOR7Xtq8fhISBHEKdOnWUmCriSrGZOjO+YgIBoFGI+bB0g77ByeIpJKcU6bfKvFk5xA/o2SzpnPWUYzirOW0YZkYfwVEMCgYk2FsZIChwXzHoaoyNJinurnHWOkC3bksnuc9D1TFWkv1yRHWOgQwAL7vMTY6+FLLly+VqG4eYm1EOwpn7aoqw4MF+go59G9NgWcDqKoda3v7+zzd3aI4kGd8bBwRQRG0RVSXLsap0mjN2by4yTasmSSsrazQPzBAEITM37/PxVcv0j9QpGnTpKgqTWvpypqOJgPQbDq2do7SbUpqKY5jTgNHdf0BpdIIxWKR9Y11VtY28Uwvx8d1fN9ndHQUZ6FYLLSAAkliWasesF877Yh1Dq5OvUlsDYuL80xNTdHX18/y8mMWFx8ShhHWNhkeHubq9HsMF2dasVHI5XzK18ZxVjtzBOjqMqxVn1IYusKPP/1KXz7h8LCGqhLHMWEYUqlUmJubo7I2y1T5empZnZLx5Mwo0/kkTcuN9yeBSW7fKnPv3jfEcYyqUqvV6O0tMDv7KQsL83z/3bdcXlrCAzxPQETU84R2dkUEEQ/nFOcczjpGSiNkfZ8oihAR7t79koODPSqVVa3X6+xub3tGVTd2ayc8a0SimiZI9EyS2hEBrr3zAdt7h9SXH+Gc486dLzSOYzltBHLjo0949+ZnG8Y5/WH74Ohx6xXoC2/rucpme3h98kqyMPf7zSgMv3LOycnJyXEQRl9/OPv5w9cm3lg81y+rXC57YRhOBEFwPUmSX1ZXV7fa7f8BAqmdfON9nh8AAAAASUVORK5CYII='
));

/**
 * STANDARD OPTIONS
 * 
 *//*
redrokk_metabox_class::getInstance('generalsettings', array(
	'title'			=> 'General Settings',
	'_object_types'	=> $loginadminpage,
	'priority'		=> 'high',
	'_fields'		=> array(
	)
));*/

/**
 * Registration Menu Item
 * 
 */
redrokk_metabox_class::getInstance('registrationsettings', array(
	'title'			=> 'Registration Settings',
	'_object_types'	=> $loginadminpage,
	'priority'		=> 'high',
	'_fields'		=> array(
		array(
			'name' 	=> 'Registration Button',
			'id' 	=> 'red_register_button',
			'type' 	=> 'checkbox',
			'options'	=> array(
				'true'	=> 'Activate this menu item',
			),
			'desc' 	=> '',
		),
		array(
			'name' 	=> 'Button Text',
			'id' 	=> 'red_register_text',
			'type' 	=> 'text',
			'desc' 	=> 'What would you like the menu item to display?',
			'default' => 'Register'
		),
		array(
			'name' 	=> 'Menu',
			'id' 	=> 'red_register_menu',
			'type' 	=> 'select_menu',
			'desc' 	=> 'If the button is active, which menu would you like the button added to?',
		),
		array(
			'name' 	=> 'Menu Position',
			'id' 	=> 'red_register_position',
			'type' 	=> 'radio',
			'options'	=> array(
				'true'	=> 'Show at the beginning',
				'false'	=> 'Show at the end',
			),
			'default' 	=> 'false',
		),
		array(
			'type'	=> 'custom',
			'desc'	=> '<hr style="border:0px;border-top:1px solid #ccc;"/>'
		),
		array(
			'name' 	=> 'Registration Page',
			'id' 	=> 'red_register_page',
			'type' 	=> 'select_pages',
			'desc'	=> 'Point all registration urls to this page'
		),
	)
));

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_menu_register($items, $args)
{
	$locations = get_nav_menu_locations();
	$menu_slug = ($args->menu
		? $args->menu
		: (isset($locations[ $args->theme_location ])
			?  wp_get_nav_menu_object( $locations[ $args->theme_location ] )->slug
			: ''
		)
	);
	
	if (!get_option('red_register_button', false)) return $items;
	if (get_option('red_register_menu', false) !== $menu_slug) return $items;
	if (is_user_logged_in()) return $items;
	
	// adding the loginout link
	$link = '<li id="menu-item-register" class="menu-item menu-item-register">'.
		'<a class="page-item-'.sanitize_title($text).'" href="'.red_login('register').'">'.
		get_option('red_register_text', 'Register').'</a></li>';
	
	$items = (get_option('red_register_position','false')=='true'?true:false)
		? $link.$items
		: $items.$link;
	
	return $items; 
}

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_pages_register($ul, $args)
{
	if (!get_option('red_register_button', false)) return $ul;
	if (is_user_logged_in()) return $ul;
	
	if (class_exists('DOMDocument'))
	{
		$ul = red_domdocument( 
			$ul, 
			red_login('register'), 
			get_option('red_register_text', 'Register'), 
			(get_option('red_register_position','false')=='true'?true:false) 
		);
	} else {
		throw Exception('DOM Document is not installed');
	}
	
	return $ul; 
}

/**
 * 
 * @param unknown_type $url
 */
function red_change_register( $url )
{
	return red_change_url($url);
}

/**
 * 
 * @param unknown_type $url
 * @param unknown_type $redirect
 */
function red_change_register_url( $url, $redirect = false )
{
	if (strpos($url, 'action=register')===false) return $url;
	return red_change_url($url);
}

/**
 * 
 * @param unknown_type $login_url
 * @param unknown_type $path
 */
function red_change_register_site_url( $url, $path )
{
	if (strpos($path, 'action=register')===false) return $url;
	return red_change_url($url);
}

/**
 * Profile Menu Item
 * 
 */
redrokk_metabox_class::getInstance('profilesettings', array(
	'title'			=> 'Profile Settings',
	'_object_types'	=> $loginadminpage,
	'priority'		=> 'high',
	'_fields'		=> array(
		array(
			'name' 	=> 'Profile Button',
			'id' 	=> 'red_profile_button',
			'type' 	=> 'checkbox',
			'options'	=> array(
				'true'	=> 'Activate this menu item',
			),
			'desc' 	=> '',
		),
		array(
			'name' 	=> 'Button Text',
			'id' 	=> 'red_profile_text',
			'type' 	=> 'text',
			'desc' 	=> 'What would you like the menu item to display?',
			'default' => 'Profile'
		),
		array(
			'name' 	=> 'Menu',
			'id' 	=> 'red_profile_menu',
			'type' 	=> 'select_menu',
			'desc' 	=> 'If the button is active, which menu would you like the button added to?',
		),
		array(
			'name' 	=> 'Menu Position',
			'id' 	=> 'red_profile_position',
			'type' 	=> 'radio',
			'options'	=> array(
				'true'	=> 'Show at the beginning',
				'false'	=> 'Show at the end',
			),
			'default' 	=> 'false',
		),
		array(
			'type'	=> 'custom',
			'desc'	=> '<hr style="border:0px;border-top:1px solid #ccc;"/>'
		),
		array(
			'name' 	=> 'Profile Page',
			'id' 	=> 'red_profile_page',
			'type' 	=> 'select_pages',
			'desc'	=> 'Point all profile urls to this page'
		),
	)
));

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_menu_profile($items, $args)
{
	$locations = get_nav_menu_locations();
	$menu_slug = ($args->menu
		? $args->menu
		: (isset($locations[ $args->theme_location ])
			?  wp_get_nav_menu_object( $locations[ $args->theme_location ] )->slug
			: ''
		)
	);
	
	if (!get_option('red_profile_button', false)) return $items;
	if (get_option('red_profile_menu', false) !== $menu_slug) return $items;
	if (!is_user_logged_in()) return $items;
	
	// adding the loginout link
	$link = '<li id="menu-item-profile" class="menu-item menu-item-profile">'.
		'<a class="page-item-profile" href="'.admin_url( 'profile.php' ).'">'.
		get_option('red_profile_text', 'Profile').'</a></li>';
	
	$items = (get_option('red_profile_position','false')=='true'?true:false)
		? $link.$items
		: $items.$link;
	
	return $items; 
}

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_pages_profile($ul, $args)
{
	if (!get_option('red_profile_button', false)) return $ul;
	if (!is_user_logged_in()) return $ul;
	
	if (class_exists('DOMDocument'))
	{
		$ul = red_domdocument( 
			$ul, 
			admin_url( 'profile.php' ), 
			get_option('red_profile_text', 'Profile'), 
			(get_option('red_profile_position','false')=='true'?true:false) 
		);
	} else {
		throw Exception('DOM Document is not installed');
	}
	
	return $ul; 
}

/**
 * 
 * @param unknown_type $url
 */
function red_change_profile_url( $url )
{
	if (strpos($url, 'profile.php')===false) return $url;
	return remove_query_arg('redirect', red_change_url($url, false, 'profile'));
}

/**
 * Login Menu Item
 * 
 * 
 */
redrokk_metabox_class::getInstance('loginsettings', array(
	'title'			=> 'Login Settings',
	'_object_types'	=> $loginadminpage,
	'priority'		=> 'high',
	'_fields'		=> array(
		array(
			'name' 	=> 'Login Button',
			'id' 	=> 'red_login_button',
			'type' 	=> 'checkbox',
			'options'	=> array(
				'true'	=> 'Activate this menu item',
			),
			'desc' 	=> '',
		),
		array(
			'name' 	=> 'Button Text',
			'id' 	=> 'red_login_text',
			'type' 	=> 'text',
			'desc' 	=> 'What would you like the menu item to display when users are logged out?',
			'default' => 'Login'
		),
		array(
			'name' 	=> 'Menu',
			'id' 	=> 'red_login_menu',
			'type' 	=> 'select_menu',
			'desc' 	=> 'If the button is active, which menu would you like the button added to?',
		),
		array(
			'name' 	=> 'Menu Position',
			'id' 	=> 'red_login_position',
			'type' 	=> 'radio',
			'options'	=> array(
				'true'	=> 'Show at the beginning',
				'false'	=> 'Show at the end',
			),
			'default' 	=> 'false',
		),
		array(
			'type'	=> 'custom',
			'desc'	=> '<hr style="border:0px;border-top:1px solid #ccc;"/>'
		),
		array(
			'name' 	=> 'Login Page',
			'id' 	=> 'red_login_page',
			'type' 	=> 'select_pages',
			'desc'	=> 'Point all login urls to this page'
		),
		array(
			'name' 	=> 'Activate Popup',
			'id' 	=> 'red_login_popup',
			'type' 	=> 'checkbox',
			'options'	=> array(
				'true'	=> 'Yes, this menu button should pop open a login area',
			),
		),
		array(
			'name' 	=> 'Popup Template',
			'id' 	=> 'red_popup_template',
			'type' 	=> 'select',
			'options'	=> array(
				'red_simple_popup'	=> 'Simple White',
				'red_white_popup'	=> 'White Clean',
			),
			'default' 	=> 'red_simple_popup',
		),
		array(
			'name' 	=> 'Redirect on Login',
			'id' 	=> 'red_redirect_login_page',
			'type' 	=> 'select_pages',
			'options' => array(
				'_other_' => ' Custom Url'
			),
		),
		array(
			'name' 	=> 'Custom Url',
			'id' 	=> 'red_redirect_login_other',
			'type' 	=> 'text',
		),
	)
));

/**
 * 
 * @param unknown_type $url
 * @return unknown
 */
function get_login_redirect_url( $url = false )
{
	$page_id = get_option('red_redirect_login_page', '0');
	if ($page_id == '0') return $url;
	
	if ($page_id == '_other_') {
		$permalink = get_option('red_redirect_login_other', false);
	} else {
		$permalink = get_permalink($page_id);
	}
	return $permalink;
}

/**
 * 
 * @param unknown_type $url
 */
function red_redirect_on_login( $url )
{
	return get_login_redirect_url($url);
}

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_menu_login($items, $args)
{
	$locations = get_nav_menu_locations();
	$menu_slug = ($args->menu
		? $args->menu
		: (isset($locations[ $args->theme_location ])
			?  wp_get_nav_menu_object( $locations[ $args->theme_location ] )->slug
			: ''
		)
	);
	
	if (!get_option('red_login_button', false)) return $items;
	if (get_option('red_login_menu', false) !== $menu_slug) return $items;
	if (is_user_logged_in()) return $items;
	
	// creating the loginout link
	$href = apply_filters('login_url', red_login());
	if (get_option('red_login_popup', false))
	{
		$href = '#';
	}
	
	$text = get_option('red_login_text', 'Login');
	$link = '<li id="menu-item'.sanitize_title($text).'" class="menu-item menu-item'.sanitize_title($text).'">'.
		'<a class="page-item-login page-item-'.sanitize_title($text).'" href="'.$href.'">'.$text.'</a></li>';
	
	$items = (get_option('red_login_position','false')=='true'?true:false)
		? $link.$items
		: $items.$link;
	
	return $items; 
}

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_pages_login($ul, $args)
{
	if (!get_option('red_login_button', false)) return $ul;
	
	if (class_exists('DOMDocument'))
	{
		$href = apply_filters('login_url', red_login());
		if (get_option('red_login_popup', false))
		{
			$href = '#';
		}
		$ul = red_domdocument( 
			$ul, 
			$href, 
			get_option('red_logout_text', 'Login'), 
			(get_option('red_login_position','false')=='true'?true:false) 
		);
	}
	else
	{
		throw Exception('DOM Document is not installed');
	}
	
	return $ul; 
}

/**
 * 
 * @param unknown_type $url
 * @param unknown_type $redirect
 */
function red_change_login_url( $url, $redirect = false )
{
	$specifically = strpos($url, 'action=login') !== false;
	$not = strpos($url, 'action=') === false;
	$noother = strpos($url, 'key=') === false && strpos($url, 'resetpass') === false;
	
	return $specifically || $not || $noother
		? red_change_url($url, $redirect, 'login')
		: $url;
}

/**
 * Functions necessary to load the login popup dialog
 * 
 * 
 */
function red_login_popup_script()
{
	if (is_admin()) return;
	if (!get_option('red_login_popup', false)) return;
	
	wp_enqueue_script( 
		'red_login_popup',
		plugin_dir_url( __file__ ).'js/popup.js',
		array('jquery-ui-dialog'), 
		'0.1', 
		true 
	);
	
	wp_enqueue_style( 
		'red_login_style',
		plugin_dir_url( __file__ ).'css/style.css',
		array(), 
		'0.1'
	);
}
function red_footer()
{
	if (is_admin()) return;
	if (!get_option('red_login_popup', false)) return;
	
	// load a custom override form the template
	if ($custom = locate_template(array('red_login_popup.php'))) {
		return load_template($custom);
	}
	
	$template = get_option('red_popup_template', 'red_simple_popup');
	$html = load_template( dirname(__file__).DS.'views'.DS."$template.php" );
	return $html;
}

/**
 * Logout Menu Item
 * 
 * 
 */
redrokk_metabox_class::getInstance('logoutsettings', array(
	'title'			=> 'Logout Settings',
	'_object_types'	=> $loginadminpage,
	'priority'		=> 'high',
	'_fields'		=> array(
		array(
			'name' 	=> 'Logout Button',
			'id' 	=> 'red_logout_button',
			'type' 	=> 'checkbox',
			'options'	=> array(
				'true'	=> 'Activate this menu item',
			),
			'desc' 	=> '',
		),
		array(
			'name' 	=> 'Button Text',
			'id' 	=> 'red_logout_text',
			'type' 	=> 'text',
			'desc' 	=> 'What would you like the menu item to display when users are logged in?',
			'default' => 'Log out'
		),
		array(
			'name' 	=> 'Menu',
			'id' 	=> 'red_logout_menu',
			'type' 	=> 'select_menu',
			'desc' 	=> 'If the button is active, which menu would you like the button added to?',
		),
		array(
			'name' 	=> 'Menu Position',
			'id' 	=> 'red_logout_position',
			'type' 	=> 'radio',
			'options'	=> array(
				'true'	=> 'Show at the beginning',
				'false'	=> 'Show at the end',
			),
			'default' 	=> 'false',
		),
		array(
			'type'	=> 'custom',
			'desc'	=> '<hr style="border:0px;border-top:1px solid #ccc;"/>'
		),
		array(
			'name' 	=> 'Redirect on Logout',
			'id' 	=> 'red_redirect_logout_page',
			'type' 	=> 'select_pages',
			'options' => array(
				'_other_' => ' Custom Url'
			),
		),
		array(
			'name' 	=> 'Custom Url',
			'id' 	=> 'red_redirect_logout_other',
			'type' 	=> 'text',
		),
	)
));

/**
 * 
 * @param unknown_type $url
 * @return unknown
 */
function get_logout_redirect_url( $url = false )
{
	$page_id = get_option('red_redirect_logout_page', '0');
	if ($page_id == '0') return $url;
	
	if ($page_id == '_other_') {
		$permalink = get_option('red_redirect_logout_other', false);
	} else {
		$permalink = get_permalink($page_id);
	}
	return $permalink;
}

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_menu_logout($items, $args)
{
	$locations = get_nav_menu_locations();
	$menu_slug = isset($args->theme_location) && isset($locations[$args->theme_location])
		? wp_get_nav_menu_object( $locations[ $args->theme_location ] )->slug
		: $args->menu;
	
	if (!get_option('red_logout_button', false)) return $items;
	if (get_option('red_logout_menu', false) !== $menu_slug) return $items;
	if (!is_user_logged_in()) return $items;
	
	// creating the loginout link
	$text = get_option('red_logout_text', 'Log out');
	$link = '<li id="menu-item'.sanitize_title($text).'" class="menu-item menu-item'.sanitize_title($text).'">'.
		'<a class="page-item-'.sanitize_title($text).'" href="'.apply_filters('logout_url', red_login('logout')).'">'.
		$text.'</a></li>';
	
	$items = (get_option('red_logout_position','false') =='true' ?true :false)
		? $link.$items
		: $items.$link;
	
	return $items; 
}

/**
 * 
 * @param unknown_type $items
 * @param unknown_type $args
 * @return string
 */
function red_activate_pages_logout($ul, $args)
{
	if (!get_option('red_login_button', false)) return $ul;
	if (!is_user_logged_in()) return $ul;
	
	if (class_exists('DOMDocument'))
	{
		$ul = red_domdocument( 
			$ul, 
			red_login('logout'), 
			get_option('red_login_text', 'Log out'), 
			(get_option('red_login_position','false')=='true'?true:false) 
		);
	}
	else
	{
		throw Exception('DOM Document is not installed');
	}
	
	return $ul; 
}

/**
 * 
 * @param unknown_type $url
 * @param unknown_type $redirect
 */
function red_change_logout_url( $url, $redirect = false )
{
	return (strpos($url, 'action=logout') !== false)
		? add_query_arg('action', 'logout', red_change_url($url, $redirect, 'login'))
		: $url;
}

/**
 * 
 * @param string $url
 */
function red_redirect_logout_url( $url )
{
	// logout has finished, we're redirecting
	if (strpos($url, 'action=logoutconfirm') !== false) {
		$default = add_query_arg('action', 'logoutconfirm', red_change_url($url, false, 'login'));
		return get_logout_redirect_url( $default );
	}
	
	return (strpos($url, 'action=logout') !== false
		? add_query_arg('action', 'logout', red_change_url($url, false, 'login'))
		: $url);
}

/**
 * 
 * @param unknown_type $ul
 * @param unknown_type $li
 * @param unknown_type $position
 */
function red_domdocument( $ul, $url, $text, $last = true )
{
	if (!class_exists('DOMDocument')) return $ul;
	
	$doc = new DOMDocument();
	$doc->loadHTML($ul);
	
	// Navigate to the ul
	$ul = $doc->getElementsByTagName('ul')->item(0);
	
	// 0 for the first li, etc..
	$node = $ul->childNodes->item( ($last ?$ul->childNodes->length :0));
	
	// Create the li
	$li = $doc->createElement('li');
	$li->setAttribute('class', 'page_item page-item-'.sanitize_title($text));
	
	// Create the link (products for the example)
	$a = $doc->createElement('a', $text);
	$a->setAttribute('href', $url);
	$a->setAttribute('class', 'link-item-'.sanitize_title($text));
	$a->setAttribute('title', $text);
	
	// Add it to the li
	$li->appendChild($a);
	
	// Add to the list before the specified item
	// To add at the end of the list, use a non-existent node ie: $ul->childNodes->item(100)
	$ul->insertBefore($li, $node);
	
	// Display
	return $doc->saveHTML();  
}

/**
 * 
 * @param unknown_type $action
 */
function red_login( $action = 'login' )
{
	$login_url = add_query_arg('redirect', red_get_redirect(), site_url('wp-login.php', 'login'));
	return add_query_arg('action', $action, $login_url);
}

/**
 * 
 */
function red_get_redirect()
{
	$url = get_queried_object_id()
		? get_permalink(get_queried_object_id())
		: get_bloginfo('url');
	
	return urlencode($url);
}

/**
 * 
 * @param unknown_type $url
 * @param unknown_type $redirect
 * @param unknown_type $action
 */
function red_change_url( $url, $redirect = false, $action = 'register' )
{
	$page_id = get_option("red_{$action}_page", '0');
	if ($page_id == '0') return $url;
	
	if (!$redirect) $redirect = red_get_redirect();
	
	$permalink = get_permalink($page_id);
	if (urldecode($redirect) !== $permalink) {
		$permalink = add_query_arg('redirect', urlencode($redirect), $permalink);
	}
	return $permalink;
}

/**
 * Method accepts the ajax submission, sends the user a lost password email and 
 * returns the appropriate information to the form.
 * 
 */
function red_lostpassword()
{
	// initializing
	$login = redrokk_login_class::getInstance();
	$errors = $login->retrieve_password();
	
	if (!is_wp_error( $errors )) {
		$results = array('action' => 'success');
		
	} elseif ($errors->get_error_messages()) {
		$msgs = $errors->get_error_messages();
		if (is_array($msgs)) {
			$msgs = implode(' ',$msgs);
		}
		
		$results = array('error' => $msgs);
	}
	
	echo json_encode($results);
	die();
}

/**
 * Method logs in user
 * 
 */
function red_login_ajax()
{
	// initializing
	$login = redrokk_login_class::getInstance();
	$errors = $login->login($_POST['log'], $_POST['pwd']);
	
	if (!is_wp_error( $errors )) {
		$page_id = get_option("red_login_page", '0');
		
		ob_start();
		$redirect = apply_filters('onJavascriptLogin', get_permalink($page_id));
		$javascript = ob_get_clean();
		
		$results = array(
			'action' 		=> 'success',
			'redirect'		=> $redirect,
			'javascript' 	=> $javascript
		);
		
	} elseif ($errors->get_error_messages()) {
		$msgs = $errors->get_error_messages();
		if (is_array($msgs)) {
			$msgs = implode(' ',$msgs);
		}
		
		$results = array('error' => $msgs);
	}
	
	echo json_encode($results);
	die();
}

/**
 * Method accepts the ajax submission, registers the user then returns the appropriate
 * user information
 * 
 */
function red_login_registration()
{
	$login = redrokk_login_class::getInstance();
	
	$user_login = $_REQUEST['user_login'];
	$user_email = $_REQUEST['user_email'];
	$errors = $login->register_new_user($user_login, $user_email);
	
	if ( !is_wp_error($errors) ) {
		$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : add_query_arg('checkemail', 'registered', $login->getLoginUrl('lostpassword&error=invalidkey'));
		$results = array('redirect'=>$redirect_to, 'userid'=>$errors);
	}
	elseif ($errors->get_error_messages()) {
		$msgs = $errors->get_error_messages();
		if (is_array($msgs)) {
			$msgs = implode(' ',$msgs);
		}
		
		$results = array('error' => $msgs);
	}
	
	echo json_encode($results);
	die();
}

/**
 * Function grabs the users facebook avatar and saves it to their profile
 *
 * @param string $avatar
 * @param int|string $id_or_email
 * @param int|string $size
 * @return string
 */
function facebook_get_avatar($avatar, $id_or_email, $size = '35')
{

	if ( is_numeric($id_or_email) ) {
		$id = (int) $id_or_email;

	} elseif ( is_object($id_or_email) && isset($id_or_email->ID) ) {
		$id = (int) $id_or_email->ID;

	} else {
		$user = get_user_by('email', $id_or_email);
		$id = (int) $user->ID;
	}

	$id = absint($id);
	$src = get_user_meta($id, 'redrokk_get_avatar', true);

	if (!$src || strpos($src, 'graph.facebook')!==false)
	{
		$fbuid = get_user_meta($id, 'fbuid', true);
		if ($fbuid)
		{
			$src = get_facebook_avatar($fbuid);
			if ($src) {
				update_user_meta($id, 'redrokk_get_avatar', $src);
			}
		}
	}

	if ($src) {
		return '<img src="'.$src.'" class="avatar avatar-'.$size.' photo" height="'.$size.'px" width="'.$size.'px">';
	}

	return $avatar;
}

/**
 * 
 * @param unknown_type $fbuid
 */
function get_facebook_avatar( $fbuid )
{
	$url = "http://graph.facebook.com/{$fbuid}/picture?type=square";
	$src = get_redirected_url($url);
	
	return $src;
}

/**
 * Function grabs the facebook avatar
 * 
 * @param string $http
 */
function get_redirected_url( $http )
{
	$ch_url = curl_init();
	curl_setopt($ch_url, CURLOPT_URL, $http); //your first url
	curl_setopt($ch_url, CURLOPT_USERAGENT, 'AppleWebKit/530.5 (KHTML, like Gecko) Chrome/2.0.172.39 Safari/530.5');
	curl_setopt($ch_url, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch_url, CURLOPT_HEADER, 1);
	curl_setopt($ch_url, CURLOPT_NOBODY, 1); //we don't need to recieve the body of response
	$headers = curl_exec($ch_url);
	curl_close ($ch_url);
	
	$pattern = '`.*?((http)://[\w#$&+,\/:;=?@.-]+)[^\w#$&+,\/:;=?@.-]*?`i'; //this regexp finds your url
	if (preg_match_all($pattern,$headers,$matches))
		$url = $matches[1][0]; //your second url

	return $url;
}

