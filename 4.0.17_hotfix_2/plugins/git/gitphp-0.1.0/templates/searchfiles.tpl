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
  {if $filesearchcount > 100}
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
          <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$filesearchlines[match].hash}&hb={$hash}&f={$filesearchlines[match].file}">tree</a>
        {else}
          <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$filesearchlines[match].hash}&hb={$hash}&f={$filesearchlines[match].file}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$filesearchlines[match].file}">history</a>
        {/if}
      </td>
    </tr>
  {/section}

  {if $filesearchcount > 100}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">next</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

