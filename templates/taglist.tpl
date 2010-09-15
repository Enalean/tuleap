{*
 * Taglist
 *
 * Tag list template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @packge GitPHP
 * @subpackage Template
 *}

 <table cellspacing="0">
   {foreach from=$taglist item=tag name=tag}
     <tr class="{cycle name=tags values="light,dark"}">
       {if ($max > 0) && ($smarty.foreach.tag.iteration == $max)}
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tags">...</a></td>
       {elseif ($max <= 0) || ($smarty.foreach.tag.iteration < $max)}
	   {assign var=object value=$tag->GetObject()}
	   {assign var=tagcommit value=$tag->GetCommit()}
	   {assign var=objtype value=$tag->GetType()}
           <td><em>{$tagcommit->GetAge()|agestring}</em></td>
           <td>
	   {if $objtype == 'commit'}
		   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$object->GetHash()}" class="list"><strong>{$tag->GetName()}</strong></a>
	   {elseif $objtype == 'tag'}
		   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$tag->GetName()}" class="list"><strong>{$tag->GetName()}</strong></a>
	   {/if}
	   </td>
           <td>
	     {assign var=comment value=$tag->GetComment()}
             {if count($comment) > 0}
               <a class="list" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$tag->GetName()}">{$comment[0]}</a>
             {/if}
           </td>
           <td class="link">
             {if !$tag->LightTag()}
   	       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$tag->GetName()}">{$resources->GetResource('tag')}</a> | 
             {/if}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$tagcommit->GetHash()}">{$resources->GetResource('commit')}</a>
	      | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$tagcommit->GetHash()}">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$tagcommit->GetHash()}">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$tagcommit->GetHash()}">{$resources->GetResource('snapshot')}</a>
           </td>
         {/if}
       </tr>
     {/foreach}
   </table>
