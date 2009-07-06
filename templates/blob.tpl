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
     {* i18n: summary = summary *}
     {* i18n: shortlog = shortlog *}
     {* i18n: log = log *}
     {* i18n: commit = commit *}
     {* i18n: comitdiff = commitdiff *}
     {* i18n: tree = tree *}
     <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hashbase}">{$localize.tree}</a><br />
     {* i18n: plain = plain *}
     {if $file}
       <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}&f={$file}">{$localize.plain}</a> | 
       {* i18n: HEAD = HEAD *}
       {if ($hashbase != "HEAD") && ($hashbase != $head)}
         <a href="{$SCRIPT_NAME}?p={$project}&a=blob&hb=HEAD&f={$file}">{$localize.HEAD}</a>
       {else}
         {$localize.HEAD}
       {/if}
       <br />
     {else}
       <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}">{$localize.plain}</a><br />
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
         <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$path.tree}&f={$path.full}">{$path.short}</a>
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
	 <td class="codeline">{$line|escape:'htmlall'}</td>
     {/foreach}
     </table>
   {/if}
 </div>

 {include file='footer.tpl'}
