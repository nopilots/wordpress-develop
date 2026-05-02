<?php

/**
 * @group admin-bar
 * @group toolbar
 * @group admin
 */
class Tests_AdminBar extends WP_UnitTestCase {
	protected static $editor_id;
	protected static $admin_id;
	protected static $no_role_id;
	protected static $post_id;
	protected static $blog_id;

	protected static $user_ids = array();

	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
	}

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$editor_id  = $factory->user->create( array( 'role' => 'editor' ) );
		self::$user_ids[] = self::$editor_id;
		self::$admin_id   = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$user_ids[] = self::$admin_id;
		self::$no_role_id = $factory->user->create( array( 'role' => '' ) );
		self::$user_ids[] = self::$no_role_id;
	}

	/**
	 * @ticket 21117
	 */
	public function test_content_post_type() {
		wp_set_current_user( self::$editor_id );

		register_post_type( 'content', array( 'show_in_admin_bar' => true ) );

		$admin_bar = new WP_Admin_Bar();

		wp_admin_bar_new_content_menu( $admin_bar );

		$nodes = $admin_bar->get_nodes();
		$this->assertFalse( $nodes['new-content']->parent );
		$this->assertSame( 'new-content', $nodes['add-new-content']->parent );

		_unregister_post_type( 'content' );
	}

	/**
	 * @ticket 21117
	 */
	public function test_merging_existing_meta_values() {
		wp_set_current_user( self::$editor_id );

		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'   => 'test-node',
				'meta' => array( 'class' => 'test-class' ),
			)
		);

		$node1 = $admin_bar->get_node( 'test-node' );
		$this->assertSame( array( 'class' => 'test-class' ), $node1->meta );

		$admin_bar->add_node(
			array(
				'id'   => 'test-node',
				'meta' => array( 'some-meta' => 'value' ),
			)
		);

		$node2 = $admin_bar->get_node( 'test-node' );
		$this->assertSame(
			array(
				'class'     => 'test-class',
				'some-meta' => 'value',
			),
			$node2->meta
		);
	}

	/**
	 * @ticket 25162
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_no_role() {
		$this->assertFalse( user_can( self::$no_role_id, 'read' ) );

		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name  = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info  = $wp_admin_bar->get_node( 'user-info' );

		// Site menu points to the home page instead of the admin URL.
		$this->assertSame( home_url( '/' ), $node_site_name->href );

		// No profile links as the user doesn't have any permissions on the site.
		$this->assertFalse( $node_my_account->href );
		$this->assertFalse( $node_user_info->href );
	}

	/**
	 * @ticket 25162
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_role() {
		$this->assertTrue( user_can( self::$editor_id, 'read' ) );

		wp_set_current_user( self::$editor_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name  = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info  = $wp_admin_bar->get_node( 'user-info' );

		// Site menu points to the admin URL.
		$this->assertSame( admin_url( '/' ), $node_site_name->href );

		$profile_url = admin_url( 'profile.php' );

		// Profile URLs point to profile.php.
		$this->assertSame( $profile_url, $node_my_account->href );
		$this->assertSame( $profile_url, $node_user_info->href );
	}

	/**
	 * @ticket 25162
	 * @group multisite
	 * @group ms-required
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_no_role_on_blog() {
		$blog_id = self::factory()->blog->create(
			array(
				'user_id' => self::$admin_id,
			)
		);

		$this->assertTrue( user_can( self::$admin_id, 'read' ) );
		$this->assertTrue( user_can( self::$editor_id, 'read' ) );

		$this->assertTrue( is_user_member_of_blog( self::$admin_id, $blog_id ) );
		$this->assertFalse( is_user_member_of_blog( self::$editor_id, $blog_id ) );

		wp_set_current_user( self::$editor_id );

		switch_to_blog( $blog_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name  = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info  = $wp_admin_bar->get_node( 'user-info' );

		// Get primary blog.
		$primary = get_active_blog_for_user( self::$editor_id );
		$this->assertIsObject( $primary );

		// No Site menu as the user isn't a member of this blog.
		$this->assertNull( $node_site_name );

		$primary_profile_url = get_admin_url( $primary->blog_id, 'profile.php' );

		// Ensure the user's primary blog is not the same as the main site.
		$this->assertNotEquals( $primary_profile_url, admin_url( 'profile.php' ) );

		// Profile URLs should go to the user's primary blog.
		$this->assertSame( $primary_profile_url, $node_my_account->href );
		$this->assertSame( $primary_profile_url, $node_user_info->href );

		restore_current_blog();
	}

	/**
	 * @ticket 25162
	 * @group multisite
	 * @group ms-required
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_no_role_on_network() {
		$this->assertTrue( user_can( self::$admin_id, 'read' ) );
		$this->assertFalse( user_can( self::$no_role_id, 'read' ) );

		$blog_id = self::factory()->blog->create(
			array(
				'user_id' => self::$admin_id,
			)
		);

		$this->assertTrue( is_user_member_of_blog( self::$admin_id, $blog_id ) );
		$this->assertFalse( is_user_member_of_blog( self::$no_role_id, $blog_id ) );
		$this->assertTrue( is_user_member_of_blog( self::$no_role_id, get_current_blog_id() ) );

		// Remove `$nobody` from the current blog, so they're not a member of any blog.
		$removed = remove_user_from_blog( self::$no_role_id, get_current_blog_id() );

		$this->assertTrue( $removed );
		$this->assertFalse( is_user_member_of_blog( self::$no_role_id, get_current_blog_id() ) );

		wp_set_current_user( self::$no_role_id );

		switch_to_blog( $blog_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name  = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info  = $wp_admin_bar->get_node( 'user-info' );

		// Get primary blog.
		$primary = get_active_blog_for_user( self::$no_role_id );
		$this->assertNull( $primary );

		// No Site menu as the user isn't a member of this site.
		$this->assertNull( $node_site_name );

		$user_profile_url = user_admin_url( 'profile.php' );

		$this->assertNotEquals( $user_profile_url, admin_url( 'profile.php' ) );

		// Profile URLs should go to the user's primary blog.
		$this->assertSame( $user_profile_url, $node_my_account->href );
		$this->assertSame( $user_profile_url, $node_user_info->href );

		restore_current_blog();
	}

	protected function get_standard_admin_bar() {
		global $wp_admin_bar;

		_wp_admin_bar_init();

		$this->assertTrue( is_admin_bar_showing() );
		$this->assertInstanceOf( 'WP_Admin_Bar', $wp_admin_bar );

		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		return $wp_admin_bar;
	}

	/**
	 * @ticket 32495
	 *
	 * @dataProvider data_admin_bar_nodes_with_tabindex_meta
	 *
	 * @param array  $node_data     The data for a node, passed to `WP_Admin_Bar::add_node()`.
	 * @param string $expected_html The expected HTML when admin menu is rendered.
	 */
	public function test_admin_bar_with_tabindex_meta( $node_data, $expected_html ) {
		$admin_bar = new WP_Admin_Bar();
		$admin_bar->add_node( $node_data );
		$admin_bar_html = get_echo( array( $admin_bar, 'render' ) );
		$this->assertStringContainsString( $expected_html, $admin_bar_html );
	}

	/**
	 * Data provider for test_admin_bar_with_tabindex_meta().
	 *
	 * @return array {
	 *     @type array {
	 *         @type array  $node_data     The data for a node, passed to `WP_Admin_Bar::add_node()`.
	 *         @type string $expected_html The expected HTML when admin bar is rendered.
	 *     }
	 * }
	 */
	public function data_admin_bar_nodes_with_tabindex_meta() {
		return array(
			array(
				// No tabindex.
				array(
					'id' => 'test-node',
				),
				'<div class="ab-item ab-empty-item" role="menuitem">',
			),
			array(
				// Empty string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '' ),
				),
				'<div class="ab-item ab-empty-item" role="menuitem">',
			),
			array(
				// Integer 1 as string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '1' ),
				),
				'<div class="ab-item ab-empty-item" tabindex="1" role="menuitem">',
			),
			array(
				// Integer -1 as string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '-1' ),
				),
				'<div class="ab-item ab-empty-item" tabindex="-1" role="menuitem">',
			),
			array(
				// Integer 0 as string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '0' ),
				),
				'<div class="ab-item ab-empty-item" tabindex="0" role="menuitem">',
			),
			array(
				// Integer, 0.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => 0 ),
				),
				'<div class="ab-item ab-empty-item" tabindex="0" role="menuitem">',
			),
			array(
				// Integer, 2.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => 2 ),
				),
				'<div class="ab-item ab-empty-item" tabindex="2" role="menuitem">',
			),
			array(
				// Boolean, false.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => false ),
				),
				'<div class="ab-item ab-empty-item" role="menuitem">',
			),
		);
	}

	/**
	 * @ticket 22247
	 */
	public function test_admin_bar_has_edit_link_for_existing_posts() {
		wp_set_current_user( self::$editor_id );

		$post = array(
			'post_author'  => self::$editor_id,
			'post_status'  => 'publish',
			'post_content' => 'Post Content',
			'post_title'   => 'Post Title',
		);
		$id   = wp_insert_post( $post );

		// Set queried object to the newly created post.
		global $wp_the_query;
		$wp_the_query->queried_object = (object) array(
			'ID'        => $id,
			'post_type' => 'post',
		);

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_edit = $wp_admin_bar->get_node( 'edit' );
		$this->assertNotNull( $node_edit );
	}

	/**
	 * @ticket 22247
	 */
	public function test_admin_bar_has_no_edit_link_for_non_existing_posts() {
		wp_set_current_user( self::$editor_id );

		// Set queried object to a non-existing post.
		global $wp_the_query;
		$wp_the_query->queried_object = (object) array(
			'ID'        => 0,
			'post_type' => 'post',
		);

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_edit = $wp_admin_bar->get_node( 'edit' );
		$this->assertNull( $node_edit );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_if_no_static_front_page() {
		set_current_screen( 'edit-post' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_contains_view_archive_link_if_static_front_page() {
		update_option( 'show_on_front', 'page' );
		set_current_screen( 'edit-post' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		$this->assertNotNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_pages() {
		set_current_screen( 'edit-page' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 37949
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_about_link_for_users_with_role() {
		wp_set_current_user( self::$editor_id );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$wp_logo_node = $wp_admin_bar->get_node( 'wp-logo' );
		$about_node   = $wp_admin_bar->get_node( 'about' );

		$this->assertNotNull( $wp_logo_node );
		$this->assertSame( admin_url( 'about.php' ), $wp_logo_node->href );
		$this->assertArrayNotHasKey( 'tabindex', $wp_logo_node->meta );
		$this->assertNotNull( $about_node );
	}

	/**
	 * @ticket 37949
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_about_link_for_users_with_no_role() {
		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$wp_logo_node = $wp_admin_bar->get_node( 'wp-logo' );
		$about_node   = $wp_admin_bar->get_node( 'about' );

		$this->assertNotNull( $wp_logo_node );
		$this->assertFalse( $wp_logo_node->href );
		$this->assertArrayHasKey( 'tabindex', $wp_logo_node->meta );
		$this->assertSame( 0, $wp_logo_node->meta['tabindex'] );
		$this->assertNull( $about_node );
	}

	/**
	 * @ticket 37949
	 * @group multisite
	 * @group ms-required
	 */
	public function test_admin_bar_contains_correct_about_link_for_users_with_no_role_in_multisite() {
		// User is not a member of the site.
		remove_user_from_blog( self::$no_role_id, get_current_blog_id() );

		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$wp_logo_node = $wp_admin_bar->get_node( 'wp-logo' );
		$about_node   = $wp_admin_bar->get_node( 'about' );

		$this->assertNotNull( $wp_logo_node );
		$this->assertSame( user_admin_url( 'about.php' ), $wp_logo_node->href );
		$this->assertArrayNotHasKey( 'tabindex', $wp_logo_node->meta );
		$this->assertNotNull( $about_node );
	}

	/**
	 * Tests that the 'contribute' node is added for users with a role in single site.
	 *
	 * @ticket 23348
	 *
	 * @group ms-excluded
	 *
	 * @covers ::wp_admin_bar_wp_menu
	 */
	public function test_admin_bar_contains_contribute_node_for_users_with_role() {
		wp_set_current_user( self::$editor_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$this->assertNotNull( $wp_admin_bar->get_node( 'contribute' ) );
	}

	/**
	 * Tests that the 'contribute' node is not added for users with no role in single site.
	 *
	 * @ticket 23348
	 *
	 * @group ms-excluded
	 *
	 * @covers ::wp_admin_bar_wp_menu
	 */
	public function test_admin_bar_does_not_contain_contribute_node_for_users_with_no_role() {
		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$this->assertNull( $wp_admin_bar->get_node( 'contribute' ) );
	}

	/**
	 * Tests that the 'contribute' node is added for users with no role in multisite.
	 *
	 * @ticket 23348
	 *
	 * @group multisite
	 * @group ms-required
	 *
	 * @covers ::wp_admin_bar_wp_menu
	 */
	public function test_admin_bar_contains_contribute_node_for_users_with_no_role_in_multisite() {
		// User is not a member of the site.
		remove_user_from_blog( self::$no_role_id, get_current_blog_id() );

		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$this->assertNotNull( $wp_admin_bar->get_node( 'contribute' ) );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_non_public_cpt() {
		register_post_type(
			'foo-non-public',
			array(
				'public'            => false,
				'has_archive'       => true,
				'show_in_admin_bar' => true,
			)
		);

		set_current_screen( 'edit-foo-non-public' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		unregister_post_type( 'foo-non-public' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_cpt_without_archive() {
		register_post_type(
			'foo-non-public',
			array(
				'public'            => true,
				'has_archive'       => false,
				'show_in_admin_bar' => true,
			)
		);

		set_current_screen( 'edit-foo-non-public' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		unregister_post_type( 'foo-non-public' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_cpt_not_shown_in_admin_bar() {
		register_post_type(
			'foo-non-public',
			array(
				'public'            => true,
				'has_archive'       => true,
				'show_in_admin_bar' => false,
			)
		);

		set_current_screen( 'edit-foo-non-public' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		unregister_post_type( 'foo-non-public' );

		$this->assertNull( $node );
	}

	public function map_meta_cap_grant_create_users( $caps, $cap ) {
		if ( 'create_users' === $cap ) {
			$caps = array( 'exist' );
		}

		return $caps;
	}

	public function map_meta_cap_deny_create_users( $caps, $cap ) {
		if ( 'create_users' === $cap ) {
			$caps = array( 'do_not_allow' );
		}

		return $caps;
	}

	public function map_meta_cap_grant_promote_users( $caps, $cap ) {
		if ( 'promote_users' === $cap ) {
			$caps = array( 'exist' );
		}

		return $caps;
	}

	public function map_meta_cap_deny_promote_users( $caps, $cap ) {
		if ( 'promote_users' === $cap ) {
			$caps = array( 'do_not_allow' );
		}

		return $caps;
	}

	/**
	 * @ticket 39252
	 */
	public function test_new_user_link_exists_for_user_with_create_users() {
		wp_set_current_user( self::$admin_id );

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_grant_create_users' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_promote_users' ), 10, 2 );

		$this->assertTrue( current_user_can( 'create_users' ) );
		$this->assertFalse( current_user_can( 'promote_users' ) );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'new-user' );

		// 'create_users' is sufficient in single- and multisite.
		$this->assertNotEmpty( $node );
	}

	/**
	 * @ticket 39252
	 */
	public function test_new_user_link_existence_for_user_with_promote_users() {
		wp_set_current_user( self::$admin_id );

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_create_users' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_grant_promote_users' ), 10, 2 );

		$this->assertFalse( current_user_can( 'create_users' ) );
		$this->assertTrue( current_user_can( 'promote_users' ) );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'new-user' );

		if ( is_multisite() ) {
			$this->assertNotEmpty( $node );
		} else {
			// 'promote_users' is insufficient in single-site.
			$this->assertNull( $node );
		}
	}

	/**
	 * @ticket 39252
	 */
	public function test_new_user_link_does_not_exist_for_user_without_create_or_promote_users() {
		wp_set_current_user( self::$admin_id );

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_create_users' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_promote_users' ), 10, 2 );

		$this->assertFalse( current_user_can( 'create_users' ) );
		$this->assertFalse( current_user_can( 'promote_users' ) );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'new-user' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 30937
	 * @covers ::wp_admin_bar_customize_menu
	 */
	public function test_customize_link() {
		global $wp_customize;
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$uuid = wp_generate_uuid4();
		$this->go_to( home_url( "/?customize_changeset_uuid=$uuid" ) );
		wp_set_current_user( self::$admin_id );

		self::factory()->post->create(
			array(
				'post_type'   => 'customize_changeset',
				'post_status' => 'auto-draft',
				'post_name'   => $uuid,
			)
		);
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		$wp_customize->start_previewing_theme();

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'customize' );
		$this->assertNotEmpty( $node );

		$parsed_url   = wp_parse_url( $node->href );
		$query_params = array();
		wp_parse_str( $parsed_url['query'], $query_params );
		$this->assertSame( $uuid, $query_params['changeset_uuid'] );
		$this->assertStringNotContainsString( 'changeset_uuid', $query_params['url'] );
	}

	/**
	 * @ticket 39082
	 * @group ms-required
	 * @dataProvider data_my_sites_network_menu_items
	 */
	public function test_my_sites_network_menu_for_regular_user( $id, $cap ) {
		wp_set_current_user( self::$editor_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$nodes = $wp_admin_bar->get_nodes();
		$this->assertArrayNotHasKey( $id, $nodes, sprintf( 'Menu item %s must not display for a regular user.', $id ) );
	}

	/**
	 * @ticket 39082
	 * @group ms-required
	 * @dataProvider data_my_sites_network_menu_items
	 */
	public function test_my_sites_network_menu_for_super_admin( $id, $cap ) {
		wp_set_current_user( self::$editor_id );

		grant_super_admin( self::$editor_id );
		$wp_admin_bar = $this->get_standard_admin_bar();
		revoke_super_admin( self::$editor_id );

		$nodes = $wp_admin_bar->get_nodes();
		$this->assertArrayHasKey( $id, $nodes, sprintf( 'Menu item %s must display for a super admin.', $id ) );
	}

	/**
	 * @ticket 39082
	 * @group ms-required
	 * @dataProvider data_my_sites_network_menu_items
	 */
	public function test_my_sites_network_menu_for_regular_user_with_network_caps( $id, $cap ) {
		global $current_user;

		$network_user_caps = array( 'manage_network', 'manage_network_themes', 'manage_network_plugins' );

		wp_set_current_user( self::$editor_id );

		foreach ( $network_user_caps as $network_cap ) {
			$current_user->add_cap( $network_cap );
		}
		$wp_admin_bar = $this->get_standard_admin_bar();
		foreach ( $network_user_caps as $network_cap ) {
			$current_user->remove_cap( $network_cap );
		}

		$nodes = $wp_admin_bar->get_nodes();
		if ( in_array( $cap, $network_user_caps, true ) ) {
			$this->assertArrayHasKey( $id, $nodes, sprintf( 'Menu item %1$s must display for a user with the %2$s cap.', $id, $cap ) );
		} else {
			$this->assertArrayNotHasKey( $id, $nodes, sprintf( 'Menu item %1$s must not display for a user without the %2$s cap.', $id, $cap ) );
		}
	}

	/**
	 * Data provider for test_my_sites_network_menu_for_regular_user() and
	 * test_my_sites_network_menu_for_super_admin().
	 *
	 * @return array {
	 *     @type array {
	 *         @type string $id  The ID of the menu item.
	 *         @type string $cap The capability required to see the menu item.
	 *     }
	 * }
	 */
	public function data_my_sites_network_menu_items() {
		return array(
			array( 'my-sites-super-admin', 'manage_network' ),
			array( 'network-admin', 'manage_network' ),
			array( 'network-admin-d', 'manage_network' ),
			array( 'network-admin-s', 'manage_sites' ),
			array( 'network-admin-u', 'manage_network_users' ),
			array( 'network-admin-t', 'manage_network_themes' ),
			array( 'network-admin-p', 'manage_network_plugins' ),
			array( 'network-admin-o', 'manage_network_options' ),
		);
	}

	/**
	 * This test ensures that WP_Admin_Bar::$proto is not defined (including magic methods).
	 *
	 * @ticket 56876
	 * @coversNothing
	 */
	public function test_proto_property_is_not_defined() {
		$admin_bar = new WP_Admin_Bar();
		$this->assertFalse( property_exists( $admin_bar, 'proto' ), 'WP_Admin_Bar::$proto should not be defined.' );
		$this->assertFalse( isset( $admin_bar->proto ), 'WP_Admin_Bar::$proto should not be defined.' );
	}

	/**
	 * This test ensures that WP_Admin_Bar::$menu is declared as a "regular" class property.
	 *
	 * @ticket 56876
	 * @coversNothing
	 */
	public function test_menu_property_is_defined() {
		$admin_bar = new WP_Admin_Bar();
		$this->assertTrue( property_exists( $admin_bar, 'menu' ), 'WP_Admin_Bar::$proto property should be defined.' );

		$menu_property = new ReflectionProperty( WP_Admin_Bar::class, 'menu' );
		$this->assertTrue( $menu_property->isPublic(), 'WP_Admin_Bar::$menu should be public.' );

		$this->assertTrue( isset( $admin_bar->menu ), 'WP_Admin_Bar::$menu should be set.' );
		$this->assertSame( array(), $admin_bar->menu, 'WP_Admin_Bar::$menu should be equal to an empty array.' );
	}

	/**
	 * Test initialize() method sets up user object correctly for logged-in users.
	 *
	 * @covers WP_Admin_Bar::initialize
	 */
	public function test_initialize_sets_user_for_logged_in_user() {
		wp_set_current_user( self::$editor_id );

		$admin_bar = new WP_Admin_Bar();
		$admin_bar->initialize();

		$this->assertInstanceOf( 'stdClass', $admin_bar->user );
		$this->assertIsArray( $admin_bar->user->blogs );
		$this->assertNotEmpty( $admin_bar->user->blogs );
	}

	/**
	 * Test initialize() method for non-logged-in users.
	 *
	 * @covers WP_Admin_Bar::initialize
	 */
	public function test_initialize_creates_empty_user_for_logged_out() {
		wp_set_current_user( 0 );

		$admin_bar = new WP_Admin_Bar();
		$admin_bar->initialize();

		$this->assertInstanceOf( 'stdClass', $admin_bar->user );
	}

	/**
	 * Test add_node() with empty ID generates deprecated warning.
	 *
	 * @covers WP_Admin_Bar::add_node
	 */
	public function test_add_node_with_empty_id_triggers_doing_it_wrong() {
		$admin_bar = new WP_Admin_Bar();

		$this->setExpectedIncorrectUsage( 'WP_Admin_Bar::add_node' );
		$admin_bar->add_node(
			array(
				'title' => 'Test Node',
			)
		);

		$node = $admin_bar->get_node( 'test-node' );
		$this->assertNotNull( $node );
	}

	/**
	 * Test add_node() with no title and no ID does nothing.
	 *
	 * @covers WP_Admin_Bar::add_node
	 */
	public function test_add_node_with_no_id_no_title_does_nothing() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node( array() );

		$nodes = $admin_bar->get_nodes();
		$this->assertNull( $nodes );
	}

	/**
	 * Test add_node() converts object arguments to array.
	 *
	 * @covers WP_Admin_Bar::add_node
	 */
	public function test_add_node_converts_object_to_array() {
		$admin_bar = new WP_Admin_Bar();

		$node_object       = new stdClass();
		$node_object->id   = 'test-object-node';
		$node_object->title = 'Test Object';

		$admin_bar->add_node( $node_object );

		$node = $admin_bar->get_node( 'test-object-node' );
		$this->assertNotNull( $node );
		$this->assertSame( 'Test Object', $node->title );
	}

	/**
	 * Test add_node() with deprecated parent arguments.
	 *
	 * @covers WP_Admin_Bar::add_node
	 */
	public function test_add_node_with_deprecated_parent() {
		$admin_bar = new WP_Admin_Bar();

		$this->setExpectedDeprecated( 'WP_Admin_Bar::add_node' );

		$admin_bar->add_node(
			array(
				'id'     => 'test-child',
				'parent' => 'my-account-with-avatar',
				'title'  => 'Test Child',
			)
		);

		$node = $admin_bar->get_node( 'test-child' );
		$this->assertSame( 'my-account', $node->parent );
	}

	/**
	 * Test add_node() with deprecated 'my-blogs' parent.
	 *
	 * @covers WP_Admin_Bar::add_node
	 */
	public function test_add_node_with_deprecated_my_blogs_parent() {
		$admin_bar = new WP_Admin_Bar();

		$this->setExpectedDeprecated( 'WP_Admin_Bar::add_node' );

		$admin_bar->add_node(
			array(
				'id'     => 'test-child',
				'parent' => 'my-blogs',
				'title'  => 'Test Child',
			)
		);

		$node = $admin_bar->get_node( 'test-child' );
		$this->assertSame( 'my-sites', $node->parent );
	}

	/**
	 * Test get_node() returns null for non-existent node.
	 *
	 * @covers WP_Admin_Bar::get_node
	 */
	public function test_get_node_returns_null_for_non_existent_node() {
		$admin_bar = new WP_Admin_Bar();

		$node = $admin_bar->get_node( 'non-existent-node' );
		$this->assertNull( $node );
	}

	/**
	 * Test get_node() returns cloned object.
	 *
	 * @covers WP_Admin_Bar::get_node
	 */
	public function test_get_node_returns_cloned_object() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-clone',
				'title' => 'Test Clone',
			)
		);

		$node1 = $admin_bar->get_node( 'test-clone' );
		$node2 = $admin_bar->get_node( 'test-clone' );

		$this->assertNotSame( $node1, $node2, 'get_node() should return a clone, not the same object.' );
		$this->assertEquals( $node1, $node2, 'Cloned nodes should be equal.' );
	}

	/**
	 * Test get_node() with empty ID returns root node.
	 *
	 * @covers WP_Admin_Bar::get_node
	 */
	public function test_get_node_with_empty_id_returns_null() {
		$admin_bar = new WP_Admin_Bar();

		$node = $admin_bar->get_node( '' );
		$this->assertNull( $node );
	}

	/**
	 * Test get_nodes() returns null when no nodes exist.
	 *
	 * @covers WP_Admin_Bar::get_nodes
	 */
	public function test_get_nodes_returns_null_when_empty() {
		$admin_bar = new WP_Admin_Bar();

		$nodes = $admin_bar->get_nodes();
		$this->assertNull( $nodes );
	}

	/**
	 * Test get_nodes() returns cloned nodes.
	 *
	 * @covers WP_Admin_Bar::get_nodes
	 */
	public function test_get_nodes_returns_cloned_nodes() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-node-1',
				'title' => 'Test Node 1',
			)
		);

		$nodes1 = $admin_bar->get_nodes();
		$nodes2 = $admin_bar->get_nodes();

		$this->assertNotSame( $nodes1['test-node-1'], $nodes2['test-node-1'], 'get_nodes() should return clones.' );
	}

	/**
	 * Test get_nodes() returns null after binding.
	 *
	 * @covers WP_Admin_Bar::get_nodes
	 * @covers WP_Admin_Bar::_bind
	 */
	public function test_get_nodes_returns_null_after_bind() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-node',
				'title' => 'Test Node',
			)
		);

		$admin_bar->render();

		$nodes = $admin_bar->get_nodes();
		$this->assertNull( $nodes, 'get_nodes() should return null after binding.' );
	}

	/**
	 * Test add_group() creates a group node.
	 *
	 * @covers WP_Admin_Bar::add_group
	 */
	public function test_add_group_creates_group_node() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_group(
			array(
				'id'     => 'test-group',
				'parent' => 'root',
			)
		);

		$node = $admin_bar->get_node( 'test-group' );
		$this->assertNotNull( $node );
		$this->assertTrue( $node->group );
	}

	/**
	 * Test add_group() with meta data.
	 *
	 * @covers WP_Admin_Bar::add_group
	 */
	public function test_add_group_with_meta() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_group(
			array(
				'id'   => 'test-group',
				'meta' => array( 'class' => 'custom-class' ),
			)
		);

		$node = $admin_bar->get_node( 'test-group' );
		$this->assertSame( 'custom-class', $node->meta['class'] );
	}

	/**
	 * Test remove_node() removes a node.
	 *
	 * @covers WP_Admin_Bar::remove_node
	 */
	public function test_remove_node_removes_node() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-remove',
				'title' => 'Test Remove',
			)
		);

		$this->assertNotNull( $admin_bar->get_node( 'test-remove' ) );

		$admin_bar->remove_node( 'test-remove' );

		$this->assertNull( $admin_bar->get_node( 'test-remove' ) );
	}

	/**
	 * Test remove_node() on non-existent node does nothing.
	 *
	 * @covers WP_Admin_Bar::remove_node
	 */
	public function test_remove_node_on_non_existent_does_nothing() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->remove_node( 'non-existent' );

		$nodes = $admin_bar->get_nodes();
		$this->assertNull( $nodes );
	}

	/**
	 * Test add_menu() is an alias for add_node().
	 *
	 * @covers WP_Admin_Bar::add_menu
	 */
	public function test_add_menu_is_alias_for_add_node() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_menu(
			array(
				'id'    => 'test-menu',
				'title' => 'Test Menu',
			)
		);

		$node = $admin_bar->get_node( 'test-menu' );
		$this->assertNotNull( $node );
		$this->assertSame( 'Test Menu', $node->title );
	}

	/**
	 * Test remove_menu() is an alias for remove_node().
	 *
	 * @covers WP_Admin_Bar::remove_menu
	 */
	public function test_remove_menu_is_alias_for_remove_node() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-menu',
				'title' => 'Test Menu',
			)
		);

		$admin_bar->remove_menu( 'test-menu' );

		$this->assertNull( $admin_bar->get_node( 'test-menu' ) );
	}

	/**
	 * Test recursive_render() is deprecated.
	 *
	 * @covers WP_Admin_Bar::recursive_render
	 */
	public function test_recursive_render_is_deprecated() {
		$admin_bar = new WP_Admin_Bar();

		$node       = new stdClass();
		$node->type = 'item';
		$node->id   = 'test-recursive';
		$node->title = 'Test';
		$node->href = '#';
		$node->parent = 'root';
		$node->children = array();
		$node->meta = array();

		$this->setExpectedDeprecated( 'WP_Admin_Bar::recursive_render' );

		ob_start();
		$admin_bar->recursive_render( 'test-recursive', $node );
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
	}

	/**
	 * Test _bind() creates root node.
	 *
	 * @covers WP_Admin_Bar::_bind
	 */
	public function test_bind_creates_root_node() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-node',
				'title' => 'Test Node',
			)
		);

		$reflection = new ReflectionClass( $admin_bar );
		$bind_method = $reflection->getMethod( '_bind' );
		$bind_method->setAccessible( true );

		$root = $bind_method->invoke( $admin_bar );

		$this->assertNotNull( $root );
		$this->assertSame( 'root', $root->id );
	}

	/**
	 * Test _bind() assigns orphan nodes to root.
	 *
	 * @covers WP_Admin_Bar::_bind
	 */
	public function test_bind_assigns_orphans_to_root() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'orphan-node',
				'title' => 'Orphan Node',
			)
		);

		$reflection = new ReflectionClass( $admin_bar );
		$bind_method = $reflection->getMethod( '_bind' );
		$bind_method->setAccessible( true );

		$root = $bind_method->invoke( $admin_bar );

		$this->assertNotEmpty( $root->children );
		$this->assertSame( 'orphan-node', $root->children[0]->id );
	}

	/**
	 * Test _bind() only runs once.
	 *
	 * @covers WP_Admin_Bar::_bind
	 */
	public function test_bind_only_runs_once() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-node',
				'title' => 'Test Node',
			)
		);

		$reflection = new ReflectionClass( $admin_bar );
		$bind_method = $reflection->getMethod( '_bind' );
		$bind_method->setAccessible( true );

		$root1 = $bind_method->invoke( $admin_bar );
		$root2 = $bind_method->invoke( $admin_bar );

		$this->assertNotNull( $root1 );
		$this->assertNull( $root2, '_bind() should return null on second call.' );
	}

	/**
	 * Test render() generates output.
	 *
	 * @covers WP_Admin_Bar::render
	 */
	public function test_render_generates_output() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-render',
				'title' => 'Test Render',
			)
		);

		ob_start();
		$admin_bar->render();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'wpadminbar', $output );
		$this->assertStringContainsString( 'Test Render', $output );
	}

	/**
	 * Test render() includes mobile class on mobile devices.
	 *
	 * @covers WP_Admin_Bar::render
	 */
	public function test_render_includes_mobile_class() {
		add_filter( 'wp_is_mobile', '__return_true' );

		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-mobile',
				'title' => 'Test Mobile',
			)
		);

		ob_start();
		$admin_bar->render();
		$output = ob_get_clean();

		remove_filter( 'wp_is_mobile', '__return_true' );

		$this->assertStringContainsString( 'mobile', $output );
	}

	/**
	 * Test render() with nested items creates default group.
	 *
	 * @covers WP_Admin_Bar::render
	 * @covers WP_Admin_Bar::_bind
	 */
	public function test_render_nested_items_creates_default_group() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'parent-item',
				'title' => 'Parent Item',
			)
		);

		$admin_bar->add_node(
			array(
				'id'     => 'child-item',
				'parent' => 'parent-item',
				'title'  => 'Child Item',
			)
		);

		ob_start();
		$admin_bar->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'parent-item-default', $output );
	}

	/**
	 * Test add_node() with all meta attributes.
	 *
	 * @covers WP_Admin_Bar::add_node
	 */
	public function test_add_node_with_all_meta_attributes() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-meta',
				'title' => 'Test Meta',
				'href'  => 'http://example.com',
				'meta'  => array(
					'class'      => 'custom-class',
					'rel'        => 'nofollow',
					'lang'       => 'en',
					'dir'        => 'ltr',
					'onclick'    => 'alert("test")',
					'target'     => '_blank',
					'title'      => 'Custom Title',
					'tabindex'   => 1,
					'menu_title' => 'Menu Title',
				),
			)
		);

		$node = $admin_bar->get_node( 'test-meta' );
		$this->assertSame( 'custom-class', $node->meta['class'] );
		$this->assertSame( 'nofollow', $node->meta['rel'] );
		$this->assertSame( 'en', $node->meta['lang'] );
		$this->assertSame( 'ltr', $node->meta['dir'] );
		$this->assertSame( 'alert("test")', $node->meta['onclick'] );
		$this->assertSame( '_blank', $node->meta['target'] );
		$this->assertSame( 'Custom Title', $node->meta['title'] );
		$this->assertSame( 1, $node->meta['tabindex'] );
		$this->assertSame( 'Menu Title', $node->meta['menu_title'] );
	}

	/**
	 * Test node with href renders as anchor tag.
	 *
	 * @covers WP_Admin_Bar::render
	 * @covers WP_Admin_Bar::_render_item
	 */
	public function test_node_with_href_renders_as_anchor() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-link',
				'title' => 'Test Link',
				'href'  => 'http://example.com',
			)
		);

		ob_start();
		$admin_bar->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<a', $output );
		$this->assertStringContainsString( 'href=\'http://example.com\'', $output );
	}

	/**
	 * Test node without href renders as div.
	 *
	 * @covers WP_Admin_Bar::render
	 * @covers WP_Admin_Bar::_render_item
	 */
	public function test_node_without_href_renders_as_div() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-no-link',
				'title' => 'Test No Link',
			)
		);

		ob_start();
		$admin_bar->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'ab-empty-item', $output );
	}

	/**
	 * Test parent node with children includes arrow and menupop class.
	 *
	 * @covers WP_Admin_Bar::render
	 * @covers WP_Admin_Bar::_render_item
	 */
	public function test_parent_node_with_children_includes_arrow() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'parent',
				'title' => 'Parent',
			)
		);

		$admin_bar->add_node(
			array(
				'id'     => 'child',
				'parent' => 'parent',
				'title'  => 'Child',
			)
		);

		ob_start();
		$admin_bar->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'menupop', $output );
		$this->assertStringContainsString( 'aria-expanded="false"', $output );
	}

	/**
	 * Test node with custom HTML in meta.
	 *
	 * @covers WP_Admin_Bar::render
	 * @covers WP_Admin_Bar::_render_item
	 */
	public function test_node_with_custom_html_in_meta() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'    => 'test-html',
				'title' => 'Test HTML',
				'meta'  => array(
					'html' => '<span class="custom">Custom HTML</span>',
				),
			)
		);

		ob_start();
		$admin_bar->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<span class="custom">Custom HTML</span>', $output );
	}

	/**
	 * Test _bind() wraps nested groups in container.
	 *
	 * @covers WP_Admin_Bar::_bind
	 */
	public function test_bind_wraps_nested_groups_in_container() {
		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_group(
			array(
				'id' => 'parent-group',
			)
		);

		$admin_bar->add_group(
			array(
				'id'     => 'child-group',
				'parent' => 'parent-group',
			)
		);

		$reflection = new ReflectionClass( $admin_bar );
		$bind_method = $reflection->getMethod( '_bind' );
		$bind_method->setAccessible( true );

		$root = $bind_method->invoke( $admin_bar );

		$nodes_method = $reflection->getMethod( '_get_nodes' );
		$nodes_method->setAccessible( true );
		$nodes = $nodes_method->invoke( $admin_bar );

		$this->assertArrayHasKey( 'parent-group-container', $nodes );
		$this->assertSame( 'container', $nodes['parent-group-container']->type );
	}
}
