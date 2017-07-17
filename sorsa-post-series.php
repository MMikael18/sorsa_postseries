<?php
/*
Plugin Name: Sorsa Post Series
*/

// add css file to front end
function sorsa_wp_enqueue_scripts() {
	wp_enqueue_style( 'sorsa_post_list_box', plugins_url( 'post_list_box.css', __FILE__ ), false );
}
add_action( 'wp_enqueue_scripts', 'sorsa_wp_enqueue_scripts' );

/*
Custom Tag to series name
Viewed list is formed to base to of tag name.
*/

function sorsa_postseries_taxonomy() {
    $args = array(
            'label' => _x('Post series', 'sorsa_postseries'),
            'hierarchical' => false,            
            'query_var' => true,
            'meta_box_cb' => 'sorsa_postseries_meta_box',
            'rewrite' => array(
                'slug' => 'series',
                'with_front' => false
            )
        );

    register_taxonomy(
        'postseries',
        'post',
        $args
    );
}
add_action( 'init', 'sorsa_postseries_taxonomy');

/*
Meta box right to post edit
*/

function sorsa_postseries_meta_box($post, $box){
    
    ?>
	<input type="hidden" name="sorsa_postseries_meta_box_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">
    <?php

    // all series terms
    $terms_list = get_terms( array(
        'taxonomy' => 'postseries',
        'hide_empty' => false
    ));
    // now values
    $termsnow = get_the_terms( $post->ID, 'postseries', true )[0]->name;    
    $order = get_post_meta( $post->ID, 'series_order', true );
    
    // form
    ?>
    
    <p class="post-attributes-label-wrapper"><label class="post-attributes-label"><?php _e("Name of series","sorsa_postseries"); ?></label></p>
    <input type="text" name="postseries" list="postserieslist" autocomplete="off" id="postseries" class="" value="<?php echo $termsnow; ?>">
    <datalist id="postserieslist">
        <?php foreach ( $terms_list as $tml ) echo "<option value='".$tml->name."'>"; ?>
    </datalist>

    <p class="post-attributes-label-wrapper"><label class="post-attributes-label"><?php _e("Order","sorsa_postseries"); ?></label></p>	
    <input type="text" name="series_order" id="series_order" class="" value="<?php echo $order; ?>">
    <p class="howto" id="new-tag-post_tag-desc"><?php _e("Order numper or letter. exp. 1, 01, a, ab","sorsa_postseries"); ?></p>

    <?php
}

/* 
Save values in meta box
*/

function sorsa_save_series_fields_meta( $post_id ) {   
	// verify nonce
	if ( !wp_verify_nonce( $_POST['sorsa_postseries_meta_box_nonce'], basename(__FILE__) ) )  return $post_id; 
	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
	// check permissions
	if ( 'page' === $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )     return $post_id;
		elseif ( !current_user_can( 'edit_post', $post_id ) ) return $post_id;
	}

	// name series
    $post = get_post($post_id);
    $postseries = empty($_POST['postseries']) ? null : $_POST['postseries'] ;

    // order numper
    $old = get_post_meta( $post_id, 'series_order', true );
    $new = strlen($_POST['series_order']) > 0 ? $_POST['series_order'] : 0 ;

    if (($post->post_type == 'post') || ($post->post_type == 'page')) {        
        // save tax or remove
        wp_set_object_terms( $post_id, $postseries, 'postseries' );
        // set numper if there is name
        if(isset($postseries)){
            update_post_meta( $post_id, 'series_order', $new );
        }
    }

    // remove numper if no series name and there is old numper
    if (is_null($postseries) ) {
		delete_post_meta( $post_id, 'series_order', $old );
	}

}
add_action( 'save_post', 'sorsa_save_series_fields_meta' );

/*
Print links list front of content if Series tag exist and there is more that one link. 
*/

function sorsa_postseries_before_content($content){
    if (is_single() && !empty($content)) {

        $termsnow = get_the_terms( get_the_ID(), 'postseries', true )[0]->slug;
        $siteID = get_the_ID();

        if(isset($termsnow)){
            
            global $post;
            $args = array( 
                'postseries' => $termsnow, 
                'meta_key' => 'series_order', 
                'orderby' => 'meta_value',
                'order' => 'ASC' );

            $myposts = get_posts( $args );
            
            ?>
            <div id="post_list_box">
            <ul>        
            <strong><?php echo $termsnow ?></strong>
            <?php
            
            foreach ( $myposts as $post ) : setup_postdata( $post ); 
                $num = get_post_meta( $post->ID, 'series_order', true );
                $num = $num == "0" ? "" : $num.". " ;

                $class = $siteID == $post->ID  ? "current" : "";

                ?>
                <li>
                    <a href="<?php the_permalink(); ?>" class="<?php echo $class ?>">
                        <?php echo $num . get_the_title(); ?>
                    </a>
                </li>
                <?php
            endforeach; 
            wp_reset_postdata();

            ?>
            </ul>
            </div>        
            <?php

        }
    }
	return $content;
}
add_filter( "the_content", "sorsa_postseries_before_content" );

?>