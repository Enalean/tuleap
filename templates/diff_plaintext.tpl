{*
 *  diff_plaintext.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Plaintext diff template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
{$localize.From}: {$from}
{$localize.Date}: {$date}
{$localize.Subject}: {$subject}
{if $tagname}
X-Git-Tag: {$tagname}
{/if}
X-Git-Url: {$url}
---
{foreach from=$comment item=line}
{$line}
{/foreach}
---


{foreach from=$diffs item=diffout}
{$diffout}
{/foreach}
