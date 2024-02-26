<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.fedegomez.es
 * @since      1.0.0
 *
 * @package    Send_To_Archive_Org
 * @subpackage Send_To_Archive_Org/includes
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
 * @package    Send_To_Archive_Org
 * @subpackage Send_To_Archive_Org/includes
 * @author     Fede Gómez <hola@fedegomez.es>
 */
class Send_To_Archive_Org {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Send_To_Archive_Org_Loader    $loader    Maintains and registers all hooks for the plugin.
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
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SEND_TO_ARCHIVE_ORG_VERSION' ) ) {
			$this->version = SEND_TO_ARCHIVE_ORG_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'send-to-archive-org';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Send_To_Archive_Org_Loader. Orchestrates the hooks of the plugin.
	 * - Send_To_Archive_Org_i18n. Defines internationalization functionality.
	 * - Send_To_Archive_Org_Admin. Defines all hooks for the admin area.
	 * - Send_To_Archive_Org_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-send-to-archive-org-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-send-to-archive-org-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-send-to-archive-org-admin.php';

		$this->loader = new Send_To_Archive_Org_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Send_To_Archive_Org_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Send_To_Archive_Org_i18n();

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

		$plugin_admin = new Send_To_Archive_Org_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_filter( 'script_loader_tag', $plugin_admin, 'add_script_as_module', 10, 3 );

        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_plugin_settings' );
        $this->loader->add_action( 'admin_menu',  $plugin_admin, 'register_options_page' );

        $this->loader->add_action( 'post_row_actions', $plugin_admin, 'add_row_action', 10, 2 );
        $this->loader->add_action('page_row_actions', $plugin_admin, 'add_row_action', 10, 2);

        $this->loader->add_action( 'admin_init', $plugin_admin, 'add_features_to_cpt' );

        $this->loader->add_action( 'wp_ajax_send_to_archive_org', $plugin_admin, 'send_to_archive_org' );
        $this->loader->add_action( 'wp_ajax_get_snapshots', $plugin_admin, 'get_archive_org_availability' );
        $this->loader->add_filter( 'manage_posts_columns', $plugin_admin, 'add_archive_org_column' );
        $this->loader->add_action( 'manage_posts_custom_column', $plugin_admin, 'add_archive_org_column_content', 10, 2 );
        $this->loader->add_filter( 'manage_pages_columns', $plugin_admin, 'add_archive_org_column' );
        $this->loader->add_action( 'manage_pages_custom_column', $plugin_admin, 'add_archive_org_column_content', 10, 2 );

        $this->loader->add_filter('bulk_actions-edit-post', $plugin_admin, 'send_to_archive_org_bulk_action');
        $this->loader->add_filter('bulk_actions-edit-page', $plugin_admin, 'send_to_archive_org_bulk_action');
        $this->loader->add_filter( 'handle_bulk_actions-edit-post', $plugin_admin, 'handle_bulk_actions', 10, 3 );
        $this->loader->add_filter( 'handle_bulk_actions-edit-page', $plugin_admin, 'handle_bulk_actions', 10, 3 );

        $this->loader->add_action( 'admin_notices', $plugin_admin, 'display_admin_notice' );

        $this->loader->add_action('save_post', $plugin_admin,'handle_post_save', 10, 3);

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
	 * @return    Send_To_Archive_Org_Loader    Orchestrates the hooks of the plugin.
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
