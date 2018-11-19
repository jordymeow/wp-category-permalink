<?php

abstract class MWCPPost
{
    public static function transition_post_status( $new_status, $old_status, $post )
    {
        if ( !isset( $_POST['permalinkTaxa'] ) || !is_array( $_POST['permalinkTaxa'] ) )
            return;
        $permalink_taxa = $_POST['permalinkTaxa'];
        $permalink_cat = MWCPPost::getPermalinkMeta( $post->ID );
        $permalink_taxa = array_merge( $permalink_cat, $permalink_taxa );
        MWCPPost::setPermalinkMeta( $post->ID, $permalink_taxa );
    }

    public static function post_link_category( $default, $cats, $post )
    {
        $permalink_cat = MWCPPost::getPermalinkMeta( $post->ID );
        if ( !isset( $permalink_cat['category'] ) )
            return $default;
        // Find the category object for this
        foreach ( $cats as $cat )
            if ( $cat->term_id == $permalink_cat['category'] )
                return $cat;
        return $default;
    }

    public static function post_type_link( $url, $post, $leavename, $sample )
    {
        global $MWCP_Admin;

        // A list of the only post types we are concerned with.
        $post_types = MWCPPost::post_types();

        if ( !isset( $post_types[$post->post_type] ) )
          return $url;

        // Find and replace these values in the permalink_structure
        $find = array( '%year%', '%monthnum%', '%day%', '%hour%', '%minute%', '%second%', '%post_id%', '%postname%', '%author%' );
        $replace = explode( '|', get_the_date( 'Y|m|d|H|i|s', $post->ID ) );
        $replace[] = $post->ID;
        $replace[] = $post->post_name;
        $replace[] = get_the_author_meta( 'user_nicename', $post->post_author );
        $permalink_taxa = MWCPPost::getPermalinkMeta( $post->ID );

        // That works well with CPT UI and Custom Post Type Permalinks
        $find[] = '%' . $post->post_type . '_slug%';
        $replace[] = $post_types[$post->post_type]->rewrite['slug'];

        if ( !$leavename ) {
          $find[] = '%' . $post->post_type . '%';
          $replace[] = $post->post_name;
        }

        foreach ( MWCPPost::taxonomies( $post->post_type ) as $info ) {
            $query_var = $info->name;
            $term_id = 0;
            if ( isset( $permalink_taxa[$query_var] ) ) {
              $term_id = $permalink_taxa[$query_var];
            }
            else
            {
                $terms = get_the_terms( $post->ID, $query_var );
                if ( is_wp_error( $terms ) ) {
                  error_log( $terms->get_error_message() );
                  die( "An error occured in WP Category Permalink." );
                }
                if ( !empty( $terms ) )
                    $term_id = $terms[0]->term_id;
            }
            if ( !empty( $term_id ) ) {
              $find[] = '%' . $query_var . '%';
              $by = self::get_taxonomy_parents( $term_id, $query_var, false, '/', true );
              $replace[] = $by;
            }
            else {
              $find[] = '/%' . $query_var . '%';
              $by = '';
              $replace[] = $by;
            }
        }

        // Base the url on the post_type slug, not the $url.
        global $wp_rewrite;
        $post_link = $wp_rewrite->get_extra_permastruct( $post->post_type );
        $slug = $post_types[$post->post_type]->rewrite['slug'];
        $slug = trailingslashit( site_url( trailingslashit( $slug ) . '%postname%' ) );
        $newUrl = str_replace( $find, $replace, $post_link );
        $newUrl = trailingslashit( trim( site_url( $newUrl ) ) );
        //echo "post_link: $post_link<br />leavename: $leavename<br/>slug: $slug<br/>url: $url<br/>newUrl: $newUrl<br /><br />";
        return $newUrl;
    }

    /**
    * Retrieve category parents with separator for general taxonomies.
    * Modified version of get_category_parents()
    *
    * @param int $id Category ID.
    * @param string $taxonomy Optional, default is 'category'.
    * @param bool $link Optional, default is false. Whether to format with link.
    * @param string $separator Optional, default is '/'. How to separate categories.
    * @param bool $nicename Optional, default is false. Whether to use nice name for display.
    * @param array $visited Optional. Already linked to categories to prevent duplicates.
    * @return string
    */
    protected static function get_taxonomy_parents( $id, $taxonomy = 'category', $link = false, $separator = '/', $nicename = false, $visited = array() )
    {
        $chain = array();
        $parent = get_term( $id, $taxonomy );

        if ( is_wp_error( $parent ) ) {
          return '-';
        }

        $name = $nicename ? $parent->slug : $parent->name;

        if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) )
        {
            $visited[] = $parent->parent;
            $chain[] = self::get_taxonomy_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
        }

        if ( $link )
        {
            $chain[] = '<a href="' . esc_url( get_term_link( $parent,$taxonomy ) ) .
            '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) .
            '">' . $name . '</a>';
        }
        else
        {
            $chain[] = $name;
        }

        return implode( $separator, $chain );
    }

    public static function post_types()
    {
        static $post_types;

        if ( !$post_types )
        {
            $post_types = get_post_types( array( '_builtin' => 0, 'public' => 1 ), 'objects' );

            if ( !is_array( $post_types ) )
            {
                $post_types = array();
            }
        }

        return $post_types;
    }

    public static function taxonomies( $post_type )
    {
        static $taxa;

        if ( !is_array( $taxa ) )
        {
            $taxa = get_taxonomies( array( 'public' => 1, 'hierarchical' => 1 ), 'objects' );
            $taxa = array_filter(
                $taxa,
                function( $a ) {
                    return !!$a->rewrite;
                }
            );
        }

        $return = array();

        foreach ( $taxa as $taxon )
        {
            if ( in_array( $post_type, $taxon->object_type ) )
            {
                $return[] = $taxon;
            }
        }

        return $return;
    }

    public static function getPermalinkMeta( $post_id )
    {
        $permalink_meta = get_post_meta( $post_id, '_category_permalink', true );

        if ( empty( $permalink_meta ) )
        {
            return array();
        }

        // Old version -> integer, new version -> array
        if ( !is_array( $permalink_meta ) )
        {
            $permalink_meta = array( 'category' => $permalink_meta );
        }

        return $permalink_meta;
    }

    public static function setPermalinkMeta( $post_id, $permalink_meta = null )
    {
        if ( is_array( $permalink_meta ) )
        {
            update_post_meta( $post_id, '_category_permalink', $permalink_meta );
        }
        else
        {
            delete_post_meta( $post_id, '_category_permalink' );
        }
    }
}
