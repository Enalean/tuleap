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

{include file='tuleap/blob-header.tpl'}

<section class="git-repository-blob-body">
    {if $datatag}
        {* We're trying to display an image *}
        <div class="git-repository-blob-image">
            <img src="data:{$mime};base64,{$data}" />
        </div>
    {elseif $geshi}
        <style type="text/css">{$extracss}</style>
        {* We're using the highlighted output from geshi *}
        {$geshiout}
    {else}
        {* Just plain display *}
        <table class="code" id="git-repository-blob-file">
            <tbody>
                <tr class="li1">
                    <td class="ln">
                        <pre class="de1">
{foreach from=$bloblines item=line name=bloblines}
{$smarty.foreach.bloblines.iteration}
{/foreach}
</pre>
                    </td>
                    <td class="de1">
                        <pre class="de1">
{foreach from=$bloblines item=line name=bloblines}
{$line|escape}
{/foreach}
</pre>
                    </td>
                </tr>
            </tbody>
        </table>
    {/if}
</section>
