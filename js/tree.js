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

function expanderLink(href, text) {
	var a = jQuery(document.createElement('a'));
	a.attr('href', href);
	a.text(text);
	a.addClass('jsTree');
	a.addClass('expander');
	return a;
};

function initTree() {
	var url = window.location.href.match(/^([^\?]+\/)/);
	if (!url) {
		return;
	}
	url = url[1];

	var collapsed = '[+]';
	var expanded = '[–]';
	var indent = '—';

	$('a.treeLink').each(function() {
		$(this).parent().parent().find('td.expander').append(expanderLink($(this).attr('href'), collapsed));
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
					if ($(this).data('parent') == treeHash) {
						$(this).data('expanded', false);
					}
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
			row.find('a.treeLink').after(img);

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

				subRows.each(function() {

					$(this).data('parent', treeHash);
					$(this).data('expanded', true);
					$(this).data('depth', depth);

					var fileCell = $(this).find('td.fileName');
					var treeLink = $(this).find('a.treeLink');
					if (treeLink && (treeLink.size() > 0)) {
						fileCell.prepend(expanderLink(treeLink.attr('href'), collapsed));
					} else {
						fileCell.prepend(indent);
					}

					for (var i = 1; i < depth; i++) {
						fileCell.prepend(indent);
					}
				});

				row.after(subRows);

				row.find('a.expander').text(expanded);
				row.find('img.treeSpinner').remove();
			});
		}

		return false;
	});
};

$(document).ready(function() {
	initTree();
});
