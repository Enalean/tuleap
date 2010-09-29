{*
 *  searchfiles.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search files template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

{include file='header.tpl'}

{* Nav *}
<div class="page_nav">
  {include file='nav.tpl' logcommit=$commit treecommit=$commit}
  <br />
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}">{$resources->GetResource('first')}</a>
  {else}
    {$resources->GetResource('first')}
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}{if $page > 1}&pg={$page-1}{/if}" accesskey="p" title="Alt-p">{$resources->GetResource('prev')}</a>
  {else}
    {$resources->GetResource('prev')}
  {/if}
    &sdot; 
  {if $hasmore}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}&pg={$page+1}" accesskey="n" title="Alt-n">{$resources->GetResource('next')}</a>
  {else}
    {$resources->GetResource('next')}
  {/if}
  <br />
</div>
<div class="title">
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$commit->GetHash()}" class="title">{$commit->GetTitle()}</a>
</div>
<table cellspacing="0">
  {* Print each match *}
  {foreach from=$results item=result key=path}
    <tr class="{cycle values="light,dark"}">
      {assign var=resultobject value=$result.object}
      {if $resultobject instanceof GitPHP_Tree}
	      <td>
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$resultobject->GetHash()}&hb={$commit->GetHash()}&f={$path}" class="list"><strong>{$path}</strong></a>
	      </td>
	      <td class="link">
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$resultobject->GetHash()}&hb={$commit->GetHash()}&f={$path}">{$resources->GetResource('tree')}</a>
	      </td>
      {else}
	      <td>
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$result.object->GetHash()}&hb={$commit->GetHash()}&f={$path}" class="list"><strong>{$path|highlight:$search}</strong></a>
		  {foreach from=$result.lines item=line name=match key=lineno}
		    {if $smarty.foreach.match.first}<br />{/if}<span class="respectwhitespace">{$lineno}. {$line|highlight:$search:50:true}</span><br />
		  {/foreach}
	      </td>
	      <td class="link">
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$resultobject->GetHash()}&hb={$commit->GetHash()}&f={$path}">{$resources->GetResource('blob')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=history&h={$commit->GetHash()}&f={$path}">{$resources->GetResource('history')}</a>
	      </td>
      {/if}
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">{$resources->GetResource('next')}</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

