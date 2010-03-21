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
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$commit->GetHash()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h={$commit->GetHash()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commit->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">tree</a>
  <br />
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}">first</a>
  {else}
    first
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}{if $page > 1}&pg={$page-1}{/if}" accesskey="p" title="Alt-p">prev</a>
  {else}
    prev
  {/if}
    &sdot; 
  {if $hasmore}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
  {else}
    next
  {/if}
  <br />
</div>
<div class="title">
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commit->GetHash()}" class="title">{$commit->GetTitle()}</a>
</div>
<table cellspacing="0">
  {* Print each match *}
  {foreach from=$results item=result key=path}
    <tr class="{cycle values="light,dark"}">
      {assign var=resultobject value=$result.object}
      {if $resultobject instanceof GitPHP_Tree}
	      <td>
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$resultobject->GetHash()}&hb={$commit->GetHash()}&f={$path}" class="list"><strong>{$path}</strong></a>
	      </td>
	      <td class="link">
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$resultobject->GetHash()}&hb={$commit->GetHash()}&f={$path}">tree</a>
	      </td>
      {else}
	      <td>
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$result.object->GetHash()}&hb={$commit->GetHash()}&f={$path}" class="list"><strong>{$path|highlight:$search}</strong></a>
		  {foreach from=$result.lines item=line name=match key=lineno}
		    {if $smarty.foreach.match.first}<br />{/if}<span class="respectwhitespace">{$lineno}. {$line|highlight:$search:50:true}</span><br />
		  {/foreach}
	      </td>
	      <td class="link">
		  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$resultobject->GetHash()}&hb={$commit->GetHash()}&f={$path}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=history&h={$commit->GetHash()}&f={$path}">history</a>
	      </td>
      {/if}
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$commit->GetHash()}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">next</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

