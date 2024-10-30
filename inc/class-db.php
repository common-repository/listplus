<?php

namespace ListPlus;

class Database {
	public function __constuct() {

	}

	protected function get_schema() {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$collate = $wpdb->get_charset_collate();

		$schema = "CREATE TABLE `{$prefix}lp_claim_entries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `status` varchar(20)   NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `email` varchar(255)   NOT NULL,
  `name` varchar(255)   NOT NULL,
  `ip` varchar(100)   NOT NULL,
  `content` text   NOT NULL,
  `meta` text   NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) {$collate};
CREATE TABLE `{$prefix}lp_enquiries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `status` varchar(20)   NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `email` varchar(255)   NOT NULL,
  `name` varchar(255)   NOT NULL,
  `ip` varchar(100)   NOT NULL,
  `title` varchar(300)   NOT NULL,
  `content` text   NOT NULL,
  `meta` text   NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) {$collate};
CREATE TABLE `{$prefix}lp_item_meta` (
  `mid` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NULL,
  `order_id` bigint(20) unsigned NULL,
  `product_id` bigint(20) unsigned NULL,
  `order_item_id` bigint(20) unsigned  NULL,
  `listing_type` varchar(100)  NULL,
  `type_id` bigint(20)  NULL,
  `item_id` varchar(100)  NULL,
  `expired` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `start_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `end_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `verified` varchar(5) NULL,
  `claimed` varchar(5) NULL,
  `is_featured` varchar(5) NULL,
  `price` float NOT NULL,
  `price_range` int(3) DEFAULT NULL,
  `region_id` bigint(20) unsigned  NULL,
  `city` varchar(50) NULL,
  `zipcode` varchar(20)  NULL,
  `state` varchar(50)  NULL,
  `country_code` varchar(5)  NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `address` varchar(255) NULL,
  `address_2` varchar(255)  NULL,
  `phone` varchar(30) NULL,
  `email` varchar(200)  NULL,
  `enquiry_status` varchar(10)  NULL,
  `count_review` bigint(20)  NULL,
  `rating_score` float NULL,
  `rating_meta` tinytext  NULL,
  `count_report` bigint(20) NULL,
  PRIMARY KEY (`mid`),
  UNIQUE KEY `mid` (`mid`),
  UNIQUE KEY `post_id` (`post_id`)
) {$collate};
CREATE TABLE `{$prefix}lp_prop_meta` (
  `prop_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `prop_type` varchar(50)   NOT NULL,
  `square_feet` float NOT NULL,
  `lot_square_feet` float NOT NULL,
  `beds` int(5) NOT NULL,
  `baths` int(5) NOT NULL,
  `area` int(5) NOT NULL,
  `pools` int(5) NOT NULL,
  `half_baths` int(5) NOT NULL,
  `garages` int(5) NOT NULL,
  `parking` varchar(5)   NOT NULL,
  `year_built` int(5) NOT NULL,
  PRIMARY KEY (`prop_id`)
) {$collate};
CREATE TABLE `{$prefix}lp_reports` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `status` varchar(50)   NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `name` varchar(50)   NOT NULL,
  `email` varchar(255)   NOT NULL,
  `reason` text   NOT NULL,
  `ip` varchar(100)   NOT NULL,
  `meta` tinytext   NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) {$collate};
CREATE TABLE `{$prefix}lp_reviews` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `status` varchar(20)   NOT NULL,
  `highlight` tinyint(1) NOT NULL DEFAULT '0',
  `rating` decimal(3,2) NOT NULL,
  `weight` decimal(3,2) NOT NULL DEFAULT '1.00',
  `max` smallint(3) NOT NULL DEFAULT '5',
  `email` varchar(255)   NOT NULL,
  `name` varchar(255)   NOT NULL,
  `title` varchar(255)   NOT NULL,
  `content` text   NOT NULL,
  `photos` tinytext   NOT NULL,
  `ip` varchar(100)   NOT NULL,
  `meta` tinytext   NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) {$collate};
CREATE TABLE `{$prefix}lp_scheduled_tasks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `hook` varchar(255)   NOT NULL,
  `status` varchar(10)   NOT NULL,
  `recurrence` int(15) NOT NULL,
  `args` text   NOT NULL,
  `date_scheduled` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `date_started` datetime NOT NULL,
  `date_completed` datetime NOT NULL,
  PRIMARY KEY (`id`)
) {$collate};
CREATE TABLE `{$prefix}lp_tax_relationships` (
  `term_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `taxonomy` varchar(100)   NOT NULL,
  `custom_value` varchar(255)   NOT NULL,
  `custom_order` int(6) NOT NULL,
  PRIMARY KEY (`term_id`,`post_id`)
)  {$collate};
";

return $schema;

	}

	public function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $this->get_schema() );

		$db_version = '1.0';
		add_option( 'listplus_db_version', $db_version );
	}

	public function uninstall() {

	}
}
