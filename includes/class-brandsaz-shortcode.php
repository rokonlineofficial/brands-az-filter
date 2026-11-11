<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'BrandsAZ_Shortcode' ) ) {
	return;
}

class BrandsAZ_Shortcode {

	private static $inline_done = false;

	public static function init() {
		add_shortcode( 'brands_az', array( __CLASS__, 'render' ) );
		add_shortcode( 'brands_az_filter', array( __CLASS__, 'render' ) ); // backup name if another plugin uses [brands_az]
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( 'brandsaz-filter', BRANDSAZ_URL . 'assets/css/brands-az.css', array(), BRANDSAZ_VERSION );
		wp_register_script( 'brandsaz-filter', BRANDSAZ_URL . 'assets/js/brands-az.js', array(), BRANDSAZ_VERSION, true );
	}

	private static function enqueue_assets( $opts ) {
		if ( ! wp_style_is( 'brandsaz-filter', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( 'brandsaz-filter' );
		wp_enqueue_script( 'brandsaz-filter' );

		if ( ! self::$inline_done ) {
			self::$inline_done = true;
			$css = sprintf(
				'.brandsaz{--brandsaz-accent:%1$s;--brandsaz-text:%2$s;--brandsaz-letter:%3$s;--brandsaz-border:%4$s;}',
				esc_html( $opts['accent'] ),
				esc_html( $opts['text'] ),
				esc_html( $opts['letter'] ),
				esc_html( $opts['border'] )
			);
			wp_add_inline_style( 'brandsaz-filter', $css );
		}
	}

	private static function find_taxonomy( $wanted ) {
		$wanted = sanitize_key( $wanted );
		if ( $wanted && 'auto' !== $wanted && taxonomy_exists( $wanted ) ) {
			return $wanted;
		}

		$candidates = array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'product_brands', 'pa_brand', 'brand' );
		foreach ( $candidates as $tax ) {
			if ( taxonomy_exists( $tax ) ) {
				return $tax;
			}
		}

		$taxes = get_object_taxonomies( 'product' );
		if ( is_array( $taxes ) ) {
			foreach ( $taxes as $tax ) {
				if ( false !== stripos( $tax, 'brand' ) ) {
					return $tax;
				}
			}
		}
		return '';
	}

	private static function first_char( $name ) {
		$name  = trim( $name );
		$first = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
		$first = strtoupper( remove_accents( $first ) );
		if ( ! preg_match( '/^[A-Z]$/', $first ) ) {
			return '0-9';
		}
		return $first;
	}

	public static function render( $atts ) {
		$o = BrandsAZ_Settings::get();

		$atts = shortcode_atts(
			array(
				'taxonomy'   => '',
				'hide_empty' => '',
				'merge'      => '',
				'search'     => 'yes',
				'view_all'   => 'yes',
				'columns'    => '2',
			),
			$atts,
			'brands_az'
		);

		$taxonomy = self::find_taxonomy( '' !== $atts['taxonomy'] ? $atts['taxonomy'] : $o['taxonomy'] );
		if ( ! $taxonomy ) {
			return current_user_can( 'manage_options' )
				? '<p>' . esc_html__( 'Brands A-Z: no brand taxonomy found. Choose one in Settings > Brands A-Z.', 'brands-az' ) . '</p>'
				: '';
		}

		$hide_empty = ( '' !== $atts['hide_empty'] ? $atts['hide_empty'] : $o['hide_empty'] );
		$hide_empty = ( 'no' === strtolower( $hide_empty ) ) ? false : true;
		$merge      = ( '' !== $atts['merge'] ? $atts['merge'] : $o['merge'] );
		$merge      = ( 'no' === strtolower( $merge ) ) ? false : true;
		$columns    = max( 1, min( 4, (int) $atts['columns'] ) );

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => $hide_empty,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		// Optionally merge terms that share the same name (show once).
		$items = array();
		foreach ( $terms as $t ) {
			$name = wp_specialchars_decode( $t->name, ENT_QUOTES );
			$key  = $merge ? strtolower( preg_replace( '/\s+/', ' ', trim( $name ) ) ) : 't' . $t->term_id;
			if ( isset( $items[ $key ] ) && (int) $items[ $key ]['count'] >= (int) $t->count ) {
				continue;
			}
			$items[ $key ] = array(
				'name'  => $name,
				'term'  => $t,
				'count' => (int) $t->count,
			);
		}

		$groups = array();
		foreach ( $items as $item ) {
			$groups[ self::first_char( $item['name'] ) ][] = $item;
		}
		ksort( $groups );
		foreach ( $groups as $letter => $list ) {
			usort(
				$list,
				function ( $a, $b ) {
					return strnatcasecmp( $a['name'], $b['name'] );
				}
			);
			$groups[ $letter ] = $list;
		}

		self::enqueue_assets( $o );

		$msg_letter = __( 'No products available for brands starting with "{letter}" yet.', 'brands-az' );
		$msg_search = __( 'No brands found for "{query}".', 'brands-az' );
		$msg_all    = __( 'No brands found.', 'brands-az' );

		ob_start();
		?>
		<div class="brandsaz" style="--brandsaz-cols:<?php echo (int) $columns; ?>"
			data-msg-letter="<?php echo esc_attr( $msg_letter ); ?>"
			data-msg-search="<?php echo esc_attr( $msg_search ); ?>"
			data-msg-all="<?php echo esc_attr( $msg_all ); ?>">

			<?php if ( 'no' !== strtolower( $atts['search'] ) ) : ?>
				<div class="brandsaz__search">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="6.5" fill="none" stroke="currentColor" stroke-width="2.6"/><path d="M15 15l6 6" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/></svg>
					<input type="text" class="brandsaz__input" placeholder="<?php esc_attr_e( 'Search for a brand', 'brands-az' ); ?>" aria-label="<?php esc_attr_e( 'Search for a brand', 'brands-az' ); ?>" autocomplete="off">
				</div>
			<?php endif; ?>

			<div class="brandsaz__nav" role="group" aria-label="<?php esc_attr_e( 'Filter brands by letter', 'brands-az' ); ?>">
				<?php foreach ( range( 'A', 'Z' ) as $L ) : ?>
					<button type="button" class="brandsaz__letter<?php echo isset( $groups[ $L ] ) ? ' has-brands' : ' is-empty'; ?>" data-letter="<?php echo esc_attr( $L ); ?>"><?php echo esc_html( $L ); ?></button>
				<?php endforeach; ?>
				<?php if ( isset( $groups['0-9'] ) ) : ?>
					<button type="button" class="brandsaz__letter has-brands" data-letter="0-9">0-9</button>
				<?php endif; ?>
				<?php if ( 'no' !== strtolower( $atts['view_all'] ) ) : ?>
					<button type="button" class="brandsaz__letter brandsaz__letter--all has-brands is-active" data-letter="all"><?php esc_html_e( 'View All', 'brands-az' ); ?></button>
				<?php endif; ?>
			</div>

			<div class="brandsaz__body">
				<?php foreach ( $groups as $letter => $list ) : ?>
					<section class="brandsaz__group" data-letter="<?php echo esc_attr( $letter ); ?>">
						<div class="brandsaz__big"><?php echo esc_html( $letter ); ?></div>
						<ul class="brandsaz__list">
							<?php
							foreach ( $list as $item ) :
								$link = get_term_link( $item['term'] );
								if ( is_wp_error( $link ) ) {
									continue;
								}
								$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $item['name'] ) : strtolower( $item['name'] );
								?>
								<li data-name="<?php echo esc_attr( $lower ); ?>"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $item['name'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>
				<p class="brandsaz__none" role="status" aria-live="polite" hidden></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
