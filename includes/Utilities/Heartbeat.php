<?php

namespace WooCommerce\Facebook\Utilities;

use WC_Queue_Interface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Heartbeat
 *
 * Responsible for scheduling cron heartbeat hooks. Currently there is a single hourly heartbeat:
 * - `facebook_for_woocommerce_heartbeat_hourly`
 *
 * @since 2.6.0
 */
class Heartbeat {

	/**
	 * Hook name for hourly heartbeat.
	 */
	const EVERY_5_MINUTES = 'facebook_for_woocommerce_5_minute_heartbeat';

	/**
	 * Hook name for hourly heartbeat.
	 */
	const HOURLY = 'facebook_for_woocommerce_hourly_heartbeat';

	/**
	 * Hook name for daily heartbeat.
	 */
	const DAILY = 'facebook_for_woocommerce_daily_heartbeat';

	/**
	 * @var string
	 */
	protected $hourly_cron_name = 'facebook_for_woocommerce_hourly_heartbeat_cron';

	/**
	 * @var string
	 */
	protected $daily_cron_name = 'facebook_for_woocommerce_daily_heartbeat_cron';

	/**
	 * @var string
	 */
	protected $every_5_minute_cron_name = 'facebook_for_woocommerce_5_minute_heartbeat_cron';

	/**
	 * @var WC_Queue_Interface
	 */
	protected $queue;

	/**
	 * Heartbeat constructor.
	 *
	 * @param WC_Queue_Interface $queue WC Action Scheduler proxy.
	 */
	public function __construct( WC_Queue_Interface $queue ) {
		$this->queue = $queue;
	}

	/**
	 * Add hooks.
	 */
	public function init() {
		add_filter( 'cron_schedules', array( $this, 'five_minutes_cron_schedules' ) );
		add_action( 'init', array( $this, 'schedule_cron_events' ) );
		add_action( $this->hourly_cron_name, array( $this, 'schedule_hourly_action' ) );
		add_action( $this->daily_cron_name, array( $this, 'schedule_daily_action' ) );
		add_action( $this->every_5_minute_cron_name, array( $this, 'schedule_every_5_minute_action' ) );
	}

	/**
	 * Schedule heartbeat cron events.
	 *
	 * WP Cron events are stored in an auto-loaded option so the performance impact is much lower than checking and
	 * scheduling an Action Scheduler action.
	 */
	public function schedule_cron_events() {
		if ( ! wp_next_scheduled( $this->hourly_cron_name ) ) {
			wp_schedule_event( time(), 'hourly', $this->hourly_cron_name );
		}
		if ( ! wp_next_scheduled( $this->daily_cron_name ) ) {
			wp_schedule_event( time(), 'daily', $this->daily_cron_name );
		}
		if ( ! wp_next_scheduled( $this->every_5_minute_cron_name ) ) {
			wp_schedule_event( time(), 'five_minutes', $this->every_5_minute_cron_name );
		}
	}

	/**
	 * Function that add a defination of interval for cron job
	 *
	 * @param string $schedules pluin system data
	 *
	 * @since 3.5.0
	 *
	 * @internal
	 */
	public function five_minutes_cron_schedules( $schedules ) {
		$schedules['five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'Five Minutes', 'facebook-for-woocommerce' ),
		);

		return $schedules;
	}

	/**
	 * Schedule the hourly heartbeat action to run immediately.
	 *
	 * Scheduling an action frees up WP Cron to process more jobs in the current request. Action Scheduler has greater
	 * throughput so running our checks there is better.
	 */
	public function schedule_hourly_action() {
		$this->queue->add( self::HOURLY );
	}

	/**
	 * Schedule the daily heartbeat action to run immediately.
	 */
	public function schedule_daily_action() {
		$this->queue->add( self::DAILY );
	}

	/**
	 * Schedule the every 5 minute heartbeat action to run immediately.
	 */
	public function schedule_every_5_minute_action() {
		$this->queue->add( self::EVERY_5_MINUTES );
	}
}
