<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;
$wp_prefix = $wpdb->prefix;

// DROP TABLES IF ALREADY PRESENT
$sql = "DROP TABLE IF EXISTS ".$wp_prefix."lmd_logs, ".$wp_prefix."lmd_config, ".$wp_prefix."lmd_upload";
$wpdb->query($sql);

// CREATE LOG TABLE
$sql = "CREATE TABLE IF NOT EXISTS ".$wp_prefix."lmd_logs (
					logtime timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					microtime DECIMAL(20,6) NOT NULL DEFAULT '0',
					msg_type VARCHAR( 10 ) NOT NULL,
					message text NOT NULL)";
$wpdb->query($sql);

// CREATE CONFIG TABLE
$sql = "CREATE TABLE IF NOT EXISTS ".$wp_prefix."lmd_config (
					name varchar(50) NOT NULL,
					value text NOT NULL,
					UNIQUE  (name))";
$wpdb->query($sql);

// CREATE UPLOAD TABLE
$sql = "CREATE TABLE IF NOT EXISTS ".$wp_prefix."lmd_upload (
					lmd_id int(11) NOT NULL,
					status varchar(15) NOT NULL,
					title text NOT NULL,
					description text NOT NULL,
					code varchar(50) NOT NULL,
					categories text NOT NULL,
					store_id int(11) NOT NULL,
					store varchar(50) NOT NULL,
  					url text NOT NULL,
					link text NOT NULL,
  					start_date timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  					expiry_date timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  					coupon_type varchar(10) NOT NULL,
  					upload_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)";
$wpdb->query($sql);

?>
