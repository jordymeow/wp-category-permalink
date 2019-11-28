<?php

abstract class MWCPUI
{
    public static function post_css()
    {
        ?>
        <style type="text/css">
        .taxa-value-selector {
            vertical-align:middle;
            display:none;
            cursor:pointer;
            cursor:hand;
        }

        ul.categorychecklist li { white-space: nowrap; }
        </style>
        <?php
    }

    public static function enqueue_script()
    {
        $url = plugins_url('/wp-category-permalink.js', dirname(__FILE__) . '/../' );

        wp_enqueue_script( 'wp-category-permalink.js', $url, array( 'jquery' ), '3.2.3', false );
        
        // Register the Gutenberg javascript component
        wp_enqueue_script( 
            'wp-category-permalink-gutenberg.js', 
            plugins_url('wp-category-permalink-gutenberg.js', __FILE__),             
            [
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-editor',
                'wp-edit-post',
                'wp-components',
                'wp-data',
                'wp-plugins',
                'wp-edit-post',
                'wp-api'
            ], 
            false, 
            false 
        );
    }

    public static function post_js()
    {
        global $post;
        global $MWCP_Admin;

        $options = array();

        if ( $post->ID )
          $options['current'] = MWCPPost::getPermalinkMeta( $post->ID );
        $MWCP_Admin = new MWCP_Admin();
        $selector = '[id^="taxonomy-"].categorydiv';

        ?>
        <script type="text/javascript">
        jQuery(function ($) {
            var re = /taxonomy-(.+)/;
            $(<?php echo json_encode($selector); ?>)
            .each(function () {
                var m = $(this).attr('id').match(re);
                $(this).data('taxonomy', m[1]);
            })
            .sCategoryPermalink(<?php echo json_encode((object) $options); ?>);
        });
        </script>
        <?php
    }

    /**
    * Add the permalink category to the list of columns that can be displayed on the manage posts screen.
    * @todo    Make it work with multiple taxonomies.
    *
    * @param   [type]  $columns  [description]
    *
    * @return  [type]
    */
    public static function manage_posts_columns( $columns )
    {
        global $post_type;
        global $MWCP_Admin;
        $option = 'manageedit-' . $post_type . 'columnshidden';
        $hidden_columns = (array) get_user_option( $option );
        $query = array(
            'public'       => true,
            'hierarchical' => true,
        );
        $taxa = get_taxonomies( $query, 'objects' );
        foreach ( $taxa as $taxon => $info )
        {
            if ( !in_array( $post_type, $info->object_type ) )
                continue;
            $column_key = $taxon . '_permalink';
            if ( isset( $columns[$column_key] ) )
                continue;
            $columns[$column_key] = __( 'Permalink ' . $info->labels->singular_name, 'wp-category-permalink' );
            $hidden_columns[] = $column_key;
        }
        return $columns;
    }

    public static function post_row_actions( $actions, $post )
    {
        $hide_permalink = get_option( 'wpcp_hide_permalink', false );
        if ( $hide_permalink )
            return $actions;
        $permalink_cat = MWCPPost::getPermalinkMeta( $post->ID );
        $isset = false;
        foreach ( $permalink_cat as $taxa => $cat ) {
          if ( !empty( $cat ) ) {
            $isset = true;
            break;
          }
        }

        $dashicon = !$isset ? '' : '<span class="dashicons dashicons-heart" style="font-size: 12px; color: rgba(255, 0, 0, 0.65); margin: 1px 0px 0px 0px; width: auto; height: auto; line-height: inherit;"></span>';
        $permalink = get_permalink( $post->ID );
        if ( get_post_type( $post ) !== 'post' )
          echo '<br />';
        echo '<small style="position: relative; top: -3px;">' . $dashicon . '&nbsp;' . $permalink . '</small>';
        return $actions;
    }

    /**
    * Displays a custom column on the manage posts screen
    *
    * @param   [type]  $column   [description]
    * @param   [type]  $post_id  [description]
    *
    * @return  [type]
    */
    public static function manage_posts_custom_column( $column, $post_id )
    {
        global $post_type;
        $taxonomy = null;
        $query = array(
            'public'       => true,
            'hierarchical' => true,
        );
        $taxa = get_taxonomies( $query, 'objects' );
        foreach ( $taxa as $taxon => $info ) {
            if ( $column === $taxon . '_permalink' ) {
                $taxonomy = $taxon;
                break;
            }
        }
        if ( empty( $taxonomy ) )
            return;

        $permalink_cat = MWCPPost::getPermalinkMeta( $post_id );
        $term_id = isset( $permalink_cat[$taxonomy] ) ? $permalink_cat[$taxonomy] : null;
        $term_name = null;

        if ( isset( $permalink_cat[$taxonomy] ) )
        {
            $term_id = $permalink_cat[$taxonomy];
        }

        if ( $term_id )
        {
            $term = get_term( $term_id, $taxonomy );
            if ( empty( $term ) || is_wp_error( $term ) )
                return;
            $term_name = $term->name;
        }
        elseif ( $taxonomy === 'category' )
        {
            $cat = get_the_category( $post_id );
            if ( empty( $cat ) || is_wp_error( $cat ) )
                return;
            if ( count( $cat ) > 1 )
                $term_name = '<span style="color: red;">' . $cat[0]->name . '</span>';
            else
                $term_name = $cat[0]->name;
        }
        else
            $term_name = '-';

        ?>
        <span class="scategory_permalink_name" data-taxonomy="<?php echo $taxonomy; ?>">
            <?php echo $term_name; ?>
        </span>
        <?php
    }
}
