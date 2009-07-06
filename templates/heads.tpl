{*
 *  heads.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Head view template
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
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$head}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$head}">{$localize.commitdiff}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$head}">{$localize.tree}</a>
   <br /><br />
 </div>
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary" class="title">&nbsp;</a>
 </div>
 <table cellspacing="0">
   {* Loop and display each head *}
   {foreach from=$headlist item=head}
     <tr class="{cycle values="light,dark"}">
       <td><i>{$head.age}</i></td>
       <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$head.name}" class="list"><b>{$head.name}</b></a></td>
       {* i18n: shortlog = shortlog *}
       {* i18n: log = log *}
       {* i18n: tree = tree *}
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$head.name}">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$head.name}">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$head.name}&hb={$head.name}">{$localize.tree}</a></td>
     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

