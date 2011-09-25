/*
 * GitPHP Javascript language selector
 * 
 * Changes the language as soon as it's selected,
 * rather than requiring a submit
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery"],
	function($) {
		return function(langSelContainer) {
			langSelContainer.find('select').change(
				function() {
					langSelContainer.find('form').submit();
				}
			);
			langSelContainer.find('input[type="submit"]').remove();
		}
	}
);
