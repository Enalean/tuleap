{*
 *  opml.tpl
 *  gitphp: A PHP git repository browser
 *  Component: OPML template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<opml version="1.0">
  <head>
    <title>{$title} OPML Export</title>
  </head>
  <body>
    <outline text="git RSS feeds">

      {foreach from=$opmllist item=proj}
      <outline type="rss" text="{$proj}" title="{$proj}" xmlUrl="{$self}?p={$proj}&amp;a=rss" htmlUrl="{$self}?p={$proj}&amp;a=summary" />

      {/foreach}
    </outline>
  </body>
</opml>
