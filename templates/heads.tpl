{*
 *  heads_nav.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Head view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$head}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$head}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$head}">tree</a>
   <br /><br />
 </div>
 <div>
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary" class="title">&nbsp;</a>
 </div>
 <table cellspacing="0">
   {* Loop and display each head *}
   {foreach from=$headlist item=head}
     {assign var=commit value=$head->GetCommit()}
     <tr class="{cycle values="light,dark"}">
       <td><em>{$commit->GetAge()|agestring}</em></td>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$head->GetName()}" class="list"><strong>{$head->GetName()}</strong></a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$head->GetName()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h=refs/heads/{$head->GetName()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h=refs/heads/{$head->GetName()}&hb={$commit->GetHash()}">tree</a></td>
     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

