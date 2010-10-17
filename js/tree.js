/*
 * GitPHP javascript tree
 * 
 * Load subtree data into tree page asynchronously
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

function initTree() {
	var url = window.location.href.match(/^([^\?]+\/)/);
	if (!url) {
		return;
	}
	url = url[1];

	var collapsed = '[+]';
	var expanded = '[–]';

	$('table.treeTable td.expander').text(collapsed);

	$('a.jsTree').live('click', function() {
		var treeHash = $(this).attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
		if (!treeHash) {
			return;
		}

		treeHash = treeHash[1];

		var cell = $(this).parent();
		var row = cell.parent();

		var treeRows = $('.' + treeHash);
		if (treeRows && treeRows.size() > 0) {
			if (treeRows.is(':visible')) {
				treeRows.hide();
				treeRows.each(function() {
					if ($(this).data('parent') == treeHash)
						$(this).data('expanded', false);
				});
				row.find('td.expander').text(collapsed);
			} else {
				treeRows.each(function() {
					if (($(this).data('parent') == treeHash) || ($(this).data('expanded') == true)) {
						$(this).show();
						$(this).data('expanded', true);
					}
				});
				row.find('td.expander').text(expanded);
			}
		} else {
			var indent = cell.html().match(/^(—+)/);
			if (indent)
				indent = indent[1];
			else
				indent = '';
			indent += '—';

			var img = jQuery(document.createElement('img'));
			img.attr('src', url + "images/tree-loader.gif");
			img.attr('alt', GITPHP_RES_LOADING);
			img.addClass('treeSpinner');
			img.appendTo(cell);

			$.get($(this).attr('href'), { o: 'js' },
			function(data) {
				var subRows = jQuery(data);

				subRows.addClass(treeHash);

				subRows.each(function() {
					$(this).data('parent', treeHash);
					$(this).data('expanded', true);
				});

				var classList = row.attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
					if (item.match(/[0-9a-fA-F]{40}/)) {
						subRows.addClass(item);
					}
				});

				subRows.find('td.fileName').prepend(indent);
				subRows.find('td.expander').text(collapsed);

				row.after(subRows);

				row.find('td.expander').text(expanded);
				cell.children('img.treeSpinner').remove();
			});
		}

		return false;
	});
}

$(document).ready(function() {
	initTree();
});
