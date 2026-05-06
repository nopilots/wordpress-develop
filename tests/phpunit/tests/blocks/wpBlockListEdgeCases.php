<?php
/**
 * Additional edge-case tests for WP_Block_List.
 *
 * @package WordPress
 * @subpackage Blocks
 *
 * @group blocks
 */
class Tests_Blocks_wpBlockListEdgeCases extends WP_UnitTestCase {

	/**
	 * Fake block type registry.
	 *
	 * @var WP_Block_Type_Registry|null
	 */
	private $registry = null;

	/**
	 * Set up each test method.
	 */
	public function set_up() {
		parent::set_up();

		$this->registry = new WP_Block_Type_Registry();
		$this->registry->register( 'core/example', array() );
	}

	/**
	 * Tear down each test method.
	 */
	public function tear_down() {
		$this->registry = null;

		parent::tear_down();
	}

	public function test_offset_get_converts_parsed_block_to_wp_block_and_caches_instance() {
		$parsed_blocks = parse_blocks( '<!-- wp:example /-->' );
		$context       = array();
		$blocks        = new WP_Block_List( $parsed_blocks, $context, $this->registry );

		$block_first_access = $blocks[0];
		$this->assertInstanceOf( 'WP_Block', $block_first_access );
		$this->assertSame( 'core/example', $block_first_access->name );

		$block_second_access = $blocks[0];
		$this->assertSame( $block_first_access, $block_second_access );
	}

	public function test_offset_set_appends_when_offset_is_null() {
		$context = array();
		$blocks  = new WP_Block_List( array(), $context, $this->registry );

		$parsed_block = parse_blocks( '<!-- wp:example /-->' )[0];
		$blocks[]     = new WP_Block( $parsed_block, $context, $this->registry );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/example', $blocks[0]->name );
	}

	public function test_unset_does_not_reindex_and_iterator_respects_remaining_key() {
		$parsed_blocks = parse_blocks( '<!-- wp:example /--><!-- wp:example /-->' );
		$context       = array();
		$blocks        = new WP_Block_List( $parsed_blocks, $context, $this->registry );

		unset( $blocks[0] );
		$this->assertCount( 1, $blocks );
		$this->assertFalse( isset( $blocks[0] ) );
		$this->assertTrue( isset( $blocks[1] ) );

		$blocks->rewind();
		$this->assertTrue( $blocks->valid() );
		$this->assertSame( 1, $blocks->key() );
		$this->assertSame( 'core/example', $blocks->current()->name );

		$blocks->next();
		$this->assertFalse( $blocks->valid() );
	}
}

