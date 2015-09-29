<?php

class FlipboardTest extends WP_UnitTestCase {

    private $class_instance;

    protected $attachment = array(
            'post_content'   => 'attachment content',
            'post_name'      => 'attachment-name',
            'post_excerpt'   => 'attachment excerpt',
            'post_title'     => 'Attachment Name',
            'post_status'    => 'publish',
            'post_type'      => 'attachment',
            'post_mime_type' => 'image/jpeg',
        );

    public function setUp() {
        parent::setUp();

        $this->class_instance = Flipboard_RSS_Feed::get_instance();
    }

	function testEnabled() {
        $this->assertFalse($this->class_instance->get_is_enabled());
	}

    function testEnabledFilter() {
        add_filter('flipboard_rss_feed_enabled', '__return_true');
        $this->assertTrue($this->class_instance->get_is_enabled());
    }

    function test_flipboard_rss_feed_per_rss(){
        add_filter('flipboard_rss_feed_per_rss', create_function('','return 150;'));
        $this->assertEquals(150, $this->class_instance->option_posts_per_rss(123));
        $this->assertNotEquals(1500, $this->class_instance->option_posts_per_rss(123));
    }

    function test_flipboard_rss_feed_url(){

        $this->assertArrayHasKey('utm_source', $this->class_instance->get_url_params());
        $this->assertArrayHasKey('utm_medium', $this->class_instance->get_url_params());
        $this->assertArrayHasKey('utm_campaign', $this->class_instance->get_url_params());

        add_filter('flipboard_rss_feed_url', '__return_empty_array');
        $this->assertEmpty($this->class_instance->get_url_params());

        add_filter('flipboard_rss_feed_url', '__return_empty_string');
        $this->assertEmpty($this->class_instance->get_url_params());



        add_filter('flipboard_rss_feed_url', create_function('','return array("testing" => "123");'));
        $this->assertArrayHasKey('testing', $this->class_instance->get_url_params());
        $this->assertContains('123', $this->class_instance->get_url_params());

    }


     function test_flipboard_caption_with_invalid_input(){

        // if empty parameter
        $this->assertEmpty($this->class_instance->flipboard_figure(''));
        $this->assertEmpty($this->class_instance->flipboard_figure(false));
        //
        //// if attachment does't exist
        $this->assertEmpty($this->class_instance->flipboard_figure( 234 ) );

        //// if attachment does't exist
        $this->assertEmpty($this->class_instance->flipboard_figure( array( 123 ) ) );
    }

    function test_flipboard_caption_with_post(){
        // if attachment existspost_content
        $post_id = $this->factory->post->create( array(
            'post_status' => 'publish'
        ) );
        $this->assertEmpty($this->class_instance->flipboard_figure( $post_id ) );
    }

    function test_flipboard_caption_with_image_content(){
        // if attachment existspost_content
        $post_id = $this->factory->post->create( array(
            'post_status' => 'publish'
        ) );
        $attachment_id = $this->factory->attachment->create_object( 'image.jpg', $post_id, $this->attachment );
        $this->assertEquals( $this->attachment['post_content'], $this->class_instance->flipboard_figure( $attachment_id ) );
    }

    function test_flipboard_caption_with_image_excerpt(){
        $post_id = $this->factory->post->create( array(
            'post_status' => 'publish'
        ) );
        $attachment_args = $this->attachment;
        $attachment_args['post_content'] = '';
        $attachment_id = $this->factory->attachment->create_object( 'image.jpg', $post_id, $attachment_args );
        $this->assertEquals( $this->attachment['post_excerpt'], $this->class_instance->flipboard_figure( $attachment_id ) );
    }

    function test_flipboard_caption_with_image_alt(){
        $post_id = $this->factory->post->create( array(
            'post_status' => 'publish'
        ) );
        $alt_value = 'alt text';
        $attachment_args = $this->attachment;
        $attachment_args['post_content'] = '';
        $attachment_args['post_excerpt'] = '';
        $attachment_id = $this->factory->attachment->create_object( 'image.jpg', $post_id, $attachment_args );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_value );
        $this->assertEquals( $alt_value, $this->class_instance->flipboard_figure( $attachment_id ) );
    }

    function test_flipboard_caption_with_no_image(){
        $post_id = $this->factory->post->create( array(
            'post_status' => 'publish'
        ) );

        $attachment_args = $this->attachment;
        $attachment_args['post_mime_type'] = 'audio/mp3';

        $attachment_id = $this->factory->attachment->create_object( 'test.mp3', $post_id,  $attachment_args );
        $this->assertEmpty($this->class_instance->flipboard_figure( $attachment_id ) );
    }


	function test_flipboard_filter_tags_out(){

		$content = 'testing<a href="#">Testing</a><strong>boom</strong>';
		$content_after = 'testing';

		$this->assertEquals($content, $this->class_instance->cleanup_feed_of_tags($content));
		$this->assertEquals($content_after, $this->class_instance->cleanup_feed_of_tags($content_after));
	}

	function test_flipboard_filter_styles_out(){

		$content = 'testing<style>#css{}</style><script>alert("testing");</script>';
		$content_after = 'testing';

		$this->assertEquals($content_after, $this->class_instance->remove_script_style_tags($content));
		$this->assertEquals($content_after, $this->class_instance->remove_script_style_tags($content_after));
	}


	function test_flipboard_filter_mixed_styles_out(){

		$content = 'testing<a href="#">Testing</a><strong>boom</strong><style>#css{}</style><script>alert("testing");</script>';
		$content_after = 'testing<a href="#">Testing</a><strong>boom</strong>';

		$this->assertEquals($content_after, $this->class_instance->remove_script_style_tags($content));
	}
}

