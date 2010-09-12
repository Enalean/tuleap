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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">{$resources->GetResource('summary')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$head}">{$resources->GetResource('commit')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$head}">{$resources->GetResource('commitdiff')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$head}">{$resources->GetResource('tree')}</a>
   <br /><br />
 </div>

 {include file='title.tpl' target='summary'}
 
 <table cellspacing="0">
   {* Loop and display each head *}
   {foreach from=$headlist item=head}
     {assign var=headcommit value=$head->GetCommit()}
     <tr class="{cycle values="light,dark"}">
       <td><em>{$headcommit->GetAge()|agestring}</em></td>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$head->GetName()}" class="list"><strong>{$head->GetName()}</strong></a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$head->GetName()}">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h=refs/heads/{$head->GetName()}">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$headcommit->GetHash()}">{$resources->GetResource('tree')}</a></td>
     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

