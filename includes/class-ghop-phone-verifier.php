<?php
/**
 * Phone verification handler.
 *
 * @package Ghop
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the phone verification.
 */
class Ghop_Phone_Verifier {

	/**
	 * Gets the user by ID.
	 *
	 * @since 1.2.0
	 *
	 * @param int|WP_User $the_user User object or ID.
	 * @return WP_User|false
	 */
	protected static function get_user( $the_user ) {
		return ( $the_user instanceof WP_User ? $the_user : get_user_by( 'id', $the_user ) );
	}

	/**
	 * Gets the phone number for the specified user.
	 *
	 * @since 1.2.0

	 * @param int $user_id The user ID.
	 * @return string
	 */
	public static function get_user_phone( $user_id ) {
		return get_user_meta( $user_id, 'mobile', true );
	}

	/**
	 * Sets the phone number for the specified user.
	 *
	 * @since 1.2.0

	 * @param int    $user_id The user ID.
	 * @param string $phone   The phone number.
	 */
	public static function set_user_phone( $user_id, $phone ) {
		update_user_meta( $user_id, 'mobile', $phone );
	}

	/**
	 * Gets if the specific user needs to verify his phone.
	 *
	 * @since 1.2.0
	 *
	 * @param int|WP_User $the_user User object or ID.
	 * @return bool
	 */
	public static function needs_verification( $the_user ) {
		if ( ! defined( 'WP_SMS_PRO_URL' ) ) {
			return false;
		}

		$user = self::get_user( $the_user );

		if ( ! $user ) {
			return false;
		}

		return (
			in_array( 'subscriber', $user->roles, true ) &&
			! get_user_meta( $user->ID, 'mobile_verified', true )
		);
	}

	/**
	 * Validates the phone number.
	 *
	 * @since 1.2.0
	 *
	 * @param string $phone   The phone number.
	 * @param int    $user_id Optional. The user ID. Default 0.
	 * @return WP_Error|true True on success. WP_Error on failure.
	 */
	public static function validate_phone( $phone, $user_id = 0 ) {
		if ( 5 > strlen( $phone ) || 0 !== strpos( $phone, '+' ) ) {
			return new WP_Error( 'invalid_phone', __( 'Invalid phone number', 'ghop' ) );
		}

		if ( self::duplicated_phone( $phone, $user_id ) ) {
			return new WP_Error( 'phone_duplicated', __( 'The phone number is already taken', 'ghop' ) );
		}

		return true;
	}

	/**
	 * Parses a phone number.
	 *
	 * @since 1.2.0
	 *
	 * @param string $phone The phone number.
	 * @return string
	 */
	public static function parse_phone( $phone ) {
		return '+' . preg_replace( '/[^0-9.]/', '', $phone );
	}

	/**
	 * Gets if the phone is already taken from another user.
	 *
	 * @since 1.2.0
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
	 * @since 1.2.0
	 *
	 * @return int
	 */
	protected static function generate_code() {
		return wp_rand( 111111, 999999 );
	}

	/**
	 * Generates a verification code for the specified user.
	 *
	 * @since 1.2.0
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
	 * Sends an SMS with a code to verify the phone number.
	 *
	 * @since 1.2.0
	 *
	 * @param int    $user_id The user ID.
	 * @param string $phone   Optional. The phone number. Default false.
	 * @return bool
	 */
	public static function send_verification_code( $user_id, $phone = false ) {
		$user = self::get_user( $user_id );

		if ( ! $user ) {
			return false;
		}

		$phone = ( $phone ? $phone : self::get_user_phone( $user_id ) );

		if ( ! $phone ) {
			return false;
		}

		/* translators: %otp%: Verification code */
		$message = __( 'This is the code to verify your phone number: %otp%', 'ghop' );
		$options = get_option( 'wpsms_settings', array() );

		if ( ! empty( $options['mobile_verify_message'] ) ) {
			$message = $options['mobile_verify_message'];
		}

		$replacements = array(
			'%otp%'        => self::generate_code_for_user( $user_id ),
			'%user_name%'  => $user->user_login,
			'%first_name%' => $user->first_name,
			'%last_name%'  => $user->last_name,
			'%nickname%'   => $user->nickname,
		);

		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $message );

		return self::send_sms( $phone, $message );
	}

	/**
	 * Validates the code for the specified user.
	 *
	 * @since 1.2.0
	 *
	 * @param int $user_id The user ID.
	 * @param int $code    The code to verify.
	 * @return bool
	 */
	public static function validate_code( $user_id, $code ) {
		$user_code = get_user_meta( $user_id, 'mobile_verify_code', true );

		return ( $user_code && $user_code === $code );
	}

	/**
	 * Verifies the code for the specified user.
	 *
	 * @since 1.2.0
	 *
	 * @param int $user_id The user ID.
	 * @param int $code    The code to verify.
	 * @return bool
	 */
	public static function verify_user( $user_id, $code ) {
		if ( ! self::validate_code( $user_id, $code ) ) {
			return false;
		}

		delete_user_meta( $user_id, 'mobile_verify_code' );
		update_user_meta( $user_id, 'mobile_verified', 1 );

		return true;
	}

	/**
	 * Sends an SMS.
	 *
	 * @since 1.2.0
	 *
	 * @param string $phone   The phone number.
	 * @param string $message The message to send.
	 * @return bool
	 */
	protected static function send_sms( $phone, $message ) {
		if ( ! function_exists( 'wp_sms_initial_gateway' ) ) {
			return false;
		}

		$sms_gateway = wp_sms_initial_gateway();

		$sms_gateway->to  = array( $phone );
		$sms_gateway->msg = $message;

		$result = $sms_gateway->SendSMS();

		return ( ! is_wp_error( $result ) );
	}
}
