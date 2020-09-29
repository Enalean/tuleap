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

<section class="tlp-pane">
    <div class="tlp-pane-container">
        {include file='tuleap/blob-header.tpl'}

        <section class="tlp-pane-section-for-cards" id="git-repository-shortlog">
            {foreach from=$shortlog_presenter->commits item=commits_per_day}
                <h2 class="tlp-pane-subtitle git-repository-shortlog-day">
                    <i class="fas fa-calendar-alt tlp-pane-title-icon"></i>
                    {$commits_per_day->day | escape}
                </h2>

                {include file="tuleap/commits-list.tpl"}
            {/foreach}


            {if $hasmorerevs || $page > 0}
                {if $commit}
                    <div class="tlp-pagination git-repository-shortlog-pagination">
                        {if $page > 0}
                            <a href="{$SCRIPT_NAME}?a=history&amp;hb={$hashbase|urlencode}&amp;pg={$page-1|urlencode}&amp;h={$commit->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}"
                               class="tlp-button-primary tlp-button-outline tlp-button-small tlp-pagination-button"
                               title="{t domain="gitphp"}Previous{/t}"
                            >
                                <i class="fa fa-angle-left"></i>
                            </a>
                        {else}
                            <button type="button"
                                    class="tlp-button-primary tlp-button-outline tlp-button-small tlp-pagination-button"
                                    title="{t domain="gitphp"}Previous{/t}"
                                    disabled
                            >
                                <i class="fa fa-angle-left"></i>
                            </button>
                        {/if}
                        {if $hasmorerevs }
                            <a href="{$SCRIPT_NAME}?a=history&amp;hb={$hashbase|urlencode}&amp;pg={$page+1|urlencode}&amp;h={$commit->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}"
                               class="tlp-button-primary tlp-button-outline tlp-button-small tlp-pagination-button"
                               title="{t domain="gitphp"}Next{/t}"
                            >
                                <i class="fa fa-angle-right"></i>
                            </a>
                        {else}
                            <button type="button"
                                    class="tlp-button-primary tlp-button-outline tlp-button-small tlp-pagination-button"
                                    title="{t domain="gitphp"}Next{/t}"
                                    disabled
                            >
                                <i class="fa fa-angle-right"></i>
                            </button>
                        {/if}
                    </div>
                {/if}
            {/if}
        </section>
    </div>
</section>
