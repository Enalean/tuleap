{*
 *  log.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}">shortlog</a> | log | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$commit->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$commit->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$commit->GetHash()}&hb={$commit->GetHash()}">tree</a>
   <br />
   {if ($commit->GetHash() != $head->GetHash()) || ($page > 0)}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">HEAD</a>
   {else}
     HEAD
   {/if}
   &sdot; 
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}&pg={$page-1}" accesskey="p" title="Alt-p">prev</a>
   {else}
     prev
   {/if}
   &sdot; 
   {if $hasmore}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
   {else}
     next
   {/if}
   <br />
 </div>
 {foreach from=$revlist item=rev}
   <div class="title">
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}" class="title"><span class="age">{$rev->GetAge()|agestring}</span>{$rev->GetTitle()}</a>
     <span class="refs">
     {foreach from=$rev->GetHeads() item=revhead}
       <span class="head">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h=refs/heads/{$revhead->GetName()}">{$revhead->GetName()}</a>
	 </span>
     {/foreach}
     {foreach from=$rev->GetTags() item=revtag}
       <span class="tag">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tag&h={$revtag->GetName()}">{$revtag->GetName()}</a>
	 </span>
     {/foreach}
     </span>
   </div>
   <div class="title_text">
     <div class="log_link">
       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$rev->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$rev->GetHash()}&hb={$rev->GetHash()}">tree</a>
       <br />
     </div>
     <em>{$rev->GetAuthorName()} [{$rev->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}]</em><br />
   </div>
   <div class="log_body">
     {foreach from=$rev->GetComment() item=line}
       {$line}<br />
     {/foreach}
     {if count($rev->GetComment()) > 0}
       <br />
     {/if}
   </div>
 {foreachelse}
   <div class="title">
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary" class="title">&nbsp</a>
   </div>
   <div class="page_body">
     Last change {$commit->GetAge()|agestring}.
     <br /><br />
   </div>
 {/foreach}

 {include file='footer.tpl'}

