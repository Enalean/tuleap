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
			<a href="{$SCRIPT_NAME}?a=commitdiff&amp;h={$titlecommit->GetHash()|urlencode}" class="title">{$titlecommit->GetTitle()|escape}</a>
		{elseif $target == 'tree'}
			<a href="{$SCRIPT_NAME}?a=tree&amp;h={$titletree->GetHash()|urlencode}&amp;hb={$titlecommit->GetHash()|urlencode}" class="title">{$titlecommit->GetTitle()|escape}</a>
		{else}
			<a href="{$SCRIPT_NAME}?a=commit&amp;h={$titlecommit->GetHash()|urlencode}" class="title">{$titlecommit->GetTitle()|escape}</a>
		{/if}
		{include file='refbadges.tpl' commit=$titlecommit}
	{else}
		{if $target == 'tree'}
			<a href="{$SCRIPT_NAME}?a=tree" class="title">&nbsp;</a>
		{elseif $target == 'shortlog'}
			{if $disablelink}
			  {t domain="gitphp"}log{/t}
			{else}
			  <a href="{$SCRIPT_NAME}?a=shortlog" class="title">{t domain="gitphp"}log{/t}</a>
			{/if}
		{elseif $target == 'tags'}
			{if $disablelink}
			  {t domain="gitphp"}tags{/t}
			{else}
			  <a href="{$SCRIPT_NAME}?a=tags" class="title">{t domain="gitphp"}tags{/t}</a>
			{/if}
		{elseif $target == 'heads'}
			{if $disablelink}
			  {t domain="gitphp"}heads{/t}
			{else}
			  <a href="{$SCRIPT_NAME}?a=heads" class="title">{t domain="gitphp"}heads{/t}</a>
			{/if}
		{else}
			&nbsp;
		{/if}
	{/if}
</div>
