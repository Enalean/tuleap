{*
 *  blob.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blob view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$blob->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}&amp;noheader=1">{t}plain{/t}</a> |
   {if ($head !== null) && ($commit->GetHash() != $head->GetHash()) && ($head->PathToHash($blob->GetPath()))}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;hb=HEAD&amp;f={$blob->GetPath()|urlencode}">{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
   {/if}
   {if $blob->GetPath()}
    | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}">{t}history{/t}</a>
   {if !$datatag} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blame&amp;h={$blob->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}&amp;hb={$commit->GetHash()|urlencode}" id="blameLink">{t}blame{/t}</a>{/if}
   {/if}
   <br />
 </div>

 {include file='title.tpl' titlecommit=$commit}

{include file='path.tpl' pathobject=$blob target='blobplain'}

 <div class="page_body">
   {if $datatag}
     {* We're trying to display an image *}
     <div>
       <img src="data:{$mime};base64,{$data}" />
     </div>
   {elseif $geshi}
     {* We're using the highlighted output from geshi *}
     {$geshiout}
   {else}
     {* Just plain display *}
<table class="code" id="blobData">
<tbody>
<tr class="li1">
<td class="ln">
<pre class="de1">
{foreach from=$bloblines item=line name=bloblines}
<a id="l{$smarty.foreach.bloblines.iteration}" href="#l{$smarty.foreach.bloblines.iteration}" class="linenr">{$smarty.foreach.bloblines.iteration}</a>
{/foreach}
</pre></td>
<td class="de1">
<pre class="de1">
{foreach from=$bloblines item=line name=bloblines}
{$line|escape}
{/foreach}
</pre>
</td>
</tr>
</tbody>
</table>
   {/if}
 </div>

 {include file='footer.tpl'}
