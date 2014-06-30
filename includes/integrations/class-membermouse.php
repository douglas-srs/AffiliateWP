<?php

class Affiliate_WP_Membermouse extends Affiliate_WP_Base {

	public function init() {

		$this->context = 'membermouse';

		add_action( 'mm_member_add',         array( $this, 'add_referral_on_free' ),      10    );
		add_action( 'mm_commission_initial', array( $this, 'add_referral' ),              10    );
		add_action( 'mm_refund_issued',      array( $this, 'revoke_referral_on_refund' ), 10    );

		add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ),  10, 2 );
	}

	public function add_referral_on_free( $member_data ) {

		if( $this->was_referred() ) {

			$membership = new MM_MembershipLevel( $member_data['membership_level'] );

			if( ! $membership->isFree() ) {
				return;
			}

			if( $this->get_affiliate_email() == $member_data['email'] ) {
				return; // Customers cannot refer themselves
			}

			// Just a fake order number so we can explode it and get the user ID later
			$reference = $member_data['member_id'] . '|0';

			$this->insert_pending_referral( 0, $reference, $member_data['membership_level_name'] );
		}

	}

	public function add_referral( $affiliate_data ) {

		if( $this->was_referred() ) {

			$user = get_userdata( $affiliate_data['member_id'] );

			if( $this->get_affiliate_email() == $user->user_email ) {
				return; // Customers cannot refer themselves
			}

			$products = json_decode( $affiliate_data['order_products'] );

			if ( ! is_array( $products ) ) $products = array();

			$description = implode( ', ', $products );

			$reference = $affiliate_data['member_id'] . '|' . $affiliate_data['order_number'];

			$this->insert_pending_referral( $affiliate_data['order_total'], $reference, $description );
			$this->complete_referral( $reference );

		}

	}

	public function revoke_referral_on_refund( $data ) {

		if( ! affiliate_wp()->settings->get( 'revoke_on_refund' ) ) {
			return;
		}

		$reference = $data['member_id'] . '|' . $data['order_number'] . '-' . $data['order_transaction_id'];

		$this->reject_referral( $reference );

	}

	public function reference_link( $reference = 0, $referral ) {

		if( empty( $referral->context ) || $this->context != $referral->context ) {

			return $reference;

		}

		$data = explode( '|', $reference );

		$url = admin_url( 'admin.php?page=manage_members&module=details_transaction_history&user_id=' . $data[0] );

		return '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
	}

}
new Affiliate_WP_Membermouse;