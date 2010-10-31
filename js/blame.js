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

	var url = window.location.href.match(/^([^\?]+\/)/);
	if (!url) {
		return;
	}
	url = url[1];

	var blameLink = $('a#blameLink');

	blameLink.toggle(function() {

		var blameCol = $('table#blobData td#blameData');
		if (blameCol && blameCol.size() > 0) {
			blameCol.show('fast');
		} else {
			var col = jQuery(document.createElement('td'));
			col.attr('id', 'blameData');
			col.css('display', 'none');

			var p = jQuery(document.createElement('p'));
			p.text(GITPHP_RES_LOADING_BLAME_DATA);
			p.appendTo(col);

			var div = jQuery(document.createElement('div'));
			div.css('text-align', 'center');

			var img = jQuery(document.createElement('img'));
			img.attr('src', url + "images/blame-loader.gif");
			img.attr('alt', GITPHP_RES_LOADING);
			img.appendTo(div);

			div.appendTo(col);

			$('table#blobData tr:first').prepend(col);
			col.show('fast');

			$.get(blameLink.attr('href'), { o: 'js' },
			function(data) {

				blameCol = $('td#blameData');
				
				var insertBlame = function() {
					blameCol.html(data).addClass('de1');
					initCommitTips();
				}

				if (blameCol.css('display') == 'none') {
					insertBlame();
				} else {
					blameCol.fadeOut('fast', function() {
						insertBlame();
						blameCol.fadeIn('fast');
					});
				}
			});
		}

		return false;
	},
	function() {
		$('table#blobData td#blameData').hide('fast');

		return false;
	});
};

$(document).ready(function() {
	initBlame();
});
