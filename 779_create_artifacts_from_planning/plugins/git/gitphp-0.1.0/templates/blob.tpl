{*
 *  blob.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blob view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* If we managed to look up commit info, we have enough info to display the full header - othewise just use a simple header *}
 <div class="page_nav">
   {if $fullnav}
     <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hashbase}">tree</a><br />
     {if $file}
       <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}&f={$file}&noheader=1">plain</a> |
       {if ($hashbase != "HEAD") && ($hashbase != $head)}
         <a href="{$SCRIPT_NAME}?p={$project}&a=blob&hb=HEAD&f={$file}">HEAD</a>
       {else}
         HEAD
       {/if}
       <br />
     {else}
       <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}&noheader=1">plain</a><br />
     {/if}
   {else}
     <br /><br />
   {/if}
 </div>
 <div>
   {if $fullnav}
     <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}
     {if $hashbaseref}
       <span class="tag">{$hashbaseref}</span>
     {/if}
     </a>
   {else}
     <div class="title">{$hash}</div>
   {/if}
 </div>
 <div class="page_path">
   {* The path to the file, with directories broken into tree links *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$hashbase}">[{$project}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$path.tree}&f={$path.full}&noheader=1">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <div class="page_body">
   {if $mime && $data}
     {* We're trying to display an image *}
     <div>
       <img src="data:{$mime};base64,{$data}" />
     </div>
   {elseif $geshiout}
     {* We're using the highlighted output from geshi *}
     {$geshiout}
   {else}
     {* Just plain display *}
     <table class="code">
     {foreach from=$lines item=line name=lines}
       <tr>
         <td class="num"><a id="l{$smarty.foreach.lines.iteration}" href="#l{$smarty.foreach.lines.iteration}" class="linenr">{$smarty.foreach.lines.iteration}</a></td>
	 <td class="codeline">{$line|escape:'html'}</td>
     {/foreach}
     </table>
   {/if}
 </div>

 {include file='footer.tpl'}
