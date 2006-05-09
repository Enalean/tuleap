{*
 *  projlist_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list header template
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
<table cellspacing="0">
<tr>
{if $order == "project"}
<th>Project</th>
{else}
<th><a class="header" href="{$SCRIPT_NAME}?o=project">Project</a></th>
{/if}
{if $order == "descr"}
<th>Description</th>
{else}
<th><a class="header" href="{$SCRIPT_NAME}?o=descr">Description</a></th>
{/if}
{if $order == "owner"}
<th>Owner</th>
{else}
<th><a class="header" href="{$SCRIPT_NAME}?o=owner">Owner</a></th>
{/if}
{if $order == "age"}
<th>Last Change</th>
{else}
<th><a class="header" href="{$SCRIPT_NAME}?o=age">Last Change</a></th>
{/if}
<th>Actions</th>
</tr>
