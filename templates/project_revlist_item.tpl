{*
 *  project_revlist_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project revision list item template
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
  <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">...</td>
 {else}
  <td><i>{$commitage}</i></td>
  <td><i>{$commitauthor}</i></td>
  <td>
  <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}" class="list" {if $title}title="{$title}"{/if}><b>{$title_short}
  {if $commitref}
  <span class="tag">{$commitref}</span>
  {/if}
  </b>
  </td>
  <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=committdiff&h={$commit}">commitdiff</a></td>
  </tr>
 {/if}
