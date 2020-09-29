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

<section class="tlp-pane git-repository-commit">
    <div class="tlp-pane-container">
        {include file='tuleap/commit-title-metadata.tpl'}
    </div>
</section>

<section class="tlp-pane">
    <div class="tlp-pane-container">
        <section class="tlp-pane-header git-repository-blob-header">
            {include file='tuleap/blob-header-title.tpl'}

            <div class="git-repository-blob-header-actions">
                <div class="tlp-button-bar">
                    <div class="tlp-button-bar-item">
                        <a href="{$SCRIPT_NAME}?a=blobdiff&amp;h={$blob->GetHash()|urlencode}&amp;hp={$blobparent->GetHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$file|urlencode}&amp;o=sidebyside"
                           class="tlp-button-primary tlp-button-outline tlp-button-small"
                        >
                            <i class="far fa-copy tlp-button-icon"></i> {t domain="gitphp"}Side by side diff{/t}
                        </a>
                    </div>
                    <div class="tlp-button-bar-item">
                        <input type="radio" class="tlp-button-bar-checkbox" checked>
                        <label class="tlp-button-primary tlp-button-outline tlp-button-small">
                            <i class="far fa-file tlp-button-icon"></i> {t domain="gitphp"}Inline diff{/t}
                        </label>
                    </div>
                </div>
            </div>
        </section>
        <section class="tlp-pane-section">
            {include file='tuleap/file-diff.tpl' diff=$filediff->GetDiff($file, false, true)}
        </section>
    </div>
</section>
