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
{include file='header.tpl'}

 {* Nav *}
   <div class="page_nav">
     {include file='nav.tpl' current='tree' logcommit=$commit}
   </div>


{if $taglist}
    {include file='title.tpl' target='tags'}

    {include file='taglist.tpl'}
{/if}

{if $headlist}

    {include file='title.tpl' target='heads'}

    {include file='headlist.tpl'}

{/if}

<br /><br />

 {include file='title.tpl' titlecommit=$commit}

{if $commit}
    {include file='path.tpl' pathobject=$tree target='tree'}
{/if}

 <div class="page_body">
     {if $commit}
         {* List files *}
         <table cellspacing="0" class="treeTable">
             {include file='treelist.tpl'}
         </table>
     {else}
         <em>{t domain="gitphp"}No commits{/t}</em>
     {/if}
 </div>

 {include file='footer.tpl'}

</div>
</div>
</section>

{if $readme_content}
<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">
                <i class="fa fa-file-text-o tlp-pane-title-icon"></i> {$readme_content->GetName()|escape}
            </h1>
        </div>
        <section class="tlp-pane-section">
            {$readme_content_interpreted}
        </section>
{/if}
