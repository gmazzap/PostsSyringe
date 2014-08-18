<?php
/**
 * Plugin Name:       Posts Syringe
 * Description:       Helps you to automatically inject CPT posts into the main loop.
 * Plugin URI:        https://github.com/giuseppe-mazzapica/PostsSyringe
 * Author:            Giuseppe Mazzapica
 * Author URI:        https://github.com/giuseppe-mazzapica
 * Requires at least: 3.9
 * Version:           0.0.1
 * License:           GPLv2+
 */
/*
  Please Note: This plugin require PHP 5.3+
 */
/*
  Copyright 2014 by Giuseppe Mazzapica

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/*
 * Even if completely rewritten, this plugin is derived from "Sponsor Posts Injector"
 * https://gist.github.com/birgire/439e36a6b08f1159f653
 * (c) 2014 by Birgir Erlendsson https://github.com/birgire
 *
 * "Sponsor Posts Injector" is released under GPLv2+
 *
 */

/*
 *
 * Example of usage:
 *
 * add_action( 'pre_get_posts', function( $query ) {
 *
 *   if ( $query->is_main_query() && ( $query->is_home() || $query->is_archive() ) ) {
 *
 *     posts_syringe(
 *       'sponsor',
 *       array( 'before_each_inject' => 3, 'per_inject' => 1 ),
 *       array( 'meta_key' => 'featured', 'meta_value' => '1' )
 *     );
 *
 *   }
 *
 * } );
 *
 * Code above injects 1 'sponsor' post every 3 posts in the main loop, in home page or archive pages.
 * Only sponsor posts having meta key 'featured' equal to '1' will be queried and injected.
 *
 */

if ( ! function_exists( 'posts_syringe' ) ) {

    /**
     * The function that makes everything works. It must be called before (or at leas during) 'wp'
     * action hook.
     *
     * @param array|string $cpt The CPT(s) to inject in main loop
     * @param array $args       Arguments that control how posts are injected. Accepted values:
     *                          'before_each_inject':   number of main-loop posts to display
     *                                                  before inject CPT posts. Default 1.
     *                          'per_inject':           number of posts to inject on every
     *                                                  injection cycle. Default 1.
     * @param array $query_args Additional query args to query CPT(s) posts to injects.
     *                          `'post_type'` and `'posts_per_page'` are set automatically, use this
     *                          argument for additional query args, e.g. `'tax_query'` and so on
     * @return void
     * @since 0.0.1
     */
    function posts_syringe( $cpt, Array $args = array (), Array $query_args = array () ) {
        if (
            is_admin() || ( ! is_array( $cpt ) && ! is_string( $cpt ) ) || did_action( 'wp' )
        ) {
            return;
        }
        if ( is_array( $cpt ) ) {
            $cpt = array_filter( $cpt, 'post_type_exists' );
            if ( empty( $cpt ) ) {
                return;
            }
        } elseif ( ! post_type_exists( $cpt ) ) {
            return;
        }
        require_once plugin_dir_path( __FILE__ ) . 'class-gm_posts_syringe_query.php';
        require_once plugin_dir_path( __FILE__ ) . 'class-gm_posts_syringe_injector.php';
        $syringe = new GM\PostsSyringe\Query( $cpt, new GM\PostsSyringe\Injector );
        $syringe->init( $args );
        $base = [ 'post_type' => $cpt ];
        $all_query_args = ! empty( $query_args ) ? array_merge( $query_args, $base ) : $base;
        $syringe->setQueryArgs( $all_query_args );
        add_filter( 'the_posts', [ $syringe, 'start' ], PHP_INT_MAX, 2 );
    }

}

if ( ! function_exists( 'is_syringe_injected' ) ) {

    /**
     * Template tag that can be used to know if a post has been injected via plugin to posts result.
     * If a post is not injected return false, otherwise if an id was set using 'id' argument
     * for `posts_syringe()` then it is returned, otherwise boolean `TRUE` is returned.
     *
     * @param \WP_Post $post The post object to check
     * @return bool|string
     * @since 0.0.1
     */
    function is_syringe_injected( $post = NULL ) {
        if ( is_null( $post ) ) {
            global $post;
        }
        return is_object( $post )
            && isset( $post->syringe_injector )
            && $post->syringe_injector instanceof GM\PostsSyringe\Injector;
    }

}

if ( ! function_exists( 'get_post_syringe' ) ) {

    /**
     * Template tag that can be used to get the Injector instance that had injected the post.
     * Can be useful when more than one `posts_syringe()` call are running for the current query
     * and one want to use different layouts for different injection.
     *
     * @param \WP_Post $post The post object to check
     * @return GM\PostsSyringe\Injector|void
     * @since 0.0.1
     */
    function get_post_syringe( $post = NULL ) {
        if ( is_null( $post ) ) {
            global $post;
        }
        return is_syringe_injected( $post ) ? $post->syringe_injector : NULL;
    }

}
