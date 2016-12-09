<?php
// API access key from Google API's Console
define( 'API_ACCESS_KEY', 'AAAAzrbjF6o:APA91bF9GUzRIOt_xAfYVKcrYisjrqmFIQwn-UlDj5iZMVjcgU3W4lKYkVvTlu9JzoDim4uZKY1gM_q88-PFiBpaMnV_R5GfrhllJUOASK4N-3DoAINS475LJh7_YlZvyTxx9RV9koeoYhSQGJYsKoelFdiS9VRaSg' );

$registrationIds = array("eGNOM5rbN9c:APA91bHIa2nrkqWUY2cN1dx0TNN5oe5L4e4nk1sRK5tFMQSjOhyHCHMUDAkxgvdwYnRF7ygQbEzw9uIbECtWY_BNlvV03X_uHLYYgHdd1jILw8iWIBPu7rDZcjO9QEgxxFcInEoxUGwp" );

//echo $_GET['id'];
// prep the bundle
$msg = array
(
	'message' 	=> 'You have a mail',
	'title'		=> 'Hellow',
	'subtitle'	=> 'This is a subtitle. subtitle',
	'tickerText'	=> 'Ticker text here...Ticker text here...Ticker text here',
	'vibrate'	=> 1,
	'sound'		=> 1,
	'largeIcon'	=> 'Mail.png',
	'smallIcon'	=> 'Mail.png',
	'icon'		=> 'Mail.png',
	'htmlbody'		=> 'message goes here'
);
$fields = array
(
	'registration_ids' 	=> $registrationIds,
	'data'			=> $msg
);
 
$headers = array
(
	'Authorization: key=' . API_ACCESS_KEY,
	'Content-Type: application/json'
);
 
$ch = curl_init();
curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
curl_setopt( $ch,CURLOPT_POST, true );
curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
$result = curl_exec($ch );
curl_close( $ch );
echo $result;

?>