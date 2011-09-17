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

define(["jquery", "ext/jquery.qtip.min"],
	function($) {

		function getCommitHash(element) {
			var hash = element.attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
			return hash ? hash[1] : null;
		}

		function buildTipConfig(url, project, hash) {
			return {
				content: {
					text: '<img src="' + url + 'images/tooltip-loader.gif" alt="' + GITPHP_RES_LOADING + '" />',
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

		return function(elements, project, href) {
			elements.each(function(){
				var jThis = $(this);
				var hash = getCommitHash(jThis);
				if (!hash) {
					return;
				}
				var config = buildTipConfig(href, project, hash);
				jThis.qtip(config);
			});
		}
	}
);
