<?php

/*******************************************************************************
 *
 *  Copyrights 2015 to Present - LinkMyDeals (TM) - ALL RIGHTS RESERVED
 *
 * All information contained herein is, and remains the property of LinkMyDeals,
 * which is a registered trademark of Sellergize Web Technology Services Pvt. Ltd.
 *
 * The intellectual and technical concepts & code contained herein are proprietary
 * to Sellergize Web Technology Services Pvt. Ltd., and are covered and protected
 * by copyright law. Reproduction of this material is strictly forbidden unless prior
 * written permission is obtained from Sellergize Web Technology Services Pvt. Ltd.
 * 
 * ******************************************************************************/
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

set_time_limit(0);

global $wpdb;
$wp_prefix = $wpdb->prefix;

$usage = array();

$msg = '';
$error = false;
$error_msg = '';

if(isset($_POST['submit_upload_feed'])) {

	if (!function_exists( 'wp_handle_upload' )) {
		require_once(ABSPATH.'wp-admin/includes/file.php');
	}
	$delimiter=',';
	$file_processed = false;
	$uploadedfile = $_FILES['feed'];
	$upload_overrides = array('test_form' => false,'mimes' => array('csv' => 'text/csv'));
	$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
	if ( !$movefile or isset($movefile['error']) ) {
		$error = true;
		$error_msg = 'Error during File Upload :'.$movefile['error'];
	} else {
		$sql = "INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'info','Uploading File')";
		$wpdb->query($sql);
		$feedFile = $movefile['file'];
		include 'saveFileToDb.php';
		$batchSize = '99999'; // process full file
		include 'processBatch.php';
	}
	
}

if(isset($_POST['submit_config'])) {

	if(isset($_POST['autopilot'])) { $autopilot = 'On'; } else { $autopilot = 'Off'; }
	$API_KEY = esc_sql($_POST['API_KEY']);
	$last_extract = esc_sql($_POST['last_extract']);

	if($autopilot=='On' and $API_KEY=='') {
		$error = true;
		$error_msg = 'API Key is required for Auto-Pilot';
	} else {

		if($last_extract=='' and $API_KEY!='') {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL,'http://feed.linkmydeals.com/getUsageDetails/?API_KEY='.$API_KEY);
			$result=curl_exec($ch);
			curl_close($ch);
			$usage = json_decode($result, true);
			$last_extract = $usage['last_extract'];
		}

		$sql = "REPLACE INTO ".$wp_prefix."lmd_config (name,value) VALUES
							('autopilot','$autopilot'),
							('API_KEY','$API_KEY'),
							('last_extract','$last_extract')";
		if($wpdb->query($sql) === false) {
			$error = true;
			$error_msg = $wpdb->last_error;
		}

		if($autopilot == 'On' and !wp_next_scheduled('pull_feed')) {
			wp_schedule_event( time(), 'hourly', 'pull_feed' );
			$msg = "<b>NOTE:</b> LinkMyDeals plugin makes use of WordPress scheduling. WordPress does NOT have a real cron scheduler. Instead, it triggers events only when someone visits your website, after the scheduled time has passed. If you currently do not have traffic on your WordPress site, you will need to load a few pages yourself to keep the offers updated.";
		}

		if($autopilot == 'Off' and wp_next_scheduled('pull_feed')) {
			wp_clear_scheduled_hook('pull_feed');
		}
		
	}
	
}

if(isset($_POST['submit_delete_offers'])) {
	include 'deleteLMDOffers.php';
}

if(isset($_POST['submit_pull_feed'])) {
	include 'pullFeed.php';
	if($error) {
		$error = true;
		$error_msg = 'Error saving feed to local database.';
	}
}
	
// GET CONFIG DETAILS
$sql = "SELECT
					(SELECT value FROM ".$wp_prefix."lmd_config WHERE name = 'autopilot') autopilot,
					(SELECT value FROM ".$wp_prefix."lmd_config WHERE name = 'API_KEY') API_KEY,
					(SELECT CONVERT_TZ(value,@@session.time_zone,'+05:30') FROM ".$wp_prefix."lmd_config WHERE name = 'last_extract') last_extract
				FROM dual";
$config = $wpdb->get_row($sql);

if(!empty($_POST['log_duration'])) { $log_duration = $_POST['log_duration']; } else { $log_duration = '1 HOUR'; }
if(!isset($_POST['log_debug'])) { $log_debug = "msg_type != 'debug'"; } else { $log_debug = "TRUE"; }
$sql_logs = "SELECT
								CONVERT_TZ(logtime,@@session.time_zone,'+05:30') logtime,
								msg_type,
								message,
								CASE
									WHEN msg_type = 'success' then 'green'
									WHEN msg_type = 'error' then 'red'
									WHEN msg_type = 'debug' then '#4a92bf'
								END as color
							FROM  ".$wp_prefix."lmd_logs
							WHERE logtime > NOW() - INTERVAL $log_duration
							AND $log_debug
							ORDER BY microtime";
$logs = $wpdb->get_results($sql_logs);

if(empty($usage)) { // do not run if already set during config update
	$result = wp_remote_get( 'http://feed.linkmydeals.com/getUsageDetails/?API_KEY='.$config->API_KEY);
	$usage = json_decode($result['body'], true);
}

?>

<div class="wrap" style="background:#F1F1F1;">

	<h2>LinkMyDeals</h2>
	<h4>Manage CouponXL Feed</h4>

	<script>
		function confirmReset() {
			var cnf = confirm("Are you sure you want to delete all offers from LinkMyDeals?");
			if (cnf == true) {
				document.getElementById("deleteOffersForm").submit();
			}
		}
	</script>
	
	<?php if($file_processed) { ?>
		<div id="file_message" class="updated notice notice-success is-dismissible below-h2">
			<p>
				<b>Process Complete.</b> 
				[Coupons Added : <span style="color:#0073aa;"><?php echo $count_new; ?></span> ] 
				[Coupons Updated : <span style="color:#0073aa;"><?php echo $count_updated; ?></span> ] 
				[Coupons Suspended : <span style="color:#0073aa;"><?php echo $count_suspended; ?></span> ]
			</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
	<?php } ?>

	<?php if($msg != '') { ?>
		<div id="message" class="updated notice notice-success is-dismissible below-h2">
			<p><?php echo $msg; ?></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
	<?php } ?>
	
	<?php if($error) { ?>
		<div id="message" class="error notice is-dismissible below-h2">
			<p style="color:#dc3232;"><?php echo $error_msg; ?></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		</div>
	<?php } ?>
	
	<hr/>

	<div class="row">

		<div class="col-md-4"> <!-- col 1 -->

			<div class="row"> <!-- row 1.1 -->
				<div class="col-md-12">
					<div class="panel panel-default">
						<div class="panel-heading">Settings</div>
						<div class="panel-body">
							
								<form name="autoPilot" role="form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
								
									<div class="form-group">
										<label for="autopilot">Auto-Pilot : </label>
										<input type="checkbox" name="autopilot" id="autopilot" data-on-color="success" data-on-label="Active" data-off-color="default" data-size="small" <?php if($config->autopilot == 'On') echo 'checked'; ?> />
										<script>
											jQuery("#autopilot").bootstrapSwitch();
										</script>
									</div>
									
									<div class="form-group">
										<label for="API_KEY">API Key :</label>
										<input type="text" name="API_KEY" id="API_KEY" class="form-control" value="<?php echo $config->API_KEY; ?>" />
									</div>
									
									<div class="form-group">
										<label for="last_extract">Last Extract (IST) :</label>
										<input type="text" name="last_extract" id="last_extract" class="form-control" data-inputmask='"mask": "y-m-d h:s:s"' placeholder="yyyy-mm-dd hh:mm:ss" value="<?php echo date('Y-m-d H:i:s',strtotime($config->last_extract.' Asia/Kolkata')); ?>" />
										<small><b>yyyy-mm-dd hh:mm:ss</b> (Ex. 2016-03-31 15:30:00)</small>
										<script>jQuery(":input").inputmask();</script>
									</div>
									
									<button class="btn btn-primary pull-right" type="submit" name="submit_config">Save</button>
									
								</form>
								
						</div>
					</div>
				</div>

			</div> <!-- row 1.1 end -->

			<div class="row"> <!-- row 1.2 -->
				<div class="col-md-12">
					<div class="panel panel-default">
					<div class="panel-heading">Reset</div>
					<div class="panel-body">					
						<form name="deleteOffersForm" id="deleteOffersForm" role="form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
							<input type="hidden" name="submit_delete_offers" value="true" />
							<button class="btn btn-danger" type="button" name="button_delete_offers" onclick="confirmReset();">Drop all LinkMyDeals Offers</button>
						</form>
					</div>
				</div>
			</div> <!-- row 1.2 end -->

			</div>
			
		</div> <!-- col1 end -->

		<div class="col-md-8"> <!-- col 2 -->

			<div class="row"> <!-- row 2.1 -->
				
				<?php if(!empty($config->API_KEY)) { ?>
					<div class="col-md-6">
						<div class="panel panel-default">
							<div class="panel-heading">API Usage</div>
							<div class="panel-body">
								<b>Daily Limit Used</b> : <?php echo $usage['limit_used'].' out of '.$usage['daily_limit']; ?><br/><br/>
								<?php
									if($config->autopilot == 'On') {
										$nextSchedule = date('g:i a',wp_next_scheduled('pull_feed') + 19800) . '  IST'; // 19800 seconds adjusts to IST
									} else {
										$nextSchedule = '(none)';
									}
								?>
								<b>Next Scheduled Extract</b> : <?php echo $nextSchedule; ?><br/><br/>
								<form name="pullFeedForm" role="form" method="post" enctype="multipart/form-data" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
									<button class="btn btn-primary pull-right" type="submit" name="submit_pull_feed">Pull Latest Feed</button>
								</form>
							</div>
						</div>
					</div>
				<?php } ?>

				<div class="col-md-6">
					<div class="panel panel-default">
					<div class="panel-heading">Manual Upload</div>
					<div class="panel-body">
						<form name="bulkUpload" class="form-inline" role="form" method="post" enctype="multipart/form-data" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
							<div class="form-group">
								<input type="file" name="feed" id="feed" />
							</div>
							<button class="btn btn-primary pull-right" type="submit" name="submit_upload_feed">Upload</button>
						</form>
					</div>
					<div class="panel-footer">
						<small><i>NOTE: If you are using a shared-server, your server may time-out in case of large files. We recommend you split such files into multiple files of ~500 coupons each. Advance plan users can make use of our <a href="http://couponfeed.linkmydeals.com" target="_blank">CSV Splitter tool</a>.</i></small>
					</div>
				</div>
				
			</div> <!-- row 2.1 end -->

			<div class="row">  <!-- row 2.2 -->
				<div class="col-md-12">
					<div class="panel panel-default">
						<div class="panel-heading">Logs</div>
						<div class="panel-body">
							<?php if(sizeof($logs) > 1) { ?>
								<table>
									<tr><th style="white-space: nowrap;">Time (IST)</th><th style="padding-left:20px;">Message</th></tr>
									<?php
										foreach($logs as $log) {
											if($log->message)
											echo '<tr style="font-size:0.85em;"><td >'.$log->logtime.'</td><td style="padding-left:20px;color:'.$log->color.';">'.$log->message.'</td></tr>';
										}
									?>
								</table>
							<?php } else { ?>
								<i>No Logs to display</i>
							<?php } ?>
						</div>
						<div class="panel-footer">
								<form name="refreshLogs" role="form" class="form-inline" method="post" enctype="multipart/form-data" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
									<div class="form-group" style="margin-right: 20px;">
										<label>Duration : </label>
										<select name="log_duration" class="form-control">
											<option value="1 HOUR" <?php if($log_duration == '1 HOUR') echo 'selected'; ?>>1 Hour</option>
											<option value="1 DAY" <?php if($log_duration == '1 DAY') echo 'selected'; ?>>Today</option>
											<option value="1 WEEK" <?php if($log_duration == '1 WEEK') echo 'selected'; ?>>Past Week</option>
										</select>
									</div>
									<div class="checkbox">
										<label>Show Debug Logs</label> <input name="log_debug" type="checkbox" <?php if(isset($_POST['log_debug'])) echo 'checked'; ?>>
									</div>
									<button class="btn btn-primary pull-right" type="submit" name="submit_fetch_logs">Refresh</button>
									<a href="<?php echo admin_url( 'admin-post.php?action=logs' ); ?>" class="btn btn-default pull-right" style="margin-right:10px;">Download Logs</a>
								</form>
						</div>
					</div>
				</div>
			</div> <!-- row 2.2 end -->

		</div> <!-- col 2 end -->
		
	</div>
	
	<div class="row">
		<div class="col-md-12">
			<div class="alert alert-warning alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<p style="font-size:smaller;"><b><u>Warning</u> : If you wish to discontinue your LinkMyDeals' subscription, we request you to please update your Website/App and replace all SmartLinks with your own Affiliate Links.</b></p>
				<p style="font-size:smaller;">SmartLinks is a paid feature. If you continue using SmartLinks even after your account has expired, LinkMyDeals reserves the right to redirect your SmartLinks via our own Affiliate IDs as a compensation for engaging our servers and other resources.</p>
			</div>
		</div>
	</div>
	
</div>
