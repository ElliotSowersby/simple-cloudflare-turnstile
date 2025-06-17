<?php

/**
 * ninja-forms.php
 * simple-cloudflare-turnstile
 *
 * @author Art Geigel (AG3)
 */

if (!defined('ABSPATH')) { exit; }


/**
 * Defines a new NF field type for a SCFT widget that plays nicely within the  NF ecosystem both on the
 * WP Admin side (NF form editor field) and on the frontend (treated as a true NF field for processing).
 *
 * @extends NF_Abstracts_Field
 */
class NF_Fields_SimpleCloudflareTurnstile extends NF_Abstracts_Field {

	protected $_name			= "simplecloudflareturnstile";
	protected $_type			= "simplecloudflareturnstile";
	protected $_section			= "simplecloudflareturnstile_section";
	protected $_icon			= "cloud";
	protected $_templates		= "simplecloudflareturnstile";
	protected $_wrap_template	= "wrap-no-label";
	protected $_test_value		= "";
	protected $_settings		= array("classes");

	public function __construct() {

		parent::__construct();
		$this->_nicename = "Simple CF Turnstile";


		/**
		 * Cuts out a ton of unneeded settings in the editor view BUT we must retain the 'container_class'
		 * to allow proper rendering of the widget placeholder within the form editor. Without it
		 * throws an error... also nice to give user the ability to add a custom class to the div.
		 */

		$classes = $this->_settings["classes"];
		if ($classes!=null && key_exists("settings", $classes)) {
			$_classes_settings = array();
			$classes_settings = $classes["settings"];
			if ($classes_settings!=null && count($classes_settings)>0) {
				foreach($classes_settings as $classes_single_setting) {
					if (key_exists("name", $classes_single_setting) && $classes_single_setting["name"]=="container_class") {
						$_classes_settings = array(
							$classes_single_setting
						);
					}
				}
			}

			$this->_settings["classes"]["settings"] = $_classes_settings;
		}


		/**
		 * Nix other settings in the form editor we won't use or need.
		 */

		unset($this->_settings["wrap_styles"]);
		unset($this->_settings["label_styles"]);
		unset($this->_settings["element_styles"]);


		/**
		 * The global settings within SCFT are treated as defaults for all new instances of a CFT
		 * field in NF. But, each CFT field instance does offer the user the ability to override and
		 * configure _for that one instance_ different values. Chosen settings for that particular
		 * instance in the form are preserved even if the global settings in SCFT are changed later.
		 *
		 * There are four (4) settings that can be specified for a CFT field:
		 *
		 *	1. Widget Size
		 *	2. Widget Theme
		 *	3. Widget Appearance
		 *	4. Widget Alignment
		 *
		 */


		/**
		 * Widget Size Setting
		 */
		{
			$cft_default_size = sanitize_text_field(get_option("cfturnstile_size"), "normal");
			$size_labels = array("normal" => "Normal", "flexible" => "Flexible", "compact" => "Compact");
			$size_labels[$cft_default_size] = $size_labels[$cft_default_size] . " (Default)";
			$this->_settings["_widget_size"] = array(
				"name" => "_widget_size",
				"type" => "select",
				"label" => "Widget Size",
				"options" => array(
					array(
						"label" => $size_labels["normal"],
						"value" => "normal"
					),
					array(
						"label" => $size_labels["flexible"],
						"value" => "flexible"
					),
					array(
						"label" => $size_labels["compact"],
						"value" => "compact"
					),
				),
				"width" => "one-half",
				"group" => "primary",
				"value" => $cft_default_size,
				"help" => "You can override the default size setting of the widget.",
			);
		}


		/**
		 * Widget Theme Setting
		 */
		{
			$cft_default_theme = sanitize_text_field(get_option("cfturnstile_theme"), "auto");
			$theme_labels = array("light" => "Light", "dark" => "Dark", "auto" => "Auto");
			$theme_labels[$cft_default_theme] = $theme_labels[$cft_default_theme] . " (Default)";
			$this->_settings["_widget_theme"] = array(
				"name" => "_widget_theme",
				"type" => "select",
				"label" => "Widget Theme",
				"options" => array(
					array(
						"label" => $theme_labels["light"],
						"value" => "light"
					),
					array(
						"label" => $theme_labels["dark"],
						"value" => "dark"
					),
					array(
						"label" => $theme_labels["auto"],
						"value" => "auto"
					),
				),
				"width" => "one-half",
				"group" => "primary",
				"value" => $cft_default_theme,
				"help" => "You can override the default theme setting of the widget.",
			);
		}


		/**
		 * Widget Appearance Setting
		 */
		{
			$cft_default_appearance = sanitize_text_field(get_option("cfturnstile_appearance"), "always");
			$appearance_labels = array("always" => "Always", "interaction-only" => "Interaction Only");
			$appearance_labels[$cft_default_appearance] = $appearance_labels[$cft_default_appearance] . " (Default)";
			$this->_settings["_widget_appearance"] = array(
				"name" => "_widget_appearance",
				"type" => "select",
				"label" => "Widget Appearance",
				"options" => array(
					array(
						"label" => $appearance_labels["always"],
						"value" => "always"
					),
					array(
						"label" => $appearance_labels["interaction-only"],
						"value" => "interaction-only"
					),
				),
				"width" => "one-half",
				"group" => "primary",
				"value" => $cft_default_appearance,
				"help" => "You can override the default appearance setting of the widget.",
			);
		}


		/**
		 * Widget Alignment Setting
		 */
		 {
			$this->_settings["_widget_alignment"] = array(
				"name" => "_widget_alignment",
				"type" => "select",
				"label" => "Widget Alignment",
				"options" => array(
					array(
						"label" => "Auto (Default)",
						"value" => "auto"
					),
					array(
						"label" => "Left",
						"value" => "left"
					),
					array(
						"label" => "Center",
						"value" => "center"
					),
					array(
						"label" => "Right",
						"value" => "right"
					),
				),
				"width" => "one-half",
				"group" => "primary",
				"value" => "auto",
				"help" => "You can override the default alignment setting of the widget.",
			);
		}


		/**
		 * Tells NF to _not_ include the CFT value in saved submissions (implemented below).
		 */
		add_filter( "nf_sub_hidden_field_types", array($this, "hide_field_type"));
	}

	/**
	 * For actual instances of the CFT field that are going to be rendered and used, we make
	 * available to the rendering template the user's chosen values assigned within the form editor.
	 * These values are accessible from the 'data' object within the two templates associated with
	 * the field. Note: One template is responsible for rendering the placeholder widget within
	 * the form editor and the other is responsible for rendering the actual widget on a page.
	 *
	 * @param	array		$settings 		Contains setting values specific to an actual instance of the CFT field.
	 * @param	object		$form     		A NF form object that reprsents the form containing this instance of the field.
	 *
	 * @return	array           			The updated settings representing any new/modified values prior to being relied upon.
	 */
	public function localize_settings( $settings, $form) {


		/**
		 * Get some values we'll need to rely on now and also use on the template at rendering.
		 */

		$form_id 		= $form->get_id();
		$field_id 		= $settings[$settings["idAttribute"]];


		/**
		 * We generate a GUID based off of the form ID and the specific field key associated with this
		 * NF field to differentiate it between another CFT instance——either within the same form or
		 * another form somewhere else on the same page.
		 *
		 * The field key will be (or _should_ be) in the format 'simplecloudflareturnstile_1748909084604'
		 * and is assigned by NF automatically. We gracefully fallback to using a random ID if there's
		 * an issue working with the field key.
		 */

		$unique_id 		= wp_rand();
		$guid_prefix 	= "F" . $form_id . "-" . $field_id;
		$field_key = explode("_", $settings["key"]);
		$guid = (count($field_key)==2 && $field_key[0]=="simplecloudflareturnstile" && is_numeric($field_key[1]))
			? $guid_prefix . "K" . trim($field_key[1])
			: $guid_prefix . "R" . $unique_id;


		/**
		 * Let's house all of our widget-specific values and settings within its own 'widget' object
		 * so they don't conflict with other NF settings.
		 */

		$widget = array();
		$widget["site_key"] 		= get_option("cfturnstile_key");
		$widget["language"] 		= sanitize_text_field(get_option("cfturnstile_language"), "auto");
		$widget["guid"] 			= $guid;
		$widget["id"] 				= "cf-turnstile-" . $guid;
		$widget["form_id"] 			= $form_id;
		$widget["field_id"] 		= $field_id;
		$widget["_field_id"] 		= "nf-field-" . $field_id;
		$widget["field_key"] 		= $settings["key"];
		$widget["appearance"] 		= $settings["_widget_appearance"];
		$widget["theme"] 			= $settings["_widget_theme"];
		$widget["size"] 			= $settings["_widget_size"];
		$widget["alignment"] 		= (key_exists("_widget_alignment", $settings) && trim($settings["_widget_alignment"])!="") ? trim($settings["_widget_alignment"]) : "auto";;
		$settings["widget"] 		= $widget;
		$settings["label_pos"] 		= "hidden";
		$settings["wrap_template"] 	= "wrap-no-label";

		return $settings;
	}



	/**
	 * Does the actual validation of the CFT token. Prior to actually pinging CF, however, a robust
	 * pre-validation validation of related values is performed so we don't unnecessarily ping CF
	 * with easily catchable "should-have-known-it-would-fail" calls. If those things look good
	 * then we rely on the core SCFT function (cfturnstile_check) for the actual call to CF.
	 *
	 *
	 * @param	array		$field			Contains the value of the CFT field (token) used in checking validity.
	 * @param	array		$data			Additional data. We don't rely on it so it is ignored.
	 *
	 * @return void        					Nothing returned indicates successful validation. Anything else is an error.
	 */
	public function validate( $field, $data ) {

		/**
		 * Just keep it simple with a standard error for anything that fails. The slug is relied on
		 * client-side for maintaining a proper accounting of error messages within NF.
		 */

		$error = array(
			"slug"		=>	"CLOUDFLARE_TURNSTILE_SERVER_ERROR",
			"message"	=>	"Captcha validation failed. Please try again.",
		);


		/**
		 * Check at multiple levels whether the values we're about to send to CF actually exist
		 * and are legitimate.
		 */

		if ((empty(get_option("cfturnstile_key")))
			|| (empty(get_option("cfturnstile_secret")))
			|| (empty($field)
			|| ($field==null)
			|| (gettype($field)!="array")
			|| !key_exists("value", $field))
			|| (empty($field["value"]))
			|| ($field["value"]==null)
			|| (gettype($field["value"])!="string")
			|| (trim($field["value"])==""))
			{ return $error; }


		/**
		 * Do the validation magic!
		 */

		$check = cfturnstile_check($field["value"]);


		/**
		 * Handle any errors in the response and ensure we're getting back something we expect.
		 */

		if ((gettype($check)!="array")
			|| (!key_exists("success", $check))
			|| ($check["success"]!==true))
			{ return $error; }
	}


	/**
	 * NF filter allowing the specification of field types to ignore when deciding whether or not
	 * a given field's value should be saved as part of the submission. We rely on it here to nix
	 * any saving of CFT values (i.e. tokens) since they're worthless at this point.
	 *
	 * @param	array		$field_types	Array of field types that NF should not save values for.
	 *
	 * @return	array              			The updated array that includes our type "simplecloudflareturnstile".
	 */
	function hide_field_type($field_types) {
		$field_types[] = $this->_name;
		return $field_types;
	}
}


/**
 * Only wire up all this stuff if we have SCFT global setting turned on for NF support which itself
 * won't even be available and/or visible to the user unless they have the NF plugin installed.
 */

if (!empty(get_option("cfturnstile_ninja_forms_all")) && get_option("cfturnstile_ninja_forms_all")) {


	/**************************************************************************************
	 ********** Public Facing / NF Forms Fields *******************************************
	  **************************************************************************************/


	/**
	 * Tell NF about our new SCFT field.
	 */

	add_filter( "ninja_forms_register_fields", function($fields) {
		$fields["simplecloudflareturnstile"] = new NF_Fields_SimpleCloudflareTurnstile();
		return $fields;
	});


	/**
	 * Tell NF where to find the template for rendering the SCFT field within public pages.
	 */

	add_action('ninja_forms_output_templates', function() {
		$template = plugin_dir_path(__FILE__) . "templates/fields-simplecloudflareturnstile.html";
		$_template = file_get_contents($template);
		echo $_template;
	});


	/**
	 * Wire up all SCFT scripts and our NF script for client-side functionality.
	 */

	add_filter("ninja_forms_display_init", function($form_id) {

		if (cfturnstile_whitelisted()
			|| empty(get_option("cfturnstile_ninja_forms_all"))
			|| !get_option("cfturnstile_ninja_forms_all")
			|| !cft_is_plugin_active("ninja-forms/ninja-forms.php")
			|| apply_filters("cfturnstile_widget_disable", false))
			{ return; }

		do_action("cfturnstile_enqueue_scripts");
		wp_enqueue_script("cfturnstile-ninja-forms", plugins_url("simple-cloudflare-turnstile/js/integrations/ninja-forms.js"), array("jquery"), "1.0", true);

	});


	/**
	 * inject CSS into public-facing <head> to allow alignment of the widget and prevent NF's
	 * annoying bells and whistels around the field for validation/failure.
	 */

	add_action("wp_head", function() { ?>
		<style>.cf-turnstile-alignment-left {
			.cf-turnstile-alignment-right {
				text-align: right;
			}
			.cf-turnstile-alignment-center {
				text-align: center;
			}
			.nf-field-container.simplecloudflareturnstile-container .nf-field .field-wrap .nf-error-wrap,
			.nf-field-container.simplecloudflareturnstile-container .nf-after-field,
			.nf-field-container.simplecloudflareturnstile-container .nf-after-field .nf-error-wrap,
			.nf-field-container.simplecloudflareturnstile-container .nf-after-field .nf-error {
				display: none !important;
			}
			.nf-field-container.simplecloudflareturnstile-container .nf-field .field-wrap.nf-error .nf-field-element:after,
			.nf-field-container.simplecloudflareturnstile-container .nf-field .field-wrap.nf-pass .nf-field-element:after {
				content: "" !important;
				background: transparent !important;
				background-color: transparent !important;
				height: 0px !important;
				width: 0px !important;
			}
		</style><?php
	});




	/**************************************************************************************
	 ********** WP Admin / NF Forms Form Builder ******************************************
	 **************************************************************************************/


	/**
	 * Within the NF form builder, create a section for our new SCFT field.
	 */

	add_filter("ninja_forms_field_type_sections", function($sections) {
		$sections["simplecloudflareturnstile_section"] = array(
			"id" 			=> "simplecloudflareturnstile_section",
			"nicename" 		=> "Simple Cloudflare Turnstile",
			"classes" 		=> "nf-simplecloudflareturnstile_section",
			"fieldTypes" 	=> array(),
		);
		return $sections;
	});


	/**
	 * Tell NF where to find the template for rendering the SCFT field within the form editor.
	 */

	add_action( "ninja_forms_builder_templates", function() {
		$template = plugin_dir_path(__FILE__) . "templates/fields-builder-simplecloudflareturnstile.html";
		$_template = file_get_contents($template);
		echo $_template;
	});


	/**
	 * Useful CSS needed to make the SCFT field sparkle and shine within the form editor.
	 */

	add_action("admin_head", function() { ?>
		<style>
			.nf-field-wrap.simplecloudflareturnstile .nf-realistic-field .nf-realistic-field--label{display:none;}
			.nf-field-wrap.simplecloudflareturnstile .nf-realistic-field .nf-realistic-field--element .cf-turnstile-builder-container {
				background:#f9f7f9;
				background-color: #f9f7f9;
				border: 1px solid #e7e7e7;
				border-radius: 10px;
			}
			.nf-field-wrap.simplecloudflareturnstile:hover .nf-realistic-field .nf-realistic-field--element .cf-turnstile-builder-container {
				border: 1px solid #dadada;
				box-shadow: 0px 0px 20px 0px rgba(204, 204, 204, 0.3);
				transition: all .4s ease;
			}
		</style><?php
	});

}