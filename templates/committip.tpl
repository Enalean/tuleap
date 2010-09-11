{*
 *  committip.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit tooltip template
 *
 *  Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}
<div>
{$resources->GetResource('author')}: {$commit->GetAuthor()} ({$commit->GetAuthorEpoch()|date_format:"%F %X"})
<br />
{$resources->GetResource('committer')}: {$commit->GetCommitter()} ({$commit->GetCommitterEpoch()|date_format:"%F %X"})
<br /><br />
{foreach from=$commit->GetComment() item=line}
{$line}<br />
{/foreach}
</div>
