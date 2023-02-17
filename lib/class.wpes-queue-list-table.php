<?php
/**
 * Handles mail queue display.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

use WP_List_Table;

if ( ! class_exists( WP_List_Table::class ) ) {
	// WP_List_Table is not loaded automatically so we need to load it in our application.
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * The Queue class.
 */
class WPES_Queue_List_Table extends WP_List_Table {

	/**
	 * Holds Column Header data.
	 *
	 * @var array
	 */
	public $_column_headers; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- WordPress ...

	/**
	 * Holds table items.
	 *
	 * @var mixed[]
	 */
	public $items;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'E-mail',
				'plural'   => 'E-mails',
				'ajax'     => false,
			]
		);
	}

	/**
	 * Implementation of WP_List_Table::prepare_items().
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();
		usort( $data, [ $this, 'sort_data' ] );

		$per_page     = 25;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->_column_headers = [ $columns, $hidden, $sortable ];
		$this->items           = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'status'  => __( 'Status', 'wpes' ),
			'dt'      => __( 'E-mail Date', 'wpes' ),
			'to'      => __( 'Recipient', 'wpes' ),
			'subject' => __( 'Subject', 'wpes' ),
		];
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return array
	 */
	public function get_hidden_columns() {
		return [];
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'id'     => [ 'id', false ],
			'status' => [ 'status', false ],
		];
	}

	/**
	 * Get the table data
	 *
	 * @return array
	 */
	private function table_data() {
		global $wpdb;

		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpes_queue ORDER BY id DESC", ARRAY_A );
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param array  $item        Data.
	 * @param string $column_name Current column name.
	 *
	 * @return Mixed
	 */
	public function column_default( $item, $column_name ) {
		$stati = [];
		$value = $item[ $column_name ];

		switch ( $column_name ) {
			case 'status':
				return $stati[ (int) $value ];
			case 'id':
				return $value;
			case 'to':
			case 'message':
			case 'subject':
			case 'headers':
			case 'attachments':
				$value = maybe_unserialize( $value );
				if ( is_array( $value ) ) {
					$value = implode( '</br />', $value );
				}

				return $value;

			default:
				return $value;
		}
	}

	/**
	 * Implementation of WP_List_Table::column_*().
	 *
	 * @param array $item Data.
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$stati = [
			Queue::FRESH   => _x( 'Still to send', 'E-mail queue: this e-mail is Sent', 'wpes' ),
			Queue::SENDING => _x( 'Sending', 'E-mail queue: this e-mail is Sent OK', 'wpes' ),
			Queue::SENT    => _x( 'Sent', 'E-mail queue: this e-mail Failed sending', 'wpes' ),
			Queue::STALE   => _x( 'Stale', 'E-mail queue: this e-mail is Opened by the receiver', 'wpes' ),
			Queue::BLOCK   => _x( 'Blocked', 'E-mail queue: this e-mail is Opened by the receiver', 'wpes' ),
		];

		$value = $item['status'];

		return sprintf(
			'<input type="checkbox" name="item[]" value="%d" />%s',
			$item['id'],
			$stati[ (int) $value ]
		);

	}

	/**
	 * Implementation of WP_List_Table::get_bulk_actions().
	 *
	 * @return array
	 */
	public function get_bulk_actions() {

		return [
			'send-now' => __( 'Fresh? Send Now', 'wpes' ),
			'resend'   => __( 'Sent? Re-send', 'wpes' ),
			'retry'    => __( 'Stale? Retry', 'wpes' ),
			'release'  => __( 'Blocked? Release', 'wpes' ),
		];

	}

	/**
	 * Set the status of a queue item.
	 *
	 * @param int $mail_id The queued item ID.
	 * @param int $status  The new status.
	 */
	private static function set_status( $mail_id, $status ) {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}wpes_queue", [ 'status' => $status ], [ 'id' => $mail_id ] );
	}

	/**
	 * Implementation of WP_List_Table::process_bulk_action().
	 */
	public function process_bulk_action() {
		// security check!
		if ( ! empty( $_POST['wpes-nonce'] ) && ! wp_verify_nonce( $_POST['wpes-nonce'] ?? false, 'wp-email-essentials--queue' ) ) {
			wp_die( 'Nope! Security check failed!' );
		}

		$action = $this->current_action();

		switch ( $action ) {

			case 'delete':
				wp_safe_redirect( remove_query_arg( 'wpes-action' ) );
				exit;

			case 'release':
				foreach ( array_map( 'intval', $_POST['item'] ) as $id ) {
					if ( Queue::is_status( $id, Queue::BLOCK ) ) {
						self::set_status( $id, Queue::FRESH );
					}
				}
				wp_safe_redirect( remove_query_arg( 'wpes-action' ) );
				exit;

			case 'retry':
				foreach ( array_map( 'intval', $_POST['item'] ) as $id ) {
					if ( Queue::is_status( $id, Queue::STALE ) || Queue::is_status( $id, Queue::SENDING ) ) {
						self::set_status( $id, Queue::FRESH );
					}
				}
				wp_safe_redirect( remove_query_arg( 'wpes-action' ) );
				exit;

			case 'resend':
				foreach ( array_map( 'intval', $_POST['item'] ) as $id ) {
					if ( Queue::is_status( $id, Queue::SENT ) ) {
						self::set_status( $id, Queue::FRESH );
					}
				}
				wp_safe_redirect( remove_query_arg( 'wpes-action' ) );
				exit;

			case 'send-now':
				foreach ( array_map( 'intval', $_POST['item'] ) as $id ) {
					if ( Queue::is_status( $id, Queue::FRESH ) ) {
						Queue::send_now( $id );
					}
				}
				wp_safe_redirect( remove_query_arg( 'wpes-action' ) );
				exit;

		}

	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @param mixed $a Item to compare/sort.
	 * @param mixed $b Item to compare/sort.
	 *
	 * @return mixed
	 */
	private function sort_data( $a, $b ) {
		// Set defaults.
		$orderby = 'id';
		$order   = 'desc';

		// If orderby is set, use this as the sort column.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Not a form.
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = $_GET['orderby'];
		}

		// If order is set use this as the order.
		if ( ! empty( $_GET['order'] ) ) {
			$order = $_GET['order'];
		}
		// phpcs:enable ignore WordPress.Security.NonceVerification.Recommended -- Not a form.

		$result = strnatcasecmp( $a[ $orderby ], $b[ $orderby ] );

		if ( 'asc' === $order ) {
			return $result;
		}

		return - $result;
	}
}
