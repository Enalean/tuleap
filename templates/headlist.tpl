{*
 * Headlist
 *
 * Head list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table cellspacing="0">
   {* Loop and display each head *}
   {foreach from=$headlist item=head name=heads}
       {assign var=headcommit value=$head->GetCommit()}
       <tr class="{cycle values="light,dark"}">
         <td><em>{$headcommit->GetAge()|agestring}</em></td>
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/heads/{$head->GetName()}" class="list"><strong>{$head->GetName()}</strong></a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/heads/{$head->GetName()}">{t}shortlog{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log&amp;h=refs/heads/{$head->GetName()}">{t}log{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$headcommit->GetHash()}">{t}tree{/t}</a></td>
       </tr>
   {/foreach}
   {if $hasmoreheads}
       <tr>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=heads">&hellip;</a></td>
       </tr>
   {/if}
 </table>

