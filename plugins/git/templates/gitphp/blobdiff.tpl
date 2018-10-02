{*
 *  blobdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br />
   {if $sidebyside}
   <a href="{$SCRIPT_NAME}?a=blobdiff&amp;h={$blob->GetHash()|urlencode}&amp;hp={$blobparent->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$file|urlencode}&amp;o=unified">{t domain="gitphp"}unified{/t}</a>
   {else}
   <a href="{$SCRIPT_NAME}?a=blobdiff&amp;h={$blob->GetHash()|urlencode}&amp;hp={$blobparent->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$file|urlencode}&amp;o=sidebyside">{t domain="gitphp"}side by side{/t}</a>
   {/if}
    |
   <a href="{$SCRIPT_NAME}?a=blobdiff_plain&amp;h={$blob->GetHash()|urlencode}&amp;hp={$blobparent->GetHash()|urlencode}&amp;f={$file|urlencode}&amp;noheader=1">{t domain="gitphp"}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blobparent target='blob'}

 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     {t domain="gitphp"}blob{/t}:<a href="{$SCRIPT_NAME}?a=blob&amp;h={$blobparent->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$file|urlencode}">{if $file}a/{$file|escape}{else}{$blobparent->GetHash()|escape}{/if}</a> -&gt; {t domain="gitphp"}blob{/t}:<a href="{$SCRIPT_NAME}?a=blob&amp;h={$blob->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$file|urlencode}">{if $file}b/{$file|escape}{else}{$blob->GetHash()|escape}{/if}</a>
   </div>
   {if $sidebyside}
   {* Display the sidebysidediff *}
   {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
   {else}
   {* Display the diff *}
   {include file='filediff.tpl' diff=$filediff->GetDiff($file, false, true)}
   {/if}
 </div>

 {include file='footer.tpl'}

