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
       <td title="{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetAge()|agestring}{else}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em>{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{$rev->GetAge()|agestring}{/if}</em></td>
       <td><em>{$rev->GetAuthorName()}</em></td>
       <td>
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}" class="list commitTip" {if strlen($rev->GetTitle()) > 50}title="{$rev->GetTitle()}"{/if}><strong>{$rev->GetTitle(50)}</strong></a>
	 {include file='refbadges.tpl' commit=$rev}
       </td>
       <td class="link">
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}">{t}commit{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$rev->GetHash()}">{t}commitdiff{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$rev->GetHash()}&hb={$rev->GetHash()}">{t}tree{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$rev->GetHash()}">{t}snapshot{/t}</a>
	 {if $source == 'shortlog'}
	  | 
	  {if $mark}
	    {if $mark->GetHash() == $rev->GetHash()}
	      <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page}">{t}deselect{/t}</a>
	    {else}
	      {if $mark->GetCommitterEpoch() > $rev->GetCommitterEpoch()}
	        {assign var=markbase value=$mark}
		{assign var=markparent value=$rev}
	      {else}
	        {assign var=markbase value=$rev}
		{assign var=markparent value=$mark}
	      {/if}
	      <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$markbase->GetHash()}&hp={$markparent->GetHash()}">{t}diff with selected{/t}</a>
	    {/if}
	  {else}
	    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page}&m={$rev->GetHash()}">{t}select for diff{/t}</a>
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
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">&hellip;</a></td>
     {else if $source == 'shortlog'}
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page+1}{if $mark}&m={$mark->GetHash()}{/if}" title="Alt-n">{t}next{/t}</a></td>
     {/if}
     </tr>
   {/if}
 </table>

