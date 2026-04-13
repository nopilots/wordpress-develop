<?php

/**
 * @group formatting
 *
 * @covers ::sanitize_html_class
 */
class Tests_Formatting_SanitizeHtmlClass extends WP_UnitTestCase {

	public function test_preserves_non_ascii_letters_numbers_and_marks() {
		$this->assertSame(
			'räksmörgås漢字مرحبا123',
			sanitize_html_class( 'räksmörgås漢字مرحبا123' )
		);
	}

	public function test_removes_disallowed_characters_and_percent_encoding() {
		$this->assertSame(
			'Data123',
			sanitize_html_class( 'D%61ta 1<2>3&' )
		);
	}

	public function test_returns_sanitized_fallback_when_primary_is_empty() {
		$this->assertSame(
			'räksmö',
			sanitize_html_class( '%^*', 'räksmö' )
		);
	}
}
