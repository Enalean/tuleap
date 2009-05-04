{*
 *  search_fileitem.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search file item template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<tr class="{$class}">
<td>
{if $tree}
<a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}&hb={$hashbase}&f={$file}" class="list"><b>{$filename}</b></a>
{else}
<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}&hb={$hashbase}&f={$file}" class="list"><b>{$filename}</b></a>
{foreach from=$matches item=line name=match}
{if $smarty.foreach.match.first}<br />{/if}<span class="respectwhitespace">{$line}</span><br />
{/foreach}
{/if}
</td>
<td class="link">{if $tree}<a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}&hb={$hashbase}&f={$file}">tree</a>{else}<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}&hb={$hashbase}&f={$file}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hashbase}&f={$file}">history</a>{/if}</td>
</tr>
