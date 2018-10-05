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
    {if $treeitem->isSubmodule() }
      <td></td>
    {else}
      <td class="monospace perms">{$treeitem->GetModeString()|escape}</td>
    {/if}
    {if $treeitem->isBlob() }
      <td class="filesize">{$treeitem->GetSize()|escape}</td>
      <td></td>
      <td class="list fileName">
        <a href="{$SCRIPT_NAME}?a=blob&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}" class="list">{$treeitem->GetName()|escape}</a>
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?a=blob&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">{t domain="gitphp"}blob{/t}</a>
	 |
	<a href="{$SCRIPT_NAME}?a=history&amp;h={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">{t domain="gitphp"}history{/t}</a>
	 |
	<a href="{$SCRIPT_NAME}?a=blob_plain&amp;h={$treeitem->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}&amp;noheader=1">{t domain="gitphp"}plain{/t}</a>
      </td>
    {elseif $treeitem->isTree() }
      <td class="filesize"></td>
      <td class="expander"></td>
      <td class="list fileName">
        <a href="{$SCRIPT_NAME}?a=tree&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}" class="treeLink">{$treeitem->GetName()|escape}</a>
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?a=tree&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">{t domain="gitphp"}tree{/t}</a>
	 |
	<a href="{$SCRIPT_NAME}?a=snapshot&amp;h={$treeitem->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}&amp;noheader=1" class="snapshotTip">{t domain="gitphp"}snapshot{/t}</a>
      </td>
    {elseif $treeitem->isSubmodule() }
      <td class="filesize"></td>
      <td class="expander"></td>
      <td class="list fileName">{$treeitem->GetName()|escape} @ {$treeitem->GetHash()|escape}</td>
      <td class="link"></td>
    {/if}
  </tr>
{/foreach}
