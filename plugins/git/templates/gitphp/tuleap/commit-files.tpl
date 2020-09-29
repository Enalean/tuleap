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

<section class="tlp-pane-header git-repository-commit-diff-header">
    <h2 class="tlp-pane-title">
        <i class="tlp-pane-title-icon fa fa-copy"></i> {t domain="gitphp"}Modified Files{/t}
    </h2>
    <div class="git-repository-commit-diff-actions">
        <div class="tlp-button-bar">
            <div class="tlp-button-bar-item">
                <input type="radio" class="tlp-button-bar-checkbox" checked>
                <label class="tlp-button-primary tlp-button-outline tlp-button-small">
                    <i class="fa fa-list"></i>  {t domain="gitphp"}List{/t}
                </label>
            </div>
            <div class="tlp-button-bar-item">
                <a href="{$commit_presenter->getCommitDiffSideBySideLink()}"
                   class="tlp-button-primary tlp-button-outline tlp-button-small"
                >
                    <i class="far fa-copy tlp-button-icon"></i> {t domain="gitphp"}Side by side diff{/t}
                </a>
            </div>
            <div class="tlp-button-bar-item">
                <a href="{$commit_presenter->getCommitDiffLink()}"
                   class="tlp-button-primary tlp-button-outline tlp-button-small"
                >
                    <i class="far fa-file tlp-button-icon"></i> {t domain="gitphp"}Inline diff{/t}
                </a>
            </div>
        </div>
    </div>
</section>
<section class="tlp-pane-section">
    <table class="tlp-table">
        <thead>
            <tr>
                <th></th>
                <th>{t domain="gitphp"}Name{/t}</th>
                <th class="tlp-table-cell-numeric"></th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$treediff item=diffline}
            <tr>
                <td class="git-repository-commit-file-status {$commit_presenter->getStatusClassname($diffline)}">{$diffline->GetStatus()|escape}</td>
                <td>{$diffline->GetFromFile()|escape}</td>
                {if (! $diffline->isBinaryFile())}
                    <td class="tlp-table-cell-numeric git-repository-commit-file-stat-added">
                        {if (! empty($diffline->hasStats()))}
                            +{$diffline->getAddedStats()}
                        {/if}
                    </td>
                    <td class="git-repository-commit-file-stat-removed">
                        {if (! empty($diffline->hasStats()))}
                            −{$diffline->getRemovedStats()}
                        {/if}
                    </td>
                {/if}
                {if ($diffline->isBinaryFile())}
                    <td class="git-repository-commit-file-stat-binary" colspan="2">
                        {t domain="gitphp"}Binary file{/t}
                    </td>
                {/if}
                <td class="tlp-table-cell-actions">
                    <a href="{$commit_presenter->getDiffLink($diffline)}"
                       class="tlp-table-cell-actions-button tlp-button-primary tlp-button-outline tlp-button-small"
                    >
                        <i class="fas fa-long-arrow-alt-right tlp-button-icon"></i> {t domain="gitphp"}Go to diff{/t}
                    </a>
                    <a href="{$SCRIPT_NAME}?a=blob&amp;h={$diffline->GetToHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}&amp;f={$diffline->GetToFile()}"
                       class="tlp-table-cell-actions-button tlp-button-primary tlp-button-outline tlp-button-small"
                    >
                        <i class="far fa-file-alt tlp-button-icon"></i> {t}View file{/t}
                    </a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</section>
