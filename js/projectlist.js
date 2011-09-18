/*
 * GitPHP Javascript projectlist loader
 * 
 * Initializes script modules used on the projectlist page
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/projectsearch", "common"], function($, projectSearch) {
	$(function() {
		projectSearch.init($('table.projectList'), $('div.projectSearch'));
	});
});
