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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}&amp;o=unified">{t}unified{/t}</a>
   {else}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}&amp;o=sidebyside">{t}side by side{/t}</a>
   {/if}
    |
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blobdiff_plain&amp;h={$blob->GetHash()}&amp;hp={$blobparent->GetHash()}&amp;f={$file}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blobparent target='blob'}
 
 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     {t}blob{/t}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$blobparent->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}a/{$file}{else}{$blobparent->GetHash()}{/if}</a> -&gt; {t}blob{/t}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$blob->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$file}">{if $file}b/{$file}{else}{$blob->GetHash()}{/if}</a>
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

