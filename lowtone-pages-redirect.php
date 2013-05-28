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

	use lowtone\content\packages\Package,
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
				
				add_action("add_meta_boxes", function() {

					add_meta_box("lowtone_pages_redirect", __("Redirect", "lowtone_pages_redirect"), function($post) {
						wp_enqueue_style("lowtone_pages_redirect", plugins_url("/assets/styles/redirect.css", __FILE__));
						wp_enqueue_script("lowtone_pages_redirect", plugins_url("/assets/scripts/redirect.js", __FILE__), array("angular"));

						echo '<div ng-app ng-controller="RedirectCtrl">';

						$form = new Form();

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
									Input::PROPERTY_LABEL => __("Redirect to another post or page.", "lowtone_pages_redirect"),
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
										))
									)
							)
							->appendChild(
								$form->createInput(Input::TYPE_CHECKBOX, array(
									Input::PROPERTY_LABEL => __("Open in a new window.", "lowtone_pages_redirect"),
								))
							)
							->setValues(settings($post->ID));

						echo $form
							->createDocument()
							->build()
							->setTemplate(LIB_DIR . "/lowtone/ui/forms/templates/form-content.xsl")
							->transform()
							->saveHtml();

						echo '</div>';
					}, Page::__postType(), "advanced");

				});
				
				return true;
			}
		));

	// Functions
	
	function settings($postId) {
		return array(
				"type" => "no_redirect",
			);
	}

}