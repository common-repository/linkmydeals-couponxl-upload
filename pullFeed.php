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
 
global $wpdb;
$wp_prefix = $wpdb->prefix;

$config = $wpdb->get_row("SELECT
																(SELECT value FROM ".$wp_prefix."lmd_config WHERE name = 'API_KEY') API_KEY,
																(SELECT value FROM ".$wp_prefix."lmd_config WHERE name = 'last_extract') last_extract
															FROM dual");
$feedFile = "http://feed.linkmydeals.com/getOffers/?API_KEY=".$config->API_KEY."&incremental=1&last_extract=".strtotime($config->last_extract.' Asia/Kolkata');

$sql = "INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'info','Pulling Feed using LinkMyDeals API')";
$wpdb->query($sql);

$sql = "INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'debug','$feedFile')";
$wpdb->query($sql);

$error = false;
$error_msg = '';
$wpdb->query( 'SET autocommit = 0;' );
include 'saveFileToDb.php';
if($totalCounter == 0) {
	// If the account is temporarily inactive, we do not get any offers in the file.
	// Not updating the last_extract time in such situations, prevents loss of data after re-activation.
	$wpdb->query( 'SET autocommit = 1;' );
	$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'error','No offers found in this extract')");
} elseif(!$error) {
	$wpdb->query("REPLACE INTO ".$wp_prefix."lmd_config (name,value) VALUES ('last_extract',CONVERT_TZ(NOW( ),@@session.time_zone ,'+05:30')) ");
	$wpdb->query( 'COMMIT;' );
	$wpdb->query( 'SET autocommit = 1;' );
	$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'info','Offer Feed saved to local database. Starting upload process') ");
	include 'processBatch.php';
} else {
	$wpdb->query( 'ROLLBACK' );
	$wpdb->query( 'SET autocommit = 1;' );
	$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES
										(".microtime(true).",'debug','".esc_sql($error_msg)."'),
										(".microtime(true).",'error','Error uploading feed to local database')");
}
	
?>
