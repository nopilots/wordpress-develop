<?php

/**
 * @group comment
 *
 * @covers ::wp_update_comment_count
 * @covers ::wp_defer_comment_counting
 */
class Tests_Comment_wpUpdateCommentCount extends WP_UnitTestCase {

	/**
	 * Tracks calls to pre_wp_update_comment_count_now.
	 *
	 * @var int
	 */
	private $update_comment_count_now_calls = 0;

	public function test_deferred_comment_counting_updates_counts_when_reactivated() {
		$post_id = self::factory()->post->create();

		self::factory()->comment->create_post_comments( $post_id, 1 );
		$this->assertSame( '1', get_comments_number( $post_id ) );

		wp_defer_comment_counting( true );

		try {
			self::factory()->comment->create_post_comments( $post_id, 2 );

			$this->assertSame( '1', get_comments_number( $post_id ) );
		} finally {
			wp_defer_comment_counting( false );
		}

		$this->assertSame( '3', get_comments_number( $post_id ) );
	}

	public function test_deferred_comment_queue_is_cleared_after_processing() {
		$post_id = self::factory()->post->create();

		add_filter( 'pre_wp_update_comment_count_now', array( $this, 'count_wp_update_comment_count_now_calls' ), 10, 3 );
		wp_defer_comment_counting( true );

		try {
			wp_update_comment_count( $post_id );
			wp_update_comment_count( $post_id );
		} finally {
			wp_defer_comment_counting( false );
			remove_filter( 'pre_wp_update_comment_count_now', array( $this, 'count_wp_update_comment_count_now_calls' ), 10 );
		}

		$this->assertSame( 1, $this->update_comment_count_now_calls );

		$this->update_comment_count_now_calls = 0;
		wp_update_comment_count( null, true );

		$this->assertSame( 0, $this->update_comment_count_now_calls );
	}

	public function count_wp_update_comment_count_now_calls( $new, $old, $post_id ) {
		++$this->update_comment_count_now_calls;

		return $new;
	}
}
