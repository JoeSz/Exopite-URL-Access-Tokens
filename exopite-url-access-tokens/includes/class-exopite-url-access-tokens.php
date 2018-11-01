<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.joeszalai.org/
 * @since      1.0.0
 *
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/includes
 * @author     Joe Szalai <joe@joeszalai.org>
 */
class Exopite_Url_Access_Tokens {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Exopite_Url_Access_Tokens_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Store plugin main class to allow public access.
	 *
	 * @since    20180622
	 * @var object      The main class.
	 */
	public $main;

	/**
	 * Store plugin admin class to allow public access.
	 *
	 * @since    20180622
	 * @var object      The admin class.
	 */
	public $admin;


	/**
	 * Store plugin public class to allow public access.
	 *
	 * @since    20180622
	 * @var object      The admin class.
	 */
	public $public;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->main = $this;

		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'exopite-url-access-tokens';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Exopite_Url_Access_Tokens_Loader. Orchestrates the hooks of the plugin.
	 * - Exopite_Url_Access_Tokens_i18n. Defines internationalization functionality.
	 * - Exopite_Url_Access_Tokens_Admin. Defines all hooks for the admin area.
	 * - Exopite_Url_Access_Tokens_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-exopite-url-access-tokens-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-exopite-url-access-tokens-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-exopite-url-access-tokens-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-exopite-url-access-tokens-public.php';

		/**
		 * Exopite Client Detector.
		 *
		 * Detect client:
		 * - real IP address
		 * - browser
		 * - os
		 * - real user agent
		 * - platform [mobile, tablet, desktop]
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/class-exopite-client-detector.php';

        /**
         * Exopite Simple Options Framework
         *
         * @link https://github.com/JoeSz/Exopite-Simple-Options-Framework
         * @author Joe Szalai
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/exopite-simple-options/exopite-simple-options-framework-class.php';

		$this->loader = new Exopite_Url_Access_Tokens_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Exopite_Url_Access_Tokens_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Exopite_Url_Access_Tokens_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->admin = new Exopite_Url_Access_Tokens_Admin( $this->get_plugin_name(), $this->get_version(), $this->main );

		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->admin, 'enqueue_scripts' );

        /**
		 *  Save/Update our plugin options with
		 * Exopite Simple Options Framework
		 */
        $this->loader->add_action( 'init', $this->admin, 'create_menu', 999 );

        $this->loader->add_action( 'wp_ajax_exopite-post-access-tokens-access-list', $this->admin, 'ajax_get_access_log' );
        $this->loader->add_action( 'wp_ajax_exopite-post-access-tokens-delete-access-list', $this->admin, 'ajax_delete_access_log' );

        $this->loader->add_action( 'post_submitbox_misc_actions', $this->admin, 'display_info_on_post_submitbox_metabox' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$this->public = new Exopite_Url_Access_Tokens_Public( $this->get_plugin_name(), $this->get_version(), $this->main );

		$this->loader->add_action( 'wp_enqueue_scripts', $this->public, 'enqueue_styles' );

        /**
         * Manage sessions.
         * We need this to "remember" tokens.
         * Otherwise only works if token is in $_GET.
         */
        $this->loader->add_action( 'init', $this->public, 'start_session', 1 );
		$this->loader->add_action( 'wp_logout', $this->public, 'end_session' );
		$this->loader->add_action( 'wp_login', $this->public, 'end_session' );
		/**
         * Handle archives, hide protected posts
         * https://wordpress.stackexchange.com/questions/83061/how-to-prevent-double-execution-of-do-action-statements
         */
        $this->loader->add_action( 'pre_get_posts', $this->public, 'pre_get_posts', 999 );

        /**
         * Handle singles
         * https://wordpress.stackexchange.com/questions/83061/how-to-prevent-double-execution-of-do-action-statements
         */
        $this->loader->add_action( 'template_redirect', $this->public, 'template_redirect', 999 );

		$this->loader->add_filter( 'wp_nav_menu_objects', $this->public, 'wp_nav_menu_objects', 999, 2 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Exopite_Url_Access_Tokens_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
