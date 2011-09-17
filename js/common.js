/*
 * GitPHP Javascript common loader
 * 
 * Initializes script modules used across all pages
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/getproject", "modules/lang", "modules/tooltip.snapshot"],
	function($, getProject, lang, tooltipSnapshot) {
		$(function() {
			lang($('div.lang_select'));
			tooltipSnapshot($('a.snapshotTip'));
		});

		if (getProject()) {

			require(["jquery", "modules/tooltip.commit", "modules/tooltip.tag"],
				function($, tooltipCommit, tooltipTag) {
					$(function() {
						tooltipCommit($('a.commitTip'));
						tooltipTag($('a.tagTip'));
					});
				}
			);

		}
	}
);
