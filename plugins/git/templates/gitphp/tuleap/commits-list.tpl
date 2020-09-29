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

{foreach from=$commits_per_day->commits item=commit_presenter}
    <div
        {if $mark and $mark->GetHash() == $commit_presenter->commit->GetHash()}
            class="tlp-card tlp-card-selectable git-repository-commit-card git-repository-commit-selected-for-diff"
        {else}
            class="tlp-card tlp-card-selectable git-repository-commit-card"
        {/if}
        data-href="{$SCRIPT_NAME}?a=commit&amp;h={$commit_presenter->commit->GetHash()|urlencode}"
    >
        <span class="selected-for-diff tlp-badge-primary">{t domain="gitphp"}Selected for diff{/t}</span>
        <div class="tlp-avatar git-repository-commit-card-avatar">
            {if $searchtype == 'committer'}
                {if ($commit_presenter->committer->has_avatar)}
                    <img src="{$commit_presenter->committer->avatar_url|escape}">
                {/if}
            {else}
                {if ($commit_presenter->author->has_avatar)}
                    <img src="{$commit_presenter->author->avatar_url|escape}">
                {/if}
            {/if}
        </div>

        <div class="git-repository-commit-card-info">
            <p class="git-repository-commit-card-info-title">
                {if $searchtype == 'commit'}
                    {$commit_presenter->commit->GetTitle()|highlight:$search}
                    {if $searchtype == 'commit'}
                        {foreach from=$commit_presenter->commit->searchDescription($search) item=line name=match}
                            <br /><span class="git-repository-commit-card-info-description">{$line|highlight:$search:50}</span>
                        {/foreach}
                    {/if}
                {else}
                    {$commit_presenter->commit->GetTitle()|escape}
                {/if}
            </p>

            <div class="git-repository-commit-card-info-metadata">
                <span class="git-repository-commit-card-info-metadata-username">
                    {if $searchtype == 'author'}
                        {if ($commit_presenter->author->is_a_tuleap_user)}
                            <a href="{$commit_presenter->author->url|escape}">{$commit_presenter->author->display_name|highlight:$search}</a>
                        {else}
                            {$commit_presenter->commit->getAuthorName()|highlight:$search}
                        {/if}
                    {elseif $searchtype == 'committer'}
                        {if ($commit_presenter->committer->is_a_tuleap_user)}
                            <a href="{$commit_presenter->committer->url|escape}">{$commit_presenter->committer->display_name|highlight:$search}</a>
                        {else}
                            {$commit_presenter->commit->GetCommitterName()|highlight:$search}
                        {/if}
                    {else}
                        {if ($commit_presenter->author->is_a_tuleap_user)}
                            <a href="{$commit_presenter->author->url|escape}">{$commit_presenter->author->display_name|escape}</a>
                        {else}
                            {$commit_presenter->commit->getAuthorName()|escape}
                        {/if}
                    {/if}
                </span>

                <span class="git-repository-commit-card-info-metadata-date">
                    <i class="far fa-clock git-repository-commit-card-info-metadata-date-icon"></i>{if $commit_presenter->commit->GetAge() > 60*60*24*7*2}{$commit_presenter->commit_date|escape}{else}{$commit_presenter->commit->GetAge()|agestring|escape}{/if}
                </span>

                {include file='tuleap/refs-badges.tpl' commit=$commit_presenter->commit}
            </div>
        </div>

        <div class="tlp-badge-secondary tlp-badge-outline git-repository-commit-card-hash">
            {$commit_presenter->short_id}
        </div>

        {assign var=revtree value=$commit_presenter->commit->GetTree()}

        <div class="tlp-button-bar">
            {if $mark}
                {if $mark->GetHash() !== $commit_presenter->commit->GetHash()}
                    {if $mark->GetCommitterEpoch() > $commit_presenter->commit->GetCommitterEpoch()}
                        {assign var=markbase value=$mark}
                        {assign var=markparent value=$commit_presenter->commit}
                    {else}
                        {assign var=markbase value=$commit_presenter->commit}
                        {assign var=markparent value=$mark}
                    {/if}

                    <div class="tlp-button-bar-item">
                        <a class="tlp-button-primary tlp-button-outline tlp-button-small"
                           href="{$SCRIPT_NAME}?a=commitdiff&amp;h={$markbase->GetHash()|urlencode}&amp;hp={$markparent->GetHash()|urlencode}">
                            {t domain="gitphp"}Diff with selected{/t}
                        </a>
                    </div>
                {else}
                    <div class="tlp-button-bar-item">
                        <a class="tlp-button-primary tlp-button-outline tlp-button-small"
                           href="{$SCRIPT_NAME}?a=shortlog&amp;h={$shortlog_presenter->first_commit->commit->getHash()|urlencode}&amp;pg={$page}">
                            {t domain="gitphp"}Deselect{/t}
                        </a>
                    </div>
                {/if}
            {else}
            <div class="tlp-button-bar-item">
                <a class="tlp-button-primary tlp-button-outline tlp-button-small"
                   href="{$SCRIPT_NAME}?a=commit&amp;h={$commit_presenter->commit->GetHash()|urlencode}"
                >
                    {t domain="gitphp"}Details{/t}
                </a>
            </div>
            <div class="tlp-button-bar-item">
                <a class="tlp-button-primary tlp-button-outline tlp-button-small"
                    href="{$SCRIPT_NAME}?a=commitdiff&amp;h={$commit_presenter->commit->GetHash()|urlencode}"
                >
                    {t domain="gitphp"}Diff{/t}
                </a>
            </div>
            {/if}
            <div class="tlp-button-bar-item tlp-dropdown">
                <button type="button"
                        class="commit-more-actions tlp-button-primary tlp-button-small tlp-button-outline"
                >
                    <i class="fa fa-ellipsis-h tlp-button-icon"></i>
                    <i class="fa fa-caret-down tlp-button-icon-right"></i>
                </button>
                <div class="tlp-dropdown-menu tlp-dropdown-menu-right" role="menu">
                    {if $mark}
                        <a class="tlp-dropdown-menu-item"
                           href="{$SCRIPT_NAME}?a=commit&amp;h={$commit_presenter->commit->GetHash()|urlencode}"
                        >
                            {t domain="gitphp"}Details{/t}
                        </a>
                        <a class="tlp-dropdown-menu-item"
                           href="{$SCRIPT_NAME}?a=commitdiff&amp;h={$commit_presenter->commit->GetHash()|urlencode}"
                        >
                            {t domain="gitphp"}Diff{/t}
                        </a>
                    {/if}
                    <a class="tlp-dropdown-menu-item"
                       href="{$SCRIPT_NAME}?a=tree&amp;h={$revtree->GetHash()|urlencode}&amp;hb={$commit_presenter->commit->GetHash()|urlencode}"
                    >
                        {t domain="gitphp"}Tree{/t}
                    </a>
                    <a class="tlp-dropdown-menu-item"
                       href="{$SCRIPT_NAME}?a=snapshot&amp;h={$commit_presenter->commit->GetHash()|urlencode}&amp;noheader=1" class="snapshotTip"
                    >
                        {t domain="gitphp"}Snapshot{/t}
                    </a>
                    {if ! $mark}
                        <a class="tlp-dropdown-menu-item"
                           href="{$SCRIPT_NAME}?a=shortlog&amp;h={$shortlog_presenter->first_commit->commit->getHash()|urlencode}&amp;pg={$page}&amp;m={$commit_presenter->commit->GetHash()|urlencode}">
                            {t domain="gitphp"}Select for diff{/t}
                        </a>
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/foreach}
