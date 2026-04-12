<?php

/**
 * Unit tests covering WP_Block_Processor functionality.
 *
 * @package WordPress
 * @subpackage HTML-API
 *
 * @since 6.9.0
 *
 * @group block-processor
 *
 * @coversDefaultClass WP_Block_Processor
 */
class Tests_Blocks_BlockProcessor_BlockProcessing extends WP_UnitTestCase {
	public function test_get_breadcrumbs() {
		$processor = new WP_Block_Processor( '<!-- wp:top --><!-- wp:inside /--><!-- /wp:top -->' );

		$this->assertTrue(
			$processor->next_delimiter(),
			'Should have found the opening "top" delimiter but found nothing.'
		);

		$this->assertSame(
			array( 'core/top' ),
			$processor->get_breadcrumbs(),
			'Should have found only the single opening delimiter.'
		);

		$processor->next_delimiter();
		$this->assertSame(
			array( 'core/top', 'core/inside' ),
			$processor->get_breadcrumbs(),
			'Should have detected the nesting structure of the blocks.'
		);
	}

	public function test_get_depth() {
		// Create a deeply-nested stack of blocks.
		$html      = '';
		$max_depth = 10;

		for ( $i = 0; $i < $max_depth; $i++ ) {
			$html .= "<!-- wp:ladder {\"level\":{$i}} -->";
		}

		for ( $i = 0; $i < $max_depth; $i++ ) {
			$html .= '<!-- /wp:ladder -->';
		}

		$processor = new WP_Block_Processor( $html );

		for ( $i = 0; $i < $max_depth; $i++ ) {
			$nth = $i + 1;

			$this->assertTrue(
				$processor->next_delimiter(),
				"Should have found opening delimiter #{$nth}: check test setup."
			);

			$this->assertSame(
				$i + 1,
				$processor->get_depth(),
				"Should have identified the proper depth of opening delimiter #{$nth}."
			);
		}

		for ( $i = 0; $i < $max_depth; $i++ ) {
			$nth = $i + 1;

			$this->assertTrue(
				$processor->next_delimiter(),
				"Should have found closing delimiter #{$nth}: check test setup."
			);

			$this->assertSame(
				$max_depth - $i - 1,
				$processor->get_depth(),
				"Should have identified the proper depth of closing delimiter #{$nth}."
			);
		}
	}

	/**
	 * @dataProvider data_block_content
	 */
	public function test_builds_block( $block_content ) {
		$processor = new WP_Block_Processor( $block_content );

		$extracted = array();
		while ( $processor->next_block( '*' ) ) {
			$extracted[] = $processor->extract_full_block_and_advance();
		}

		$this->assertSame(
			parse_blocks( $block_content ),
			$extracted,
			'Should have extracted a block matching the input group block.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public static function data_block_content() {
		$contents = array(
			'no blocks, just freeform HTML',
			'<!-- wp:void /-->',
			'<!-- wp:paragraph --><p>Inner HTML</p><!-- /wp:paragraph -->',
			<<<HTML
<!-- wp:cover -->
<img>
<!-- /wp:cover -->

<!-- wp:group -->
<!-- wp:heading {"level":2} -->
<h2>Testing works!</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Who knew?</p>
<!-- /wp:paragraph -->
<!-- /wp:group -->
HTML
			,
		);

		return array_map(
			function ( $content ) {
				return array( $content );
			},
			$contents
		);
	}

	/**
	 * Tests that deeply nested blocks are correctly extracted.
	 *
	 * This test validates the recursion handling in extract_full_block_and_advance()
	 * to ensure it can handle deep nesting without stack overflow or data corruption.
	 *
	 * @ticket TBD
	 */
	public function test_extract_deeply_nested_blocks() {
		// Create 20 levels of nesting to test recursion depth.
		$max_depth = 20;
		$html      = '';

		// Build nested structure.
		for ( $i = 0; $i < $max_depth; $i++ ) {
			$html .= "<!-- wp:group {\"level\":{$i}} -->\n";
			$html .= "<div class=\"level-{$i}\">\n";
		}

		// Add content at the deepest level.
		$html .= "<!-- wp:paragraph -->\n";
		$html .= "<p>Deeply nested content</p>\n";
		$html .= "<!-- /wp:paragraph -->\n";

		// Close all the nested groups.
		for ( $i = $max_depth - 1; $i >= 0; $i-- ) {
			$html .= "</div>\n";
			$html .= "<!-- /wp:group -->\n";
		}

		$processor = new WP_Block_Processor( $html );
		$blocks    = array();

		while ( $processor->next_block( '*' ) ) {
			$blocks[] = $processor->extract_full_block_and_advance();
		}

		// Verify extraction matches parse_blocks().
		$expected = parse_blocks( $html );
		$this->assertSame(
			$expected,
			$blocks,
			'Deeply nested blocks should be extracted correctly.'
		);

		// Verify the nesting depth is correct.
		$this->assertCount( 1, $blocks, 'Should have one top-level block.' );

		// Traverse the nested structure to verify integrity.
		$current_block = $blocks[0];
		for ( $i = 0; $i < $max_depth - 1; $i++ ) {
			$this->assertSame(
				'core/group',
				$current_block['blockName'],
				"Block at level {$i} should be a group."
			);
			$this->assertArrayHasKey( 'level', $current_block['attrs'], "Block at level {$i} should have level attribute." );
			$this->assertSame( $i, $current_block['attrs']['level'], "Block at level {$i} should have correct level value." );
			$this->assertCount( 1, $current_block['innerBlocks'], "Block at level {$i} should have exactly one inner block." );
			$current_block = $current_block['innerBlocks'][0];
		}

		// Verify the innermost block (last group before paragraph).
		$this->assertSame( 'core/group', $current_block['blockName'], 'Innermost group should be a group block.' );
		$this->assertCount( 1, $current_block['innerBlocks'], 'Innermost group should have one inner block.' );

		// Verify the paragraph at the deepest level.
		$paragraph = $current_block['innerBlocks'][0];
		$this->assertSame( 'core/paragraph', $paragraph['blockName'], 'Deepest block should be a paragraph.' );
		$this->assertStringContainsString( 'Deeply nested content', $paragraph['innerHTML'], 'Paragraph should contain the expected content.' );
	}

	/**
	 * Tests extraction of blocks with multiple siblings at various nesting levels.
	 *
	 * @ticket TBD
	 */
	public function test_extract_nested_blocks_with_siblings() {
		$html = <<<HTML
<!-- wp:group -->
<!-- wp:paragraph -->
<p>First paragraph</p>
<!-- /wp:paragraph -->

<!-- wp:group -->
<!-- wp:paragraph -->
<p>Nested paragraph 1</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Nested paragraph 2</p>
<!-- /wp:paragraph -->
<!-- /wp:group -->

<!-- wp:paragraph -->
<p>Second paragraph</p>
<!-- /wp:paragraph -->
<!-- /wp:group -->
HTML;

		$processor = new WP_Block_Processor( $html );
		$blocks    = array();

		while ( $processor->next_block( '*' ) ) {
			$blocks[] = $processor->extract_full_block_and_advance();
		}

		$expected = parse_blocks( $html );
		$this->assertSame(
			$expected,
			$blocks,
			'Nested blocks with siblings should be extracted correctly.'
		);

		// Verify structure.
		$this->assertCount( 1, $blocks, 'Should have one top-level block.' );
		$this->assertSame( 'core/group', $blocks[0]['blockName'], 'Top-level block should be a group.' );
		$this->assertCount( 3, $blocks[0]['innerBlocks'], 'Top-level group should have 3 inner blocks.' );

		// Verify the nested group.
		$nested_group = $blocks[0]['innerBlocks'][1];
		$this->assertSame( 'core/group', $nested_group['blockName'], 'Second inner block should be a group.' );
		$this->assertCount( 2, $nested_group['innerBlocks'], 'Nested group should have 2 inner blocks.' );
	}
}
