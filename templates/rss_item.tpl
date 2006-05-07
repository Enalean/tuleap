{*
 *  rss_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: RSS item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *}
<item>
<title>{$cdmday} {$cdmonth} {$cdhour}:{$cdminute} - {$title}</title>
<author>{$author}</author>
<pubDate>{$cdrfc2822}</pubDate>
<guid isPermaLink="true">{$self}?p={$project}&amp;a=commit&amp;h={$commit}</guid>
<link>{$self}?p={$project}&amp;a=commit&amp;h={$commit}</link>
<description>{$title}</description>
<content:encoded>
<![CDATA[
{foreach from=$comment item=line}
{$line}<br />
{/foreach}
{foreach from=$difftree item=line}
{$line}<br />
{/foreach}
]]>
</content:encoded>
</item>
