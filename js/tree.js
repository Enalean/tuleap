/*
 * GitPHP Javascript tree loader
 * 
 * Initializes script modules used on the tree page
 * 
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery", "modules/treedrilldown", "common"], function($, treeDrill) {
	$(function() {
		treeDrill.init($('table.treeTable'));
	});
});
