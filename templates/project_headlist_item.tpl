{*
 *  project_headlist_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project head list item template
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
 {if $truncate}
   <td><a href="{$SCRIPT_NAME}?p={$project}&a=heads">...</a></td>
 {else}
   <td><i>{$headage}</i></td>
   <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$headname}" class="list"><b>{$headname}</b></td>
   <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$headname}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$headname}">log</a></td>
 {/if}
 </tr>
