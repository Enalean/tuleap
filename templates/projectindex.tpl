{*
 *  projectindex.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project index template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{if $categorized}
{foreach from=$projlist item=plist}
{foreach from=$plist item=proj}
{$proj}
{/foreach}
{/foreach}
{else}
{foreach from=$projlist item=proj}
{$proj}
{/foreach}
{/if}
