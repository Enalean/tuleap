{*
 *  tree_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view nav template
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
 <div class="page_nav"><a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hashbase}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hashbase}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">commitdiff</a> | tree<br /><br />
 </div>
 <div><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}
 {if $hashbaseref}
 <span class="tag">{$hashbaseref}</span>
 {/if}
 </a></div>
