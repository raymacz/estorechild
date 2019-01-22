<?php
/**
 * Child theme functions
 *
 * @since    1.0.0
 * @version  1.0.0
 */

/**
 * Enqueue parent theme stylesheet
 *
 * This runs only if parent theme does not claim support for
 * `child-theme-stylesheet`, and so we need to enqueue this
 * child theme's `style.css` file ourselves.
 *
 * If parent theme supports `child-theme-stylesheet`, it enqueues
 * this child theme's `style.css` file automatically.
 *
 * @since    1.0.0
 * @version  1.0.0
 */

 // child functions.php is loaded first before parent, that's why all scripts are called forth from child first.
function rbtm_eschild_enqueue_styles() {
    $parent_style = 'rbtm_eschild-style'; // This is 'boot2wp-style' for the B2W theme.
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( $parent_style ), wp_get_theme()->get('Version'));
    wp_enqueue_style( 'rbtm_eschild-google-fonts', 'https://fonts.googleapis.com/css?family=Raleway:400,700' );
}

//load the scripts from parent to child
add_action( 'wp_enqueue_scripts', 'rbtm_eschild_enqueue_styles' );

// change or add email template styles
add_filter('woocommerce_email_styles', function($css){
    // var_dump($css); //rbtm
    return $css;
});

// fix &times; in woocommerce dimensions
add_filter( 'woocommerce_format_dimensions', 'rbtm_eschild_change_formated_product_dimentions', 10, 2 );
function rbtm_eschild_change_formated_product_dimentions( $dimension_string, $dimensions ){
    if ( empty( $dimension_string ) )
        return __( 'N/A', 'woocommerce' );
    $dimensions = array_filter( array_map( 'wc_format_localized_decimal', $dimensions ) );
//    var_dump($dimensions);//    var_dump($dimension_string);
    return implode( ' x ',  $dimensions ) . get_option( 'woocommerce_dimension_unit' );
}

// move single meta template
remove_action('woocommerce_single_product_summary','woocommerce_template_single_meta',40);
add_action('woocommerce_after_single_product_summary', 'woocommerce_template_single_meta', 12);

// remove description tab with a particular product post
add_filter('woocommerce_product_tabs', function($tabs){
   global $product;
//   var_dump($tabs);
    if (is_single() && get_the_id()==1848) {
        unset($tabs['description']);
    }
//    $tabs['reviews']['title']= __('Success Stories ').'('. $product->get_review_count().')';
    $tabs['reviews']['title']=  sprintf( '%1$s %2$s', __('Success Stories'), ($product->get_review_count()>0)? '('.$product->get_review_count().')' : '');
    return $tabs;
 }, 100);

 // changing the breadcrumb delimeter
 add_filter('woocommerce_breadcrumb_defaults', function( $breadcrumb ){
     // var_dump($breadcrumb);
     $breadcrumb['delimiter'] = ' &gt ';
     return $breadcrumb;
 });

// customize currency - dollar symbol

add_filter( 'woocommerce_currency_symbol', function($currency_symbol, $currency){
    $currency_symbol = 'USD $';
    return $currency_symbol;
}, 30,  2 );


// change products per page

add_filter( 'loop_shop_per_page', function($num_per_page){
  return 9;
}, $priority = 20 );

// change products # of columns

add_filter( 'loop_shop_columns', function($cols){
  return 3;
}, $priority = 20 );

// Display amount saved from woocommerce_sale_price_html to woocommerce_get_price_html
// display savings by % or  amount
add_filter( 'woocommerce_get_price_html', function($html, $product){
 // echo "<pre>";
 // print $product->regular_price.' rp ';
 // print $product->sale_price. ' sp ';
 // echo "</pre>";
  if (($product->regular_price - $product->sale_price) > 999) {
        $value_int =  round($product->regular_price - $product->sale_price);
        $value = 'USD $'.number_format((float)$value_int, 2);
  } else {
        $value_int = round( ($product->regular_price - $product->sale_price)/$product->regular_price * 100 );
        $value = $value_int.'%';
  }
//
  if ((intval($value_int))<=0 || (intval($product->sale_price))==0) return $html;
  // if ($value_int<=0 || $product->sale_price==0) return $html;
  return $html.sprintf( __('<span class="saveprice"> >> Save %s << </span>', 'woocommerce'), $value);
},  10, 2 );


//remove orderby in sorting & adding a new sort functionality
add_filter( 'woocommerce_catalog_orderby', function($orderby) {
  // print_r($orderby);
  unset($orderby['price-desc']);
  $orderby['date']= __('Sort by date: newest to oldest','woocommerce');
  $orderby['oldest_to_recent'] = __('Sort by date: oldest to newest','woocommerce');
  return $orderby;
}, 20, 1 );

add_filter('woocommerce_get_catalog_ordering_args', function($args) {

  $orderby_value = isset( $_GET['orderby'] ) ? wc_clean( (string) wp_unslash( $_GET['orderby'] ) ) : wc_clean( get_query_var( 'orderby' ) ); // WPCS: sanitization ok, input var ok, CSRF ok.

			if ( ! $orderby_value ) {
				if ( is_search() ) {
					$orderby_value = 'relevance';
				} else {
					$orderby_value = apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
				}
			}

  if ($orderby_value == 'oldest_to_recent') {
       var_dump($args);
       $args    = array(
			'orderby'  => 'date',
			'order'    => 'ASC',
			'meta_key' => '',
		);
  }
  return $args;
}, 20, 1);

// add "empty cart" functionality
add_action('woocommerce_cart_actions', function(){
    echo '<a class="button" href="?empty-cart=true">'. __('Empty Cart', 'wooocommerce') . '</a>';
}, 20, 1);

add_action('init', function(){
  global $woocommerce;
  if (isset($_GET['empty-cart'])) {
    $woocommerce->cart->empty_cart();
  }
},20, 1);


// remove phone field functionality in woocommerce
add_filter( 'woocommerce_checkout_fields', function($myfield) {
// var_dump($myfield['billing']['billing_phone']);
  unset($myfield['billing']['billing_phone']);
// make the billing full-width
  var_dump($myfield['billing']['billing_email']);
  return $myfield;
}, 20, 1 );

// Add "how did you hear about us" new field in woocommerce

add_filter( 'woocommerce_checkout_fields', function($myfield) {
    
// make the billing full-width
  $myfield['order']['hear_about_us'] = array (
    'type'  => 'select',
    'class' => array ('form-row-hide'),
    'label' => 'How did you hear about us?',
    'options' => array (
         'default' => '-- select and option --',
         'tv' => 'TV',
         'radio' => 'Radio',
         'podcasts' => 'Billboards',
         'billboards' => 'Podcasts',
         'research' => 'Research Online',
    )
  );
  var_dump($myfield['order']);
  return $myfield;
}, 20, 1 );


?>
