{*
 * blame.tpl
 * gitphp: A PHP git repository browser
 * Component: Blame view template
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* If we managed to look up commit info, we have enough info to display the full header - othewise just use a simple header *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hashbase->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hashbase->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$hashbase->GetHash()}">tree</a><br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob_plain&h={$hash->GetHash()}&f={$hash->GetPath()}">plain</a> | 
   {if $hashbase->GetHash() != $head->GetHash()}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blame&hb=HEAD&f={$hash->GetPath()}">HEAD</a>
   {else}
     HEAD
   {/if}
    | blame
   <br />
 </div>
 <div class="title">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hashbase->GetHash()}" class="title">{$hashbase->GetTitle()}</a>
   <span class="refs">
   {foreach from=$hashbase->GetHeads() item=head}
     <span class="head">
       <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$head->GetName()}">{$head->GetName()}</a>
     </span>
   {/foreach}
   {foreach from=$hashbase->GetTags() item=tag}
     <span class="tag">
       <a href="{$SCIRPT_NAME}?p={$project->GetProject()}&a=tag&h={$tag->GetName()}">{$tag->GetName()}</a>
     </span>
   {/foreach}
   </span>
 </div>
 <div class="page_path">
   {* The path to the file, with directories broken into tree links *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hashbase->GetHash()}&h={$hashbase->GetHash()}">[{$project->GetProject()}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob_plain&h={$path.tree}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$hashbase->GetHash()}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <div class="page_body">
 	<table class="code">
	{foreach from=$hash->GetData(true) item=blobline name=blob}
	  {assign var=blamecommit value=$blame[$smarty.foreach.blob.iteration]}
	  {if $blamecommit}
	    {cycle values="light,dark" assign=rowclass}
	  {/if}
	  <tr class="{$rowclass}">
	    <td class="date">
	      {if $blamecommit}
	        <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$blamecommit->GetHash()}" title="{$blamecommit->GetTitle()}">{$blamecommit->GetAuthorEpoch()|date_format:"%F %X"}</a>
	      {/if}
	    </td>
	    <td class="author">
	      {if $blamecommit}
	        {$blamecommit->GetAuthor()}
	      {/if}
	    </td>
	    <td class="num"><a id="l{$smarty.foreach.blob.iteration}" href="#l{$smarty.foreach.blob.iteration}" class="linenr">{$smarty.foreach.blob.iteration}</a></td>
	    <td class="codeline">{$blobline|escape}</td>
	  </tr>
	{/foreach}
	</table>
 </div>

 {include file='footer.tpl'}
