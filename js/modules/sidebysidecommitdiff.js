/*
 * GitPHP javascript commitdiff
 * 
 * Javascript enhancements to make side-by-side
 * commitdiff more usable
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(["jquery"],
	function($) {
		var toc = null;
		var blobContent = null;

		var tocYLoc = null;
		var tocPosition = null;
		var tocTop = null;

		var pinned = false;

		function scrollToTop() {
			var contentTop = blobContent.offset().top;
			if ($(document).scrollTop() > contentTop) {
				$('html, body').animate({
					scrollTop: contentTop
				}, 200);
			}
		}

		function markTOCItemActive(item) {
			toc.find('a.SBSTOCItem').each(function(index, value) {
				if (item == value) {
					$(this).parent().addClass('activeItem');
				} else {
					$(this).parent().removeClass('activeItem');
				}
			});
		}

		function showBlob(id) {
			blobContent.find('div.diffBlob').each(function() {
				var jThis = $(this);
				if (jThis.attr('id') == id) {
					jThis.slideDown('fast');
				} else {
					jThis.slideUp('fast');
				}
			});
		}

		var windowScroll = function() {
			var windowYLoc = $(document).scrollTop();
			if (windowYLoc > tocYLoc) {
				if (!pinned) {
					toc.css('position', 'fixed');
					toc.css('top', '0px');
					pinned = true;
				}
			} else {
				if (pinned) {
					toc.css('position', tocPosition);
					toc.css('top', tocTop);
					pinned = false;
				}
			}
		};

		var tocClick = function() {
			var jThis = $(this);

			markTOCItemActive(jThis.get(0));

			showBlob(jThis.attr('href').substring(1));

			toc.find('a.showAll').show();

			scrollToTop();

			return false;
		};

		var showAllClick = function() {
			toc.find('a.SBSTOCItem').parent().removeClass('activeItem');
			blobContent.find('div.diffBlob').slideDown('fast');
			$(this).hide();
			scrollToTop();
			return false;
		};

		var init = function(tocElem, blobContentElem) {
			toc = tocElem;
			blobContent = blobContentElem;

			tocYLoc = toc.position().top;
			tocPosition = toc.css('position');
			tocTop = toc.css('top');

			$(window).scroll(windowScroll);
			toc.find('a.SBSTOCItem').click(tocClick);
			toc.find('a.showAll').click(showAllClick);
		};

		return {
			init: init
		}
	}
);
