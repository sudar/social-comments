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
if (!class_exists('TwitterProfileImage')) {
    require_once('libs/TwitterProfileImage.php');
}
require_once('libs/twitteroauth/twitteroauth.php');
require_once('libs/Fetcher.php');
require_once('libs/TwitterFetcher.php');
require_once('config.php');

/* Create a TwitterOauth object with consumer/user tokens. */
$twitterFetcher = new TwitterFetcher(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

$twitterFetcher->analyseUserTimeline();
$twitterFetcher->analyseUserMentions();
$twitterFetcher->analyseSearchResults();
//$twitterFetcher->repopulateAllTweetText();
//$twitterFetcher->analyseAboutMe();
