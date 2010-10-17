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

	$('a.jsTree').live('click', function() {
		var treeHash = $(this).attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
		if (!treeHash) {
			return;
		}

		treeHash = treeHash[1];

		var treeRows = $('.' + treeHash);
		if (treeRows && treeRows.size() > 0) {
			if (treeRows.is(':visible'))
				treeRows.hide();
			else
				treeRows.show();
		} else {
			var cell = $(this).parent();
			var row = cell.parent();
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

				var classList = row.attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
					if (item.match(/[0-9a-fA-F]{40}/)) {
						subRows.addClass(item);
					}
				});

				subRows.find('td.fileName').prepend(indent);

				row.after(subRows);

				cell.children('img.treeSpinner').remove();
			});
		}

		return false;
	});
}

$(document).ready(function() {
	initTree();
});
