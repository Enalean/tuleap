/*
 * GitPHP Javascript tag tooltip
 * 
 * Displays tag messages in a tooltip
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/geturl", "modules/getproject", "ext/jquery.qtip.min"],
	function($, getUrl, getProject) {

		var url = null;
		var project = null;

		function getTagName(element) {
			var tag = element.attr('href').match(/h=([^&]+)/);
			return tag ? tag[1] : null;
		}

		function buildTipConfig(tag) {
			return {
				content: {
					text: '<img src="' + url + 'images/tooltip-loader.gif" alt="' + GitPHP.Resources.Loading + '" />',
					ajax: {
						url: 'index.php',
						data: {
							p: project,
							a: 'tag',
							o: 'jstip',
							h: tag
						},
						type: 'GET'
					}
				},
				style: {
					classes: 'ui-tooltip-light ui-tooltip-shadow'
				},
				position: {
					adjust: {
						screen: true
					}
				}
			}
		}

		return function(elements) {
			url = getUrl();
			project = getProject();
			elements.each(function(){
				var jThis = $(this);
				var tag = getTagName(jThis);
				if (!tag) {
					return;
				}
				var config = buildTipConfig(tag);
				jThis.qtip(config);
			});
		}
	}
);
