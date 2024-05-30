{*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

<div class="git-repository-shortlog-container">
    <section class="tlp-pane git-repository-shortlog-search">
        <form method="get" class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i class="tlp-pane-title-icon fa-solid fa-search" aria-hidden="true"></i>
                    {t domain="gitphp"}Search{/t}
                </h1>
            </div>
            <section class="tlp-pane-section">
                <input type="hidden" name="a" value="search" />
                <input type ="hidden" name="hb" value="{$hashbase|escape}" />

                <div class="tlp-form-element">
                    <label class="tlp-label" for="search-type">{t domain="gitphp"}Type{/t}</label>
                    <select id="search-type" class="tlp-select" name="st">
                        <option {if isset($searchtype) && $searchtype == 'commit'}selected="selected"{/if} value="commit">{t domain="gitphp"}Commit{/t}</option>
                        <option {if isset($searchtype) && $searchtype == 'author'}selected="selected"{/if} value="author">{t domain="gitphp"}Author{/t}</option>
                        <option {if isset($searchtype) && $searchtype == 'committer'}selected="selected"{/if} value="committer">{t domain="gitphp"}Committer{/t}</option>
                    </select>
                </div>

                <div class="tlp-form-element">
                    <label class="tlp-label" for="search">{t domain="gitphp"}Terms{/t}</label>
                    <input type="text"
                           class="tlp-input"
                           id="search"
                           name="s"
                           placeholder="{t domain="gitphp"}Author name, description, …{/t}"
                           {if isset($search) && $search}value="{$search|escape}"{/if}
                           pattern="{literal}.{2,}{/literal}"
                           title="{t domain="gitphp"}Search text of at least 2 characters{/t}"
                           required>
                </div>

                <div class="tlp-pane-section-submit">
                    <button type="submit" class="tlp-button-primary tlp-button-wide">
                        <i class="fa-solid fa-search tlp-button-icon" aria-hidden="true"></i> {t domain="gitphp"}Search{/t}
                    </button>
                </div>
            </section>
        </form>
    </section>
    {if empty($shortlog_presenter) || (isset($hasemptysearchresults) && $hasemptysearchresults)}
        <h1 class="empty-state-title git-repository-shortlog-results">
            {if isset($hasemptysearchresults) && $hasemptysearchresults}
                {t domain="gitphp" 1=$search}No matches for "%1"{/t}
            {else}
                {t domain="gitphp"}No commits{/t}
            {/if}
        </h1>
    {else}
        <section id="git-repository-shortlog" class="git-repository-shortlog-results">
            {include file="tuleap/commits-as-cards.tpl"}

            {if (isset($hasmorerevs) && $hasmorerevs) || $page > 0}
                {if $commit}
                    <div class="tlp-pagination git-repository-shortlog-pagination">
                        {if $page > 0}
                            <a href="?a={if isset($search) && $search}search&amp;s={$search|urlencode}&amp;st={$searchtype|urlencode}{else}shortlog{/if}&amp;hb={$hashbase|urlencode}&amp;pg={$page-1|urlencode}{if isset($mark) && $mark}&amp;m={$mark->GetHash()|urlencode}{/if}"
                               class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                               title="{t domain="gitphp"}Previous{/t}"
                            >
                                <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
                            </a>
                        {else}
                            <button type="button"
                                    class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                                    title="{t domain="gitphp"}Previous{/t}"
                                    disabled
                            >
                                <i class="fa-solid fa-angle-left" aria-hidden="true"></i>
                            </button>
                        {/if}
                        {if isset($hasmorerevs) && $hasmorerevs }
                            <a href="?a={if isset($search) && $search}search&amp;s={$search|urlencode}&amp;st={$searchtype|urlencode}{else}shortlog{/if}&amp;hb={$hashbase|urlencode}&amp;pg={$page+1|urlencode}{if isset($mark) && $mark}&amp;m={$mark->GetHash()|urlencode}{/if}"
                               class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                               title="{t domain="gitphp"}Next{/t}"
                            >
                                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                            </a>
                        {else}
                            <button type="button"
                                    class="tlp-button-primary tlp-button-outline tlp-pagination-button"
                                    title="{t domain="gitphp"}Next{/t}"
                                    disabled
                            >
                                <i class="fa-solid fa-angle-right" aria-hidden="true"></i>
                            </button>
                        {/if}
                    </div>
                {/if}
            {/if}
        </section>
    {/if}
</div>
