<?php

/**
 * Plugin Name:     Mai - Recommendations
 * Plugin URI:      https://maipro.io
 * Description:     Manage and display recommendations on your website.
 * Version:         0.1.0
 *
 * Author:          Mike Hemberger, BizBudding Inc
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Mai_Recommendations_Setup' ) ) :

/**
 * Main Mai_Recommendations_Setup Class.
 *
 * @since 0.1.0
 */
final class Mai_Recommendations_Setup {

	/**
	 * @var    Mai_Recommendations_Setup The one true Mai_Recommendations_Setup
	 * @since  0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_Recommendations_Setup Instance.
	 *
	 * Insures that only one instance of Mai_Recommendations_Setup exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_Recommendations_Setup::setup_constants() Setup the constants needed.
	 * @uses    Mai_Recommendations_Setup::includes() Include the required files.
	 * @uses    Mai_Recommendations_Setup::setup() Activate, deactivate, etc.
	 * @see     Mai_Recommendations()
	 * @return  object | Mai_Recommendations_Setup The one true Mai_Recommendations_Setup
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the init
			self::$instance = new Mai_Recommendations_Setup;
			// Methods
			self::$instance->setup_constants();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-aec' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-aec' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'MAI_RECOMMENDATIONS_VERSION' ) ) {
			define( 'MAI_RECOMMENDATIONS_VERSION', '0.1.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_RECOMMENDATIONS_PLUGIN_DIR' ) ) {
			define( 'MAI_RECOMMENDATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'MAI_RECOMMENDATIONS_INCLUDES_DIR' ) ) {
			define( 'MAI_RECOMMENDATIONS_INCLUDES_DIR', MAI_RECOMMENDATIONS_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_RECOMMENDATIONS_PLUGIN_URL' ) ) {
			define( 'MAI_RECOMMENDATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'MAI_RECOMMENDATIONS_PLUGIN_FILE' ) ) {
			define( 'MAI_RECOMMENDATIONS_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'MAI_RECOMMENDATIONS_BASENAME' ) ) {
			define( 'MAI_RECOMMENDATIONS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	public function setup() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		/**
		 * Setup the updater.
		 * This class/code is in Mai Pro Engine.
		 * Since this is a dependent plugin, we don't include that code twice.
		 *
		 * @uses  https://github.com/YahnisElsts/plugin-update-checker/
		 */
		if ( class_exists( 'Puc_v4_Factory' ) ) {
			$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/mai-recommendations/', __FILE__, 'mai-recommendations' );
		}
		// Bail if CMB2 is not running anywhere
		if ( ! defined( 'CMB2_LOADED' ) ) {
			add_action( 'admin_init',    array( $this, 'deactivate_plugin' ) );
			add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			return;
		}
		// Includes
		$this->includes();
		// Run
		$this->run();
	}

	function deactivate_plugin() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	function admin_notice() {
		printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', __( 'Mai - Recommendations requires the Mai Pro Engine plugin or CMB2 plugin in order to run. As a result, this plugin has been deactivated.', 'mai-recommendations' ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		foreach ( glob( MAI_RECOMMENDATIONS_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
		require_once MAI_RECOMMENDATIONS_INCLUDES_DIR . 'vendor/extended-cpts.php';
		require_once MAI_RECOMMENDATIONS_INCLUDES_DIR . 'vendor/extended-taxos.php';
		require_once MAI_RECOMMENDATIONS_INCLUDES_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
	}

	public function run() {

		register_activation_hook(   __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

		add_action( 'init',             array( $this, 'register_content_types' ) );
		add_action( 'cmb2_admin_init',  array( $this, 'metabox' ) );
		add_action( 'current_screen',   array( $this, 'maybe_do_gettext_filter' ) );

		add_filter( 'post_type_link',   array( $this, 'permalink' ), 10, 2 );

		// Setup the updater
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/mai-recommendations/', __FILE__, 'mai-recommendations' );
	}

	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
	}

	public function register_content_types() {

		/***********************
		 *  Custom Post Types  *
		 ***********************/

		$args = array(
			'exclude_from_search' => false,
			'menu_icon'           => 'dashicons-admin-links',
			'public'              => false,
			'publicly_queryable'  => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_ui'             => true,
			'supports'            => array( 'title', 'excerpt', 'thumbnail' ),
		);

		$labels = array(
			'singular' => 'Recommendation',
			'plural'   => 'Recommended',
			'slug'     => 'recommendations',
		);

		// Recommendations
		register_extended_post_type( 'recommendation', apply_filters( 'mai_recommendation_args', $args ), apply_filters( 'mai_recommendation_labels', $labels ) );

		/***********************
		 *  Custom Taxonomies  *
		 ***********************/

		// Recommendation Categories
		register_extended_taxonomy( 'recommendation_cat', 'recommendation', array(
			'public'            => false,
			'hierarchical'      => true,
			'query_var'         => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => false,
			'show_ui'           => true,
			'rewrite'           => array(
				'slug'       => 'recommendation-category',
				'with_front' => true,
			),
		), array(
			'singular' => 'Rec Category',
			'plural'   => 'Rec Categories',
		) );

	}

	/**
	 * Define the metabox and field configurations.
	 */
	function metabox() {

		// Initiate the metabox
		$cmb = new_cmb2_box( array(
			'id'              => 'mai_recommendations',
			'object_types'    => array( 'recommendation' ),
			'context'         => 'after_title',
			'show_names'      => true,
			'remove_box_wrap' => true,
		) );

		// URL text field
		$cmb->add_field( array(
			'id'         => 'url',
			'type'       => 'text_url',
			'before'     => '<span style="display: inline-block;background: #f5f5f5;font-size: 18px;padding: 5px 4px 2px;margin: 1px -2px 1px 1px;border: 1px solid #ddd;vertical-align: top;" class="dashicons dashicons-admin-links"></span>',
			'attributes' => array(
				'style'       => 'width: 100%;',
				'placeholder' => __( 'Enter URL here', 'mai-recommendations' ),
			),
		) );

	}

	function maybe_do_gettext_filter() {
		$screen = get_current_screen();
		if ( 'recommendation' !== $screen->post_type ) {
			return;
		}
		add_action( 'admin_head',  array( $this, 'admin_css' ) );
		add_filter( 'gettext',     array( $this, 'translate' ), 10, 3 );
	}

	/**
	 * Change text for the post excerpt
	 *
	 * @since   0.1.0
	 *
	 * @param   string $translated_text
	 * @param   string $text
	 * @param   string $domain
	 *
	 * @return  string
	 */
	function translate( $translated_text, $text, $domain ) {
		if ( 'default' !== $domain ) {
			return $translated_text;
		}
		switch ( $translated_text ) {
			case 'Excerpt' :
				$translated_text = __( 'Description', 'mai-recommendations' );
			break;
			case 'Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="%s">Learn more about manual excerpts</a>.' :
				$translated_text = '';
			break;
		}
		return $translated_text;
	}

	/**
	 * Add custom CSS to <head>
	 *
	 * @since  1.0.0
	 *
	 * @return void
	 */
	function admin_css() {
		echo '<style type="text/css">
			.cmb2-context-wrap.cmb2-context-wrap-mai_recommendations {
				margin-top: 16px;
			}
			#cmb2-metabox-mai_recommendations .cmb-td {
				display: flex;
				flex: 1 1 100%;
				width: 100%;
				max-width: 100%;
			}
		}
		</style>';
	}

	/**
	 * Use the 'url' custom field value for the permalink URL of all recommendation posts.
	 */
	function permalink( $url, $post ) {
		if ( 'recommendation' !== $post->post_type ) {
			return $url;
		}
		$recommendation_url = get_post_meta( $post->ID, 'url', true );
		if ( ! $recommendation_url ) {
			return $url;
		}
		return esc_url( $recommendation_url );
	}

}
endif; // End if class_exists check.

/**
 * The main function for that returns Mai_Recommendations_Setup
 *
 * The main function responsible for returning the one true Mai_Recommendations_Setup
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_Recommendations(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_Recommendations_Setup The one true Mai_Recommendations_Setup Instance.
 */
function Mai_Recommendations() {
	return Mai_Recommendations_Setup::instance();
}

// Get Mai_Recommendations Running.
Mai_Recommendations();
