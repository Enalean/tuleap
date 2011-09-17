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

define(["jquery", "modules/lang", "modules/tooltip.snapshot"], function($, lang, tooltipSnapshot) {
	$(function() {
		lang($('div.lang_select'));
		tooltipSnapshot($('a.snapshotTip'));
	});

	var project = window.location.href.match(/p=([^&]+)/);
	if (!project) {
		return;
	}
	project = unescape(project[1]);

	var url = window.location.href.match(/^([^\?]+\/)/);
	if (!url) {
		return;
	}
	url = url[1];

	require(["jquery", "modules/tooltip.commit", "modules/tooltip.tag"], function($, tooltipCommit, tooltipTag) {
		$(function() {
			tooltipCommit($('a.commitTip'), project, url);
			tooltipTag($('a.tagTip'), project, url);
		});
	});
});
