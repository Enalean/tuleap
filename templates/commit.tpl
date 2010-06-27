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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">{$resources->GetResource('summary')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}">{$resources->GetResource('log')}</a> | {$resources->GetResource('commit')} | {if $commit->GetParent()}<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$commit->GetHash()}">{$resources->GetResource('commitdiff')}</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">{$resources->GetResource('tree')}</a>
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
       <td>{$resources->GetResource('author')}</td>
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
       <td>{$resources->GetResource('committer')}</td>
       <td>{$commit->GetCommitterName()}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} ({$commit->GetCommitterLocalEpoch()|date_format:"%R"} {$commit->GetCommitterTimezone()})</td>
     </tr>
     <tr>
       <td>{$resources->GetResource('commit')}</td>
       <td class="monospace">{$commit->GetHash()}</td>
     <tr>
     <tr>
       <td>{$resources->GetResource('tree')}</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}" class="list">{$tree->GetHash()}</a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">{$resources->GetResource('tree')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=snapshot&h={$commit->GetHash()}">{$resources->GetResource('snapshot')}</a></td>
     </tr>
     {foreach from=$commit->GetParents() item=par}
       <tr>
         <td>{$resources->GetResource('parent')}</td>
	 <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$par->GetHash()}" class="list">{$par->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$par->GetHash()}">{$resources->GetResource('commit')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$commit->GetHash()}&hp={$par->GetHash()}">{$resources->GetResource('commitdiff')}</a></td>
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
     {$resources->Format('%1$d files changed:', $treediff->Count())|escape}
   {/if}
 </div>
 <table cellspacing="0">
   {* Loop and show files changed *}
   {foreach from=$treediff item=diffline}
     <tr class="{cycle values="light,dark"}">
	 
       {if $diffline->GetStatus() == "A"}
         <td>
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}" class="list">
	     {$diffline->GetFromFile()}
	   </a>
	 </td>
         <td>
	   <span class="newfile">
	     {assign var=localtotype value=$resources->GetResource($diffline->GetToFileType())}
	     [
	     {if $diffline->ToFileIsRegular()}
	     {$resources->Format('new %1$s with mode %2$s', $localtotype, $diffline->GetToModeShort())|escape}
	     {else}
	     {$resources->Format('new %1$s', $localtotype)|escape}
	     {/if}
	     ]
	   </span>
	 </td>
         <td class="link">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}">{$resources->GetResource('blob')}</a>
	 </td>
       {elseif $diffline->GetStatus() == "D"}
         <td>
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}" class="list">
	     {$diffline->GetFromFile()}
	   </a>
	 </td>
         <td>
	   <span class="deletedfile">
	     {assign var=localfromtype value=$resources->GetResource($diffline->GetFromFileType())}
	     [ {$resources->Format('deleted %1$s', $localfromtype)|escape} ]
	   </span>
	 </td>
         <td class="link">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}">{$resources->GetResource('blob')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=history&h={$commit->GetHash()}&f={$diffline->GetFromFile()}">{$resources->GetResource('history')}</a>
	 </td>
       {elseif $diffline->GetStatus() == "M" || $diffline->GetStatus() == "T"}
         <td>
           {if $diffline->GetToHash() != $diffline->GetFromHash()}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blobdiff&h={$diffline->GetToHash()}&hp={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}" class="list">
	       {$diffline->GetToFile()}
	     </a>
           {else}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}" class="list">
	       {$diffline->GetToFile()}
	     </a>
           {/if}
         </td>
         <td>
	   {if $diffline->GetFromMode() != $diffline->GetToMode()}
	     <span class="changedfile">
	       [
	       {if $diffline->FileTypeChanged()}
	     	 {assign var=localfromtype value=$resources->GetResource($diffline->GetFromFileType())}
	     	 {assign var=localtotype value=$resources->GetResource($diffline->GetToFileType())}
	         {if $diffline->FileModeChanged()}
		   {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
		     {$resources->Format('changed from %1$s to %2$s mode: %3$s -> %4$s', $localfromtype, $localtotype, $diffline->GetFromModeShort(), $diffline->GetToModeShort())|escape}
		   {elseif $diffline->ToFileIsRegular()}
		     {$resources->Format('changed from %1$s to %2$s mode: %3$s', $localfromtype, $localtotype, $diffline->GetToModeShort())|escape}
		   {else}
		     {$resources->Format('changed from %1$s to %2$s', $localfromtype, $localtotype)|escape}
		   {/if}
		 {else}
		   {$resources->Format('changed from %1$s to %2$s', $localfromtype, $localtotype)|escape}
		 {/if}
	       {else}
	         {if $diffline->FileModeChanged()}
		   {if $diffline->FromFileIsRegular() && $diffline->ToFileIsRegular()}
		     {$resources->Format('changed mode: %1$s -> %2$s', $diffline->GetFromModeShort(), $diffline->GetToModeShort())|escape}
		   {elseif $diffline->ToFileIsRegular()}
		     {$resources->Format('changed mode: %1$s', $diffline->GetToModeShort())|escape}
		   {else}
		     {$resources->GetResource('changed')}
		   {/if}
		 {else}
		   {$resources->GetResource('changed')}
		 {/if}
	       {/if}
	       ]
	     </span>
	   {/if}
	 </td>
         <td class="link">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">{$resources->GetResource('blob')}</a>
	   {if $diffline->GetToHash() != $diffline->GetFromHash()}
	     | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blobdiff&h={$diffline->GetToHash()}&hp={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">{$resources->GetResource('diff')}</a>
	   {/if}
	     | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=history&h={$commit->GetHash()}&f={$diffline->GetFromFile()}">{$resources->GetResource('history')}</a>
	 </td>
       {elseif $diffline->GetStatus() == "R"}
         <td>
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}" class="list">
	     {$diffline->GetToFile()}</a>
	 </td>
         <td>
	   <span class="movedfile">
	     {capture assign=fromfilelink}
	     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetFromFile()}" class="list">{$diffline->GetFromFile()}</a>
	     {/capture}
	     [
	     {if $diffline->GetFromMode() != $diffline->GetToMode()}
	       {$resources->Format('moved from %1$s with %2$d%% similarity, mode: %3$s', $fromfilelink, $diffline->GetSimilarity(), $diffline->GetToModeShort())}
	     {else}
	       {$resources->Format('moved from %1$s with %2$d%% similarity', $fromfilelink, $diffline->GetSimilarity())}
	     {/if}
	     ]
	   </span>
	 </td>
         <td class="link">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$diffline->GetToHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">{$resources->GetResource('blob')}</a>
	   {if $diffline->GetToHash() != $diffline->GetFromHash()}
	     | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blobdiff&h={$diffline->GetToHash()}&hp={$diffline->GetFromHash()}&hb={$commit->GetHash()}&f={$diffline->GetToFile()}">{$resources->GetResource('diff')}</a>
	   {/if}
	 </td>
       {/if}

     </tr>
   {/foreach}
 </table>

 {include file='footer.tpl'}

