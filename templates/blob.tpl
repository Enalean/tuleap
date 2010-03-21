{*
 *  blob.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blob view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commit->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">tree</a><br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob_plain&h={$hash->GetHash()}&f={$file}">plain</a> | 
   {if $commit->GetHash() != $head->GetHash()}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&hb=HEAD&f={$file}">HEAD</a>
   {else}
     HEAD
   {/if}
   {if !$datatag} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blame&h={$hash->GetHash()}&f={$file}&hb={$commit->GetHash()}">blame</a>{/if}
   <br />
 </div>

 {include file='title.tpl' titlecommit=$commit}

{include file='path.tpl' pathobject=$hash target='blobplain'}

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
     <table class="code">
     {foreach from=$bloblines item=line name=bloblines}
       <tr>
         <td class="num"><a id="l{$smarty.foreach.bloblines.iteration}" href="#l{$smarty.foreach.bloblines.iteration}" class="linenr">{$smarty.foreach.bloblines.iteration}</a></td>
	 <td class="codeline">{$line|escape}</td>
       </tr>
     {/foreach}
     </table>
   {/if}
 </div>

 {include file='footer.tpl'}
