<?php
/**
 * Class for Report table
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SNDR_Report_List Class
 */
class SNDR_Report_List extends WP_List_Table {
	/**
	 * Constructor of class
	 */
	public function __construct() {
		global $status, $page;
		parent::__construct(
			array(
				'singular' => __( 'report', 'sender' ),
				'plural'   => __( 'reports', 'sender' ),
				'ajax'     => true,
			)
		);
	}

	/**
	 * Function to prepare data before display
	 */
	public function prepare_items() {
		global $wpdb, $sndr_url;

		$paged       = isset( $_GET['paged'] ) ? '&paged=' . absint( $_GET['paged'] ) : '';
		$mail_status = isset( $_REQUEST['mail_status'] ) ? '&mail_status=' . sanitize_text_field( wp_unslash( $_REQUEST['mail_status'] ) ) : '';
		$orderby     = isset( $_REQUEST['orderby'] ) ? '&orderby=' . sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : '';
		$order       = isset( $_REQUEST['order'] ) ? '&order=' . sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : '';

		$sndr_url = '?page=view_mail_send' . $paged . $mail_status . $orderby . $order;

		$columns               = $this->get_columns();
		$hidden                = get_hidden_columns( $this->screen );
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->report_list();
		$per_page              = $this->get_items_per_page( 'reports_per_page', 30 );
		$total_items           = $this->items_count();
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Function to show message if no reports found
	 */
	public function no_items() {
		?>
		<p style="color:red;"><?php esc_html_e( 'No messages sent', 'sender' ); ?></p>
		<?php
	}

	/**
	 * Get a list of columns.
	 *
	 * @return array list of columns and titles
	 */
	public function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'subject' => __( 'Subject', 'sender' ),
			'status'  => __( 'Status', 'sender' ),
			'date'    => __( 'Date', 'sender' ),
		);
		return $columns;
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @return array list of sortable columns
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'subject' => array( 'subject', false ),
			'status'  => array( 'status', false ),
			'date'    => array( 'date', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Function to add filters below and above mailout list
	 *
	 * @return array $status_links
	 */
	public function get_views() {
		global $wpdb;
		$status_links   = array();
		$all_count      = 0;
		$done_count     = 0;
		$progress_count = 0;
		$filters_count  = $wpdb->get_results(
			'SELECT COUNT(`mail_send_id`) AS `all`,
				( SELECT COUNT(`mail_send_id`) FROM ' . $wpdb->prefix . 'sndr_mail_send WHERE `mail_status`=1 ) AS `done`,
				( SELECT COUNT(`mail_send_id`) FROM ' . $wpdb->prefix . 'sndr_mail_send WHERE `mail_status`=0 ) AS `in_progress`
			FROM ' . $wpdb->prefix . 'sndr_mail_send'
		);
		foreach ( $filters_count as $count ) {
			$all_count      = empty( $count->all ) ? 0 : $count->all;
			$done_count     = empty( $count->done ) ? 0 : $count->done;
			$progress_count = empty( $count->in_progress ) ? 0 : $count->in_progress;
		}

		/* Get class for action links */
		$all_class      = ( ! isset( $_REQUEST['mail_status'] ) ) ? ' current' : '';
		$progress_class = ( isset( $_REQUEST['mail_status'] ) && 'progress_mailout' === $_REQUEST['mail_status'] ) ? ' current' : '';
		$done_class     = ( isset( $_REQUEST['mail_status'] ) && 'done_mailout' === $_REQUEST['mail_status'] ) ? ' current' : '';
		/* Get array with action links */
		$status_links['all']         = '<a class="sndr-filter' . $all_class . '" href="?page=view_mail_send">' . __( 'All', 'sender' ) . '<span class="sndr-count"> ( ' . $all_count . ' )</span></a>';
		$status_links['in_progress'] = '<a class="sndr-filter' . $progress_class . '" href="?page=view_mail_send&mail_status=in_progress">' . __( 'In Progress', 'sender' ) . '<span class="sndr-count"> ( ' . $progress_count . ' )</span></a>';
		$status_links['done']        = '<a class="sndr-filter' . $done_class . '" href="?page=view_mail_send&mail_status=done">' . __( 'Done', 'sender' ) . '<span class="sndr-count"> ( ' . $done_count . ' )</span></a>';
		return $status_links;
	}

	/**
	 * Function to add action links to drop down menu before and after reports list
	 *
	 * @return array of actions.
	 */
	public function get_bulk_actions() {
		$actions                   = array();
		$actions['delete_reports'] = __( 'Delete Campaigns', 'sender' );
		return $actions;
	}

	/**
	 * Fires when the default column output is displayed for a single row.
	 *
	 * @param array  $item        Item's array.
	 * @param string $column_name The custom column's name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
			case 'date':
			case 'subject':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Function to add column of checboxes
	 *
	 * @param array $item Item's array.
	 * @return string     With html-structure of <input type=['checkbox']>.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input id="cb_%1$s" type="checkbox" name="report_id[]" value="%2$s" />', $item['id'], $item['id'] );
	}

	/**
	 * Function to add action links to subject column depenting on status page
	 *
	 * @param array $item Item's array.
	 * @return string     With action links.
	 */
	public function column_subject( $item ) {
		global $sndr_url;
		$actions = array();

		$list_per_page = isset( $_REQUEST['list_per_page'] ) ? absint( $_REQUEST['list_per_page'] ) : 30;

		$actions['show_report'] = '<a class="sndr-show-users-list" href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $item['id'], 'sndr_show_report' . $item['id'] ) . '">' . __( 'Show Report', 'sender' ) . '</a>';
		if ( isset( $_REQUEST['action'] ) && 'show_report' === $_REQUEST['action'] && isset( $_REQUEST['report_id'] ) && absint( $_REQUEST['report_id'] ) === absint( $item['id'] ) ) {
			unset( $actions['show_report'] );
			$actions['hide_report'] = '<a href="' . wp_nonce_url( $sndr_url . '&action=hide_report&report_id=' . $item['id'], 'sndr_hide_report' . $item['id'] ) . '">' . __( 'Hide Report', 'sender' ) . '</a>';
		}
		$actions['delete_report'] = '<a href="' . wp_nonce_url( $sndr_url . '&action=delete_report&report_id=' . $item['id'], 'sndr_delete_report' . $item['id'] ) . '">' . __( 'Delete Report', 'sender' ) . '</a>';
		return sprintf( '%1$s %2$s', $item['subject'], $this->row_actions( $actions ) );
	}

	/**
	 * Display status of mailout
	 *
	 * @param array $item Current mailout data.
	 * @return string $column_content With action links.
	 */
	public function column_status( $item ) {
		global $wpdb;

		$report = $item['id'];

		/* check if table has PRO-column and adjust our query to exclude receivers from PRO mailous */
		$colum_exists         = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . "sndr_users` LIKE 'id_mailout';" );
		$additional_condition = ( 0 === $colum_exists ) ? '' : ' AND ( `id_mailout` = `id_mail` OR `id_mailout` = 0 )';

		$all_result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*)
				FROM `' . $wpdb->prefix . 'sndr_users`
				WHERE `id_mail` = %d' . $additional_condition . ' ;',
				$report
			)
		);

		$done = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*)
				FROM `' . $wpdb->prefix . 'sndr_users`
				WHERE `id_mail` = %d' . $additional_condition . ' AND `status`=1;',
				$report
			)
		);

		switch ( $item['status'] ) {
			case '0': /* mailout in progress */
				$column_content = '<span class="sndr-inprogress-label">' . __( 'In Progress', 'sender' ) . ' ( ' . $done . ' ' . __( 'of', 'sender' ) . ' ' . $all_result . ' )</span>';
				break;
			case '1': /* mailout was done */
				$column_content = '<span class="sndr-done-label">' . __( 'All Done', 'sender' ) . '</span>';
				break;
			default:
				$column_content = '';
				break;
		}
		return $column_content;
	}

	/**
	 * Function to add necessary class and id to table row
	 *
	 * @param array $report With report data.
	 */
	public function single_row( $report ) {
		if ( preg_match( '/done-status/', $report['status'] ) ) {
			$row_class = 'sndr-done-row';
		} elseif ( preg_match( '/inprogress-status/', $report['status'] ) ) {
			$row_class = 'sndr-inprogress-row';
		} else {
			$row_class = null;
		}
		echo '<tr id="report-' . esc_attr( $report['id'] ) . '" class="' . esc_attr( trim( $row_class ) ) . '">';
			$this->single_row_columns( $report );
		echo "</tr>\n";
	}

	/**
	 * Function to get report list
	 *
	 * @return array List of reports.
	 */
	public function report_list() {
		global $wpdb;
		$i            = 0;
		$reports_list = array();
		$per_page     = intval( get_user_option( 'reports_per_page' ) );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 30;
		}

		$start_row = ( isset( $_REQUEST['paged'] ) && 1 !== absint( $_REQUEST['paged'] ) ) ? $per_page * ( absint( $_REQUEST['paged'] ) - 1 ) : 0;

		if ( isset( $_REQUEST['orderby'] ) ) {
			switch ( $_REQUEST['orderby'] ) {
				case 'date':
					$order_by = 'date_create';
					break;
				case 'subject':
					$order_by = 'subject';
					break;
				case 'status':
					$order_by = 'mail_status';
					break;
				default:
					$order_by = 'mail_send_id';
					break;
			}
		} else {
			$order_by = 'mail_send_id';
		}
		$order     = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';
		$sql_query = 'SELECT * FROM `' . $wpdb->prefix . 'sndr_mail_send` ';
		if ( isset( $_REQUEST['s'] ) ) {
			$search_query = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
			$sql_query   .= $wpdb->prepare(
				'WHERE `subject`LIKE %s',
				'%' . $search_query . '%'
			);
		} else {
			if ( isset( $_REQUEST['mail_status'] ) ) {
				switch ( $_REQUEST['mail_status'] ) {
					case 'in_progress':
						$sql_query .= 'WHERE `mail_status` = 0';
						break;
					case 'done':
						$sql_query .= 'WHERE `mail_status` = 1';
						break;
					default:
						break;
				}
			}
		}
		$sql_query   .= ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT ' . $per_page . ' OFFSET ' . $start_row . ';';
		$reports_data = $wpdb->get_results( $sql_query, ARRAY_A );
		foreach ( $reports_data as $report ) {
			$subject                       = empty( $report['subject'] ) ? '( ' . __( 'No Subject', 'sender' ) . ' )' : $report['subject'];
			$date                          = new DateTime( $report['date_create'] );
			$reports_list[ $i ]            = array();
			$reports_list[ $i ]['id']      = $report['mail_send_id'];
			$reports_list[ $i ]['status']  = $report['mail_status'];
			$reports_list[ $i ]['subject'] = $subject . '<input type="hidden" name="report_' . $report['mail_send_id'] . '" value="' . $report['mail_send_id'] . '">' . $this->show_report( $report['mail_send_id'] );
			$reports_list[ $i ]['date']    = $date->format( 'd M Y H:i' );
			$i ++;
		}
		return $reports_list;
	}

	/**
	 * Function to get number of all reports
	 *
	 * @return sting Rreports number.
	 */
	public function items_count() {
		global $wpdb;
		$sql_query = 'SELECT COUNT(`mail_send_id`) FROM `' . $wpdb->prefix . 'sndr_mail_send` ';
		if ( isset( $_REQUEST['mail_status'] ) ) {
			switch ( $_REQUEST['mail_status'] ) {
				case 'in_progress':
					$sql_query .= ' WHERE `mail_status`=0;';
					break;
				case 'done':
					$sql_query .= ' WHERE `mail_status`=1;';
					break;
				default:
					break;
			}
		}
		$items_count = $wpdb->get_var( $sql_query );
		return $items_count;
	}

	/**
	 * Function to show list of subscribers
	 *
	 * @param string $mail_id Id of report.
	 * @return string         list of subscribers in table format.
	 */
	public function show_report( $mail_id ) {
		$list_table = null;
		if ( isset( $_REQUEST['action'] ) && 'show_report' === $_REQUEST['action'] && isset( $_REQUEST['report_id'] ) && $mail_id === $_REQUEST['report_id'] && check_admin_referer( 'sndr_show_report' . $mail_id ) ) {
			global $wpdb, $sndr_url;

			$report        = absint( $_REQUEST['report_id'] );
			$list_paged    = isset( $_GET['list_paged'] ) ? absint( $_GET['list_paged'] ) : 1;
			$list_order_by = isset( $_REQUEST['list_order_by'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['list_order_by'] ) ) : 'user_display_name';
			$list_per_page = isset( $_REQUEST['list_per_page'] ) ? absint( $_REQUEST['list_per_page'] ) : 30;

			if ( isset( $_REQUEST['list_order'] ) ) {
				$list_order      = ( 'ASC' === $_REQUEST['list_order'] ) ? 'DESC' : 'ASC';
				$link_list_order = sanitize_text_field( wp_unslash( $_REQUEST['list_order'] ) );
			} else {
				$list_order      = 'ASC';
				$link_list_order = 'ASC';
			}

			$start_row = ( 1 < $list_paged ) ? $list_per_page * ( $list_paged - 1 ) : 0;
			/* check if table has PRO-column and adjust our query to exclude receivers from PRO mailous */
			$colum_exists         = $wpdb->query( 'SHOW COLUMNS FROM `' . $wpdb->prefix . "sndr_users` LIKE 'id_mailout';" );
			$additional_condition = ( 0 === $colum_exists ) ? '' : ' AND ( `id_mailout`=`id_mail` OR `id_mailout`=0 )';

			$users_list = $wpdb->get_results(
				'SELECT DISTINCT `' . $wpdb->prefix . 'sndr_users`.`id_user`,`status`, `view`, `try`, `user_display_name`, `user_email`
				FROM `' . $wpdb->prefix . 'sndr_users`
				LEFT JOIN `' . $wpdb->prefix . 'sndr_mail_users_info`  ON `' . $wpdb->prefix . 'sndr_users`.`id_user`=`' . $wpdb->prefix . 'sndr_mail_users_info`.`id_user`
				WHERE `id_mail`=' . $report . $additional_condition . ' ORDER BY ' . $list_order_by . ' ' . $list_order . ' LIMIT ' . $list_per_page . ' OFFSET ' . $start_row . ';'
			);

			if ( ! empty( $users_list ) ) {
				$list_table =
					'<table class="report">
						<thead>
							<tr scope="row">
								<td colspan="4">' . $this->subscribers_pagination( $report, $list_per_page, $list_paged, $list_order_by, $link_list_order, 'top' ) . '</td>
							</tr>
							<tr>
								<td class="sndr-username"><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=user_display_name&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Username', 'sender' ) . '</a></td>
								<td><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=status&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Status', 'sender' ) . '</a></td>
								<td>' . __( 'Try', 'sender' ) . '</td>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td class="sndr-username"><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=user_display_name&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Username', 'sender' ) . '</a></td>
								<td><a href="' . wp_nonce_url( $sndr_url . '&list_per_page=' . $list_per_page . '&action=show_report&report_id=' . $report . '&list_paged=' . $list_paged . '&list_order_by=status&list_order=' . $list_order, 'sndr_show_report' . $report ) . '">' . __( 'Status', 'sender' ) . '</a></td>
								<td>' . __( 'Try', 'sender' ) . '</td>
							</tr>
							<tr scope="row">
								<td colspan="4">' . $this->subscribers_pagination( $report, $list_per_page, $list_paged, $list_order_by, $link_list_order, 'bottom' ) . '</td>
							</tr>
						</tfoot>
						<tbody>';
				foreach ( $users_list as $list ) {
					$user_name = empty( $list->user_display_name ) ? $list->user_email : $list->user_display_name;
					if ( empty( $user_name ) ) {
						$user_name = '<i>- ' . __( 'User was deleted', 'sender' ) . ' -</i>';
					}
					$list_table .= '<tr>
						<td class="sndr-username">' . $user_name . '</td>
						<td>';
					if ( '1' === $list->status ) {
						$list_table .= '<p style="color: green;">' . __( 'received', 'sender' ) . '</p>';
					} else {
						$list_table .= '<p style="color: #555;">' . __( 'in the queue', 'sender' ) . '</p>';
					}
					$list_table .= '</td>
						<td style="display: none;">';
					if ( '1' === $list->view ) {
						$list_table .= '<p style="color: green;">' . __( 'read', 'sender' ) . '</p>';
					} else {
						$list_table .= '<p style="color: #555;">' . __( 'not read', 'sender' ) . '</p>';
					}
					$list_table .= '</td>
						<td>' . $list->try . '</td>
					</tr>';
				}
				$list_table .=
						'</tbody>
					</table>
				<input type="hidden" name="sndr_url" value="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $report . '&list_order_by=status&list_order=' . $list_order, 'sndr_show_report' . $report ) . '" />';
			} else {
				$list_table = '<p style="color:red;">' . __( "The list of subscribers can't be found.", 'sender' ) . '</p>';
			}
		}
		return $list_table;
	}

	/**
	 * Function to get subscribers list pagination
	 *
	 * @param string $mail_id       Id of report.
	 * @param string $list_per_page Number of subscribers on each page.
	 * @param string $list_paged    Desired page number.
	 * @param string $list_order_by On what grounds will be sorting.
	 * @param string $list_order    "ASC" or "DESC.
	 * @param string $place         Ppostfix to fields name.
	 * @return string               Pagination elements.
	 */
	public function subscribers_pagination( $mail_id, $list_per_page, $list_paged, $list_order_by, $list_order, $place ) {
		global $wpdb, $sndr_url;
		$users_count = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT( `id_user` )
				FROM `' . $wpdb->prefix . 'sndr_users`
				WHERE `id_mail` = %d;',
				$mail_id
			)
		);

		/* Open block with pagination elements */
		$pagination_block =
			'<div class="sndr-pagination">
				<p class="total-users">' . __( 'Total Subscribers:', 'sender' ) . ' ' . $users_count . '</p>
				<div class="list-per-page">
					<input type="number" min="1" max="1000" class="sndr_set_list_per_page small-text hide-if-no-js" name="set_list_per_page_' . $place . '" value="' . $list_per_page . '" title="' . __( 'Number of Subscribers on Page', 'sender' ) . '" />
					<span class="total_pages"><span class="hide-if-js">' . $list_per_page . '</span>' . __( 'on page', 'sender' ) . '</span>
				</div>';

		/* If more than 1 page */
		if ( intval( $users_count ) > $list_per_page ) {
			/* Get number of all pages */
			$total_pages = ceil( $users_count / $list_per_page );

			$pagination_block .=
				'<div class="list-paged">';
			if ( 1 < $list_paged ) { /* if this is NOT first page of subscribers list */
				$previous_page_link = $list_paged - 1;
				$pagination_block  .=
					'<a class="first-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=1&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the First Page', 'sender' ) . '">&laquo;</a>
					<a class="previous-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=' . $previous_page_link . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the Previous Page', 'sender' ) . '">&lsaquo;</a>';
			} else { /* if this is first page of subscribers list */
				$pagination_block .=
					'<span class="first-page-disabled">&laquo;</span>
					<span class="previous-page-disabled">&lsaquo;</span>';
			}
			/* Field to choose number of subscribers on page and current page */
			$pagination_block .=
				'<input type="number" class="sndr_list_paged hide-if-no-js small-text" min="1" max="' . $total_pages . '" name="list_paged_' . $place . '" value="' . $list_paged . '" title="' . __( 'Current Page', 'sender' ) . '"/>
				<span class="total_pages"><span class="hide-if-js">' . $list_paged . '</span>' . __( 'of', 'sender' ) . ' ' . $total_pages . ' ' . __( 'pages', 'sender' ) . '</span>';

			if ( $list_paged < $total_pages ) { /* if this is NOT last page */
				$next_page_link    = $list_paged + 1;
				$pagination_block .=
					'<a class="next-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=' . $next_page_link . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the Next Page', 'sender' ) . '">&rsaquo;</a>
					<a class="last-page" href="' . wp_nonce_url( $sndr_url . '&action=show_report&report_id=' . $mail_id . '&list_per_page=' . $list_per_page . '&list_paged=' . $total_pages . '&list_order_by=' . $list_order_by . '&list_order=' . $list_order, 'sndr_show_report' . $mail_id ) . '" title="' . __( 'Go to the Last Page', 'sender' ) . '">&raquo;</a>';
			} else { /* if this is last page */
				$pagination_block .=
					'<span class="next-page-disabled">&rsaquo;</span>
					<span class="last-page-disabled">&raquo;</span>';
			}
			$pagination_block .= '</div><!-- .list-paged -->';
		}
		/* Close block with pagination elememnts */
		$pagination_block .= '</div><!-- .sndr-pagination -->';
		return $pagination_block;
	}
}
