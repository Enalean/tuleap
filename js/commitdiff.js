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
};

$(document).ready(function() {
	initSBSCommitDiff();
});
