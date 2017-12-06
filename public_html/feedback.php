<?php

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(403);
    exit();
}

// honey pot
if($_POST["lastname"] != null){
    $badIpPath = "../data/bad-ip.json";
    // add to bad-ip list
    $file = fopen($badIpPath, "r");
    if (flock($file, LOCK_EX)) {
    
        $json = json_decode(file_get_contents($badIpPath),true);
        $json[$ip] = date('c');    
        file_put_contents($badIpPath, json_encode($json));
        
        flock($file, LOCK_UN);
        fclose($file);
    }
    http_response_code(200);
    exit();
}

if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) == false) {
    http_response_code(403);
    exit();
}

if( $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest" ) {
    http_response_code(403);
    exit();
}

$name = $_POST["name"];
$email = $_POST["email"];
$message = $_POST["message"];
$source = $_POST["source"];

$subject = "Levelsmap " . $source;

if($email && $name && $source){
    // save email to list
    $path = null;
    if($source == "feedback"){
        $path = "../data/feedback-email.json";
    } 
    if($source == "upgrade"){
        $path = "../data/upgrade-email.json";
    }

    $file = fopen($path, "r");
    if (flock($file, LOCK_EX)) {

        $json = json_decode(file_get_contents($path),true);

        // $data["email"] =  $email;  
        $json[$name] = $email;

        file_put_contents($path, json_encode($json));
        
        flock($file, LOCK_UN);
        fclose($file);
    }
}


$textbody = "Name: " . $name . "\n\nEmail: " . $email . "\n\nMessage: " . $message;


require "../access-keys.php";
require "../lib/aws/aws-autoloader.php";

use Aws\Ses\SesClient;
use Aws\Ses\Exception\SesException;

$client = SesClient::factory(array(
    'credentials' => array(
        'key'    => AWS_ACCESS_KEY_ID,
        'secret' => AWS_SECRET_ACCESS),
    'version'=> 'latest',     
    'region' => "us-east-1"
));

try {
     $result = $client->sendEmail([
    'Destination' => [
        'ToAddresses' => [
            EMAIL_ADDRESS,
        ],
    ],
    'Message' => [
        'Body' => [
			'Text' => [
                'Charset' => 'UTF-8',
                'Data' => $textbody,
            ],
        ],
        'Subject' => [
            'Charset' => 'UTF-8',
            'Data' => $subject,
        ],
    ],
    'Source' => EMAIL_ADDRESS
    ]);
} catch (SesException $error) {
    http_response_code(403);
    exit();
}

?>
