{*
 * blame.tpl
 * gitphp: A PHP git repository browser
 * Component: Blame view template
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commit->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">tree</a><br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob_plain&h={$blob->GetHash()}&f={$blob->GetPath()}">plain</a> | 
   {if $commit->GetHash() != $head->GetHash()}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blame&hb=HEAD&f={$blob->GetPath()}">HEAD</a>
   {else}
     HEAD
   {/if}
    | blame
   <br />
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blob target='blob'}
 
 <div class="page_body">
 	<table class="code">
	{foreach from=$blob->GetData(true) item=blobline name=blob}
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
