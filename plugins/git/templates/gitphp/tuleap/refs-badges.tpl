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

{foreach from=$commit->GetHeads() item=commithead}
    <a href="{$SCRIPT_NAME}?a=shortlog&amp;h=refs/heads/{$commithead->GetName()|urlencode}" class="tlp-badge-primary tlp-badge-outline">{$commithead->GetName()|escape}</a>
{/foreach}
{foreach from=$commit->GetTags() item=committag}
    <a href="{$SCRIPT_NAME}?a=tag&amp;h={$committag->GetName()|urlencode}"
       class="tlp-badge-primary {if !$committag->LightTag()}tagTip{/if}"
    >
        {$committag->GetName()|escape}
    </a>
{/foreach}
