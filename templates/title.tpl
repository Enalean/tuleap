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
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()}</a>
		{elseif $target == 'tree'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$titletree->GetHash()}&hb={$titlecommit->GetHash()}" class="title">{$commit->GetTitle()}</a>
		{else}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$titlecommit->GetHash()}" class="title">{$titlecommit->GetTitle()}</a>
		{/if}
		<span class="refs">
			{foreach from=$titlecommit->GetHeads() item=titlehead}
				<span class="head">
					<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$titlehead->GetName()}">{$titlehead->GetName()}</a>
				</span>
			{/foreach}
			{foreach from=$titlecommit->GetTags() item=titletag}
				<span class="tag">
					<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tag&h={$titletag->GetName()}">{$titletag->GetName()}</a>
				</span>
			{/foreach}
		</span>
	{else}
		{if $target == 'summary'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary" class="title">&nbsp;</a>
		{elseif $target == 'shortlog'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog" class="title">shortlog</a>
		{elseif $target == 'tags'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tags" class="title">tags</a>
		{elseif $target == 'heads'}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=heads" class="title">heads</a>
		{else}
			&nbsp;
		{/if}
	{/if}
</div>
