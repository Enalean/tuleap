{*
 *  projlist_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
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
