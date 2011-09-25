/*
 * GetUrl
 * 
 * Gets the page url for use in ajax requests
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(
	function() {
		return function() {
			var url = window.location.href.match(/^([^\?]+\/)/);
			return url ? url[1] : null;
		}
	}
);
