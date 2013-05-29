<?php
/*
 * Plugin Name: Page Redirects
 * Plugin URI: http://wordpress.lowtone.nl/pages-redirect
 * Description: Redirect pages to other posts, pages, or an external URL.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2011-2013, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\pages\redirect
 */

namespace lowtone\pages\redirect {

	use lowtone\Util,
		lowtone\content\packages\Package,
		lowtone\net\URL,
		lowtone\posts\lists\sections\Section,
		lowtone\ui\forms\Form,
		lowtone\ui\forms\FieldSet,
		lowtone\ui\forms\Input,
		lowtone\wp\pages\Page;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone", "lowtone\\wp"),
			Package::INIT_MERGED_PATH => __NAMESPACE__,
			Package::INIT_SUCCESS => function() {

				// Meta box
				
				add_action("add_meta_boxes", function() {

					add_meta_box("lowtone_pages_redirect", __("Redirect", "lowtone_pages_redirect"), function($post) {
						wp_enqueue_style("lowtone_pages_redirect", plugins_url("/assets/styles/redirect.css", __FILE__));
						wp_enqueue_script("lowtone_pages_redirect", plugins_url("/assets/scripts/redirect.js", __FILE__), array("angular"));
						wp_localize_script("lowtone_pages_redirect", "lowtone_pages_redirect", ($settings = settings($post->ID)));

						echo '<div ng-app ng-controller="RedirectCtrl">';

						$form = new Form();

						$pages = array();

						foreach (Page::allOfType() as $page) 
							$pages[$page->ID] = apply_filters("lowtone_pages_redirect_post_title", $page->post_title, $page, &$pages);

						asort($pages);

						$form
							->appendChild(
								$form->createInput(Input::TYPE_RADIO, array(
									Input::PROPERTY_LABEL => __("Do not redirect.", "lowtone_pages_redirect"),
									Input::PROPERTY_NAME => "type",
									Input::PROPERTY_VALUE => "no_redirect",
									Input::PROPERTY_ATTRIBUTES => array(
										"ng-model" => "type",
									),
								))
							)
							->appendChild(
								$form->createInput(Input::TYPE_RADIO, array(
									Input::PROPERTY_LABEL => __("Redirect to another page or post.", "lowtone_pages_redirect"),
									Input::PROPERTY_NAME => "type",
									Input::PROPERTY_VALUE => "post",
									Input::PROPERTY_ATTRIBUTES => array(
										"ng-model" => "type",
									),
								))
							)
							->appendChild(
								$form
									->createFieldSet(array(
										FieldSet::PROPERTY_LEGEND => __("Select post or page", "lowtone_pages_redirect"),
										FieldSet::PROPERTY_ATTRIBUTES => array(
											"ng-show" => "'post' == type",
										),
									))
									->appendChild(
										$form->createInput(Input::TYPE_SELECT, array(
											Input::PROPERTY_LABEL => __("Select page", "lowtone_pages_redirect"),
											Input::PROPERTY_NAME => "post_id",
											Input::PROPERTY_VALUE => array_keys($pages),
											Input::PROPERTY_ALT_VALUE => array_values($pages),
										))
									)
							)
							->appendChild(
								$form->createInput(Input::TYPE_RADIO, array(
									Input::PROPERTY_LABEL => __("Redirect to an external URL", "lowtone_pages_redirect"),
									Input::PROPERTY_NAME => "type",
									Input::PROPERTY_VALUE => "url",
									Input::PROPERTY_ATTRIBUTES => array(
										"ng-model" => "type",
									),
								))
							)
							->appendChild(
								$form
									->createFieldSet(array(
										FieldSet::PROPERTY_LEGEND => __("URL", "lowtone_pages_redirect"),
										FieldSet::PROPERTY_ATTRIBUTES => array(
											"ng-show" => "'url' == type",
										),
									))
									->appendChild(
										$form->createInput(Input::TYPE_TEXT, array(
											Input::PROPERTY_LABEL => __("URL", "lowtone_pages_redirect"),
											Input::PROPERTY_NAME => "url",
										))
									)
							)
							->setValues($settings)
							->prefixNames("lowtone_pages_redirect");

						echo $form
							->createDocument()
							->build()
							->setTemplate(LIB_DIR . "/lowtone/ui/forms/templates/form-content.xsl")
							->transform()
							->saveHtml();

						echo '</div>';
					}, Page::__postType(), "advanced");

				});

				// Save post
				
				add_action("save_post", function($id, $page) {
					if (Util::isAutoSave())
						return;

					if (!isset($_POST["lowtone_pages_redirect"]))
						return;

					update_post_meta($id, "lowtone_pages_redirect", $_POST["lowtone_pages_redirect"]);
				}, 10, 2);

				// Notice
				
				add_action("load-post.php", function() {

					add_action("admin_notices", function() {
						global $post;

						if (NULL === ($url = url($post->ID)))
							return;

						$href = $url;
						$target = "_self";

						$settings = settings($post->ID);

						switch ($settings["type"]) {
							case "post":
								$href = (string) URL::fromString(admin_url("post.php"))->query(array("post" => $settings["post_id"], "action" => "edit"));
								break;

							case "url":
								$target = "_blank";
								break;
						}

						echo '<div class="updated"><p>' .
							sprintf(__('Visitors of this page are redirected to <a href="%s" target="%s">%s</a>.', "lowtone_pages_redirect"), esc_url($href), esc_attr($target), esc_html($url)) . 
							'</p></div>';
					});

				});

				// Template redirect
				
				add_action("template_redirect", function() {
					if (!is_singular())
						return;

					global $wp_query;

					if (NULL === ($url = url($wp_query->post->ID)))
						return;

					wp_redirect($url, 301);

					exit;
				});

				// Permalink
				
				add_filter("page_link", function($permalink, $id) {
					if (is_admin())
						return $permalink;

					if (NULL === ($url = url($id)))
						return $permalink;

					return $url;
				}, 9999, 2);

				// Register text domain
				
				add_action("plugins_loaded", function() {
					load_plugin_textdomain("lowtone_pages_redirect", false, basename(__DIR__) . "/assets/languages");
				});
				
				return true;
			}
		));

	// Functions
	
	function settings($postId) {
		return array_merge(array(
				"type" => "no_redirect",
			), get_post_meta($postId, "lowtone_pages_redirect", true) ?: array());
	}

	function url($postId) {
		$settings = settings($postId);

		$url = NULL;

		switch ($settings["type"]) {
			case "post":
				$url = get_permalink($settings["post_id"]);
				break;

			case "url":
				$url = $settings["url"];
				break;
		}

		return $url;
	}

}