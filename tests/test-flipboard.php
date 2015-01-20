<?php

class FlipboardTest extends WP_UnitTestCase {

    private $class_instance;

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


	function test_flipboard_filter_tags_out(){

		$content = 'testing<a href="#">Testing</a><strong>boom</strong>';
		$content_after = 'testing';

		$this->assertEquals($content_after, $this->class_instance->cleanup_feed_of_tags($content));
		$this->assertEquals($content_after, $this->class_instance->cleanup_feed_of_tags($content_after));
	}

	function test_flipboard_filter_styles_out(){

		$content = 'testing<style>#css{}</style><script>alert("testing");</script>';
		$content_after = 'testing';

		$this->assertEquals($content_after, $this->class_instance->remove_script_style_tags($content));
		$this->assertEquals($content_after, $this->class_instance->remove_script_style_tags($content_after));
	}

}

