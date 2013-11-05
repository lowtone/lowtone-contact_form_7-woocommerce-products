<?php
/*
 * Plugin Name: WooCommerce Product Field for Contact Form 7
 * Plugin URI: http://wordpress.lowtone.nl/plugins/contact_form_7-woocommerce-products/
 * Description: Register a field to select products in the contact form.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\contact_form_7\woocommerce\products
 */

namespace lowtone\contact_form_7\woocommerce\products {

	use lowtone\content\packages\Package;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone\\scripts\\chosen\\ajax"),
			Package::INIT_SUCCESS => function() {

				add_action("init", function() {

					wp_register_script("lowtone_contact_form_7_woocommerce_products", plugins_url("/assets/scripts/jquery-contact_form_7-woocommerce-products.js", __FILE__), array("ajax-chosen"));
					wp_localize_script("lowtone_contact_form_7_woocommerce_products", "lowtone_contact_form_7_woocommerce_products", array(
							"ajaxurl" => admin_url("admin-ajax.php"),
							"keepTypingMsg" => __("Keep typing...", "lowtone_contact_form_7_woocommerce_products"),
							"lookingForMsg" => __("Looking for", "lowtone_contact_form_7_woocommerce_products"),
						));

					// Manually enqueue style because script is included in footer

					wp_enqueue_style("chosen");

					wpcf7_add_shortcode("woocommerce_products", function($tag) {var_dump(debug_backtrace());exit;
						wp_enqueue_script("lowtone_contact_form_7_woocommerce_products");

						$tag["options"][] = "multiple";

						$select = wpcf7_select_shortcode_handler($tag);

						preg_match("/^(.+)><option/", $select, $matches);

						return $matches[1] . sprintf(' data-placeholder="%s">', esc_attr(__("Select one or more products", "lowtone_contact_form_7_woocommerce_products"))) . 
							'</select></span>';
					}, true);

				});

				add_filter("wpcf7_posted_data", function($data) {
					global $wpcf7_contact_form;

					foreach ($wpcf7_contact_form->form_scan_shortcode() as $input) {
						if ("woocommerce_products" != $input["type"])
							continue;

						if (!isset($data[$input["name"]]))
							continue;

						$products = array();

						foreach ((array) $data[$input["name"]] as $id) {
							if (false === ($product = get_product($id)))
								continue;

							$products[] = sprintf("%s (%s)", $product->get_title(), get_permalink($id));
						}

						$data[$input["name"]] = implode("\n", $products);
					}

					return $data;
				});

				$search = function() {
					$products = array();

					$response = function() use (&$products) {
						header("Content-type: application/json");

						echo json_encode(array_map(function($post) {
								return array(
										"value" => $post->ID,
										"text" => $post->post_title
									);
							}, $products));

						exit;
					};

					if (!isset($_REQUEST["_wpnonce"]) || !isset($_REQUEST["_wpcf7"]))
						return $response();

					if (!wpcf7_verify_nonce($_REQUEST["_wpnonce"], $_REQUEST["_wpcf7"]))
						return $response();

					if (isset($_REQUEST["s"]))
						$products = get_posts(array(
							'post_type' => "product", 
							'suppress_filters' => true,
							'update_post_term_cache' => false,
							'update_post_meta_cache' => false,
							'post_status' => 'publish',
							'order' => 'DESC',
							'orderby' => "post_title", 
							'nopaging' => true, 
							's' => $_REQUEST["s"]
						));

					$response();
				};

				add_action("wp_ajax_lowtone_contact_form_7_woocommerce_products", $search);
				add_action("wp_ajax_nopriv_lowtone_contact_form_7_woocommerce_products", $search);
			
			}
		));

}