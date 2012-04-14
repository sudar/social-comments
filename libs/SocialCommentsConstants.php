<?php
/**
 * Class to store constants
 *
 * @package SocialComments
 * @subpackage default
 * @author Sudar
 */
class SocialCommentsConstants {
	
	//Twitter
	//-------
    // comment Types
    const TWITTER_TWEETS = 'twitter_tweets';

    // Post meta keys
    const TWITTER_RETWEETS = 'twitter_retweets';
    const TWEET_COMMENT_MAP = 'tweet_comment_map';
	const PM_REFERED_BY_TWITTER = 'refered_by_twitter';

    // comment Meta Keys
    const COMMENT_AUTHOR_TWITTER = 'comment_author_twitter';
    const COMMENT_AUTHOR_TWITTER_PROFILE = 'comment_author_twitter_profile';

    // options
    const LAST_USERTIMELINE_TWEET = 'last_usertimeline_tweet';
    const LAST_MENTION_TWEET = 'last_mention_tweet';
    const LAST_SEARCH_TWEET = 'last_search_tweet';

    // custom field name to store short urls
    const SHORTURLS = 'shorturls';

	//hooks
	const SINGLE_PAGE_REFERER_HOOK = 'single_page_referer';

	//urls
	const TWITTER_SEARCH_API_URL = 'http://search.twitter.com/search.json';

} // END class SocialCommentsConstants
?>
