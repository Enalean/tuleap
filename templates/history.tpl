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
   {* i18n: summary = summary *}
   {* i18n: shortlog = shortlog *}
   {* i18n: log = log *}
   {* i18n: commit = commit *}
   {* i18n: commitdiff = commitdiff *}
   {* i18n: tree = tree *}
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">{$localize.tree}</a>
   <br /><br />
 </div>
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}" class="title">{$title}
   {if $hashbaseref}
     <span class="tag">{$hashbaseref}</span>
   {/if}
   </a>
 </div>
 <div class="page_path">
   {* File path *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hash}&h={$hash}">[{$project}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$path.tree}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hash}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <table cellspacing="0">
   {* Display each history line *}
   {section name=history loop=$historylines}
     <tr class="{cycle values="light,dark"}">
       <td title="{$historylines[history].agestringage}"><i>{$historylines[history].agestringdate}</i></td>
       <td><i>{$historylines[history].authorname}</i></td>
       <td><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$historylines[history].commit}" class="list"><b>{$historylines[history].title}{if $historylines[history].commitref} <span class="tag">{$historylines[history].commitref}</span>{/if}</b></a></td>
       {* i18n: difftocurrent = diff to current *}
       {* i18n: blob = blob *}
       {* i18n: commit = commit *}
       {* i18n: commitdiff = commitdiff *}
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$historylines[history].commit}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$historylines[history].commit}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=blob&hb={$historylines[history].commit}&f={$historylines[history].file}">{$localize.blob}</a>{if $historylines[history].blob && $historylines[history].blobparent} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$historylines[history].blob}&hp={$historylines[history].blobparent}&hb={$historylines[history].commit}&f={$historylines[history].file}">{$localize.difftocurrent}</a>{/if}
       </td>
     </tr>
   {/section}
 </table>

 {include file='footer.tpl'}

