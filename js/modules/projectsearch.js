/*
 * GitPHP javascript project search
 *
 * Live search of project list
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery"],
	function($) {

		var table = null;
		var searchPanel = null;
		var msgContainer = null;

		var currentSearch = '';
		var searchTimer = null;

		var self = null;

		var clearSearch = function() {
			searchPanel.find('img searchSpinner').show();
			searchPanel.find('input.projectSearchBox').val('');
			currentSearch = '';
			search('');
			searchPanel.find('img.searchSpinner').hide();
			return false;
		};

		var doSearch = function() {
			var newSearch = searchPanel.find('input.projectSearchBox').val().toLowerCase();
			if (newSearch != currentSearch) {
				searchPanel.find('img.searchSpinner').show();
				if (searchTimer != null) {
					clearTimeout(searchTimer);
				}
				currentSearch = newSearch;
				searchTimer = setTimeout(function() {
					self.search(newSearch);
					searchPanel.find('img.searchSpinner').hide();
				}, 500);
			}
		};

		function bindEvents() {
			searchPanel.find('form').keypress(function(e) {
				if (e.which == 13) {
					return false;
				}
			});
			if (table.find('tr.projectRow').size() > 0) {
				searchPanel.find('a.clearSearch').click(clearSearch);
				searchPanel.find('input.projectSearchBox').keyup(doSearch).bind('input paste', doSearch);
			}
		}

		function searchRow(row, searchString) {
			var projectName = row.find('td.projectName a').text();
			if ((projectName.length > 0) && (projectName.toLowerCase().indexOf(searchString) != -1)) {
				return true;
			}

			var projectDesc = row.find('td.projectDescription a').text();
			if ((projectDesc.length > 0) && (projectDesc.toLowerCase().indexOf(searchString) != -1)) {
				return true;
			}

			var projectOwner = row.find('td.projectOwner em').text();
			if ((projectOwner.length > 0) && (projectOwner.toLowerCase().indexOf(searchString) != -1)) {
				return true;
			}

			return false;
		}

		function noMatchesMessage(show, searchString) {
			if (show) {
				if (!msgContainer) {
					msgContainer = jQuery(document.createElement('div'));
					msgContainer.addClass('message');
					msgContainer.appendTo(table);
				}

				var msg = GitPHP.Resources.NoMatchesFound.replace(new RegExp('%1'), searchString);
				msgContainer.text(msg);

				msgContainer.show();
			} else {
				if (msgContainer) {
					msgContainer.hide();
				}
			}
		}

		var search = function(searchString) {
			clearTimeout(searchTimer);
			searchTimer = null;

			if (searchString.length == 0) {
				searchPanel.find('a.clearSearch').hide();
			} else {
				searchPanel.find('a.clearSearch').show();
			}

			var hasMatch = false;
			var visibleCategories = [];

			// search each project
			table.find('tr.projectRow').each(function() {
				var jThis = $(this);
				if (searchString.length < 1) {
					jThis.show();
					hasMatch = true;
					return;
				}
				if (searchRow(jThis, searchString)) {
					jThis.show();
					hasMatch = true;
					var category = jThis.data('category');
					if (category && (jQuery.inArray(category, visibleCategories) == -1)) {
						visibleCategories.push(category);
					}
				} else {
					jThis.hide();
				}
			});

			// show categories that have matching projects
			table.find('tr.categoryRow').each(function() {
				var jThis = $(this);
				if (searchString.length < 1) {
					jThis.show();
					return;
				}
				var category = jThis.children('th.categoryName').text();
				if (category.length > 0) {
					if (jQuery.inArray(category, visibleCategories) !== -1) {
						jThis.show();
					} else {
						jThis.hide();
					}
				}
			});

			if (hasMatch) {
				noMatchesMessage(false);
				table.find('tr.projectHeader').show();
			} else {
				noMatchesMessage(true, searchString)
				table.find('tr.projectHeader').hide();
			}
		};

		var init = function(tableElem, searchPanelElem) {
			table = tableElem;
			searchPanel = searchPanelElem;
			self = this;

			// store project categories
			var category = "";
			table.find('tr').each(function() {
				var jThis = $(this);
				if (jThis.hasClass('categoryRow')) {
					category = jThis.children('th.categoryName').text();
				} else if (jThis.hasClass('projectRow')) {
					if (category.length > 0) {
						jThis.data('category', category);
					}
				}
			});
			bindEvents();
		};

		return {
			init: init,
			search: search
		}
	}
);
