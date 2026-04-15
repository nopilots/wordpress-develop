<?php

/**
 * @group ms-required
 * @group multisite
 *
 * @covers ::wpmu_signup_blog_notification
 */
class Tests_Multisite_wpmuSignupBlogNotification extends WP_UnitTestCase {

	/**
	 * Test that activation URL uses set_url_scheme() for subdomain installs on network 1.
	 *
	 * @ticket TBD
	 */
	public function test_activation_url_uses_set_url_scheme_for_subdomain_network_1() {
		// Store original values.
		$original_is_subdomain = is_subdomain_install();
		$original_network_id   = get_current_network_id();

		// Mock subdomain install on network 1.
		add_filter( 'pre_option_subdomain_install', '__return_true' );

		// Capture email.
		$captured_message = '';
		add_filter(
			'wp_mail',
			function( $args ) use ( &$captured_message ) {
				$captured_message = $args['message'];
				return false; // Prevent actual email sending.
			}
		);

		$domain     = 'testblog.example.com';
		$path       = '/';
		$title      = 'Test Blog';
		$user_login = 'testuser';
		$user_email = 'testuser@example.com';
		$key        = 'test-activation-key-12345';
		$meta       = array();

		// Call the function.
		wpmu_signup_blog_notification( $domain, $path, $title, $user_login, $user_email, $key, $meta );

		// Clean up filters.
		remove_filter( 'pre_option_subdomain_install', '__return_true' );
		remove_all_filters( 'wp_mail' );

		// Verify the URL in the message.
		// The URL should be properly scheme-aware (http or https depending on is_ssl()).
		$expected_scheme = is_ssl() ? 'https' : 'http';
		$expected_url    = "$expected_scheme://{$domain}{$path}wp-activate.php?key=$key";

		$this->assertStringContainsString(
			$expected_url,
			$captured_message,
			'Activation URL should use proper scheme via set_url_scheme()'
		);
	}

	/**
	 * Test that activation URL uses network_site_url() for non-subdomain installs.
	 *
	 * @ticket TBD
	 */
	public function test_activation_url_uses_network_site_url_for_non_subdomain() {
		// Mock non-subdomain install.
		add_filter( 'pre_option_subdomain_install', '__return_false' );

		// Capture email.
		$captured_message = '';
		add_filter(
			'wp_mail',
			function( $args ) use ( &$captured_message ) {
				$captured_message = $args['message'];
				return false; // Prevent actual email sending.
			}
		);

		$domain     = 'example.com';
		$path       = '/testblog/';
		$title      = 'Test Blog';
		$user_login = 'testuser';
		$user_email = 'testuser@example.com';
		$key        = 'test-activation-key-12345';
		$meta       = array();

		// Call the function.
		wpmu_signup_blog_notification( $domain, $path, $title, $user_login, $user_email, $key, $meta );

		// Clean up filters.
		remove_filter( 'pre_option_subdomain_install', '__return_false' );
		remove_all_filters( 'wp_mail' );

		// Verify the URL in the message uses network_site_url().
		$expected_url = network_site_url( "wp-activate.php?key=$key" );

		$this->assertStringContainsString(
			$expected_url,
			$captured_message,
			'Activation URL should use network_site_url() for non-subdomain installs'
		);
	}

	/**
	 * Test that activation URL is properly escaped.
	 *
	 * @ticket TBD
	 */
	public function test_activation_url_is_escaped() {
		// Mock subdomain install.
		add_filter( 'pre_option_subdomain_install', '__return_true' );

		// Capture email.
		$captured_message = '';
		add_filter(
			'wp_mail',
			function( $args ) use ( &$captured_message ) {
				$captured_message = $args['message'];
				return false; // Prevent actual email sending.
			}
		);

		$domain     = 'testblog.example.com';
		$path       = '/';
		$title      = 'Test Blog';
		$user_login = 'testuser';
		$user_email = 'testuser@example.com';
		$key        = 'test<script>alert("xss")</script>';
		$meta       = array();

		// Call the function.
		wpmu_signup_blog_notification( $domain, $path, $title, $user_login, $user_email, $key, $meta );

		// Clean up filters.
		remove_filter( 'pre_option_subdomain_install', '__return_true' );
		remove_all_filters( 'wp_mail' );

		// Verify the URL is escaped (should not contain the raw script tag).
		$this->assertStringNotContainsString(
			'<script>',
			$captured_message,
			'Activation URL should be properly escaped via esc_url()'
		);
	}

	/**
	 * Test that filter can bypass notification.
	 *
	 * @ticket TBD
	 */
	public function test_filter_can_bypass_notification() {
		// Add filter to bypass notification.
		add_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Capture email (should not be sent).
		$email_sent = false;
		add_filter(
			'wp_mail',
			function( $args ) use ( &$email_sent ) {
				$email_sent = true;
				return false;
			}
		);

		$domain     = 'testblog.example.com';
		$path       = '/';
		$title      = 'Test Blog';
		$user_login = 'testuser';
		$user_email = 'testuser@example.com';
		$key        = 'test-activation-key-12345';
		$meta       = array();

		// Call the function.
		$result = wpmu_signup_blog_notification( $domain, $path, $title, $user_login, $user_email, $key, $meta );

		// Clean up filters.
		remove_filter( 'wpmu_signup_blog_notification', '__return_false' );
		remove_all_filters( 'wp_mail' );

		$this->assertFalse( $result, 'Function should return false when bypassed by filter' );
		$this->assertFalse( $email_sent, 'Email should not be sent when bypassed by filter' );
	}

	/**
	 * Test that HTTPS scheme is used when is_ssl() returns true.
	 *
	 * @ticket TBD
	 */
	public function test_activation_url_uses_https_when_ssl() {
		// Mock subdomain install.
		add_filter( 'pre_option_subdomain_install', '__return_true' );

		// Mock is_ssl() to return true.
		add_filter( 'is_ssl', '__return_true' );

		// Capture email.
		$captured_message = '';
		add_filter(
			'wp_mail',
			function( $args ) use ( &$captured_message ) {
				$captured_message = $args['message'];
				return false; // Prevent actual email sending.
			}
		);

		$domain     = 'testblog.example.com';
		$path       = '/';
		$title      = 'Test Blog';
		$user_login = 'testuser';
		$user_email = 'testuser@example.com';
		$key        = 'test-activation-key-12345';
		$meta       = array();

		// Call the function.
		wpmu_signup_blog_notification( $domain, $path, $title, $user_login, $user_email, $key, $meta );

		// Clean up filters.
		remove_filter( 'pre_option_subdomain_install', '__return_true' );
		remove_filter( 'is_ssl', '__return_true' );
		remove_all_filters( 'wp_mail' );

		// Verify the URL uses HTTPS.
		$expected_url = "https://{$domain}{$path}wp-activate.php?key=$key";

		$this->assertStringContainsString(
			$expected_url,
			$captured_message,
			'Activation URL should use HTTPS when is_ssl() returns true'
		);
	}
}
