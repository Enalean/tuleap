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
  {if $filesearchcount > 100}
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
  {section name=match loop=$filesearchlines}
    <tr class="{cycle values="light,dark"}">
      <td>
        {if $filesearchlines[match].tree}
          <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$filesearchlines[match].hash}&hb={$hash}&f={$filesearchlines[match].file}" class="list"><b>{$filesearchlines[match].filename}</b></a>
        {else}
          <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$filesearchlines[match].hash}&hb={$hash}&f={$filesearchlines[match].file}" class="list"><b>{$filesearchlines[match].filename}</b></a>
          {foreach from=$filesearchlines[match].matches item=line name=match}
            {if $smarty.foreach.match.first}<br />{/if}<span class="respectwhitespace">{$line}</span><br />
          {/foreach}
        {/if}
      </td>
      <td class="link">
        {if $filesearchlines[match].tree}
	  {* i18n: tree = tree *}
          <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$filesearchlines[match].hash}&hb={$hash}&f={$filesearchlines[match].file}">{$localize.tree}</a>
        {else}
	  {* i18n: blob = blob *}
	  {* i18n: history = history *}
          <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$filesearchlines[match].hash}&hb={$hash}&f={$filesearchlines[match].file}">{$localize.blob}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$filesearchlines[match].file}">{$localize.history}</a>
        {/if}
      </td>
    </tr>
  {/section}

  {if $filesearchcount > 100}
    <tr>
      {* i18n: next = next *}
      <td><a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">{$localize.next}</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

