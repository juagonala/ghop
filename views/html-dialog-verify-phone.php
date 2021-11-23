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
 */
?>
<div class="ghop-dialog-content phone-verification">
	<h3 class="phone-verification-heading">Mobile verification</h3>

	<?php if ( 1 === $step ) : ?>
		<p>Enter your phone number</p>
	<?php elseif ( 2 === $step ) : ?>
		<p>We send you a verification code</p>
	<?php else : ?>
		<p>Your phone was verified successfully.</p>
	<?php endif; ?>

	<?php if ( 3 > $step ) : ?>
		<form id="ghop-verify-phone-form" action="" method="POST">
			<?php if ( 1 === $step ) : ?>
				<label class="screen-reader-text" for="phone">Phone</label>
				<input id="phone" class="wp-sms-input-mobile" type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" />
			<?php else : ?>
				<label class="screen-reader-text" for="phone">Code</label>
				<input id="code" type="text" name="code" value="" placeholder="123456" />
			<?php endif; ?>

			<input type="hidden" name="step" value="<?php echo esc_attr( $step ); ?>">
			<input class="button" type="submit" value="Submit" />
		</form>
	<?php endif; ?>
</div>
