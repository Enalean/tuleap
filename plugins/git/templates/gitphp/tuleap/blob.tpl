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

<section class="tlp-pane">
    <div class="tlp-pane-container">
        {include file='tuleap/blob-header.tpl'}

        <section class="git-repository-blob-body">
            {if isset($datatag)}
                {* We're trying to display an image *}
                <div class="git-repository-blob-image">
                    <img src="data:{$mime};base64,{$data}" />
                </div>
            {elseif isset($is_binaryfile) && $is_binaryfile}
                <section class="empty-state-pane">
                    {include file='tuleap/blob-binary-file-svg.tpl'}
                    <h1 class="empty-state-title">{t domain="gitphp"}This file is a binary file{/t}</h1>
                    <p class="empty-state-text">
                        {t domain="gitphp"}It can't be previewed in Tuleap yet.{/t}
                    </p>
                </section>
            {elseif isset($is_file_in_special_format) && $is_file_in_special_format}
                <section class="empty-state-pane">
                    {include file='tuleap/blob-binary-lfs-file-svg.tpl'}
                    <h1 class="empty-state-title">{t domain="gitphp"}This file is handled by Git LFS{/t}</h1>
                    <p class="empty-state-text">
                        {t domain="gitphp"}It can't be previewed in Tuleap yet.{/t}
                    </p>
                </section>
            {else}
                {* Just plain display *}
                {if isset($rendered_file)}
                <div id="git-repository-blob-file-rendered">
                    {$rendered_file}
                </div>
                {/if}
                {if isset($bloblines)}
                    {if isset($potentially_dangerous_bidirectional_text_warning) }
                        <section class="tlp-pane-section"><div class="tlp-alert-warning">
                            {$potentially_dangerous_bidirectional_text_warning}
                        </div></section>
                    {/if}
                <div id="git-repository-blob-file">
                    <div class="git-repository-blob-file-linenumbers">{foreach from=$bloblines item=line name=bloblines}
<a href="#L{$smarty.foreach.bloblines.iteration}"
   id="L{$smarty.foreach.bloblines.iteration}"
   class="git-repository-blob-file-linenumbers-line"
>{$smarty.foreach.bloblines.iteration}</a>
{/foreach}
</div>
                    <tlp-syntax-highlighting>
                        <pre class="git-repository-blob-file-code"><!--
                            --><div class="git-repository-highlight-line" id="git-repository-highlight-line"></div><!--
                            --><code class="language-{$language}">{foreach from=$bloblines item=line name=bloblines}
{$line|escape}
{/foreach}</code><!--
                        --></pre>
                    </tlp-syntax-highlighting>
                </div>
                {/if}
            {/if}
        </section>
    </div>
</section>
