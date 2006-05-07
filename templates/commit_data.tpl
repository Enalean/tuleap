{*
 *  commit_data.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view data template
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
 <div>
 {if $parent}
   <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}" class="title">{$title}
   {if $commitref}
   <span class="tag">{$commitref}</span>
   {/if}
   </a>
 {else}
   <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}" class="title">{$title}</a>
 {/if}
 </div>
 <div class="title_text">
 <table cellspacing="0">
 <tr><td>author</td><td>{$author}</td></tr>
 <tr><td></td><td> {$adrfc2822} ({if $adhourlocal < 6}<span style="color: #cc0000;">{/if}{$adhourlocal}:{$adminutelocal}{if $adhourlocal < 6}</span>{/if} {$adtzlocal})
 </td></tr>
 <tr><td>committer</td><td>{$committer}</td></tr>
 <tr><td></td><td> {$cdrfc2822} ({$cdhourlocal}:{$cdminutelocal} {$cdtzlocal})</td></tr>
 <tr><td>commit</td><td style="font-family:monospace">{$id}</td><tr>
 <tr><td>tree</td><td style="font-family:monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}" class="list">{$tree}</a></td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">tree</a></td></tr>
 {foreach from=$parents item=par}
 <tr><td>parent</td><td style="font-family:monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}" class="list">{$par}</a></td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}&hp={$par}">commitdiff</a></td></tr>
 {/foreach}
 </table>
 </div>
 <div class="page_body">
 {foreach from=$comment item=line}
 {$line}<br />
 {/foreach}
 </div>
 <div class="list_head">
 {if $difftreesize > 11}
   {$difftreesize} files changed:
 {/if}
 </div>
 <table cellspacing="0">
