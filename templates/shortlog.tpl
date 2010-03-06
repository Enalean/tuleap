{*
 *  shortlog.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | shortlog | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h={$hash->GetHash()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hash->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$hash->GetHash()}&hb={$hash->GetHash()}">tree</a>
   <br />
   {if ($hash->GetHash() != $head->GetHash()) || ($page > 0)}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">HEAD</a>
   {else}
     HEAD
   {/if}
     &sdot; 
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash->GetHash()}&pg={$page-1}" accesskey="p" title="Alt-p">prev</a>
   {else}
     prev
   {/if}
     &sdot; 
   {if $hasmore}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash->GetHash()}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
   {else}
     next
   {/if}
   <br />
 </div>
 <div class="title">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary" class="title">&nbsp;</a>
 </div>
 <table cellspacing="0">
   {foreach from=$revlist item=rev}
     <tr class="{cycle values="light,dark"}">
       <td title="{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetAge()|agestring}{else}{$rev->GetCommitterEpoch()|date_format:"%F"}{/if}"><em>{if $rev->GetAge() > 60*60*24*7*2}{$rev->GetCommitterEpoch()|date_format:"%F"}{else}{$rev->GetAge()|agestring}{/if}</em></td>
       <td><em>{$rev->GetAuthorName()}</em></td>
       <td>
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$rev->GetHash()}" class="list" {if strlen($rev->GetTitle()) > 50}title="{$rev->GetTitle()}"{/if}><strong>{$rev->GetTitle(50)}</strong></a>
	 <span class="refs">
	 {foreach from=$rev->GetHeads() item=revhead}
	   <span class="head">
	     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$revhead->GetName()}">{$revhead->GetName()}</a>
	   </span>
	 {/foreach}
	 {foreach from=$rev->GetTags() item=revtag}
	   <span class="tag">
	     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tag&h={$revtag->GetName()}">{$revtag->GetName()}</a>
	   </span>
	 {/foreach}
	 </span>
       </td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$rev->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$rev->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$rev->GetHash()}&hb={$rev->GetHash()}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=snapshot&h={$rev->GetHash()}">snapshot</a>
       </td>
     </tr>
   {/foreach}

   {if $hasmore}
     <tr>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash->GetHash()}&pg={$page+1}" title="Alt-n">next</a></td>
     </tr>
   {/if}
 </table>

 {include file='footer.tpl'}

