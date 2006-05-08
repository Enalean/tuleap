{*
 *  blobdiff_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view header template
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
 {if $file}
 <div class="page_path"><b>/{$file}</b></div>
 {/if}
 <div class="page_body">
 <div class="diff_info">blob:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hashparent}&hb={$hashbase}&f={$file}">{$hashparent}</a> -&gt; blob:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}&hb={$hashbase}&f={$file}">{$hash}</a></div>
