jQuery(document).ready(function($) {
   
	document.addEventListener('DOMContentLoaded', function () {
        // Get all tab links
        var tabLinks = document.querySelectorAll('.nav-tab-wrapper a');
        var tabContents = document.querySelectorAll('.tab-content');

        // Loop through the tab links to add click event
        tabLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();

                // Remove active class from all tabs
                tabLinks.forEach(function (tabLink) {
                    tabLink.classList.remove('nav-tab-active');
                });

                // Hide all tab contents
                tabContents.forEach(function (content) {
                    content.style.display = 'none';
                });

                // Add active class to the clicked tab
                this.classList.add('nav-tab-active');

                // Show the corresponding tab content
                var targetContent = document.querySelector(this.getAttribute('href'));
                if (targetContent) {
                    targetContent.style.display = 'block';
                }
            });
        });
    });

    /*
    $('.wc-bundle-add-to-cart').on('click', function(e) {
        e.preventDefault();

        var productIds = [];
        var bundleOptions = wc_bundle_deals_options;

        if (bundleOptions && bundleOptions.wc_bundle_deal_products) {
            productIds = bundleOptions.wc_bundle_deal_products;
        }

        productIds.forEach(function(productId) {
            $.ajax({
                type: 'POST',
                url: wc_add_to_cart_params.ajax_url,
                data: {
                    action: 'woocommerce_add_to_cart',
                    product_id: productId,
                },
                success: function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }

                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisbutton]);
                }
            });
        });
    });
    */

    function clearInputFields() {
        $('#wc_bundle_deal_name').val('');
        
		$('#wc_bundle_deal_discount_type').val('');
		/*
        $('#wc_bundle_deal_cta_name').val('');
        $('#wc_bundle_deal_expiry_date').val('');
        $('#wc_bundle_deal_discount_amount').val('');
        $('#wc_bundle_deal_minimum_amount').val('');
        $('#wc_bundle_deal_maximum_amount').val('');
        $('#wc_bundle_deal_product_match').val('');
        $('#wc_bundle_deal_product_combination').val('');
        $('#wc_bundle_deal_images_list').empty();
        $('#wc_bundle_deal_images_input').val('');
		*/
    }

    clearInputFields();

    //Open image upload gallery 
    $('#wc_bundle_deal_images_button').on('click', function(e) {
        //console.log("Image button clicked");
        e.preventDefault();

        var imageFrame = wp.media({
            title: 'Select or Upload Images',
            button: {
                text: 'Use this image'
            },
            multiple: false // Set to true to allow multiple images
        });

        imageFrame.on('select', function() {
            var attachment = imageFrame.state().get('selection').first().toJSON();
            var product_id = $('#wc_bundle_deal_product_match').val();

            $('#wc_bundle_deal_images_list').append('<li data-attachment_id="' + attachment.id + '"><img src="' + attachment.url + '" /><button type="button" class="remove_image_button">&times;</button></li>');

            // Force show the section and enable the button
            $('#add-another-product-section').css('display', 'block');
            $('#wc_bundle_deal_save_button').show().prop('disabled', false);

            // Save the image and product ID in a hidden input
            var existingImages = $('#wc_bundle_deal_images_input').val();
            var newImageEntry = '{"image_id":"' + attachment.id + '","product_id":"' + product_id + '"}';
            var updatedImages = existingImages ? existingImages + '|' + newImageEntry : newImageEntry;
            $('#wc_bundle_deal_images_input').val(updatedImages);
        });

        imageFrame.open();
    });

    var selectedProducts = [];

    // Initially hide the image selection and add another product section
    $('#image-selection-section').hide();
    $('#add-another-product-section').hide();

    // Show image selection after product selection
    $('#wc_bundle_deal_product_match').on('change', function() {
        if ($(this).val()) {
            $('#image-selection-section').show();
            $('#add-another-product-section').show();
        } else {
            $('#image-selection-section').hide();
            $('#add-another-product-section').hide();
        }
    });

    // Add product and image to the list and reset for the next product
    $('#add_another_product_button').on('click', function() {
        var selectedProduct = $('#wc_bundle_deal_product_match').val();
        var selectedImage = $('#wc_bundle_deal_images_input').val();

        if (selectedProduct && selectedImage) {
            selectedProducts.push({
                product_id: selectedProduct,
                image_id: selectedImage
            });

            // Update hidden input with the JSON string of selected products
            $('#selected_products_input').val(JSON.stringify(selectedProducts));

            // Optionally: Add visual feedback to show selected products
            var productName = $('#wc_bundle_deal_product_match option:selected').text();
            $('#selected-products-list').append('<li>' + productName + '</li>');

            // Reset selections for next product
            $('#wc_bundle_deal_product_match').val('');
            $('#wc_bundle_deal_images_input').val('');
            $('#wc_bundle_deal_images_list').html('');
            $('#image-selection-section').hide();
            $('#add-another-product-section').show();
        } else {
            alert('Please select a product and attach an image.');
        }
    });
	
	

});
