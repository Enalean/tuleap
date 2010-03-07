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
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hashbase}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree}&hb={$hashbase}">tree</a>
     <br />
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff_plain&h={$hash}&hp={$hashparent}&f={$file}">plain</a>
   {else}
     <br /><br />
   {/if}
 </div>
 <div class="title">
   {if $fullnav}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hashbase}" class="title">{$title}
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
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hashbase}&h={$hashbase}">[{$project->GetProject()}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$path.tree}&hb={$hashbase}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hashbase}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     blob:<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$hashparent}&hb={$hashbase}&f={$file}">{if $file}a/{$file}{else}{$hashparent}{/if}</a> -&gt; blob:<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$hash}&hb={$hashbase}&f={$file}">{if $file}b/{$file}{else}{$hash}{/if}</a>
   </div>
   {* Display the diff *}
   {include file='filediff.tpl' diff=$filediff->GetDiff($file, false, true)}
 </div>

 {include file='footer.tpl'}

