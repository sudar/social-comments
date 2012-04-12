<?php
//require_once(dirname(__FILE__) . '/../../../wp-blog-header.php');
require_once(dirname(__FILE__) . '/../../../../wpfiles/wp-blog-header.php');
nocache_headers();
/**
 * @file
 * 
 */

/* Load required lib files. */
session_start();

require_once('libs/twitteroauth/twitteroauth.php');
require_once('config.php');

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/rate_limit_status');
echo "Current API hits remaining: {$content->remaining_hits}.";

/* Get logged in user to help with tests. */
$user = $connection->get('account/verify_credentials');

/* statuses/user_timeline */
$user_tweets = $connection->get('statuses/user_timeline', array('include_entities' => 1));
if ($connection->http_code == 200) { // it was success
    foreach($user_tweets as $tweet) {
        $tweeturls = $tweet->entities->urls;

        echo '<p>', $tweet->text, "</p>\n";

        if (count($tweeturls) > 0) {
            foreach ($tweeturls as $tweeturl) {
                $url = $tweeturl->url;
                $expanded_url = $tweeturl->expanded_url;
                $post_id = getPostByURL($url, $expanded_url);
                if ($post_id > 0) {
                    // TODO: 
                }
            }
        }
    }

} else {
    // TODO: Handle the error condition
}

function getPostByURL($url, $expanded_url) {
    // TODO: Really expand the url
    return url_to_postid($epanded_url);
    //echo $url, ' => ', $expanded_url;
}

//print_r($user_tweets);

///* statuses/mentions */
//twitteroauth_row('statuses/mentions', $connection->get('statuses/mentions'), $connection->http_code);
//
///* statuses/retweeted_by_me */
//twitteroauth_row('statuses/retweeted_by_me', $connection->get('statuses/retweeted_by_me'), $connection->http_code);
//
///* statuses/retweets_of_me */
//twitteroauth_row('statuses/retweets_of_me', $connection->get('statuses/retweets_of_me'), $connection->http_code);
