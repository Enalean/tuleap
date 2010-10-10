{*
 *  committip.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit tooltip template
 *
 *  Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}
<div>
{t}author{/t}: {$commit->GetAuthor()} ({$commit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M:%S"})
<br />
{t}committer{/t}: {$commit->GetCommitter()} ({$commit->GetCommitterEpoch()|date_format:"%Y-%m-%d %H:%M:%S"})
<br /><br />
{foreach from=$commit->GetComment() item=line}
{$line|escape}<br />
{/foreach}
</div>
