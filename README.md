PostsSyringe
============

WordPress plugin that helps you to automatically inject CPT posts into the main loop. requires PHP 5.3.

Posts can be injected using `posts_syringe()` function.

It accepts 3 arguments:

 1. array|string **`$cpt`** The CPT(s) to inject in the main loop
 2. array **`$args`** Arguments that control how posts are injected. Accepted values:
    - 'before_each_inject':  number of main-loop posts to display before inject CPT posts. Default 1.
    - 'per_inject':          number of posts to inject on every injection cycle. Default 1.
 3. array **`$query_args`** Additional query args to query posts to injects. `'post_type'` and `'posts_per_page'` are set automatically, use this argument for additional query args, e.g. `'tax_query'` and so on

##Quick Example##

    add_action( 'pre_get_posts', function( $query ) {

      if ( $query->is_main_query() && ( $query->is_home() || $query->is_archive() ) ) {

        posts_syringe(
          'sponsor',
          array( 'before_each_inject' => 3, 'per_inject' => 1 ),
          array( 'meta_key' => 'featured', 'meta_value' => '1' )
        );

      }

    } );
    
Code above injects 1 'sponsor' post every 3 posts in the main loop, in home page or archive pages.
Only sponsor posts having meta key 'featured' equal to '1' will be queried and injected.

Note that is possible to use more than one `posts_syringe()` calls with different arguments at same time.

Posts are injected in the main loop and they will be trated as posts being part of it.

However, inside templates is possible to distinguish from *regular* main loop posts from ones that were been injected.
That is possible thanks to the `is_syringe_injected()` template tag.

Example:

    while ( have_posts() ) : the_post(); ?>
				
				if ( is_syringe_injected() ) {
					get_template_part( 'sponsor-entry' );
				} else {
          get_template_part( 'content', get_post_format() );
				}
				
		endwhile;
