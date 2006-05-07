{*
 *  tags_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view item template
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
 <td><i>{$age}</i></td>
 <td><a href="{$SCRIPT_NAME}?p={$project}&a={$reftype}&h={$refid}" class="list"><b>{$name}</b></a></td>
 <td>
 {if $comment}
 <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$id}" class="list">{$comment}</a>
 {/if}
 </td>
 <td class="link">
 {if $type == "tag"}<a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$id}">tag</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project}&a={$reftype}&h={$refid}">{$reftype}</a>{if $reftype == "commit"} | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$refid}">log</a>{/if}
 </td>
 </tr>
