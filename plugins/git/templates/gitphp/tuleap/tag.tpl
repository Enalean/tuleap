{*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

{assign var=object value=$tag->GetObject()}
{assign var=objtype value=$tag->GetType()}

<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header git-repository-tag-header">
            <h2 class="git-repository-tag-title">
                <span>
                    {$tag->GetName()|escape}
                </span>
            </h2>
        </div>

        <section class="tlp-pane-section git-repository-tag-info">
            {if $tag->GetComment()}
                <p class="git-repository-tag-description">{php}
                    echo $this->get_template_vars('purifier')->purify(
                        implode(PHP_EOL, $this->get_template_vars('tag')->GetComment()),
                        CODENDI_PURIFIER_BASIC_NOBR,
                        HTTPRequest::instance()->getProject()->getID()
                    );
                {/php}
                </p>
            {/if}
            <div class="git-repository-tag-metadata">
                <div class="git-repository-tag-metadata-properties">
                    {if $tag->GetTagger()|escape}
                        <div class="git-repository-tag-metadata-properties-group">
                            <div class="tlp-property">
                                <label class="tlp-label">{t domain="gitphp"}Author{/t}</label>
                                <div class="git-repository-tag-metadata-username-date">

                                    {if ($author->is_a_tuleap_user)}
                                        <a href="{$author->url|escape}">
                                            <div class="tlp-avatar git-repository-tag-metadata-username-avatar">
                                                {if ($author->has_avatar)}
                                                    <img src="{$author->avatar_url|escape}">
                                                {/if}
                                            </div><!--
                                                -->{$author->display_name|escape}
                                        </a>
                                    {else}
                                        <div class="tlp-avatar git-repository-tag-metadata-username-avatar"></div>
                                        {$tagger_name|escape}
                                    {/if}

                                    <span class="tlp-text-muted git-repository-tag-metadata-date">
                                        <i class="far fa-clock"></i> {$tag->GetTaggerEpoch()|date_format:"%Y-%m-%d %H:%M"}
                                    </span>
                                </div>
                            </div>
                        </div>
                    {/if}
                    <div class="git-repository-tag-metadata-properties-group">
                        <div class="tlp-property">
                            <label class="tlp-label">
                                {if $objtype == 'commit'}
                                    {t domain="gitphp"}Commit{/t}
                                {elseif $objtype == 'tag'}
                                    {t domain="gitphp"}Tag{/t}
                                {elseif $objtype == 'blob'}
                                    {t domain="gitphp"}Blob{/t}
                                {else}
                                    {t domain="gitphp"}Unknown object{/t}
                                {/if}
                            </label>
                            <div>
                                {if $objtype == 'commit'}
                                    <a href="{$SCRIPT_NAME}?a=commit&amp;h={$object->GetHash()|urlencode}">{$object->GetHash()|escape}</a>
                                {elseif $objtype == 'tag'}
                                    <a href="{$SCRIPT_NAME}?a=tag&amp;h={$object->GetName()|urlencode}">{$object->GetHash()|escape}</a>
                                {elseif $objtype == 'blob'}
                                    <a href="{$SCRIPT_NAME}?a=blob&amp;h={$object->GetHash()|urlencode}">{$object->GetHash()|escape}</a>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>
