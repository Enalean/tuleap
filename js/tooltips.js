/*
 * GitPHP Javascript tooltips
 *
 * Javascript tooltips to show more info about a commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

function initSnapshotTips() {

	$('a.snapshotTip').each(function()
	{
		var href = $(this).attr('href');

		var content = '<div>' + GITPHP_RES_SNAPSHOT + ': ';
		var first = true;
		for (var type in GITPHP_SNAPSHOT_FORMATS) {
			if (!first) {
				content += ' | ';
			}
			content += '<a href="' + href + '&fmt=' + type + '">' + GITPHP_SNAPSHOT_FORMATS[type] + '</a>';
			first = false;
		}
		content += '</div>';

		$(this).qtip(
		{
			content: {
				text: content
			},
			show: {
				event: 'click'
			},
			hide: {
				fixed: true,
				delay: 150
			},
			style: {
				classes: 'ui-tooltip-light ui-tooltip-shadow'
			},
			position: {
				adjust: {
					screen: true
				}
			}
		});

		$(this).click(function() { return false; });
	});

};

function initCommitTips() {

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
				text: '<img src="' + url + 'images/tooltip-loader.gif" alt="' + GITPHP_RES_LOADING + '" />',
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
				classes: 'ui-tooltip-light ui-tooltip-shadow'
			},
			position: {
				adjust: {
					screen: true
				}
			}
		});
	});

};

function initTagTips() {

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
				text: '<img src="' + url + 'images/tooltip-loader.gif" alt="' + GITPHP_RES_LOADING + '" />',
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
				classes: 'ui-tooltip-light ui-tooltip-shadow'
			},
			position: {
				adjust: {
					screen: true
				}
			}
		});
	});

};

$(document).ready(function() {
	initCommitTips();
	initTagTips();
	initSnapshotTips();
});
