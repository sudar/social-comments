<?php 
/**
 * Fetch comments from Twitter
 *
 * @package SocialComments
 * @subpackage Twitter
 * @author Sudar
 */
class TwitterFetcher {

    private $consumer_key; 
    private $consumer_secret; 
    private $oauth_token; 
    private $oauth_token_secret;
        
    private $oAuthConnection;

    public function __construct($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->oauth_token = $oauth_token;
        $this->oauth_token_secret = $oauth_token_secret;
        
        /* Create a TwitterOauth object with consumer/user tokens. */
        $this->oAuthConnection = new TwitterOAuth($this->consumer_key, $this->consumer_secret, $this->oauth_token, $this->oauth_token_secret);
    }

    public function analyseUserTimeline()
    {
        /* statuses/user_timeline */
        $user_tweets = $this->oAuthConnection->get('statuses/user_timeline', array('include_entities' => 1));
        if ($this->oAuthConnection->http_code == 200) { // it was success
            foreach($user_tweets as $tweet) {
                $tweeturls = $tweet->entities->urls;

                echo '<p>', $tweet->text;

                if (count($tweeturls) > 0) {
                    foreach ($tweeturls as $tweeturl) {
                        $url = $tweeturl->url;
                        $expanded_url = $tweeturl->expanded_url;
                        $post_id = $this->getPostByURL($url, $expanded_url);
                        if ($post_id > 0) {
                            // TODO: Store the tweet as comment
                            // TODO: Store the tweet id as custom field
                            // TODO: Store the short url as permalink
                            echo "<br>Found post: $post_id";
                        }
                    }
                }

                echo  "</p>\n";
            }

        } else {
            // TODO: Handle the error condition
            error_log("There was some problem in connection " + $this->oAuthConnection->http_code);
        }

    }

    private function getPostByURL($url, $expanded_url) {
        // TODO: Really expand the url
        return url_to_postid($epanded_url);
        //echo $url, ' => ', $expanded_url;
    }


    private function checkRateLimit()
    {
        /* If method is set change API call made. Test is called by default. */
        $content = $this->oAuthConnection->get('account/rate_limit_status');
        echo "Current API hits remaining: {$content->remaining_hits}.";

    }
} // END class TwitterFetcher


?>
