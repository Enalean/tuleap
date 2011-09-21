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

define(["jquery", "modules/geturl", "modules/tooltip.commit"],
	function($, getUrl, tooltipCommit) {

		var blobTable = null;
		var blameLink = null;
		var blameCol = null;

		function buildContainer() {
			var col = $(document.createElement('td'));
			col.attr('id', 'blameData');
			col.css('display', 'none');

			var p = $(document.createElement('p'));
			p.text(GitPHP.Resources.LoadingBlameData);
			p.appendTo(col);

			var div = $(document.createElement('div'));
			div.css('text-align', 'center');

			var img = $(document.createElement('img'));
			img.attr('src', getUrl() + 'images/blame-loader.gif');
			img.attr('alt', GitPHP.Resources.Loading);
			img.appendTo(div);

			div.appendTo(col);

			return col;
		}

		function insertBlame(data) {
			blameCol.html(data).addClass('de1');

			tooltipCommit($('a.commitTip'));
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

		var init = function(blobTableElem, blameLinkElem) {
			blobTable = blobTableElem;
			blameLink = blameLinkElem;

			blameLink.toggle(showBlame, hideBlame);
		};

		return {
			init: init
		}
	}
);

