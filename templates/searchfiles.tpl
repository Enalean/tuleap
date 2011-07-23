{*
 *  searchfiles.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search files template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

{* Nav *}
<div class="page_nav">
  {include file='nav.tpl' logcommit=$commit treecommit=$commit}
  <br />
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}">{t}first{/t}</a>
  {else}
    {t}first{/t}
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}{if $page > 1}&amp;pg={$page-1}{/if}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
  {else}
    {t}prev{/t}
  {/if}
    &sdot; 
  {if $hasmore}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" accesskey="n" title="Alt-n">{t}next{/t}</a>
  {else}
    {t}next{/t}
  {/if}
  <br />
</div>
<div class="title">
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$commit->GetHash()}" class="title">{$commit->GetTitle()}</a>
</div>
<table cellspacing="0">
  {* Print each match *}
  {foreach from=$results item=result key=path}
    <tr class="{cycle values="light,dark"}">
      {assign var=resultobject value=$result.object}
      {if $resultobject instanceof GitPHP_Tree}
	      <td>
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}" class="list"><strong>{$path}</strong></a>
	      </td>
	      <td class="link">
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}">{t}tree{/t}</a>
	      </td>
      {else}
	      <td>
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$result.object->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}" class="list"><strong>{$path|highlight:$search}</strong></a>
		  {foreach from=$result.lines item=line name=match key=lineno}
		    {if $smarty.foreach.match.first}<br />{/if}<span class="matchline">{$lineno}. {$line|highlight:$search:50:true}</span><br />
		  {/foreach}
	      </td>
	      <td class="link">
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$resultobject->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$path}">{t}blob{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$path}">{t}history{/t}</a>
	      </td>
      {/if}
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()}&amp;s={$search}&amp;st={$searchtype}&amp;pg={$page+1}" title="Alt-n">{t}next{/t}</a></td>
    </tr>
  {/if}
</table>

{/block}
