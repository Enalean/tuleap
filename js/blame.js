/*
 * GitPHP Javascript blame
 * 
 * Load blame data into blob page asynchronously
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

function initBlame() {

	$('a#blameLink').toggle(function() {

		var blameCol = $('table#blobData td#blameData');
		if (blameCol && blameCol.size() > 0) {
			blameCol.show('fast');
		} else {
			var colElement = document.createElement('td');
			var col = jQuery(colElement);
			col.attr('id', 'blameData');
			col.css('display', 'none');

			var imgElement = document.createElement('img');
			var img = jQuery(imgElement);
			img.attr('src', "images/blame-loader.gif");
			img.attr('alt', "Loading...");
			img.appendTo(col);

			$('table#blobData tr:first').prepend(col);
			col.show('fast');

			$.get($('a#blameLink').attr('href'), { o: 'js' },
			function(data) {
				$('td#blameData').html(data);
				$('td#blameData').addClass('de1');
				initTooltips();
			});
		}

		return false;
	},
	function() {
		var blameCol = $('table#blobData td#blameData');
		blameCol.hide('fast');

		return false;
	});
}

$(document).ready(function() {
	initBlame();
});
