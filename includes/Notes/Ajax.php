<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Notes;

defined( 'ABSPATH' ) || exit;

class Ajax {

	private Repository $repository;

	public function __construct( Repository $repository ) {
		$this->repository = $repository;
	}

	public function register(): void {
		add_action( 'wp_ajax_wccp_get_notes',   [ $this, 'handle_get' ] );
		add_action( 'wp_ajax_wccp_add_note',    [ $this, 'handle_add' ] );
		add_action( 'wp_ajax_wccp_update_note', [ $this, 'handle_update' ] );
		add_action( 'wp_ajax_wccp_delete_note', [ $this, 'handle_delete' ] );
	}

	private function allowed_tags(): array {
		return [
			'strong' => [],
			'b'      => [],
			'em'     => [],
			'i'      => [],
			'u'      => [],
			'ul'     => [],
			'ol'     => [],
			'li'     => [],
			'p'      => [],
			'br'     => [],
			'a'      => [ 'href' => true, 'target' => true, 'rel' => true ],
		];
	}

	public function handle_get(): void {
		check_ajax_referer( 'wccp_notes' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'customer-profile-page-for-woocommerce' ) ], 403 );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		$page        = isset( $_POST['page'] )        ? max( 1, absint( $_POST['page'] ) ) : 1;
		$per_page    = isset( $_POST['per_page'] )    ? min( 50, max( 1, absint( $_POST['per_page'] ) ) ) : 5;
		$search      = isset( $_POST['search'] )      ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$author_id   = isset( $_POST['author_id'] )   ? absint( $_POST['author_id'] ) : 0;

		if ( ! $customer_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data.', 'customer-profile-page-for-woocommerce' ) ], 400 );
		}

		$notes   = $this->repository->get_by_customer( $customer_id, $page, $per_page, $search, $author_id );
		$total   = $this->repository->count_by_customer( $customer_id, $search, $author_id );
		$authors = $this->repository->get_authors_by_customer( $customer_id );

		$date_format = get_option( 'date_format' );

		wp_send_json_success( [
			'notes'       => array_map( static function ( array $note ) use ( $date_format ): array {
				return array_merge( $note, [
					'avatar_url'     => (string) get_avatar_url( (int) $note['author_id'], [ 'size' => 32 ] ),
					'date_formatted' => date_i18n( $date_format, strtotime( $note['created_at'] ) ),
				] );
			}, $notes ),
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
			'authors'     => $authors,
		] );
	}

	public function handle_add(): void {
		check_ajax_referer( 'wccp_notes' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'customer-profile-page-for-woocommerce' ) ], 403 );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		$note        = isset( $_POST['note'] ) ? wp_kses( wp_unslash( $_POST['note'] ), $this->allowed_tags() ) : '';

		if ( ! $customer_id || ! $note ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data.', 'customer-profile-page-for-woocommerce' ) ], 400 );
		}

		$author_id = get_current_user_id();
		$id        = $this->repository->create( $customer_id, $author_id, $note );

		if ( ! $id ) {
			wp_send_json_error( [ 'message' => __( 'Could not save note.', 'customer-profile-page-for-woocommerce' ) ], 500 );
		}

		$author = get_userdata( $author_id );

		wp_send_json_success( [
			'id'             => $id,
			'note'           => $note,
			'author_id'      => $author_id,
			'author_name'    => $author ? $author->display_name : '',
			'avatar_url'     => get_avatar_url( $author_id, [ 'size' => 32 ] ),
			'date_formatted' => date_i18n( get_option( 'date_format' ) ),
		] );
	}

	public function handle_update(): void {
		check_ajax_referer( 'wccp_notes' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'customer-profile-page-for-woocommerce' ) ], 403 );
		}

		$id        = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;
		$note      = isset( $_POST['note'] ) ? wp_kses( wp_unslash( $_POST['note'] ), $this->allowed_tags() ) : '';
		$author_id = get_current_user_id();

		if ( ! $id || ! $note ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data.', 'customer-profile-page-for-woocommerce' ) ], 400 );
		}

		if ( ! $this->repository->update( $id, $author_id, $note ) ) {
			wp_send_json_error( [ 'message' => __( 'Could not update note.', 'customer-profile-page-for-woocommerce' ) ], 500 );
		}

		wp_send_json_success( [ 'id' => $id, 'note' => $note ] );
	}

	public function handle_delete(): void {
		check_ajax_referer( 'wccp_notes' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'customer-profile-page-for-woocommerce' ) ], 403 );
		}

		$id        = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;
		$author_id = get_current_user_id();

		if ( ! $id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data.', 'customer-profile-page-for-woocommerce' ) ], 400 );
		}

		if ( ! $this->repository->delete( $id, $author_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Could not delete note.', 'customer-profile-page-for-woocommerce' ) ], 500 );
		}

		wp_send_json_success( [ 'id' => $id ] );
	}
}
