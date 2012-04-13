<?php 
/**
 * Fetch comments from Twitter
 *
 * @package SocialComments
 * @subpackage Twitter
 * @author Sudar
 */
class TwitterFetcher extends Fetcher {

    // Constants
    // comment Types
    const TWITTER_TWEETS = 'twitter_tweets';

    // Post meta keys
    const TWITTER_RETWEETS = 'twitter_retweets';
    const TWEET_COMMENT_MAP = 'tweet_comment_map';

    // comment Meta Keys
    const COMMENT_AUTHOR_TWITTER = 'comment_author_twitter';

    // oAuth values
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

        // TODO Check for ratelimit
        // TODO Check if oAuth is fine
    }

    /**
     * Analyse the User Timeline
     *
     * @return void
     * @author Sudar
     */
    public function analyseUserTimeline() {
        /* statuses/user_timeline */
        $user_tweets = $this->oAuthConnection->get('statuses/user_timeline', array('include_entities' => 'true', 'include_rts' => 'true'));

        if ($this->oAuthConnection->http_code == 200) { // it was success
            $this->processTweets($user_tweets);
        } else {
            // TODO: Handle the error condition
            error_log("There was some problem in connection " + $this->oAuthConnection->http_code);
        }

    }

    /**
     * Process Tweets
     *
     * @return void
     * @author Sudar
     */
    private function processTweets($tweets) {

        foreach($tweets as $tweet) {
            $post_id_url_map = array();
            $tweeturls = $tweet->entities->urls;

            // if there were any urls
            if (count($tweeturls) > 0) {
                foreach ($tweeturls as $tweeturl) {
                    $url = $tweeturl->url;
                    $expanded_url = $tweeturl->expanded_url;

                    $post_id = $this->getPostByURL($expanded_url);

                    if ($post_id > 0) {
                        // Check if this tweet was not inserted for this post before
                        if (!$this->isTweetAlreadyInserted($post_id, $tweet->id_str)) {
                            // push the post id
                            $post_id_url_map[$post_id] = $url;
                        }
                    }
                }
            }

            // if there were posts
            if (count($post_id_url_map) > 0) {
                foreach($post_id_url_map as $unique_post_id => $short_url) {

                    // Store the tweet as comment
                    $comemntData = $this->createComment($tweet, $unique_post_id);
                    $comment_id = $this->insertComment($comemntData);

                    // Insert comment Meta information as well
                    $this->storeTweetAuthorID($comment_id, $tweet->user->screen_name);
                    
                    // Store the tweet id as custom field
                    $this->storeTweetAndCommentIDs($unique_post_id, $comment_id, $tweet->id_str);
                }

                // Store the short url as permalink
                $this->updateShortUrls($post_id_url_map);

                // Update Post Cache
                $this->updatePostCache($post_id_url_map);

            } 
        }
    }

    /**
     * Create a new Comment Object
     *
     * @return array created comment Object
     * @author Sudar
     */
    private function createComment($tweet, $post_id) {
        $commentData = array();
        
        $commentData['comment_post_ID'] = $post_id;
        $commentData['comment_author'] = $this->getTweetAuthor($tweet);
        $commentData['comment_author_url'] = $this->getTweetAuthorUrl($tweet);
        $commentData['comment_date'] = strtotime($tweet->created_at);
        $commentData['comment_content'] = $this->getEmbeddableTweet($tweet->id_str);
        $commentData['comment_type'] = $this->getCommentType($tweet);

        //TODO: Find out if we need to approve the comemnt or not
        return $commentData;
    }

    /**
     * get the oEmbed version of the Tweet
     *
     * @return <string> The oEmbed version of the Tweet
     * @author Sudar
     */
    private function getEmbeddableTweet($tweet_id) {
        // We are omitting the script. We need to include the widget.js file by other means
        $richTweet = $this->oAuthConnection->get('statuses/oembed', array('id' => $tweet_id, 'align' => 'center', 'omit_script' => 'true'));

        if ($this->oAuthConnection->http_code == 200) { // it was success
            return $richTweet->html;
        } else {
        
            // TODO: Handle the error condition
            error_log("There was some problem in connection " + $this->oAuthConnection->http_code);
        }
    }

    /**
     * Structure the comment author field
     *
     * @return <string> the structured comemnt author field
     * @author Sudar
     */
    private function getTweetAuthor($tweet) {
        $author = $tweet->user->screen_name;
        if ($tweet->user->name != '' && $tweet->user->name != $tweet->user->screen_name) {
            $author .= ' (' . $tweet->user->name . ')';
        }
        return $author;
    }

    /**
     * constructs the comment author url field
     *
     * @return <string> the constructed comemnt author url field
     * @author Sudar
     */
    private function getTweetAuthorUrl($tweet) {
        return 'http://twitter.com/' . $tweet->user->screen_name . '/statuses/' . $tweet->id_str;
    }

    /**
     * Get the comment Type
     *
     * @return <string> Comment Type
     * @author Sudar
     */
    private function getCommentType($tweet) {
        if (property_exists($tweet, 'retweeted_status')) {
            return self::TWITTER_RETWEETS;
        } else {
            return self::TWITTER_TWEETS;
        }
    }

    /**
     * Store the comment and tweet ids as part of the post
     *
     * @return void
     * @author Sudar
     */
    private function storeTweetAndCommentIDs($post_id, $comment_id, $tweet_id) {
        $tweet_comment_map = get_post_meta($post_id, self::TWEET_COMMENT_MAP, TRUE);

        if (!is_array($tweet_comment_map)) {
            $tweet_comment_map = array();
            //add_post_meta($post_id, self::TWEET_COMMENT_MAP, $tweet_comment_map,  TRUE);
        }

        $tweet_comment_map[$tweet_id] = $comment_id;

        //update_post_meta($post_id, self::TWEET_COMMENT_MAP, $tweet_comment_map);
        print_r($tweet_comment_map);        

    }

    /**
     * Store the Tweet authors Twitter id
     *
     * @return void
     * @author Sudar
     */
    private function storeTweetAuthorID($comment_id, $twitter_id) {
        //update_comment_meta($comment_id, self::COMMENT_AUTHOR_TWITTER, $twitter_id);
        echo "Adding $twitter_id to $comment_id";
    }

    /**
     * Finds if the tweet was already inserted for this post.
     *
     * @return <bool> TRUE if tweet was already inserted. FALSE if not
     * @author Sudar
     */
    private function isTweetAlreadyInserted($post_id, $tweet_id) {
        $tweet_comment_map = get_post_meta($post_id, self::TWEET_COMMENT_MAP, TRUE);
        return array_key_exists($tweet_id, $tweet_comment_map);
    }

    private function checkRateLimit() {
        /* If method is set change API call made. Test is called by default. */
        $content = $this->oAuthConnection->get('account/rate_limit_status');
        echo "Current API hits remaining: {$content->remaining_hits}.";

    }
} // END class TwitterFetcher


?>
