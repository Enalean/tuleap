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

define(["jquery", "modules/blameasync", "common"], function($, blameAsync) {
	$(function() {
		blameAsync.init($('table#blobData'), $('a#blameLink'));
	});
});
