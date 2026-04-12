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
	 * Verifies that the processor mirrors parse_blocks() behavior when
	 * encountering mismatched closing delimiters.
	 *
	 * According to the spec parser (parse_blocks()), closing delimiters
	 * do not need to match the block type of the opener. The processor
	 * should mirror this behavior by popping the stack on any closer,
	 * regardless of whether the name matches.
	 *
	 * This test documents that name matching is intentionally not performed,
	 * which is spec-compliant behavior.
	 *
	 * @ticket 61401
	 *
	 * @dataProvider data_mismatched_closer_content
	 *
	 * @param string $html HTML with mismatched closing delimiters.
	 */
	public function test_handles_mismatched_closers_like_parse_blocks( $html ) {
		$processor = new WP_Block_Processor( $html );

		$extracted = array();
		while ( $processor->next_block( '*' ) ) {
			$extracted[] = $processor->extract_full_block_and_advance();
		}

		$this->assertSame(
			parse_blocks( $html ),
			$extracted,
			'Should match parse_blocks() behavior for mismatched closers.'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public static function data_mismatched_closer_content() {
		return array(
			'Simple mismatch'                  => array(
				'<!-- wp:paragraph -->content<!-- /wp:group -->',
			),
			'Mismatch with namespaces'         => array(
				'<!-- wp:core/paragraph -->content<!-- /wp:core/group -->',
			),
			'Mismatch with different plugins'  => array(
				'<!-- wp:plugin-a/block -->content<!-- /wp:plugin-b/block -->',
			),
			'Nested with outer mismatch'       => array(
				'<!-- wp:outer --><!-- wp:inner /--><!-- /wp:different -->',
			),
			'Multiple nested mismatches'       => array(
				'<!-- wp:a --><!-- wp:b --><!-- wp:c /--><!-- /wp:x --><!-- /wp:y -->',
			),
			'Mismatch in complex structure'    => array(
				<<<HTML
<!-- wp:group -->
<div>
<!-- wp:paragraph -->
<p>Text</p>
<!-- /wp:columns -->
</div>
<!-- /wp:section -->
HTML
				,
			),
		);
	}
}
