<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WC_Bundle_Deals_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_wc_bundle_deals_save', array( $this, 'handle_wc_bundle_deals_form_submission' ) );
        add_action( 'admin_post_wc_bundle_deals_delete', array( $this, 'handle_wc_bundle_deals_delete' ) );
        add_action('admin_notices', array($this, 'display_admin_notices')); 
		add_action('admin_post_wc_bundle_toggle_active', 'wc_bundle_toggle_active_status');
		//add_action('admin_post_wc_bundle_toggle_active', 'wc_bundle_toggle_active_status');
    }
    

    // Add the "Bundle Deals" submenu under WooCommerce
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Bundle Deals', 'wc-bundle-deals' ),
            __( 'Bundle Deals', 'wc-bundle-deals' ),
            'manage_woocommerce',
            'wc-bundle-deals',
            array( $this, 'bundle_deals_page' )
        );
    }

    // Load the admin styles
    public function enqueue_admin_styles() {
       $style_version = filemtime( plugin_dir_path( __FILE__ ) . '/assets/admin-style.css' );
		wp_enqueue_style( 'wc-bundle-deals-admin-style', plugins_url( '/assets/admin-style.css', __FILE__ ), array(), $style_version );

    }

    // Load the admin scripts
   public function enqueue_admin_scripts() {
        $script_version = filemtime( plugin_dir_path( __FILE__ ) . '/assets/admin-script.js' );
		wp_enqueue_script( 'wc-bundle-deals-admin-script', plugins_url( '/assets/admin-script.js', __FILE__ ), array( 'jquery' ), $script_version, true );

        wp_enqueue_media(); // Enqueue the media uploader

    }
    
    
    
    
    //Handle delete action for bundle
    public function handle_wc_bundle_deals_delete() {
        // Verify nonce for security
		if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wc_bundle_deals_delete_' . intval($_GET['coupon_id']))) {
			wp_die(esc_html__('Invalid nonce. Please try again.', 'wc-bundle-deals'));
		}

        // Get the coupon ID
        if (isset($_GET['coupon_id']) && intval($_GET['coupon_id'])) {
            $coupon_id = intval($_GET['coupon_id']);

            // Delete the coupon
            wp_delete_post($coupon_id, true);

            // Redirect back to the bundle deals page with a success message
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals&status=deleted'));
            exit;
        } else {
            // Redirect back with an error message if the coupon ID is invalid
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals&status=error'));
            exit;
        }
    }

		
	


    // Display the Bundle Deals admin page
    public function bundle_deals_page() {
        // Check if the form has been submitted
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['wc_bundle_deals_options'])) {
            // Verify nonce for security
            check_admin_referer('wc_bundle_deals_save', '_wpnonce');

            // Save the options
            $options = $_POST['wc_bundle_deals_options']; 
            update_option('wc_bundle_deals_options', $options);

            // Fetch all WooCommerce coupons with 'wc_bundle_deal' meta
            $args = array(
                'post_type'      => 'shop_coupon',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => 'wc_bundle_deal',
                        'value'   => 'yes',
                        'compare' => '=',
                    ),
                ),
            );
            $coupons = get_posts($args);

            // Redirect to the same page to avoid resubmission
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals&status=saved'));
            exit;
        }

        // Fetch the saved options
        $options = get_option('wc_bundle_deals_options', array());

        // Fetch all products and categorize them
        $products = wc_get_products(array(
            'limit' => -1,
            'orderby' => 'name',
            'order' => 'asc',
        ));

        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        $products_by_category = array();
        foreach ($categories as $category) {
            $products_by_category[$category->term_id] = array(
                'name' => $category->name,
                'simple' => array(),
                'variable' => array(),
            );
        }

        foreach ($products as $product) {
            if ($product->get_type() === 'simple') {
                foreach ($product->get_category_ids() as $cat_id) {
                    if (isset($products_by_category[$cat_id])) {
                        $products_by_category[$cat_id]['simple'][] = $product;
                    }
                }
            } elseif ($product->get_type() === 'variable') {
                foreach ($product->get_category_ids() as $cat_id) {
                    if (isset($products_by_category[$cat_id])) {
                        $products_by_category[$cat_id]['variable'][] = $product;
                    }
                }
            }
        }

        // Fetch the existing bundle deals (coupons)
        $args = array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => 'wc_bundle_deal',
                    'value'   => 'yes',
                    'compare' => '=',
                ),
            ),
        );
        $query = new WP_Query($args);

        wp_reset_postdata();

        ?>

        <div class="wrap">
            <h1>Bundle Deals</h1>
            
            <!-- Nav bar -->
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="#tab-bundle" class="nav-tab nav-tab-active" id="tab-bundle-link">Bundle</a>
            </nav>
         
    
            <div class="content-wrap">
            
                <div id="tab-bundle" class="tab-content" style="display: block;">
                
                    
                
            
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('wc_bundle_deals_save'); ?>
                    <input type="hidden" name="action" value="wc_bundle_deals_save">
                    <section>
                        <!-- Bundle Name -->
                        <p>
                            <label for="wc_bundle_deal_name">Bundle Name</label>
                            <input type="text" id="wc_bundle_deal_name" name="wc_bundle_deals_options[wc_bundle_deal_name]" value="<?php echo esc_attr($options['wc_bundle_deal_name'] ?? 'wc-bundle-deals'); ?>">
                        </p>

                        <!-- Discount Type -->
                        <p>
                            <label for="wc_bundle_deal_discount_type">Discount Type</label>
                            <select id="wc_bundle_deal_discount_type" name="wc_bundle_deals_options[wc_bundle_deal_discount_type]">
                                <option value="percent" <?php selected($options['wc_bundle_deal_discount_type'] ?? '', 'percent'); ?>>Percentage Discount</option>
                                <option value="fixed_cart" <?php selected($options['wc_bundle_deal_discount_type'] ?? '', 'fixed_cart'); ?>>Fixed Cart Discount</option>
                                <option value="fixed_product" <?php selected($options['wc_bundle_deal_discount_type'] ?? '', 'fixed_product'); ?>>Fixed Product Discount</option>
                            </select>
                        </p>

                        <!-- Step 1: Product Match -->
                        <p>
                        <label for="wc_bundle_deal_product_match">Select a Product</label>
                        <select id="wc_bundle_deal_product_match" name="wc_bundle_deal_product_match">
                            <option value=""><?php esc_html_e('Select a product or variant', 'wc-bundle-deals'); ?></option>
                            <?php
                            foreach ($products_by_category as $cat_id => $category) {
                                echo '<optgroup label="' . esc_attr($category['name']) . '">';

                                // Simple products
                                foreach ($category['simple'] as $product) {
                                    echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                                }

                                // Variable products and their variants
                                foreach ($category['variable'] as $product) {
                                    echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';

                                    $variations = $product->get_available_variations();
                                    foreach ($variations as $variation) {
                                        $variation_obj = wc_get_product($variation['variation_id']);
                                        echo '<option value="' . esc_attr($variation_obj->get_id()) . '">' . esc_html($variation_obj->get_name()) . ' (v)</option>';
                                    }
                                }

                                echo '</optgroup>';
                            }
                            ?>
                            </select>
                        </p>

                        <!-- Step 2: Image Selection -->
                        <p id="image-selection-section" style="display: none;">
                            <label for="wc_bundle_deal_images">Choose your Image</label>
                            <input type="button" id="wc_bundle_deal_images_button" class="button" value="<?php esc_html_e('Upload Image', 'wc-bundle-deals'); ?>"/>
                            <ul id="wc_bundle_deal_images_list"></ul>
                            <input type="hidden" id="wc_bundle_deal_images_input">
                        </p>

                        <!-- Step 3: Save and Add Another Product -->
                        <p id="add-another-product-section" style="display: none;">
                            <button type="button" id="add_another_product_button" class="button button-primary button-large"><?php esc_html_e('Add product to your list ↴', 'wc-bundle-deals'); ?></button>
                        </p>

                        <!-- Hidden field to store selected products -->
                        <input type="hidden" id="selected_products_input" name="wc_bundle_deals_options[selected_products]" value="">

                        <!-- Display selected products -->
                        <ul id="selected-products-list">
                            <li class="initial">Your product list:</li>
                        </ul>
                    
                        <p>
                                            

                    </section>

                    <section class="choose_product">
                        
                    <!-- Discount Expiry Date -->
                        <p>
                            <label for="wc_bundle_deal_expiry_date">Discount Expiry Date</label>
                            <input type="text" id="wc_bundle_deal_expiry_date" name="wc_bundle_deals_options[wc_bundle_deal_expiry_date]" value="<?php echo esc_attr($options['wc_bundle_deal_expiry_date'] ?? ''); ?>" placeholder="DD-MM-YYY" pattern="\d{2}-\d{2}-\d{4}" class="date-picker" />

                        </p>

                        <!-- Discount Amount -->
                        <p>
                            <label for="wc_bundle_deal_discount_amount">Discount Amount</label>
                            <input type="text" id="wc_bundle_deal_discount_amount" name="wc_bundle_deals_options[wc_bundle_deal_discount_amount]" value="<?php echo esc_attr($options['wc_bundle_deal_discount_amount'] ?? ''); ?>">
                        </p>

                        <!-- Minimum Spend -->
                        <p>
                            <label for="wc_bundle_deal_minimum_amount">Minimum Spend</label>
                            <input type="text" id="wc_bundle_deal_minimum_amount" name="wc_bundle_deals_options[wc_bundle_deal_minimum_amount]" value="<?php echo esc_attr($options['wc_bundle_deal_minimum_amount'] ?? ''); ?>">
                        </p>

                        <!-- Maximum Spend -->
                        <p>
                            <label for="wc_bundle_deal_maximum_amount">Maximum Spend</label>
                            <input type="text" id="wc_bundle_deal_maximum_amount" name="wc_bundle_deals_options[wc_bundle_deal_maximum_amount]" value="<?php echo esc_attr($options['wc_bundle_deal_maximum_amount'] ?? ''); ?>">
                        </p>

                        <!-- Individual Use Only -->
                        <p class="inline">
                            <input type="checkbox" id="wc_bundle_deal_individual_use" name="wc_bundle_deals_options[wc_bundle_deal_individual_use]" <?php checked($options['wc_bundle_deal_individual_use'] ?? '', 'on'); ?>>
                            <label for="wc_bundle_deal_individual_use">Individual Use Only</label>
                        </p>

                        <!-- Exclude Sale Items -->
                        <p class="inline">
                            <input type="checkbox" id="wc_bundle_deal_exclude_sale_items" name="wc_bundle_deals_options[wc_bundle_deal_exclude_sale_items]" <?php checked($options['wc_bundle_deal_exclude_sale_items'] ?? '', 'on'); ?>>
                            <label for="wc_bundle_deal_exclude_sale_items">Exclude Sale Items</label>
                        </p>

                        <!-- Allow Free Shipping -->
                        <p class="inline">
                            <input type="checkbox" id="wc_bundle_deal_allow_free_shipping" name="wc_bundle_deals_options[wc_bundle_deal_allow_free_shipping]" <?php checked($options['wc_bundle_deal_allow_free_shipping'] ?? '', 'on'); ?>>
                            <label for="wc_bundle_deal_allow_free_shipping">Allow Free Shipping</label>
                        </p>


                    <!-- Product Combination -->
                    
                        <input type="submit" class="button-primary" value="<?php esc_html_e('Save the Bundle', 'wc-bundle-deals'); ?>">
                    </p>
                    
                </section>

              </form>
            </div>

            </div>

            

            <div class="table-display-bundles">
			<!-- Existing Bundle Deals -->
			<h2>Existing Bundle Deals</h2>
			<?php
			// Set up pagination
			$coupons_per_page = 10;
			$paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;

			$args = [
				'post_type'      => 'shop_coupon',
				'posts_per_page' => $coupons_per_page,
				'paged'          => $paged,
				'post_status'    => 'publish',
			];

			$query = new WP_Query($args);

			if ($query->have_posts()) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e('Bundle Name', 'wc-bundle-deals'); ?></th>
						<th><?php esc_html_e('Product Match', 'wc-bundle-deals'); ?></th>
						<th><?php esc_html_e('Discount Type', 'wc-bundle-deals'); ?></th>
						<th><?php esc_html_e('Discount Amount', 'wc-bundle-deals'); ?></th>
						<th><?php esc_html_e('Images', 'wc-bundle-deals'); ?></th>
						<th><?php esc_html_e('Actions', 'wc-bundle-deals'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php while ($query->have_posts()) : $query->the_post(); 
						$coupon_id = get_the_ID();
						$coupon_meta = get_post_meta($coupon_id);

						// Existing bundle details
						$bundle_name = isset($coupon_meta['wc_bundle_deal_name'][0]) ? $coupon_meta['wc_bundle_deal_name'][0] : '';
						$product_match_ids = isset($coupon_meta['product_ids'][0]) ? explode(',', $coupon_meta['product_ids'][0]) : [];
						$discount_type = isset($coupon_meta['discount_type'][0]) ? $coupon_meta['discount_type'][0] : '';
						$discount_amount = isset($coupon_meta['coupon_amount'][0]) ? $coupon_meta['coupon_amount'][0] : 0;
						$image_ids = isset($coupon_meta['wc_bundle_deal_images'][0]) ? maybe_unserialize($coupon_meta['wc_bundle_deal_images'][0]) : [];

						// Fetch bundle visibility status
						$is_active = get_post_meta($coupon_id, '_wc_bundle_active', true); // Custom meta for Active/Inactive status
						if ($is_active === '') {
							$is_active = 'active'; // Default value
						}

						?>
						<tr>
							<td><?php echo esc_html($bundle_name); ?></td>
							<td>
								<?php
								if (!empty($product_match_ids)) {
									foreach ($product_match_ids as $product_id) {
										$product = wc_get_product($product_id);
										if ($product) {
											echo esc_html($product->get_name()) . '<br>';
										}
									}
								}
								?>
							</td>
							<td><?php echo esc_html($discount_type); ?></td>
							<td><?php echo esc_html($discount_amount); ?></td>
							<td>
								<?php
									$image_html = '';
									if (!empty($image_ids)) {
										foreach ($image_ids as $image_id) {
											$image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
											// Escape the URL using esc_url()
											$image_html .= '<img src="' . esc_url($image_url) . '" width="50" height="50" loading="lazy" /><br>';
										}
									}

									// Escape the whole output using wp_kses_post() to allow basic HTML but escape unwanted tags
									echo wp_kses_post($image_html);
								?>
							</td>
							<td>
								<a href="<?php echo esc_url(get_edit_post_link($coupon_id)); ?>" target="_blank"><?php esc_html_e('Edit', 'wc-bundle-deals'); ?></a> |
								<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wc_bundle_deals_delete&coupon_id=' . $coupon_id), 'wc_bundle_deals_delete_' . $coupon_id)); ?>" onclick="return confirm('<?php esc_html_e('Are you sure you want to delete this bundle deal?', 'wc-bundle-deals'); ?>');"><?php esc_html_e('Delete', 'wc-bundle-deals'); ?></a> |
								<?php if ($is_active == 'active') : ?>
									<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wc_bundle_toggle_active&coupon_id=' . $coupon_id . '&status=deactive'), 'wc_bundle_toggle_active_' . $coupon_id)); ?>"><?php esc_html_e('Deactivate', 'wc-bundle-deals'); ?></a>
								<?php else : ?>
									<a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=wc_bundle_toggle_active&coupon_id=' . $coupon_id . '&status=active'), 'wc_bundle_toggle_active_' . $coupon_id)); ?>"><?php esc_html_e('Activate', 'wc-bundle-deals'); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		   <?php
			// Pagination
			if ($query->max_num_pages > 1) :
				// Escape the output of paginate_links using wp_kses_post
				echo wp_kses_post(paginate_links([
					'total'   => $query->max_num_pages,
					'current' => $paged,
				]));
			endif;
		else : ?>
			<p><?php esc_html_e('No bundle deals found.', 'wc-bundle-deals'); ?></p>
		<?php endif;
		wp_reset_postdata(); ?>
				
</div>

        <?php
    }

    // Register settings
    public function register_settings() {
        register_setting( 'wc_bundle_deals_options', 'wc_bundle_deals_options' );
    }

    // Handle form submission
    public function handle_wc_bundle_deals_form_submission() {
        if (!isset($_POST['wc_bundle_deals_options']) || !wp_verify_nonce($_POST['_wpnonce'], 'wc_bundle_deals_save')) {
            $this->add_admin_notice('error', __('Invalid nonce.', 'wc-bundle-deals'));
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals'));
            exit;
        }

        $options = $_POST['wc_bundle_deals_options'];
        $selected_products_json = stripslashes($options['selected_products']);
        $selected_products = !empty($selected_products_json) ? json_decode($selected_products_json, true) : [];

        if (empty($selected_products)) {
            $this->add_admin_notice('error', __('Please select at least one product and image.', 'wc-bundle-deals'));
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals'));
            exit;
        }

        // Validate date format for expiry date
        if (!empty($options['wc_bundle_deal_expiry_date']) && !strtotime($options['wc_bundle_deal_expiry_date'])) {
            $this->add_admin_notice('error', __('Invalid date format for expiry date.', 'wc-bundle-deals'));
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals'));
            exit;
        }

        // Required fields
        $required_fields = [
            'wc_bundle_deal_name',
            'wc_bundle_deal_discount_type',
            'wc_bundle_deal_discount_amount'
        ];

        $missing_fields = [];

        // Validate required fields
        foreach ($required_fields as $field) {
            if (empty($options[$field])) {
                $missing_fields[] = $field;
            }
        }

        // If there are missing fields, add an admin notice and redirect back
        if (!empty($missing_fields)) {
            $error_message = __('Please fill in all required fields: ', 'wc-bundle-deals') . implode(', ', $missing_fields);
            $this->add_admin_notice('error', $error_message);
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals'));
            exit;
        }

        // Validate discount amount based on the discount type
        $discount_type = sanitize_text_field($options['wc_bundle_deal_discount_type']);
        $discount_amount = floatval($options['wc_bundle_deal_discount_amount']);

        if ($discount_type === 'percent' && ($discount_amount <= 0 || $discount_amount > 100)) {
            $this->add_admin_notice('error', __('Percentage discount must be between 0 and 100.', 'wc-bundle-deals'));
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals'));
            exit;
        }

        if (($discount_type === 'fixed_cart' || $discount_type === 'fixed_product') && $discount_amount <= 0) {
            $this->add_admin_notice('error', __('Fixed discount amount must be greater than 0.', 'wc-bundle-deals'));
            wp_redirect(admin_url('admin.php?page=wc-bundle-deals'));
            exit;
        }

        // Create or update the coupon
        $coupon_code = sanitize_text_field($options['wc_bundle_deal_name']);
        $coupon = new WC_Coupon($coupon_code);

        // Set coupon properties
        $coupon->set_discount_type($discount_type);
        $coupon->set_amount($discount_amount);
        $coupon->set_date_expires(sanitize_text_field($options['wc_bundle_deal_expiry_date']));
        $coupon->set_individual_use(isset($options['wc_bundle_deal_individual_use']) ? 'yes' : 'no');
        $coupon->set_exclude_sale_items(isset($options['wc_bundle_deal_exclude_sale_items']) ? 'yes' : 'no');
        $coupon->set_minimum_amount(floatval($options['wc_bundle_deal_minimum_amount']));
        $coupon->set_maximum_amount(floatval($options['wc_bundle_deal_maximum_amount']));

        // Add custom meta data
        $coupon->add_meta_data('wc_bundle_deal', 'yes');
        $coupon->add_meta_data('wc_bundle_deal_name', sanitize_text_field($options['wc_bundle_deal_name']));
        $coupon->add_meta_data('wc_bundle_deal_discount_type', sanitize_text_field($options['wc_bundle_deal_discount_type']));
        $coupon->add_meta_data('wc_bundle_deal_allow_free_shipping', isset($options['wc_bundle_deal_allow_free_shipping']) ? 'yes' : 'no');

        $product_match_ids = [];
        $image_ids = [];

        // Process and save the selected products and images
        foreach ($selected_products as $product_match) {
            // Decode the image_id JSON string
            $image_data = json_decode($product_match['image_id'], true);

            if (!empty($product_match['product_id']) && !empty($image_data['image_id'])) {
                $product_match_ids[] = $product_match['product_id'];
                $image_ids[] = $image_data['image_id'];

                // Store image URL directly as metadata
                $image_url = wp_get_attachment_url($image_data['image_id']);
                if ($image_url) {
                    $coupon->add_meta_data('wc_bundle_deal_image_url_' . $product_match['product_id'], $image_url);
                }
            }
        }

        // Store the product match IDs in the coupon's usage restrictions
        if (!empty($product_match_ids)) {
            $coupon->set_product_ids($product_match_ids);
        }

        $image_ids_serialized = serialize($image_ids);
        update_post_meta($coupon_id, 'wc_bundle_deal_images', $image_ids_serialized);

        $coupon->add_meta_data('wc_bundle_deal_product_match', $product_match_ids);
        $coupon->add_meta_data('wc_bundle_deal_images', $image_ids);

        // Save the coupon
        $coupon->save();

        // Redirect to the same page to avoid form resubmission
        wp_redirect(admin_url('admin.php?page=wc-bundle-deals&status=saved'));
        exit;
}


    
    
    
    // Method to add admin notices
    public function add_admin_notice($type, $message) {
        $notices = get_option('wc_bundle_deals_notices', []);
        $notices[] = ['type' => $type, 'message' => $message];
        update_option('wc_bundle_deals_notices', $notices);
    }

    // Method to display admin notices
    public function display_admin_notices() {
        $notices = get_option('wc_bundle_deals_notices', []);
        if (!empty($notices)) {
            foreach ($notices as $notice) {
                echo '<div class="' . esc_attr($notice['type']) . ' notice is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
            }
            delete_option('wc_bundle_deals_notices');
        }
    }
	
	


}

 	function wc_bundle_toggle_active_status() {
		if (!isset($_GET['coupon_id'], $_GET['status'], $_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wc_bundle_toggle_active_' . $_GET['coupon_id'])) {
			wp_die(esc_html_e('Invalid request.', 'wc-bundle-deals'));
		}

		$coupon_id = intval($_GET['coupon_id']);
		$status = sanitize_text_field($_GET['status']);

		// Update the coupon's active/inactive status
		update_post_meta($coupon_id, '_wc_bundle_active', $status);

		// Redirect back to the bundle deals page
		wp_redirect(admin_url('admin.php?page=wc-bundle-deals'));
		exit;
	}
	




