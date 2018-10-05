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

 <table cellspacing="0" class="tagTable">
   {foreach from=$taglist item=tag name=tag}
     <tr class="{cycle name=tags values="light,dark"}">
	   {assign var=object value=$tag->GetObject()}
	   {assign var=tagcommit value=$tag->GetCommit()}
	   {assign var=objtype value=$tag->GetType()}
           <td><em>{if $tagcommit}{$tagcommit->GetAge()|agestring|escape}{else}{$tag->GetAge()|agestring|escape}{/if}</em></td>
           <td>
	   {if $objtype == 'commit'}
		   <a href="{$SCRIPT_NAME}?a=commit&amp;h={$object->GetHash()|urlencode}" class="list"><strong>{$tag->GetName()|escape}</strong></a>
	   {elseif $objtype == 'tag'}
		   <a href="{$SCRIPT_NAME}?a=tag&amp;h={$tag->GetName()|urlencode}" class="list"><strong>{$tag->GetName()|escape}</strong></a>
	   {elseif $objtype == 'blob'}
		   <a href="{$SCRIPT_NAME}?a=tag&amp;h={$tag->GetName()|urlencode}" class="list"><strong>{$tag->GetName()|escape}</strong></a>
	   {/if}
	   </td>
           <td>
	     {assign var=comment value=$tag->GetComment()}
             {if count($comment) > 0}
               <a class="list {if !$tag->LightTag()}tagTip{/if}" href="{$SCRIPT_NAME}?a=tag&amp;h={$tag->GetName()|urlencode}">{$comment[0]|escape}</a>
             {/if}
           </td>
           <td class="link">
             {if !$tag->LightTag()}
               <a href="{$SCRIPT_NAME}?a=tag&amp;h={$tag->GetName()|urlencode}">{t domain="gitphp"}tag{/t}</a> |
             {/if}
	     {if $objtype == 'blob'}
		<a href="{$SCRIPT_NAME}?a=blob&amp;h={$object->GetHash()|urlencode}">{t domain="gitphp"}blob{/t}</a>
	     {else}
             <a href="{$SCRIPT_NAME}?a=commit&amp;h={$tagcommit->GetHash()|urlencode}">{t domain="gitphp"}commit{/t}</a>
	      | <a href="{$SCRIPT_NAME}?a=shortlog&amp;h={$tagcommit->GetHash()|urlencode}">{t domain="gitphp"}log{/t}</a> | <a href="{$SCRIPT_NAME}?a=snapshot&amp;h={$tagcommit->GetHash()|urlencode}&amp;noheader=1" class="snapshotTip">{t domain="gitphp"}snapshot{/t}</a>
	      {/if}
           </td>
       </tr>
     {/foreach}
     {if $hasmoretags}
       <tr>
         <td><a href="{$SCRIPT_NAME}?a=tags">&hellip;</a></td>
       </tr>
     {/if}
   </table>
