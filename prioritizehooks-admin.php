<?php

//Set up admin page & menu
add_action( 'admin_menu', array( 'PrioritizeHooksAdmin', 'admin_menu' ) );
add_action( 'admin_init', array( 'PrioritizeHooksAdmin', 'admin_init' ) );
add_action( 'admin_enqueue_scripts', array('PrioritizeHooksAdmin', 'admin_enqueue_scripts') );

class PrioritizeHooksAdmin {
	const PAGE_NAME = 'prioritize-hooks';
	const OPTION_GROUP = 'priorities';

	//Add a submenu link to Prioritize options page on the Settings menu.
	static function admin_menu() {
		add_management_page(
			'Prioritize Hooks', //page title
			'Prioritize Hooks', //menu heading
			'manage_options', //credentials
			'prioritize-hooks', //menu slug
			array( 'PrioritizeHooksAdminView', 'displayHooks' ) //display callback
		);
	}

	static function admin_init() {
		$overrides = PrioritizeHooks::getOverridenCallbacks();
		$defaults = PrioritizeHooks::getDefaultPriorities();
		$disabledhooks = PrioritizeHooks::disabledHooks();

		//Register hooks option and add a section for each installable
		self::registerOption( PrioritizeHooks::slug );
		foreach( PrioritizeHooks::getInstalledCallbacksByPackage() as $package => $callbacks ) {
			$_disabled = isset( $disabledhooks[ $package ] ) ? $disabledhooks[ $package ] : array();
			self::addPackage( $package );
			self::addPackageHooks( $package, array_merge( $callbacks, $_disabled ), $overrides, $defaults );
		}
	}

	static function validateField( $input ) {
		foreach( (array)$input[ 'priorities' ] as $tag => $fnames )
			foreach( (array)$fnames as $fname => $override )
				if( is_numeric( $override ) )
					$input[ 'priorities' ][ $tag ][ $fname ] = (int)$override;
				elseif( PrioritizeHooks::disabled_hook_flag == $override );
				else
					unset( $input[ 'priorities' ][ $tag ][ $fname ] );

		return $input;
	}

	static function admin_enqueue_scripts() {
		wp_enqueue_script( 'prioritize-hooks-admin-script', plugins_url('/resources/admin-scripts.js', __FILE__), array( 'jquery-ui-accordion' ) );
		wp_enqueue_style( 'prioritize-hooks-admin-styles', plugins_url('/resources/admin-styles.css', __FILE__) );
		wp_enqueue_style( 'jquery-ui', 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css' );
	}

	private static function registerOption( $option ) {
		register_setting( self::OPTION_GROUP, //option group
			$option, //option name
			array( __CLASS__, 'validateField' ) //sanitation callback
		);
	}

	private static function addPackage( $package ) {
		add_settings_section(
			PrioritizeHooks::slug . "-{$package}", //id of section
			$package, //title of section
			array( 'PrioritizeHooksAdminView', 'displaySection' ), //display callback
			self::PAGE_NAME //page to display section
		);
	}

	private static function addPackageHooks( $package, $hooks, $overrides, $defaults ) {
		foreach( $hooks as $index => $callback ) {
			extract( $callback, EXTR_PREFIX_ALL, 'cb' );

			add_settings_field(
				PrioritizeHooks::slug . "-priority-{$index}", //id of field
				"<strong>$cb_tag</strong><p>{$cb_fname}</p>", //title of field
				array( 'PrioritizeHooksAdminView', 'displayOptionField' ), //callback for populating field
				self::PAGE_NAME, //page to display field
				PrioritizeHooks::slug . "-{$package}", //section to display field
				array( //args to pass to callback
					'callback' => $callback,
					'override' => isset( $overrides[ $cb_tag ][ $cb_fname ] ) ? $overrides[ $cb_tag ][ $cb_fname ] : false,
					'default' => isset( $defaults[ $cb_tag ][ $cb_fname ] ) ? $defaults[ $cb_tag ][ $cb_fname ] : false,
					'index' => $index
				)
			);
		}
	}

}

class PrioritizeHooksAdminView {
	//Display Prioritize Hooks configuration.
	static function displayHooks() {
		current_user_can('manage_options') or wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

		echo '<div id="prioritize-hooks" class="wrap">';
		echo '<h2>Prioritize Hooks</h2>';
		echo '<div class="updated fade below-h2"><p class="summary">Override priorities as desired. Use hyphen(-) as the priority to disable a hook. Leave a priority blank to reset it.</p></div>';
		echo '<form method="post" action="options.php">';
		settings_fields( PrioritizeHooksAdmin::OPTION_GROUP );
		echo '<div id="prioritize-hooks-form-fields">';
		do_settings_sections( PrioritizeHooksAdmin::PAGE_NAME );
		echo '</div>';
		echo '<p class="submit">';
		echo '<input type="submit" name="submit" class="button-primary" value="' . __( 'Save Changes' ) . '" />';
		echo '</p>';
		echo '</form>';
		echo '</div>';
	}

	//Displays this after section heading.
	static function displaySection( $section ) {
	}

	//Renders the setting fields.
	static function displayOptionField( $args ) {
		extract( $args[ 'callback' ], EXTR_PREFIX_ALL, 'cb' );

		$fieldname = PrioritizeHooks::slug . "[priorities][$cb_tag][{$cb_fname}]";
		$id = PrioritizeHooks::slug . "-priority-{$args[ 'index' ]}";
		false !== $value = $args[ 'override' ] or $value = false;
		false !== $default = $args[ 'default' ] or $default = $cb_priority;

			if( PrioritizeHooks::disabled_hook_flag == $value )
				$class = 'disabled prioritized';
			elseif( false !== $value )
				$class = 'prioritized';
			else
				$class = '';

		printf(
			'<input id="%d" class="%s" name="%s" type="text" size="3" value="%s"/> <span class="default-value">%s: %d</span>',
			$id, $class, $fieldname, $value, __( 'default priority', 'prioritize-hooks' ), $default
		);
	}

}

?>