<?php namespace GM\PostsSyringe;

class Query {

    /**
     * @var string|array CPT(s) slug(s) to inject in main loop
     */
    private $cpt;

    /**
     * @var GM\PostsSyringe\Injector Class that will handle injection of posts
     */
    private $injector;

    /**
     * @var array Arguments to setup way class instance will act
     */
    private $args = [ ];

    /**
     * @var array Arguments to setup query arguments for CPT(s) posts to be njected
     */
    private $query_args = [ ];

    /**
     * Constructor
     *
     * @param string|array $cpt                 CPT(s) slug(s) of posts to inject
     * @param \GM\PostsSyringe\Loop $injector   Instance of class used to display injected posts
     */
    public function __construct( $cpt, Injector $injector ) {
        $this->cpt = $cpt;
        $this->injector = $injector;
    }

    /**
     * Init instance arguments
     *
     * @param array $args
     */
    public function init( Array $args = [ ] ) {
        $this->args = wp_parse_args( $args, array ( 'before_each_inject' => 1, 'per_inject' => 1 ) );
        $this->args['before_each_inject'] = absint( $this->args['before_each_inject'] );
        $this->args['per_inject'] = absint( $this->args['per_inject'] );
    }

    /**
     * Set the args to query posts to inject
     *
     * @param array $query_args
     */
    public function setQueryArgs( Array $query_args = array () ) {
        $defaults = [ 'paged' => get_query_var( 'paged' ) ? : 1 ];
        $this->query_args = wp_parse_args( $query_args, $defaults );
    }

    /**
     * Prepare and run the new WP_Query for the posts to inject and save results in instance variable.
     * Run on 'loop_start' for main query.
     *
     * @param \WP_Query $query
     * @uses \GM\PostsSyringe\Loop\inject() Perform the query and injects posts using Injector object
     * @uses \GM\PostsSyringe\Loop\getPerPage() Calculate the posts_per_page argument based on main query
     */
    public function start( $posts, \WP_Query $query ) {
        remove_filter( current_filter(), [ $this, __FUNCTION__ ] );
        //$posts = $GLOBALS['wp_the_query']->posts;
        $count = count( $posts );
        if ( $this->args['before_each_inject'] > 0 && ( $count >= $this->args['before_each_inject'] ) ) {
            $this->query_args = apply_filters( 'syringe_query_args', $this->query_args, $this );
            $this->query_args['posts_per_page'] = (int) $this->getPerPage( $query );
            $posts = $this->inject( $posts );
        }
        return $posts;
    }

    /**
     * Get the array of configuration for the instance
     *
     * @return array|void
     */
    public function getArgs() {
        return $this->args;
    }

    /**
     * Get the query arguments to get posts to be injected
     *
     * @return array|void
     */
    public function getQueryArgs() {
        return $this->query_args;
    }

    private function inject( Array $posts ) {
        $posts_to_inject = get_posts( $this->query_args );
        if ( ! empty( $posts_to_inject ) ) {
            $this->injector->setArgs( $this->getArgs() );
            $this->injector->setCpt( $this->cpt );
            $this->injector->setPosts( $posts_to_inject );
            $posts = $this->injector->inject( $posts );
        }
        return $posts;
    }

    private function getPerPage( $query ) {
        $total = $query->get( 'posts_per_page' );
        return floor( $this->args['per_inject'] * ( $total / $this->args['before_each_inject'] ) );
    }

}