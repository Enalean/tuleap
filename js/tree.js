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

	$('a.jsTree').each(function() {
		var a = jQuery(document.createElement('a'));
		a.attr('href', $(this).attr('href'));
		a.text(collapsed);
		a.addClass('jsTree');
		a.addClass('expander');
		$(this).parent().parent().find('td.expander').append(a);
	});

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
				row.find('a.expander').text(collapsed);
			} else {
				treeRows.each(function() {
					if (($(this).data('parent') == treeHash) || ($(this).data('expanded') == true)) {
						$(this).show();
						$(this).data('expanded', true);
					}
				});
				row.find('a.expander').text(expanded);
			}
		} else {
			var depth = row.data('depth');
			if (depth == null)
				depth = 0;
			depth++;

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
					$(this).data('depth', depth);
				});

				var classList = row.attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
					if (item.match(/[0-9a-fA-F]{40}/)) {
						subRows.addClass(item);
					}
				});

				subRows.each(function() {
					var fileCell = $(this).find('td.fileName');
					for (var i = 0; i < depth; i++) {
						if (i == 0) {
							var treeLink = $(this).find('a.jsTree');
							if (treeLink && (treeLink.size() > 0)) {
								var a1 = jQuery(document.createElement('a'));
								a1.attr('href', treeLink.attr('href'));
								a1.text(collapsed);
								a1.addClass('jsTree');
								a1.addClass('expander');
								fileCell.prepend(a1);
							} else {
								fileCell.prepend('—');
							}
						} else {
							fileCell.prepend('—');
						}
					}
				});

				row.after(subRows);

				row.find('a.expander').text(expanded);
				cell.children('img.treeSpinner').remove();
			});
		}

		return false;
	});
}

$(document).ready(function() {
	initTree();
});
