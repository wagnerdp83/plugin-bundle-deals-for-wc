<?php
class WC_Bundle_Deals_Frontend {

    public function __construct() {
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles_scripts']);
        add_shortcode('display_bundle_deals', [$this, 'display_bundle_deals_shortcode']);

        // Hooks to display bundle deals and apply discounts
        add_action('woocommerce_after_add_to_cart_button', [$this, 'display_bundle_deal'], 25);

        // Remove WooCommerce add to cart action and add custom handling
        remove_action('wp_loaded', ['WC_Form_Handler', 'add_to_cart_action'], 20);
        add_action('wp_loaded', [$this, 'woocommerce_add_multiple_products_to_cart'], 15);

        add_action('woocommerce_add_to_cart_validation', [$this, 'check_product_added_to_cart'], 10, 3);
        add_action('wp_loaded', [$this, 'woocommerce_add_coupon_links'], 30);
        add_action('woocommerce_add_to_cart', [$this, 'woocommerce_add_coupon_links']);
    }

    public function display_bundle_deals_shortcode() {
        ob_start();
        $this->display_bundle_deals();
        return ob_get_clean();
    }

    public function get_bundle_coupons() {
		$args = [
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => 'discount_type',
					'value'   => 'fixed_cart', // Adjust based on coupon type
					'compare' => '=',
				],
				[
					'key'     => 'product_ids',
					'value'   => '', // Filter for products later
					'compare' => '!=', // Ensure it has products attached
				],
				[
					'key'     => '_wc_bundle_active',  // Meta key for active status
					'value'   => 'active',             // Only show active bundles
					'compare' => '=',                  // Match only if it's active
				],
			],
		];

		$coupons = get_posts($args);

		// Log the retrieved coupons for debugging
		if (WP_DEBUG) {
			error_log('Retrieved Coupons: ' . print_r($coupons, true));
		}

		return $coupons;
	}

    public function enqueue_styles_scripts() {
        wp_enqueue_style('wc-bundle-deals', plugins_url('/assets/css/wc-bundle-deals.css', __FILE__));
        wp_enqueue_script('wc-bundle-deals', plugins_url('/assets/js/wc-bundle-deals.js', __FILE__), ['jquery'], null, true);

        // Prepare data to pass to JavaScript
        wp_localize_script('wc-bundle-deals', 'wc_bundle_deals_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wc_bundle_deals_nonce'),
            'cart_url' => wc_get_cart_url(),
        ]);
    }

    public function calculate_total_price_after_discount($product_ids, $discount) {
        $total_price = array_reduce($product_ids, function ($total, $product_id) {
            $product = wc_get_product($product_id);
            return $total + ($product ? $product->get_price() : 0);
        }, 0);

        // Apply the discount
        return max(0, $total_price - $discount); // Ensure the price doesn't go below zero
    }

    public function display_bundle_deal() {
        static $is_bundle_displayed = false;

        if ($is_bundle_displayed) {
            return; // Exit if the bundle has already been displayed
        }

        $is_bundle_displayed = true;
        global $product;

        $coupons = $this->get_bundle_coupons();
        $current_product_id = $product->get_id();

        // Initialize an array to collect all matching bundles
        $matching_bundles = [];

        foreach ($coupons as $coupon) {
            $coupon_obj = new WC_Coupon($coupon->post_title);
            $product_ids = $coupon_obj->get_product_ids();
            $discount = $coupon_obj->get_amount();

            if (in_array($current_product_id, $product_ids)) {
                // Dynamically retrieve image IDs for the current coupon
                $image_ids_serialized = get_post_meta($coupon->ID, 'wc_bundle_deal_images', true);
                $image_ids = is_string($image_ids_serialized) ? maybe_unserialize($image_ids_serialized) : $image_ids_serialized;
                $image_ids = is_array($image_ids) ? $image_ids : [];

                $matching_bundles[] = [
                    'coupon'       => $coupon->post_title,
                    'product_ids'  => $product_ids,
                    'discount'     => $discount,
                    'image_ids'    => $image_ids,
                ];
            }
        }
		
		if (!empty($matching_bundles)) {
        
			echo '<div class="product-bundle">';
        	echo '<h3>' . esc_html__('Bundle Deals', 'woocommerce') . '</h3>';

        
            foreach ($matching_bundles as $bundle) {
                echo '<div class="bundle">';
                echo '<div class="columns">';

                $total_images = count($bundle['image_ids']);

                if ($total_images > 0) {
                    foreach ($bundle['image_ids'] as $index => $image_id) {
                        $image_url = wp_get_attachment_url($image_id);
                        $bundle_product = wc_get_product($bundle['product_ids'][$index] ?? 0); // Get the product associated with this image

                        if ($image_url && $bundle_product) {
                            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($bundle_product->get_name()) . '" style="width: 115px; height: 140px; object-fit: cover;" />';

                            // Display the <span>+</span> if there are more than 2 images and this is not the last image
                            if ($total_images > 1 && $index < $total_images - 1) {
                                echo '<span>+</span>';
                            }
                        }
                    }
                } else {
                    echo 'No image data available.';
                }

                echo '</div>'; // .columns
                echo '<div class="cta">';
				
                echo '<p class="sale-desc">' . esc_html((count($bundle['product_ids']) === 2 ? 'Buy Both For' : 'Buy All For')) . ' ' . wp_kses_post(wc_price($this->calculate_total_price_after_discount($bundle['product_ids'], $bundle['discount']))) . ' - ' . esc_html__('Save', 'text-domain') . ' ' . wp_kses_post(wc_price($bundle['discount'])) . '</p>';

				
				
                echo '<p class="add_to_cart">';

                $product_ids_str = implode(',', $bundle['product_ids']);
                echo '<a class="add_bundle_to_cart button" title="' . esc_attr($bundle['coupon']) . '" href="' . esc_url(wc_get_cart_url()) . '?add-to-cart=' . esc_attr($product_ids_str) . '&coupon_code=' . esc_attr($bundle['coupon']) . '">' . esc_html($product->single_add_to_cart_text()) . '</a>';

                echo '</p>';
                echo '</div>'; // .cta
                echo '</div>'; // .bundle
            }
        } else {
            error_log("Product ID " . $current_product_id . " is not part of any bundle.");
        }

        echo '</div>'; // .product-bundle
    }

    public function woocommerce_add_multiple_products_to_cart() {
        // Ensure WooCommerce is installed and the 'add-to-cart' parameter is present
        if (!class_exists('WC_Form_Handler') || empty($_REQUEST['add-to-cart'])) {
            return;
        }

        // Parse the 'add-to-cart' parameter
        $product_ids = array_map('absint', explode(',', $_REQUEST['add-to-cart']));
        $count = count($product_ids);

        foreach ($product_ids as $index => $product_id) {
            if ($index === $count - 1) {
                // Handle the last product separately
                $_REQUEST['add-to-cart'] = $product_id;
                return WC_Form_Handler::add_to_cart_action();
            }

            $product_id = apply_filters('woocommerce_add_to_cart_product_id', $product_id);
            $adding_to_cart = wc_get_product($product_id);

            if (!$adding_to_cart) {
                continue;
            }

            $quantity = !empty($_REQUEST['quantity']) ? wc_stock_amount($_REQUEST['quantity']) : 1;
            $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);

            if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity)) {
                wc_add_to_cart_message([$product_id => $quantity], true);
            }
        }
    }

    public function check_product_added_to_cart($passed, $product_id, $quantity) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['data']->get_id() == $product_id) {
                return false; // Product already in cart
            }
        }
        return $passed;
    }

    public function woocommerce_add_coupon_links() {
        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        $query_var = apply_filters('woocommerce_coupon_links_query_var', 'coupon_code');
        $coupon_code = isset($_GET[$query_var]) ? sanitize_text_field($_GET[$query_var]) : '';

        if (empty($coupon_code)) {
            return;
        }

        WC()->session->set_customer_session_cookie(true);

        if (!WC()->cart->has_discount($coupon_code)) {
            WC()->cart->add_discount($coupon_code);
        }
    }
}
