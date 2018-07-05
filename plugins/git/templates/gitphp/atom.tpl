{*
 *  atom.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Atom feed template
 *
 *  Copyright (C) 2010 Christian Weiske <cweiske@cweiske.de>
 *}
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="en">
  <title>{$project->GetProject()|escape}</title>
  <subtitle type="text">{$project->GetProject()|escape} log</subtitle>
  <link href="{scripturl}?a=summary&amp;noheader=1"/>
  <link rel="self" href="{scripturl}?a=atom&amp;noheader=1"/>
  <id>{scripturl}</id>
  {if $log}
  <updated>{$log.0->GetCommitterEpoch()|date_format:"%FT%T+00:00"}</updated>
  {/if}

{foreach from=$log item=logitem}
  <entry>
    <id>{scripturl}?a=commit&amp;h={$logitem->GetHash()|urlencode}</id>
    <title>{$logitem->GetTitle()|escape:'html'}</title>
    <author>
      <name>{$logitem->GetAuthorName()|escape:'html'}</name>
    </author>
    <published>{$logitem->GetCommitterEpoch()|date_format:"%FT%T+00:00"}</published>
    <updated>{$logitem->GetCommitterEpoch()|date_format:"%FT%T+00:00"}</updated>
    <link rel="alternate" href="{scripturl}?a=commit&amp;h={$logitem->GetHash()|escape}&amp;noheader=1"/>
    <summary>{$logitem->GetTitle()|escape:'html'}</summary>
    <content type="xhtml">
      <div xmlns="http://www.w3.org/1999/xhtml">
        <p>
        {foreach from=$logitem->GetComment() item=line}
          {$line|htmlspecialchars}<br />
        {/foreach}
        </p>
        <ul>
        {foreach from=$logitem->DiffToParent() item=diffline}
          <li>{$diffline->GetToFile()|htmlspecialchars}</li>
        {/foreach}
        </ul>
      </div>
    </content>
  </entry>
{/foreach}

</feed>
