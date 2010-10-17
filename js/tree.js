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
	var project = window.location.href.match(/p=([^&]+)/);
	if (!project) {
		return;
	}
	project = unescape(project[1]);

	$('a.jsTree').live('click', function() {
		var treeHash = $(this).attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
		if (!treeHash) {
			return;
		}

		treeHash = treeHash[1];

		var treeTable = $('table#' + treeHash);
		if (treeTable && treeTable.size() > 0) {
			if (treeTable.is(':visible'))
				treeTable.slideUp('fast');
			else
				treeTable.slideDown('fast');
		} else {
			var row = jQuery(document.createElement('tr'));

			var cell = jQuery(document.createElement('td'));
			cell.attr('colspan', '4');
			cell.attr('id', 'td' + treeHash);
			cell.addClass('subTree');
			cell.appendTo(row);

			$(this).parent().parent().after(row);

			var par = $(this).parent();
			var htm = par.html();
			var indent = par.html().match(/^(—+)/);
			if (indent)
				indent = indent[1];
			else
				indent = '';
			indent += '—';

			$.get($(this).attr('href'), { o: 'js' },
			function(data) {
				var subTable = jQuery(data);
				subTable.find('td.fileName').prepend(indent);
				$('#td' + treeHash).html(subTable);
			});
		}

		return false;
	});
}

$(document).ready(function() {
	initTree();
});
