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
  <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$treehash}&hb={$hash}">tree</a>
  <br />
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}">first</a>
  {else}
    first
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}{if $page > 1}&pg={$page-1}{/if}" accesskey="p" title="Alt-p">prev</a>
  {else}
    prev
  {/if}
    &sdot; 
  {if $revlistcount > 100}
    <a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
  {else}
    next
  {/if}
  <br />
</div>
<div>
  <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}" class="title">{$title}</a>
</div>
<table cellspacing="0">
  {* Print each match *}
  {section name=match loop=$commitlines}
    <tr class="{cycle values="light,dark"}">
      <td title="{$commitlines[match].agestringage}"><i>{$commitlines[match].agestringdate}</i></td>
      <td><i>{$commitlines[match].authorname}</i></td>
      <td><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[match].commit}" class="list" {if $title}title="{$commitlines[match].title}"{/if}><b>{$commitlines[match].title_short}</b>
        {foreach from=$commitlines[match].matches item=line name=match}
          {if $smarty.foreach.match.first}<br />{/if}{$line}<br />
        {/foreach}
      </td>
      <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[match].commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commitlines[match].commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$commitlines[match].committree}&hb={$commitlines[match].commit}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$commitlines[match].commit}&noheader=1">snapshot</a>
      </td>
    </tr>
  {/section}

  {if $revlistcount > 100}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">next</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

