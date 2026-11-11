<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'BrandsAZ_Settings' ) ) {
	return;
}

class BrandsAZ_Settings {

	const OPT   = 'brandsaz_options';
	const GROUP = 'brandsaz_group';
	const SLUG  = 'brandsaz-filter';

	public static function defaults() {
		return array(
			'taxonomy'   => 'auto',
			'hide_empty' => 'yes',
			'merge'      => 'yes',
			'accent'     => '#C8A56B',
			'text'       => '#000000',
			'letter'     => '#000000',
			'border'     => '#000000',
		);
	}

	public static function get() {
		$saved = get_option( self::OPT, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( BRANDSAZ_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::SLUG );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'brands-az' ) . '</a>' );
		return $links;
	}

	public static function menu() {
		add_options_page(
			__( 'Brands A-Z', 'brands-az' ),
			__( 'Brands A-Z', 'brands-az' ),
			'manage_options',
			self::SLUG,
			array( __CLASS__, 'page' )
		);
	}

	public static function register() {
		register_setting(
			self::GROUP,
			self::OPT,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize( $input ) {
		$def   = self::defaults();
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		$tax             = isset( $input['taxonomy'] ) ? sanitize_key( $input['taxonomy'] ) : 'auto';
		$out['taxonomy'] = ( 'auto' === $tax || taxonomy_exists( $tax ) ) ? $tax : 'auto';

		foreach ( array( 'hide_empty', 'merge' ) as $key ) {
			$out[ $key ] = ( isset( $input[ $key ] ) && 'no' === $input[ $key ] ) ? 'no' : 'yes';
		}

		foreach ( array( 'accent', 'text', 'letter', 'border' ) as $key ) {
			$color       = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
			$out[ $key ] = $color ? $color : $def[ $key ];
		}

		return $out;
	}

	public static function admin_assets( $hook ) {
		if ( 'settings_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".brandsaz-color").wpColorPicker();});' );
	}

	public static function product_taxonomies() {
		$list  = array();
		$taxes = get_object_taxonomies( 'product', 'objects' );
		if ( is_array( $taxes ) ) {
			foreach ( $taxes as $slug => $obj ) {
				$list[ $slug ] = $obj->labels->name . ' (' . $slug . ')';
			}
		}
		return $list;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o     = self::get();
		$name  = self::OPT;
		$taxes = self::product_taxonomies();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Brands A-Z', 'brands-az' ); ?></h1>
			<p>
				<?php esc_html_e( 'Add this shortcode to any page (Elementor: Shortcode widget):', 'brands-az' ); ?>
				<code>[brands_az]</code>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="brandsaz_taxonomy"><?php esc_html_e( 'Brand taxonomy', 'brands-az' ); ?></label></th>
						<td>
							<select id="brandsaz_taxonomy" name="<?php echo esc_attr( $name ); ?>[taxonomy]">
								<option value="auto" <?php selected( $o['taxonomy'], 'auto' ); ?>><?php esc_html_e( 'Auto detect (recommended)', 'brands-az' ); ?></option>
								<?php foreach ( $taxes as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $o['taxonomy'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Hide brands with no products', 'brands-az' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[hide_empty]" value="yes" <?php checked( $o['hide_empty'], 'yes' ); ?>> <?php esc_html_e( 'Yes', 'brands-az' ); ?></label>
							&nbsp;
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[hide_empty]" value="no" <?php checked( $o['hide_empty'], 'no' ); ?>> <?php esc_html_e( 'No', 'brands-az' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Merge duplicate brand names', 'brands-az' ); ?></th>
						<td>
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[merge]" value="yes" <?php checked( $o['merge'], 'yes' ); ?>> <?php esc_html_e( 'Yes (show a name only once)', 'brands-az' ); ?></label>
							&nbsp;
							<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[merge]" value="no" <?php checked( $o['merge'], 'no' ); ?>> <?php esc_html_e( 'No', 'brands-az' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="brandsaz_accent"><?php esc_html_e( 'A-Z bar color', 'brands-az' ); ?></label></th>
						<td><input type="text" id="brandsaz_accent" class="brandsaz-color" name="<?php echo esc_attr( $name ); ?>[accent]" value="<?php echo esc_attr( $o['accent'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="brandsaz_text"><?php esc_html_e( 'Text color (letters, brand names)', 'brands-az' ); ?></label></th>
						<td><input type="text" id="brandsaz_text" class="brandsaz-color" name="<?php echo esc_attr( $name ); ?>[text]" value="<?php echo esc_attr( $o['text'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="brandsaz_letter"><?php esc_html_e( 'Big letter color', 'brands-az' ); ?></label></th>
						<td><input type="text" id="brandsaz_letter" class="brandsaz-color" name="<?php echo esc_attr( $name ); ?>[letter]" value="<?php echo esc_attr( $o['letter'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="brandsaz_border"><?php esc_html_e( 'Search box border color', 'brands-az' ); ?></label></th>
						<td><input type="text" id="brandsaz_border" class="brandsaz-color" name="<?php echo esc_attr( $name ); ?>[border]" value="<?php echo esc_attr( $o['border'] ); ?>"></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
