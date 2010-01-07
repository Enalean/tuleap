<?php

class HelloPlugin extends VGPlugin
{
	function __construct() {
		global $conf;
		if (isset($conf['hello'])) {
			$this->register_action('hello');
			$this->register_hook('pagenav');
		}
	}

	function action($action) {
		$this->display_plugin_template('hello');
	}

	function hook($type) {
		if ($type == 'pagenav') {
			global $page;
			$page['links']['hello'] = array('a' => 'hello');
		}
	}
}

