<?php
/**
 * Base API Class
 *
 * @package wp-api-libraries-base
 */

/* Exit if accessed directly. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Check if class exists. */
if ( ! class_exists( 'WpLibrariesBase' ) ) {

	/**
	 * Abstract WpLibrariesBase class.
	 *
	 * @abstract
	 */
	abstract class WpLibrariesBase {

		/**
		 * Arguments.
		 *
		 * @var mixed
		 * @access protected
		 */
		protected $args;


		/**
		 * Base URI.
		 *
		 * @var mixed
		 * @access protected
		 */
		protected $base_uri;


		/**
		 * Is Debug.
		 *
		 * @var mixed
		 * @access protected
		 */
		protected $is_debug;


		/**
		 * __construct function.
		 *
		 * @access public
		 * @param mixed $base_uri Base URI.
		 * @param mixed $debug Debug.
		 * @return void
		 */
		public function __construct( $base_uri, $debug ) {
			$this->base_uri = $base_uri;
			$this->is_debug = $debug;
		}


		/**
		 * Build request function: prepares the class for a fetch request.
		 *
		 * @access protected
		 * @param mixed  $route Route, URL to be accessed.
		 * @param array  $body (default: array()) Arguments to pass in. If the method is GET, will be passed as query arguments attached to the route. If the method is not get, but the content type as defined in headers is 'application/json', then the body of the request will be set to a json_encode of $args. Otherwise, they will be passed as the body.
		 * @param string $method (default: 'GET') The method.
		 * @return The return of the function.
		 */
		protected function build_request( $route, $body = array(), $method = 'GET' ) {

			// Sets headers.
			$this->set_headers();

			// Sets method.
			$this->args['method'] = $method;

			// Sets route.
			$this->route = $route;

			// If method is get, then there is no body.
			if ( 'GET' === $method ) {
				$this->route = add_query_arg( array_filter( $body ), $route );
			} // Otherwise, if the content type is application/json, then the body needs to be json_encoded.
			elseif ( isset( $this->args['headers']['Content-Type'] ) && 'application/json' === $this->args['headers']['Content-Type'] ) {
				$this->args['body'] = wp_json_encode( $body );
			} // Anything else, let the user take care of it.
			else {
				$this->args['body'] = $body;
			}

			return $this;

		}

		/**
		 * Fetch.
		 *
		 * @access protected
		 * @return Request Body.
		 */
		protected function fetch() {

			// Make the request.
			$response = wp_remote_request( $this->base_uri . $this->route, $this->args );

			// Retrieve status code and body.
			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ) );
			
			$this->clear();

			if ( ! $this->is_status_ok( $code ) && ! $this->is_debug ) {
				return new WP_Error( 'response-error', sprintf( __( 'Status: &d', 'wp-libraries-api-base' ), $code ), $body );
			}

			return $body;

		}

		/**
		 * Function to be overwritten, gets called before call. Should be used to set headers.
		 *
		 * @access protected
		 * @abstract
		 * @return void
		 */
		protected function set_headers(){
			$this->args = array();
		}

		/**
		 * Function to be overwritten, gets called after the request has been made (if status code was ok). Should be used to reset headers.
		 *
		 * @access protected
		 * @abstract
		 * @return void
		 */
		protected function clear(){
			$this->args = array();
		}

		/**
		 * Returns whether status is in [ 200, 300 ).
		 *
		 * @access protected
		 * @param mixed $code Code.
		 * @return Status Code.
		 */
		protected function is_status_ok( $code ) {
			return ( 200 <= $code && 300 > $code );
		}
	}
}
