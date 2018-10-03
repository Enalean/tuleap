{*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title"><i class="tlp-pane-title-icon fa fa-files-o"></i> {t}Files{/t}</h1>
        </div>
        <section class="tlp-pane-section">
            <div class="git-repository-blob-diff-header">
                <a href="{$SCRIPT_NAME}?a=commit&amp;h={$commit->GetHash()|urlencode}"
                   class="git-repository-blob-diff-header-link"
                >{t}Commit{/t}</a>
                <div class="tlp-button-bar">
                    <div class="tlp-button-bar-item">
                        <a href="{$SCRIPT_NAME}?a=blobdiff&amp;h={$blob->GetHash()|urlencode}&amp;hp={$blobparent->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$file|urlencode}&amp;o=sidebyside"
                           class="tlp-button-primary tlp-button-outline tlp-button-small tlp-tooltip tlp-tooltip-bottom"
                           data-tlp-tooltip="{t}Side by side diff{/t}"
                        >
                            <i class="fa fa-files-o tlp-button-icon"></i>
                        </a>
                    </div>
                    <div class="tlp-button-bar-item">
                        <input type="radio" class="tlp-button-bar-checkbox" checked>
                        <label class="tlp-button-primary tlp-button-outline tlp-button-small tlp-tooltip tlp-tooltip-bottom"
                               data-tlp-tooltip="{t}Unified diff{/t}"
                        >
                            <i class="fa fa-file-o tlp-button-icon"></i>
                        </label>
                    </div>
                </div>
            </div>
            <div class="git-repository-commit-diff-file-header">
                <span class="git-repository-commit-diff-file-header-element">
                    {include file='path.tpl' pathobject=$blobparent target='blob'}
                </span>
            </div>
            {include file='tuleap/file-diff.tpl' diff=$filediff->GetDiff($file, false, true)}
        </section>
    </div>
</section>
