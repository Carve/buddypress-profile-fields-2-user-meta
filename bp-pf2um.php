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
 * @param $field_id int ID of a saved field
 * @param $value string New value of a field
 *
 * @return bool
 */
function bppf2um_profile_prepare_sync_field($field_id, $value){
    if ( is_admin() ) {
        $user_id = (int) $_GET['user_id'];
    } else{
        $user_id = bp_displayed_user_id();
    }

    return bppf2um_profile_do_sync_field($user_id, $field_id, $value);
}
add_action( 'xprofile_profile_field_data_updated', 'bppf2um_profile_prepare_sync_field', 10, 2 );

/**
 * Actually do the sync
 *
 * @param $user_id
 * @param $field_id
 * @param $value
 * @return bool
 */
function bppf2um_profile_do_sync_field($user_id, $field_id, $value){
    /** @var $wpdb WPDB */
    global $wpdb, $bp;

    // get meta_key
    $meta_key   = bp_xprofile_get_meta($field_id, 'field', 'user_meta_key');
    $meta_value = '';

    if ( empty($meta_key) ) {
        return false;
    }

    // does this field have options?
    $options = $wpdb->get_results( "SELECT id, name
                                    FROM {$bp->profile->table_name_fields}
                                    WHERE parent_id = '{$field_id}'" );
    if ( !empty($options) ) {
        foreach ($options as $option) {
            // get transition value for this option
            if ( $option->name == $value ){
                // get meta_value
                $meta_value = bp_xprofile_get_meta($option->id, 'field', 'user_meta_value');
                break;
            } else {
                $meta_value = $value;
            }
        }
    } else {
        $meta_value = $value;
    }

    return bp_update_user_meta( $user_id, $meta_key, $meta_value );
}

/**
 * Save RSS feed link to usermeta after user account activation
 *
 * @param $user_id int
 * @param $key string
 * @param $user array
 */
function bppf2um_after_signup_fields_sync($user_id, $key, $user){
    /** @var $wpdb WPDB */
    global $wpdb, $bp;

    // get fields from group 1 (they are on reg page)
    $fields = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields}
                                WHERE id IN ({$user['meta']['profile_field_ids']})");

    if ( !empty($fields) ) {
        foreach ( $fields as $field_id ) {
            bppf2um_profile_do_sync_field($user_id, $field_id, $user['meta']['field_'.$field_id]);
        }
    }
}
add_action( 'bp_core_activated_user', 'bppf2um_after_signup_fields_sync', 10, 3 );