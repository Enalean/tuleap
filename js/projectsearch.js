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

var oldSearchValue = '';

function runSearch() {
	var search = $('input.projectSearchBox').val();
	oldSearchValue = search;
	
	$('img.searchSpinner').show();

	if (search.length == 0) {
		$('a.clearSearch').hide();
	} else {
		$('a.clearSearch').show();
	}

	var visibleCats = [];

	$('table.projectList tr.projectRow').each(function() {
		if (search.length < 1) {
			$(this).show();
			return;
		}

		var category = '';

		var projectName = $(this).find('td.projectName a').text();
		if (projectName.length > 0) {
			if (projectName.indexOf(search) != -1) {
				$(this).show();
				category = $(this).data('category');
				if (category) {
					if (jQuery.inArray(category, visibleCats) == -1) {
						visibleCats.push(category);
					}
				}
				return;
			}
		}
		var projectDesc = $(this).find('td.projectDescription a').text();
		if (projectDesc.length > 0) {
			if (projectDesc.indexOf(search) != -1) {
				$(this).show();
				category = $(this).data('category');
				if (category) {
					if (jQuery.inArray(category, visibleCats) == -1) {
						visibleCats.push(category);
					}
				}
				return;
			}
		}
		var projectOwner = $(this).find('td.projectOwner em').text();
		if (projectOwner.length > 0) {
			if (projectOwner.indexOf(search) != -1) {
				$(this).show();
				category = $(this).data('category');
				if (category) {
					if (jQuery.inArray(category, visibleCats) == -1) {
						visibleCats.push(category);
					}
				}
				return;
			}
		}
		$(this).hide();
	});

	$('table.projectList tr.categoryRow').each(function() {
		if (search.length < 1) {
			$(this).show();
			return;
		}

		var category = $(this).children('th.categoryName').text();
		if (category.length > 0) {
			if (jQuery.inArray(category, visibleCats) !== -1) {
				$(this).show();
			} else {
				$(this).hide();
			}
		}
	});

	$('img.searchSpinner').hide();
}

function initProjectSearch() {
	$('#projectSearchForm').keypress(function(e) {
		if (e.which == 13) {
			return false;
		}
	});

	// Store project categories
	var category = '';
	$('table.projectList tr').each(function() {
		if ($(this).hasClass('categoryRow')) {
			category = $(this).children('th.categoryName').text();
		} else if ($(this).hasClass('projectRow')) {
			if (category.length > 0) {
				$(this).data('category', category);
			}
		}
	});

	$('a.clearSearch').click(function() {
		$('input.projectSearchBox').val('');
		oldSearchValue = '';
		runSearch();
		return false;
	});

	$('input.projectSearchBox').keyup(function() {
		if ($('input.projectSearchBox').val() != oldSearchValue)
			runSearch();
	});
}

$(document).ready(function() {
	initProjectSearch();
});
