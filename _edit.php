<?php

@session_start();

//echo "Old Session: $old_sessionid<br />";
//echo "New Session: $new_sessionid<br />";

ini_set('display_errors', 1); ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once('config.php');
include_once('include/functions.php');


//print_r($_POST);
//print_r($_SESSION);
				
$action = clean_request($_POST, "action", "basic");
$updateField['master_id'] = clean_request($_POST, "master_id", "numeric");
$updateField['_username'] = clean_request($_POST, "_username", "basic");
$updateField['status'] = clean_request($_POST, "status", "tiptoe");
$updateField['action'] = clean_request($_POST, "action", "tiptoe");
$updateField['name'] = clean_request($_POST, "name", "tiptoe");
$updateField['day'] = clean_request($_POST, "day", "alpha", 30);
$updateField['time'] = clean_request($_POST, "time", "basic");
$updateField['end_time'] = clean_request($_POST, "end_time", "basic");
$updateField['tz_code'] = clean_request($_POST, "tz_code", "basic");
if (isset($_POST["types"])) {
	$updateField['types'] = $_POST["types"];
}
$updateField['notes'] = clean_request($_POST, "notes", "tiptoe");
$updateField['location_notes'] = clean_request($_POST, "location_notes", "tiptoe");
$updateField['special_contact_label'] = clean_request($_POST, "special_contact_label", "basic");
$updateField['special_contact'] = clean_request($_POST, "special_contact", "tiptoe");
$updateField['primary_language'] = clean_request($_POST, "primary_language", "basic");
$updateField['last_contact'] = clean_request($_POST, "last_contact", "basic");
$updateField['phone'] = clean_request($_POST, "phone", "tiptoe");
$updateField['email'] = clean_request($_POST, "email", "basic");
$updateField['website'] = clean_request($_POST, "website", "basic");

if ($action == 'pushweb') {

    define('WP_USE_THEMES', false);
    require('/var/www/html/wp-load.php');
    push_admin_meeting($updateField['master_id'],$updateField['action']);
    
}
if ($action == 'fixpermalink') {
    define('WP_USE_THEMES', false);
    require('/var/www/html/wp-load.php');

    if ($updateField['status'] == 'Active') {
    
		//fix_permalink($updateField['master_id']);
    }

}
if ($action == 'web') {

	$dbWeb->where("meta_key", "master_id");
	$dbWeb->where("meta_value", $updateField['master_id']);
	$result = $dbWeb->get ("dbswp_postmeta");
	
//	print_r($result);
	foreach ($result as $resultData) {
	
		$post_id = $resultData['post_id'];
	
	}

	//print "post_id: " . $post_id . "\n";

	$dbWeb->where("ID", $post_id);
	$result = $dbWeb->get ("dbswp_posts");
	
//	print_r($result);
	foreach ($result as $resultData) {
	
		$post_parent = $resultData['post_parent'];
	
	}

	//print "post_parent: " . $post_parent . "\n";
	
	
	//* BEGIN UPDATES
	
	//* dbswp_posts by post_parent holds location_notes only, This rec is tsml_location.
	//* this record is global location notes, and so this will override location_notes for the
	//* same location for different meetings. meeting_location_notes are below.
	//* Developer note: I will never get this WordPress thing. It's an old blog with stuff 
	//* built on top of it. Like Windows on top of DOS. 
	//*	select * from dbswp_posts where ID=@post_parent;

	$dbWeb->where ('ID', $post_parent);

	$data = Array (
					'post_content' => $updateField['location_notes']
				);


	if ($dbWeb->update ('dbswp_posts', $data)) {
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";


	//* dbswp_posts by post_id holds notes, name and canonical url (post_title), This rec is tsml_meeting.
	//*	select * from dbswp_posts where ID=@post_id;


	$dbWeb->where ('ID', $post_id);

	$data = Array (
					'post_content' => $updateField['notes']
				);


	if ($dbWeb->update ('dbswp_posts', $data)) {
		echo "Group notes updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";
	
	$canonical_name = strtolower($updateField['name']);
	$canonical_name = str_replace(" ", "-", $canonical_name);
	$canonical_name = str_replace(",", "", $canonical_name);
	$canonical_name = str_replace("'", "", $canonical_name);
	$canonical_name = str_replace("(", "", $canonical_name);
	$canonical_name = str_replace(")", "", $canonical_name);
	$canonical_name = str_replace(".", "", $canonical_name);
	$canonical_name = str_replace("!", "", $canonical_name);

	$url = "https://debtorsanonymous.org/meetings/";
	
	$canonical_url = $url . $canonical_name;
	//print "CANONICAL NAME: " . $canonical_name;
	$dbWeb->where ('ID', $post_id);

	$data = Array (
					'post_title' => $updateField['name'],
					'post_name' => $canonical_name,
					'guid' => $canonical_url
				);


	if ($dbWeb->update ('dbswp_posts', $data)) {
		echo "Name updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";


	//* META:
	/*
	day
	time
	end_time
	types
	master_id
	primary_language
	meeting_location_notes
	group_number
	special_contact_label
	special_contact
	tz_code
	tz_description
	physical_location
	last_contact
	website
	phone
	email
	*/
	//*select * from dbswp_postmeta where post_id = @post_id; 

	//* DAY
	
	if ($updateField['day'] == "") {
	
		$day_numeric = 7;
		
	} else {
	
		$day_numeric = date('N', strtotime($updateField['day']));
		$day_numeric = (string)$day_numeric;

	}
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'day');

	$data = Array (
					'meta_value' => $day_numeric
				);

	if ($dbWeb->update ('dbswp_postmeta', $data)) {
		echo "Day updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";


//* TIME
	$time_prep = substr($updateField['time'], 0, 5);

	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'time');

	$data = Array (
					'meta_value' => $time_prep
				);

	if ($dbWeb->update ('dbswp_postmeta', $data)) {
		echo "Start time updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

//* END TIME
	$time_prep = substr($updateField['end_time'], 0, 5);

	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'end_time');

	$data = Array (
					'meta_value' => $time_prep
				);

	if ($dbWeb->update ('dbswp_postmeta', $data)) {
		echo "End time updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

//* TYPES
	$types_serialized = serialize($updateField['types']);
	//die($types_serialized);	
	
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'types');

	$data = Array (
					'meta_value' => $types_serialized
				);

	if ($dbWeb->update ('dbswp_postmeta', $data)) {
		echo "Meeting types updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

//*LOCATION NOTES
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'meeting_location_notes');

	$data = Array (
					'meta_value' => $updateField['location_notes']
				);

	if ($dbWeb->update ('dbswp_postmeta', $data)) {
		echo "Meeting notes updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

//* PRIMARY_LANGUAGE
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'primary_language');

	$data = Array (
					'meta_value' => $updateField['primary_language']
				);

	if ($dbWeb->update ('dbswp_postmeta', $data)) {
		echo "Primary Language updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

//* LAST_CONTACT
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'last_contact');

	$data = Array (
					'meta_value' => $updateField['last_contact']
				);

	if ($dbWeb->update ('dbswp_postmeta', $data)) {
		echo "Last contact date updated (" . $dbWeb->count . "), ";
		//echo $dbWeb->count . ' records were updated';
		
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

/*
	special_contact_label
	special_contact
	tz_code
	tz_description
	physical_location
	last_contact
	website
	phone
	email
*/

	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'special_contact_label');

	if ($dbWeb->delete ('dbswp_postmeta')) {
		//echo $dbWeb->count . ' records were updated';
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

//* SPECIAL CONTACT LABEL

	if ($updateField['special_contact_label'] != '') {

		$data = Array (
						'post_id' => $post_id,
						'meta_key' => 'special_contact_label',
						'meta_value' => $updateField['special_contact_label']
					);

		//if (
		$newID = $dbWeb->insert ('dbswp_postmeta', $data);
		//	echo $dbWeb->count . ' records were updated';
		
		//} else {
			//echo 'insert failed: ' . $dbWeb->getLastError();
		//}	
		//print $dbWeb->getLastQuery() . "\n";

	}
	

//* SPECIAL CONTACT FIELD
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'special_contact');

	if ($dbWeb->delete ('dbswp_postmeta')) {
		//echo $dbWeb->count . ' records were updated';
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";
	

	if ($updateField['special_contact'] != '') {

		$data = Array (
						'post_id' => $post_id,
						'meta_key' => 'special_contact',
						'meta_value' => $updateField['special_contact']
					);

		//if (
		$newID = $dbWeb->insert ('dbswp_postmeta', $data);
		//	echo $dbWeb->count . ' records were updated';
		
		//} else {
			//echo 'insert failed: ' . $dbWeb->getLastError();
		//}	
		//print $dbWeb->getLastQuery() . "\n";

	}


	echo "Special contact updated, ";
	//die("TZ: " . $updateField['tz_code']);

//* TIMEZONE
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'tz_code');

	if ($dbWeb->delete ('dbswp_postmeta')) {
		//echo $dbWeb->count . ' records were updated';
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";
	
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'tz_description');

	if ($dbWeb->delete ('dbswp_postmeta')) {
		//echo $dbWeb->count . ' records were updated';
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";

	if ($updateField['tz_code'] != '') {
	
		$db->where ('tz_code', $updateField['tz_code']);
		$tzData = $db->get('timezones');

		foreach($tzData as $tzRow) {
		
			$tz_description = $tzRow['tz_description'];
			
		}

		//die('CODE: ' . $updateField['tz_code']. ' DESC: ' . $tz_description);

		$data = Array (
						'post_id' => $post_id,
						'meta_key' => 'tz_code',
						'meta_value' => $updateField['tz_code']
					);

		//if (
		$newID = $dbWeb->insert ('dbswp_postmeta', $data);
		//	echo $dbWeb->count . ' records were updated';
		
		//} else {
			//echo 'insert failed: ' . $dbWeb->getLastError();
		//}	
		//print $dbWeb->getLastQuery() . "\n";

		$data = Array (
						'post_id' => $post_id,
						'meta_key' => 'tz_description',
						'meta_value' => $tz_description
					);

		//if (
		$newID = $dbWeb->insert ('dbswp_postmeta', $data);
		//	echo $dbWeb->count . ' records were updated';
		
		//} else {
			//echo 'insert failed: ' . $dbWeb->getLastError();
		//}	
		//print $dbWeb->getLastQuery() . "\n";

	}
	echo "Timezones updated\n";
	
//* WEBSITE FIELD
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'website');

	if ($dbWeb->delete ('dbswp_postmeta')) {
		//echo $dbWeb->count . ' records were updated';
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";
	

	if ($updateField['website'] != '') {

		$data = Array (
						'post_id' => $post_id,
						'meta_key' => 'website',
						'meta_value' => $updateField['website']
					);

		//if (
		$newID = $dbWeb->insert ('dbswp_postmeta', $data);
		//	echo $dbWeb->count . ' records were updated';
		
		//} else {
			//echo 'insert failed: ' . $dbWeb->getLastError();
		//}	
		//print $dbWeb->getLastQuery() . "\n";

	}


	echo "Website updated, ";
	//die("TZ: " . $updateField['tz_code']);
	
	
//* PHONE FIELD
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'phone');

	if ($dbWeb->delete ('dbswp_postmeta')) {
		//echo $dbWeb->count . ' records were updated';
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";
	

	if ($updateField['phone'] != '') {

		$data = Array (
						'post_id' => $post_id,
						'meta_key' => 'phone',
						'meta_value' => $updateField['phone']
					);

		//if (
		$newID = $dbWeb->insert ('dbswp_postmeta', $data);
		echo "Phone updated (" . $dbWeb->count . "), ";
		
		//} else {
			//echo 'insert failed: ' . $dbWeb->getLastError();
		//}	
		//print $dbWeb->getLastQuery() . "\n";

	}


	echo "Phone updated, ";
	//die("TZ: " . $updateField['tz_code']);
	
//* EMAIL FIELD
	$dbWeb->where ('post_id', $post_id);
	$dbWeb->where ('meta_key', 'email');

	if ($dbWeb->delete ('dbswp_postmeta')) {
		//echo $dbWeb->count . ' records were updated';
	} else {
		//echo 'update failed: ' . $dbWeb->getLastError();
	}	
	//print $dbWeb->getLastQuery() . "\n";
	

	if ($updateField['email'] != '') {

		$data = Array (
						'post_id' => $post_id,
						'meta_key' => 'email',
						'meta_value' => $updateField['email']
					);

		//if (
		$newID = $dbWeb->insert ('dbswp_postmeta', $data);
		echo "Email updated (" . $dbWeb->count . "), ";
		
		//} else {
			//echo 'insert failed: ' . $dbWeb->getLastError();
		//}	
		//print $dbWeb->getLastQuery() . "\n";

	}


	//die("TZ: " . $updateField['tz_code']);
	
	
	print "Web Updated successfully.";


}
