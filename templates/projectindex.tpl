{*
 *  projectindex.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project index template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{foreach from=$projlist item=proj}
{$proj->GetProject()}
{/foreach}
