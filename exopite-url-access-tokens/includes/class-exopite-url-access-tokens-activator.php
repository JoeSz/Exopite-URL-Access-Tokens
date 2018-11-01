<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.joeszalai.org/
 * @since      1.0.0
 *
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Exopite_Url_Access_Tokens
 * @subpackage Exopite_Url_Access_Tokens/includes
 * @author     Joe Szalai <joe@joeszalai.org>
 */
class Exopite_Url_Access_Tokens_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

        self::create_db();

	}

    public static function create_db() {

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        /**
         * Store access logs
         */
        $sql[] = "CREATE TABLE " . $wpdb->prefix . "post_access_tokens_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ip tinytext,
            time datetime DEFAULT '0000-00-00 00:00:00',
            token varchar(128) DEFAULT '',
            post_id mediumint,
            browser text,
            title text,
            status tinytext,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        /**
         * It seems IF NOT EXISTS isn't needed if you're using dbDelta - if the table already exists it'll
         * compare the schema and update it instead of overwriting the whole table.
         *
         * @link https://code.tutsplus.com/tutorials/custom-database-tables-maintaining-the-database--wp-28455
         */
        dbDelta( $sql );

    }

}
