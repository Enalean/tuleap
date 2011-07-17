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
    <td class="monospace perms">{$treeitem->GetModeString()}</td>
    {if $treeitem instanceof GitPHP_Blob}
      <td class="filesize">{$treeitem->GetSize()}</td>
      <td></td>
      <td class="list fileName">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="list">{$treeitem->GetName()}</a>
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}blob{/t}</a>
	 | 
	<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=history&amp;h={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}history{/t}</a>
	 | 
	<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$treeitem->GetHash()}&amp;f={$treeitem->GetPath()}">{t}plain{/t}</a>
      </td>
    {elseif $treeitem instanceof GitPHP_Tree}
      <td class="filesize"></td>
      <td class="expander"></td>
      <td class="list fileName">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}" class="treeLink">{$treeitem->GetName()}</a>
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$treeitem->GetHash()}&amp;hb={$commit->GetHash()}&amp;f={$treeitem->GetPath()}">{t}tree{/t}</a>
	 | 
	<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$treeitem->GetHash()}&amp;f={$treeitem->GetPath()}" class="snapshotTip">{t}snapshot{/t}</a>
      </td>
    {/if}
  </tr>
{/foreach}
