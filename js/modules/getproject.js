/*
 * GetProject
 * 
 * Gets the page project for use in ajax requests
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(
	function() {
		return function() {
			var project = window.location.href.match(/p=([^&]+)/);
			return project ? unescape(project[1]) : null;
		}
	}
);
