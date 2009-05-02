{*
 *  projlist_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
<tr class="{$class}">
<td>
{if $idt}<span style="white-space:pre;">  {/if}<a href="{$SCRIPT_NAME}?p={$project}&a=summary" class="list">{$project}</a>{if $idt}</span>{/if}
</td>
<td>{$descr}</td>
<td><i>{$owner}</i></td>
<td>
{if $age_colored}
<span style="color: #009900;">
{/if}
{if $age_bold}
<b>
{/if}
<i>
{$age_string}
</i>
{if $age_bold}
</b>
{/if}
{if $age_colored}
</span>
{/if}
</td>
<td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h=HEAD">snapshot</a></td>
</tr>
