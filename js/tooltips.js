/*
 * GitPHP Javascript tooltips
 *
 * Javascript tooltips to show more info about a commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

function initTooltips() {

	var project = window.location.href.match(/p=([^&]+)/);
	if (!project) {
		return;
	}
	project = unescape(project[1]);

	$('a.commitTip').each(function()
	{
		var commitHash = $(this).attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
		if (!commitHash) {
			return;
		}

		commitHash = commitHash[1];

		$(this).qtip(
		{
			content: {
				text: '<img src="images/tooltip-loader.gif" alt="' + GITPHP_RES_LOADING + '" />',
				ajax: {
					url: 'index.php',
					data: {
						p: project,
						a: 'commit',
						o: 'jstip',
						h: commitHash
					},
					type: 'GET'
				}
			},
			style: {
				classes: 'ui-tooltip-light'
			}
		});
	});

	$('a.tagTip').each(function()
	{
		var tagName = $(this).attr('href').match(/h=([^&]+)/);
		if (!tagName) {
			return;
		}

		tagName = tagName[1];

		$(this).qtip(
		{
			content: {
				text: '<img src="images/tooltip-loader.gif" alt="' + GITPHP_RES_LOADING + '" />',
				ajax: {
					url: 'index.php',
					data: {
						p: project,
						a: 'tag',
						o: 'jstip',
						h: tagName
					},
					type: 'GET'
				}
			},
			style: {
				classes: 'ui-tooltip-light'
			}
		});
	});
}

$(document).ready(function() {
	initTooltips();
});
