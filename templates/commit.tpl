{*
 *  commit.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 <div class="page_nav">
   {* Nav *}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$commit->GetHash()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h={$commit->GetHash()}">log</a> | commit | {if $commit->GetParent()}<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}">commitdiff</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">tree</a>
   <br /><br />
 </div>

{if $commit->GetParent()}
 	{include file='title.tpl' titlecommit=$commit target='commitdiff'}
{else}
	{include file='title.tpl' titlecommit=$commit titletree=$tree target='tree'}
{/if}
 
 <div class="title_text">
   {* Commit data *}
   <table cellspacing="0">
     <tr>
       <td>author</td>
       <td>{$commit->GetAuthorName()}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} 
       {assign var=hourlocal value=$commit->GetAuthorLocalEpoch()|date_format:"%H"}
       {if $hourlocal < 6}
       (<span class="latenight">{$commit->GetAuthorLocalEpoch()|date_format:"%R"}</span> {$commit->GetAuthorTimezone()})</td>
       {else}
       ({$commit->GetAuthorLocalEpoch()|date_format:"%R"} {$commit->GetAuthorTimezone()})</td>
       {/if}
     </tr>
     <tr>
       <td>committer</td>
       <td>{$commit->GetCommitterName()}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} ({$commit->GetCommitterLocalEpoch()|date_format:"%R"} {$commit->GetCommitterTimezone()})</td>
     </tr>
     <tr>
       <td>commit</td>
       <td class="monospace">{$commit->GetHash()}</td>
     <tr>
     <tr>
       <td>tree</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}" class="list">{$tree->GetHash()}</a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=snapshot&h={$commit->GetHash()}">snapshot</a></td>
     </tr>
     {foreach from=$commit->GetParents() item=par}
       <tr>
         <td>parent</td>
	 <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$par->GetHash()}" class="list">{$par->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$par->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}&hp={$par->GetHash()}">commitdiff</a></td>
       </tr>
     {/foreach}
   </table>
 </div>
 <div class="page_body">
   {foreach from=$commit->GetComment() item=line}
     {$line}<br />
   {/foreach}
 </div>
 <div class="list_head">
   {if $treediff->Count() > 10}
     {$treediff->Count()} files changed:
   {/if}
 </div>
 <table cellspacing="0">
   {* Loop and show files changed *}
   {foreach from=$treediff item=diffline}
     <tr class="{cycle values="light,dark"}">
	 
       {if $diffline->GetStatus() == "A"}
         <td>
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}" class="list">
	     {$diffline->GetFromFile()}
	   </a>
	 </td>
         <td>
	   <span class="newfile">
	     [
	     {if $diffline->ToFileIsRegular()}
	     new {$diffline->GetToFileType()} with mode: {$diffline->GetToModeShort()}
	     {else}
	     new {$diffline->GetToFileType()}
	     {/if}
	     ]
	   </span>
	 </td>
         <td class="link">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}">blob</a>
	 </td>
       {elseif $diffline->GetStatus() == "D"}
         <td>
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}" class="list">
	     {$diffline->GetFromFile()}
	   </a>
	 </td>
         <td>
	   <span class="deletedfile">
	     [ deleted {$diffline->GetFromFileType()} ]
	   </span>
	 </td>
         <td class="link">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=history&h={$commit->GetHash()}&f={$diffline->GetFromFile()}">history</a>
	 </td>
       {elseif $diffline->GetStatus() == "M" || $diffline->GetStatus() == "T"}
         <td>
           {if $diffline->GetToHash() != $diffline->GetFromHash()}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$diffline->GetToHash()}&hp={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}" class="list">
	       {$diffline->GetToFile()}
	     </a>
           {else}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}" class="list">
	       {$diffline->GetToFile()}
	     </a>
           {/if}
         </td>
         <td>
	   {if $diffline->GetFromMode() != $diffline->GetToMode()}
	     <span class="changedfile">
	       [
	       {if $diffline->FileTypeChanged()}
	         {if $diffline->FileModeChanged()}
		   {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
		     changed from {$diffline->GetFromFileType()} to {$diffline->GetToFileType()} mode: {$diffline->GetFromModeShort()} -&gt; {$diffline->GetToModeShort()}
		   {elseif $diffline->ToFileIsRegular()}
		     changed from {$diffline->GetFromFileType()} to {$diffline->GetToFileType()} mode: {$diffline->GetToModeShort()}
		   {else}
		     changed from {$diffline->GetFromFileType()} to {$diffline->GetToFileType()}
		   {/if}
		 {else}
		   changed from {$diffline->GetFromFileType()} to {$diffline->GetToFileType()}
		 {/if}
	       {else}
	         {if $diffline->FileModeChanged()}
		   {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
		     changed mode: {$diffline->GetFromModeShort()} -&gt; {$diffline->GetToModeShort()}
		   {elseif $diffline->ToFileIsRegular()}
		     changed mode: {$diffline->GetToModeShort()}
		   {else}
		     changed
		   {/if}
		 {else}
		   changed
		 {/if}
	       {/if}
	       ]
	     </span>
	   {/if}
	 </td>
         <td class="link">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">blob</a>
	   {if $diffline->GetToHash() != $diffline->GetFromHash()}
	     | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$diffline->GetToHash()}&hp={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">diff</a>
	   {/if}
	     | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=history&h={$commit->GetHash()}&f={$diffline->GetFromFile()}">history</a>
	 </td>
       {elseif $diffline->GetStatus() == "R"}
         <td>
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}" class="list">
	     {$diffline->GetToFile()}</a>
	 </td>
         <td>
	   <span class="movedfile">
	     [
	     {if $diffline->GetFromMode() != $diffline->GetToMode()}
	       moved from <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}" class="list">{$diffline->GetFromFile()}</a> with {$diffline->GetSimilarity()}% similarity, mode: {$diffline->GetToModeShort()}
	     {else}
	       moved from <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}" class="list">{$diffline->GetFromFile()}</a> with {$diffline->GetSimilarity()}% similarity
	     {/if}
	     ]
	   </span>
	 </td>
         <td class="link">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">blob</a>
	   {if $diffline->GetToHash() != $diffline->GetFromHash()}
	     | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$diffline->GetToHash()}&hp={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">diff</a>
	   {/if}
	 </td>
       {/if}

     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

