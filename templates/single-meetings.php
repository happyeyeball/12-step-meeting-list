<?php
tsml_assets();

$meeting = tsml_get_meeting();

//define some vars for the map
wp_localize_script('tsml_public', 'tsml_map', array(
    'directions' => __('Directions', '12-step-meeting-list'),
    'directions_url' => $meeting->directions,
    'formatted_address' => $meeting->formatted_address,
    'latitude' => $meeting->latitude,
    'location' => get_the_title($meeting->post_parent),
    'location_id' => $meeting->post_parent,
    'location_url' => get_permalink($meeting->post_parent),
    'longitude' => $meeting->longitude,
));

$startDate = tsml_format_next_start($meeting);

//adding custom body classes
add_filter('body_class', 'tsml_body_class');
function tsml_body_class($classes)
{
    $classes[] = 'tsml tsml-detail tsml-meeting';
    return $classes;
}

get_header();


?>

<div id="tsml">
	<div id="meeting" class="container mobilemod"">
		<div class="row">
			<div class="col-xs-6 col-sm-6 col-md-12 col-md-offset-1 col-xs-offset-neg-1 main" >

				<div class="page-header">
					<h1><?php echo tsml_format_name($meeting->post_title, $meeting->types) ?></h1>
					<?php echo tsml_link(get_post_type_archive_link('tsml_meeting'), '<i class="glyphicon glyphicon-chevron-right"></i> ' . __('Back to Meetings', '12-step-meeting-list'), 'tsml_meeting') ?>
				</div>

				<div class="row">
				
<?php if ($meeting->physical_location =='Y') { ?>
					<div class="col-md-6">
<?php } else { ?>
					<div class="col-md-12">
<?php } ?>
						<div class="panel panel-default">
						

<?php if ($meeting->physical_location =='Y') { ?>

							<a class="panel-heading tsml-directions" href="#" data-latitude="<?php echo $meeting->latitude ?>" data-longitude="<?php echo $meeting->longitude ?>" data-location="<?php echo $meeting->location ?>">
								<h3 class="panel-title">
									<?php _e('Get Directions', '12-step-meeting-list')?>
									<span class="panel-title-buttons">
										<span class="glyphicon glyphicon-share-alt"></span>
									</span>
								</h3>
							</a>
							
<?php } ?>
						</div>

						<div class="panel panel-default">
							<ul class="list-group">
								<li class="list-group-item meeting-info">
									<h3 class="list-group-item-heading">
									
									<?php _e('Meeting Information', '12-step-meeting-list')?></h3>
								<?php
									echo '<p class="meeting-time"' . ($startDate ? ' content="' . $startDate . '"' : '') . '>';

									if ($meeting->day <> 7) {
										echo tsml_format_day_and_time($meeting->day, $meeting->time);

									} else {
										echo $meeting->time;
									}
									if (!empty($meeting->end_time)) {
										/* translators: until */
										echo __(' to ', '12-step-meeting-list'), tsml_format_time($meeting->end_time);
									}
    if (!empty($meeting->tz_description)) {
									echo '<br>';
									echo $meeting->tz_description;
	}
									echo '</p>';
									
									
if (count($meeting->types_expanded)) { 

	if (count($meeting->types_expanded) == 1) {
		echo '<p class="meeting-time"><b>Meeting type:</b> ';
	} else {
		echo '<p class="meeting-time"><b>Meeting types:</b> ';
	}

?>



<!---	<ul class="meeting-types"> -->
<?php 
	
$dummy = "";
	foreach ($meeting->types_expanded as $type) { 
	$dummy .= $type . ", ";
}
$dummy = substr($dummy, 0, -2); 
_e($dummy, '12-step-meeting-list');
	
?>
<!---	</ul> --->
	<?php 
	if (!empty($meeting->type_description)) {?>
		$dummy = 
		<p class="meeting-type-description"><?php _e($meeting->type_description, '12-step-meeting-list')?></p>
	<?php }
}

echo '<p class="meeting-time"><b>Primary language:</b> ';
echo $meeting->primary_language;
echo '</p>';


if (!empty($meeting->notes)) {?>
										<section class="meeting-notes"><?php echo wpautop($meeting->notes) ?></section>
<?php }
if (!empty($meeting->meeting_location_notes)) {?>
										<section class="meeting-notes"><?php echo wpautop($meeting->meeting_location_notes) ?></section>
<?php }?>
			
								</li>
								<?php
if (!empty($meeting->location_id)) {
    $location_info = '<div>
										<h3 class="list-group-item-heading">' . $meeting->location . '</h3>';
    if ($other_meetings = count($meeting->location_meetings) - 1 && $displayDirections) {
        $location_info .= '<p class="location-other-meetings">' . sprintf(_n('%d other meeting at this location', '%d other meetings at this location', $other_meetings, '12-step-meeting-list'), $other_meetings) . '</p>';
    }

    $location_info .= '<p class="location-address">' . tsml_format_address($meeting->formatted_address) . '</p>';

    if (!empty($meeting->meeting_location_notes)) {
        $location_info .= '<section class="location-notes">' . wpautop($meeting->meeting_location_notes) . '</section>';
    }

    if (!empty($meeting->region) && !strpos($meeting->formatted_address, $meeting->region)) {
        $location_info .= '<p class="location-region">' . $meeting->region . '</p>';
    }
//print "LOC NOTES: " . $meeting->meeting_location_notes . "<Br>";
    $location_info .= '</div>';


	if ($displayDirections) { 

		echo tsml_link(
			get_permalink($meeting->post_parent),
			$location_info,
			'tsml_meeting',
			'list-group-item list-group-item-location'
		);
    
    } else {
    
    	//echo '<p style="margin-left:30px;">' . $location_info . '</p>';
    
    }
}

if (!empty($meeting->group) || !empty($meeting->website) || !empty($meeting->website_2) || !empty($meeting->email) || !empty($meeting->phone)) {?>
									<li class="list-group-item list-group-item-group">
										<h3 class="list-group-item-heading"><?php echo $meeting->group ?></h3>
										<?php if (!empty($meeting->group_notes)) {?>
											<section class="group-notes"><?php echo wpautop($meeting->group_notes) ?></section>
										<?php }
    if (!empty($meeting->district)) {?>
											<section class="group-district"><?php echo $meeting->district ?></section>
										<?php }
    if (!empty($meeting->website)) {?>
											<p class="group-website">
												<a href="<?php echo $meeting->website ?>" target="_blank"><?php echo $meeting->website ?></a>
											</p>
										<?php }
    if (!empty($meeting->website_2)) {?>

										<?php }
    if (!empty($meeting->email)) {?>
											<p class="group-email">
												<a href="mailto:<?php echo $meeting->email ?>"><?php echo $meeting->email ?></a>
											</p>
											<?php }
    if (!empty($meeting->phone)) {?>
											<p class="group-phone">
												<a href="tel:<?php echo $meeting->phone ?>"><?php echo $meeting->phone ?></a>
											</p>
										</a>
										<?php }
    if (!empty($meeting->venmo)) {?>
											<p class="group-venmo">
												Venmo: <a href="https://venmo.com/<?php echo substr($meeting->venmo, 1) ?>" target="_blank"><?php echo $meeting->venmo ?></a>
											</p>
										</a>
										<?php }?>
									</li>
								<?php }?>
								<li class="list-group-item list-group-item-updated">


<?php
if (!empty($meeting->special_contact_label) && !empty($meeting->special_contact)) {

	echo '<p class="meeting-time">' . $meeting->special_contact_label . ':<br>';
	if (strpos($meeting->special_contact, 'http')===0) {
		echo '<a href="' . $meeting->special_contact . '" target="_blank">' . $meeting->special_contact . '</a>';
	} else {
		echo $meeting->special_contact;
	}
	echo '</p>';

}

echo '<p class="meeting-time">Group number: ';
echo $meeting->group_number;
echo '</p>';
?>
	

									<?php _e('Updated', '12-step-meeting-list')?>
									<?php
									
									/* MODIFIED 2019-12-06 JDF
									WP LAST MODIFIED NOT A GOOD LAST CONTACT Date
									USING LAST_CONTACT
									*/
									
									//the_modified_date();
									
									echo date('F Y', strtotime($meeting->last_contact)) . '<br>';
									$now = time();

									$timeSetup = mktime(date("G"),date("i"),date("s"),date("m"),date("d"),date("Y") - 1);
									$activeTimestamp = date("Y-m-d G:i:s", $timeSetup);

									$timeSetup = mktime(date("G"),date("i"),date("s"),date("m"),date("d"),date("Y") - 3);
									$possibleTimestamp = date("Y-m-d G:i:s", $timeSetup);

									//print $meeting->last_contact . ' ' . $activeTimestamp;
									if ($meeting->last_contact >= $activeTimestamp) {
									
										echo '<strong style="color:green">ACTIVE</strong>';
									}
									if ($meeting->last_contact >= $possibleTimestamp && $meeting->last_contact < $activeTimestamp) {
									
										echo '<strong style="color:orange">POSSIBLY ACTIVE</strong>';
									}
									if ($meeting->last_contact < $possibleTimestamp) {
									
										echo '<strong style="color:orange">UNKNOWN IF ACTIVE</strong>';
									}
									//echo "<Br>";
									//print_r($meeting);
									
									?>
								</li>
							</ul>
						</div>

						<?php
if ($tsml_contact_display == 'public') {
    for ($i = 1; $i <= GROUP_CONTACT_COUNT; $i++) {
        if (!empty($meeting->{'contact_' . $i . '_name'}) || !empty($meeting->{'contact_' . $i . '_email'}) || !empty($meeting->{'contact_' . $i . '_phone'})) {?>
								<div class="panel panel-default">
									<div class="panel-heading">
										<h3 class="panel-title">
											<?php if (!empty($meeting->{'contact_' . $i . '_name'})) {
            echo $meeting->{'contact_' . $i . '_name'};
        }
            ?>
											<span class="panel-title-buttons">
												<?php if (!empty($meeting->{'contact_' . $i . '_email'})) {?><a href="mailto:<?php echo $meeting->{'contact_' . $i . '_email'} ?>"><span class="glyphicon glyphicon-envelope"></span></a><?php }?>
												<?php if (!empty($meeting->{'contact_' . $i . '_phone'})) {?><a href="tel:<?php echo preg_replace('~\D~', '', $meeting->{'contact_' . $i . '_phone'}) ?>"><span class="glyphicon glyphicon-earphone"></span></a><?php }?>
											</span>
										</h3>
									</div>
								</div>
								<?php }
    }
}
if (!empty($tsml_feedback_addresses)) {?>
						<form id="feedback">
							<input type="hidden" name="action" value="tsml_feedback">
							<input type="hidden" name="master_id" value="<?php echo $meeting->master_id ?>">
							<input type="hidden" name="meeting_id" value="<?php echo $meeting->ID ?>">
							<input type="hidden" name="group_number" value="<?php echo $meeting->group_number ?>">
							<?php wp_nonce_field($tsml_nonce, 'tsml_nonce', false)?>
							<div class="panel panel-default panel-expandable">
								<div class="panel-heading">
									<h3 class="panel-title">
										<?php _e('Request a change to this listing (you must be a registered representative)', '12-step-meeting-list')?>
										<span class="panel-title-buttons">
											<span class="glyphicon glyphicon-chevron-left"></span>
										</span>
									</h3>
								</div>
								<ul class="list-group">
									<li class="list-group-item list-group-item-warning">
										<?php _e('Use this form to submit a simple change to the meeting information above. Please use the <a href="https://debtorsanonymous.org/meeting-registration/" target="_blank">full meeting registation form</a> for annual updates. *Note: Each meeting is autonomous. We do not pass requests to the individual meetings.', '12-step-meeting-list')?>
									</li>
									<li class="list-group-item list-group-item-form">
										<input type="text" id="tsml_name" name="tsml_name" placeholder="<?php _e('Your Name', '12-step-meeting-list')?>" class="required">
									</li>
									<li class="list-group-item list-group-item-form">
										<input type="email" id="tsml_email" name="tsml_email" placeholder="<?php _e('Email Address', '12-step-meeting-list')?>" class="required email">
									</li>
									<li class="list-group-item list-group-item-form">
										<textarea id="tsml_message" name="tsml_message" placeholder="<?php _e('Message', '12-step-meeting-list')?>" class="required"></textarea>
									</li>
									<li class="list-group-item list-group-item-form">
										<button type="submit"><?php _e('Submit', '12-step-meeting-list')?></button>
									</li>
								</ul>
							</div>
						</form>
						<?php }?>

					</div>
<?php if ($meeting->physical_location =='Y') { ?>
					<div class="col-md-6">

						<?php if (!empty($tsml_mapbox_key) || !empty($tsml_google_maps_key)) {?>
						<div id="map" class="panel panel-default"></div>
						<?php }?>

<?php } else { ?>

						<?php if (!empty($tsml_mapbox_key) || !empty($tsml_google_maps_key)) {?>
						<div id="map" style="display:none"></div>
						<?php }?>
	

<?php } ?>
					</div>
				</div>
			</div>
		</div>

		<?php if (is_active_sidebar('tsml_meeting_bottom')) {?>
			<div class="widgets meeting-widgets meeting-widgets-bottom" role="complementary">
				<?php dynamic_sidebar('tsml_meeting_bottom')?>
			</div>
		<?php }?>

	</div>
</div>
<?php
get_footer();
