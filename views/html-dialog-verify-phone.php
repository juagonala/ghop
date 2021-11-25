<?php
/**
 * View: Verify phone dialog.
 *
 * @package Ghop/Views
 * @since   {version}
 */

defined( 'ABSPATH' ) || exit;

/**
 * Template vars.
 *
 * @var int    $step  The dialog step.
 * @var string $phone Customer phone.
 * @var string $error Error message.
 */
?>
<div class="ghop-dialog-content phone-verification">
	<h3 class="phone-verification-heading"><?php esc_html_e( 'Verify your phone', 'ghop' ); ?></h3>

	<?php if ( 1 === $step ) : ?>
		<p><?php esc_html_e( 'Enter your phone number', 'ghop' ); ?></p>
	<?php elseif ( 2 === $step ) : ?>
		<p><?php esc_html_e( 'We sent you a SMS with a code', 'ghop' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'Your phone was verified successfully.', 'ghop' ); ?></p>
		<p><button class="phone-verified-button"><?php esc_html_e( 'Accept', 'ghop' ); ?></button></p>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( 3 > $step ) : ?>
		<form id="ghop-verify-phone-form" method="POST" action="">
			<?php if ( 1 === $step ) : ?>
				<label class="screen-reader-text" for="phone"><?php esc_html_e( 'Phone', 'ghop' ); ?></label>
				<input id="phone" class="wp-sms-input-mobile" type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" required="required" />
			<?php else : ?>
				<label class="screen-reader-text" for="code"><?php esc_html_e( 'Code', 'ghop' ); ?></label>
				<input id="code" type="text" name="code" value="" placeholder="123456" required="required" />
			<?php endif; ?>

			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>">
			<input class="button" type="submit" value="<?php esc_attr_e( 'Submit', 'ghop' ); ?>" />
		</form>
	<?php endif; ?>
</div>
