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

if ( current_user_can( 'manage_options' ) ) {
	 
	global $wpdb;
	$wp_prefix = $wpdb->prefix;
	
	$logs = $wpdb->get_results("SELECT CONVERT_TZ(logtime,@@session.time_zone,'+05:30') logtime, msg_type, message FROM  ".$wp_prefix."lmd_logs ORDER BY microtime");
	
	$filename = "lmd_".date("YmdHis").".log";
	$seperator = "\t";
	
	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=".$filename);
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
	header("Pragma: no-cache"); // HTTP 1.0
	header("Expires: 0"); // Proxies
	header("Content-Transfer-Encoding: UTF-8");
	
	$fp = fopen("php://output", "w");
	
	foreach($logs as $log) {
		fputcsv($fp, array($log->logtime, $log->msg_type, $log->message), $seperator);
	}
	
	fclose($fp);
	
} else {
	echo 'You do not have the rights to download these logs';
}

?>
