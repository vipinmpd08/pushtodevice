<?php 
$app->get('/session', function() {
    $db = new DbHandler();
    $session = $db->getSession();
    $response["uid"] = $session['uid'];
    $response["email"] = $session['email'];
    $response["name"] = $session['name'];
    echoResponse(200, $session);
});

$app->post('/login', function() use ($app) {
    require_once 'passwordHash.php';
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email', 'password'),$r->customer);
    $response = array();
    $db = new DbHandler();
    $password = $r->customer->password;
    $email = $r->customer->email;
    $user = $db->getOneRecord("select uid,name,password,email,created,fb_apiaccesskey from customers_auth where phone='$email' or email='$email'");
    if ($user != NULL) {
        if(passwordHash::check_password($user['password'],$password)){
        $response['status'] = "success";
        $response['message'] = 'Logged in successfully.';
        $response['name'] = $user['name'];
        $response['uid'] = $user['uid'];
        $response['email'] = $user['email'];
        $response['createdAt'] = $user['created'];
		$response['fb_apiaccesskey'] = $user['fb_apiaccesskey'];
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['uid'] = $user['uid'];
        $_SESSION['email'] = $email;
        $_SESSION['name'] = $user['name'];
		$_SESSION['fb_apiaccesskey'] = $user['fb_apiaccesskey'];
        } else {
            $response['status'] = "error";
            $response['message'] = 'Login failed. Incorrect credentials';
        }
    }else {
            $response['status'] = "error";
            $response['message'] = 'No such user is registered';
        }
    echoResponse(200, $response);
});
$app->post('/signUp', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email', 'name', 'password'),$r->customer);
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $phone = $r->customer->phone;
    $name = $r->customer->name;
    $email = $r->customer->email;
    $address = $r->customer->address;
    $password = $r->customer->password;
    $isUserExists = $db->getOneRecord("select 1 from customers_auth where phone='$phone' or email='$email'");
    if(!$isUserExists){
        $r->customer->password = passwordHash::hash($password);
        $tabble_name = "customers_auth";
        $column_names = array('phone', 'name', 'email', 'password', 'city', 'address');
        $result = $db->insertIntoTable($r->customer, $column_names, $tabble_name);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "User account created successfully";
            $response["uid"] = $result;
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['uid'] = $response["uid"];
            $_SESSION['phone'] = $phone;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create customer. Please try again";
            echoResponse(201, $response);
        }            
    }else{
        $response["status"] = "error";
        $response["message"] = "An user with the provided phone or email exists!";
        echoResponse(201, $response);
    }
});


$app->get('/logout', function() {
    $db = new DbHandler();
    $session = $db->destroySession();
    $response["status"] = "info";
    $response["message"] = "Logged out successfully"; 
    echoResponse(200, $response);
});


$app->post('/sendNotification', function() use ($app) {
    $response = array();
	$registrationIds = array();
    $r = json_decode($app->request->getBody());
			
	//Get all Subscriptions for this user
	$db = new DbHandler();
    $session = $db->getSession();
	$subscriptions = $db->getRecords("select instanceidtoken from subscriptions where uid=".$session['uid']);


	//Prepare Registration IDs
	if(NULL != $subscriptions) {
		foreach($subscriptions as $subscriptionRow) { 
			array_push($registrationIds , $subscriptionRow['instanceidtoken']); 
		}
	}
 
	// prep the message bundle
	$msg = array
	(
		'title'		=> $r->message->title,
		'message' 	=> $r->message->body,
		'subtitle'	=> 'This is a subtitle. subtitle',
		'tickerText'=> 'Ticker text here...Ticker text here...Ticker text here',
		'vibrate'	=> 1,
		'sound'		=> 1,
		'icon'		=> property_exists($r->message, 'icon') ? $r->message->icon : 'Mail.png',
		'websiteurl'	=> $r->message->websiteurl,
		'htmlbody'	=> property_exists($r->message, 'htmlBody') ? $r->message->htmlBody : ''
	);
	$fields = array
	(
		'registration_ids' 	=> $registrationIds,
		'data'			=> $msg
	);	 
	$headers = array
	(
		'Authorization: key=' . $session['fb_apiaccesskey'],
		'Content-Type: application/json'
	);
	 
	//Send the message
	$ch = curl_init();	
	if(NULL != $ch) {
		curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		curl_close( $ch );
		
		$response["status"] = "success";
		$response["registrationIds"] = $registrationIds;
		$response["message"] = "Message pushed successfully. Have fun !";
		echoResponse(200, $response);			
	}
	else {
        $response["status"] = "error";
        $response["message"] = "There was some problem in sending the notification";
        echoResponse(201, $response);		
	}
	 
	
    //verifyRequiredParams(array('title', 'name', 'body'),$r->message);
	
});

$app->post('/addSubscriptions', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
   // verifyRequiredParams(array('email', 'name', 'password'),$r->customer);
    
	$db = new DbHandler();
	
	$valueObj = (object) array(
			'uid' => $r->subscribe->uid, 
			'instanceidtoken' => $r->subscribe->instanceidtoken,
			'client_ip' => (property_exists($r->subscribe, 'client_ip') && NULL != $r->subscribe->client_ip) ? $r->subscribe->client_ip : get_client_ip()
	);
	
    $isSubExists = $db->getOneRecord("select 1 from subscriptions where instanceidtoken='".$valueObj->instanceidtoken."' and uid=".$valueObj->uid);

    if(!$isSubExists){
		$tabble_name = "subscriptions";
        $column_names = array('uid', 'instanceidtoken', 'client_ip');
        $result = $db->insertIntoTable($valueObj, $column_names, $tabble_name);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "Subscribed successfully";           
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to Subscribe";
            echoResponse(201, $response);
        }            
    }else{
        $response["status"] = "error";
        $response["message"] = "Subscription exists already!";
        echoResponse(201, $response);
    }
});


function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}
?>