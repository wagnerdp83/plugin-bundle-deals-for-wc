// assets/js/wc-bundle-deals.js

jQuery(document).ready(function($) {
	
	$('.add_bundle_to_cart').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        window.location.href = url;
    });

    
	/*
	$('.wc-bundle-add-to-cart').on('click', function(e) {
        e.preventDefault();

        // Collect necessary data
        var bundleData = {
            action: 'add_bundle_to_cart',
            security: wc_bundle_deals_params.nonce,
            product_ids: wc_bundle_deals_params.product_ids
        };

        // Send AJAX request to the server
        $.post(wc_bundle_deals_params.ajax_url, bundleData, function(response) {
            if (response.success) {
                // Update the cart or notify the user
                window.location.href = wc_bundle_deals_params.cart_url;
            } else {
                alert(response.data.message);
            }
        });
    });
	*/
	

	
	
	
});
