-- Adminer 4.2.6-dev MySQL dump

CREATE TABLE `wp_lp_claim_entries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `meta` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `wp_lp_enquiries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(300) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `meta` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `wp_lp_item_meta` (
  `mid` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `listing_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `type_id` bigint(20) NOT NULL,
  `item_id` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `expired` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `start_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `end_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `verified` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `claimed` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `price` float NOT NULL,
  `price_range` int(3) DEFAULT NULL,
  `region_id` bigint(20) unsigned NOT NULL,
  `city` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `zipcode` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `state` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `country_code` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address_2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `enquiry_status` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `count_review` bigint(20) NOT NULL,
  `rating_score` float NOT NULL,
  `rating_meta` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `count_report` bigint(20) NOT NULL,
  PRIMARY KEY (`mid`),
  UNIQUE KEY `mid` (`mid`),
  UNIQUE KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `wp_lp_prop_meta` (
  `prop_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `prop_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `square_feet` float NOT NULL,
  `lot_square_feet` float NOT NULL,
  `beds` int(5) NOT NULL,
  `baths` int(5) NOT NULL,
  `area` int(5) NOT NULL,
  `pools` int(5) NOT NULL,
  `half_baths` int(5) NOT NULL,
  `garages` int(5) NOT NULL,
  `parking` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `year_built` int(5) NOT NULL,
  PRIMARY KEY (`prop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `wp_lp_reports` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `status` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `reason` text COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `meta` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `wp_lp_reviews` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `status` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `highlight` tinyint(1) NOT NULL DEFAULT '0',
  `rating` decimal(3,2) NOT NULL,
  `weight` decimal(3,2) NOT NULL DEFAULT '1.00',
  `max` smallint(3) NOT NULL DEFAULT '5',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `photos` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `meta` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `wp_lp_scheduled_tasks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `hook` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `recurrence` int(15) NOT NULL, 
  `args` text COLLATE utf8_unicode_ci NOT NULL,
  `date_scheduled` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `date_started` datetime NOT NULL,
  `date_completed` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE `wp_lp_tax_relationships` (
  `term_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `taxonomy` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `custom_value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `custom_order` int(6) NOT NULL,
  PRIMARY KEY (`term_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


