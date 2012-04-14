<?php 
/**
 * Fetch comments from Twitter
 *
 * @package SocialComments
 * @subpackage Twitter
 * @author Sudar
 */
class TwitterFetcher extends Fetcher {

    // oAuth values
    private $consumer_key; 
    private $consumer_secret; 
    private $oauth_token; 
    private $oauth_token_secret;
        
    private $oAuthConnection;

    /**
     * Constructor. Get's the oAuth values and asigns them
     *
     * @return void
     * @author Sudar
     */
    public function __construct($consumer_key, $consumer_secret, $oauth_token, $oauth_token_secret) {
        $this->consumer_key       = $consumer_key;
        $this->consumer_secret    = $consumer_secret;
        $this->oauth_token        = $oauth_token;
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
        // get last tweet id
        $last_tweet_id = get_option(SocialCommentsConstants::LAST_USERTIMELINE_TWEET, 0);

        // build the options array
        $options = array('include_entities' => 'true', 'include_rts' => 'true', 'count' => 200 );
        if ($last_tweet_id > 0) {
            $options['since_id'] = $last_tweet_id;
        } 

        // Retrieve the tweets
        $user_tweets = $this->oAuthConnection->get('statuses/user_timeline', $options);

        if ($this->oAuthConnection->http_code == 200) { // it was success
            if (count($user_tweets) > 0) {
                $this->processTweets($user_tweets);
                update_option(SocialCommentsConstants::LAST_USERTIMELINE_TWEET, $user_tweets[0]->id_str);
            }
        } else {
            // TODO: Handle the error condition
            error_log("There was some problem in connection " + $this->oAuthConnection->http_code);
        }
    }

    /**
     * Analyse User Mentions
     *
     * @return void
     * @author Sudar
     */
    public function analyseUserMentions() {
        // get last tweet id
        $last_tweet_id = get_option(SocialCommentsConstants::LAST_MENTION_TWEET, 0);

        // build the options array
        $options = array('include_entities' => 'true', 'include_rts' => 'true', 'count' => 200 );
        if ($last_tweet_id > 0) {
            $options['since_id'] = $last_tweet_id;
        } 

        // Retrieve the tweets
        $mention_tweets = $this->oAuthConnection->get('statuses/mentions', $options);

        if ($this->oAuthConnection->http_code == 200) { // it was success
            if (count($mention_tweets) > 0) {
                $this->processTweets($mention_tweets);
                update_option(SocialCommentsConstants::LAST_MENTION_TWEET, $mention_tweets[0]->id_str);
            }
        } else {
            // TODO: Handle the error condition
            error_log("There was some problem in connection " + $this->oAuthConnection->http_code);
        }
    }

    /**
     * Perform a Twitter API Search
     *
     * @return void
     * @author Sudar
     */
    public function analyseSearchResults() {
        global $wpdb;

        // retrieve all posts which have twitter check enabled
        $query = "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta
            WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = %s AND wpostmeta.meta_value = %s AND wposts.post_status = 'publish'
            ORDER BY wposts.post_date DESC";

        $twitter_refered_posts = $wpdb->get_col($wpdb->prepare($query, SocialCommentsConstants::PM_REFERED_BY_TWITTER, '1'));

        // for each post, repopulate the tweets
        if ($twitter_refered_posts) {
            foreach($posts_with_tweets as $post_id) {
                // TODO: get shorturls for each post
                $permalink = get_permalink($post_id);
                
                // perform the search
                $response = $this->performSearch($permalink);
                $results = $response->results;

                $this->processTweets($results);
                //TODO: Update last seen tweet

                // revert the referal status
                update_post_meta($post_id, SocialCommentsConstants::PM_REFERED_BY_TWITTER, '0');
            }
        }
    }

    /**
     * Re Fetch Tweet text
     *
     * @return void
     * @author Sudar
     */
    public function repopulateAllTweetText() {
        global $wpdb;

        // find all posts that have tweets
        $query = "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta
            WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = %s AND wposts.post_status = 'publish'
            ORDER BY wposts.post_date DESC";

        $posts_with_tweets = $wpdb->get_col($wpdb->prepare($query, SocialCommentsConstants::TWEET_COMMENT_MAP));

        // for each post, repopulate the tweets
        if ($posts_with_tweets) {
            foreach($posts_with_tweets as $post_id) {
                echo "Currently processing post: $post_id";
                $this->repopulateTweetText($post_id);
            }
        }
    }

    /**
     * Re fetch Tweet Text for a particular post
     *
     * @return void
     * @author Sudar
     */
    public function repopulateTweetText($post_id) {
        // Get the tweet comment map
        $tweet_comment_map = get_post_meta($post_id, SocialCommentsConstants::TWEET_COMMENT_MAP, TRUE);

        if (is_array($tweet_comment_map)) {
            foreach($tweet_comment_map as $tweet_id => $comment_id) {
                // Fetch text for each comment
                $commentData = get_comment($comment_id, ARRAY_A);
                $commentData['comment_content'] = $this->getEmbeddableTweet($tweet_id);

                // update the comment
                $this->updateComment($commentData);
            } 
        }
    }
    
    /**
     * Using the undocumented activity/aboutme
     *
     * @return void
     * @author Sudar
     */
    public function analyseAboutMe() {
        $aboutme = $this->oAuthConnection->get('activity/about_me');
        print_r($aboutme);
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
                    $comment_data = $this->createComment($tweet, $unique_post_id);
                    $comment_id   = $this->insertComment($comment_data);

                    // Insert comment Meta information as well
                    $this->storeTweetAuthor($comment_id, $this->getTweetAuthorScreenName($tweet));
                    
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
        
        $commentData['comment_post_ID']    = $post_id;
        $commentData['comment_author']     = $this->getTweetAuthor($tweet);
        $commentData['comment_author_url'] = $this->getTweetAuthorUrl($tweet);
        $commentData['comment_date_gmt']   = date('Y-m-d H:i:s', strtotime($tweet->created_at));
        $commentData['comment_date']       = get_date_from_gmt($commentData['comment_date_gmt']);
        $commentData['comment_content']    = $this->getEmbeddableTweet($tweet->id_str);
        $commentData['comment_type']       = $this->getCommentType($tweet);

        //TODO: Find out if we need to approve the comemnt or not
        return $commentData;
    }

    /**
     * get the oEmbed version of the Tweet
     *
     * @return <string> The oEmbed version of the Tweet
     * @author Sudar
     */
    private function getEmbeddableTweet($tweet_id, $align = "left", $hide_thread = 'false') {
        // We are omitting the script. We need to include the widget.js file by other means
        $richTweet = $this->oAuthConnection->get('statuses/oembed', array('id' => $tweet_id, 'align' => $align, 'hide_thread' => $hide_thread, 'omit_script' => 'true'));

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
        $screen_name = $this->getTweetAuthorScreenName($tweet);
        $name = $this->getTweetAuthorName($tweet);

        $author = $screen_name;

        if ($name != '' && $name != $screen_name) {
            $author .= ' (' . $name . ')';
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
        return 'http://twitter.com/' . $this->getTweetAuthorScreenName($tweet) . '/statuses/' . $tweet->id_str;
    }

    /**
     * Get Tweet Author Screenname from tweet
     *
     * @return <string> Tweet author screenname
     * @author Sudar
     */
    private function getTweetAuthorScreenName($tweet) {
        if (property_exists($tweet, 'user')) {
            // usertimeline or mentions
            return $tweet->user->screen_name;
        } else {
            // search results
            return $tweet->from_user;
        }
    }

    /**
     * Get Tweet Author Name from tweet
     *
     * @return <string> Tweet author screenname
     * @author Sudar
     */
    private function getTweetAuthorName($tweet) {
        if (property_exists($tweet, 'user')) {
            // usertimeline or mentions
            return $tweet->user->name;
        } else {
            // search results
            return $tweet->from_user_name;
        }

    }

    /**
     * Get the comment Type
     *
     * @return <string> Comment Type
     * @author Sudar
     */
    private function getCommentType($tweet) {
        if (property_exists($tweet, 'retweeted_status')) {
            return SocialCommentsConstants::TWITTER_RETWEETS;
        } else {
            return SocialCommentsConstants::TWITTER_TWEETS;
        }
    }

    /**
     * Store the comment and tweet ids as part of the post
     *
     * @return void
     * @author Sudar
     */
    private function storeTweetAndCommentIDs($post_id, $comment_id, $tweet_id) {
        $tweet_comment_map = get_post_meta($post_id, SocialCommentsConstants::TWEET_COMMENT_MAP, TRUE);

        if (!is_array($tweet_comment_map)) {
            $tweet_comment_map = array();
        }

        $tweet_comment_map[$tweet_id] = $comment_id;

        update_post_meta($post_id, SocialCommentsConstants::TWEET_COMMENT_MAP, $tweet_comment_map);
        print_r($tweet_comment_map);        

    }

    /**
     * Store the Tweet authors Twitter id
     *
     * @return void
     * @author Sudar
     */
    private function storeTweetAuthor($comment_id, $twitter_id) {
        update_comment_meta($comment_id, SocialCommentsConstants::COMMENT_AUTHOR_TWITTER, $twitter_id);

        // TODO: Find the proper size that's needed
        $profile_image = TwitterProfileImage::getProfileImage($twitter_id);
        update_comment_meta($comment_id, SocialCommentsConstants::COMMENT_AUTHOR_TWITTER_PROFILE, $profile_image);
    }

    /**
     * Finds if the tweet was already inserted for this post.
     *
     * @return <bool> TRUE if tweet was already inserted. FALSE if not
     * @author Sudar
     */
    private function isTweetAlreadyInserted($post_id, $tweet_id) {
        $tweet_comment_map = get_post_meta($post_id, SocialCommentsConstants::TWEET_COMMENT_MAP, TRUE);
        if (is_array($tweet_comment_map)) {
            return array_key_exists($tweet_id, $tweet_comment_map);
        } else {
            return FALSE;
        }
    }

    /**
     * Perform Search for a query and return resutls
     *
     * @return <object> Search Results
     * @author Sudar
     */
    private function performSearch($query) {
        // TODO: Perform somekind of limit
        $params = array('include_entities' => 'true', 'result_type' => 'recent', 'rpp' => 100);
        $params['q'] = $query;

        $response = $this->oAuthConnection->http(SocialCommentsConstants::TWITTER_SEARCH_API_URL . '?' . OAuthUtil::build_http_query($params), 'GET');
        return json_decode($response);
    }

    private function checkRateLimit() {
        /* If method is set change API call made. Test is called by default. */
        $content = $this->oAuthConnection->get('account/rate_limit_status');
        echo "Current API hits remaining: {$content->remaining_hits}.";
    }
} // END class TwitterFetcher
?>
