/*
 * GitPHP Javascript commitdiff loader
 * 
 * Initializes script modules used on the commitdiff page
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "common"], function($) {
	$(function(){
		var toc = $('div.commitDiffSBS div.SBSTOC');
		var content = $('div.SBSContent');
		if ((toc.size() > 0) && (content.size() > 0)) {
			require(["jquery", "modules/sidebysidecommitdiff"],
				function($, sbsDiff) {
					$(function() {
						sbsDiff.init(toc, content);
					});
				}
			);
		}
	});
});
