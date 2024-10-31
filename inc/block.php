<?php
namespace WPP\Inc;

require_once WPP_DIR_PATH . 'inc/style.php';

class WPPBlock{
	function __construct(){
		add_action( 'init', [$this, 'onInit'] );
	}

	function onInit() {
		wp_register_style( 'wpp-popular-products-style', WPP_DIR_URL . 'dist/style.css', [], WPP_PLUGIN_VERSION ); // Style
		wp_register_style( 'wpp-popular-products-editor-style', WPP_DIR_URL . 'dist/editor.css', [ 'wpp-popular-products-style' ], WPP_PLUGIN_VERSION ); // Backend Style

		register_block_type( __DIR__, [
			'editor_style'		=> 'wpp-popular-products-editor-style',
			'render_callback'	=> [$this, 'render']
		] ); // Register Block

		wp_set_script_translations( 'wpp-popular-products-editor-script', 'popular-products', WPP_DIR_PATH . 'languages' );
	}

	function render( $attributes ){
		extract( $attributes );

		wp_enqueue_style( 'wpp-popular-products-style' );

		$className = $className ?? '';
		$blockClassName = "wp-block-wpp-popular-products $className align$align";

		$products = wc_get_products( [
			'limit'			=> $productsPerPage,
			'meta_key'		=> 'total_sales',
			'orderby'		=> ['meta_value_num' => 'DESC', 'date' => 'DESC'],
			'order'			=> 'DESC',
			'stock_status'	=> $stockStatus,
			'category'		=> $selectedCategories
		] );

		if( empty( $products ) ){
			ob_start(); ?>
				<h3 class='wppNoProductFound'><?php echo __( 'No product found! Please add some or change query...', 'popular-products' ); ?></h3>
			<?php return ob_get_clean();
		}

		ob_start(); ?>
		<div class='<?php echo esc_attr( $blockClassName ); ?>' id='wppPopularProducts-<?php echo esc_attr( $cId ) ?>'>
			<style>
				<?php echo wp_kses( Style::generatedStyle( $attributes ), [] ); ?>
			</style>

			<div class='wppPopularProducts columns-<?php echo esc_attr( $columns['desktop'] ); ?> columns-tablet-<?php echo esc_attr( $columns['tablet'] ); ?> columns-mobile-<?php echo esc_attr( $columns['mobile'] ); ?>'>
				<?php foreach( $products as $product ) {
					echo $this->singlePostLayout( $attributes, $product );
				} ?>
			</div>
		</div>

		<?php return ob_get_clean();
	} // Render

	function singlePostLayout( $attributes, $product ){
		extract( $attributes );

		$ID = $product->get_id();

		ob_start(); ?>
		<article class='wppProduct wppProduct-<?php echo esc_attr( $ID ); ?>'>
			<?php echo $this->productImage( $product, $attributes ); ?>
			
			<div class='wppProductDetails'>
				<?php
					echo $this->productTitle( $product, $attributes );
					echo $this->productRating( $product, $attributes );
					echo $this->productPrice( $product, $attributes );
					echo $this->productAddToCartArea( $product, $attributes );
				?>
			</div>

			<?php echo $this->productOnSale( $product, $attributes ); ?>
		</article>
		<?php return ob_get_clean();
	} // Single Post Layout

	function productImage( $product, $attributes ){
		$ID = $product->get_id();
		$link = esc_url( $product->get_permalink() );
		$hasImage = has_post_thumbnail( $ID );
		$imgHTML = get_the_post_thumbnail( $ID );
		$placeImg = wc_placeholder_img_src();

		if( !empty( $attributes['isImage'] ) ){
			ob_start(); ?>
			<a href='<?php echo esc_attr( $link ); ?>'>
				<figure class='wppProductImg'>
					<?php echo $hasImage ? wp_kses_post( $imgHTML ) : "<img src='". esc_attr( $placeImg ) ."' alt='Placeholder' />"; ?>
				</figure>
			</a>
		<?php return ob_get_clean();
		}else{
			return '';
		}
	} // Product Image

	function productTitle( $product, $attributes ){
		$link = esc_url( $product->get_permalink() );

		if( !empty( $attributes['isTitle'] ) ){
			ob_start(); ?>
			<h3 class='productTitle'>
				<a href='<?php echo esc_attr( $link ); ?>'>
					<?php echo wp_kses_post( $product->get_title() ); ?>
				</a>
			</h3>
		<?php return ob_get_clean();
		}else{
			return '';
		}
	} // Product Title

	function productRating( $product, $attributes ){
		$rating_count	= $product->get_rating_count();
		$average		= $product->get_average_rating();

		if( !empty( $attributes['isRating'] ) ){
			ob_start(); ?>
			<div class='productRating'>
				<?php echo wc_get_rating_html( $average, $rating_count ) ?>
			</div>
		<?php return ob_get_clean();
		}else{
			return '';
		}
	} // Product Rating

	function productPrice( $product, $attributes ){
		if( !empty( $attributes['isPrice'] ) ){
			ob_start(); ?>
			<div class='productPrice'>
				<?php echo $product->get_price_html(); ?>
			</div>
		<?php return ob_get_clean();
		}else{
			return '';
		}
	} // Product Price

	function productAddToCartArea( $product, $attributes ) {
		$attr = [
			'aria-label'		=> $product->add_to_cart_description(),
			'data-quantity'		=> '1',
			'data-product_id'	=> $product->get_id(),
			'data-product_sku'	=> $product->get_sku(),
			'rel'				=> 'nofollow',
			'class'				=> 'button add_to_cart_button',
		];

		if (
			$product->supports( 'ajax_add_to_cart' ) &&
			$product->is_purchasable() &&
			( $product->is_in_stock() || $product->backorders_allowed() )
		) {
			$attr['class'] .= ' ajax_add_to_cart';
		}

		if( !empty( $attributes['isAddToCartBtn'] ) ){
			ob_start(); ?>
			<div class='productAddToCartArea'>
				<a href='<?php echo esc_url( $product->add_to_cart_url() ); ?>' <?php echo wc_implode_html_attributes( $attr ); ?>>
					<?php echo esc_html( $product->add_to_cart_text() ); ?>
				</a>
			</div>
		<?php return ob_get_clean();
		}else{
			return '';
		}
	} // Add To Cart Button

	function productOnSale( $product, $attributes ) {
		if( $product->is_on_sale() ){
			ob_start(); ?>
			<div class='productOnSale'>
				<span aria-hidden='true'>
					<?php echo esc_html__( 'Sale', 'popular-products' ); ?>
				</span>

				<span class='screen-reader-text'>
					<?php echo esc_html__( 'Product on sale', 'popular-products' ); ?>
				</span>
			</div>
		<?php return ob_get_clean();
		}else{
			return '';
		}
	} // Product On Sale
}
new WPPBlock();