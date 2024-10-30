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

if(empty($batchSize)) {
	$batchSize = 750;
}

$count_new = $count_suspended = $count_updated = 0;

wp_defer_term_counting( true );
$wpdb->query( 'SET autocommit = 0;' );

$categories=array();
$categoryTerms = get_terms('offer_cat');
foreach($categoryTerms as $term) {
	$categories[$term->name] = $term->slug;
}

$stores=array();
$sql_stores = "SELECT ID,post_title FROM ".$wp_prefix."posts WHERE post_type = 'store' ";
$result_stores = $wpdb->get_results($sql_stores);
foreach($result_stores as $str) {
	$stores[$str->ID] = $str->post_title;
}

$coupons = $wpdb->get_results("SELECT * FROM  ".$wp_prefix."lmd_upload ORDER BY upload_date LIMIT 0,".$batchSize);

foreach($coupons as $coupon) {
	
	if($coupon->status == 'new') {

		$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'debug','Adding New Coupon (".$coupon->lmd_id.")')");
		
		$post_data = array(
			'ID'             => '',
			'post_title'     => $coupon->title,
			'post_content'   => $coupon->description,
			'post_status'    => 'publish',
			'post_type'      => 'offer',
			'post_author'    => get_current_user_id()
		);
		
		$post_id = wp_insert_post($post_data,$wp_error);
		
		if (strpos($coupon->categories, ',') !== FALSE) {
			$cat_names = explode(',',$coupon->categories);
			foreach($cat_names as $cat) {
				wp_set_object_terms($post_id, $cat, 'offer_cat', true);
			}
		} else {
			wp_set_object_terms($post_id, $coupon->categories, 'offer_cat', true);
		}
		
		if($coupon->store_id!=0 and $coupon->store_id!='') {
			update_post_meta($post_id, 'offer_store', $coupon->store_id);
		} elseif(array_search($coupon->store,$stores)) {
			update_post_meta($post_id, 'offer_store', array_search($coupon->store,$stores));
		} else {
			$store_data = array(
				'ID'             => '',
				'post_title'     => $coupon->store,
				'post_status'    => 'publish',
				'post_type'      => 'store',
				'post_author'    => get_current_user_id()
			);
			$store_id = wp_insert_post($store_data,$wp_error);
			$stores[$store_id] = $coupon->store;
			update_post_meta($post_id, 'offer_store', $store_id);
		}
		
		update_post_meta($post_id, 'lmd_id', $coupon->lmd_id);
		update_post_meta($post_id, 'coupon_code', $coupon->code);
		update_post_meta($post_id, 'coupon_url', $coupon->url);
		update_post_meta($post_id, 'coupon_link', $coupon->link);
		update_post_meta($post_id, 'coupon_sale', $coupon->link);
		update_post_meta($post_id, 'offer_start', strtotime($coupon->start_date));
		update_post_meta($post_id, 'offer_expire', strtotime($coupon->expiry_date));
		update_post_meta($post_id, 'coupon_type', $coupon->coupon_type);
		update_post_meta($post_id, 'offer_clicks', '0');
		update_post_meta($post_id, 'offer_views', '1');
		update_post_meta($post_id, 'offer_in_slider', 'yes');
		update_post_meta($post_id, 'offer_initial_payment', 'paid');
		update_post_meta($post_id, 'deal_type', 'shared');
		update_post_meta($post_id, 'deal_status', 'has_items');
		update_post_meta($post_id, 'offer_type', 'coupon');
		update_post_meta($post_id, 'offer_views', '1');
		
		$wpdb->query("DELETE FROM ".$wp_prefix."lmd_upload WHERE lmd_id = ".$coupon->lmd_id);
		$count_new = $count_new + 1;
		
	} elseif($coupon->status == 'updated') {
		
		$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'debug','Updating Coupon (".$coupon->lmd_id.")')");

		$lmd_id = $coupon->lmd_id;
		$sql_id = "SELECT post_id FROM ".$wp_prefix."postmeta WHERE meta_key = 'lmd_id' AND meta_value = '$lmd_id' LIMIT 0,1";
		$post_id = $wpdb->get_var($sql_id);
		
		$post_data = array(
			'ID'             => $post_id,
			'post_title'     => $coupon->title,
			'post_content'   => $coupon->description,
			'post_status'    => 'publish'
		);
				
		wp_update_post($post_data);
		
		if (strpos($coupon->categories, ',') !== FALSE) {
			$cat_names = explode(',',$coupon->categories);
			$append = false;
			foreach($cat_names as $cat) {
				wp_set_object_terms($post_id, $cat, 'offer_cat', $append);
				$append = true;
			}
		} else {
			wp_set_object_terms($post_id, $coupon->categories, 'offer_cat', false);
		}
		
		if($coupon->store_id!=0 and $coupon->store_id!='') {
			update_post_meta($post_id, 'offer_store', $coupon->store_id);
		} elseif(array_search($coupon->store,$stores)) {
			update_post_meta($post_id, 'offer_store', array_search($coupon->store,$stores));
		} else {
			$store_data = array(
				'ID'             => '',
				'post_title'     => $coupon->store,
				'post_status'    => 'publish',
				'post_type'      => 'store',
				'post_author'    => get_current_user_id()
			);
			$store_id = wp_insert_post($store_data,$wp_error);
			$stores[$store_id] = $coupon->store;
			update_post_meta($post_id, 'offer_store', $store_id);
		}
		
		update_post_meta($post_id, 'coupon_code', $coupon->code);
		update_post_meta($post_id, 'coupon_url', $coupon->url);
		update_post_meta($post_id, 'coupon_link', $coupon->link);
		update_post_meta($post_id, 'coupon_sale', $coupon->link);
		update_post_meta($post_id, 'offer_start', strtotime($coupon->start_date));
		update_post_meta($post_id, 'offer_expire', strtotime($coupon->expiry_date));
		update_post_meta($post_id, 'coupon_type', $coupon->coupon_type);
		
		$wpdb->query("DELETE FROM ".$wp_prefix."lmd_upload WHERE lmd_id = ".$coupon->lmd_id);
		$count_updated = $count_updated + 1;
		
	} elseif($coupon->status == 'suspended') {
		
		$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'debug','Suspending Coupon (".$coupon->lmd_id.")')");

		$lmd_id = $coupon->lmd_id;
		$sql_id = "SELECT post_id FROM ".$wp_prefix."postmeta WHERE meta_key = 'lmd_id' AND meta_value = '$lmd_id' LIMIT 0,1";
		$post_id = $wpdb->get_var($sql_id);
		
		wp_delete_post($post_id,true);

		$wpdb->query("DELETE FROM ".$wp_prefix."lmd_upload WHERE lmd_id = ".$coupon->lmd_id);
		$count_suspended = $count_suspended + 1;
		
	}
	
}

$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'info','Processed Offers - $count_new New , $count_updated Updated , $count_suspended Suspended.')");
	
wp_defer_term_counting( false );
$wpdb->query( 'COMMIT;' );
$wpdb->query( 'SET autocommit = 1;' );
$file_processed = true;

$remainingCoupons = $wpdb->get_var("SELECT count(1) FROM ".$wp_prefix."lmd_upload");
if($remainingCoupons > 0) {
	$loop++;
	wp_schedule_single_event( time() , 'process_batch' , array($loop) ); // process next loop
} else {
	$wpdb->query("DELETE FROM ".$wp_prefix."lmd_logs WHERE logtime < CURDATE() - INTERVAL 30 DAY");
	$wpdb->query("INSERT INTO ".$wp_prefix."lmd_logs (microtime,msg_type,message) VALUES (".microtime(true).",'success','All offers processed successfully.')");
}

?>
