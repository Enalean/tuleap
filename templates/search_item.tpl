{*
 *  search_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search view item template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<tr class="{$class}">
<td title="{$agestringage}"><i>{$agestringdate}</i></td>
<td><i>{$authorname}</i></td>
<td><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}" class="list" {if $title}title="{$title}"{/if}><b>{$title_short}</b>
{foreach from=$matches item=line name=match}
{if $smarty.foreach.match.first}<br />{/if}{$line}<br />
{/foreach}
</td>
<td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$committree}&hb={$commit}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$commit}">snapshot</a>
</td>
</tr>
