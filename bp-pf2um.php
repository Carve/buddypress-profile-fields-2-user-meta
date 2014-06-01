<?php
/**
 * Plugin Name: BuddyPress Profile Fields 2 User Meta
 * Plugin URI:  https://github.com/CFCommunity-net/
 * Description: Sync any profile field with usermeta table
 * Version:     1.0
 * Author:      slaFFik
 * Author URI:  http://ovirium.com
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BPPF2UM_VERSION', '1.0' );
define( 'BPPF2UM_PATH',    dirname(__FILE__) ); // without /

/**
 * In case somebody will want to translate the plugin
 */
add_action( 'plugins_loaded', 'bppf2um_load_textdomain' );
function bppf2um_load_textdomain() {
    load_plugin_textdomain( 'bppf2um', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}

/**
 * Admin area
 */
if ( is_admin() ) {
    include_once( BPPF2UM_PATH . '/bp-pf2um-admin.php' );
}

/**
 * Make the sync from profile fields to user_meta as defined in admin area
 *
 * @param $user_id
 * @param $posted_field_ids
 * @param $errors
 * @param $old_values
 * @param $new_values
 */
function bppf2um_profile_sync_fields($user_id, $posted_field_ids, $errors, $old_values, $new_values){
    if ( !empty( $errors ) ) {
        return false;
    }
    /** @var $wpdb WPDB */
    global $wpdb, $bp;

    foreach ($new_values as $field_id => $data) {
        // get meta_key
        $meta_key   = bp_xprofile_get_meta($field_id, 'field', 'user_meta_key');
        $meta_value = '';

        if ( empty($meta_key) ) {
            continue;
        }

        // does this field have options?
        $options = $wpdb->get_results( "SELECT id, name
                                        FROM {$bp->profile->table_name_fields}
                                        WHERE parent_id = '{$field_id}'" );
        if ( !empty($options) ) {
            foreach ($options as $option) {
                // get transition value for this option
                if ( $option->name == $data['value'] ){
                    // get meta_value
                    $meta_value = bp_xprofile_get_meta($option->id, 'field', 'user_meta_value');
                    break;
                } else {
                    $meta_value = $data['value'];
                }
            }
        } else {
            $meta_value = $data['value'];
        }

        bp_update_user_meta( $user_id, $meta_key, $meta_value );
    }
}
add_action( 'xprofile_updated_profile', 'bppf2um_profile_sync_fields', 10, 5 );