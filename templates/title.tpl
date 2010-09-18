{*
 * Title
 *
 * Title template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

<div class="title">
	{if $titlecommit}
		{if $target == 'commitdiff'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()}</a>
		{elseif $target == 'tree'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$titletree->GetHash()}&hb={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()}</a>
		{else}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()}</a>
		{/if}
		{include file='refbadges.tpl' commit=$titlecommit}
	{else}
		{if $target == 'summary'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary" class="title">&nbsp;</a>
		{elseif $target == 'shortlog'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog" class="title">{$resources->GetResource('shortlog')}</a>
		{elseif $target == 'tags'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tags" class="title">{$resources->GetResource('tags')}</a>
		{elseif $target == 'heads'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=heads" class="title">{$resources->GetResource('heads')}</a>
		{else}
			&nbsp;
		{/if}
	{/if}
</div>
