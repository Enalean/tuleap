{*
 * Tree list
 *
 * Tree filelist template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

{foreach from=$tree->GetContents() item=treeitem}
  <tr class="{cycle values="light,dark"}">
    <td class="monospace perms">{$treeitem->GetModeString()|escape}</td>
    {if $treeitem instanceof GitPHP_Blob}
      <td class="filesize">{$treeitem->GetSize()|escape}</td>
      <td></td>
      <td class="list fileName">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}" class="list">{$treeitem->GetName()|escape}</a>
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">{t}blob{/t}</a>
	 | 
	<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">{t}history{/t}</a>
	 | 
	<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$treeitem->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}&amp;noheader=1">{t}plain{/t}</a>
      </td>
    {elseif $treeitem instanceof GitPHP_Tree}
      <td class="filesize"></td>
      <td class="expander"></td>
      <td class="list fileName">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}" class="treeLink">{$treeitem->GetName()|escape}</a>
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">{t}tree{/t}</a>
	 | 
	<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$treeitem->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}&amp;noheader=1" class="snapshotTip">{t}snapshot{/t}</a>
      </td>
    {/if}
  </tr>
{/foreach}
