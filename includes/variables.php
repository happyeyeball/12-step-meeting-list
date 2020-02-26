<?php
/*	
Don't make changes to this file! You'll have to reapply them every time you update the plugin.
if you need to customize your site, please follow the instructions on our FAQ:
https://wordpress.org/plugins/12-step-meeting-list/
*/

//get the current boundaries of the coverage map
$tsml_bounds = get_option('tsml_bounds');

//get the secret cache location
if (!$tsml_cache = get_option('tsml_cache')) {
	$tsml_cache = '/tsml-cache-' . substr(str_shuffle(md5(microtime())), 0, 10) . '.json';
	update_option('tsml_cache', $tsml_cache);
}

//load the set of columns that should be present in the list (not sure why this shouldn't go after plugins_loaded below)
$tsml_columns = array(
	'time' => 'Time',
	'distance' => 'Distance', 
	'name' => 'Meeting',
	'location' => 'Location',
	'address' => 'Address',
	'region' => 'Region',
	'district' => 'District'
);

//whether contacts are displayed publicly (defaults to no)
$tsml_contact_display = get_option('tsml_contact_display', 'private');

//empty global curl handle in case we need it
$tsml_curl_handle = null;

//load the array of URLs that we're using
$tsml_data_sources = get_option('tsml_data_sources', array());

//meeting search defaults
$tsml_defaults = array(
	'distance' => 2,
	'time' => null,
	'region' => null,
	'district' => null,
	'day' => intval(current_time('w')),
	'type' => null,
	'mode' => 'search',
	'query' => null,
	'view' => 'list',
);

//load the distance units that we're using (ie miles or kms)
$tsml_distance_units = get_option('tsml_distance_units', 'mi');

//load email addresses to send user feedback about meetings
$tsml_feedback_addresses = get_option('tsml_feedback_addresses', array());

//load the API key user saved, if any
$tsml_google_maps_key = get_option('tsml_google_maps_key');

/*
unfortunately the google geocoding API is not always perfect. used by tsml_import() and admin.js
find correct coordinates with http://nominatim.openstreetmap.org/ and https://www.latlong.net/
*/
$tsml_google_overrides = array(

	'38 West End Ave, Old Greenwich, CT 06870, USA' => array(
		'formatted_address' => '38 West End Ave, Old Greenwich, CT 06870, USA',
		'city' => 'Old Greenwich',
		'latitude' => 41.0310048,
		'longitude' => -73.5719473,
	),


);

//get the blog's language (used as a parameter when geocoding)
$tsml_language = substr(get_bloginfo('language'), 0, 2);

//alternative maps provider
$tsml_mapbox_key = get_option('tsml_mapbox_key');

//if no maps key, check to see if the events calendar plugin has one
if (empty($tsml_google_maps_key) && empty($tsml_mapbox_key)) {
	if ($tribe_options = get_option('tribe_events_calendar_options', array())) {
		if (array_key_exists('google_maps_js_api_key', $tribe_options)) {
			$tsml_google_maps_key = $tribe_options['google_maps_js_api_key'];
			update_option('tsml_google_maps_key', $tsml_google_maps_key);
		}
	}
}

//used to secure forms
$tsml_nonce = plugin_basename(__FILE__);

//load email addresses to send emails when there is a meeting change
$tsml_notification_addresses = get_option('tsml_notification_addresses', array());

//load the program setting (NA, AA, etc)
$tsml_program = get_option('tsml_program', 'da');

//get the sharing policy
$tsml_sharing = get_option('tsml_sharing', 'restricted');

//get the sharing policy
$tsml_sharing_keys = get_option('tsml_sharing_keys', array());

//the default meetings sort order
$tsml_sort_by = 'time';

//only show the street address (not the full address) in the main meeting list
$tsml_street_only = true;

//for timing
$tsml_timestamp = microtime(true);

//these are empty now because polylang might change the language. gets set in the plugins_loaded hook
$tsml_days = $tsml_days_order = $tsml_programs = $tsml_types_in_use = $tsml_slug = null; $tsml_strings = null;

add_action('plugins_loaded', 'tsml_define_strings');

function tsml_define_strings() {
	global $tsml_days, $tsml_days_order, $tsml_programs, $tsml_program, $tsml_slug, $tsml_strings, $tsml_types_in_use;

    //load internationalization
    load_plugin_textdomain('12-step-meeting-list', false, '12-step-meeting-list/languages');

//* LD_MASTER: EVEN THOUGH WE ARE USING 'See Notes' AS A DAY OF THE WEEK (DATA VALUE 7) WE
//* DON'T USE IT HERE CAUSE WE DON'T WANT IT TO APPEAR IN THE DAYS DROPDOWN IN MEETING SEARCH
	//days of the week
	$tsml_days	= array(
		__('Sunday', '12-step-meeting-list'),
		__('Monday', '12-step-meeting-list'),
		__('Tuesday', '12-step-meeting-list'),
		__('Wednesday', '12-step-meeting-list'),
		__('Thursday', '12-step-meeting-list'), 
		__('Friday', '12-step-meeting-list'), 
		__('Saturday', '12-step-meeting-list'),
		//__('See notes', '12-step-meeting-list'),
	);

	//adjust if the user has set the week to start on a different day
	if ($start_of_week = get_option('start_of_week', 0)) {
		$remainder = array_slice($tsml_days, $start_of_week, null, true);
		$tsml_days = $remainder + $tsml_days;
	}

	//used by tsml_meetings_sort() over and over
	$tsml_days_order = array_keys($tsml_days);
	
	//supported program names (alpha by the 'name' key)
	$tsml_programs = array(
		'da' => array(
			'abbr' => __('DA', '12-step-meeting-list'),
			'flags' => array('M', 'W'), //for /men and /women at end of meeting name (used in tsml_format_name())
			'name' => __('Debtors Anonymous', '12-step-meeting-list'),
			'types' => array(
				'AB' => __('Abundance', '12-step-meeting-list'),
				'AR' => __('Artist', '12-step-meeting-list'),
				'B' => __('Business Owner (BDA)', '12-step-meeting-list'),
				'C' => __('Closed (D.A. members only)', '12-step-meeting-list'),
				'CL' => __('Clutter', '12-step-meeting-list'),
				'GSR' => __('GSR', '12-step-meeting-list'),
//				'HOW' => __('HOW (Honesty, Openness, Willingness)', '12-step-meeting-list'),
				'SK' => __('Skype', '12-step-meeting-list'),
				'HY' => __('Hybrid (in-person and telephone)', '12-step-meeting-list'),
				'IG' => __('Intergroup', '12-step-meeting-list'),
				'IN' => __('International', '12-step-meeting-list'),
				'NET' => __('Internet', '12-step-meeting-list'),
				'M' => __('Men', '12-step-meeting-list'),
				'N' => __('Numbers', '12-step-meeting-list'),
				'O' => __('Open (to all)', '12-step-meeting-list'),
				'P' => __('Prosperity', '12-step-meeting-list'),
				'SP' => __('Speaker', '12-step-meeting-list'),
				'ST' => __('Step Study', '12-step-meeting-list'),
				'TE' => __('Telephone', '12-step-meeting-list'),
				'TI' => __('Time', '12-step-meeting-list'),
				'TO' => __('Toolkit', '12-step-meeting-list'),
				'V' => __('Vision', '12-step-meeting-list'),
				'W' => __('Women', '12-step-meeting-list'),
				'X' => __('Wheelchair Accessible', '12-step-meeting-list'),
			),
		),

	);
	//the location where the list will show up, eg https://intergroup.org/meetings
	$tsml_slug = sanitize_title(__('meetings', '12-step-meeting-list'));

	//strings that must be synced between the javascript and the PHP
	$tsml_strings = array(
		'data_error' => __('Got an improper response from the server, try refreshing the page.', '12-step-meeting-list'),
		'email_not_sent' => __('Email was not sent.', '12-step-meeting-list'),
		'loc_empty' => __('Enter a location in the field above.', '12-step-meeting-list'),
		'loc_error' => __('Google could not find that location.', '12-step-meeting-list'),
		'loc_thinking' => __('Looking up address…', '12-step-meeting-list'),
		'geo_error' => __('There was an error getting your location.', '12-step-meeting-list'),
		'geo_error_browser' => __('Your browser does not appear to support geolocation.', '12-step-meeting-list'),
		'geo_thinking' => __('Finding you…', '12-step-meeting-list'),
		'groups' => __('Groups', '12-step-meeting-list'),
		'locations' => __('Locations', '12-step-meeting-list'),
		'meetings' => __('Meetings', '12-step-meeting-list'),
		'men' => __('Men', '12-step-meeting-list'),
		'no_meetings' => __('No meetings were found matching the selected criteria.', '12-step-meeting-list'),
		'regions' => __('Regions', '12-step-meeting-list'),
		'women' => __('Women', '12-step-meeting-list'),
		'first_search' => __('Search for meetings - Select search criteria from the choice above', '12-step-meeting-list'),
	);

	$tsml_types_in_use = get_option('tsml_types_in_use', array());
	if (!is_array($tsml_types_in_use)) $tsml_types_in_use = array();
}
