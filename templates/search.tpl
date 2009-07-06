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
  {* i18n: summary = summary *}
  {* i18n: shortlog = shortlog *}
  {* i18n: log = log *}
  {* i18n: commit = commit *}
  {* i18n: commitdiff = commitdiff *}
  {* i18n: tree = tree *}
  <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$treehash}&hb={$hash}">{$localize.tree}</a>
  <br />
  {* i18n: first = first *}
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}">{$localize.first}</a>
  {else}
    {$localize.first}
  {/if}
    &sdot; 
  {* i18n: prev = prev *}
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}{if $page > 1}&pg={$page-1}{/if}" accesskey="p" title="Alt-p">{$localize.prev}</a>
  {else}
    {$localize.prev}
  {/if}
    &sdot; 
  {* i18n: next = next *}
  {if $revlistcount > 100}
    <a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" accesskey="n" title="Alt-n">{$localize.next}</a>
  {else}
    {$localize.next}
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
      <td><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[match].commit}" class="list" {if $title}title="{$commitlines[match].title}"{/if}><b>{$commitlines[match].title_short}</b></a>
        {foreach from=$commitlines[match].matches item=line name=match}
          {if $smarty.foreach.match.first}<br />{/if}{$line}<br />
        {/foreach}
      </td>
      {* i18n: snapshot = snapshot *}
      {* i18n: tree = tree *}
      {* i18n: commit = commit *}
      {* i18n: commitdiff = commitdiff *}
      <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[match].commit}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commitlines[match].commit}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$commitlines[match].committree}&hb={$commitlines[match].commit}">{$localize.tree}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$commitlines[match].commit}">{$localize.snapshot}</a>
      </td>
    </tr>
  {/section}

  {if $revlistcount > 100}
    <tr>
      {* i18n: next = next *}
      <td><a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">{$localize.next}</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

