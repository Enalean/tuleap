{*
 *  rss.tpl
 *  gitphp: A PHP git repository browser
 *  Component: RSS template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel>
    <title>{$project}</title>
    <link>{$self}?p={$project}&amp;a=summary</link>
    <description>{$project} log</description>
    <language>en</language>

    {section name=rssitem loop=$commitlines}
      <item>
        <title>{$commitlines[rssitem].cdmday} {$commitlines[rssitem].cdmonth} {$commitlines[rssitem].cdhour}:{$commitlines[rssitem].cdminute} - {$commitlines[rssitem].title|escape:'html'}</title>
        <author>{$commitlines[rssitem].author|escape:'html'}</author>
        <pubDate>{$commitlines[rssitem].cdrfc2822}</pubDate>
        <guid isPermaLink="true">{$self}?p={$project}&amp;a=commit&amp;h={$commitlines[rssitem].commit}</guid>
        <link>{$self}?p={$project}&amp;a=commit&amp;h={$commitlines[rssitem].commit}</link>
        <description>{$commitlines[rssitem].title|escape:'html'}</description>
        <content:encoded>
          <![CDATA[
          {foreach from=$commitlines[rssitem].comment item=line}
            {$line}<br />
          {/foreach}
          {foreach from=$commitlines[rssitem].difftree item=line}
            {$line}<br />
          {/foreach}
          ]]>
        </content:encoded>
      </item>
    {/section}

  </channel>
</rss>
