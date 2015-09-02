<?php

/**
 * Raw data page
 *
 * @package   UK_Mapping
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */
global $wpdb;
$query_submitted = isset( $_POST[ $this->plugin_slug . '_raw_data_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_raw_data', $this->plugin_slug . '_raw_data_nonce' );

?>

<div class="wrap">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( $query_submitted ) { ?>

		<?php

		$authority_details = $this->raw_postcode_to_local_authority( $_REQUEST[$this->plugin_slug . '-raw-postcode'], $_REQUEST[$this->plugin_slug . '-raw-area-type'] );

		echo '<p><b>' . __( 'Area', $this->plugin_slug ) . ' (' . $this->code_type_names[ $authority_details->code_type ] . '):</b> ' . $authority_details->area_title . '</p>';

		?>

	<?php } else if ( $_GET['kml'] ) { ?>

		<p><em><?php _e( 'KML imported successfully.' ); ?></em></p>

	<?php } else if ( $_GET['populate'] ) { ?>

		<p><em><?php _e( 'Data populated successfully.' ); ?></em></p>

	<?php } ?>

	<form method="post" action="">

		<?php wp_nonce_field( $this->plugin_slug . '_raw_data', $this->plugin_slug . '_raw_data_nonce' ); ?>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo $this->plugin_slug . '-raw-postcode'; ?>"><?php _e( 'Postcode' ); ?></label>
					</th>
					<td>
						<input type="text" name="<?php echo $this->plugin_slug . '-raw-postcode'; ?>" id="<?php echo $this->plugin_slug . '-raw-postcode'; ?>" class="regular-text"<?php if ( $query_submitted ) { ?> value="<?php echo $_POST[$this->plugin_slug . '-raw-postcode']; ?>"<?php } ?>>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo $this->plugin_slug . '-raw-area-type'; ?>"><?php _e( 'Get...' ); ?></label>
					</th>
					<td>
						<select name="<?php echo $this->plugin_slug . '-raw-area-type'; ?>" id="<?php echo $this->plugin_slug . '-raw-area-type'; ?>" class="regular-text">
							<option value="cty"<?php if ( $query_submitted ) selected( $_POST[$this->plugin_slug . '-raw-area-type'], 'cty' ); ?>><?php _e( 'County', $this->plugin_slug ); ?></option>
							<option value="dis"<?php if ( $query_submitted ) selected( $_POST[$this->plugin_slug . '-raw-area-type'], 'dis' ); ?>><?php _e( 'District', $this->plugin_slug ); ?></option>
							<option value="diw"<?php if ( $query_submitted ) selected( $_POST[$this->plugin_slug . '-raw-area-type'], 'diw' ); ?>><?php _e( 'Ward', $this->plugin_slug ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Do query', $this->plugin_slug ); ?>"></p>

	</form>

	<?php if ( $this->options['postcode_post_type'] != 'none' ) { ?>

		<h3><?php _e( 'Populate data' ); ?></h3>

		<?php $cpt_args = $this->custom_post_type_args(); ?>

		<form method="post" action="">

			<?php wp_nonce_field( $this->plugin_slug . '_populate_data', $this->plugin_slug . '_populate_data_nonce' ); ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo $this->plugin_slug . '_populate_post_type'; ?>"><?php _e( 'Populate post type' ); ?></label>
						</th>
						<td>
							<select name="<?php echo $this->plugin_slug . '_populate_post_type'; ?>" id="<?php echo $this->plugin_slug . '_populate_post_type'; ?>" class="regular-text">
								<option value="<?php echo $this->options['postcode_post_type']; ?>"><?php echo $cpt_args[ $this->options['postcode_post_type'] ]['labels']['name']; ?></option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Populate', $this->plugin_slug ); ?>"></p>

		</form>

	<?php } ?>

	<?php /*

 	<h2><?php _e( 'KML import', $this->plugin_sliug ); ?></h2>

	<?php if ( $this->import_kml ) { ?>

		<form method="post" action="">

			<?php wp_nonce_field( $this->plugin_slug . '_kml_import', $this->plugin_slug . '_kml_import_nonce' ); ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<td>
							<?php foreach( $this->import_kml as $kml_file ) { ?>
								<?php $filename_full =  basename( $kml_file ); ?>
								<?php $filename = str_replace( '.kml', '', $filename_full ); ?>
								<?php $filename_safe = esc_attr( $filename ); ?>
								<label for="<?php echo $filename_safe; ?>"><input type="radio" name="<?php echo $filename_safe; ?>" id="<?php echo $filename_safe; ?>" value="1"> <?php echo $filename_full; ?></label>
							<?php } ?>
						</td>
					</tr>
				</tbody>
			</table>

			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Import KML file', $this->plugin_slug ); ?>"></p>

		</form>

		<?php

	} else {

		echo '<p><em>' . __( 'No KML files to import' ) . '</em></p>';

	}

	?>

 	*/ ?>

</div>
