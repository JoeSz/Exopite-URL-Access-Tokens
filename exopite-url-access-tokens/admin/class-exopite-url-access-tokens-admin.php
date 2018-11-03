<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.joeszalai.org/
 * @since      1.0.0
 *
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/admin
 */

/**
 * ToDos:
 * - display meta box? on protected page
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/admin
 * @author     Joe Szalai <joe@joeszalai.org>
 */
class Exopite_Url_Access_Tokens_Admin {

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_main ) {

        $this->main = $plugin_main;
		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/exopite-url-access-tokens-admin.min.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

        /**
         * https://sweetalert.js.org/guides/
         */
        wp_enqueue_script( 'sweetalert', '//unpkg.com/sweetalert/dist/sweetalert.min.js',  false, '2.1.0', true );

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/exopite-url-access-tokens-admin.min.js', array( 'jquery' ), $this->version, true );

	}

       public function ajax_get_access_log () {

        global $wpdb;

        $record_per_page = 25;
        $page = ( isset( $_POST['args']['page'] ) ) ? intval( $_POST['args']['page'] ) : 1;
        $offset = ( $record_per_page * ( $page - 1 ) );
        $status = ( isset( $_POST['args']['status'] ) ) ? sanitize_text_field( $_POST['args']['status'] ) : 'success';
        $sort = "{$wpdb->prefix}post_access_tokens_log." . sanitize_text_field( $_POST['args']['sort'] ) . " ";
        $order = ( isset( $_POST['args']['order'] ) ) ? sanitize_text_field( $_POST['args']['order'] ) : "DESC";

        $search = ( isset( $_POST['args']['search'] ) ) ? sanitize_text_field( $_POST['args']['search'] ) : '';
        $search_sql_str = '';

        if ( ! empty( $_POST['args']['search'] ) && strlen( $_POST['args']['search'] ) > 2 ) {

            $search_fields_like = array(
                $wpdb->prefix . 'post_access_tokens_log.ip',
                $wpdb->prefix . 'post_access_tokens_log.time',
                $wpdb->prefix . 'post_access_tokens_log.token',
                $wpdb->prefix . 'post_access_tokens_log.browser',
            );

            $search_sql = array();

            foreach ( $search_fields_like as $search_field ) {
                $search_sql[] = $search_field . " LIKE '%" . $search . "%'";
            }

            $search_sql_str = ' AND (';
            $search_sql_str .= implode( ' OR ', $search_sql );
            $search_sql_str .= ') ';

        }

         $sql = "SELECT {$wpdb->prefix}post_access_tokens_log.id, {$wpdb->prefix}post_access_tokens_log.ip, {$wpdb->prefix}post_access_tokens_log.time, {$wpdb->prefix}post_access_tokens_log.token, {$wpdb->prefix}post_access_tokens_log.browser, {$wpdb->prefix}post_access_tokens_log.post_id FROM `{$wpdb->prefix}post_access_tokens_log` WHERE {$wpdb->prefix}post_access_tokens_log.status='" . $status . "' " . $search_sql_str . "ORDER BY " . $sort . $order . " LIMIT " . $offset . ", " . $record_per_page;

        $sql_count = "SELECT COUNT(*) AS total FROM `{$wpdb->prefix}post_access_tokens_log` WHERE {$wpdb->prefix}post_access_tokens_log.status='" . $status . "'" . $search_sql_str ;

        //--

        $logs = $wpdb->get_results( $sql );

        $count_logs = $wpdb->get_results( $sql_count, ARRAY_A );

        $pages = intval( ceil( $count_logs[0]['total'] / $record_per_page ) );

        echo '<div class="token-type-wrapper js-token-type-wrapper clearfix" data-status="' . $status . '">';
        echo '<span class="link token-type js-token-type ';
        if ( $status == 'success' ) echo 'current';
        echo '" data-status="success">' . esc_html__( 'Successful', 'exopite-url-access-tokens' ) . '</span> ';
        echo '<span class="link token-type js-token-type ';
        if ( $status == 'error' ) echo 'current';
        echo '" data-status="error">' . esc_html__( 'Failed', 'exopite-url-access-tokens' ) . '</span>';
        echo '<span class="link token-type js-token-clear-logs danger">' . esc_html__( 'Clear logs', 'exopite-url-access-tokens' ) . '</span>';
        echo '<input class="js-token-list-search" type="search" placeholder="search" value="' . $search . '">';
        echo '</div>';
        echo '<table class="token-log js-token-log" data-sort="' . esc_attr( $_POST['args']['sort'] ) . '" data-order="' . $order . '">';

        echo '<thead><tr>';
        echo '<th class="token-log-time"><span class="link token-log-title js-token-sort" data-sort="time">' . esc_html__( 'Time', 'exopite-url-access-tokens' ) . '</span></th>';
        echo '<th class="token-log-ip"><span class="link token-log-title js-token-sort" data-sort="ip">' . esc_html__( 'IP', 'exopite-url-access-tokens' ) . '</span></th>';
        echo '<th class="token-log-name"><span class="link token-log-title js-token-sort" data-sort="token">' . esc_html__( 'Token', 'exopite-url-access-tokens' ) . '</span></th>';
        if ( ! isset( $_POST['postId'] ) ) echo '<th class="token-log-post-id"><span class="token-log-title">' . esc_html__( 'Post ID', 'exopite-url-access-tokens' ) . '</span></th>';
        echo '<th class="token-log-browser"><span class="token-log-title">' . esc_html__( 'Browser/OS', 'exopite-url-access-tokens' ) . '</span></th>';
        echo '</tr></thead><tbody>';

        //Mozilla Firefox | 59.0 | Windows 10

        foreach ( $logs as $log ) {
            $token = ( isset( $log->token ) ) ? $log->token : '';
            $token_info = ( ! empty( $token ) ) ? $this->main->public->get_token_by_hash( $token ) : '';
            $token_name = ( ! empty( $token_info ) && isset( $token_info['token_name'] ) ) ? $token_info['token_name'] : '';
            echo '<tr>';
            echo '<td class="token-log-time">' . $log->time . '</td>';
            echo '<td class="token-log-ip" title="' . $log->ip . '">' . $log->ip . '</td>';
            $token = ( isset( $token ) && ! empty( $token ) ) ? $token = ' (' . $token .  ')' : '';
            echo '<td class="token-log-name" title="' . $token_name . '">' . $token_name . $token .  '</td>';
            if ( ! isset( $_POST['postId'] ) ) echo '<td class="token-log-post-id" title="Title: ' . get_the_title( $log->post_id ) . ' - ID: ' . $log->post_id . '"><a href="' . get_edit_post_link( $log->post_id ) . '">' . get_the_title( $log->post_id ) . '</a></td>';
            echo '<td class="token-log-browser" title="' . $log->browser . '"><i class="fa fa-info-circle" aria-hidden="true"></i></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<div class="row"><div class="col-xs-12 col-12 col-md-6">' . esc_html__( 'Pages:', 'exopite-post-access-tokens' ) . ' ' . $page . '/' . $pages . '</div>';
        echo '<div class="col-xs-12 col-12 col-md-6 token-log-pages text-right js-token-log-pages">';
        for ( $i = 0; $i < $pages; $i++ ) {
            $current = ( ( $page - 1 ) == $i ) ? ' current' : '';
            echo '<span class="js-token-page btn btn-sm btn-wp' . $current . '" data-page="' . ( $i + 1 ) . '">' . ( $i + 1 ) . '</span>';
        }
        echo '</div></div>';

        die();
    }


    public function generate_pagination() {
        //https://wordpress.stackexchange.com/questions/110126/pagination-keep-prev-and-next-link-even-on-the-first-last-page
    }

    public function get_hashes_list( $args ) {

        $class = 'exopite-post-access-tokens-access-list';
        $attrs = ' data-nonce="' . wp_create_nonce( 'exopite-post-access-tokens-access-list-posts ' ) . '"';

        echo '<div class="' . $class . '"' . $attrs . '>' . esc_html__( 'Loading...', 'exopite-post-access-tokens' ) . '</div>';

    }

    public function ajax_delete_access_log() {
        global $wpdb;
        $delete = $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}post_access_tokens_log`");
        die();
    }

    public function get_all_posts( $post_type_slug ) {

        $query = new WP_Query( array(
            'post_type'      => $post_type_slug,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ));

        $posts_array = array();

        while ( $query->have_posts() ) {
            $query->the_post();

            $posts_array[get_the_ID()] = get_the_title();
        }

        wp_reset_query();

        return $posts_array;

    }

    public function get_post_types_array( $post_type_slug = '' ) {

        $args = array(
           // 'public'   => true,
        );

        $post_types = get_post_types( $args, 'objects' );

        $post_types_array = array();

        $to_skip = array( 'attachment', 'snp_popups', 'snp_ab' );

        foreach ( $post_types as $post_type ) {
            if ( ! empty( $post_type_slug ) ) {
                if ( $post_type->name == $post_type_slug ) return $post_type->label;
            } else {
                if ( in_array( $post_type->name, $to_skip ) ) continue;
                $post_types_array[$post_type->name] = $post_type->label;
            }
        }

        return $post_types_array;

    }

	/**
	 * Create admin menu with
	 * Exopite Simple Options Framework
	 */
    public function create_menu() {

        $options = get_option( $this->plugin_name );
        // $post_types = ( isset( $options['post'] ) && is_array( $options['post'] ) ) ? $options['post'] : array( 'post', 'page' );

        /**
		 * Plugin admin menu
		 */
        $config = array(

            'type'              => 'menu',                          // Required, menu or metabox
            'id'                => $this->plugin_name,              // Required, meta box id, unique per page, to save: get_option( id )
            'parent'            => 'plugins.php',                   // Required, sub page to your options page
            'submenu'           => true,                            // Required for submenu
            'title'             => esc_html__( 'Exopite Access Tokens', 'exopite-url-access-tokens' ),    //The name of this page
            'capability'        => 'manage_options',                // The capability needed to view the page
			'plugin_basename'   =>  plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' ),
			'multilang'			=> false,
            // 'tabbed'            => false,

        );

        $fields[0] = array(
            'name'   => 'general_options',
            'title'  => esc_html__( 'General', 'exopite-url-access-tokens' ),
            'icon'   => 'dashicons-admin-generic',
            'fields' => array(

                // array(
                //     'type'    => 'card',
                //     'class'   => 'class-name', // for all fieds
                //     'content' => esc_html__( '<p>TEXT</p>', 'exopite-url-access-tokens' ),
                //     'header' => 'Information',
                // ),

                array(
                    'id'      => 'activated',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Activate Access Management', 'exopite-url-access-tokens' ),
                    'default' => 'no',
                ),

                array(
                    'id'      => 'use_session',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Use session', 'exopite-url-access-tokens' ),
                    'default' => 'yes',
                    'after'   => '<small class="exopite-sof-info--small">' . esc_html__( 'User tokens will saved in session for 30 days to later access (max 30 tokens). This will create a cookie in user computer.', 'exopite-url-access-tokens' ) . '</small>',
                ),

                array(
                    'id'      => 'log_access',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Log access', 'exopite-url-access-tokens' ),
                    'default' => 'yes',
                    'after'   => '<small class="exopite-sof-info--small">' . esc_html__( 'You can disable to create access logs here. This will not effect file logs.', 'exopite-url-access-tokens' ) . '</small>',
                ),

                array(
                    'id'      => 'log_file',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Log in file', 'exopite-url-access-tokens' ),
                    'default' => 'no',
                    'after'   => '<small class="exopite-sof-info--small">' . esc_html__( 'Note: this will apply always for all notification type, even if you did not added. Create a separate file for each notification type. You will find the log files under:', 'exopite-url-access-tokens' ) . '<br><code>' . plugin_dir_path( __DIR__ ) . 'logs</code><br>' . esc_html__( 'Max size is 1MB, then one backup file will be created and log file will be overwritten.', 'exopite-url-access-tokens' ) . '</small>',
                ),

                array(
                    'id'      => 'admin_notification',
                    'type'    => 'switcher',
                    'title'   => esc_html__( 'Display admin notification', 'exopite-url-access-tokens' ),
                    'default' => 'yes',
                    'after'   => '<small class="exopite-sof-info--small">' . esc_html__( 'Display admin notification in frontend on protected sites.', 'exopite-url-access-tokens' ) . '<br>' . esc_html__( 'Note: Admin can access all protected files with or without token(s).', 'exopite-url-access-tokens' ) . '</small>',
                ),

                array(
                    'id'             => 'post',
                    'type'           => 'select',
                    'title'          => esc_html__( 'Select Post Types', 'exopite-url-access-tokens' ),
					'default'        => array( 'post', 'page' ),
					'query'          => array(
                        'type'          => 'callback',
                        'function'      => array( $this, 'get_post_types_array' ),
                        'args'          => array() // WordPress query args
                    ),
                    // 'options'        => 'callback',
                    // 'query_args'     => array(
                    //     'function'      => array( $this, 'get_post_types_array' ),
                    // ),
                    'default_option' => 'Post types',
                    'class'       => 'chosen',
                    'attributes' => array(
                        'multiple' => 'multiple',
                        'style'    => 'width: 200px; height: 125px;',
                        'placeholder' => esc_html__( 'Select a post types', 'exopite-url-access-tokens' ),
                    ),
                    'after'   => esc_html__( 'to check tokens.', 'exopite-url-access-tokens' ) . '<br><small class="exopite-sof-info--small">' . esc_html__( 'Note: After you change this, please reload the page.', 'exopite-url-access-tokens' ) . '</small>',
                ),

            ),
        );

        $fields[1] = array(
            'name'   => 'tokens_options',
            'title'  => esc_html__( 'Tokens', 'exopite-url-access-tokens' ),
            'icon'   => 'dashicons-admin-generic',
            'fields' => array(

                array(
                    'type'    => 'group',
                    'id'      => 'tokens',
                    'title'   => 'Tokens',
                    'options' => array(
                        'repeater'          => true,
                        'accordion'         => true,
                        'button_title'      => esc_html__( 'Add new', 'exopite-url-access-tokens' ),
                        'accordion_title'   => esc_html__( 'Token Title', 'exopite-url-access-tokens' ),
                        'limit'             => 50,
                        'sortable'          => true,
                    ),
                    'fields'  => array(

                        array(
                            'id'      => 'token_name',
                            'type'    => 'text',
                            'title'   => esc_html__( 'Name', 'exopite-url-access-tokens' ),
                            'attributes' => array(
                                'data-title' => 'title',
                                'placeholder' => esc_html__( 'Name of the token', 'exopite-url-access-tokens' ),
                            ),
                        ),

                        array(
                            'id'      => 'activated',
                            'type'    => 'switcher',
                            'title'   => esc_html__( 'Token Aktive', 'exopite-url-access-tokens' ),
                            'default' => 'no',
                        ),

                        array(
                            'id'      => 'token_hash',
                            'type'    => 'text',
                            'title'   => esc_html__( 'Token', 'exopite-url-access-tokens' ),
                            'class'  => 'token-hash',
                        ),

                        array(
                            'id'     => 'token_from_date',
                            'type'   => 'date',
                            'title'  => esc_html__( 'Valid from', 'exopite-url-access-tokens' ),
                            'format' => 'yy-mm-dd',
                        ),

                        array(
                            'id'     => 'token_until_date',
                            'type'   => 'date',
                            'title'  => esc_html__( 'Valid until', 'exopite-url-access-tokens' ),
                            'format' => 'yy-mm-dd',
                        ),


                    ),

                ),

            ),
        );

        $fields[2] = array(
            'name'   => 'tokens_log',
            'title'  => esc_html__( 'Access Logs', 'exopite-url-access-tokens' ),
            'icon'   => 'dashicons-clock',
            'fields' => array(

                array(
                    'id'      => 'content_tokens',
                    'type'    => 'content',
                    'callback' => array(
                        'function' => array( $this, 'get_hashes_list' ),
                        'args'     => array(
                            'type'     => 'options-hash-list',
                        ),
                    ),
                ),

            ),

        );

        $fields[3] = array(
            'name'   => 'backup_options',
            'title'  => esc_html__( 'Backup', 'exopite-url-access-tokens' ),
            'icon'   => 'dashicons-admin-generic',
            'fields' => array(

                array(
                    'type'    => 'backup',
                    'title'   => esc_html__( 'Backup', 'exopite-url-access-tokens' ),
                ),

            ),
        );


        $posts_to_display = ( isset( $options['post'] ) && is_array( $options['post'] ) ) ? $options['post'] : array( 'post', 'page' );

        foreach ( $posts_to_display as $post_type_slug ) {

            $post_type_obj = get_post_type_object( $post_type_slug );

            $fields[1]['fields'][0]['fields'][] =
                array(
                    'id'             => $post_type_slug,
                    'type'           => 'select',
                    'title'          => $post_type_obj->labels->name,
					// 'title'          => $this->get_post_types_array( $post_type_slug ),
                    'query'          => array(
                        'type'          => 'callback',
                        'function'      => array( $this, 'get_all_posts' ),
                        'args'          => array(
                            'post_type' => $post_type_slug, // WordPress query args
                        ),
                    ),
                    // 'options'        => 'callback',
                    // 'query_args'     => array(
                    //     'function'      => array( $this, 'get_all_posts' ),
                    //     'args'          => $post_type_slug,
                    // ),
                    'class'       => 'chosen',
                    'attributes' => array(
                        'multiple' => 'multiple',
                        'style'    => 'width: 200px; height: 125px;',
                    ),
                );

        }

        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        if ( is_plugin_active( 'exopite-notificator/exopite-notificator.php' ) ) {

            $fields[1]['fields'][0]['fields'][] = array(
                'id'      => 'notification_email',
                'type'    => 'text',
                'title'   => esc_html__( 'Notification via email', 'exopite-url-access-tokens' ),
                // 'class'  => 'token-hash',
                'after'   => '<mute>' . esc_html__( 'comma separated list', 'exopite-url-access-tokens' ) . '</mute>',
            );

            $fields[1]['fields'][0]['fields'][] = array(
                'id'      => 'notification_telegram',
                'type'    => 'text',
                'title'   => esc_html__( 'Notification via telegram', 'exopite-url-access-tokens' ),
                // 'class'  => 'token-hash',
                'after'   => '<mute>' . esc_html__( 'comma separated list', 'exopite-url-access-tokens' ) . '</mute>',
            );

        }

        $options_panel = new Exopite_Simple_Options_Framework( $config, $fields );

    }

    public function get_tokens_hash_by_post_id( $post_id = null ) {

        $post_id = ( $post_id === null ) ? get_the_ID() : $post_id;
        $post_type = get_post_type();
        $now = strtotime( date( "Y-m-d" ) );
        $tokens_hash = array();

        foreach ( $this->main->public->options['tokens'] as $token ) {

            if ( ! $this->main->public->validate_token_time( $now, $token['token_from_date'], $token['token_until_date'] ) ) continue;

            if ( isset( $token[$post_type] ) && is_array( $token[$post_type] ) && in_array( $post_id, $token[$post_type] ) ) {
                $tokens_hash[] = $token['token_hash'];
            }

        }

        return $tokens_hash;

    }

    public function display_info_on_post_submitbox_metabox() {

        $tokens_hash = $this->get_tokens_hash_by_post_id();

        if ( ! empty( $tokens_hash ) ) {

            ?>
            <div class="misc-pub-section" id="token-lock">
            <span style="color:#dc3232;" class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Protected by', 'exopite-url-access-tokens' ); ?> <a href="#" class="show-hide-tokens-js" title="<?php esc_html_e( 'Show tokens', 'exopite-url-access-tokens' ); ?>">token access</a>.
            <div id="show-hide-tokens-js" class="hide-if-js">
            <?php esc_html_e( 'Token(s)', 'exopite-url-access-tokens' ); ?>: <a title="<?php esc_html_e( 'Go to plugin options', 'exopite-url-access-tokens' ); ?>" href="/wp-admin/plugins.php?page=exopite-url-access-tokens&section=tokens_options"><code><?php echo implode( '<br>', $tokens_hash ); ?></code></a>
            </div>
            </div>
            <?php

        }

    }

}
