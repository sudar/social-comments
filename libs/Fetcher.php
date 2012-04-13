<?php
/**
 * Fetcher - Parent class for all Fetchers
 *
 * @package SocialComments
 * @subpackage default
 * @author Sudar
 */ 
class Fetcher {
    
    // custome field name to store short urls
    const SHORTURLS = 'shorturls';

    /**
     * Get Post id of a post based on permalink
     *
     * @return <int>post_id - 0 if not found
     * @author Sudar
     */
    protected function getPostByURL($expanded_url) {
        $expanded_url = Utils::expandUrl($expanded_url);
        // retrieve the post id based on the permalink
        return url_to_postid($expanded_url);
    }

    /**
     * Inserts the comment into the table
     */
    protected function insertComment($commentData) {
        print_r($commentData);
        return 0;
        return wp_insert_comment($commentData);
    }

    /**
     * Update the shorturls field for the post
     *
     * @return void
     * @author Sudar
     */
    protected function updateShortUrls($post_id_url_map) {

        foreach($post_id_url_map as $post_id => $short_url) {
            $shorturls = get_post_meta($post_id, self::SHORTURLS, TRUE);

            if (!is_array($shorturls)) {
                $shorturls = array();		
                //add_post_meta($post_id, self::SHORTURLS, $shorturls, TRUE);	
            }

            // Insert into post meta if it not preset already
            if (!array_search($short_url, $shorturls)) {
                $shorturls[$short_url] = $short_url;
                //update_post_meta($post_id, self::SHORTURLS, $shorturls);
                print_r($shorturls);
            }
        }
    }

    /**
     * Update Post cache
     *
     * @return void
     * @author Sudar
     */
    protected function updatePostCache($post_id) {
        if (function_exists('wp_cache_post_change')) {
            foreach($post_id_url_map as $post_id => $short_url) {
                wp_cache_post_change($post_id);
            }
		}
    }

}
?>
