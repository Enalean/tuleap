{*
 *  blob.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blob view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hashbase->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hashbase->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$hashbase->GetHash()}">tree</a><br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob_plain&h={$hash->GetHash()}&f={$file}">plain</a> | 
   {if $hashbase->GetHash() != $head->GetHash()}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&hb=HEAD&f={$file}">HEAD</a>
   {else}
     HEAD
   {/if}
   {if !$datatag} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blame&h={$hash->GetHash()}&f={$file}&hb={$hashbase->GetHash()}">blame</a>{/if}
   <br />
 </div>
 <div class="title">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hashbase->GetHash()}" class="title">{$hashbase->GetTitle()}</a>
   <span class="refs">
   {foreach from=$hashbase->GetHeads() item=hashhead}
     <span class="head">
       <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$hashhead->GetName()}">{$hashhead->GetName()}</a>
     </span>
   {/foreach}
   {foreach from=$hashbase->GetTags() item=hashtag}
     <span class="tag">
       <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tag&h={$hashtag->GetName()}">{$hashtag->GetName()}</a>
     </span>
   {/foreach}
   </span>
 </div>
 <div class="page_path">
   {* The path to the file, with directories broken into tree links *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hashbase->GetHash()}&h={$hashbase->GetHash()}">[{$project->GetProject()}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob_plain&h={$path.tree}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hashbase->GetHash()}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
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
