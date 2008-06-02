{*
 *  rss_header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: RSS header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
<title>{$project}</title>
<link>{$self}?p={$project}&amp;a=summary</link>
<description>{$project} log</description>
<language>en</language>
