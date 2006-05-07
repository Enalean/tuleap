{*
 *  commit_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view item template
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
 
 {if $status == "A"}
  <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}" class="list">{$file}</a></td>
  <td><span style="color: #008000;">[new {$to_filetype}{if $isreg} with mode: {$to_mode_cut}{/if}]</span></td>
  <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">blob</a></td>
 {elseif $status == "D"}
  <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}" class="list">{$file}</a></td>
  <td><span style="color: #c00000;">[deleted {$from_filetype}]</span></td>
  <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$file}">history</a></td>
 {elseif $status == "M" || $status == "T"}
  <td>
  {if $to_id != $from_id}
    <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$to_id}&hp={$from_id}&hb={$hash}&f={$file}" class="list">{$file}</a>
  {else}
    <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}" class="list">{$file}</a>
  {/if}
  </td>
  <td>{if $from_mode != $to_mode} <span style="color: #777777;">[changed{$modechange}]</span>{/if}</td>
  <td class="link">
  <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">blob</a>{if $to_id != $from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$to_id}&hp={$from_id}&hb={$hash}&f={$file}">diff</a>{/if} | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$file}">history</a></td>
 {elseif $status == "R"}
  <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$to_file}" class="list">{$to_file}</a></td>
  <td><span style="color:#777777;">[moved from <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$from_file}" class="list">{$from_file}</a> with {$similarity}% similarity{$simmodechg}]</span></td>
  <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$to_file}">blob</a>{if $to_id != $from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$to_id}&hp={$from_id}&hb={$hash}&f={$to_file}">diff</a>{/if}</td>
 {/if}

 </tr>
