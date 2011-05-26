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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}&amp;o=unified">{t}unified{/t}</a> |
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff_plain&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;f={$file}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blobparent target='blob'}

 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     {t}blob{/t}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}a/{$file}{else}{$blobparent->GetHash()}{/if}</a> -&gt; {t}blob{/t}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$blob->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}b/{$file}{else}{$blob->GetHash()}{/if}</a>
   </div>
   {* Display the sidebysidediff *}
   <table class="diffTable">
   {foreach from=$filediff->GetDiffSplit() item=lineinfo}
     {if $lineinfo[0]=='added'}
     <tr class="diff-added">
     {elseif $lineinfo[0]=='deleted'}
     <tr class="diff-deleted">
     {elseif $lineinfo[0]=='modified'}
     <tr class="diff-modified">
     {else}
     <tr>
     {/if}
       <td class="diff-left">{if $lineinfo[1]}{$lineinfo[1]|escape}{else}&nbsp;{/if}</td>
       <td>{if $lineinfo[2]}{$lineinfo[2]|escape}{else}&nbsp;{/if}</td>
     </tr>
   {/foreach}
   </table>
 </div>

 {include file='footer.tpl'}
