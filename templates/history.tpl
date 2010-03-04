{*
 *  history.tpl
 *  gitphp: A PHP git repository browser
 *  Component: History view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Page header *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hash}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree}&hb={$hash}">tree</a>
   <br /><br />
 </div>
 <div class="title">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash}" class="title">{$title}
   {if $hashbaseref}
     <span class="tag">{$hashbaseref}</span>
   {/if}
   </a>
 </div>
 <div class="page_path">
   {* File path *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hash}&h={$hash}">[{$project->GetProject()}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$path.tree}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hash}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <table cellspacing="0">
   {* Display each history line *}
   {section name=history loop=$historylines}
     <tr class="{cycle values="light,dark"}">
       <td title="{$historylines[history].agestringage}"><em>{$historylines[history].agestringdate}</em></td>
       <td><em>{$historylines[history].authorname}</em></td>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$historylines[history].commit}" class="list"><b>{$historylines[history].title}{if $historylines[history].commitref} <span class="tag">{$historylines[history].commitref}</span>{/if}</b></a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$historylines[history].commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$historylines[history].commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&hb={$historylines[history].commit}&f={$historylines[history].file}">blob</a>{if $historylines[history].blob && $historylines[history].blobparent} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$historylines[history].blob}&hp={$historylines[history].blobparent}&hb={$historylines[history].commit}&f={$historylines[history].file}">diff to current</a>{/if}
       </td>
     </tr>
   {/section}
 </table>

 {include file='footer.tpl'}

