<?php
class Sunshine {

	public $version = 0;
	public $options = array();
	public $errors = array();
	public $messages = array();
	public $base_url = '';
	public $pages = array();
	public $favorites = array();
	public $shipping = array();
	public $cart = array();
	public $is_pro;
	public $notices;
	public $comment_status;

	public $session = array();
	public $current_gallery = array();
	public $current_image = array();
	public $current_order = array();

	public function __construct() {

		$this->options = apply_filters( 'sunshine_option_values', get_option( 'sunshine_options' ) );
		$this->options['endpoint_gallery'] = ( !empty( $this->options['endpoint_gallery'] ) ) ? $this->options['endpoint_gallery'] : 'gallery';
		$this->options['endpoint_image'] = ( !empty( $this->options['endpoint_image'] ) ) ? $this->options['endpoint_image'] : 'image';
		$this->options['endpoint_order'] = ( !empty( $this->options['endpoint_order'] ) ) ? $this->options['endpoint_order'] : 'purchase';

		$this->version = get_option( 'sunshine_version' );

		$this->errors = (array) SunshineSession::instance()->errors;
		$this->messages = (array) SunshineSession::instance()->messages;

		if ( !is_admin() ) {
			add_filter( 'wp_redirect', array( $this, 'redirect' ), 1, 2 );
		}

		add_action( 'init', array( $this, 'init' ), 0 );

	}

	function init() {
		$this->post_types();
		$this->image_sizes();

		$this->set_base_url();
		$this->set_pages();
		$this->shipping = new SunshineShipping();
		$this->cart = new SunshineCart();

		add_filter( 'intermediate_image_sizes', array( $this, 'sunshine_image_sizes' ), 999, 1 );
		add_action( 'sunshine_before_content', array( $this, 'show_messages' ) );

	}

	public function post_types() {

		/* SUNSHINE GALLERIES post type */
		$labels = array(
			'name' => _x( 'Galleries', 'post type general name' ),
			'singular_name' => _x( 'Gallery', 'post type singular name' ),
			'add_new' => _x( 'Add New', 'gallery' ),
			'add_new_item' => __( 'Add New Gallery' ),
			'edit_item' => __( 'Edit Gallery' ),
			'new_item' => __( 'New Gallery' ),
			'all_items' => __( 'All Galleries' ),
			'view_item' => __( 'View Gallery' ),
			'search_items' => __( 'Search Galleries' ),
			'not_found' =>  __( 'No galleries found' ),
			'not_found_in_trash' => __( 'No galleries found in trash' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Galleries' )
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_nav_menus' => true,
			'show_in_menu' => false,
			'query_var' => true,
			'has_archive' => false,
			'hierarchical' => true,
			'register_meta_box_cb' => 'sunshine_gallery_meta_boxes',
			'capability_type' => array( 'sunshine_gallery', 'sunshine_galleries' ),
			'capabilities' => array(
			        'edit_post' => 'edit_sunshine_gallery',
			        'edit_posts' => 'edit_sunshine_galleries',
			        'edit_others_posts' => 'edit_others_sunshine_galleries',
			        'publish_posts' => 'publish_sunshine_gallery',
			        'read_private_posts' => 'read_private_sunshine_galleries',
			        'delete_post' => 'delete_sunshine_gallery',
			        'delete_posts' => 'delete_sunshine_gallery'
			    ),
			'supports' => array( 'title', 'editor', 'page-attributes', 'thumbnail' )
		);
		register_post_type( 'sunshine-gallery', $args );

		/* SUNSHINE_PRODUCTS Custom Post Type */
		$labels = array(
			'name' => _x( 'Products', 'post type general name' ),
			'singular_name' => _x( 'Product', 'post type singular name' ),
			'add_new' => _x( 'Add Single Product', 'product' ),
			'add_new_item' => __( 'Add New Product' ),
			'edit_item' => __( 'Edit Product' ),
			'new_item' => __( 'New Product' ),
			'all_items' => __( 'All Products' ),
			'view_item' => __( 'View Products' ),
			'search_items' => __( 'Search Products' ),
			'not_found' =>  __( 'No products found' ),
			'not_found_in_trash' => __( 'No products found in trash' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Products' )
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_nav_menus' => false,
			'show_in_menu' => false,
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'sunshine_product',
			'capabilities' => array(
			        'edit_post' => 'edit_sunshine_product',
			        'edit_posts' => 'edit_sunshine_products',
			        'edit_others_posts' => 'edit_others_sunshine_products',
			        'publish_posts' => 'publish_sunshine_product',
			        'read_private_posts' => 'read_private_sunshine_products',
			        'delete_post' => 'delete_sunshine_product',
			        'delete_posts' => 'delete_sunshine_product'
			    ),
			'has_archive' => false,
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'thumbnail', 'page-attributes' )
		);
		register_post_type( 'sunshine-product',$args );

		$labels = array(
			'name'             => __( 'Product Categories', 'sunshine' ),
			'singular_name'    => __( 'Product Category', 'sunshine' ),
			'search_items'     =>  __( 'Search Product Categories', 'sunshine' ),
			'all_items'        => __( 'All Product Categories', 'sunshine' ),
			'parent_item'      => __( 'Parent Product Category', 'sunshine' ),
			'parent_item_colon'=> __( 'Parent Product Category:', 'sunshine' ),
			'edit_item'        => __( 'Edit Product Category', 'sunshine' ),
			'update_item'      => __( 'Update Product Category', 'sunshine' ),
			'add_new_item'     => __( 'Add New Product Category', 'sunshine' ),
			'new_item_name'    => __( 'New Product Category Name', 'sunshine' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'sunshine' ),
			'choose_from_most_used' => __( 'Choose from the most used categories', 'sunshine' ),
			'popular_items' => NULL
		);
		$args = array(
			'label' => __( 'Product Category', 'sunshine' ),
			'labels' => $labels,
			'capability_type' => array( 'sunshine_manage_options' ),
			'hierarchical' => true,
			'show_ui'  => true,
			'query_var'=> true
		);
		register_taxonomy( 'sunshine-product-category', 'sunshine-product', $args );

		$labels = array(
			'name'             => __( 'Price Level', 'sunshine' ),
			'singular_name'    => __( 'Price Level', 'sunshine' ),
			'search_items'     =>  __( 'Search Price Levels', 'sunshine' ),
			'all_items'        => __( 'All Price Levels', 'sunshine' ),
			'parent_item'      => __( 'Parent Price Level', 'sunshine' ),
			'parent_item_colon'=> __( 'Parent Price Level:', 'sunshine' ),
			'edit_item'        => __( 'Edit Price Level', 'sunshine' ),
			'update_item'      => __( 'Update Price Level', 'sunshine' ),
			'add_new_item'     => __( 'Add New Price Level', 'sunshine' ),
			'new_item_name'    => __( 'New Price Level', 'sunshine' )
		);
		$args = array(
			'label' => __( 'Price Level', 'sunshine' ),
			'labels' => $labels,
			'capability_type' => array( 'sunshine_manage_options' ),
			'public' => false,
			'hierarchical' => false,
			'show_ui'  => true,
			'query_var'=> true,
			'show_in_nav_menus' => false,
		);
		register_taxonomy( 'sunshine-product-price-level', 'sunshine-product', $args );

		/* SUNSHINE_ORDERS Custom Post Type */
		$labels = array(
			'name' => _x( 'Orders', 'post type general name' ),
			'singular_name' => _x( 'Order', 'post type singular name' ),
			'add_new' => _x( 'Add New', 'order' ),
			'add_new_item' => __( 'Add New Order' ),
			'edit_item' => __( 'Edit Order' ),
			'new_item' => __( 'New Order' ),
			'all_items' => __( 'All Orders' ),
			'view_item' => __( 'View Orders' ),
			'search_items' => __( 'Search Orders' ),
			'not_found' =>  __( 'No orders found' ),
			'not_found_in_trash' => __( 'No orders found in trash' ),
			'parent_item_colon' => '',
			'menu_name' => __( 'Orders' )
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_nav_menus' => false,
			'show_in_menu' => false,
			'query_var' => true,
			'capability_type' => 'sunshine_order',
			'capabilities' => array(
			        'edit_post' => 'edit_sunshine_order',
			        'edit_posts' => 'edit_sunshine_orders',
			        'edit_others_posts' => 'edit_others_sunshine_orders',
			        'publish_posts' => 'publish_sunshine_order',
			        'read_private_posts' => 'read_private_sunshine_orders',
			        'delete_post' => 'delete_sunshine_order',
			        'delete_posts' => 'delete_sunshine_order'
			    ),
			'has_archive' => true,
			'hierarchical' => false,
			'register_meta_box_cb' => 'sunshine_order_meta_boxes',
			'supports' => array( 'comments' )
		);
		register_post_type( 'sunshine-order', $args );

		$labels = array(
			'name'             => __( 'Order Status', 'sunshine' ),
			'singular_name'    => __( 'Order Status', 'sunshine' ),
			'search_items'     =>  __( 'Search Order Status', 'sunshine' ),
			'all_items'        => __( 'All Order Status', 'sunshine' ),
			'parent_item'      => __( 'Parent Order Status', 'sunshine' ),
			'parent_item_colon'=> __( 'Parent Order Status:', 'sunshine' ),
			'edit_item'        => __( 'Edit Order Status', 'sunshine' ),
			'update_item'      => __( 'Update Order Status', 'sunshine' ),
			'add_new_item'     => __( 'Add New Order Status', 'sunshine' ),
			'new_item_name'    => __( 'New Order Status', 'sunshine' )
		);
		$args = array(
			'label' => 'Order Status',
			'labels' => $labels,
			'public' => false,
			'hierarchical' => false,
			'show_ui'  => true,
			'query_var'=> true,
			'show_in_nav_menus' => false
		);
		register_taxonomy( 'sunshine-order-status', 'sunshine-order',$args );

	}

	function image_sizes() {
		// Allow post thumbnails if current theme doesn't have it already
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
		add_image_size( 'sunshine-thumbnail', $this->options['thumbnail_width'], $this->options['thumbnail_height'], $this->options['thumbnail_crop'] );
		if ( is_sunshine() ) {
			set_post_thumbnail_size( $this->options['thumbnail_width'], $this->options['thumbnail_height'], $this->options['thumbnail_crop'] );
		}
	}

	function sunshine_image_sizes( $image_sizes ) {
		if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'sunshine_gallery_upload' || $_POST['action'] == 'sunshine_file_save' ) ) {
			$new_image_sizes = array();
			foreach ( $image_sizes as $image_size ) {
				if ( strpos( $image_size, 'sunshine' ) ) {
					$new_image_sizes[] = $image_size;
				}
			}
			$new_image_sizes[] = 'sunshine-thumbnail';
			$image_sizes = apply_filters( 'sunshine_image_sizes', $new_image_sizes );
		}
		return $image_sizes;
	}

	public function set_pages() {
		$pages = array(
			'home' => $this->options['page'],
			'account' => $this->options['page_account'],
			'cart' => $this->options['page_cart'],
			'checkout' => $this->options['page_checkout']
		);
		$this->pages = apply_filters( 'sunshine_pages', $pages );
	}

	public function add_error( $error ) {
		array_push( $this->errors, $error );
	}

	public function add_message( $message ) {
		array_push( $this->messages, $message );
	}

	public function has_errors() {
		return !empty( $this->errors );
	}

	public function has_messages() {
		return !empty( $this->messages );
	}

	public function clear_messages() {
		$this->errors = array();
		$this->messages = array();
		unset( SunshineSession::instance()->messages );
		unset( SunshineSession::instance()->errors );
	}

	public function show_messages() {
		echo $this->get_messages();
	}

	public function get_messages() {
		$messages = '';
		if ( $this->has_errors() ) {
			$messages = '<div id="sunshine-errors" class="sunshine-messages"><ul>';
			foreach ( $this->errors as $error ) {
				$messages .= '<li>'.$error.'</li>';
			}
			$messages .= '</ul></div>';
		}
		if ( $this->has_messages() ) {
			$messages .= '<div id="sunshine-messages" class="sunshine-messages"><ul>';
			foreach ( $this->messages as $message ) {
				$messages .= '<li>'.$message.'</li>';
			}
			$messages .= '</ul></div>';
		}
		$this->clear_messages();
		return $messages;
	}


	public function redirect( $location, $status = NULL ) {
		SunshineSession::instance()->errors = $this->errors;
		SunshineSession::instance()->messages = $this->messages;
		return $location;
	}

	public function is_pro() {
		if ( !empty( $this->is_pro ) ) {
			return $this->is_pro;
		}
		$this->is_pro = ( get_option( 'sunshine_pro_license_active' ) == 'valid' ) ? true : false;
		return $this->is_pro;
	}

	public function set_base_url() {
		$this->base_url = get_permalink( $this->options['page'] );
	}

	function install() {

		global $sunshine;

		$this->post_types();

		update_option( 'sunshine_version', SUNSHINE_VERSION );

		flush_rewrite_rules();

		// Capabilities
		$sub = get_role( 'subscriber' );
		$sub->add_cap( 'read_private_sunshine_galleries' );
		$sub->add_cap( 'edit_others_posts' ); // Workaround to let users see attachments of private galleries

		$admin = get_role( 'administrator' );
		add_role( 'sunshine_manager', 'Sunshine Manager' );
		$manager = get_role( 'sunshine_manager' );

		$admin_rules = array(
			'edit_sunshine_gallery',
			'read_sunshine_gallery',
			'delete_sunshine_gallery',
			'edit_sunshine_galleries',
			'edit_others_sunshine_galleries',
			'publish_sunshine_galleries',
			'publish_sunshine_gallery',
			'read_private_sunshine_galleries',
			'delete_sunshine_galleries',
			'delete_private_sunshine_galleries',
			'delete_published_sunshine_galleries',
			'delete_others_sunshine_galleries',
			'edit_private_sunshine_galleries',
			'edit_published_sunshine_galleries',
			'edit_sunshine_product',
			'read_sunshine_product',
			'delete_sunshine_product',
			'edit_sunshine_products',
			'edit_others_sunshine_products',
			'publish_sunshine_products',
			'publish_sunshine_product',
			'read_private_sunshine_products',
			'delete_sunshine_products',
			'delete_private_sunshine_products',
			'delete_published_sunshine_products',
			'delete_others_sunshine_products',
			'edit_private_sunshine_products',
			'edit_published_sunshine_products',
			'edit_sunshine_order',
			'read_sunshine_order',
			'delete_sunshine_order',
			'edit_sunshine_orders',
			'edit_others_sunshine_orders',
			'publish_sunshine_orders',
			'publish_sunshine_order',
			'read_private_sunshine_orders',
			'delete_sunshine_orders',
			'delete_private_sunshine_orders',
			'delete_published_sunshine_orders',
			'delete_others_sunshine_orders',
			'edit_private_sunshine_orders',
			'edit_published_sunshine_orders',
			'sunshine_manage_options',
			'read'
		);
		foreach ( $admin_rules as $rule ) {
			$admin->add_cap( $rule );
			$manager->add_cap( $rule );
		}

		// Default options
		$options = get_option( 'sunshine_options' );

		if ( !$options['page'] ) {
			$options['page'] = wp_insert_post( array(
					'post_title' => 'Client Galleries',
					'post_content' => '<p>'.__( 'This is a placeholder page that Sunshine uses to display itself. You can edit this text by going to Pages > Client Galleries.','sunshine' ).'</p>',
					'post_type' => 'page',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_status' => 'publish'
				) );
		}
		if ( !$options['page_account'] ) {
			$options['page_account'] = wp_insert_post( array(
					'post_title' => __( 'Account','sunshine' ),
					'post_content' => '',
					'post_type' => 'page',
					'post_status' => 'publish',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_parent' => $options['page']
				) );
		}
		if ( !$options['page_cart'] ) {
			$options['page_cart'] = wp_insert_post( array(
					'post_title' => __( 'Cart','sunshine' ),
					'post_content' => '',
					'post_type' => 'page',
					'post_status' => 'publish',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_parent' => $options['page']
				) );
		}
		if ( !$options['page_checkout'] ) {
			$options['page_checkout'] = wp_insert_post( array(
					'post_title' => __( 'Checkout','sunshine' ),
					'post_content' => '',
					'post_type' => 'page',
					'post_status' => 'publish',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_parent' => $options['page']
				) );
		}
		if ( !$options['rows'] )
			$options['rows'] = 5;
		if ( !$options['columns'] )
			$options['columns'] = 4;
		if ( !$options['thumbnail_width'] )
			$options['thumbnail_width'] = 400;
		if ( !$options['thumbnail_height'] )
			$options['thumbnail_height'] = 300;
		if ( !$options['thumbnail_crop'] )
			$options['thumbnail_crop'] = 1;
		if ( !$options['email_register'] )
			$options['email_register'] = __( 'Thank you for registering! You can now mark your favorite photos and make purchases on the website.','sunshine' );
		if ( !$options['email_receipt'] )
			$options['email_receipt'] = __( 'Thank you for your order! Below is a summary of the order for your records. You will receive a separate email when your item(s) have shipped.','sunshine' );
		if ( !$options['email_signature'] )
			$options['email_signature'] = __( 'Thank you!','sunshine' );
		if ( !$options['from_email'] )
			$options['from_email'] = get_bloginfo( 'admin_email' );
		if ( !$options['from_name'] )
			$options['from_name'] = get_bloginfo( 'name' );
		if ( !$options['header_footer'] )
			$options['header_footer'] = 'standard';
		if ( !$options['currency'] )
			$options['currency'] = 'USD';
		if ( !$options['currency_symbol_position'] )
			$options['currency_symbol_position'] = 'left';
		if ( !$options['currency_thousands_separator'] )
			$options['currency_thousands_separator'] = ',';
		if ( !$options['currency_decimal_separator'] )
			$options['currency_decimal_separator'] = '.';
		if ( !$options['currency_decimals'] )
			$options['currency_decimals'] = '2';
		if ( !$options['share_gallery'] )
			$options['share_gallery'] = '0';
		if ( !$options['share_image'] )
			$options['share_image'] = '0';
		if ( !$options['theme'] )
			$options['theme'] = 'theme';
		if ( !$options['flat_rate_name'] )
			$options['flat_rate_name'] = __( 'Flat Rate Shipping','sunshine' );
		if ( !$options['local_name'] )
			$options['local_name'] = __( 'Local Delivery','sunshine' );
		if ( !$options['pickup_name'] )
			$options['pickup_name'] = __( 'Local Pickup','sunshine' );
		if ( !$options['offline_name'] )
			$options['offline_name'] = __( 'Offline','sunshine' );
		if ( !$options['offline_desc'] )
			$options['offline_desc'] = __( 'Send payment outside of website (ie, check, phone call, other)','sunshine' );
		if ( !$options['paypal_name'] )
			$options['paypal_name'] = __( 'PayPal','sunshine' );
		if ( !$options['paypal_desc'] )
			$options['paypal_desc'] = __( 'Submit payment via PayPal account or use a credit card','sunshine' );
		if ( !$options['stripe_name'] )
			$options['stripe_name'] = 'Stripe';
		if ( !$options['stripe_desc'] )
			$options['stripe_desc'] = __( 'Pay by credit card (Visa, MasterCard, American Express, Discover, JCB, and Diners Club)','sunshine' );
		if ( !$options['payjunction_name'] )
			$options['payjunction_name'] = 'PayJunction';
		if ( !$options['payjunction_desc'] )
			$options['payjunction_desc'] = __( 'Pay by credit card (Visa, MasterCard, American Express, Discover)','sunshine' );
		if ( !$options['country'] )
			$options['country'] = 'US';
		if ( isset( $options['tax_state'] ) && $options['tax_state'] != '' )
			$options['tax_location'] = 'US|'.$options['tax_state'];
		if ( !$options['tax_basis'] )
			$options['tax_basis'] = 'shipping';
		if ( !$options['display_price'] )
			$options['display_price'] = 'without_tax';
		if ( !$options['price_has_tax'] )
			$options['price_has_tax'] = 'no';
		if ( !$options['email_subject_register'] )
			$options['email_subject_register'] = __( 'New user account info at [sitename]','sunshine' );
		if ( !$options['email_subject_order_receipt'] )
			$options['email_subject_order_receipt'] = __( 'Receipt for order #[order_id] from [sitename]','sunshine' );
		if ( !$options['email_subject_order_status'] )
			$options['email_subject_order_status'] = __( 'Your order #[order_id] from [sitename] has been updated','sunshine' );
		if ( !$options['email_subject_order_comment'] )
			$options['email_subject_order_comment'] = __( 'A new comment on order #[order_id] at [sitename]','sunshine' );

		if ( !$options['allowed_countries'] )
			$options['allowed_countries'] = 'all';
		if ( !$options['billing_fields'] || !array_search( 1, maybe_unserialize( $options['billing_fields' ] ) ) )
			$options['billing_fields'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'address2' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );
		if ( !$options['shipping_fields'] || !array_search( 1, maybe_unserialize( $options['shipping_fields' ] ) ) )
			$options['shipping_fields'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'address2' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );
		if ( !$options['billing_fields_required'] || !array_search( 1, maybe_unserialize( $options['billing_fields_required' ] ) ) )
			$options['billing_fields_required'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );
		if ( !$options['shipping_fields_required'] || !array_search( 1, maybe_unserialize( $options['shipping_fields_required' ] ) ) )
			$options['shipping_fields_required'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );

		if ( !isset( $options['endpoint_gallery'] ) ) {
			$post_types = get_post_types();
			foreach ( $post_types as $post_type ) {
				if ( $post_type == 'gallery' )
					$options['endpoint_gallery'] = 'sgallery';
			}
		}

		$options = apply_filters( 'sunshine_install_options', $options );
		update_option( 'sunshine_options', $options );

		if ( !term_exists( 'pending', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Pending','sunshine' ), 'sunshine-order-status', array( 'slug' => 'pending', 'description' => __( 'We have received your order but payment is still pending','sunshine' ) ) );
		if ( !term_exists( 'new', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'New','sunshine' ), 'sunshine-order-status', array( 'slug' => 'new', 'description' => __( 'We have received your order and payment','sunshine' ) ) );
		if ( !term_exists( 'processing', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Processing','sunshine' ), 'sunshine-order-status', array( 'slug' => 'processing', 'description' => __( 'The images in your order are being processed and/or printed','sunshine' ) ) );
		if ( !term_exists( 'shipped', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Shipped/Completed','sunshine' ), 'sunshine-order-status', array( 'slug' => 'shipped', 'description' => __( 'Your items have shipped (or are available for download)!','sunshine' ) ) );
		if ( !term_exists( 'cancelled', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Cancelled/Refunded','sunshine' ), 'sunshine-order-status', array( 'slug' => 'cancelled', 'description' => __( 'Your order was cancelled and/or refunded','sunshine' ) ) );
		if ( !term_exists( 'pickup', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Ready for pickup','sunshine' ), 'sunshine-order-status', array( 'slug' => 'pickup', 'description' => __( 'Your order is ready to be picked up','sunshine' ) ) );

		if ( !$terms = get_terms( 'sunshine-product-price-level', array( 'hide_empty' => 0 ) ) ) {
			wp_insert_term( __( 'Default','sunshine' ), 'sunshine-product-price-level' );
		}

		$upload_dir = wp_upload_dir();
		if ( !is_dir( $upload_dir['basedir'].'/sunshine' ) )
			wp_mkdir_p( $upload_dir['basedir'].'/sunshine' );

		update_option( 'sunshine_install_time', current_time( 'timestamp' ) );
		update_option( 'sunshine_install_redirect', 1 );

		do_action( 'sunshine_install' );

		flush_rewrite_rules();

	}

	function update() {

		global $sunshine, $wpdb;

		flush_rewrite_rules();

		$admin = get_role( 'administrator' );
		add_role( 'sunshine_manager', 'Sunshine Manager' );
		$manager = get_role( 'sunshine_manager' );

		$admin_rules = array(
			'edit_sunshine_gallery',
			'read_sunshine_gallery',
			'delete_sunshine_gallery',
			'edit_sunshine_galleries',
			'edit_others_sunshine_galleries',
			'publish_sunshine_galleries',
			'publish_sunshine_gallery',
			'read_private_sunshine_galleries',
			'delete_sunshine_galleries',
			'delete_private_sunshine_galleries',
			'delete_published_sunshine_galleries',
			'delete_others_sunshine_galleries',
			'edit_private_sunshine_galleries',
			'edit_published_sunshine_galleries',
			'edit_sunshine_product',
			'read_sunshine_product',
			'delete_sunshine_product',
			'edit_sunshine_products',
			'edit_others_sunshine_products',
			'publish_sunshine_products',
			'publish_sunshine_product',
			'read_private_sunshine_products',
			'delete_sunshine_products',
			'delete_private_sunshine_products',
			'delete_published_sunshine_products',
			'delete_others_sunshine_products',
			'edit_private_sunshine_products',
			'edit_published_sunshine_products',
			'edit_sunshine_order',
			'read_sunshine_order',
			'delete_sunshine_order',
			'edit_sunshine_orders',
			'edit_others_sunshine_orders',
			'publish_sunshine_orders',
			'publish_sunshine_order',
			'read_private_sunshine_orders',
			'delete_sunshine_orders',
			'delete_private_sunshine_orders',
			'delete_published_sunshine_orders',
			'delete_others_sunshine_orders',
			'edit_private_sunshine_orders',
			'edit_published_sunshine_orders',
			'sunshine_manage_options',
			'read'
		);
		foreach ( $admin_rules as $rule ) {
			$admin->add_cap( $rule );
			$manager->add_cap( $rule );
		}

		// Default options
		$options = get_option('sunshine_options');
		$version = get_option('sunshine_version');

		if ( !term_exists( 'pending', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Pending','sunshine' ), 'sunshine-order-status', array( 'slug' => 'pending', 'description' => __( 'We have received your order but payment is still pending','sunshine' ) ) );
		if ( !term_exists( 'new', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'New','sunshine' ), 'sunshine-order-status', array( 'slug' => 'new', 'description' => __( 'We have received your order and payment','sunshine' ) ) );
		if ( !term_exists( 'processing', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Processing','sunshine' ), 'sunshine-order-status', array( 'slug' => 'processing', 'description' => __( 'The images in your order are being processed and/or printed','sunshine' ) ) );
		if ( !term_exists( 'shipped', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Shipped/Completed','sunshine' ), 'sunshine-order-status', array( 'slug' => 'shipped', 'description' => __( 'Your items have shipped (or are available for download)!','sunshine' ) ) );
		if ( !term_exists( 'cancelled', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Cancelled/Refunded','sunshine' ), 'sunshine-order-status', array( 'slug' => 'cancelled', 'description' => __( 'Your order was cancelled and/or refunded','sunshine' ) ) );
		if ( !term_exists( 'pickup', 'sunshine-order-status' ) )
			$term[] = wp_insert_term( __( 'Ready for pickup','sunshine' ), 'sunshine-order-status', array( 'slug' => 'pickup', 'description' => __( 'Your order is ready to be picked up','sunshine' ) ) );

		if ( !$terms = get_terms( 'sunshine-product-price-level', array( 'hide_empty' => 0 ) ) ) {
			wp_insert_term( __( 'Default','sunshine' ), 'sunshine-product-price-level' );
		}

		if ( version_compare($version, '1.9.5', '<') ) {

			if (!isset($options['endpoint_gallery'])) {
				$post_types = get_post_types();
				foreach ($post_types as $post_type) {
					if ($post_type == 'gallery')
						$options['endpoint_gallery'] = 'sgallery';
				}
			}

		}

		if ( version_compare($version, '1.9.6', '<') ) {

			$wpdb->query(
				$wpdb->prepare("DELETE FROM $wpdb->usermeta WHERE meta_key = %s", 'sunshine_card_number')
			);

			// Changes for all galleries
			$args = array(
				'post_type' => 'sunshine-gallery',
				'nopaging' => true
			);
			$the_query = new WP_Query( $args );
			while ( $the_query->have_posts() ) : $the_query->the_post();
				// Change values for gallery access
				$require_account = get_post_meta(get_the_ID(), 'sunshine_gallery_require_account', true);
				if ($require_account)
					update_post_meta(get_the_ID(), 'sunshine_gallery_access', 'account');
			endwhile; wp_reset_postdata();
		}

		if ( version_compare($version, '2.2.5', '<') ) {
			// Changes for all galleries
			$args = array(
				'post_type' => 'sunshine-gallery',
				'has_password' => true,
				'nopaging' => true
			);
			$the_query = new WP_Query( $args );
			while ( $the_query->have_posts() ) : $the_query->the_post();
				update_post_meta(get_the_ID(), 'sunshine_gallery_access', 'password');
			endwhile; wp_reset_postdata();
		}

		if ( version_compare($version, '2.2.10', '<') ) {
			// Changes for requiring email for all galleries
			$args = array(
				'post_type' => 'sunshine-gallery',
				'nopaging' => true
			);
			$the_query = new WP_Query( $args );
			while ( $the_query->have_posts() ) : $the_query->the_post();
				$access = get_post_meta( get_the_ID(), 'sunshine_gallery_access', true );
				$require_email = get_post_meta( get_the_ID(), 'sunshine_gallery_require_email', true );
				update_post_meta( get_the_ID(), 'sunshine_gallery_status', $access );
				delete_post_meta( get_the_ID(), 'sunshine_gallery_access' );
				if ( $access == 'account' ) {
					$new_access[] = 'account';
				}
				if ( $require_email ) {
					$new_access[] = 'email';
				}
				if ( is_array( $new_access ) ) {
					update_post_meta( get_the_ID(), 'sunshine_gallery_access', $new_access );
				}
			endwhile; wp_reset_postdata();
		}

		if ( version_compare($version, '2.2.7', '<') ) {
			// Changes for requiring email for all galleries
			$args = array(
				'post_type' => 'sunshine-gallery',
				'nopaging' => true
			);
			$the_query = new WP_Query( $args );
			while ( $the_query->have_posts() ) : $the_query->the_post();
				$access = get_post_meta( get_the_ID(), 'sunshine_gallery_access', true );
				if ( $access == 'email' ) {
					update_post_meta( get_the_ID(), 'sunshine_gallery_access', '' );
					update_post_meta( get_the_ID(), 'sunshine_gallery_require_email', '1' );
				}
			endwhile; wp_reset_postdata();
		}

		if ( version_compare($version, '2.4', '<') ) {
			update_option('sunshine_update_image_location', 'yes' );
		}

		if ( version_compare($version, '2.5.4', '<') ) {
			if ( empty( $options['sharing_services'] ) ) {
				$options['sharing_services'] = array( 'facebook', 'twitter', 'pinterest', 'google' );
			}
		}

		if ( version_compare( $version, '2.5.7', '<' ) ) {
			if ( !$options['allowed_countries'] )
				$options['allowed_countries'] = 'all';
			if ( !$options['tax_basis'] )
				$options['tax_basis'] = 'shipping';

			if ( !$options['billing_fields'] || !array_search( 1, maybe_unserialize( $options['billing_fields' ] ) ) )
				$options['billing_fields'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'address2' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );
			if ( !$options['shipping_fields'] || !array_search( 1, maybe_unserialize( $options['shipping_fields' ] ) ) )
				$options['shipping_fields'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'address2' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );
			if ( !$options['billing_fields_required'] || !array_search( 1, maybe_unserialize( $options['billing_fields_required' ] ) ) )
				$options['billing_fields_required'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );
			if ( !$options['shipping_fields_required'] || !array_search( 1, maybe_unserialize( $options['shipping_fields_required' ] ) ) )
				$options['shipping_fields_required'] = array( 'country' => 1, 'first_name' => 1, 'last_name' => 1, 'address' => 1, 'city' => 1, 'state' => 1, 'zip' => 1 );
		}

		if ( version_compare( $version, '2.6', '<' ) ) {
			$options['other_fields_required'] = array( 'phone' => 1 );
		}

		if ( !$options['display_price'] )
			$options['display_price'] = 'without_tax';
		if ( !$options['price_has_tax'] )
			$options['price_has_tax'] = 'no';


		$options = apply_filters( 'sunshine_update_options', $options );
		update_option( 'sunshine_options', $options );

		update_option( 'sunshine_version', SUNSHINE_VERSION );
		$sunshine->version = SUNSHINE_VERSION;

		do_action( 'sunshine_update' );

		wp_redirect( admin_url( '/admin.php?page=sunshine_about&sunshine_updated' ) );
		exit;

	}

}
?>
