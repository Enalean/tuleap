{*
 * blame.tpl
 * gitphp: A PHP git repository browser
 * Component: Blame view template
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

 <div class="page_nav">
   {include file='nav.tpl' treecommit=$commit}
   <br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$blob->GetHash()}&amp;f={$blob->GetPath()|urlencode}"&noheader=1>{t}plain{/t}</a> |
   {if $commit->GetHash() != $head->GetHash()}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blame&amp;hb=HEAD&amp;f={$blob->GetPath()|urlencode}"&noheader=1>{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
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
	        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$blamecommit->GetHash()|urlencode}" title="{$blamecommit->GetTitle()|htmlspecialchars}" class="commitTip"&noheader=1>{$blamecommit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M:%S"}</a>
	      {/if}
	    </td>
	    <td class="author">
	      {if $blamecommit}
	        {$blamecommit->GetAuthor()}
	      {/if}
	    </td>
	    <td class="num"><a id="l{$smarty.foreach.blob.iteration}" href="#l{$smarty.foreach.blob.iteration}" class="linenr"&noheader=1>{$smarty.foreach.blob.iteration}</a></td>
	    <td class="codeline">{$blobline|escape}</td>
	  </tr>
	{/foreach}
	</table>
  {/if}
 </div>

 {include file='footer.tpl'}
