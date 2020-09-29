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

<h1 class="git-repository-blob-header-title">
    <i class="tlp-pane-title-icon far fa-file-alt"></i>
    {assign var=blobcommit value=$blob->GetCommit()}
    {assign var=blobtree value=$blobcommit->GetTree()}

    <a href="{$SCRIPT_NAME}?a=tree&amp;hb={$blobcommit->GetHash()|urlencode}">{$project->GetProject()|escape}</a>/<!--
    -->{foreach from=$pathtree item=pathtreepiece}<!--
        --><a href="{$SCRIPT_NAME}?a=tree&amp;hb={$blobcommit->GetHash()|urlencode}&amp;f={$pathtreepiece->path|urlencode}">{$pathtreepiece->name|escape}</a>/<!--
    -->{/foreach}<!--
    -->{if $blob->isBlob()}<!--
        --><a href="{$SCRIPT_NAME}?a=blob&amp;hb={$blobcommit->GetHash()|urlencode}&amp;f={$blob->GetPath()|urlencode}">{$blob->GetName()|escape}</a>
    {/if}
</h1>
