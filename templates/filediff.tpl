{*
 *  filediff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Single file diff template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<pre>
{foreach from=$diff item=diffline}
{if substr($diffline,0,1)=="+"}
<span class="diffplus">{$diffline|escape:'html'}</span>
{elseif substr($diffline,0,1)=="-"}
<span class="diffminus">{$diffline|escape:'html'}</span>
{elseif substr($diffline,0,1)=="@"}
<span class="diffat">{$diffline|escape:'html'}</span>
{else}
<span>{$diffline|escape:'html'}</span>
{/if}
{/foreach}
</pre>
