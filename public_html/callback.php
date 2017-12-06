<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] != "GET") {
    http_response_code(403);
    exit();
}

require "../access-keys.php";
require "../lib/twitteroauth/autoload.php";
use Abraham\TwitterOAuth\TwitterOAuth;

$request_token = array( "oauth_token" =>        $_SESSION['oauth_token'], 
                        "oauth_token_secret" => $_SESSION['oauth_token_secret']);

unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

$success = null;

// check temp token didn't get mixed during roundtrip to twitter
if (isset($_GET['oauth_token']) && $request_token['oauth_token'] === $_GET['oauth_token']) {
    
    // use temp credentials to get access token
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $request_token['oauth_token'], $request_token['oauth_token_secret']);
    $access_token = $connection->oauth("oauth/access_token", ["oauth_verifier" => $_GET['oauth_verifier']]);

    // use access token
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

    // add my twitter account!
    $create = $connection->post("friendships/create", ["screen_name" => "mcknco"]);

    // get user info
    $user = $connection->get("account/verify_credentials", ["include_email" => "true"]);

    // save user email to list
    if( isset($user->email) ) {
        $email = $user->email; 
        $screen_name = $user->screen_name;
        $path = "../data/twitter-email.json";

        $file = fopen($path, "r");
        if (flock($file, LOCK_EX)) {
            $json = json_decode(file_get_contents($path),true);
            $json[$screen_name] = $email;
            file_put_contents($path, json_encode($json));
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
    $_SESSION['user'] = $user;    
    $success = "true";
} else {
    $success = "false";
}
?>

<!doctype html>
<html>
<body>
<p>Close this window to continue.</p>
<script>
    try { window.opener.popupclose("<?php echo $success; ?>"); } catch(e) {}    
    window.close();
</script>
</body>
</html>
