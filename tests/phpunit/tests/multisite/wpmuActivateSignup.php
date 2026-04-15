<?php

/**
 * @group ms-required
 * @group multisite
 *
 * @covers ::wpmu_activate_signup
 */
class Tests_Multisite_wpmuActivateSignup extends WP_UnitTestCase {

	/**
	 * Test that blog creation failure results in user deletion for new users.
	 *
	 * When a user is created during signup activation but blog creation fails,
	 * the user should be deleted to prevent orphaned accounts.
	 */
	public function test_blog_creation_failure_deletes_newly_created_user() {
		global $wpdb;

		// Disable notifications.
		add_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Sign up for a new blog.
		wpmu_signup_blog( 'example.com', '/', 'Test Blog', 'testuser', 'test@example.com', array() );

		// Get the activation key.
		$key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM $wpdb->signups WHERE user_login = %s", 'testuser' ) );

		remove_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Mock blog creation failure by filtering wpmu_create_blog.
		add_filter(
			'wp_insert_site_data',
			function( $data ) {
				return new WP_Error( 'db_insert_error', 'Database error' );
			}
		);

		// Attempt activation - should fail blog creation.
		$result = wpmu_activate_signup( $key );

		remove_all_filters( 'wp_insert_site_data' );

		// Verify blog creation returned an error.
		$this->assertWPError( $result );
		$this->assertSame( 'db_insert_error', $result->get_error_code() );

		// Verify the user was deleted.
		$user = get_user_by( 'login', 'testuser' );
		$this->assertFalse( $user, 'User should be deleted when blog creation fails' );

		// Verify user metadata was deleted.
		$usermeta_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->usermeta WHERE user_id = (SELECT ID FROM $wpdb->users WHERE user_login = %s)", 'testuser' ) );
		$this->assertSame( '0', $usermeta_count, 'User metadata should be deleted' );
	}

	/**
	 * Test that blog creation failure does not delete pre-existing users.
	 *
	 * When a pre-existing user tries to activate a blog but blog creation fails,
	 * the user account should remain intact.
	 */
	public function test_blog_creation_failure_preserves_existing_user() {
		global $wpdb;

		// Create a user first.
		$user_id = self::factory()->user->create(
			array(
				'user_login' => 'existinguser',
				'user_email' => 'existing@example.com',
			)
		);

		// Disable notifications.
		add_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Sign up for a blog with the existing user's credentials.
		wpmu_signup_blog( 'example.com', '/', 'Test Blog', 'existinguser', 'existing@example.com', array() );

		// Get the activation key.
		$key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM $wpdb->signups WHERE user_login = %s", 'existinguser' ) );

		remove_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Mock blog creation failure.
		add_filter(
			'wp_insert_site_data',
			function( $data ) {
				return new WP_Error( 'db_insert_error', 'Database error' );
			}
		);

		// Attempt activation - should fail blog creation.
		$result = wpmu_activate_signup( $key );

		remove_all_filters( 'wp_insert_site_data' );

		// Verify blog creation returned an error.
		$this->assertWPError( $result );

		// Verify the user still exists.
		$user = get_user_by( 'login', 'existinguser' );
		$this->assertInstanceOf( 'WP_User', $user, 'Existing user should not be deleted when blog creation fails' );
		$this->assertSame( $user_id, $user->ID );
	}

	/**
	 * Test successful blog activation creates both user and blog.
	 */
	public function test_successful_blog_activation() {
		global $wpdb;

		// Disable notifications.
		add_filter( 'wpmu_signup_blog_notification', '__return_false' );
		add_filter( 'wpmu_welcome_notification', '__return_false' );

		// Sign up for a new blog.
		wpmu_signup_blog( 'newblog.example.com', '/', 'New Blog', 'newuser', 'newuser@example.com', array() );

		// Get the activation key.
		$key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM $wpdb->signups WHERE user_login = %s", 'newuser' ) );

		remove_filter( 'wpmu_signup_blog_notification', '__return_false' );
		remove_filter( 'wpmu_welcome_notification', '__return_false' );

		// Activate the signup.
		$result = wpmu_activate_signup( $key );

		// Verify activation was successful.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'blog_id', $result );
		$this->assertArrayHasKey( 'user_id', $result );

		// Verify user exists.
		$user = get_user_by( 'login', 'newuser' );
		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertSame( $result['user_id'], $user->ID );

		// Verify blog exists.
		$blog = get_site( $result['blog_id'] );
		$this->assertNotNull( $blog );
		$this->assertSame( 'newblog.example.com', $blog->domain );
	}

	/**
	 * Test blog_taken error still marks signup as active without deleting user.
	 *
	 * The 'blog_taken' error indicates the blog was created in a previous failed
	 * activation attempt. In this case, we should mark the signup active without
	 * deleting the user.
	 */
	public function test_blog_taken_error_preserves_user_and_marks_active() {
		global $wpdb;

		// Disable notifications.
		add_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Sign up for a new blog.
		wpmu_signup_blog( 'taken.example.com', '/', 'Taken Blog', 'takenuser', 'taken@example.com', array() );

		// Get the activation key.
		$key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM $wpdb->signups WHERE user_login = %s", 'takenuser' ) );

		remove_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Create the blog beforehand to simulate blog_taken scenario.
		// This would happen if a previous activation attempt created the blog but failed before marking signup active.
		wpmu_create_blog( 'taken.example.com', '/', 'Taken Blog', self::factory()->user->create(), array(), get_current_network_id() );

		// Attempt activation - should get blog_taken error.
		$result = wpmu_activate_signup( $key );

		// Verify we got the expected error.
		$this->assertWPError( $result );
		$this->assertSame( 'blog_taken', $result->get_error_code() );

		// Verify user was created and not deleted.
		$user = get_user_by( 'login', 'takenuser' );
		$this->assertInstanceOf( 'WP_User', $user, 'User should be created and preserved even with blog_taken error' );

		// Verify signup was marked active.
		$signup = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE activation_key = %s", $key ) );
		$this->assertSame( '1', $signup->active, 'Signup should be marked active for blog_taken error' );
	}

	/**
	 * Test user-only signup is not affected by blog creation rollback logic.
	 */
	public function test_user_only_signup_success() {
		global $wpdb;

		// Disable notifications.
		add_filter( 'wpmu_signup_user_notification', '__return_false' );
		add_filter( 'wpmu_welcome_user_notification', '__return_false' );

		// Sign up for user-only (no blog).
		wpmu_signup_user( 'useronly', 'useronly@example.com', array() );

		// Get the activation key.
		$key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM $wpdb->signups WHERE user_login = %s", 'useronly' ) );

		remove_filter( 'wpmu_signup_user_notification', '__return_false' );
		remove_filter( 'wpmu_welcome_user_notification', '__return_false' );

		// Activate the user.
		$result = wpmu_activate_signup( $key );

		// Verify activation was successful.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'user_id', $result );
		$this->assertArrayNotHasKey( 'blog_id', $result );

		// Verify user exists.
		$user = get_user_by( 'login', 'useronly' );
		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertSame( $result['user_id'], $user->ID );
	}

	/**
	 * Test user cache is cleaned after user deletion on blog creation failure.
	 */
	public function test_user_cache_cleaned_after_deletion() {
		global $wpdb;

		// Disable notifications.
		add_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Sign up for a new blog.
		wpmu_signup_blog( 'cache.example.com', '/', 'Cache Blog', 'cacheuser', 'cache@example.com', array() );

		// Get the activation key.
		$key = $wpdb->get_var( $wpdb->prepare( "SELECT activation_key FROM $wpdb->signups WHERE user_login = %s", 'cacheuser' ) );

		remove_filter( 'wpmu_signup_blog_notification', '__return_false' );

		// Mock blog creation failure.
		add_filter(
			'wp_insert_site_data',
			function( $data ) {
				return new WP_Error( 'db_insert_error', 'Database error' );
			}
		);

		// Attempt activation - should fail blog creation and delete user.
		$result = wpmu_activate_signup( $key );

		remove_all_filters( 'wp_insert_site_data' );

		// Verify user was deleted.
		$user = get_user_by( 'login', 'cacheuser' );
		$this->assertFalse( $user );

		// Verify cache was cleaned by attempting to get user by ID.
		// If cache wasn't cleaned, this might return stale data.
		$user_id = username_exists( 'cacheuser' );
		$this->assertFalse( $user_id, 'User cache should be cleaned after deletion' );
	}
}
