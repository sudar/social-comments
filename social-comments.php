<?php
/**
Plugin Name: Social Comments
Plugin URI: http://sudarmuthu.com/wordpress/social-comments
Description: Get all your comments and discussions from social media sites into your blog.
Donate Link: http://sudarmuthu.com/if-you-wanna-thank-me
License: GPL
Author: Sudar
Version: 0.1
Author URI: http://sudarmuthu.com/
Text Domain: social-comments

=== RELEASE NOTES ===
*/

/*  Copyright 2010  Sudar Muthu  (email : sudar@sudarmuthu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Social Comments Plugin Class
 */
class SocialComments {

    /**
     * Initalize the plugin by registering the hooks
     */
    function __construct() {

        // Load localization domain
        load_plugin_textdomain( 'social-comments', false, dirname(plugin_basename(__FILE__)) . '/languages' );

        // Register hooks
		add_action( 'single_page_referer', array(&$this, 'twitter_referer'), 10, 2 );
		
        //add_action( 'admin_menu', array(&$this, 'register_settings_page') );
        //add_action( 'admin_init', array(&$this, 'add_settings') );

        /* Use the admin_menu action to define the custom boxes */
		//add_action('admin_menu', array(&$this, 'add_custom_box'));

        /* Use the save_post action to do something with the data entered */
		//add_action('save_post', array(&$this, 'save_postdata'));

        // Register filters
        add_filter('the_content', array(&$this, 'check_referer'));

        //$plugin = plugin_basename(__FILE__);
        //add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));
    }

    /**
     * Register the settings page
     */
    function register_settings_page() {
        add_options_page( __('Social Comments', 'social-comments'), __('Social Comments', 'social-comments'), 8, 'social-comments', array(&$this, 'settings_page') );
    }

    /**
     * add options
     */
    function add_settings() {
        // Register options
        register_setting( 'social-comments', 'retweet-style');
    }

    /**
     * Enqueue the Retweet script
     */
    function add_script() {
        // Enqueue the script only if the button type is bit.ly
        $options = get_option('retweet-style');

        if ($options['button-type'] == 'bit.ly') {
            wp_enqueue_script('retweet', get_option('home') . '/?retweetjs');
        }
    }

    /**
     * Adds the custom section in the Post and Page edit screens
     */
    function add_custom_box() {

        add_meta_box( 'retweet_enable_button', __( 'Social Comments Button', 'social-comments' ),
                    array(&$this, 'inner_custom_box'), 'post', 'side' );
        add_meta_box( 'retweet_enable_button', __( 'Social Comments Button', 'social-comments' ),
                    array(&$this, 'inner_custom_box'), 'page', 'side' );
    }

    /**
     * Prints the inner fields for the custom post/page section
     */
    function inner_custom_box() {
        global $post;
        $post_id = $post->ID;
        
        $option_value = '';
        
        if ($post_id > 0) {
            $enable_retweet = get_post_meta($post_id, 'enable_retweet_button', true);
            if ($enable_retweet != '') {
                $option_value = $enable_retweet;
            }

            $custom_retweet_text = get_post_meta($post_id, 'custom_retweet_text', true);

        }
        // Use nonce for verification
?>
        <input type="hidden" name="retweet_noncename" id="retweet_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) );?>" />
        <p>
        <label><input type="radio" name="retweet_button" value ="1" <?php checked('1', $option_value); ?> /> <?php _e('Enabled', 'social-comments'); ?></label>
        <label><input type="radio" name="retweet_button" value ="0"  <?php checked('0', $option_value); ?> /> <?php _e('Disabled', 'social-comments'); ?></label>
        </p>
        <p>
            <label><?php _e('Custom Retweet Text:', 'social-comments'); ?><input type ="text" name="custom_retweet_text" value ="<?php echo $custom_retweet_text;?>" /></label>
        </p>
        <p>
            <?php _e('If left blank, the post title will be used.', 'social-comments'); ?>
        </p>
<?php
    }

    /**
     * When the post is saved, saves our custom data
     * @param string $post_id
     * @return string return post id if nothing is saved
     */
    function save_postdata( $post_id ) {

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times

        if ( !wp_verify_nonce( $_POST['retweet_noncename'], plugin_basename(__FILE__) )) {
            return $post_id;
        }

        if ( 'page' == $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ))
                return $post_id;
        } else {
            if ( !current_user_can( 'edit_post', $post_id ))
                return $post_id;
        }

        // OK, we're authenticated: we need to find and save the data

        if (isset($_POST['retweet_button'])) {
            $choice = $_POST['retweet_button'];
            $choice = ($choice == '1')? '1' : '0';
            update_post_meta($post_id, 'enable_retweet_button', $choice);
        }

        if (isset($_POST['custom_retweet_text'])) {
            $custom_retweet_text = esc_attr($_POST['custom_retweet_text']);
            update_post_meta($post_id, 'custom_retweet_text', $custom_retweet_text);
        }
    }

    /**
     * hook to add action links
     * @param <type> $links
     * @return <type>
     */
    function add_action_links( $links ) {
        // Add a link to this plugin's settings page
        $settings_link = '<a href="options-general.php?page=social-comments">' . __("Settings", 'social-comments') . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Adds Footer links. Based on http://striderweb.com/nerdaphernalia/2008/06/give-your-wordpress-plugin-credit/
     */
    function add_footer_links() {
        $plugin_data = get_plugin_data( __FILE__ );
        printf('%1$s ' . __("plugin", 'social-comments') .' | ' . __("Version", 'social-comments') . ' %2$s | '. __('by', 'social-comments') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
    }

    /**
     * Dipslay the Settings page
     */
    function settings_page() {
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'Social Comments Settings', 'social-comments' ); ?></h2>

            <form id="smer_form" method="post" action="options.php">
                <?php settings_fields('social-comments'); ?>
                <?php $options = get_option('retweet-style'); ?>
                <?php $options['username'] = ($options['username'] == "")? "retweetjs" : $options['username'];?>
                <?php $options['align'] = ($options['align'] == "")? "hori":$options['align'];?>
                <?php $options['position'] = ($options['position'] == "")? "after":$options['position'];?>
                <?php $options['text'] = ($options['text'] == "")? "Retweet":$options['text'];?>
                
                <?php $options['button-type'] = ($options['button-type'] == "")? "twitter":$options['button-type'];?>
                <?php $options['t-count'] = ($options['t-count'] == "")? "horizontal":$options['t-count'];?>

                <h3><?php _e('General Settings', 'social-comments'); ?></h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Display', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="checkbox" name="retweet-style[display-page]" value="1" <?php checked("1", $options['display-page']); ?> /> <?php _e("Display the button on pages", 'social-comments');?></label></p>
                            <p><label><input type="checkbox" name="retweet-style[display-archive]" value="1" <?php checked("1", $options['display-archive']); ?> /> <?php _e("Display the button on archive pages", 'social-comments');?></label></p>
                            <p><label><input type="checkbox" name="retweet-style[display-home]" value="1" <?php checked("1", $options['display-home']); ?> /> <?php _e("Display the button in home page", 'social-comments');?></label></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Position', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="retweet-style[position]" value="before" <?php checked("before", $options['position']); ?> /> <?php _e("Before the content of your post", 'social-comments');?></label></p>
                            <p><label><input type="radio" name="retweet-style[position]" value="after" <?php checked("after", $options['position']); ?> /> <?php _e("After the content of your post", 'social-comments');?></label></p>
                            <p><label><input type="radio" name="retweet-style[position]" value="both" <?php checked("both", $options['position']); ?> /> <?php _e("Before AND After the content of your post", 'social-comments');?></label></p>
                            <p><label><input type="radio" name="retweet-style[position]" value="manual" <?php checked("manual", $options['position']); ?> /> <?php _e("Manually call the retweet button", 'social-comments');?></label></p>
                            <p><?php _e("You can manually call the <code>easy_retweet_button</code> function. E.g. <code>if (function_exists('easy_retweet_button')) echo easy_retweet_button();.", 'social-comments'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Button type', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="retweet-style[button-type]" value="bit.ly" <?php checked("bit.ly", $options['button-type']); ?> /> <?php _e("Bit.ly hit count button", 'social-comments');?></label></p>
                            <p><label><input type="radio" name="retweet-style[button-type]" value="twitter" <?php checked("twitter", $options['button-type']); ?> /> <?php _e("Official Twitter button", 'social-comments');?></label></p>
                        </td>
                    </tr>

                </table>

                <div id="bitly-button">
                    <h3><?php _e('Bit.ly Button', 'social-comments'); ?></h3>
                    
                    <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Bit.ly Username', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[username]" value="<?php echo $options['username']; ?>" /></label></p>
                            <p><?php _e("A default account will be used if left blank.", 'social-comments');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Bit.ly API Key', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[apikey]" value="<?php echo $options['apikey']; ?>" /></label></p>
                            <p><?php _e("You can get it from <a href = 'http://bit.ly/account/' target = '_blank'>http://bit.ly/account/</a>.", 'social-comments');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Bit.ly Domain', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[domain]" value="<?php echo $options['domain']; ?>" /></label></p>
                            <p><?php _e("Either bit.ly (default), your custom bit.ly Pro domain, or j.mp.", 'social-comments');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Button style', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="retweet-style[align]" value="vert" <?php checked("vert", $options['align']); ?> /> <img src ="<?php echo plugin_dir_url(__FILE__); ?>images/vert.png" /> (<?php _e("Vertical button", 'social-comments');?>)</label></p>
                            <p><label><input type="radio" name="retweet-style[align]" value="hori" <?php checked("hori", $options['align']); ?> /> <img src ="<?php echo plugin_dir_url(__FILE__); ?>images/hori.png" /> (<?php _e("Horizontal button", 'social-comments');?>)</label></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Text', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[text]" value="<?php echo $options['text']; ?>" /></label></p>
                            <p><?php _e("The text that you enter here will be displayed in the button.", 'social-comments');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Message Prefix', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[prefix]" value="<?php echo $options['prefix']; ?>" /></label></p>
                            <p><?php _e("The text that you want to be added in front of each twitter message. eg: <code>RT: @sudarmuthu</code>", 'social-comments');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Link Attributes', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[linkattr]" value="<?php echo $options['linkattr']; ?>" /></label></p>
                            <p><?php _e("eg: <code>rel='nofollow'</code> or <code>target = '_blank'</code>", 'social-comments');?></p>
                        </td>
                    </tr>

                </table>
                </div>

                <div id="twitter-button">
                    <h3><?php _e('Twitter Button', 'social-comments'); ?></h3>

                    <table class="form-table">

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Button Style', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="retweet-style[t-count]" value="vertical" <?php checked("vertical", $options['t-count']); ?> /> <img src ="<?php echo plugin_dir_url(__FILE__); ?>images/t-vert.png" /> (<?php _e("Vertical count", 'social-comments');?>)</label></p>
                            <p><label><input type="radio" name="retweet-style[t-count]" value="horizontal" <?php checked("horizontal", $options['t-count']); ?> /> <img src ="<?php echo plugin_dir_url(__FILE__); ?>images/t-hori.png" /> (<?php _e("Horizontal count", 'social-comments');?>)</label></p>
                            <p><label><input type="radio" name="retweet-style[t-count]" value="none" <?php checked("none", $options['t-count']); ?> /> <img src ="<?php echo plugin_dir_url(__FILE__); ?>images/t-no.png" /> (<?php _e("No count", 'social-comments');?>)</label></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Recommended Twitter account', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[account1]" value="<?php echo $options['account1']; ?>" /></label></p>
                            <p><?php _e("Twitter account for users to follow after they share content from your website. This account could include your own, or that of a contributor or a partner.", 'social-comments');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Additional styles', 'social-comments' ); ?></th>
                        <td>
                            <p><label><input type="text" name="retweet-style[t-style]" value="<?php echo $options['t-style']; ?>" /></label></p>
                            <p><?php _e("eg: <code>float: left; margin-right: 10px;</code>.", 'social-comments');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Language', 'social-comments' ); ?></th>
                        <td>
                            <select name="retweet-style[t-language]">
                                <option value="en" <?php selected("en", $options['t-language']); ?>><?php _e('English', 'social-comments' ); ?></option>
                                <option value="fr" <?php selected("fr", $options['t-language']); ?>><?php _e('French', 'social-comments' ); ?></option>
                                <option value="de" <?php selected("de", $options['t-language']); ?>><?php _e('German', 'social-comments' ); ?></option>
                                <option value="es" <?php selected("es", $options['t-language']); ?>><?php _e('Spanish', 'social-comments' ); ?></option>
                                <option value="ja" <?php selected("ja", $options['t-language']); ?>><?php _e('Japanese', 'social-comments' ); ?></option>
                            </select>
                            <p><?php _e("This is the language that the button will render in on your website.", 'social-comments');?></p>
                        </td>
                    </tr>

                </table>
                </div>

                <p class="submit">
                    <input type="submit" name="social-comments-submit" class="button-primary" value="<?php _e('Save Changes', 'social-comments'); ?>" />
                </p>
            </form>

            <h3><?php _e('Support', 'social-comments'); ?></h3>
            <p><?php _e('If you have any questions/comments/feedback about the Plugin then post a comment in the <a target="_blank" href = "http://sudarmuthu.com/wordpress/social-comments">Plugins homepage</a>.','social-comments'); ?></p>
            <p><?php _e('If you like the Plugin, then consider doing one of the following.', 'social-comments'); ?></p>
            <ul style="list-style:disc inside">
                <li><?php _e('Write a blog post about the Plugin.', 'social-comments'); ?></li>
                <li><a href="http://twitter.com/share" class="twitter-share-button" data-url="http://sudarmuthu.com/wordpress/social-comments" data-text="Social Comments WordPress Plugin" data-count="none" data-via="sudarmuthu">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script><?php _e(' about it.', 'social-comments'); ?></li>
                <li><?php _e('Give a <a href = "http://wordpress.org/extend/plugins/social-comments/" target="_blank">good rating</a>.', 'social-comments'); ?></li>
                <li><?php _e('Say <a href = "http://sudarmuthu.com/if-you-wanna-thank-me" target="_blank">thank you</a>.', 'social-comments'); ?></li>
            </ul>
        </div>
<?php
        // Display credits in Footer
        add_action( 'in_admin_footer', array(&$this, 'add_footer_links'));
    }

	/**
	 * Check the referer
	 *
	 * @return void
	 * @author Sudar
	 */
	function check_referer($content) {
		global $post;

		if (is_singular()) {
			// it is a single post or page. Now check for referer
			$referer_domain = $this->get_ref_domain(trim($_SERVER["HTTP_REFERER"]));
			$post_id = $post->ID;

			// do the action. We can hook into it by adding add_action();
			do_action('single_page_referer', $post_id, $referer_domain);
		}
		return $content;
	}

	/**
	 * Twitter Referer
	 *
	 * @return void
	 * @author Sudar
	 */
	public function twitter_referer($post_id, $referer) {
		error_log("Referrer: " . $referer);
	}

	/**
	 * Get the referer domain. Copied from http://plugins.svn.wordpress.org/wp-greet-box/trunk/includes/wp-greet-box.class.php
	 *
	 * @return void
	 * @author Sudar
	 */
	private function get_ref_domain($http_referrer, $strip_www=true) {
      // Break out quickly so we don't waste CPU cycles on non referrals
      if (!isset($http_referrer) || ($http_referrer == '')) return false;
    
      $referer_info = parse_url($http_referrer);
      $referer = $referer_info['host'];
    
      if($strip_www && substr($referer, 0, 4) == 'www.') {
        // Remove www. if necessary
        $referer = substr($referer, 4);
      }
    
      return $referer;
    }

    // PHP4 compatibility
    function SocialComments() {
        $this->__construct();
    }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'SocialComments' ); function SocialComments() { global $SocialComments; $SocialComments = new SocialComments(); }

?>
