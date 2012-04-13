<?php
/**
 * Common Utill Class
 *
 * @package SocialComments
 * @subpackage Util
 * @author Sudar
 */ 
class Utils {
    /**
    * Expand short urls
    *
    * @param <string> $shortUrl - Short url
    * @return <string> - Longer version of the short url
    * TODO: Make it recursive to avoid double url shortening
    */
    static function expandURL($shortUrl) {
        //Get response headers
        $response = get_headers($shortUrl, 1);
        //Get the location property of the response header. If failure, return original url
        $location = $response["Location"];
        if (isset($location)) {
            if (is_array($location)) {
                return $location[count($location) - 1];
            }
        }
        return $shortUrl;
    }    
}
?>
