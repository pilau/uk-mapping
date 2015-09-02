<?php

/**
 * Represents the view for the administration dashboard.
 *
 * @package   UK_Mapping
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */

?>

<div class="wrap">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( isset( $_GET['done'] ) ) { ?>
		<div class="updated"><p><strong><?php _e( 'Settings updated successfully.' ); ?></strong></p></div>
	<?php } ?>

	<form method="post" action="">

		<?php wp_nonce_field( $this->plugin_slug . '_admin_page', $this->plugin_slug . '_admin_page_admin_nonce' ); ?>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><?php _e( 'Postcode post type' ); ?></th>
					<td>
						<fieldset>

							<legend class="screen-reader-text"><span><?php _e( 'Postcode post type' ); ?></span></legend>

							<label for="<?php echo $this->plugin_slug . '_postcode_post_type_none'; ?>"><input type="radio" name="<?php echo $this->plugin_slug . '_postcode_post_type'; ?>" id="<?php echo $this->plugin_slug . '_postcode_post_type_none'; ?>" value="none"<?php checked( $this->options['postcode_post_type'], 'none' ); ?>> <?php _e( 'None', $this->plugin_slug ); ?></label><br>

							<?php foreach ( $this->custom_post_type_args() as $post_type => $post_type_args ) { ?>
								<?php if ( strpos( $post_type, 'postcode' ) !== false ) { ?>
									<label for="<?php echo $this->plugin_slug . '_postcode_post_type_' . $post_type; ?>"><input type="radio" name="<?php echo $this->plugin_slug . '_postcode_post_type'; ?>" id="<?php echo $this->plugin_slug . '_postcode_post_type_' . $post_type; ?>" value="<?php echo $post_type; ?>"<?php checked( $this->options['postcode_post_type'], $post_type ); ?>> <?php echo $post_type_args['labels']['name']; ?></label><br>
								<?php } ?>
							<?php } ?>

						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Update settings', $this->plugin_slug ); ?>"></p>

	</form>

</div>
