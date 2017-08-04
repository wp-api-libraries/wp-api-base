<?php

/**
 * Base API Class
 */

/* Exit if accessed directly. */
if( !defined( 'ABSPATH' ) ) { exit; }

/* Check if class exists. */
if( !class_exists( 'WpLibrariesBase' ) ) {

	/* Define abstract class. */
	abstract class WpLibrariesBase{

		/* */
		protected $args;

		protected $base_uri;

		public function __construct( $base_uri ){
			$this->base_uri = $base_uri;
		}

		/**
		 * Build request function: prepares the class for a fetch request.
		 *
		 *
		 * @param  string $route 	URL to be accessed.
		 * @param  array  $args  	Arguments to pass in. If the method is GET, will be passed as query arguments attached to the route. If the method is not get, but the content type as defined in headers is 'application/json', then the body of the request will be set to a json_encode of $args. Otherwise, they will be passed as the body.
		 * @param  string $method (Default: 'GET') The method.
		 * @return [type]        	The return of the function.
		 */
		protected function build_request( $route, $body = array(), $method = 'GET' ){

			// Sets headers.
			$this->set_headers();

			// Sets method.
			$this->args['method'] = $method;

			// Sets route.
			$this->route = $route;

			// If method is get, then there is no body.
			if( 'GET' === $method ){
				$this->route = add_query_arg( array_filter( $body ), $route );
			}

			// Otherwise, if the content type is application/json, then the body needs to be json_encoded
			else if( 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $body );
			}

			// Anything else, let the user take care of it.
			else{
				$this->args['body'] = $body;
			}

			return $this;

		}

		protected function fetch(){

			// pp( $this->args, 'here' );

			// Make the request.
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );

			// Retrieve status code and body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );

			// pp( $body );

			if( !$this->is_status_ok( $code ) ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: &d', 'wp-postmark-api' ), $code ), $body );
			}

			$this->clear();

			return $body;

		}

		/**
		 * Function to be overwritten, gets called before call. Should be used to set headers.
		 */
		abstract protected function set_headers();

		/**
		 * Function to be overwritten, gets called after the request has been made (if status code was ok). Should be used to reset headers.
		 */
		abstract protected function clear();

		/**
		 * Returns whether status is in [ 200, 300 ).
		 */
		protected function is_status_ok( $code ){
			return ( 200 <= $code && 300 > $code );
		}
	}
}
