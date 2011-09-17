/*
 * GitPHP Javascript blame
 * 
 * Load blame data into blob page asynchronously
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/tooltip.commit"],
	function($, tooltipCommit) {

		var blobTable = null;
		var blameLink = null;
		var blameCol = null;

		var url = null;

		function buildContainer() {
			var col = $(document.createElement('td'));
			col.attr('id', 'blameData');
			col.css('display', 'none');

			var p = $(document.createElement('p'));
			p.text(GITPHP_RES_LOADING_BLAME_DATA);
			p.appendTo(col);

			var div = $(document.createElement('div'));
			div.css('text-align', 'center');

			var img = $(document.createElement('img'));
			img.attr('src', url + 'images/blame-loader.gif');
			img.attr('alt', GITPHP_RES_LOADING);
			img.appendTo(div);

			div.appendTo(col);

			return col;
		}

		function insertBlame(data) {
			blameCol.html(data).addClass('de1');

			var project = window.location.href.match(/p=([^&]+)/);
			if (!project) {
				return;
			}
			project = unescape(project[1]);

			var url = window.location.href.match(/^([^\?]+\/)/);
			if (!url) {
				return;
			}
			url = url[1];

			tooltipCommit($('a.commitTip'), project, url);
		}

		var showBlame = function() {
			if (blameCol) {
				blameCol.show('fast');
				return false;
			}

			blameCol = buildContainer();
			blobTable.find('tr:first').prepend(blameCol);
			blameCol.show('fast');
			$.get(blameLink.attr('href'), { o: 'js' },
			function(data) {
				if (blameCol.css('display') == 'none') {
					blameCol.html(data).addClass('de1');
					insertBlame(data);
				} else {
					blameCol.fadeOut('fast', function() {
						insertBlame(data);
						blameCol.fadeIn('fast');
					});
				}
			});
		};

		var hideBlame = function() {
			if (blameCol) {
				blameCol.hide('fast');
			}
			return false;
		};

		var init = function(blobTableElem, blameLinkElem, pageUrl) {
			blobTable = blobTableElem;
			blameLink = blameLinkElem;
			url = pageUrl;

			blameLink.toggle(showBlame, hideBlame);
		};

		return {
			init: init
		}
	}
);

