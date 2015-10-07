	<table class="widefat <?php echo SCAL_SLUG; ?>">
		<thead>
			<tr>
				<th colspan="2"><?php echo date( 'Y', $this->base_date ); ?></th>
<?php	$arr_month = array(); ?>
<?php	for ( $i = 0; $i < 6; $i++ ) : $arr_month[ $i ] = date( 'n', strtotime( "+{$i} month", $this->base_date ) ); ?>
				<th><?php echo $arr_month[ $i ]; ?>月</th>
<?php	endfor; ?>
			</tr>
		</thead>
		<tbody>
<?php	for ( $i = 0; $i < 31; $i++ ) : $d = $i + 1; ?>
<?php		foreach ( $this->settings[ 'arr_time' ] as $time ) : ?>
			<tr>
<?php			if ( $time == reset( $this->settings[ 'arr_time' ] ) ) : ?>
				<th rowspan="<?php echo count( $this->settings[ 'arr_time' ] ); ?>"><?php echo $d; ?></th>
<?php			endif; ?>
				<th><?php echo $time->name; ?></th>
<?php			for ( $j = 0; $j < 6; $j++ ) : $m = $j + 1; $this_time = strtotime( "+{$i} day +{$j} month", $this->base_date ); ?>
				<td>
<?php				if ( $arr_month[ $j ] == date( 'n', $this_time ) ) : $term_id = $meta_data[ 'admin_data' ][ date( 'Y-m-d', $this_time ) ][ $time->term_id ]; ?>
					<input type="button" class="button btn-shift-calendar" data-id="0" value="<?php echo ( $this->settings[ 'list_persons' ][ $term_id ] ) ? $this->settings[ 'list_persons' ][ $term_id ] : '--'; ?>">
					<input type="hidden" name="<?php echo SCAL_SLUG; ?>[<?php echo date( 'Y-m-d', $this_time ); ?>][<?php echo $time->term_id; ?>]" value="<?php echo $term_id; ?>">
<?php				endif; ?>
				</td>
<?php			endfor; ?>
			</tr>
<?php		endforeach; ?>
<?php	endfor; ?>
		</tbody>
	</table>



<script>
var arrShift = [ "--" <?php foreach ( $this->settings[ 'arr_persons' ] as $persons ) echo ', "' . $persons->name . '"'; ?> ];
var arrTermID = [ 0 <?php foreach ( $this->settings[ 'arr_persons' ] as $persons ) echo ', "' . $persons->term_id . '"'; ?> ];
jQuery( document ).ready( function ( $ ) {
	$( ".btn-shift-calendar" ).on( "click", function () {
		$( this ).data( "id", ( $( this ).data( "id" ) + 1 ) % arrShift.length );
		$( this ).val( arrShift[ $( this ).data( "id" ) ] );
		$( this ).next().val( arrTermID[ $( this ).data( "id" ) ] );
	} );
} );
</script>
