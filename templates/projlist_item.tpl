{*
 *  projlist_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *}
<tr class="{$class}">
<td>
{if $idt}<span style="white-space:pre;">  {/if}<a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$project}</a>{if $idt}</span>{/if}
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
<td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h=HEAD">latest snapshot</a></td>
</tr>
