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
 
$sql = "INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'info','Preparing to Save to DB')";
$wpdb->query($sql);

$topheader = NULL;
$totalCounter = 0;
$counter = 0;
$maxRowsPerQuery = 750;
$sep = '';
$delimiter = ',';
$found = false;
$sql_insert = $sql_insert_base = "INSERT INTO ".$wp_prefix."lmd_upload (lmd_id,status,title,description,code,categories,store_id,store,url,link,start_date,expiry_date,coupon_type,upload_date) VALUES ";

if (($handle = fopen($feedFile, 'r')) !== FALSE) { // $feedFile is set by API or File Upload
	
	while (($row = fgetcsv($handle, 10000, $delimiter)) !== FALSE) {

		if(!$topheader) {
				$topheader = $row;
		} else {
	
			$coupon = array_combine($topheader, $row);

			if(array_key_exists('store_id',$coupon))
				$store_id = $coupon['store_id'];
			else
				$store_id = 0;
			
			$sql_insert .= $sep."(".$coupon['lmd_id'].",
															'".$coupon['status']."',
															'".esc_sql($coupon['title'])."',
															'".esc_sql($coupon['description'])."',
															'".esc_sql($coupon['code'])."',
															'".esc_sql($coupon['categories'])."',
															".$store_id.",
															'".esc_sql($coupon['store'])."',
															'".esc_sql($coupon['url'])."',
															'".esc_sql($coupon['link'])."',
															'".$coupon['start_date']."',
															'".$coupon['expiry_date']."',
															'".$coupon['coupon_type']."',
															NOW())";
			$found = true;
			$totalCounter++;
			$counter++;
			$sep = ",";
			
			if($counter == $maxRowsPerQuery) {
				// Query is too large. Fire this much, and reset counters
				if($wpdb->query($sql_insert) === false) {
					$error = true;
					$error_msg = $wpdb->last_error . PHP_EOL . 'Query: ' . $sql_insert;
					$wpdb->print_error();
					$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'debug','".esc_sql($sql_insert)."')");
					$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'error','".esc_sql($error_msg)."')");
				}
				$found = false;
				$counter = 0;
				$sep = "";
				$sql_insert = $sql_insert_base;
			}
			
		}
		
	} // [ /while ]
	
	if($found) {
		if($wpdb->query($sql_insert) === false) {
			$error = true;
			$error_msg = $wpdb->last_error . PHP_EOL . 'Query: ' . $sql_insert;
			$wpdb->print_error();
			$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'debug','".esc_sql($sql_insert)."')");
			$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'error','".esc_sql($error_msg)."')");
		}
	}
	
}

?>
