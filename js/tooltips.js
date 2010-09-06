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

	$('a.commitTip').each(function()
	{
		var project = window.location.href.match(/p=([^&]+)&/);
		if (!project) {
			return;
		}

		project = unescape(project[1]);

		var commitHash = $(this).attr('href').match(/h=([0-9a-fA-F]{40}|HEAD)/);
		if (!commitHash) {
			return;
		}

		commitHash = commitHash[1];

		$(this).qtip(
		{
			content: {
				text: '<img src="images/tooltip-loader.gif" alt="Loading..." />',
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
}

$(document).ready(function() {
	initTooltips();
});
