<?php

/**
 * Base class plugins should extend. This defines the public, hopefully
 * somewhat static API plugins should be able to rely on.
 *
 * Plugins go to plugins/name/main.php and must contain a NamePlugin class.
 */
class VGPlugin
{
	/**
	 * Actions, hooks and other things must be initialized/registered here.
	 */
	function __construct() {

	}

	/**
	 * Called when a registered action is triggered.
	 */
	function action($action) {}

	/**
	 * Display the given template.
	 */
	function display_template($template, $with_headers = true) {
		if ($with_headers) {
			require 'templates/header.php';
		}
		require "$template";
		if ($with_headers) {
			require 'templates/footer.php';
		}
	}

	/**
	 * Display the given template.
	 */
	function display_plugin_template($template, $with_headers = true) {
		$name = 'plugins/'. $this->get_name() .'/templates/'. $template .'.php';
		$this->display_template($name, $with_headers);
	}

	/**
	 * Return the name of this plugin, for example: "hello".
	 */
	function get_name() {
		$name = get_class($this);
		return strtolower(str_replace('Plugin', '', $name));
	}

	/**
	 * Called when a registered hook is triggered.
	 * 
	 * Hooks:
	 * header - before closing the head tag
	 * summary - add to summary page
	 * page_start - after body is opened
	 * footer - before closing the body tag
	 * pagenav - $page['links'] can be modified to add more pagenav links, see templates/header.php
	 */
	function hook($type) {}

	/**
	 * Can be used to output xhtml.
	 */
	function output($xhtml) {
		echo($xhtml);
	}

	/**
	 * Registers the given action for this plugin.
	 */
	function register_action($action) {
		self::$plugin_actions[$action] = $this;
	}

	function register_hook($type) {
		self::$plugin_hooks[$type][] = $this;
	}

	// Static members + methods

	public static $plugin_actions = array();
	public static $plugin_hooks = array();

	/**
	 * Call plugin hooks of given type.
	 * @see VGPlugin::register_hook()
	 */
	static function call_hooks($type) {
		if (in_array($type, array_keys(self::$plugin_hooks))) {
			foreach (self::$plugin_hooks[$type] as $class) {
				$class->hook($type);
			}
		}
	}
}

