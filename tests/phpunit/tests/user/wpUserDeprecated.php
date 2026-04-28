<?php

/**
 * Tests for deprecated/back-compat behaviors in WP_User.
 */
class Tests_User_WPUserDeprecated extends WP_UnitTestCase {
	/**
	 * Ensures that calling the deprecated internal method does not trigger
	 * WP_User::for_site(), which would recurse via WP_User::__call().
	 */
	public function test_init_caps_does_not_call_for_site() {
		$user = self::factory()->user->create_and_get();
		$wp_user = new WP_User( $user->ID );

		add_filter(
			'deprecated_function_run',
			static function ( $function_name ) {
				if ( 'WP_User::for_site' === $function_name ) {
					self::fail( 'WP_User::for_site() should not be called from _init_caps().' );
				}
			},
			10,
			1
		);

		// Invoke via __call() to simulate legacy access.
		$wp_user->_init_caps();

		remove_all_filters( 'deprecated_function_run' );
	}
}

