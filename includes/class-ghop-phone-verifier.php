<?php
/**
 * Phone verification handler.
 *
 * @package Ghop
 * @since   {version}
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the phone verification.
 */
class Ghop_Phone_Verifier {

	/**
	 * Gets the phone number for the specified user.
	 *
	 * @since {version}

	 * @param int $user_id The user ID.
	 * @return string
	 */
	public static function get_user_phone( $user_id ) {
		return get_user_meta( $user_id, 'mobile', true );
	}

	/**
	 * Sets the phone number for the specified user.
	 *
	 * @since {version}

	 * @param int    $user_id The user ID.
	 * @param string $phone   The phone number.
	 */
	public static function set_user_phone( $user_id, $phone ) {
		update_user_meta( $user_id, 'mobile', $phone );
	}

	/**
	 * Validates the phone number.
	 *
	 * @since {version}
	 *
	 * @param string $phone   The phone number.
	 * @param int    $user_id Optional. The user ID. Default 0.
	 * @return WP_Error|true True on success. WP_Error on failure.
	 */
	public static function validate_phone( $phone, $user_id = 0 ) {
		if ( empty( $phone ) ) {
			return new WP_Error( 'phone_required', __( 'Phone number required', 'phone' ) );
		}

		if ( self::duplicated_phone( $phone, $user_id ) ) {
			return new WP_Error( 'phone_duplicated', __( 'The phone number is already taken', 'phone' ) );
		}

		return true;
	}

	/**
	 * Gets if the phone is already taken from another user.
	 *
	 * @since {version}
	 *
	 * @param string $phone   The phone number.
	 * @param int    $user_id Optional. The user ID. Default 0.
	 * @return bool
	 */
	protected static function duplicated_phone( $phone, $user_id = 0 ) {
		global $wpdb;

		$result = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count( * )
				FROM {$wpdb->usermeta}
				WHERE meta_key = 'mobile' AND meta_value = %s AND user_id != %d",
				array(
					$phone,
					$user_id,
				)
			)
		);

		return ( $result > 0 );
	}

	/**
	 * Generates a verification code.
	 *
	 * @since {version}
	 *
	 * @return int
	 */
	public static function generate_code() {
		return wp_rand( 111111, 999999 );
	}

	/**
	 * Generates a verification code for the specified user.
	 *
	 * @since {version}
	 *
	 * @param int $user_id The user ID.
	 * @return int
	 */
	public static function generate_code_for_user( $user_id ) {
		$code = self::generate_code();

		update_user_meta( $user_id, 'mobile_verify_code', $code );

		return $code;
	}

	/**
	 * Verifies the code for the specified user.
	 *
	 * @since {version}
	 *
	 * @param int $user_id The user ID.
	 * @param int $code    The code to verify.
	 * @return bool
	 */
	public static function is_valid( $user_id, $code ) {
		$user_code = get_user_meta( $user_id, 'mobile_verify_code', true );

		return ( $user_code && $user_code === $code );
	}

	/**
	 * Verifies the code for the specified user.
	 *
	 * @since {version}
	 *
	 * @param int $user_id The user ID.
	 * @param int $code    The code to verify.
	 * @return bool
	 */
	public static function verify_user( $user_id, $code ) {
		if ( ! self::is_valid( $user_id, $code ) ) {
			return false;
		}

		delete_post_meta( $user_id, 'mobile_verify_code' );
		update_post_meta( $user_id, 'mobile_verified', 1 );

		return true;
	}
}
