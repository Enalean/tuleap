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
			var col = jQuery(document.createElement('td'));
			col.attr('id', 'blameData');
			col.css('display', 'none');

			var p = jQuery(document.createElement('p'));
			p.text("Loading blame data...");
			p.appendTo(col);

			var div = jQuery(document.createElement('div'));
			div.css('text-align', 'center');

			var img = jQuery(document.createElement('img'));
			img.attr('src', "images/blame-loader.gif");
			img.attr('alt', "Loading...");
			img.appendTo(div);

			div.appendTo(col);

			$('table#blobData tr:first').prepend(col);
			col.show('fast');

			$.get($('a#blameLink').attr('href'), { o: 'js' },
			function(data) {
				$('td#blameData').fadeOut('fast', function() {
					$('td#blameData').html(data);
					$('td#blameData').addClass('de1');
					initTooltips();
					$('td#blameData').fadeIn('fast');
				});
			});
		}

		return false;
	},
	function() {
		$('table#blobData td#blameData').hide('fast');

		return false;
	});
}

$(document).ready(function() {
	initBlame();
});
