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
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tags">&hellip;</a></td>
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
               <a class="list {if !$tag->LightTag()}tagTip{/if}" href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$tag->GetName()}">{$comment[0]}</a>
             {/if}
           </td>
           <td class="link">
             {if !$tag->LightTag()}
   	       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$tag->GetName()}">{t}tag{/t}</a> | 
             {/if}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$tagcommit->GetHash()}">{t}commit{/t}</a>
	      | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$tagcommit->GetHash()}">{t}shortlog{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$tagcommit->GetHash()}">{t}log{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$tagcommit->GetHash()}">{t}snapshot{/t}</a>
           </td>
         {/if}
       </tr>
     {/foreach}
   </table>
