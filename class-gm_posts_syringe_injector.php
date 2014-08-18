<?php namespace GM\PostsSyringe;

class Injector {

    /**
     * @var array Settings arument for the instance
     */
    private $args;

    /**
     * @var string|array CPT(s) slug(s) to inject in main loop
     */
    private $cpt;

    /**
     * @var array Posts objects to inject
     */
    private $posts;

    /**
     * Set the array of configuration for the instance
     *
     * @param array $args
     */
    public function setArgs( Array $args = array () ) {
        $this->args = $args;
    }

    /**
     * Set the CPT(s) slug(s) to inject in main loop
     *
     * @param array|string $cpt
     */
    public function setCpt( $cpt ) {
        $this->cpt = $cpt;
    }

    /**
     * Set the posts array to be injected
     *
     * @param array $posts
     */
    public function setPosts( Array $posts ) {
        $this->posts = $posts;
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
     * Get the CPT(s) slug(s) to inject in main loop
     *
     * @return array|string|void
     */
    public function getCpt() {
        return $this->cpt;
    }

    /**
     * Get the array of posts to be injected
     *
     * @return array|void
     */
    public function getPosts() {
        return $this->posts;
    }

    /**
     * Perform the merging between orginal main loop posts an posts to be injected
     *
     * @param array $posts Main loop posts
     * @return array                Main loop posts merged with post to be injected
     * @uses GM\PostsSyringe\Injector::parseChunks()
     * @uses GM\PostsSyringe\Injector::flatten()
     */
    public function inject( Array $posts = array () ) {
        $injected = FALSE;
        if ( ! empty( $this->posts ) && ! empty( $posts ) ) {
            $orig_count = count( $posts );
            do_action( 'syringe_before_inject', $this, $this->posts, $posts );
            $original = array_chunk( $posts, $this->args['before_each_inject'] );
            $to_inject = array_chunk( $this->posts, $this->args['per_inject'] );
            $posts = $this->flatten( $this->parseChunks( $original, $to_inject ) );
            $injected = count( $posts ) > $orig_count;
            do_action( 'syringe_after_inject', $this, $posts, $this->posts );
        }
        return apply_filters( 'syringe_posts', $posts, $injected, $this, $this->posts );
    }

    private function parseChunks( Array $original, Array $injected ) {
        $results = array ();
        while ( ! empty( $original ) ) {
            $chunk = array_shift( $original );
            array_push( $results, $chunk );
            if ( empty( $injected ) ) {
                continue;
            }
            $injected_chunk = array_map( function( $post ) {
                $post->syringe_injector = $this;
                return $post;
            }, array_shift( $injected ) );
            array_push( $results, $injected_chunk );
        }
        return $results;
    }

    private function flatten( Array $results ) {
        $posts = array ();
        array_map( function( $chunck ) use( &$posts ) {
            foreach ( $chunck as $post ) {
                array_push( $posts, $post );
            }
        }, $results );
        return $posts;
    }

}