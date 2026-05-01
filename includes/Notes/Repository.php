<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Notes;

defined( 'ABSPATH' ) || exit;

class Repository {

	private \wpdb $db;

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
	}

	public function table_name(): string {
		return $this->db->prefix . 'cpfw_customer_notes';
	}

	public function create_table(): void {
		$table           = $this->table_name();
		$charset_collate = $this->db->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			customer_id bigint(20) unsigned NOT NULL,
			author_id bigint(20) unsigned NOT NULL,
			note text NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY customer_id (customer_id),
			KEY author_id (author_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function create( int $customer_id, int $author_id, string $note ): int {
		$now    = current_time( 'mysql' );
		$result = $this->db->insert(
			$this->table_name(),
			[
				'customer_id' => $customer_id,
				'author_id'   => $author_id,
				'note'        => $note,
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			[ '%d', '%d', '%s', '%s', '%s' ]
		);

		return false !== $result ? (int) $this->db->insert_id : 0;
	}

	public function update( int $id, int $author_id, string $note ): bool {
		return (bool) $this->db->update(
			$this->table_name(),
			[
				'note'       => $note,
				'updated_at' => current_time( 'mysql' ),
			],
			[ 'id' => $id, 'author_id' => $author_id ],
			[ '%s', '%s' ],
			[ '%d', '%d' ]
		);
	}

	public function delete( int $id, int $author_id ): bool {
		return (bool) $this->db->delete(
			$this->table_name(),
			[ 'id' => $id, 'author_id' => $author_id ],
			[ '%d', '%d' ]
		);
	}

	/**
	 * @return array<int, array{id: string, customer_id: string, author_id: string, note: string, created_at: string, updated_at: string, author_name: string}>
	 */
	public function get_by_customer( int $customer_id, int $page = 1, int $per_page = 5, string $search = '', int $author_id = 0 ): array {
		$wheres = [ 'n.customer_id = %d' ];
		$args   = [ $customer_id ];

		if ( $search !== '' ) {
			$wheres[] = 'n.note LIKE %s';
			$args[]   = '%' . $this->db->esc_like( $search ) . '%';
		}

		if ( $author_id > 0 ) {
			$wheres[] = 'n.author_id = %d';
			$args[]   = $author_id;
		}

		$where  = implode( ' AND ', $wheres );
		$offset = ( $page - 1 ) * $per_page;
		$args[] = $per_page;
		$args[] = $offset;

		return $this->db->get_results(
			$this->db->prepare(
				"SELECT n.*, u.display_name AS author_name
				FROM {$this->table_name()} n
				LEFT JOIN {$this->db->users} u ON n.author_id = u.ID
				WHERE {$where}
				ORDER BY n.created_at DESC
				LIMIT %d OFFSET %d",
				...$args
			),
			ARRAY_A
		) ?: [];
	}

	public function count_by_customer( int $customer_id, string $search = '', int $author_id = 0 ): int {
		$wheres = [ 'customer_id = %d' ];
		$args   = [ $customer_id ];

		if ( $search !== '' ) {
			$wheres[] = 'note LIKE %s';
			$args[]   = '%' . $this->db->esc_like( $search ) . '%';
		}

		if ( $author_id > 0 ) {
			$wheres[] = 'author_id = %d';
			$args[]   = $author_id;
		}

		$where = implode( ' AND ', $wheres );

		return (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM {$this->table_name()} WHERE {$where}",
				...$args
			)
		);
	}

	/**
	 * @return array<int, array{author_id: string, author_name: string}>
	 */
	public function get_authors_by_customer( int $customer_id ): array {
		return $this->db->get_results(
			$this->db->prepare(
				"SELECT DISTINCT n.author_id, u.display_name AS author_name
				FROM {$this->table_name()} n
				INNER JOIN {$this->db->users} u ON n.author_id = u.ID
				WHERE n.customer_id = %d
				ORDER BY u.display_name ASC",
				$customer_id
			),
			ARRAY_A
		) ?: [];
	}
}
