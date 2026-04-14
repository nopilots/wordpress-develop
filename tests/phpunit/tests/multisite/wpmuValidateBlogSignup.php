<?php

/**
 * @group ms-required
 * @group multisite
 *
 * @covers ::wpmu_validate_blog_signup
 */
class Tests_Multisite_wpmuValidateBlogSignup extends WP_UnitTestCase {

	protected static $super_admin_id;

	protected static $existing_user_login = 'existinguserfoo';
	protected static $existing_user_id;

	protected static $existing_blog_name = 'existingsitefoo';
	protected static $existing_blog_id;

	protected $minimum_site_name_length = 4;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$super_admin_id = $factory->user->create();
		grant_super_admin( self::$super_admin_id );

		self::$existing_user_id = $factory->user->create( array( 'user_login' => self::$existing_user_login ) );

		$network = get_network();

		if ( is_subdomain_install() ) {
			$domain = self::$existing_blog_name . '.' . preg_replace( '|^www\.|', '', $network->domain );
			$path   = $network->path;
		} else {
			$domain = $network->domain;
			$path   = $network->path . self::$existing_blog_name . '/';
		}

		self::$existing_blog_id = $factory->blog->create(
			array(
				'domain'     => $domain,
				'path'       => $path,
				'network_id' => $network->id,
			)
		);
	}

	public static function wpTearDownAfterClass() {
		revoke_super_admin( self::$super_admin_id );
		wpmu_delete_user( self::$super_admin_id );

		wpmu_delete_user( self::$existing_user_id );

		wp_delete_site( self::$existing_blog_id );
	}

	/**
	 * @dataProvider data_validate_blogname
	 */
	public function test_validate_blogname( $blog_name, $error_message ) {
		$result = wpmu_validate_blog_signup( $blog_name, 'Foo Site Title', get_userdata( self::$super_admin_id ) );
		$this->assertContains( 'blogname', $result['errors']->get_error_codes(), $error_message );
	}

	public function data_validate_blogname() {
		$data = array(
			array( '', 'Site names must not be empty.' ),
			array( 'foo-hello', 'Site names must not contain hyphens.' ),
			array( 'foo_hello', 'Site names must not contain underscores.' ),
			array( 'foo hello', 'Site names must not contain spaces.' ),
			array( 'FooHello', 'Site names must not contain uppercase letters.' ),
			array( '12345678', 'Site names must not consist of numbers only.' ),
			array( self::$existing_blog_name, 'Site names must not collide with an existing site name.' ),
			array( self::$existing_user_login, 'Site names must not collide with an existing user login.' ),
			array( 'foo', 'Site names must at least contain 4 characters.' ),
		);

		$illegal_names = get_site_option( 'illegal_names' );
		if ( ! empty( $illegal_names ) ) {
			$data[] = array( array_shift( $illegal_names ), 'Illegal site names are not allowed.' );
		} else {
			$data[] = array( 'www', 'Illegal site names are not allowed.' );
		}

		return $data;
	}

	public function test_validate_empty_blog_title() {
		$result = wpmu_validate_blog_signup( 'uniqueblogname1234', '', get_userdata( self::$super_admin_id ) );
		$this->assertContains( 'blog_title', $result['errors']->get_error_codes(), 'Site titles must not be empty.' );
	}

	public function test_validate_blogname_from_same_existing_user() {
		$result = wpmu_validate_blog_signup( self::$existing_user_login, 'Foo Site Title', get_userdata( self::$existing_user_id ) );
		$this->assertEmpty( $result['errors']->get_error_codes() );
	}

	/**
	 * @ticket 39676
	 *
	 * @dataProvider data_filter_minimum_site_name_length
	 */
	public function test_filter_minimum_site_name_length( $site_name, $minimum_length, $expect_error ) {
		$this->minimum_site_name_length = $minimum_length;
		add_filter( 'minimum_site_name_length', array( $this, 'filter_minimum_site_name_length' ) );

		$result = wpmu_validate_blog_signup( $site_name, 'Site Title', get_userdata( self::$super_admin_id ) );

		remove_filter( 'minimum_site_name_length', array( $this, 'filter_minimum_site_name_length' ) );
		$this->minimum_site_name_length = 4;

		if ( $expect_error ) {
			$this->assertContains( 'blogname', $result['errors']->get_error_codes() );
		} else {
			$this->assertEmpty( $result['errors']->get_error_codes() );
		}
	}

	public function data_filter_minimum_site_name_length() {
		return array(
			array( 'fooo', 5, true ),
			array( 'foooo', 5, false ),
			array( 'foo', 4, true ),
			array( 'fooo', 4, false ),
			array( 'fo', 3, true ),
			array( 'foo', 3, false ),
		);
	}

	public function filter_minimum_site_name_length() {
		return $this->minimum_site_name_length;
	}

	/**
	 * @ticket 43667
	 */
	public function test_signup_nonce_check() {
		$original_php_self       = $_SERVER['PHP_SELF'];
		$_SERVER['PHP_SELF']     = '/wp-signup.php';
		$_POST['signup_form_id'] = 'blog-signup-form';
		$_POST['_signup_form']   = wp_create_nonce( 'signup_form_' . $_POST['signup_form_id'] );

		$valid               = wpmu_validate_blog_signup( 'my-nonce-site', 'Site Title', get_userdata( self::$super_admin_id ) );
		$_SERVER['PHP_SELF'] = $original_php_self;

		$this->assertNotContains( 'invalid_nonce', $valid['errors']->get_error_codes() );
	}

	/**
	 * @ticket 43667
	 */
	public function test_signup_nonce_check_invalid() {
		$original_php_self       = $_SERVER['PHP_SELF'];
		$_SERVER['PHP_SELF']     = '/wp-signup.php';
		$_POST['signup_form_id'] = 'blog-signup-form';
		$_POST['_signup_form']   = wp_create_nonce( 'invalid' );

		$valid               = wpmu_validate_blog_signup( 'my-nonce-site', 'Site Title', get_userdata( self::$super_admin_id ) );
		$_SERVER['PHP_SELF'] = $original_php_self;

		$this->assertContains( 'invalid_nonce', $valid['errors']->get_error_codes() );
	}

	/**
	 * Tests that a pending blog signup in the signups table blocks a new signup for the same domain/path.
	 */
	public function test_pending_blog_signup_blocks_duplicate_domain_path() {
		global $wpdb;

		$network = get_network();

		// Set up domain and path based on subdomain vs subdirectory install.
		if ( is_subdomain_install() ) {
			$domain = 'pendingblog.' . preg_replace( '|^www\.|', '', $network->domain );
			$path   = $network->path;
		} else {
			$domain = $network->domain;
			$path   = $network->path . 'pendingblog/';
		}

		// Insert a pending signup for this domain/path.
		$wpdb->insert(
			$wpdb->signups,
			array(
				'domain'         => $domain,
				'path'           => $path,
				'title'          => 'Pending Blog',
				'user_login'     => 'testuser1',
				'user_email'     => 'testuser1@example.com',
				'registered'     => current_time( 'mysql', true ),
				'activation_key' => 'test_activation_key_1',
				'meta'           => '',
			)
		);

		// Try to validate a signup for the same domain/path.
		$result = wpmu_validate_blog_signup( 'pendingblog', 'New Blog Title', get_userdata( self::$super_admin_id ) );

		// Should have an error because the domain/path is reserved.
		$this->assertContains( 'blogname', $result['errors']->get_error_codes(), 'Should block duplicate domain/path signup.' );

		// Clean up.
		$wpdb->delete( $wpdb->signups, array( 'domain' => $domain, 'path' => $path ) );
	}

	/**
	 * Tests that same email can create multiple blog signups (email is not checked in blog validation).
	 *
	 * This test validates that the TODO removal was correct: email should NOT be checked in
	 * wpmu_validate_blog_signup() because the same email can legitimately create multiple blogs.
	 */
	public function test_same_email_can_signup_multiple_blogs() {
		global $wpdb;

		$network    = get_network();
		$user_email = 'multisite@example.com';

		// Set up first blog domain/path.
		if ( is_subdomain_install() ) {
			$domain1 = 'firstblog.' . preg_replace( '|^www\.|', '', $network->domain );
			$path1   = $network->path;
		} else {
			$domain1 = $network->domain;
			$path1   = $network->path . 'firstblog/';
		}

		// Insert a pending signup for the first blog with this email.
		$wpdb->insert(
			$wpdb->signups,
			array(
				'domain'         => $domain1,
				'path'           => $path1,
				'title'          => 'First Blog',
				'user_login'     => 'testuser2',
				'user_email'     => $user_email,
				'registered'     => current_time( 'mysql', true ),
				'activation_key' => 'test_activation_key_2',
				'meta'           => '',
			)
		);

		// Now try to validate a signup for a DIFFERENT blog with the SAME email.
		$result = wpmu_validate_blog_signup( 'secondblog', 'Second Blog Title', get_userdata( self::$super_admin_id ) );

		// Should NOT have an error - the same email can create multiple blogs.
		$this->assertEmpty( $result['errors']->get_error_codes(), 'Same email should be allowed for different blog signups.' );

		// Clean up.
		$wpdb->delete( $wpdb->signups, array( 'domain' => $domain1, 'path' => $path1 ) );
	}

	/**
	 * Tests that old pending signups (>2 days) are automatically cleaned up.
	 */
	public function test_old_pending_signup_is_cleaned_up() {
		global $wpdb;

		$network = get_network();

		// Set up domain and path.
		if ( is_subdomain_install() ) {
			$domain = 'oldblog.' . preg_replace( '|^www\.|', '', $network->domain );
			$path   = $network->path;
		} else {
			$domain = $network->domain;
			$path   = $network->path . 'oldblog/';
		}

		// Insert a pending signup registered 3 days ago.
		$old_date = gmdate( 'Y-m-d H:i:s', time() - ( 3 * DAY_IN_SECONDS ) );
		$wpdb->insert(
			$wpdb->signups,
			array(
				'domain'         => $domain,
				'path'           => $path,
				'title'          => 'Old Blog',
				'user_login'     => 'testuser3',
				'user_email'     => 'testuser3@example.com',
				'registered'     => $old_date,
				'activation_key' => 'test_activation_key_3',
				'meta'           => '',
			)
		);

		// Verify the signup exists.
		$signup_before = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE domain = %s AND path = %s", $domain, $path ) );
		$this->assertInstanceOf( 'stdClass', $signup_before, 'Old signup should exist before validation.' );

		// Try to validate a signup for the same domain/path.
		$result = wpmu_validate_blog_signup( 'oldblog', 'New Blog Title', get_userdata( self::$super_admin_id ) );

		// Should NOT have an error - old signup should be cleaned up.
		$this->assertEmpty( $result['errors']->get_error_codes(), 'Old pending signup should be cleaned up automatically.' );

		// Verify the old signup was deleted.
		$signup_after = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->signups WHERE domain = %s AND path = %s", $domain, $path ) );
		$this->assertNull( $signup_after, 'Old signup should be deleted after validation.' );
	}
}
