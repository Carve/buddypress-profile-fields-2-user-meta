<?php

/**
 * Adding our subpages to Groups
 */
function bppf2um_admin_init() {
    add_action( bp_core_admin_hook(), 'bppf2um_admin_menu', 99 );
}
add_action( 'bp_init', 'bppf2um_admin_init' );

function bppf2um_admin_menu(){
    add_submenu_page(
        'users.php',
        __( 'Profile Fields 2 User Meta', 'bppf2um' ),
        __( 'Profile Fields 2 User Meta', 'bppf2um' ),
        'bp_moderate',
        'bppf2um-admin',
        'bppf2um_admin'
    );

    if ( isset($_POST['bppf2um_save']) && $_GET['page'] == 'bppf2um-admin' ) {
        bppf2um_admin_save();
    }
}

function bppf2um_admin(){
    /** @var $wpdb WPDB */
    global $wpdb, $bp;
    $groups = $wpdb->get_results("SELECT * FROM {$bp->profile->table_name_groups} ORDER BY group_order ASC");

    wp_enqueue_script('jquery-ui-tabs');
    ?>

    <div class="wrap">
        <?php screen_icon( 'buddypress-users' ); ?>
        <h2>
            <?php _e( 'Sync Profile Fields & User Meta', 'bppf2um' ); ?>
        </h2>

        <?php
        if ( isset($_GET['status']) && $_GET['status'] == 'saved' ) {
            echo '<div id="message" class="updated"><p>'.__('Mapping was successfully saved.', 'bppf2um').'</p></div>';
        }
        ?>

        <form action="" method="POST">

            <div id="tabs">
                <ul class="nav-tab-wrapper">
                    <?php
                    $i = 0;
                    foreach($groups as $group) {
                        $class = $i == 0 ? 'nav-tab-active' : '';
                        echo '<li style="float:left"><a href="#group-'.$group->id.'" class="nav-tab '.$class.'">'.$group->name.'</a></li>';
                        $i++;
                    }
                    ?>
                </ul>

                <div class="clear"></div>

                <?php foreach($groups as $group) { ?>
                    <div id="group-<?=$group->id;?>">
                        <p class="description"><?=$group->description;?></p>

                        <?php
                        $fields = $wpdb->get_results( "SELECT id, name, description, type, parent_id
                                                        FROM {$bp->profile->table_name_fields}
                                                        WHERE group_id = '{$group->id}'
                                                          AND parent_id = 0
                                                        ORDER BY field_order ASC" );

                        echo '<p>'. sprintf(__('Save value of the field to <code>%s</code> table with the defined <code>meta_key</code>:', 'bppf2um'), $wpdb->usermeta ) . '</p>'; ?>
                        <table class="form-table">
                            <?php
                            foreach($fields as $field) { ?>

                                <tr>
                                    <th scope="row"><label for="field_<?=$field->id;?>"><?=stripslashes($field->name); ?></label></th>
                                    <td>
                                        <input name="bppf2um[fields][<?=$field->id;?>]" type="text" id="field_<?=$field->id;?>" class="regular-text" value="<?php echo bp_xprofile_get_meta($field->id, 'field', 'user_meta_key');?>">
                                        <?php
                                        $options = $wpdb->get_results( "SELECT id, name
                                                    FROM {$bp->profile->table_name_fields}
                                                    WHERE parent_id = '{$field->id}'
                                                    ORDER BY field_order ASC" );
                                        if ( !empty($options) ) {
                                            echo '<ul style="margin-left:20px;list-style-type:circle">';
                                            foreach( $options as $option ) {
                                                echo '<li>'.
                                                        sprintf(
                                                            __('value for %1$s option save as %2$s <code>meta_value</code>', 'bppf2um'),
                                                            '<strong>'.$option->name.'</strong>',
                                                            '<input name="bppf2um[options]['.$option->id.']" type="text" style="width:100px" value="'.bp_xprofile_get_meta($option->id, 'field', 'user_meta_value').'">'
                                                        ).
                                                     '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                <?php } ?>
            </div>

            <hr />

            <p class="description"><?php _e('If you don\'t want to sync profile fields with user meta - just leave appropriate <code>meta_key</code> inputs empty.', 'bppf2um'); ?></p>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Mapping', 'bppf2um'); ?>" name="bppf2um_save">
            </p>

        </form>
    </div>

    <script>
        jQuery(document).ready(function() {
            jQuery("#tabs").tabs();
        });
    </script>

    <?php
}

function bppf2um_admin_save(){
    if ( empty($_POST['bppf2um']) ) {
        return false;
    }

    foreach($_POST['bppf2um']['fields'] as $field_id => $meta_key) {
        bp_xprofile_update_field_meta($field_id, 'user_meta_key', $meta_key);
    }

    if ( isset($_POST['bppf2um']['options']) && !empty($_POST['bppf2um']['options']) ) {
        foreach($_POST['bppf2um']['options'] as $option_id => $meta_value) {
            bp_xprofile_update_meta($option_id, 'field', 'user_meta_value', $meta_value);
        }
    }

    wp_redirect(add_query_arg('status', 'saved'));
    exit();
}