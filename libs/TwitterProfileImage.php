<?php
/**
 * Retrieves Twitter Profile Image of a screenname
 *
 * @package SocialComments
 * @subpackage Twitter
 * @author Sudar
 */ 
class TwitterProfileImage {
    // API url got from https://dev.twitter.com/docs/api/1/get/users/profile_image/%3Ascreen_name
    const API_URL = 'https://api.twitter.com/1/users/profile_image';
    
    /**
     * get the profileImage of the passed in screenname
     *
     * @param <string> $screenname - Screen name of the Twitter user
     * @param <string> $size - size of the image (allowed values: bigger, normal, mini, original) default value: bigger
     *                         bigger - 73px by 73px, normal - 48px by 48px, mini - 24px by 24px, 
     *                         original - Original size of the upload (might be bigger)
     *
     * @return <string> Path to the profile Image
     * @author Sudar
     */
    static function getProfileImage($screenname, $size = 'bigger') {
        $url = self::API_URL . '?screen_name=' . $screenname . '&size=' . $size;
            
        $response = wp_remote_request($url); 

        if (is_a($response, 'WP_Error')) {
            return '';
        } else {
            return $response['body'];
        }
    }
} // END static class TwitterProfileImage

echo TwitterProfileImage::getProfileImage('sudarmuthu');
?>
