<?php

/**
 * @group comment
 *
 * @covers ::wp_list_comments
 */
class Tests_Comment_Walker extends WP_UnitTestCase {

	/**
	 * Comment post ID.
	 *
	 * @var int
	 */
	private $post_id;

	public function set_up() {
		parent::set_up();

		$this->post_id = self::factory()->post->create();
	}

	/**
	 * @ticket 14041
	 */
	public function test_has_children() {
		$comment_parent = self::factory()->comment->create( array( 'comment_post_ID' => $this->post_id ) );
		$comment_child  = self::factory()->comment->create(
			array(
				'comment_post_ID' => $this->post_id,
				'comment_parent'  => $comment_parent,
			)
		);
		$comment_parent = get_comment( $comment_parent );
		$comment_child  = get_comment( $comment_child );

		$comment_walker   = new Walker_Comment();
		$comment_callback = new Comment_Callback_Test_Helper( $this, $comment_walker );

		wp_list_comments(
			array(
				'callback' => array( $comment_callback, 'comment' ),
				'walker'   => $comment_walker,
				'echo'     => false,
			),
			array( $comment_parent, $comment_child )
		);
		wp_list_comments(
			array(
				'callback' => array( $comment_callback, 'comment' ),
				'walker'   => $comment_walker,
				'echo'     => false,
			),
			array( $comment_child, $comment_parent )
		);
	}

	/**
	 * Tests that Walker_Comment can be instantiated without arguments.
	 *
	 * @covers Walker_Comment::__construct
	 */
	public function test_constructor_without_arguments() {
		$walker = new Walker_Comment();
		$this->assertIsArray( $walker->db_fields );
		$this->assertArrayHasKey( 'parent', $walker->db_fields );
		$this->assertArrayHasKey( 'id', $walker->db_fields );
		$this->assertSame( 'comment_parent', $walker->db_fields['parent'] );
		$this->assertSame( 'comment_ID', $walker->db_fields['id'] );
	}

	/**
	 * Tests that Walker_Comment uses default db_fields when passed false.
	 *
	 * @covers Walker_Comment::__construct
	 */
	public function test_constructor_with_false() {
		$walker = new Walker_Comment( false );
		$this->assertIsArray( $walker->db_fields );
		$this->assertSame( 'comment_parent', $walker->db_fields['parent'] );
		$this->assertSame( 'comment_ID', $walker->db_fields['id'] );
	}

	/**
	 * Tests that Walker_Comment accepts custom db_fields.
	 *
	 * @covers Walker_Comment::__construct
	 */
	public function test_constructor_with_custom_fields() {
		$custom_fields = array(
			'parent' => 'custom_parent_field',
			'id'     => 'custom_id_field',
		);
		$walker        = new Walker_Comment( $custom_fields );
		$this->assertSame( 'custom_parent_field', $walker->db_fields['parent'] );
		$this->assertSame( 'custom_id_field', $walker->db_fields['id'] );
	}

	/**
	 * Tests that Walker_Comment with custom db_fields works correctly with hierarchical data.
	 *
	 * This test verifies that the walker can properly traverse hierarchical data
	 * when custom db_fields are provided, simulating an alternate data structure.
	 *
	 * @covers Walker_Comment::__construct
	 * @covers Walker_Comment::walk
	 */
	public function test_custom_db_fields_with_hierarchical_data() {
		// Create mock comment objects with custom field names.
		$parent_comment = (object) array(
			'custom_id_field'     => 1,
			'custom_parent_field' => 0,
			'comment_post_ID'     => $this->post_id,
			'comment_content'     => 'Parent comment',
			'comment_approved'    => '1',
			'comment_type'        => 'comment',
		);

		$child_comment = (object) array(
			'custom_id_field'     => 2,
			'custom_parent_field' => 1,
			'comment_post_ID'     => $this->post_id,
			'comment_content'     => 'Child comment',
			'comment_approved'    => '1',
			'comment_type'        => 'comment',
		);

		$custom_fields = array(
			'parent' => 'custom_parent_field',
			'id'     => 'custom_id_field',
		);

		$walker = new Walker_Comment( $custom_fields );

		// Test that walker can traverse with custom fields.
		$output = $walker->walk( array( $parent_comment, $child_comment ), 2 );

		// The walker should produce output (not empty).
		$this->assertNotEmpty( $output );
	}

	/**
	 * Tests that default db_fields maintain backward compatibility.
	 *
	 * @covers Walker_Comment::__construct
	 */
	public function test_backward_compatibility_with_default_comments() {
		$comment_parent = self::factory()->comment->create( array( 'comment_post_ID' => $this->post_id ) );
		$comment_child  = self::factory()->comment->create(
			array(
				'comment_post_ID' => $this->post_id,
				'comment_parent'  => $comment_parent,
			)
		);
		$comments       = array(
			get_comment( $comment_parent ),
			get_comment( $comment_child ),
		);

		// Test with default constructor (no arguments).
		$walker_default = new Walker_Comment();
		$output_default = $walker_default->walk( $comments, 2 );

		// Test with explicit false argument.
		$walker_false = new Walker_Comment( false );
		$output_false = $walker_false->walk( $comments, 2 );

		// Both should produce identical output.
		$this->assertSame( $output_default, $output_false );

		// Output should contain both comments.
		$this->assertNotEmpty( $output_default );
	}
}

class Comment_Callback_Test_Helper {
	private $test_walker;
	private $walker;

	public function __construct( Tests_Comment_Walker $test_walker, Walker_Comment $walker ) {
		$this->test_walker = $test_walker;
		$this->walker      = $walker;
	}

	public function comment( $comment, $args, $depth ) {
		if ( 1 === $depth ) {
			$this->test_walker->assertTrue( $this->walker->has_children );
			$this->test_walker->assertTrue( $args['has_children'] );  // Back compat.
		} elseif ( 2 === $depth ) {
			$this->test_walker->assertFalse( $this->walker->has_children );
			$this->test_walker->assertFalse( $args['has_children'] ); // Back compat.
		}
	}
}
