<?php
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

/* If access tokens are not available redirect to connect page. */
//if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    //header('Location: ./clearsessions.php');
//}
/* Get user access tokens out of the session. */
//$access_token = $_SESSION['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/rate_limit_status');
echo "Current API hits remaining: {$content->remaining_hits}.";

/* Get logged in user to help with tests. */
$user = $connection->get('account/verify_credentials');

function twitteroauth_row($method, $response, $http_code, $parameters = '') {
  echo '<tr>';
  echo "<td><b>{$method}</b></td>";
  switch ($http_code) {
    case '200':
    case '304':
      $color = 'green';
      break;
    case '400':
    case '401':
    case '403':
    case '404':
    case '406':
      $color = 'red';
      break;
    case '500':
    case '502':
    case '503':
      $color = 'orange';
      break;
    default:
      $color = 'grey';
  }
  echo "<td style='background: {$color};'>{$http_code}</td>";
  if (!is_string($response)) {
    $response = print_r($response, TRUE);
  }
  if (!is_string($parameters)) {
    $parameters = print_r($parameters, TRUE);
  }
  echo '<td>', strlen($response), '</td>';
  echo '<td>', $parameters, '</td>';
  echo '</tr><tr>';
  echo '<td colspan="4">', $response, '...</td>';
  echo '</tr>';

}

function twitteroauth_header($header) {
  echo '<tr><th colspan="4" style="background: grey;">', $header, '</th></tr>';
}

/* Start table. */
echo '<br><br>';
echo '<table border="1" cellpadding="2" cellspacing="0">';
echo '<tr>';
echo '<th>API Method</th>';
echo '<th>HTTP Code</th>';
echo '<th>Response Length</th>';
echo '<th>Parameters</th>';
echo '</tr><tr>';
echo '<th colspan="4">Response Snippet</th>';
echo '</tr>';

/**
 * Help Methods.
 */
twitteroauth_header('Help Methods');

/* help/test */
twitteroauth_row('help/test', $connection->get('help/test'), $connection->http_code);


/**
 * Timeline Methods.
 */
twitteroauth_header('Timeline Methods');

/* statuses/user_timeline */
twitteroauth_row('statuses/user_timeline', $connection->get('statuses/user_timeline'), $connection->http_code);

/* statuses/mentions */
twitteroauth_row('statuses/mentions', $connection->get('statuses/mentions'), $connection->http_code);

/* statuses/retweeted_by_me */
twitteroauth_row('statuses/retweeted_by_me', $connection->get('statuses/retweeted_by_me'), $connection->http_code);

/* statuses/retweets_of_me */
twitteroauth_row('statuses/retweets_of_me', $connection->get('statuses/retweets_of_me'), $connection->http_code);
