{*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
        {include file='tuleap/commit-title-metadata.tpl'}
    </div>
</section>

<section class="tlp-pane">
    <div class="tlp-pane-container">
        <section class="tlp-pane-header git-repository-commit-diff-header">
            <h2 class="tlp-pane-title">
                <i class="tlp-pane-title-icon fa-regular fa-copy" aria-hidden="true"></i> {t domain="gitphp"}Modified Files{/t}
            </h2>

            {if ! $commit_presenter->is_diff_between_two_commits}
                <div class="git-repository-commit-diff-actions">
                    <div class="tlp-button-bar">
                        <div class="tlp-button-bar-item">
                            <a href="{$commit_presenter->getCommitListLink()}"
                               class="tlp-button-primary tlp-button-outline tlp-button-small"
                            >
                                <i class="fa-solid fa-list-ul tlp-button-icon" aria-hidden="true"></i> {t domain="gitphp"}List{/t}
                            </a>
                        </div>
                        <div class="tlp-button-bar-item">
                            <a href="{$commit_presenter->getCommitDiffSideBySideLink()}"
                               class="tlp-button-primary tlp-button-outline tlp-button-small"
                            >
                                <i class="fa-regular fa-copy tlp-button-icon" aria-hidden="true"></i> {t domain="gitphp"}Side by side diff{/t}
                            </a>
                        </div>
                        <div class="tlp-button-bar-item">
                            <input type="radio" class="tlp-button-bar-checkbox" checked>
                            <label class="tlp-button-primary tlp-button-outline tlp-button-small">
                                <i class="fa-regular fa-file tlp-button-icon" aria-hidden="true"></i> {t domain="gitphp"}Inline diff{/t}
                            </label>
                        </div>
                    </div>
                </div>
            {/if}
        </section>
        <section>
            {foreach from=$treediff item=filediff}
                <div class="git-repository-commit-diff-file-header">
                    <span class="{$commit_presenter->getStatusClassname($filediff)} git-repository-commit-diff-file-header-element git-repository-commit-file-status"
                    >{$filediff->GetStatus()|escape}</span>
                    <a href="{$commit_presenter->getDiffLink($filediff)}"
                       class="git-repository-commit-diff-file-header-element"
                    >{$filediff->GetFromFile()|escape}</a>
                    <div class="git-repository-commit-diff-file-header-spacer"></div>
                    <span class="git-repository-commit-file-stat-added git-repository-commit-diff-file-header-element">
                        {if (! empty($filediff->hasStats()))}
                            +{$filediff->getAddedStats()}
                        {/if}
                    </span>
                    <span class="git-repository-commit-file-stat-removed git-repository-commit-diff-file-header-element">
                        {if (! empty($filediff->hasStats()))}
                            −{$filediff->getRemovedStats()}
                        {/if}
                    </span>
                </div>
                <section class="tlp-pane-section">
                    {include file='tuleap/file-diff.tpl' diff=$filediff->GetDiff('', true, true)}
                </section>
            {/foreach}
        </section>
    </div>
</section>
