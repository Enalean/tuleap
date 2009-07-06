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
   {* i18n: summary = summary *}
   {* i18n: shortlog = shortlog *}
   {* i18n: log = log *}
   {* i18n: commit = commit *}
   {* i18n: commitdiff = commitdiff *}
   {* i18n: tree = tree *}
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">{$localize.shortlog}</a> | {$localize.log} | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}&hb={$hash}">{$localize.tree}</a>
   <br />
   {* i18n: HEAD = HEAD *}
   {if ($hash != $head) || $page}
     <a href="{$SCRIPT_NAME}?p={$project}&a=log">{$localize.HEAD}</a>
   {else}
     {$localize.HEAD}
   {/if}
   &sdot; 
   {* i18n: prev = prev *}
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}&pg={$page-1}" accesskey="p" title="Alt-p">{$localize.prev}</a>
   {else}
     {$localize.prev}
   {/if}
   &sdot; 
   {* i18n: next = next *}
   {if $revlistcount > 100}
     <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}&pg={$page+1}" accesskey="n" title="Alt-n">{$localize.next}</a>
   {else}
     {$localize.next}
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
       {* i18n: tree = tree *}
       {* i18n: commit = commit *}
       {* i18n: commitdiff = commitdiff *}
       <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[log].commit}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commitlines[log].commit}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$commitlines[log].commit}&hb={$commitlines[log].commit}">{$localize.tree}</a>
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

