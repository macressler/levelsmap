<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(403);
    exit();
}

if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) === false) {
    http_response_code(403);
    exit();
}

if( $_SERVER['HTTP_X_REQUESTED_WITH'] !== "XMLHttpRequest" ) {
    http_response_code(403);
    exit();
}

if ($_SESSION['user'] == null) {
    http_response_code(403);
    exit();
}

$user = $_SESSION['user'];
$large_image = str_replace("normal", "bigger", $user->profile_image_url_https);
$screen_name = $user->screen_name;
$description = $user->description;

// restore original urls in description (if needed)
if( isset($user->entities->description->urls) ) {
    foreach($user->entities->description->urls as $i){
        $description = str_replace($i->url, "<a target=\"_blank\" href=\"" . $i->expanded_url . "\">" . $i->display_url . "</a>", $description);
    }
}

// add links to twitter in description
$words = explode(" ", $description);
for($i = 0; $i < count($words); $i++){
    $w = $words[$i];
    if(substr($w,0,1) == "@"){
        $words[$i] = "<a class=\"twcl\" target=\"_blank\" href=\"https://twitter.com/" . substr($w,1) . "\">" . $w . "</a>";
    }
}
$description = implode(" ", $words);    

$data = array("img" => $large_image, "screen_name" => $screen_name);

// formatted description
if( $description ){  
    $data["desc"] = $description; 
}

// user website
if( isset( $user->entities->url ) ){
    $data["url"] = $user->entities->url->urls[0]->display_url;
}

echo json_encode($data);
?>
