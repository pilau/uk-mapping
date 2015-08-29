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

		// Build area code equivalents
		$area_code_equivalents = array();
		foreach ( $this->code_type_equivalents[ $_POST[$this->plugin_slug . '-raw-area-type'] ] as $code_type ) {
			$area_code_equivalents[] = ' ac.code_type = \'' . strtoupper( $code_type ) . '\'';
		}

		// Do query
		$area = $wpdb->get_var("
			SELECT	ac.area_title
			FROM	$this->table_area_codes_raw ac, $this->table_postcodes_raw pc
			WHERE	pc.postcode														LIKE '" . strtoupper( $_POST[$this->plugin_slug . '-raw-postcode'] ) . "%'
			AND		pc." . $_POST[$this->plugin_slug . '-raw-area-type'] . "_code	= ac.code
			AND		( " . implode( ' OR ', $area_code_equivalents ) . " )
		");

		echo '<p><b>' . __( 'Area', $this->plugin_slug ) . ':</b> ' . $area ? $area : '<em>' . __( 'No match', $this->plugin_slug ) . '</em>' . '</p>';

		?>

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

</div>
