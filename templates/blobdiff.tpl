{*
 *  blobdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view template
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
     {* i18n: commitdiff = commitdiff *}
     {* i18n: tree = tree *}
     <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hashbase}">{$localize.tree}</a>
     <br />
     {* i18n: plain = plain *}
     <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff_plain&h={$hash}&hp={$hashparent}&f={$file}">{$localize.plain}</a>
   {else}
     <br /><br />
   {/if}
 </div>
 <div class="title">
   {if $fullnav}
     <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}
       {if $hashbaseref}
         <span class="tag">{$hashbaseref}</span>
       {/if}
     </a>
   {else}
     {$hash} vs {$hashparent}
   {/if}
 </div>
 <div class="page_path">
   {* The path to the file, with directories broken into tree links *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$hashbase}">[{$project}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$path.tree}&hb={$hashbase}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     {* i18n: blob = blob *}
     {$localize.blob}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hashparent}&hb={$hashbase}&f={$file}">{if $file}a/{$file}{else}{$hashparent}{/if}</a> -&gt; {$localize.blob}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}&hb={$hashbase}&f={$file}">{if $file}b/{$file}{else}{$hash}{/if}</a>
   </div>
   {* Display the diff *}
   {include file='filediff.tpl'}
 </div>

 {include file='footer.tpl'}

