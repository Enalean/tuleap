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

<div class="tlp-pane-header git-repository-commit-header">
    <h2 class="git-repository-commit-title">
        <span>
            {php}
                $commit_presenter = $this->get_template_vars('commit_presenter');
                $commit           = $this->get_template_vars('commit');
                echo $commit_presenter->purifier->purify(
                    $commit->GetTitle(),
                    CODENDI_PURIFIER_BASIC_NOBR,
                    HTTPRequest::instance()->getProject()->getID()
                );
            {/php}
        </span>
        {include file='tuleap/refs-badges.tpl' commit=$commit}
    </h2>
    <div class="git-repository-commit-header-actions">
        <a href="{$SCRIPT_NAME}?a=commitdiff_plain&amp;h={$commit->GetHash()|escape}&amp;noheader=1"
           class="tlp-button-primary tlp-button-outline tlp-button-small git-repository-commit-action"
           title="{t domain="gitphp"}Download commit as plain text file{/t}"
        >
            {t domain="gitphp"}Download{/t}
        </a>
        <a href="{$SCRIPT_NAME}?a=tree&amp;hb={$commit->GetHash()|escape}"
           class="tlp-button-primary tlp-button-outline tlp-button-small git-repository-commit-action"
           title="{t domain="gitphp"}Browse repository at this commit{/t}"
        >
            {t domain="gitphp"}Browse{/t}
        </a>
    </div>
</div>

<section class="tlp-pane-section git-repository-commit-info">
    {if $commit_presenter->has_description}
        <p class="git-repository-commit-description">{php}
    $commit_presenter = $this->get_template_vars('commit_presenter');
    $description      = $commit_presenter->description;
    echo $commit_presenter->purifier->purify(
        $description,
        CODENDI_PURIFIER_BASIC_NOBR,
        HTTPRequest::instance()->getProject()->getID()
    );
{/php}
        </p>
    {/if}
    <div class="git-repository-commit-metadata">
        <div class="git-repository-commit-metadata-changes">
            <span class="git-repository-commit-metadata-changes-added">+{$commit_presenter->stats_added|escape}</span>
            <span class="git-repository-commit-metadata-changes-removed">−{$commit_presenter->stats_removed|escape}</span>
        </div>

        <div class="git-repository-commit-metadata-properties">
            <div class="git-repository-commit-metadata-properties-group">
                <div class="tlp-property">
                    <label class="tlp-label">{t domain="gitphp"}Author{/t}</label>

                    <div class="git-repository-commit-metadata-username-date">
                        {if ($commit_presenter->author->is_a_tuleap_user)}
                            <a href="{$commit_presenter->author->url|escape}">
                                <div class="tlp-avatar git-repository-commit-metadata-username-avatar">
                                    {if ($commit_presenter->author->has_avatar)}
                                        <img src="{$commit_presenter->author->avatar_url|escape}">
                                    {/if}
                                </div><!--
                                    -->{$commit_presenter->author->display_name|escape}
                            </a>
                        {else}
                            <div class="tlp-avatar git-repository-commit-metadata-username-avatar"></div>
                            {$commit->getAuthorName()|escape}
                        {/if}

                        <span class="tlp-text-muted git-repository-commit-metadata-date">
                            <i class="far fa-clock"></i> {$commit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M"}
                        </span>
                    </div>
                </div>

                <div class="tlp-property">
                    <label class="tlp-label">{t domain="gitphp"}Committer{/t}</label>
                    <div class="git-repository-commit-metadata-username-date">
                        {if ($commit_presenter->committer->is_a_tuleap_user)}
                            <a href="{$commit_presenter->committer->url|escape}">
                                <div class="tlp-avatar git-repository-commit-metadata-username-avatar">
                                    {if ($commit_presenter->committer->has_avatar)}
                                        <img src="{$commit_presenter->committer->avatar_url|escape}">
                                    {/if}
                                </div><!--
                                    -->{$commit_presenter->committer->display_name|escape}
                            </a>
                        {else}
                            <div class="tlp-avatar git-repository-commit-metadata-username-avatar"></div>
                            {$commit->GetCommitterName()|escape}
                        {/if}

                        <span class="tlp-text-muted git-repository-commit-metadata-date">
                            <i class="far fa-clock"></i> {$commit->GetCommitterEpoch()|date_format:"%Y-%m-%d %H:%M"}
                        </span>
                    </div>
                </div>
            </div>
            <div class="git-repository-commit-metadata-properties-group">
                <div class="tlp-property">
                    <label class="tlp-label">{t domain="gitphp"}Hash{/t}</label>
                    <span>{$commit->GetHash()|escape}</span>
                </div>

                {if $commit_presenter->number_of_parents == 1}
                    <div class="tlp-property">
                        <label class="tlp-label">{t domain="gitphp"}Parent{/t}</label>
                        {foreach from=$commit->GetParents() item=parent}
                            <a href="{$SCRIPT_NAME}?a=commit&amp;h={$parent->GetHash()|urlencode}">{$parent->GetHash()|escape}</a>
                        {/foreach}
                    </div>
                {/if}
                {if $commit_presenter->number_of_parents > 1}
                    <div class="tlp-property">
                        <label class="tlp-label">{t domain="gitphp"}Parents{/t}</label>
                        <ul>
                            {foreach from=$commit->GetParents() item=parent}
                                <li>
                                    <a href="{$SCRIPT_NAME}?a=commit&amp;h={$parent->GetHash()|urlencode}">{$parent->GetHash()|escape}</a>
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}
                <div class="tlp-property">
                    <label class="tlp-label">{t domain="gitphp"}Reference{/t}</label>
                    <span>git #{$project->GetProject()|substr:0:-4|escape}/{$commit->GetHash()|escape}</span>
                </div>
            </div>
        </div>
    </div>
</section>
