/*
 * GitPHP javascript commitdiff
 * 
 * Javascript enhancements to make side-by-side
 * commitdiff more usable
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 */

var TOCYloc = null;
var TOCposition = null;
var TOCtop = null;

function initSBSCommitDiff() {
	var sbsTOC = $('div.commitDiffSBS div.SBSTOC');
	if (sbsTOC.size() < 1) {
		return;
	}

	TOCYloc = sbsTOC.position().top;
	TOCposition = sbsTOC.css('position');
	TOCtop = sbsTOC.css('top');
	$(window).scroll(function() {
		var windowYloc = $(document).scrollTop();
		if (windowYloc > TOCYloc) {
			sbsTOC.css('position', 'fixed');
			sbsTOC.css('top', '0px');
		} else {
			sbsTOC.css('position', TOCposition);
			sbsTOC.css('top', TOCtop);
		}
	});

	$('a.SBSTOCItem').click(function() {
		var clickedItem = $(this).get(0);
		$('a.SBSTOCItem').each(function(index, value) {
			if (clickedItem == value) {
				$(this).parent().addClass('activeItem');
			} else {
				$(this).parent().removeClass('activeItem');
			}
		});
		var clickedId = $(this).attr('href').substring(1);
		$('div.diffBlob').each(function() {
			if ($(this).attr('id') == clickedId) {
				$(this).slideDown('fast');
			} else {
				$(this).slideUp('fast');
			}
		});
		$('a.showAll').show();
		if ($(document).scrollTop() > $('div.SBSContent').offset().top) {
			$('html, body').animate({
				scrollTop: $('div.SBSContent').offset().top
			}, 200);
		}
		return false;
	});
	$('a.showAll').click(function() {
		$('a.SBSTOCItem').parent().removeClass('activeItem');
		$('div.diffBlob').slideDown('fast');
		$(this).hide();
		if ($(document).scrollTop() > $('div.SBSContent').offset().top) {
			$('html, body').animate({
				scrollTop: $('div.SBSContent').offset().top
			}, 200);
		}
		return false;
	});
};

$(document).ready(function() {
	initSBSCommitDiff();
});
