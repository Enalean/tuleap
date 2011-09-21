/*
 * GitPHP javascript tree
 * 
 * Load subtree data into tree page asynchronously
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/geturl"],
	function($, getUrl) {

		var collapsed = '[+]';
		var expanded = '[–]';
		var indent = '—';

		var treeTable = null;

		var url = null;

		function expanderLink(href, text) {
			var a = $(document.createElement('a'));
			a.attr('href', href);
			a.text(text);
			a.addClass('jsTree');
			a.addClass('expander');
			return a;
		}

		function createExpanders() {
			treeTable.find('a.treeLink').each(function() {
				var jThis = $(this);
				var href = jThis.attr('href');
				jThis.parent().parent().find('td.expander').append(expanderLink(href, collapsed));
			});
		}

		function toggleTreeRows(treeHash, parentRow, rows) {
			if (rows.is(':visible')) {
				rows.hide();
				rows.each(function() {
					var jThis = $(this);
					if (jThis.data('parent') == treeHash) {
						jThis.data('expanded', false);
					}
				});
				parentRow.find('a.expander').text(collapsed);
			} else {
				rows.each(function() {
					var jThis = $(this);
					if ((jThis.data('parent') == treeHash) || (jThis.data('expanded') == true)) {
						jThis.show();
						jThis.data('expanded', true);
					}
				});
				parentRow.find('a.expander').text(expanded);
			}
		}

		function loadTreeRows(treeHash, parentRow, href) {
			var depth = parentRow.data('depth') || 0;
			depth++;

			var img = $(document.createElement('img'));
			img.attr('src', url + "images/tree-loader.gif");
			img.attr('alt', GitPHP.Resources.Loading);
			img.addClass('treeSpinner');
			parentRow.find('a.treeLink').after(img);

			$.get(href, { o: 'js' },
			function(data) {
				var subRows = $(data);

				subRows.addClass(treeHash);

				var classList = parentRow.attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
					if (item.match(/[0-9a-fA-F]{40}/)) {
						subRows.addClass(item);
					}
				});

				subRows.each(function() {
					var jThis = $(this);
					jThis.data('parent', treeHash);
					jThis.data('expanded', true);
					jThis.data('depth', depth);

					var fileCell = jThis.find('td.fileName');
					var treeLink = jThis.find('a.treeLink');
					if (treeLink && (treeLink.size() > 0)) {
						fileCell.prepend(expanderLink(treeLink.attr('href'), collapsed));
					} else {
						fileCell.prepend(indent);
					}

					for (var i = 1; i < depth; i++) {
						fileCell.prepend(indent);
					}
				});

				parentRow.after(subRows);

				parentRow.find('a.expander').text(expanded);
				parentRow.find('img.treeSpinner').remove();
			});
		}

		var expanderClick = function() {
			var jThis = $(this);

			var treeHash = jThis.attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
			if (!treeHash) {
				return false;
			}

			treeHash = treeHash[1];

			var cell = $(this).parent();
			var row = cell.parent();

			var treeRows = treeTable.find('.' + treeHash);
			if (treeRows && (treeRows.size() > 0)) {
				toggleTreeRows(treeHash, row, treeRows);
			} else {
				loadTreeRows(treeHash, row, jThis.attr('href'));
			}

			return false;
		}

		var init = function(treeTableElem) {
			treeTable = treeTableElem;
			url = getUrl();
			createExpanders();
			treeTable.find('a.jsTree').live('click', expanderClick);
		};

		return {
			init: init
		};
	}
);
