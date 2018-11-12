<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.joeszalai.org/
 * @since      1.0.0
 *
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/public
 * @author     Joe Szalai <joe@joeszalai.org>
 */
class Exopite_Url_Access_Tokens_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    20180622
     * @var object      The main class.
     */
    public $main;

	public $options;
	public $log_in_file;
	public $log_access;
	public $use_session;
	public $frontend_message;
	public $notification_info = array();
	public $display_admin_notice;
    public $current_user_is_administrator;
    public $hash;
    public $post__not_in;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_main ) {

        $this->main = $plugin_main;

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->options = get_option( $this->plugin_name );

        $this->log_in_file = ( isset( $this->options['log_file'] ) ) ? ( $this->options['log_file'] == 'yes' ) : false;
        $this->log_access = ( isset( $this->options['log_access'] ) ) ? ( $this->options['log_access'] == 'yes' ) : false;
        $this->use_session = ( isset( $this->options['use_session'] ) ) ? ( $this->options['use_session'] == 'yes' ) : false;
        $this->display_admin_notice = ( isset( $this->options['admin_notification'] ) ) ? ( $this->options['admin_notification'] == 'yes' ) : false;

        if( ! function_exists( 'wp_get_current_user' ) ) {
            include( ABSPATH . "wp-includes/pluggable.php" );
        }

        $this->current_user_is_administrator = current_user_can( 'administrator' );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/exopite-url-access-tokens-public.min.css', array(), $this->version, 'all' );

	}

    public function display_frontend_notice() {

        echo $this->frontend_notice( '' );

    }

    public function frontend_notice( $content ) {

        $content = '<div class="exopite-frontend-notice">' . $this->frontend_message . '</div>' . $content;
        return $content;

    }

    // add links/menus to the admin bar
    public function add_admin_bar_menu() {
        global $wp_admin_bar;

        if ( ! is_admin_bar_showing() ) return;

        $wp_admin_bar->add_menu( array(
            'id' => 'exopite-url-access-tokens-admin-bar',
            'title' => esc_html__('Token aktive', 'exopite-url-access-tokens' ),
            'href' => admin_url( 'plugins.php?page=exopite-url-access-tokens&section=tokens_options')
        ) );

    }


    /**
     * Token Rules:
     * - token MUST be unique to check from-to
     *   if same hash assigned to multipe pages, from-to validation vaildate only first!
     * - accept multipe hashes from user GET
     * - if post has multiple token in options, user muss have all tokens to see post
     */

	/**
	 * Manage access for singles (post) and pages.
	 * (Action hook)
	 *
	 * Infos:
	 * - https://codex.wordpress.org/Function_Reference/did_action
	 *
	 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/template_redirect
	 */
	public function template_redirect() {

		if ( ! $this->is_plugin_activated() ) return;

		if ( is_admin() ) return;

		if ( ! in_array( get_post_type(), $this->options['post'] ) ) return;

        if ( is_archive() || is_home() || is_search() ) return;

		$access_tokens = array_filter( $this->get_option_tokens() );

        /**
         * Check tokens
         */
        if ( ! empty( $access_tokens ) ) {

            if ( $this->current_user_is_administrator ) {
                if ( $this->display_admin_notice ) {
                    add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 101 );
                }
                return;
            }

            global $wp_query, $wpdb;

            $post_id = get_the_id();

            $user_tokens = $this->get_user_tokens();

            $valid_token = $this->is_intersect( $access_tokens, $user_tokens );

            if ( $user_tokens && $valid_token ) {
                /**
                 * Success
                 */

                $this->notification_info['message'] = 'Successful token access';
                $this->notification_info['token'] = $valid_token;
                $this->logging( 'success', $valid_token );

            } else {

                $this->notification_info['message'] = 'Token access failed';
                $this->notification_info['token'] = false;
                $this->logging( 'error', '' );

                $this->set_404();

            }

            add_filter( 'exopite-notificator-send-messages', array( $this, 'send_notification' ), 10, 1 );

        }



	}

	/**
	 * Manage access for archives, menus and search.
	 * (Action hook)
	 * _ALL_ post/page with active token need to filter out in pre_get_posts
	 *
	 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/pre_get_posts
	 */
	public function pre_get_posts( $query ) {

        /**
         * Handle archives
         * Need to remove protected posts (actually any post type) form all loops,
         * make sure, they do not show up in "next/previous posts", "releated posts",
         * blog or any post type archive or in widgets, etc...
         *
         * generate token and assign posts to is -> SETTINGS
         * - use posts__not_in for all assigned posts
         * - except given tokens
         */

		if ( ! $this->is_plugin_activated() ) return;

		if ( is_admin() ) return;

		if ( $this->current_user_is_administrator ) return;

		// Do not apply for single and page.
		// Those will be handeled by template_redirect.
        if ( $query->is_single || $query->is_page ) return;

        global $wpdb;
		$user_tokens = $this->get_user_tokens();
		$now = strtotime( date( "Y-m-d" ) );

		/**
         * Check tokens from plugin options
         *
         * Check if options has tokens and access management is activated.
         */
        if ( isset( $this->options['tokens'] ) && is_array( $this->options['tokens'] ) ) {

            $post__not_in = array();

            // Tokens from settings
            foreach ( $this->options['tokens'] as $token_option ) {

                if ( ! isset( $token_option['activated'] ) || $token_option['activated'] !== 'yes' ) return;

                /**
                 * If token is in the time frame and not in user tokens then hide posts
                 */
                if ( $this->validate_token_time( $now, $token_option['token_from_date'], $token_option['token_until_date'] ) &&
                     ! in_array( $token_option['token_hash'], $user_tokens ) ) {

                    foreach ( $this->options['post'] as $options_post_type ) {

						if ( isset( $token_option[$options_post_type] ) && is_array( $token_option[$options_post_type] ) ) {

                            $post__not_in = array_merge( $post__not_in, $token_option[$options_post_type] );

                        }

                    }

                }


            }

            $this->post__not_in = $post__not_in;
            $query->set( 'post__not_in', $post__not_in );

        }

	}

    public function wp_nav_menu_objects( $sorted_menu_objects, $args ) {

        if ( $this->current_user_is_administrator ) return $sorted_menu_objects;

        foreach ($sorted_menu_objects as $key => $menu_object) {

            if ( in_array( $menu_object->object_id, $this->post__not_in  ) ) {

                unset($sorted_menu_objects[$key]);

            }

        }

        return $sorted_menu_objects;

    }

	/**
	 * Access Log
	 */
	public function logging( $status, $token ) {

        if ( ! $this->log_access && ! $this->log_in_file ) return;

		$client = Exopite_Client_Detector::get_client();

        if ( $this->log_access ) {
            global $wpdb;

            $wpdb->insert( $wpdb->prefix . "post_access_tokens_log", array(
                    'ip'      => $client['ip'],
                    'time'    => date("Y-m-d H:i:s"),
                    'token'   => $token,
                    'post_id' => get_the_id(),
                    'browser' => $client['name'] . " | " . $client['version'] . " | " . $client['os'] . " | " . $client['platform'],
                    'title'   => get_the_title(),
                    'status'  => $status,
                )
            );

        }

        if ( $this->log_in_file ) {

            $token_info = $this->get_token_by_hash( $token );
            $log_line = 'IP: ' . $client['ip'] . ', Token: ' . $token_info['token_name'] . ', Token-Hash: ' . $token . ', ID: ' . get_the_id() . ', Title: ' . get_the_title() . ', Browser: ' . $client['name'] . " | " . $client['version'] . " | " . $client['os'] . " | " . $client['platform'] . ', Permalink: ' . get_the_permalink();

            $this->write_log( 'access-' . $status, $log_line );

        }

    }

    public function get_token_by_hash( $token_hash ) {

        foreach ( $this->options['tokens'] as $token_item ) {

            if ( $token_hash == $token_item['token_hash'] ) {
                return $token_item;
            }

        }

        return array();

    }

    public function get_token_notification_settings( $token_user ) {

        $token = $this->get_token_by_hash( $token_user );

        if ( ! empty( $token ) ) {
            return array(
                'email' => explode( ',', preg_replace( '/\s+/', '', $token['notification_email'] ) ),
                'telegram' => explode( ',', preg_replace( '/\s+/', '', $token['notification_telegram'] ) ),
            );
        }

        return array();
    }

    public function send_notification( $messages = '' ) {

        if ( ! is_plugin_active( 'exopite-notificator/exopite-notificator.php' ) ) return;

        /**
         * - Check if notification activated.
         * - Check if post has
         *   - email or telegram channel
         *   This will send message on failer or success
         * - Check if token has email or telegram channel
         *   This send message only in success
         */

        $notifications = array();

        if ( isset( $this->notification_info['token'] ) && $this->notification_info['token'] !== false && ! empty( $this->notification_info['token'] ) ) {
            $token_notifications = $this->get_token_notification_settings( $this->notification_info['token'] );
            $notifications = array_merge_recursive( $notifications, $token_notifications );

            $token_info = $this->get_token_by_hash( $this->notification_info['token'] );
            $this->notification_info['message'] .= PHP_EOL . '<br>Token: ' . $token_info['token_name'] . '<br>' . PHP_EOL . 'Token-Hash: ' . $token_info['token_hash'];
        }

        $subject = 'SUBJECT';

        $notifications = array_filter( array_map( 'array_filter', $notifications ) );

        if ( ! empty( $notifications['email'] ) ) {

            $messages[] = array(
                'type' => 'email',
                'message' => $this->notification_info['message'] . PHP_EOL . '<br>Site: ' . get_the_permalink() . PHP_EOL . '<br>IP: {{user-ip}}' . PHP_EOL . '<br>Browser: {{user-agent}}' . PHP_EOL . '<br>Date: {{datetime}}',
                'email_recipients' => implode( ',', $notifications['email'] ),
                'email_subject' => $subject,
                'email_smtp_override' => 'no',
                'email_disable_bloginfo' => 'yes',
                'alert-type' => 'Token access',
            );

        }

        if ( ! empty( $notifications['telegram'] ) ) {

            $messages[] = array(
                'type' => 'telegram',
                'message' => $this->notification_info['message'] . PHP_EOL . 'Site: ' . get_the_permalink() . PHP_EOL . 'IP: {{user-ip}}' . PHP_EOL . 'Browser: {{user-agent}}' . PHP_EOL . 'Date: {{datetime}}',
                'telegram_recipients' => implode( ',', $notifications['telegram'] ),
                'alert-type' => 'Token access',
            );

        }

        return $messages;

    }

	public function is_plugin_activated() {
		return ( isset( $this->options['activated'] ) && $this->options['activated'] === 'yes' );
	}

	/**
	 * ToDos:
	 * - Check if get_post_type() and/or get_the_id() is available for pre_get_posts?
	 */
	public function get_option_tokens() {

		$now = strtotime( date( "Y-m-d" ) );
		$option_tokens = array();
		$post_type = get_post_type();
		$post_id = get_the_id();

        if ( isset( $this->options['tokens'] ) && ! empty( $this->options['tokens'] ) ) {

            foreach ( $this->options['tokens'] as $token ) {

                // Check if individual token is active.
                if ( ! isset( $token['activated'] ) || $token['activated'] !== 'yes' ) continue;

                // If in options current post/page is listed for token(s) for current post type.
                if ( ! isset( $token[ $post_type ] ) || ! is_array( $token[ $post_type ] ) || ! in_array( $post_id, $token[ $post_type ] ) ) continue;

                // Check if token is inside the time frame
                if ( ! $this->validate_token_time( $now, $token['token_from_date'], $token['token_until_date'] ) ) continue;

                $option_tokens[] = $token['token_hash'];

            }

        }

		return $option_tokens;

	}

	public function get_user_tokens() {

        /**
         * Get tokens from session,
         * also add a fallback if session not working.
         * Later maybe an option to disable session.
         */
        if ( $this->use_session && isset( $_SESSION['tokens'] ) ) {
            foreach ( $_SESSION['tokens'] as $key => $token ) {
                $user_tokens_input[] = $key;
            }
        } else {
            $user_tokens_input = ( isset( $_GET['token'] ) ) ? array_unique( explode( ',', $_GET['token'] ) ) : array();
        }

		$user_tokens = array();

        foreach ( $user_tokens_input as $user_token_raw ) {
            $user_tokens[] = esc_attr( $user_token_raw );
		}

		return $user_tokens;

	}

    public function set_404() {

        global $wp_query;
        $wp_query->set_404();
        $wp_query->max_num_pages = 0; // stop theme from showing Next/Prev links

    }

    public function validate_token_time( $now, $token_start, $token_end ) {

        $token_from = strtotime( $token_start );
        $token_to   = strtotime( $token_end );

        if ( ( $now >= $token_from && $now <= $token_to ) ||        // we have start and end date
                ( $now >= $token_from && ! $token_to ) ||           // only start date
				( $now <= $token_to && ! $token_from ) ||           // only end date
				( ! $token_to && ! $token_from )					// no start or end date
		) {
            return true;
        }

        return false;

    }

    /**
     * PHP Comparing 2 Arrays For Existence of Value in Each
     *
     * @link https://stackoverflow.com/questions/4692016/php-comparing-2-arrays-for-existence-of-value-in-each/4692080#4692080
     */
    function is_intersect( $a, $b ) {

        if ( ! is_array( $a ) || ! is_array( $b ) ) return false;

        $c = array_flip( $a );
        foreach ( $b as $v ) {
            if ( isset( $c[$v] ) ) return $v;
        }
        return false;
    }

    /*/*
     * Max 30 day valid.
     * Max 30 keys.
     */
    public function start_session() {

        if ( ! $this->use_session || is_admin() ) return;

        // DEBUG -> OFF
        if ( $this->current_user_is_administrator ) return;

        if( ! session_id() ) {
            session_start();

            $tokens_raw = ( isset( $_GET['token'] ) ) ? array_filter( explode( ',', esc_attr( trim( $_GET['token'] ) ) ) ) : '';

            $tokens = array();
            $session_tokens = $_SESSION['tokens'];

            $now = new \DateTime();

            /**
             * Add new tokens from user if any.
             */
            if ( ! empty( $tokens_raw ) ) {

                foreach ( $tokens_raw as $token ) {
                    $tokens[$token] = $now->format( 'Y-m-d' );
                }

                if ( ! is_array( $session_tokens ) ) $session_tokens = array();
                $session_tokens = array_merge( $session_tokens, $tokens );

            }

            if ( ! empty( $session_tokens ) ) {

                /**
                 * Check if token older then 30 days.
                 */
                foreach ( $session_tokens as $key => $session_token ) {

                    $token_date = DateTime::createFromFormat( 'Y-m-d', $session_token );

                    if( $token_date->diff( $now )->days > 30 || empty( $key ) || ! $token_date ) {

                        unset( $session_tokens[$key] );

                    }

                }

            }

            /**
             * Max 30 tokens.
             */
            if ( count( $session_tokens ) > 30 ) {
                $session_tokens = array_slice( $session_tokens, 0, -30 );
            }

            $_SESSION['tokens'] = $session_tokens;

        }

    }

    public function end_session() {
        if( ( $this->use_session || ! is_admin() ) && session_id() ) session_destroy ();
    }

    /**
     * Log in file functions.
     */

    public function check_or_create_md5_hash() {

        // Check if md5 exist

        if ( ! isset( $this->options['_hash'] ) ) {

            $this->options['_hash'] = md5( uniqid( rand(), true ) );
            update_option( $this->plugin_name, $this->options );

        }

        if ( ! $this->hash ) {
            $this->hash = $this->options['_hash'];
        }

    }

    public function manage_log_size( $fn, $log_dir ) {

        if ( ! file_exists( $fn ) ) {
            $file_size = 0;
        } else {
            $file_size = filesize( $fn );
        }

        /**
         * If log file is bigger then 1MB, rename it to backup.log and start new log,
         * in this case we have max 2MB log file per type.
         */
        if ( $file_size > 1000000 ) {
            rename( $fn, $log_dir . DIRECTORY_SEPARATOR . $type . '-' . $this->hash . '.backup.log' );
        }

    }

    public function check_or_create_log_folter( $log_dir ) {

        if ( ! file_exists( $log_dir ) ) {
            mkdir( $log_dir, 0777, true );
        }

    }

    /**
     * Create log
     */
    public function write_log( $type, $log_line, $log_title = '', $var_export = false, $fn_include_date = false ) {

        $now = date('Y-m-d H:i:s');

        if ( $fn_include_date ) {
            $type .= '-' . $now;
        }

        $this->check_or_create_md5_hash();

        $log_dir = EXOPITE_URL_ACCESS_TOKENS_PLUGIN_DIR . 'logs';
        $fn = $log_dir . DIRECTORY_SEPARATOR . $type . '-' . $this->hash . '.log';

        $this->check_or_create_log_folter( $log_dir );

        $this->manage_log_size( $fn, $log_dir );

        if ( ! empty( $log_title ) ) {
            $log_title .= PHP_EOL;
        }

        if ( $var_export ) {
            $log_line = var_export( $log_linr, true );
        }

        $log_in_file = file_put_contents( $fn, $now . ' - ' . $log_title . $log_line . PHP_EOL, FILE_APPEND );

    }

}
