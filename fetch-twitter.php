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

require_once('libs/Utils.php');
require_once('libs/TwitterProfileImage.php');
require_once('libs/twitteroauth/twitteroauth.php');
require_once('libs/Fetcher.php');
require_once('libs/TwitterFetcher.php');
require_once('config.php');

/* Create a TwitterOauth object with consumer/user tokens. */
$twitterFetcher = new TwitterFetcher(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

$twitterFetcher->analyseUserTimeline();
//$twitterFetcher->analyseUserMentions();
//$twitterFetcher->repopulateAllTweetText();

//$twittercomments 	= get_post_meta(1811, "twittercomments",true);
//print_r($twittercomments);
//
//echo url_to_postid('http://sudarmuthu.com/blog/bulk-delete-plugin-now-supports-deleting-by-permalink');
//print_r($user_tweets);

///* statuses/mentions */
//twitteroauth_row('statuses/mentions', $connection->get('statuses/mentions'), $connection->http_code);
//
///* statuses/retweeted_by_me */
//twitteroauth_row('statuses/retweeted_by_me', $connection->get('statuses/retweeted_by_me'), $connection->http_code);
//
///* statuses/retweets_of_me */
//twitteroauth_row('statuses/retweets_of_me', $connection->get('statuses/retweets_of_me'), $connection->http_code);
