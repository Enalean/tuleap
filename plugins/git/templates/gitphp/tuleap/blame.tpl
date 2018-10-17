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

        <section class="git-repository-blob-body">
            <table id="git-repository-blame-file">
                <tbody>
                    <tr>
                        <td class="git-repository-blame-line">
                            {foreach from=$blob->GetData(true) item=blobline name=blob}
                                {assign var=blamecommit value=$blame[$smarty.foreach.blob.iteration]}
                                {if $blamecommit}
                                    {if $opened}</div>{/if}
                                    <div class="git-repository-blame-cell">
                                        {assign var=opened value=true}
                                        <a href="{$SCRIPT_NAME}?a=commit&amp;h={$blamecommit->GetHash()|urlencode}"
                                            title="{$blamecommit->GetTitle()|htmlspecialchars}"
                                        >{$blamecommit->GetAuthorEpoch()|date_format:"%Y-%m-%d"}</a>
                                        <span title="{$blamecommit->GetAuthor()|escape}">{$blamecommit->GetAuthorName()|escape}</span>
                                {/if}
                                <br/>
                            {/foreach}
                            {if $opened}</div>{/if}
                        </td>
                        <td class="git-repository-blame-linenumbers">
                            <div class="git-repository-blob-file-linenumbers">
                                {foreach from=$bloblines item=line name=bloblines}<a href="#L{$smarty.foreach.bloblines.iteration}"
                                   id="L{$smarty.foreach.bloblines.iteration}"
                                   class="git-repository-blob-file-linenumbers-line"
                                    >{$smarty.foreach.bloblines.iteration}</a>{/foreach}
                            </div>
                        </td>
                        <td>
                            <pre class="git-repository-blob-file-code"><!--
                                --><div class="git-repository-highlight-line" id="git-repository-highlight-line"></div><!--
                                --><code class="language-{$language}">{foreach from=$bloblines item=line name=bloblines}
{$line|escape}
{/foreach}</code><!--
                            --></pre>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</section>
