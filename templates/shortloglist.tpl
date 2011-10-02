{*
 * Shortlog List
 *
 * Shortlog list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table cellspacing="0">
   {foreach from=$revlist item=rev}
     <tr class="{cycle values="light,dark"}">
       <td class="monospace">{$rev->GetHash(true)}</td>
       <td title="{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetAge()|agestring}{else}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em>{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{$rev->GetAge()|agestring}{/if}</em></td>
       <td><em>{$rev->GetAuthorName()}</em></td>
       <td>
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}" class="list commitTip" {if strlen($rev->GetTitle()) > 50}title="{$rev->GetTitle()|htmlspecialchars}"{/if}>
         {if $rev->IsMergeCommit()}<span class="merge_title">{else}<span class="commit_title">{/if}{$rev->GetTitle(50)|escape}</span>
         </a>
	 {include file='refbadges.tpl' commit=$rev}
       </td>
       <td class="link">
         {assign var=revtree value=$rev->GetTree()}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$rev->GetHash()}">{t}commit{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$rev->GetHash()}">{t}commitdiff{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$revtree->GetHash()}&amp;hb={$rev->GetHash()}">{t}tree{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$rev->GetHash()}" class="snapshotTip">{t}snapshot{/t}</a>
	 {if $source == 'shortlog'}
	  | 
	  {if $mark}
	    {if $mark->GetHash() == $rev->GetHash()}
	      <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page}">{t}deselect{/t}</a>
	    {else}
	      {if $mark->GetCommitterEpoch() > $rev->GetCommitterEpoch()}
	        {assign var=markbase value=$mark}
		{assign var=markparent value=$rev}
	      {else}
	        {assign var=markbase value=$rev}
		{assign var=markparent value=$mark}
	      {/if}
	      <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$markbase->GetHash()}&amp;hp={$markparent->GetHash()}">{t}diff with selected{/t}</a>
	    {/if}
	  {else}
	    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page}&amp;m={$rev->GetHash()}">{t}select for diff{/t}</a>
	  {/if}
	{/if}
       </td>
     </tr>
   {foreachelse}
     <tr><td><em>{t}No commits{/t}</em></td></tr>
   {/foreach}

   {if $hasmorerevs}
     <tr>
     {if $source == 'summary'}
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog">&hellip;</a></td>
     {else if $source == 'shortlog'}
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h={$commit->GetHash()}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()}{/if}" title="Alt-n">{t}next{/t}</a></td>
     {/if}
     </tr>
   {/if}
 </table>

