<?php
/**
 * Additional WP_Customize_Manager tests.
 *
 * @package WordPress
 */

/**
 * Tests for additional WP_Customize_Manager behaviors.
 *
 * @group customize
 */
class Tests_WP_Customize_Manager_Additional extends WP_UnitTestCase {

	/**
	 * Customize manager instance re-instantiated with each test.
	 *
	 * @var WP_Customize_Manager
	 */
	public $manager;

	/**
	 * Set up test.
	 */
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$GLOBALS['wp_customize'] = new WP_Customize_Manager();
		$this->manager           = $GLOBALS['wp_customize'];
	}

	/**
	 * Tear down test.
	 */
	public function tear_down() {
		$this->manager = null;
		unset( $GLOBALS['wp_customize'] );
		parent::tear_down();
	}

	/**
	 * Ensure the messenger channel value is sanitized.
	 *
	 * @ticket 61165
	 * @covers WP_Customize_Manager::get_messenger_channel
	 * @covers WP_Customize_Manager::__construct
	 */
	public function test_get_messenger_channel_sanitized() {
		$wp_customize = new WP_Customize_Manager(
			array(
				'messenger_channel' => " preview-123\n\t",
			)
		);

		$this->assertSame( 'preview-123', $wp_customize->get_messenger_channel() );
	}

	/**
	 * Ensure that calling unsanitized_post_values() with no input yields an empty array.
	 *
	 * @covers WP_Customize_Manager::unsanitized_post_values
	 */
	public function test_unsanitized_post_values_empty() {
		unset( $_POST['customized'] );
		unset( $_POST['customize_changeset_data'] );
		unset( $_POST['customize_autosave'] );
		unset( $_POST['customize_theme'] );

		$this->assertSame( array(), $this->manager->unsanitized_post_values() );
	}
}

