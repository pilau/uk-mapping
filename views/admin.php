<?php

/**
 * Represents the view for the administration dashboard.
 *
 * @package   UK_Mapping
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */
global $wpdb;

?>

<div class="wrap">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( isset( $_GET['done'] ) ) { ?>
		<div class="updated"><p><strong><?php _e( 'Done.' ); ?></strong></p></div>
	<?php } ?>

	<form method="post" action="">

		<?php wp_nonce_field( $this->plugin_slug . '_admin_page', $this->plugin_slug . '_admin_page_admin_nonce' ); ?>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Submit', $this->plugin_slug ); ?>"></p>

	</form>

</div>
