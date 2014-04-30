<?php
/**
 * Flipboard RSS Feed
 *
 * @package   Flipboard_RSS_Feed
 * @author    Jonathan Harris <jonathan_harris@ipcmedia.com>
 * @license   GPL-2.0+
 * @link      http://www.jonathandavidharris.co.uk/
 * @copyright 2014 IPC Media
 */

/**
 *
 *
 * @package Flipboard_RSS_Feed
 * @author  Jonathan Harris <jonathan_harris@ipcmedia.com>
 */
class Flipboard_RSS_Feed {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    const VERSION = '1.0.4';

    /*
     *
     * Unique identifier for your plugin.
     *
     *
     * The variable name is used as the text domain when internationalizing strings
     * of text. Its value should match the Text Domain file header in the main
     * plugin file.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_slug = 'flipboard-rss-feed';

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;


    /**
     * Array of sizes
     *
     * @since    1.0.0
     *
     * @var      array
     */
    protected $image_sizes = array();

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     1.0.0
     */
    private function __construct() {
        // Load plugin text domain
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Activate plugin when new blog is added
        add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );


       // if ( !$this->get_is_enabled() )
        //    return;

        add_filter('option_rss_use_excerpt', array( $this, 'option_rss_use_excerpt' ), 15, 1 );
        add_filter('option_posts_per_rss',   array( $this, 'option_posts_per_rss' ), 15, 1  );

        add_action('template_redirect', array($this, 'template_redirect'));

    }

    /**
     * Return the plugin slug.
     *
     * @since    1.0.0
     *
     * @return    Plugin slug variable.
     */
    public function get_plugin_slug() {
        return $this->plugin_slug;
    }

    /**
     * Load these filters after pre post has run.
     */
    public function template_redirect(){

        $this->set_image_size();
        //  no large enough image sizes, lets quit
        if(count($this->get_image_sizes()) == 0)
            return;

        if(!is_feed()){
            return;
        }

        add_action('rss2_ns',   array( $this, 'mrss_ns' ));
        add_action('rss2_item', array( $this, 'mrss_item'), 10, 0);
        add_filter('the_permalink_rss',      array( $this, 'the_permalink_rss' )      );
    }

    /**
     * Returns whether this plugin should be enabled.
     * This function is filterable - flipboard_rss_feed_enabled
     *
     * @since    1.0.0
     *
     * @return boolean true | false
     */
    public function get_is_enabled(){
        return apply_filters('flipboard_rss_feed_enabled', ((isset( $_GET['mrss'] ) && $_GET['mrss'] == '1' )));
    }

    /**
     * Return the images sizes array.
     *
     * @since    1.0.0
     *
     * @return    image sizes variable.
     */

    public function get_image_sizes(){
        return apply_filters('flipboard_rss_feed_image_sizes', $this->image_sizes );
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Fired when the plugin is activated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Activate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       activated on an individual blog.
     */
    public static function activate( $network_wide ) {

        if ( function_exists( 'is_multisite' ) && is_multisite() ) {

            if ( $network_wide  ) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    self::single_activate();
                }

                restore_current_blog();

            } else {
                self::single_activate();
            }

        } else {
            self::single_activate();
        }

    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @since    1.0.0
     *
     * @param    boolean    $network_wide    True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate( $network_wide ) {

        if ( function_exists( 'is_multisite' ) && is_multisite() ) {

            if ( $network_wide ) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ( $blog_ids as $blog_id ) {

                    switch_to_blog( $blog_id );
                    self::single_deactivate();

                }

                restore_current_blog();

            } else {
                self::single_deactivate();
            }

        } else {
            self::single_deactivate();
        }

    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @since    1.0.0
     *
     * @param    int    $blog_id    ID of the new blog.
     */
    public function activate_new_site( $blog_id ) {

        if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
            return;
        }

        switch_to_blog( $blog_id );
        self::single_activate();
        restore_current_blog();

    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @since    1.0.0
     *
     * @return   array|false    The blog ids, false if no matches.
     */
    private static function get_blog_ids() {

        global $wpdb;

        // get an array of blog ids
        $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

        return $wpdb->get_col( $sql );

    }

    /**
     * Fired for each blog when the plugin is activated.
     *
     * @since    1.0.0
     */
    private static function single_activate() {
        // Do nothing for now
    }

    /**
     * Fired for each blog when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    private static function single_deactivate() {
        // Do nothing for now
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->plugin_slug;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

    }


    /**
     * Set the image sizes. Only get image sizes for image size that have width more than 400px
     *
     * @author   Jonathan Harris
     * @since    1.0.0
     */
    protected  function set_image_size(){
        global $_wp_additional_image_sizes;

        $image_width = apply_filters('flipboard_rss_feed_image_width', 400);

        foreach( get_intermediate_image_sizes() as $s ){
            $size = 0;
            if( in_array( $s, array( 'thumbnail', 'medium', 'large' ) ) ){
                $size = get_option( $s . '_size_w' );
            }else{
                if( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $s ] ) )
                    $size =  $_wp_additional_image_sizes[ $s ]['width'];
            }

            if($size >= $image_width){
                $this->image_sizes[] = $s;
            }
        }


    }


    /**
     * Force show full content in RSS feed.
     *
     * @author   Jonathan Harris
     * @since    1.0.0
     */
    public function option_rss_use_excerpt($value){
        return 0;
    }

    /**
     * Force show 30 items in the feed.
     *
     * @author   Jonathan Harris
     * @since    1.0.0
     */
    public function option_posts_per_rss($value){
        return apply_filters('flipboard_rss_feed_per_rss', 30 );
    }

    /**
     * Get params for utm tracking to url
     *
     * @author   Jonathan Harris
     * @since    1.0.0
     */
    public function get_url_params(){
        $arr_params = array( 'utm_source' => 'flipboard', 'utm_medium' => 'flipboard_rss', 'utm_campaign' => urlencode(strtolower(get_bloginfo('name'))) );
        $arr_params = apply_filters('flipboard_rss_feed_url', $arr_params );
        return $arr_params;
    }

    /**
     * Add utm tracking to url
     *
     * @author   Jonathan Harris
     * @since    1.0.0
     */
    public function the_permalink_rss($url){
        return add_query_arg( $this->get_url_params(), $url );
    }


    protected function flipboard_figure($attachment_id){
        $attachment_data = wp_prepare_attachment_for_js($attachment_id);
        if($attachment_data['description'])  $fig_caption = $attachment_data['description'];
        else if($attachment_data['caption']) $fig_caption = $attachment_data['caption'];
        else if($attachment_data['alt'])     $fig_caption = $attachment_data['alt'];
        else $fig_caption = '';
        return $fig_caption;
    }

    /**
     * Add extra fields to header of RSS to make RSS feed valid
     *
     * @author   Jonathan Harris
     * @since    1.0.0
     */
    public function mrss_ns() {
        echo 'xmlns:media="http://search.yahoo.com/mrss/"
        xmlns:georss="http://www.georss.org/georss"';
    }

    /**
     * Add mrss and georss fields to rss item
     *
     * @author   Jonathan Harris
     * @since    1.0.0
     */
    public function mrss_item(){

        // GEO values are be set by core
        $geo_latitude  = get_post_meta(get_the_ID(),'geo_latitude',true);
        $geo_longitude = get_post_meta(get_the_ID(),'geo_longitude',true);

        // If geo latitude / geo_longitude are set, show georss
        if($geo_latitude && $geo_longitude) {
            echo "<georss:point>$geo_latitude $geo_longitude</georss:point>";
        }

        $post_thumbnail_id = apply_filters('flipboard_post_thumbnail_id', get_post_thumbnail_id());

        $post_thumbnail_alt = trim(strip_tags( $this->flipboard_figure($post_thumbnail_id) ));
        $format = '<media:content type="%1$s" medium="image" width="%2$s" height="%3$s"  url="%4$s"><media:description type="plain">%5$s</media:description></media:content>';
        $image_attributes = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );

        $media = "";
        if(!empty($post_thumbnail_id)){
            $media = '';

            $existing_images = array();
            foreach($this->get_image_sizes() as $size){
                $image_attributes = wp_get_attachment_image_src( $post_thumbnail_id, $size );

                // Don't show the same image more than once
                if(!in_array($image_attributes[0], $existing_images)){
                    $media .=  sprintf($format, get_post_mime_type( $post_thumbnail_id ), $image_attributes[1],$image_attributes[2],$image_attributes[0],$post_thumbnail_alt);
                }
                $existing_images[] = $image_attributes[0];
            }
        }
        $media = apply_filters('flipboard_media_element', $media);

        // If no media is set
        if(empty($media))
            return;
        ?>
        <media:group>
            <?php echo $media; ?>
        </media:group>
    <?php
    }

}
