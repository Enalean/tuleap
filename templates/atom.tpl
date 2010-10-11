{*
 *  atom.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Atom feed template
 *
 *  Copyright (C) 2010 Christian Weiske <cweiske@cweiske.de>
 *}
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="en">
  <title>{$project->GetProject()}</title>
  <subtitle type="text">{$project->GetProject()} log</subtitle>
  <link href="{scripturl}?p={$project->GetProject()|urlencode}&amp;a=summary"/>
  <link rel="self" href="{scripturl}?p={$project->GetProject()|urlencode}&amp;a=atom"/>
  <id>{scripturl}?p={$project->GetProject()|urlencode}</id>
  {if $log}
  <updated>{$log.0->GetCommitterEpoch()|date_format:"%FT%T+00:00"}</updated>
  {/if}

{foreach from=$log item=logitem}
  <entry>
    <id>{scripturl}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$logitem->GetHash()}</id>
    <title>{$logitem->GetTitle()|escape:'html'}</title>
    <author>
      <name>{$logitem->GetAuthorName()|escape:'html'}</name>
    </author>
    <published>{$logitem->GetCommitterEpoch()|date_format:"%FT%T+00:00"}</published>
    <updated>{$logitem->GetCommitterEpoch()|date_format:"%FT%T+00:00"}</updated>
    <link rel="alternate" href="{scripturl}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$logitem->GetHash()}"/>
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
