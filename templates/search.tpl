{*
 *  search.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

{include file='header.tpl'}

{* Nav *}
<div class="page_nav">
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h={$hash}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hash}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$treehash}&hb={$hash}">tree</a>
  <br />
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash}&s={$search}&st={$searchtype}">first</a>
  {else}
    first
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash}&s={$search}&st={$searchtype}{if $page > 1}&pg={$page-1}{/if}" accesskey="p" title="Alt-p">prev</a>
  {else}
    prev
  {/if}
    &sdot; 
  {if $revlistcount > 100}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
  {else}
    next
  {/if}
  <br />
</div>
<div class="title">
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash}" class="title">{$title}</a>
</div>
<table cellspacing="0">
  {* Print each match *}
  {section name=match loop=$commitlines}
    <tr class="{cycle values="light,dark"}">
      <td title="{$commitlines[match].agestringage}"><em>{$commitlines[match].agestringdate}</em></td>
      <td><em>{$commitlines[match].authorname}</em></td>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commitlines[match].commit}" class="list" {if $title}title="{$commitlines[match].title}"{/if}><strong>{$commitlines[match].title_short}</strong>
        {foreach from=$commitlines[match].matches item=line name=match}
          {if $smarty.foreach.match.first}<br />{/if}{$line}<br />
        {/foreach}
      </td>
      <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commitlines[match].commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commitlines[match].commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$commitlines[match].committree}&hb={$commitlines[match].commit}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=snapshot&h={$commitlines[match].commit}">snapshot</a>
      </td>
    </tr>
  {/section}

  {if $revlistcount > 100}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">next</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

