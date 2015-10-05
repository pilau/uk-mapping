<?php

/**
 * Raw data page
 *
 * @package   UK_Mapping
 * @author    Steve Taylor
 * @license   GPL-2.0+
 */
global $wpdb;
$match_postcode_to_la_query_submitted = isset( $_POST[ $this->plugin_slug . '_raw_data_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_raw_data', $this->plugin_slug . '_raw_data_nonce' );
$check_postcode_straddle_query_submitted = isset( $_POST[ $this->plugin_slug . '_check_postcode_straddle_nonce' ] ) && check_admin_referer( $this->plugin_slug . '_check_postcode_straddle', $this->plugin_slug . '_check_postcode_straddle_nonce' );

?>

<div class="wrap">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( $match_postcode_to_la_query_submitted ) { ?>

		<?php

		$authority_details = $this->raw_postcode_to_local_authority( $_REQUEST[$this->plugin_slug . '-raw-postcode'], $_REQUEST[$this->plugin_slug . '-raw-area-type'] );

		echo '<p><b>' . __( 'Area', $this->plugin_slug ) . ' (' . $this->code_type_names[ $authority_details->code_type ] . '):</b> ' . $authority_details->area_title . '</p>';

		?>

	<?php } else if ( $check_postcode_straddle_query_submitted ) { ?>

		<?php

		$check_straddle_results = $this->raw_check_postcode_straddle( $_REQUEST[ $this->plugin_slug . '-check-postcode-straddle-level' ], $_REQUEST[ $this->plugin_slug . '-check-postcode-straddle-la-type' ] );

		?>

		<p><em><?php _e( 'Postcode straddle checking results:' ); ?></em></p>

		<ul>
			<li><b><?php _e( 'Postcode level:' ); ?></b> <?php echo ucfirst( $_REQUEST[ $this->plugin_slug . '-check-postcode-straddle-level' ] ); ?></li>
			<li><b><?php _e( 'Local authority type:' ); ?></b> <?php echo $this->code_type_names[ strtoupper( $_REQUEST[ $this->plugin_slug . '-check-postcode-straddle-la-type' ] ) ]; ?></li>
			<li><b><?php _e( 'Number of postcodes at this level straddling more than one local authority of this type:' ); ?></b> <?php echo count( $check_straddle_results['straddling_postcodes'] ); ?></li>
			<li><b><?php _e( 'This number as a percentage of the total unique postcodes at this level:' ); ?></b> <?php echo $check_straddle_results['percentage_of_total']; ?>%</li>
		</ul>

	<?php } else if ( $_GET['kml'] ) { ?>

		<p><em><?php _e( 'KML imported successfully.' ); ?></em></p>

	<?php } else if ( $_GET['populate'] ) { ?>

		<p><em><?php _e( 'Data populated successfully.' ); ?></em></p>

	<?php } ?>


	<form method="post" action="">

		<?php wp_nonce_field( $this->plugin_slug . '_raw_data', $this->plugin_slug . '_raw_data_nonce' ); ?>

		<h3><?php _e( 'Match postcode to local authority' ); ?></h3>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo $this->plugin_slug . '-raw-postcode'; ?>"><?php _e( 'Postcode' ); ?></label>
					</th>
					<td>
						<input type="text" name="<?php echo $this->plugin_slug . '-raw-postcode'; ?>" id="<?php echo $this->plugin_slug . '-raw-postcode'; ?>" class="regular-text"<?php if ( $match_postcode_to_la_query_submitted ) { ?> value="<?php echo $_POST[$this->plugin_slug . '-raw-postcode']; ?>"<?php } ?>>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo $this->plugin_slug . '-raw-area-type'; ?>"><?php _e( 'Get...' ); ?></label>
					</th>
					<td>
						<select name="<?php echo $this->plugin_slug . '-raw-area-type'; ?>" id="<?php echo $this->plugin_slug . '-raw-area-type'; ?>" class="regular-text">
							<?php foreach ( $this->code_type_equivalents as $la_type => $la_type_equivalents ) { ?>
								<option value="<?php echo strtolower( $la_type ); ?>"<?php if ( $match_postcode_to_la_query_submitted ) selected( $_POST[$this->plugin_slug . '-raw-area-type'], strtolower( $la_type ) ); ?>><?php echo $la_type; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Do query', $this->plugin_slug ); ?>"></p>

	</form>


	<h3><?php _e( 'Check for postcodes straddling more than one local authority' ); ?></h3>

	<form method="post" action="">

		<?php wp_nonce_field( $this->plugin_slug . '_check_postcode_straddle', $this->plugin_slug . '_check_postcode_straddle_nonce' ); ?>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo $this->plugin_slug . '-check-postcode-straddle-level'; ?>"><?php _e( 'Postcode level' ); ?></label>
					</th>
					<td>
						<select name="<?php echo $this->plugin_slug . '-check-postcode-straddle-level'; ?>" id="<?php echo $this->plugin_slug . '-check-postcode-straddle-level'; ?>" class="regular-text">
							<?php foreach ( array_reverse( $this->postcode_levels ) as $postcode_level ) { ?>
								<option value="<?php echo $postcode_level; ?>"><?php echo ucfirst( $postcode_level ); ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo $this->plugin_slug . '-check-postcode-straddle-la-type'; ?>"><?php _e( 'Local authority type' ); ?></label>
					</th>
					<td>
						<select name="<?php echo $this->plugin_slug . '-check-postcode-straddle-la-type'; ?>" id="<?php echo $this->plugin_slug . '-check-postcode-straddle-la-type'; ?>" class="regular-text">
							<?php foreach ( $this->code_type_equivalents as $la_type => $la_type_equivalents ) { ?>
								<option value="<?php echo strtolower( $la_type ); ?>"<?php if ( $match_postcode_to_la_query_submitted ) selected( $_POST[$this->plugin_slug . '-check-postcode-straddle-la-type'], strtolower( $la_type ) ); ?>><?php echo $la_type; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Check', $this->plugin_slug ); ?>"></p>

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
