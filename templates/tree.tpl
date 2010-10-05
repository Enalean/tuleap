{*
 *  tree.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

 {* Nav *}
   <div class="page_nav">
     {include file='nav.tpl' current='tree' logcommit=$commit}
     <br /><br />
   </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$tree target='tree'}
 
 <div class="page_body">
   {* List files *}
   <table cellspacing="0">
     {foreach from=$tree->GetContents() item=treeitem}
       <tr class="{cycle values="light,dark"}">
         <td class="monospace">{$treeitem->GetModeString()}</td>
         {if $treeitem instanceof GitPHP_Blob}
	   <td class="filesize">{$treeitem->GetSize()}</td>
           <td class="list">
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="list">{$treeitem->GetName()}</a>
	   </td>
           <td class="link">
	     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}blob{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}history{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$treeitem->GetHash()}&amp;f={$treeitem->GetPath()}">{t}plain{/t}</a>
	   </td>
         {elseif $treeitem instanceof GitPHP_Tree}
	   <td></td>
           <td class="list">
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{$treeitem->GetName()}</a>
	   </td>
           <td class="link">
	     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}tree{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}snapshot{/t}</a>
	   </td>
         {/if}
       </tr>
     {/foreach}
   </table>
 </div>

 {include file='footer.tpl'}

