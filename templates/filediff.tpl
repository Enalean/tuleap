{*
 *  filediff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Single file diff template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<div class="pre">
{foreach from=$diff item=diffline}
  {if substr($diffline,0,1)=="+"}
    <span class="diffplus">{$diffline|escape:'htmlall'}</span>
  {elseif substr($diffline,0,1)=="-"}
    <span class="diffminus">{$diffline|escape:'htmlall'}</span>
  {elseif substr($diffline,0,1)=="@"}
    <span class="diffat">{$diffline|escape:'htmlall'}</span>
  {else}
    <span>{$diffline|escape:'htmlall'}</span>
  {/if}
{/foreach}
</div>
