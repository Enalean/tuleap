/*
 * GitPHP Javascript snapshot tooltip
 * 
 * Displays choices of snapshot format in a tooltip
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "ext/jquery.qtip.min"],
	function($) {
		
		function buildTipContent(href) {
			var content = '<div>' + GitPHP.Resources.Snapshot + ': ';
			var first = true;
			var formats = GitPHP.Snapshot.Formats
			for (var type in formats) {
				if (formats.hasOwnProperty(type)) {
					if (!first) {
						content += ' | ';
					}
					content += '<a href="' + href + '&fmt=' + type + '">' + formats[type] + '</a>';
					first = false;
				}
			}
			content += '</div>';
			return content;
		}

		function buildTipConfig(content) {
			return {
				content: {
					text: content
				},
				show: {
					event: 'click'
				},
				hide: {
					fixed: true,
					delay: 150
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
			elements.each(function(){
				var jThis = $(this);
				var href = jThis.attr('href');
				var content = buildTipContent(href);
				var config = buildTipConfig(content);
				jThis.qtip(config);
				jThis.click(function() { return false; });
			});
		}
	}
);
