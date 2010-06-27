{*
 * blame.tpl
 * gitphp: A PHP git repository browser
 * Component: Blame view template
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">{$resources->GetResource('summary')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$commit->GetHash()}">{$resources->GetResource('commit')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$commit->GetHash()}">{$resources->GetResource('commitdiff')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">{$resources->GetResource('tree')}</a><br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob_plain&h={$blob->GetHash()}&f={$blob->GetPath()}">plain</a> | 
   {if $commit->GetHash() != $head->GetHash()}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blame&hb=HEAD&f={$blob->GetPath()}">HEAD</a>
   {else}
     HEAD
   {/if}
    | blame
   <br />
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$blob target='blob'}
 
 <div class="page_body">
   {if $geshi}
     {$geshihead}
       <td class="ln de1" id="blameData">
        {include file='blamedata.tpl'}
       </td>
     {$geshibody}
     {$geshifoot}
   {else}
 	<table class="code">
	{foreach from=$blob->GetData(true) item=blobline name=blob}
	  {assign var=blamecommit value=$blame[$smarty.foreach.blob.iteration]}
	  {if $blamecommit}
	    {cycle values="light,dark" assign=rowclass}
	  {/if}
	  <tr class="{$rowclass}">
	    <td class="date">
	      {if $blamecommit}
	        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$blamecommit->GetHash()}" title="{$blamecommit->GetTitle()}" class="commitTip">{$blamecommit->GetAuthorEpoch()|date_format:"%F %X"}</a>
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
  {/if}
 </div>

 {include file='footer.tpl'}
