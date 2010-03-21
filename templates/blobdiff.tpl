{*
 *  blobdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Blobdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* If we managed to look up commit info, we have enough info to display the full header - othewise just use a simple header *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$commit->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$commit->GetHash()}">tree</a>
   <br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff_plain&h={$hash->GetHash()}&hp={$hashparent->GetHash()}&f={$file}">plain</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}

 {include file='path.tpl' pathobject=$hashparent target='blob'}
 
 <div class="page_body">
   <div class="diff_info">
     {* Display the from -> to diff header *}
     blob:<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$hashparent->GetHash()}&hb={$commit->GetHash()}&f={$file}">{if $file}a/{$file}{else}{$hashparent->GetHash()}{/if}</a> -&gt; blob:<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$hash->GetHash()}&hb={$commit->GetHash()}&f={$file}">{if $file}b/{$file}{else}{$hash->GetHash()}{/if}</a>
   </div>
   {* Display the diff *}
   {include file='filediff.tpl' diff=$filediff->GetDiff($file, false, true)}
 </div>

 {include file='footer.tpl'}

