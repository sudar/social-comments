<?php
/**
 * Common Utill Class
 *
 * @package SocialComments
 * @subpackage Util
 * @author Sudar
 * Copied from http://sudarmuthu.com/blog/expand-any-shortened-url-using-php
 */ 
class Utils {
    /**
    * Expand short urls
    *
    * @param <string> $shortUrl - Short url
    * @return <string> - Longer version of the short url
    */
    static function expandURL($shortUrl) {
        //Get response headers
        $response = get_headers($shortUrl, 1);
        //Get the location property of the response header. If failure, return original url
        if (array_key_exists('Location', $response)) {
            $location = $response["Location"];
            if (is_array($location)) {
                // t.co gives Location as an array
                return self::expandURL($location[count($location) - 1]);
            } else {
                return self::expandURL($location);
            }
        }
        return $shortUrl;
    }
}
?>
