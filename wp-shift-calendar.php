<?php
/*
Plugin Name: Shift Calendar
Plugin URI: http://
Description: A Simple calendar for shift, roster, booking, business day and more.
Author: Hideyuki Motoo
Author URI: http://nearly.jp/
Version: 0.1
*/

if ( ! class_exists( 'Shift_Calendar' ) ) {

	define( 'SCAL_SLUG', 'wp-shift-calendar' );
	define( 'SCAL_SLUG_TIME', SCAL_SLUG . '-time' );
	define( 'SCAL_SLUG_PERSONS', SCAL_SLUG . '-persons' );
	define( 'SCAL_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'SCAL_DIR_PATH', plugin_dir_path( __FILE__ ) );
	
	class Shift_Calendar {
		
		var $settings;
		var $base_date;
		var $arr_time;
		var $arr_persons;
		var $list_time;
		var $list_persons;
		
		public function __construct() {
			load_plugin_textdomain( SCAL_SLUG, false, SCAL_DIR_PATH . 'languages/' );
			add_action( 'init', array( $this, 'init' ) );
			
			$this->base_date = mktime( 0, 0, 0, date( 'n' ), 1, date( 'Y' ) );
		}
		function init() {
			register_post_type( SCAL_SLUG, array(
				'labels' => array(
					'name' => 'WP Shift Calendar',
					'add_new' => '新規カレンダー追加',
					'add_new_item' => 'カレンダーを新規追加',
					'edit_item' =>  'カレンダーを編集',
					'new_item' => '新規カレンダー',
				),
				'public' => false,
				'show_ui' => true,
				'capability_type' => 'page',
				'hierarchical' => true,
				'menu_icon' => 'dashicons-calendar-alt',
				'rewrite' => false,
				'query_var' => SCAL_SLUG,
				'supports' => array(
					'title',
				),
				'show_in_menu'	=> true,
			));

			register_taxonomy( SCAL_SLUG_TIME, SCAL_SLUG, array(
				'label' => '日割りの時間帯',
				'public' => true,
				'show_ui' => true,
				'hierarchical' => false,
				'rewrite' => false,
			) );

			register_taxonomy( SCAL_SLUG_PERSONS, SCAL_SLUG, array(
				'label' => '担当・営業種別',
				'public' => true,
				'show_ui' => true,
				'hierarchical' => false,
				'rewrite' => false,
			) );
			
			add_action( 'current_screen', array( $this, 'current_screen' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_head', array( $this, 'admin_head' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			
			add_shortcode( SCAL_SLUG, array( $this, 'shortcode' ));
		}
		function current_screen() {
			$current_screen = get_current_screen();
			if ( SCAL_SLUG == $current_screen->post_type ) {
				wp_enqueue_style(  SCAL_SLUG, SCAL_DIR_URL . 'views/admin/css/' . SCAL_SLUG . '.css' );
				wp_enqueue_script( SCAL_SLUG, SCAL_DIR_URL . 'views/admin/js/' . SCAL_SLUG . '.js', array( 'jquery' ) );
			}
		}
		function admin_init() {
			$this->get_taxonomies();

			$this->settings = array(
				'list_time' => $this->list_time,
				'list_persons' => $this->list_persons,
				'arr_time' => $this->arr_time,
				'arr_persons' => $this->arr_persons,
			);
			add_action( 'save_post_' . SCAL_SLUG, array( $this, 'save_post' ) );

			remove_meta_box( 'slugdiv', SCAL_SLUG, 'normal' );
			remove_meta_box( 'tagsdiv-' . SCAL_SLUG_TIME, SCAL_SLUG, 'normal' );
			remove_meta_box( 'tagsdiv-' . SCAL_SLUG_PERSONS, SCAL_SLUG, 'normal' );
		}
		function admin_head() {
			//
		}
		function wp_enqueue_scripts() {
			wp_enqueue_style(  SCAL_SLUG, SCAL_DIR_URL . 'css/' . SCAL_SLUG . '.css' );
			wp_enqueue_script( SCAL_SLUG, SCAL_DIR_URL . 'js/' . SCAL_SLUG . '.js', array( 'jquery' ) );
		}
		function add_meta_boxes() {
			add_meta_box( SCAL_SLUG, 'Calendar', array( $this, 'create_meta_box' ), SCAL_SLUG, 'normal' );
		}
		
		function create_meta_box() {
			global $post;
			$meta_data = reset( get_post_meta( $post->ID, SCAL_SLUG, false ) );
			include( SCAL_DIR_PATH . 'views/admin/meta_box.php' );
		}
		function save_post( $post_id ) {
			if ( !isset( $_POST[ SCAL_SLUG ] ) ) {
				return $post_id;
			}
			
			if ( !empty( $_POST[ SCAL_SLUG ] ) ) {
				$this->get_taxonomies();

				$meta_data = array(
					'admin_data' => stripslashes_deep( $_POST[ SCAL_SLUG ] ), 
					'time' => $this->list_time,
					'persons' => $this->list_persons,
				);
				foreach ( $meta_data[ 'admin_data' ] as $ymd => $shifts ) {
					foreach ( $shifts as $time => $shift ) {
						if ( !$shift ) continue;
						$meta_data[ 'front_data' ][ $ymd ][ $this->settings[ 'arr_time' ][ $time ]->name ] = $this->settings[ 'arr_persons' ][ $shift ]->name;
					}
				}
				update_post_meta( $post_id, SCAL_SLUG, $meta_data );
			}
			
			return $post_id;
		}

		function shortcode( $atts ) {
			global $wp_locale;
			
			$atts = shortcode_atts( array(
				'id' => false,
				'months' => 3,
				'begin' => 0
			), $atts );
			
			if ( !$atts[ 'id' ] ) return;
			
			$meta_data = reset( get_post_meta( $atts[ 'id' ], SCAL_SLUG, false ) );
			$meta_data[ 'time2term_id' ] = array_flip( $meta_data[ 'time' ] );
			$meta_data[ 'person2term_id' ] = array_flip( $meta_data[ 'persons' ] );
			
			ob_start();
			
			for ( $i = 0; $i < $atts[ 'months' ]; $i++ ) :
				$this_month = strtotime( "+{$i} month", $this->base_date );
?>
<table class="scal-table scal-month-<?php echo date( 'Ym', $this_month ); ?>">
	<caption><?php echo date( 'Y年n月', $this_month ); ?></caption>
	<thead>
		<tr>
<?php			for ( $w = 0; $w < 7; $w++ ) : ?>
			<th scope="col" class="scal-week-<?php echo esc_attr( ( $w + $atts[ 'begin' ] ) % 7 ); ?>"><?php echo esc_html( $wp_locale->get_weekday_initial( $wp_locale->get_weekday( ( $w + $atts[ 'begin' ] ) % 7 ) ) ) ?></th>
<?php			endfor ?>
		</tr>
	</thead>
	<tbody>
		<tr>
<?php	$pad = calendar_week_mod( date( 'w', $this_month ) - $atts[ 'begin' ] ); ?>
<?php			if ( $pad != 0 ) : ?>
			<td colspan="<?php echo esc_attr( $pad ) ?>" class="scal-pad"></td>
<?php			endif; ?>

<?php			for ( $d = 1; $d <= date( 't', $this_month ); $d++ ) : ?>
<?php				$this_day = strtotime( '+' . ( $d - 1 ) . 'day', $this_month ); ?>
<?php				$this_weekday = date( 'w', $this_day ); ?>
<?php				if ( !empty( $break ) ) : $break = false; ?>
		</tr>
		<tr>
<?php				endif ?>
			<td class="scal-day-<?php echo esc_attr( $d ) ?> scal-week-<?php echo esc_attr( $this_weekday ); ?>">
				<em class="scal-day"><?php echo esc_html($d); ?></em>
<?php				foreach ( $meta_data[ 'time' ] as $time ) : $person = $meta_data[ 'front_data' ][ date( 'Y-m-d', $this_day ) ][ $time ]; ?>
				<div class="scal-time-<?php echo esc_attr( $meta_data[ 'time2term_id' ][ $time ] ); ?> scal-person-<?php echo esc_attr( $meta_data[ 'person2term_id' ][ $person ] ); ?>"><i><?php echo esc_html( $time ); ?></i> <?php echo esc_html( $person ); ?></div>
<?php				endforeach; ?>
			</td>

<?php				if ( calendar_week_mod( date( 'w', mktime(0, 0, 0, date( 'n', $this_month ), $d, date( 'Y', $this_month) ) ) - $atts[ 'begin' ] ) == 6 ) $break = true; ?>
<?php			endfor ?>
			
<?php			$pad = 7 - calendar_week_mod( date( 'w', mktime( 0, 0, 0, date( 'n', $this_month ), $d, date( 'Y', $this_month) ) ) - $atts[ 'begin' ] ); ?>
<?php			if ( $pad != 0 && $pad != 7 ) : ?>
			<td colspan="<?php echo esc_attr( $pad ) ?>" class="scal-pad"></td>
<?php			endif ?>
		</tr>
	</tbody>
</table>

<?php
			endfor;
			
			$buffer = ob_get_contents();
			ob_end_clean();
			return $buffer;
		}
		
		//helper
		function get_taxonomies() {
			$arr_time_tmp = get_categories( array( 'hide_empty' => 0, 'taxonomy' => SCAL_SLUG_TIME ) );
			if ( empty( $arr_time_tmp ) ) $arr_time_tmp[] = (object) array( 'term_id' => 'default', 'name' => '終日' );
			$this->arr_time = array();
			foreach ( $arr_time_tmp as $data ) $this->arr_time[ $data->term_id ] = $data;

			$arr_persons_tmp = get_categories( array( 'hide_empty' => 0, 'taxonomy' => SCAL_SLUG_PERSONS ) );
			if ( empty( $arr_persons_tmp ) ) $arr_persons_tmp[] = (object) array( 'term_id' => 'default', 'name' => '休み' );
			$this->arr_persons = array();
			foreach ( $arr_persons_tmp as $data ) $this->arr_persons[ $data->term_id ] = $data;
			
			$this->list_time = array();
			foreach ( $this->arr_time as $data ) $this->list_time[ $data->term_id ] = $data->name;
			
			$this->list_persons = array();
			foreach ( $this->arr_persons as $data ) $this->list_persons[ $data->term_id ] = $data->name;
		}
	}
	new Shift_Calendar();
}