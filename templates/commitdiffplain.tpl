{*
 *  commitdiffplain.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Plaintext diff template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
From: {$hash->GetAuthor()}
Date: {$hash->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}
Subject: {$hash->GetTitle()}
{assign var=tag value=$hash->GetContainingTag()}
{if $tag}
X-Git-Tag: {$tag->GetName()}
{/if}
X-Git-Url: {$self}?p={$project->GetProject()}&a=commitdiff&h={$hash->GetHash()}
---
{foreach from=$hash->GetComment() item=line}
{$line}
{/foreach}
---


{foreach from=$treediff item=filediff}
{$filediff->GetDiff()}
{/foreach}
