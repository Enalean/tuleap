{*
 *  commitdiff_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view item template
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
 {if $status == "A"}
   <div class="diff_info">
   {$to_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">{$to_id}</a>(new)
   </div>
 {elseif $status == "D"}
   <div class="diff_info">
   {$from_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}">{$from_id}</a>(deleted)
   </div>
 {elseif $status == "M"}
   {if $from_id != $to_id}
     <div class="diff_info">
     {$from_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}">{$from_id}</a> -&gt; {$to_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">{$to_id}</a>
     </div>
   {/if}
 {/if}
