{*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *}

<section class="tlp-pane-section">

    {if $commit}
        {assign var=treecommit value=$tree->GetCommit()}
        {assign var=treecommittree value=$treecommit->GetTree()}

        {if $tree->GetName()}
            <p>
                <a href="{$SCRIPT_NAME}?a=tree&amp;hb={$treecommit->GetHash()|urlencode}&amp;h={$treecommittree->GetHash()|urlencode}">{$project->GetProject()|escape}</a>/<!--
                -->{foreach from=$tree->GetPathTree() item=pathtreepiece}<!--
                    --><a href="{$SCRIPT_NAME}?a=tree&amp;hb={$treecommit->GetHash()|urlencode}&amp;h={$pathtreepiece->GetHash()|urlencode}&amp;f={$pathtreepiece->GetPath()|urlencode}">{$pathtreepiece->GetName()|escape}</a><!--
                    -->/<!--
                -->{/foreach}<!--
                --><a href="{$SCRIPT_NAME}?a=tree&amp;hb={$treecommit->GetHash()|urlencode}&amp;h={$tree->GetHash()|urlencode}&amp;f={$tree->GetPath()|urlencode}">{$tree->GetName()|escape}</a>/
            </p>
        {/if}
    {/if}

    <table class="tlp-table">
        <thead>
            <tr>
                <th>{t}Name{/t}</th>
            </tr>
        </thead>
        <tbdoy>
            {if $commit}
                {foreach from=$tree_presenter->sorted_content item=treeitem}
                    <tr>
                        {if $treeitem->isBlob() }
                            <td>
                                <a href="{$SCRIPT_NAME}?a=blob&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">
                                    <i class="fa fa-file-text-o fa-fw"></i> {$treeitem->GetName()|escape}
                                </a>
                            </td>
                        {elseif $treeitem->isTree() }
                            <td>
                                <a href="{$SCRIPT_NAME}?a=tree&amp;h={$treeitem->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetPath()|urlencode}">
                                    <i class="fa fa-folder fa-fw"></i> {$treeitem->GetName()|escape}
                                </a>
                            </td>
                        {elseif $treeitem->isSubmodule() }
                            <td><i class="fa fa-folder-o fa-fw"></i> {$treeitem->GetName()|escape} @ {$treeitem->GetHash()|escape}</td>
                        {/if}
                    </tr>
                {/foreach}
            {else}
                <td class="tlp-table-cell-empty">{t}No commits{/t}</td>
            {/if}
        </tbdoy>
    </table>
</section>
