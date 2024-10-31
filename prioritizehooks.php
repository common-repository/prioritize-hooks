<?php
/*
Plugin Name: Prioritize Hooks
Plugin URI: http://portfolio.planetjon.ca/projects/prioritize-hooks/
Description: Prioritize Hooks allows for overriding the priority of various filters and actions hooked by plugins and themes.
Version: 1.2
Author: Jonathan Weatherhead
Author URI: http://jonathanweatherhead.com
License: GPL2
Text Domain: prioritize-hooks
Domain Path: /languages
*/

if( is_admin() )
	include_once( plugin_dir_path( __file__ ) . 'prioritizehooks-admin.php' );

add_action( 'plugins_loaded', array( 'PrioritizeHooks', 'plugins_loaded' ) );
add_action( 'wp_loaded', array( 'PrioritizeHooks', 'overrideCallbackPriorities' ) );

class PrioritizeHooks {
	const slug = 'PRIORITIZE_HOOKS';
	const override_core = false;
	const disabled_hook_flag = '-';

	private static $instance;

	private $pluginroot;
	private $themeroot;
	private $options;
	private $defaultpriorities;
	private $disabledhooks;

	static function plugins_loaded() {
		//standard plugin hooks
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

		//load translations
		load_plugin_textdomain( 'prioritize-hooks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	static function activate() {
		add_option( self::slug, array( 'priorities' => array() ) );
	}

	static function deactivate() {}

	static function uninstall() {
		delete_option(self::slug);
	}

	function __construct() {
		$this->pluginroot = strtr( dirname( plugin_dir_path( __FILE__ ) ), '\\', '/');
		$this->themeroot = strtr(get_theme_root(), '\\', '/');
		$this->options = get_option( self::slug, array( 'priorities' => array( 'priorities' => array() ) ) );
		$this->defaultpriorities = $this->disabledhooks = array();
	}

	static function overrideCallbackPriorities() {
		$overrides = self::getOverridenCallbacks();
		$installedcallbacks = self::readHookCallbacks( self::override_core );

		foreach( $installedcallbacks as $callback ) {
			extract( $callback, EXTR_PREFIX_ALL, 'cb' );
			if( isset( $overrides[ $cb_tag ][ $cb_fname ] ) ) {
				self::getInstance()->defaultpriorities[ $cb_tag ][ $cb_fname ] = $cb_priority;
				remove_action( $cb_tag, $cb_callable, $cb_priority, $cb_fargs );

				if( self::disabled_hook_flag == $overrides[ $cb_tag ][ $cb_fname ] )
					self::getInstance()->disabledhooks[ self::nameOfInstallable( $cb_file ) ][] = $callback;
				else
					add_action( $cb_tag, $cb_callable, $overrides[ $cb_tag ][ $cb_fname ], $cb_fargs );
			}
		}
	}

	static function getOverridenCallbacks() {
		return apply_filters( 'prioritize_hooks_priorities', self::getInstance()->options[ 'priorities' ] );
	}

	static function getDefaultPriorities() {
		return self::getInstance()->defaultpriorities;
	}

	static function disabledHooks() {
		return self::getInstance()->disabledhooks;
	}

	static function getInstalledCallbacksByPackage() {
		$callbacks = array();

		foreach( self::readHookCallbacks() as $callback )
			$callbacks[ self::nameOfInstallable( $callback[ 'file' ] ) ][] = $callback;

		ksort( $callbacks );
		return apply_filters( 'prioritize_hooks_admin_filter_callbacks', $callbacks );
	}

	static function readHookCallbacks( $includecore = self::override_core ) {
		global $wp_filter;
		$instance = self::getInstance();
		$callbacks = array();

		foreach( $wp_filter as $tag => $priorities )
			foreach( $priorities as $priority => $fhashes )
				foreach( $fhashes as $fhash => $callable)
					if( ! $reflection = self::reflectFunction( $callable[ 'function' ] ) )
						continue;
					elseif( $includecore || strpos( $reflection[ 'file' ], $instance->pluginroot ) !== false || strpos( $reflection[ 'file' ], $instance->themeroot ) !== false )
						$callbacks[] = array(
							'tag' => $tag,
							'priority' => $priority,
							'fhash' => $fhash,
							'fname' => $reflection[ 'name' ],
							'fargs' => $callable[ 'accepted_args' ],
							'callable' => $callable[ 'function' ],
							'file' => $reflection[ 'file' ]
						);

		return apply_filters( 'prioritize_hooks_callbacks', $callbacks );
	}

	private static function getInstance() {
		if( ! self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	private static function nameOfInstallable( $path ) {
		$instance = self::getInstance();
		if( preg_match("#^({$instance->pluginroot}|{$instance->themeroot})/([^/]+)#", $path, $matches) )
			return $matches[ 2 ];
		else
			return false;
	}

	private static function reflectFunction( $callable ) {
		$reflection = array();

		try {
			if( is_array( $callable ) or is_string( $callable ) && strpos( $callable, '::' ) !== false && $callable = explode( '::', $callable ) )
				$reflector = new ReflectionMethod( $callable[ 0 ], $callable[ 1 ] );
			else
				$reflector = new ReflectionFunction( $callable );

			$reflection = array_merge( $reflection, array(
				'name' => ( $reflector instanceof ReflectionMethod ? "{$reflector->class}::" : '' )
					. $reflector->getName(),
				'file' => strtr( $reflector->getFileName(), '\\', '/' )
			));
		}
		catch( Exception $e ) {
		}

		return $reflection;
	}

}

?>