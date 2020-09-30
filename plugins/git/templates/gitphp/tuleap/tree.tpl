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

<div class="git-repository-files-readme">
    <section class="tlp-pane git-repository-files">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                    {if $commit && $tree->GetName()}
                        <h1 class="git-repository-files-title">
                            <i class="tlp-pane-title-icon far fa-copy"></i>
                            {assign var=treecommit value=$tree->GetCommit()}
                            {assign var=treecommittree value=$treecommit->GetTree()}
                            <a href="{$SCRIPT_NAME}?a=tree&amp;hb={$treecommit->GetHash()|urlencode}&amp;h={$treecommittree->GetHash()|urlencode}">{$project->GetProject()|escape}</a>/<!--
                                -->{foreach from=$tree->GetPathTree() item=pathtreepiece}<!--
                                --><a href="{$SCRIPT_NAME}?a=tree&amp;hb={$treecommit->GetHash()|urlencode}&amp;h={$pathtreepiece->GetHash()|urlencode}&amp;f={$pathtreepiece->GetPath()|urlencode}">{$pathtreepiece->GetName()|escape}</a><!--
                                -->/<!--
                            -->{/foreach}<!--
                            --><a href="{$SCRIPT_NAME}?a=tree&amp;hb={$treecommit->GetHash()|urlencode}&amp;h={$tree->GetHash()|urlencode}&amp;f={$tree->GetPath()|urlencode}">{$tree->GetName()|escape}</a>/
                        </h1>
                    {else}
                        <h1 class="tlp-pane-title">
                            <i class="tlp-pane-title-icon far fa-copy"></i>
                            {t domain="gitphp"}Files{/t}
                        </h1>
                    {/if}
            </div>
            <section class="tlp-pane-section">
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th>{t domain="gitphp"}Name{/t}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {if $commit}
                            {foreach from=$tree_presenter->sorted_content item=treeitem}
                                <tr>
                                    {if $treeitem->isBlob() }
                                        <td>
                                            <a href="{$SCRIPT_NAME}?a=blob&amp;hb={$commit->GetHash()|urlencode}&amp;h={$treeitem->GetHash()|urlencode}&amp;f={$treeitem->GetFullPath()|urlencode}">
                                                <i class="far fa-file-alt fa-fw git-repository-tree-icon"></i>{$treeitem->GetName()|escape}
                                            </a>
                                        </td>
                                    {elseif $treeitem->isTree() }
                                        <td>
                                            <a href="{$SCRIPT_NAME}?a=tree&amp;hb={$commit->GetHash()|urlencode}&amp;f={$treeitem->GetFullPath()|urlencode}">
                                                <i class="fa fa-folder fa-fw git-repository-tree-icon"></i>{$treeitem->GetName()|escape}
                                            </a>
                                        </td>
                                    {elseif $treeitem->isSubmodule() }
                                        <td><i class="far fa-folder fa-fw git-repository-tree-icon"></i>{$treeitem->GetName()|escape} @ {$treeitem->GetHash()|escape}</td>
                                    {/if}
                                </tr>
                            {/foreach}
                        {else}
                            <td class="tlp-table-cell-empty">{t domain="gitphp"}No commits{/t}</td>
                        {/if}
                    </tbody>
                </table>
            </section>
        </div>
    </section>

    {if $readme_content}
        <section class="tlp-pane git-repository-readme">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        <i class="far fa-file-alt tlp-pane-title-icon"></i> {$readme_content->GetName()|escape}
                    </h1>
                </div>
                <section class="tlp-pane-section git-repository-readme-content">
                    {$readme_content_interpreted}
                </section>
            </div>
        </section>
    {/if}
</div>
