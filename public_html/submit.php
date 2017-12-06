<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

$lat = floatval(trim( $_POST["lat"]));
$lng = floatval(trim( $_POST["lng"]));

if($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180){
    http_response_code(403);
    exit();    
}

if ($_SESSION['user'] == null) {
    http_response_code(403);
    exit();
}

$user = $_SESSION['user'];

// download and store image
$large_image = str_replace("normal", "bigger", $user->profile_image_url_https);
$tmp = explode('.', $large_image);
$extension = end($tmp);
$filename = $user->screen_name . "." . $extension;
$ch = curl_init($large_image);
$fp = fopen("img/" . $filename, 'wb');
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_exec($ch);
curl_close($ch);
fclose($fp);

$image_url = $filename;

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

$data["img"] = $image_url;
$data["lat"] = $lat;
$data["lng"] = $lng;

// formatted description
if( $description ){  
    $data["desc"] = $description; 
}

// user website
if( isset( $user->entities->url ) ){
    $data["url"] = $user->entities->url->urls[0]->display_url;
}    

// write user to json
$path = "map.json";
$file = fopen($path, "r");
if (flock($file, LOCK_EX)) {
    $json = json_decode(file_get_contents($path),true);
    $json[$user->screen_name] = $data;
    file_put_contents($path, json_encode($json));
    flock($file, LOCK_UN);
    fclose($file);
}

http_response_code(200);
echo json_encode($data);

?>
