/*
 * GitPHP Javascript commit tooltip
 * 
 * Displays commit messages in a tooltip
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

		function getCommitHash(element) {
			var hash = element.attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
			return hash ? hash[1] : null;
		}

		function buildTipConfig(hash) {
			return {
				content: {
					text: '<img src="' + url + 'images/tooltip-loader.gif" alt="' + GitPHP.Resources.Loading + '" />',
					ajax: {
						url: 'index.php',
						data: {
							p: project,
							a: 'commit',
							o: 'jstip',
							h: hash
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
				var hash = getCommitHash(jThis);
				if (!hash) {
					return;
				}
				var config = buildTipConfig(hash);
				jThis.qtip(config);
			});
		}
	}
);
