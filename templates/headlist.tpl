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
     {if ($max > 0) && ($smarty.foreach.heads.iteration == $max)}
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=heads">...</a></td>
     {elseif ($max <= 0) || ($smarty.foreach.heads.iteration < $max)}
       {assign var=headcommit value=$head->GetCommit()}
       <tr class="{cycle values="light,dark"}">
         <td><em>{$headcommit->GetAge()|agestring}</em></td>
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$head->GetName()}" class="list"><strong>{$head->GetName()}</strong></a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$head->GetName()}">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h=refs/heads/{$head->GetName()}">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$headcommit->GetHash()}">{$resources->GetResource('tree')}</a></td>
       </tr>
     {/if}
   {/foreach}
 </table>

