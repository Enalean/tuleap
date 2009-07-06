{*
 *  shortlog.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view template
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
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | {$localize.shortlog} | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}&hb={$hash}">{$localize.tree}</a>
   <br />
   {* i18n: HEAD = HEAD *}
   {if ($hash != $head) || $page}
     <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.HEAD}</a>
   {else}
     {$localize.HEAD}
   {/if}
     &sdot; 
   {* i18n: prev = prev *}
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}&pg={$page-1}" accesskey="p" title="Alt-p">{$localize.prev}</a>
   {else}
     {$localize.prev}
   {/if}
     &sdot; 
   {* i18n: next = next *}
   {if $revlistcount > 100}
     <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}&pg={$page+1}" accesskey="n" title="Alt-n">{$localize.next}</a>
   {else}
     {$localize.next}
   {/if}
   <br />
 </div>
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary" class="title">&nbsp;</a>
 </div>
 <table cellspacing="0">
   {* Display each log entry *}
   {section name=log loop=$commitlines}
     <tr class="{cycle values="light,dark"}">
       <td title="{$commitlines[log].agestringage}"><i>{$commitlines[log].agestringdate}</i></td>
       <td><i>{$commitlines[log].authorname}</i></td>
       <td>
         <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[log].commit}" class="list" {if $commitlines[log].title}title="{$commitlines[log].title}"{/if}><b>{$commitlines[log].title_short}
         {if $commitlines[log].commitref}
           <span class="tag">{$commitlines[log].commitref}</span>
         {/if}
         </b></a>
       </td>
       {* i18n: snapshot = snapshot *}
       {* i18n: commit = commit *}
       {* i18n: commitdiff = commitdiff *}
       {* i18n: tree = tree *}
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commitlines[log].commit}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commitlines[log].commit}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$commitlines[log].commit}&hb={$commitlines[log].commit}">{$localize.tree}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$commitlines[log].commit}">{$localize.snapshot}</a>
       </td>
     </tr>
   {/section}

   {if $revlistcount > 100}
     <tr>
       {* i18n: next = next *}
       <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}&pg={$page+1}" title="Alt-n">{$localize.next}</a></td>
     </tr>
   {/if}
 </table>

 {include file='footer.tpl'}

