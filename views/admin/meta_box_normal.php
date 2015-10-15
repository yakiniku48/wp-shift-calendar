<?php	if ( !empty( $this->arr_time ) ) : ?>
	<table class="widefat <?php echo SCAL_SLUG; ?>">
		<thead>
			<tr>
				<th colspan="2"><?php echo date( 'Y', $this->base_date ); ?></th>
<?php	$arr_month = array(); ?>
<?php		for ( $i = 0; $i < 6; $i++ ) : $arr_month[ $i ] = date( 'n', strtotime( "+{$i} month", $this->base_date ) ); ?>
				<th><?php echo $arr_month[ $i ]; ?>月</th>
<?php		endfor; ?>
			</tr>
		</thead>
		<tbody>
<?php		for ( $i = 0; $i < 31; $i++ ) : $d = $i + 1; ?>
<?php			foreach ( $this->arr_time as $time ) : ?>
			<tr>
<?php				if ( $time == reset( $this->arr_time ) ) : ?>
				<th rowspan="<?php echo count( $this->arr_time ); ?>"><?php echo $d; ?></th>
<?php				endif; ?>
				<th><?php echo $time->name; ?></th>
<?php				for ( $j = 0; $j < 6; $j++ ) : $m = $j + 1; $this_time = strtotime( "+{$i} day +{$j} month", $this->base_date ); ?>
<?php					$td_class = '';
						if ( date( 'w', $this_time ) == 0 ) $td_class = 'weekday_sun';
						if ( date( 'w', $this_time ) == 6 ) $td_class = 'weekday_sat';
?>
				<td class="<?php echo $td_class; ?>">
<?php					if ( $arr_month[ $j ] == date( 'n', $this_time ) ) : $term_id = $meta_data[ 'admin_data' ][ date( 'Y-m-d', $this_time ) ][ $time->term_id ]; ?>
					<input type="button" class="button btn-shift-calendar" data-id="0" value="<?php echo ( $this->list_persons[ $term_id ] ) ? $this->list_persons[ $term_id ] : '--'; ?>">
					<input type="hidden" name="<?php echo SCAL_SLUG; ?>[<?php echo date( 'Y-m-d', $this_time ); ?>][<?php echo $time->term_id; ?>]" value="<?php echo $term_id; ?>">
<?php					endif; ?>
				</td>
<?php				endfor; ?>
			</tr>
<?php			endforeach; ?>
<?php		endfor; ?>
		</tbody>
	</table>

<script>
var arrShift = [ "--" <?php foreach ( $this->list_persons as $term_id => $person ) echo ', "' . $person . '"'; ?> ];
var arrTermID = [ 0 <?php foreach ( $this->list_persons as  $term_id => $person ) echo ', "' . $term_id . '"'; ?> ];
jQuery( document ).ready( function ( $ ) {
	$( ".btn-shift-calendar" ).on( "click", function () {
		$( this ).data( "id", ( $( this ).data( "id" ) + 1 ) % arrShift.length );
		$( this ).val( arrShift[ $( this ).data( "id" ) ] );
		$( this ).next().val( arrTermID[ $( this ).data( "id" ) ] );
	} );
} );
</script>

<?php	else : ?>
	<h4>まずは「日割りの時間帯」「担当・営業種別」を登録して、下書き保存か更新してください</h4>
	<ul>
	<li>日割りの時間帯の例）「終日」「AM」「PM」「10:00〜12:00」など</li>
	<li>担当・営業種別の例）「新垣」「長澤」「休診」など</li>
	</ul>
<?php	endif; ?>