<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] != "GET") {
    http_response_code(403);
    exit();
}

// clear session
unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);
unset($_SESSION['user']);

require "../access-keys.php";
require "../lib/twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);

// generate temp request token
$request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => OAUTH_CALLBACK));
$_SESSION['oauth_token'] = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

// send user to twitter using token
$url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));

header("Location: " . $url);
?>

