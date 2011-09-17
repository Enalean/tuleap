/*
 * GitPHP Javascript blob loader
 * 
 * Initializes script modules used on the blob page
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["blameasync"], function(blameAsync) {
	$(function() {
		var url = window.location.href.match(/^([^\?]+\/)/);
		if (!url) {
			return;
		}
		url = url[1];
		blameAsync.init($('table#blobData'), $('a#blameLink'), url);
	});
});
