<?php

/**
 * Plugin Name:     Mai Favorites
 * Plugin URI:      https://maitheme.com
 * Description:     Manage and display your favorite external/affiliate links (products/services/etc) on your Mai Theme powered website.
 * Version:         1.2.1
 *
 * Author:          MaiTheme.com
 * Author URI:      https://maitheme.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Mai_Favorites_Setup Class.
 *
 * @since 1
 */
final class Mai_Favorites_Setup {

	/**
	 * @var    Mai_Favorites_Setup The one true Mai_Favorites_Setup
	 * @since  1.0.0
	 */
	private static $instance;

	/**
	 * Main Mai_Favorites_Setup Instance.
	 *
	 * Insures that only one instance of Mai_Favorites_Setup exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @uses    Mai_Favorites_Setup::setup_constants() Setup the constants needed.
	 * @uses    Mai_Favorites_Setup::setup() Activate, deactivate, etc.
	 * @see     Mai_Favorites()
	 * @return  object | Mai_Favorites_Setup The one true Mai_Favorites_Setup
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the init
			self::$instance = new Mai_Favorites_Setup;
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
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-favorites' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-favorites' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'MAI_FAVORITES_VERSION' ) ) {
			define( 'MAI_FAVORITES_VERSION', '1.2.1' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_FAVORITES_PLUGIN_DIR' ) ) {
			define( 'MAI_FAVORITES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_FAVORITES_PLUGIN_URL' ) ) {
			define( 'MAI_FAVORITES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'MAI_FAVORITES_PLUGIN_FILE' ) ) {
			define( 'MAI_FAVORITES_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'MAI_FAVORITES_BASENAME' ) ) {
			define( 'MAI_FAVORITES_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Setup the plugin.
	 *
	 * @return  void
	 */
	public function setup() {

		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return  void
	 */
	public function init() {

		if ( is_admin() ) {
			/**
			 * Setup the updater.
			 *
			 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
			 *
			 * @return  void
			 */
			if ( class_exists( 'Puc_v4_Factory' ) ) {
				$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-favorites/', __FILE__, 'mai-favorites' );
			}
		}

		// Run
		$this->hooks();
	}

	/**
	 * Run the main plugin hooks and filters.
	 *
	 * @return void
	 */
	public function hooks() {
		register_activation_hook(   __FILE__,  array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__,  'flush_rewrite_rules' );

		add_action( 'init',                    array( $this, 'register_content_types' ) );
		add_action( 'restrict_manage_posts',   array( $this, 'taxonomy_filter' ) );
		add_action( 'current_screen',          array( $this, 'maybe_do_admin_functions' ) );

		add_filter( 'post_type_link',          array( $this, 'permalink' ), 10, 2 );
		add_filter( 'shortcode_atts_grid',     array( $this, 'grid_atts' ), 8, 3 );
		add_filter( 'mai_more_link_text',      array( $this, 'more_link_text' ), 10, 3 );
	}

	/**
	 * Plugin activation, includes flushing rewrite rules.
	 *
	 * @return void
	 */
	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
	}

	/**
	 * Register post types and taxonomies.
	 *
	 * @return void
	 */
	public function register_content_types() {

		/***********************
		 *  Custom Post Types  *
		 ***********************/

		register_post_type( 'favorite',array(
			'exclude_from_search' => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'labels'              => array(
				'name'               => _x( 'Favorites', 'Favorite general name'        , 'mai-favorites' ),
				'singular_name'      => _x( 'Favorite' , 'Favorite singular name'       , 'mai-favorites' ),
				'menu_name'          => _x( 'Favorites', 'Favorite admin menu'          , 'mai-favorites' ),
				'name_admin_bar'     => _x( 'Favorite' , 'Favorite add new on admin bar', 'mai-favorites' ),
				'add_new'            => _x( 'Add New'  , 'Favorite add new'             , 'mai-favorites' ),
				'add_new_item'       => __( 'Add New Favorite'                          , 'mai-favorites' ),
				'new_item'           => __( 'New Favorite'                              , 'mai-favorites' ),
				'edit_item'          => __( 'Edit Favorite'                             , 'mai-favorites' ),
				'view_item'          => __( 'View Favorite'                             , 'mai-favorites' ),
				'all_items'          => __( 'All Favorites'                             , 'mai-favorites' ),
				'search_items'       => __( 'Search Favorites'                          , 'mai-favorites' ),
				'parent_item_colon'  => __( 'Parent Favorites:'                         , 'mai-favorites' ),
				'not_found'          => __( 'No Favorites found.'                       , 'mai-favorites' ),
				'not_found_in_trash' => __( 'No Favorites found in Trash.'              , 'mai-favorites' )
			),
			'menu_icon'          => 'dashicons-star-filled',
			'public'             => false,
			'publicly_queryable' => false,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => false,
			'show_ui'            => true,
			'rewrite'            => false,
			'supports'           => array( 'title', 'excerpt', 'page-attributes', 'author', 'thumbnail' ),
			'taxonomies'         => array( 'favorite_cat' ),
		) );

		/***********************
		 *  Custom Taxonomies  *
		 ***********************/

		register_taxonomy( 'favorite_cat', 'favorite', array(
			'exclude_from_search' => true,
			'has_archive'         => false,
			'hierarchical'        => true,
			'labels' => array(
				'name'                       => _x( 'Favorites Categories', 'taxonomy general name', 'mai-favorites' ),
				'singular_name'              => _x( 'Favorite Category' , 'taxonomy singular name' , 'mai-favorites' ),
				'search_items'               => __( 'Search Favorite Categories'                   , 'mai-favorites' ),
				'popular_items'              => __( 'Popular Favorite Categories'                  , 'mai-favorites' ),
				'all_items'                  => __( 'All Categories'                               , 'mai-favorites' ),
				'edit_item'                  => __( 'Edit Favorite Category'                       , 'mai-favorites' ),
				'update_item'                => __( 'Update Favorite Category'                     , 'mai-favorites' ),
				'add_new_item'               => __( 'Add New Favorite Category'                    , 'mai-favorites' ),
				'new_item_name'              => __( 'New Favorite Category Name'                   , 'mai-favorites' ),
				'separate_items_with_commas' => __( 'Separate Favorite Categories with commas'     , 'mai-favorites' ),
				'add_or_remove_items'        => __( 'Add or remove Favorite Categories'            , 'mai-favorites' ),
				'choose_from_most_used'      => __( 'Choose from the most used Favorite Categories', 'mai-favorites' ),
				'not_found'                  => __( 'No Favorite Categories found.'                , 'mai-favorites' ),
				'menu_name'                  => __( 'Favorite Categories'                          , 'mai-favorites' ),
				'parent_item'                => null,
				'parent_item_colon'          => null,
			),
			'public'            => false,
			'rewrite'           => false,
			'show_admin_column' => true,
			'show_in_menu'      => true,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_ui'           => true,
		) );

	}

	/**
	 * Display a favorite_cat taxonomy dropdown in admin.
	 *
	 * @uses    wp_dropdown_categories().
	 *
	 * @return  void
	 */
	function taxonomy_filter() {
		global $typenow;
		if ( $typenow !== 'favorite' ) {
			return;
		}
		$taxonomy      = 'favorite_cat';
		$selected      = isset( $_GET[$taxonomy] ) ? $_GET[$taxonomy] : '';
		$info_taxonomy = get_taxonomy( $taxonomy );
		wp_dropdown_categories( array(
			'hierarchical'     => true,
			'hide_empty'       => true,
			'name'             => $taxonomy,
			'orderby'          => 'name',
			'selected'         => $selected,
			'show_count'       => true,
			'show_option_all'  => __( "All {$info_taxonomy->label}", 'mai-favorites' ),
			'show_option_none' => __( 'All Categories', 'mai-favorites' ),
			'taxonomy'         => $taxonomy,
			'value_field'      => 'slug',
		));
	}

	/**
	 * Maybe add custom CSS and filter the metabox text.
	 *
	 * @return  void
	 */
	function maybe_do_admin_functions() {
		$screen = get_current_screen();
		if ( 'favorite' !== $screen->post_type ) {
			return;
		}
		add_filter( 'gettext',    array( $this, 'translate' ), 10, 3 );
	}

	/**
	 * Change text for the post excerpt
	 *
	 * @since   1.0.0
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
				$translated_text = __( 'Description', 'mai-favorites' );
			break;
			case 'Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="%s">Learn more about manual excerpts</a>.' :
				$translated_text = '';
			break;
		}
		return $translated_text;
	}

	/**
	 * Use the 'url' custom field value for the permalink URL of all favorite posts.
	 *
	 * @return  string  The url/permalink.
	 */
	function permalink( $url, $post ) {
		if ( 'favorite' !== $post->post_type ) {
			return $url;
		}
		$favorite_url = get_post_meta( $post->ID, 'url', true );
		return $favorite_url ? esc_url( $favorite_url ) : $url;
	}

	/**
	 * Filter the default args for [grid] shortcode when displaying favorites.
	 *
	 * @param   array  $out    The modified attributes.
	 * @param   array  $pairs  Entire list of supported attributes and their defaults.
	 * @param   array  $atts   User defined attributes in shortcode tag.
	 *
	 * @return  array  The modified attributes.
	 */
	function grid_atts( $out, $pairs, $atts ) {

		// Bail if not a favorite.
		if ( ! isset( $atts['content'] ) || ( 'favorite' !== $atts['content'] ) ) {
			return $out;
		}

		if ( ! isset( $atts['show'] ) ) {
			$out['show'] = 'image, title, excerpt, more_link';
		}

		if ( ! isset( $atts['more_link_text'] ) ) {
			$out['more_link_text'] = __( 'Learn More', 'mai-favorites' );
		}

		if ( ! isset( $atts['target'] ) ) {
			$out['target'] = '_blank';
			if ( ! isset( $atts['rel'] ) ) {
				$out['rel'] = 'noopener';
			}
		}

		return $out;
	}

	/**
	 * Use button_text meta field value for more link text.
	 *
	 * @param   string     $text          The more link text.
	 * @param   object|id  $object_or_id  The post/term object or id.
	 * @param   string     $type          The type of object, currently only 'post' or 'term'.
	 *
	 * @return  string|HTML  Return more link HTML.
	 */
	function more_link_text( $text, $object_or_id, $type ) {
		if ( 'post' !== $type ) {
			return $text;
		}
		if ( 'favorite' !== get_post_type( $object_or_id ) ) {
			return $text;
		}
		global $post;
		$button_text = get_post_meta( $post->ID, 'button_text', true );
		return $button_text ? esc_html( $button_text ) : $text;
	}

}

/**
 * The main function for that returns Mai_Favorites_Setup
 *
 * The main function responsible for returning the one true Mai_Favorites_Setup
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_Favorites(); ?>
 *
 * @since  1.0.0
 *
 * @return object|Mai_Favorites_Setup The one true Mai_Favorites_Setup Instance.
 */
function Mai_Favorites() {
	return Mai_Favorites_Setup::instance();
}

// Get Mai_Favorites Running.
Mai_Favorites();



add_action( 'add_meta_boxes', 'maifavorites_add_meta_box' );
function maifavorites_add_meta_box( $post_type ) {
	if ( 'favorite' !== $post_type ) {
		return;
	}

	add_meta_box(
		'maifavorites_meta_box',
		esc_html__( 'URL & Button Text', 'mai-favorites' ),
		'maifavorites_render_meta_box',
		$post_type,
		'normal',
		'high'
	);
}

/**
 * Render Meta Box content.
 *
 * @param WP_Post $post The post object.
 */
function maifavorites_render_meta_box( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'maifavorites_meta_box', 'maifavorites_meta_box_nonce' );

	// Use get_post_meta to retrieve an existing value from the database.
	$button_url  = get_post_meta( $post->ID, 'url', true );
	$button_text = get_post_meta( $post->ID, 'button_text', true );

	// TODO: Get button text placeholder from v2 template (customizer) args.

	// Display the form, using the current value.
	printf( '<p style="margin-bottom:4px;"><label for="maifavorites_url">%s*</label></p>', esc_html__( 'URL', 'mai-favorites' ) );
	printf( '<input style="display:block;width:100%%;" type="url" id="maifavorites_url" name="maifavorites_url" value="%s" placeholder="%s" required/>', esc_attr( $button_url ), __( 'Enter URL here', 'mai-favorites' ) );
	printf( '<p style="margin-bottom:4px;"><label for="maifavorites_button_text">%s</label></p>', esc_html__( 'Button Text', 'mai-favorites' ) );
	printf( '<input style="display:block;width:100%%;margin-bottom:1em;" type="text" id="maifavorites_button_text" name="maifavorites_button_text" value="%s" placeholder="%s" />', esc_attr( $button_text ), __( 'Learn More', 'mai-favorites' ) );
}

// add_action( 'save_post_favorite', 'maifavorites_save_meta_box' );
/**
 * Save the meta when the post is saved.
 * We need to verify this came from the our screen and with proper authorization,
 * because save_post can be triggered at other times.*
 *
 * @param int $post_id The ID of the post being saved.
 */
function maifavorites_save_meta_box( $post_id ) {
	// Check if our nonce is set.
	if ( ! isset( $_POST['maifavorites_meta_box_nonce'] ) ) {
		return $post_id;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['maifavorites_meta_box_nonce'], 'maifavorites_meta_box' ) ) {
		return $post_id;
	}

	// Bail if an autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// Check the user's permissions.
	if ( ! current_user_can( 'edit_favorite', $post_id ) ) {
		return $post_id;
	}

	// Update the meta fields.
	update_post_meta( $post_id, 'url', esc_url( $_POST['maifavorites_url'] ) );
	update_post_meta( $post_id, 'button_text', sanitize_text_field( $_POST['maifavorites_button_text'] ) );
}

add_filter( 'mai_grid_post_types', 'maifavorites_grid_post_type' );
function maifavorites_grid_post_type( $post_types ) {
	$post_types[] = 'favorite';
	return $post_types;
}
