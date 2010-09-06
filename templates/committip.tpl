{*
 *  committip.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit tooltip template
 *
 *  Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}
<div>
{foreach from=$commit->GetComment() item=line}
{$line}<br />
{/foreach}
</div>
