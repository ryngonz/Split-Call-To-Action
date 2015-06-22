<?php
/*
Plugin Name: Split Call To Action
Plugin URI: http://www.ryandev.rocks
Description: A double button CTA.
Author: Ryan G. Gonzales
Version: 1.0 beta
Author URI: http://www.ryandev.rocks
License: GNU GPL2
*/
/*  
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/**
 * Calls the class on the post edit screen.
 */
add_action('wp_head','splitcta_script');

function splitcta_script( $template_path ) {
    wp_enqueue_style( 'splitcta-css', plugin_dir_url( __FILE__ ).'css/style.css' );
}


function call_splitcta() {
    new SplitCta();
}

if ( is_admin() ) {
    add_action( 'load-post.php', 'call_splitcta' );
    add_action( 'load-post-new.php', 'call_splitcta' );
}

add_filter( 'the_content', 'prefix_insert_splitcta' );

function prefix_insert_splitcta( $content ) {
    global $post;

    // Use get_post_meta to retrieve an existing value from the database.
    $value = get_post_meta( $post->ID, '_splitcta_value_key', true );

    if ( is_single() && $value['enable'] == 1 ) {
        $get_code = get_splitcta_template($value);
        return $content . $get_code;
    }
    
    return $content;
}

function get_splitcta_template($splitcta){
    $cta_code = "<div class='splitcta_container'>";
    $cta_code .= "<div class='splitcta_content'>";
    $cta_code .= "<h2>".$splitcta['content']."</h2>";
    $cta_code .= "</div>";
    $cta_code .= "<div class='splitcta_buttons'>";
    $cta_code .= "<a href='".$splitcta['a_url']."' class='a_button splitcta_btn'>".$splitcta['a_label']."</a>";
    $cta_code .= " or ";
    $cta_code .= "<a href='".$splitcta['b_url']."' class='b_button splitcta_btn'>".$splitcta['b_label']."</a>";
    $cta_code .= "</div>";
    $cta_code .= "</div>";
    return $cta_code;
}

/** 
 * The Class.
 */
class SplitCta {

    /**
     * Hook into the appropriate actions when the class is constructed.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save' ) );
    }

    /**
     * Adds the meta box container.
     */
    public function add_meta_box( $post_type ) {
            $post_types = array('post', 'page');     //limit meta box to certain post types
            if ( in_array( $post_type, $post_types )) {
        add_meta_box(
            'splitcta_meta_box'
            ,__( 'Split Call To Action', 'splitcta_textdomain' )
            ,array( $this, 'render_meta_box_content' )
            ,$post_type
            ,'advanced'
            ,'high'
        );
            }
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save( $post_id ) {
    
        /*
         * We need to verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times.
         */

        // Check if our nonce is set.
        if ( ! isset( $_POST['splitcta_inner_custom_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['splitcta_inner_custom_box_nonce'];

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'splitcta_inner_custom_box' ) )
            return $post_id;

        // If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
            return $post_id;

        // Check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;
    
        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        /* OK, its safe for us to save the data now. */

        $mydata = $_POST['splitcta_fields'];

        // Update the meta field.
        update_post_meta( $post_id, '_splitcta_value_key', $mydata );
    }


    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post The post object.
     */
    public function render_meta_box_content( $post ) {
    
        // Add an nonce field so we can check for it later.
        wp_nonce_field( 'splitcta_inner_custom_box', 'splitcta_inner_custom_box_nonce' );

        // Use get_post_meta to retrieve an existing value from the database.
        $value = get_post_meta( $post->ID, '_splitcta_value_key', true );

        $checked = "";
        if($value['enable'] == 1) $checked = "checked";

        echo '<p><label for="splitcta_fields">';
        _e( 'Enable Split CTA:', 'splitcta_enable' );
        echo '</label> ';
        echo '<input type="checkbox" id="splitcta_enable" name="splitcta_fields[enable]"';
                echo ' value="1" ' . $checked . ' /></p>';


        echo '<p><label for="splitcta_fields">';
        _e( 'Intro Content:', 'splitcta_content' );
        echo '</label> ';
        echo '<input type="text" id="splitcta_fields" name="splitcta_fields[content]"';
                echo ' value="' . esc_attr( $value['content'] ) . '" style="width:100%" /></p>';

        echo '<p><label for="splitcta_fields">';
        _e( 'A Button Label:', 'splitcta_yes_label' );
        echo '</label> ';
        echo '<input type="text" id="splitcta_fields" name="splitcta_fields[a_label]"';
                echo ' value="' . esc_attr( $value['a_label'] ) . '" style="width:100%" /></p>';

        echo '<p><label for="splitcta_fields">';
        _e( 'A Button URL:', 'splitcta_yes_label' );
        echo '</label> ';
        echo '<input type="text" id="splitcta_fields" name="splitcta_fields[a_url]"';
                echo ' value="' . esc_attr( $value['a_url'] ) . '" style="width:100%" /></p>';

        echo '<p><label for="splitcta_fields">';
        _e( 'B Button Label:', 'splitcta_b_label' );
        echo '</label> ';
        echo '<input type="text" id="splitcta_fields" name="splitcta_fields[b_label]"';
                echo ' value="' . esc_attr( $value['b_label'] ) . '" style="width:100%" /></p>';

        echo '<p><label for="splitcta_fields">';
        _e( 'B Button URL:', 'splitcta_b_url' );
        echo '</label> ';
        echo '<input type="text" id="splitcta_fields" name="splitcta_fields[b_url]"';
                echo ' value="' . esc_attr( $value['b_url'] ) . '" style="width:100%" /></p>';
    }
}

?>