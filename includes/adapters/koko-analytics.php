<?php
/**
 * Bundled adapter: Koko Analytics.
 *
 * The first traffic provider for the Overview chart. Koko keeps daily site
 * totals in {prefix}koko_analytics_site_stats (date, visitors, pageviews) —
 * a stable schema we read directly. When Koko is active, the Overview
 * "Activity" chart becomes a real Traffic chart with a Visitors stat card.
 *
 * Any analytics plugin can provide the same data through the
 * `minn_admin_traffic` filter. See docs/for-plugin-authors.md.
 *
 * @package minn-admin
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'minn_admin_traffic', function ( $traffic, $days ) {
	if ( null !== $traffic ) {
		return $traffic; // another provider answered first
	}
	if ( ! defined( 'KOKO_ANALYTICS_VERSION' ) && ! class_exists( 'KokoAnalytics\Plugin' ) ) {
		return $traffic;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'koko_analytics_site_stats';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
		return $traffic;
	}

	$days       = max( 1, (int) $days );
	$cur_start  = gmdate( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS );
	$prev_start = gmdate( 'Y-m-d', time() - ( 2 * $days - 1 ) * DAY_IN_SECONDS );

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT date, visitors, pageviews FROM {$table} WHERE date >= %s ORDER BY date ASC", // phpcs:ignore
		$prev_start
	) );

	$map  = array();
	$prev = 0;
	foreach ( (array) $rows as $row ) {
		if ( $row->date >= $cur_start ) {
			$map[ $row->date ] = array(
				'visitors'  => (int) $row->visitors,
				'pageviews' => (int) $row->pageviews,
			);
		} else {
			$prev += (int) $row->visitors;
		}
	}

	return array(
		'source'        => 'Koko Analytics',
		'days'          => $map,
		'prev_visitors' => $prev,
	);
}, 10, 2 );
