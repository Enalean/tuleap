{*
 *  rss_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: RSS item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
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
