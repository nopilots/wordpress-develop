<?php
/**
 * Tests for get_rest_url() in a multisite environment.
 *
 * @package WordPress
 * @subpackage REST API
 */

require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . WPINC . '/rest-api.php';

/**
 * @group restapi
 * @group ms-required
 * @group multisite
 */
class Tests_REST_API_URL_Multisite extends WP_UnitTestCase {

	/**
	 * Blog ID for a secondary test site.
	 *
	 * @var int
	 */
	protected static $blog_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$blog_id = $factory->blog->create();
	}

	public static function wpTearDownAfterClass() {
		wp_delete_site( self::$blog_id );
	}

	/**
	 * Tests that get_rest_url() uses pretty permalinks when the target blog
	 * has a permalink structure in multisite, even if the current blog does not.
	 *
	 * This is the primary reason the multisite blog_id check exists:
	 * without it, the function would only check the current blog's
	 * permalink structure via get_option(), incorrectly producing a
	 * plain query-string URL for a blog that supports pretty permalinks.
	 */
	public function test_get_rest_url_target_blog_has_permalinks_current_does_not() {
		// Current blog: no pretty permalinks.
		$this->set_permalink_structure( '' );

		// Target blog: pretty permalinks.
		update_blog_option( self::$blog_id, 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );

		$url = get_rest_url( self::$blog_id );

		$this->assertStringContainsString( 'wp-json', $url, 'URL should use the wp-json prefix when the target blog has pretty permalinks.' );
		$this->assertStringNotContainsString( 'rest_route=', $url, 'URL should not use query parameter when the target blog has pretty permalinks.' );
	}

	/**
	 * Tests that get_rest_url() falls back to a plain URL when neither the
	 * target blog nor the current blog has a permalink structure.
	 */
	public function test_get_rest_url_no_permalinks_anywhere() {
		// Current blog: no pretty permalinks.
		$this->set_permalink_structure( '' );

		// Target blog: no pretty permalinks.
		update_blog_option( self::$blog_id, 'permalink_structure', '' );

		$url = get_rest_url( self::$blog_id );

		$this->assertStringContainsString( 'rest_route=', $url, 'URL should use the rest_route query parameter when no blog has pretty permalinks.' );
	}

	/**
	 * Tests that get_rest_url() includes the correct home URL for a different blog.
	 */
	public function test_get_rest_url_for_different_blog_uses_correct_home_url() {
		update_blog_option( self::$blog_id, 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );

		$url       = get_rest_url( self::$blog_id );
		$blog_home = get_home_url( self::$blog_id );

		$this->assertStringStartsWith( $blog_home, $url, 'REST URL should start with the target blog home URL.' );
	}

	/**
	 * Tests that get_rest_url() with null blog ID returns the current blog's
	 * REST URL in a multisite context.
	 */
	public function test_get_rest_url_null_blog_id_returns_current_blog_url() {
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		$this->assertSame( get_rest_url(), get_rest_url( null ) );
	}

	/**
	 * Tests that get_rest_url() correctly appends the path for another blog.
	 */
	public function test_get_rest_url_with_path_for_different_blog() {
		update_blog_option( self::$blog_id, 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );

		$url = get_rest_url( self::$blog_id, '/wp/v2/posts' );

		$this->assertStringContainsString( 'wp-json/wp/v2/posts', $url, 'REST URL should include the specified path.' );
	}

	/**
	 * Tests that the rest_url filter receives the correct blog ID when
	 * generating a URL for a different blog.
	 */
	public function test_get_rest_url_filter_receives_blog_id() {
		$received_blog_id = null;

		$callback = function ( $url, $path, $blog_id ) use ( &$received_blog_id ) {
			$received_blog_id = $blog_id;
			return $url;
		};
		add_filter( 'rest_url', $callback, 10, 3 );

		get_rest_url( self::$blog_id, '/wp/v2/posts' );

		$this->assertSame( self::$blog_id, $received_blog_id, 'The rest_url filter should receive the correct blog ID.' );

		remove_filter( 'rest_url', $callback, 10 );
	}
}
