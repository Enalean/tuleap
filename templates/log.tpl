{*
 *  log.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | log | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}&hb={$hash}">tree</a>
   <br />
   {if ($hash != $head) || $page}
     <a href="{$SCRIPT_NAME}?p={$project}&a=log">HEAD</a>
   {else}
     HEAD
   {/if}
   &sdot; 
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}&pg={$page-1}" accesskey="p" title="Alt-p">prev</a>
   {else}
     prev
   {/if}
   &sdot; 
   {if $revlistcount > 100}
     <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
   {else}
     next
   {/if}
   <br />
 </div>
 {if $norevlist}
   <div>
     <a href="{$SCRIPT_NAME}?p={$project}&a=summary" class="title">&nbsp</a>
   </div>
   <div class="page_body">
     Last change {$lastchange}.
     <br /><br />
   </div>
 {/if}
 {* Display each commit *}
 {section name=log loop=$commitlines}
   <div>
     <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[log].commit}" class="title"><span class="age">{$commitlines[log].agestring}</span>{$commitlines[log].title}
       {if $commitlines[log].commitref}
         <span class="tag">{$commitlines[log].commitref}</span>
       {/if}
     </a>
   </div>
   <div class="title_text">
     <div class="log_link">
       <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[log].commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commitlines[log].commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$commitlines[log].commit}&hb={$commitlines[log].commit}">tree</a>
       <br />
     </div>
     <i>{$commitlines[log].authorname} [{$commitlines[log].rfc2822}]</i><br />
   </div>
   <div class="log_body">
     {foreach from=$commitlines[log].comment item=line}
       {$line}<br />
     {/foreach}
     {if count($commitlines[log].comment) > 0}
       <br />
     {/if}
   </div>
 {/section}

 {include file='footer.tpl'}

