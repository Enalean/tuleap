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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | shortlog | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h={$hash}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hash}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$hash}&hb={$hash}">tree</a>
   <br />
   {if ($hash != $head) || $page}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">HEAD</a>
   {else}
     HEAD
   {/if}
     &sdot; 
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash}&pg={$page-1}" accesskey="p" title="Alt-p">prev</a>
   {else}
     prev
   {/if}
     &sdot; 
   {if $revlistcount > 100}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
   {else}
     next
   {/if}
   <br />
 </div>
 <div class="title">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary" class="title">&nbsp;</a>
 </div>
 <table cellspacing="0">
   {* Display each log entry *}
   {section name=log loop=$commitlines}
     <tr class="{cycle values="light,dark"}">
       <td title="{$commitlines[log].agestringage}"><em>{$commitlines[log].agestringdate}</em></td>
       <td><em>{$commitlines[log].authorname}</em></td>
       <td>
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commitlines[log].commit}" class="list" {if $commitlines[log].title}title="{$commitlines[log].title}"{/if}><strong>{$commitlines[log].title_short}
         {if $commitlines[log].commitref}
           <span class="tag">{$commitlines[log].commitref}</span>
         {/if}
         </strong>
       </td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commitlines[log].commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commitlines[log].commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$commitlines[log].commit}&hb={$commitlines[log].commit}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=snapshot&h={$commitlines[log].commit}">snapshot</a>
       </td>
     </tr>
   {/section}

   {if $revlistcount > 100}
     <tr>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash}&pg={$page+1}" title="Alt-n">next</a></td>
     </tr>
   {/if}
 </table>

 {include file='footer.tpl'}

