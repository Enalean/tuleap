{*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
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
From: {$commit->GetAuthor()}
Date: {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}
Subject: {$commit->GetTitle()}
{assign var=tag value=$commit->GetContainingTag()}
{if $tag}
X-Git-Tag: {$tag->GetName()}
{/if}
---
{foreach from=$commit->GetComment() item=line}
{$line}
{/foreach}
---


{foreach from=$treediff item=filediff}
{$filediff->GetDiff()}
{/foreach}
