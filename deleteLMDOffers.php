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

$count_new = $count_suspended = $count_updated = 0;

wp_defer_term_counting( true );
$wpdb->query( 'SET autocommit = 0;' );

$coupons = $wpdb->get_results("SELECT post_id FROM  ".$wp_prefix."postmeta WHERE meta_key = 'lmd_id'");

foreach($coupons as $coupon) {	
		$post_id = $coupon->post_id;
		wp_delete_post($post_id,true);
		$count_suspended = $count_suspended + 1;
}
	
$wpdb->query("DELETE FROM ".$wp_prefix."lmd_upload");

wp_defer_term_counting( false );
$wpdb->query( 'COMMIT;' );
$wpdb->query( 'SET autocommit = 1;' );

$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'success','All LinkMyDeals Offers have been dropped.')");

$file_processed = true;

?>
