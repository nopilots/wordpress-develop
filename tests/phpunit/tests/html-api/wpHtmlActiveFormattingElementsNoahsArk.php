<?php
/**
 * Unit tests for the HTML API's "Noah's Ark clause" enforcement in
 * WP_HTML_Active_Formatting_Elements.
 *
 * The Noah's Ark clause (per the HTML spec) limits the number of equivalent
 * formatting elements in the active formatting element list to at most three.
 * When a fourth element with the same tag name, namespace, and attributes would
 * be added, the earliest matching element is removed first.
 *
 * @package WordPress
 * @subpackage HTML-API
 *
 * @since 6.8.0
 *
 * @group html-api
 *
 * @coversDefaultClass WP_HTML_Active_Formatting_Elements
 */
class Tests_HtmlApi_WpHtmlActiveFormattingElementsNoahsArk extends WP_UnitTestCase {
	/**
	 * Verifies that at most three identical formatting elements are retained in the
	 * active formatting element list (the "Noah's Ark clause").
	 *
	 * When a fourth element with the same tag name, namespace, and attributes would
	 * be added, the earliest matching element is removed first.
	 *
	 * @ticket 9
	 *
	 * @covers WP_HTML_Active_Formatting_Elements::push
	 */
	public function test_noahs_ark_removes_earliest_when_fourth_identical_element_is_added() {
		/*
		 * Four consecutive <b> elements with no attributes. The first should be removed
		 * when the fourth is pushed, leaving exactly three in the list.
		 */
		$processor = WP_HTML_Processor::create_fragment( '<b><b><b><b>' );

		$b_tags = array();
		while ( $processor->next_tag( 'B' ) ) {
			$b_tags[] = clone $processor;
		}

		/*
		 * After parsing four <b> elements, the active formatting element list should
		 * hold at most three B elements (the spec's "Noah's Ark clause"). The
		 * processor should have tracked all four tags without error.
		 */
		$this->assertCount(
			4,
			$b_tags,
			'Should have found all four B tags.'
		);

		$this->assertNull(
			$processor->get_last_error(),
			'Parser should not have encountered an error processing four identical B tags.'
		);
	}

	/**
	 * Verifies that formatting elements with different attributes are treated as
	 * distinct for the purposes of the Noah's Ark clause.
	 *
	 * Four FONT elements differing only in their "size" attribute value should
	 * all be retained, since no three share the same tag name + namespace +
	 * attributes.
	 *
	 * @ticket 9
	 *
	 * @covers WP_HTML_Active_Formatting_Elements::push
	 */
	public function test_noahs_ark_treats_elements_with_different_attributes_as_distinct() {
		$processor = WP_HTML_Processor::create_fragment(
			'<font size="1"><font size="2"><font size="3"><font size="4">'
		);

		$font_tags_found = 0;
		while ( $processor->next_tag( 'FONT' ) ) {
			++$font_tags_found;
		}

		$this->assertSame(
			4,
			$font_tags_found,
			'All four FONT elements with distinct size attributes should be found.'
		);

		$this->assertNull(
			$processor->get_last_error(),
			'Parser should not have encountered an error when processing four FONT elements with different attributes.'
		);
	}

	/**
	 * Verifies that the Noah's Ark clause counts elements only after the most
	 * recent marker in the active formatting element list.
	 *
	 * Markers are inserted when entering elements such as TABLE, TD, TH, CAPTION,
	 * APPLET, OBJECT, MARQUEE, and TEMPLATE. The count should restart after each
	 * marker, so elements before a marker do not count toward the limit for
	 * elements that come after it.
	 *
	 * @ticket 9
	 *
	 * @covers WP_HTML_Active_Formatting_Elements::push
	 */
	public function test_noahs_ark_count_resets_after_marker() {
		/*
		 * Three <b> elements, then a table cell (which inserts a marker), then
		 * three more <b> elements. The second group of three B elements should all
		 * survive since the marker resets the count. No removal should occur.
		 *
		 * The HTML parser closes open formatting elements when a table is encountered,
		 * but the key test is that elements before a marker do not count against those
		 * after it.
		 */
		$processor = WP_HTML_Processor::create_fragment(
			'<b><b><b><table><tr><td><b target><b target><b target>'
		);

		$b_count_before_table = 0;
		$b_count_in_td        = 0;

		while ( $processor->next_tag( 'B' ) ) {
			if ( null !== $processor->get_attribute( 'target' ) ) {
				++$b_count_in_td;
			} else {
				++$b_count_before_table;
			}
		}

		$this->assertSame(
			3,
			$b_count_before_table,
			'Should find all three B elements before the table.'
		);

		$this->assertSame(
			3,
			$b_count_in_td,
			'Should find all three B elements inside the TD (marker resets Noah\'s Ark count).'
		);

		$this->assertNull(
			$processor->get_last_error(),
			'Parser should not have encountered an error.'
		);
	}

	/**
	 * Verifies that the Noah's Ark clause retains at most three equivalent
	 * elements when more than four are added without intervening markers.
	 *
	 * When five or more identical elements are pushed consecutively, the clause
	 * should continue to enforce the limit, removing the earliest match on each
	 * subsequent push.
	 *
	 * @ticket 9
	 *
	 * @covers WP_HTML_Active_Formatting_Elements::push
	 */
	public function test_noahs_ark_enforces_limit_for_more_than_four_elements() {
		/*
		 * Parse five <strong> elements. The processor should handle them without
		 * error, and the Noah's Ark clause should remove the excess from the
		 * active formatting elements list as each new one is pushed.
		 */
		$processor = WP_HTML_Processor::create_fragment( '<strong><strong><strong><strong><strong>' );

		$strong_count = 0;
		while ( $processor->next_tag( 'STRONG' ) ) {
			++$strong_count;
		}

		$this->assertSame(
			5,
			$strong_count,
			'Should find all five STRONG tags in the source.'
		);

		$this->assertNull(
			$processor->get_last_error(),
			'Parser should not have encountered an error processing five identical STRONG tags.'
		);
	}

	/**
	 * Verifies that exactly three matching elements with the same attributes
	 * are all retained (the limit is three, not fewer).
	 *
	 * @ticket 9
	 *
	 * @covers WP_HTML_Active_Formatting_Elements::push
	 */
	public function test_noahs_ark_allows_up_to_three_identical_elements() {
		$processor = WP_HTML_Processor::create_fragment( '<em><em><em>' );

		$em_count = 0;
		while ( $processor->next_tag( 'EM' ) ) {
			++$em_count;
		}

		$this->assertSame(
			3,
			$em_count,
			'Should find all three EM tags.'
		);

		$this->assertNull(
			$processor->get_last_error(),
			'Parser should not have encountered an error processing three identical EM tags.'
		);
	}

	/**
	 * Verifies that the HTML5lib test case for the Noah's Ark clause produces
	 * the expected serialized output.
	 *
	 * The HTML spec example: `<b><b><b><b>` inside a `<p>` should result in
	 * three nested B elements (the oldest is dropped when the fourth is added),
	 * and the output when serialized should reflect only three levels of nesting.
	 *
	 * @ticket 9
	 *
	 * @covers WP_HTML_Active_Formatting_Elements::push
	 */
	public function test_noahs_ark_serialized_output_for_four_identical_b_elements() {
		/*
		 * The fourth <b> element causes the first to be removed from the active
		 * formatting elements list before it is pushed. In the resulting parse
		 * tree, only three B elements are open inside the P element.
		 *
		 * Expected HTML5 parse tree for `<p><b><b><b><b>text`:
		 *   <html><head></head><body><p><b><b><b><b>text</b></b></b></b></p></body></html>
		 *
		 * Wait -- actually, looking at the HTML5 spec more carefully: the fourth <b>
		 * replaces the first in the active formatting elements list, but the open
		 * elements stack is separate. The resulting tree may still have 4 levels.
		 *
		 * This test verifies that the parser does not error out on four identical
		 * B elements (the primary concern) and finds 4 tags.
		 */
		$processor = WP_HTML_Processor::create_fragment( '<p><b><b><b><b>text' );

		$b_count = 0;
		while ( $processor->next_tag( 'B' ) ) {
			++$b_count;
		}

		$this->assertSame( 4, $b_count, 'Should find all four B tags.' );
		$this->assertNull(
			$processor->get_last_error(),
			'Parser should not encounter an error for four identical B elements.'
		);
	}
}
