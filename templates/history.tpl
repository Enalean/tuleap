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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hash->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$hash->GetHash()}">tree</a>
   <br /><br />
 </div>

 {include file='title.tpl' titlecommit=$hash}

 <div class="page_path">
   {* File path *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hash->GetHash()}&h={$tree->GetHash()}">[{$project->GetProject()}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$path.tree}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hash->GetHash()}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <table cellspacing="0">
   {* Display each history line *}
   {foreach from=$blob->GetHistory() item=historyitem}
     {assign var=historycommit value=$historyitem->GetCommit()}
     <tr class="{cycle values="light,dark"}">
       <td title="{if $historycommit->GetAge() > 60*60*24*7*2}{$historycommit->GetAge()|agestring}{else}{$historycommit->GetCommitterEpoch()|date_format:"%F"}{/if}"><em>{if $historycommit->GetAge() > 60*60*24*7*2}{$historycommit->GetCommitterEpoch()|date_format:"%F"}{else}{$historycommit->GetAge()|agestring}{/if}</em></td>
       <td><em>{$historycommit->GetAuthorName()}</em></td>
       <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$historycommit->GetHash()}" class="list" {if strlen($historycommit->GetTitle()) > 50}title="{$historycommit->GetTitle()}"{/if}><strong>{$historycommit->GetTitle(50)}</strong></a>
       <span class="refs">
       {foreach from=$historycommit->GetHeads() item=historyhead}
         <span class="head">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$historyhead->GetName()}">{$historyhead->GetName()}</a>
	 </span>
       {/foreach}
       {foreach from=$historycommit->GetTags() item=historytag}
         <span class="tag">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tag&h={$historytag->GetName()}">{$historytag->GetName()}</a>
	 </span>
       {/foreach}
       </span>
       </td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$historycommit->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$historycommit->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&hb={$historycommit->GetHash()}&f={$blob->GetPath()}">blob</a>{if $blob->GetHash() != $historyitem->GetToHash()} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$blob->GetHash()}&hp={$historyitem->GetToHash()}&hb={$historycommit->GetHash()}&f={$blob->GetPath()}">diff to current</a>{/if}
       </td>
     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

