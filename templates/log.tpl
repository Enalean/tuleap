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
   {include file='nav.tpl' current='log' logcommit=$commit treecommit=$commit}
   <br />
   {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log{if $mark}&m={$mark->GetHash()}{/if}">{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
   {/if}
   &sdot; 
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}&pg={$page-1}{if $mark}&m={$mark->GetHash()}{/if}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
   &sdot; 
   {if $hasmorerevs}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}&pg={$page+1}{if $mark}&m={$mark->GetHash()}{/if}" accesskey="n" title="Alt-n">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
   <br />
   {if $mark}
     {t}selected{/t} &sdot;
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$mark->GetHash()}" class="list commitTip" {if strlen($mark->GetTitle()) > 30}title="{$mark->GetTitle()}"{/if}><strong>{$mark->GetTitle(30)}</strong></a>
     &sdot;
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}&pg={$page}">{t}deselect{/t}</a>
     <br />
   {/if}
 </div>
 {foreach from=$revlist item=rev}
   <div class="title">
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}" class="title"><span class="age">{$rev->GetAge()|agestring}</span>{$rev->GetTitle()}</a>
     {include file='refbadges.tpl' commit=$rev}
   </div>
   <div class="title_text">
     <div class="log_link">
       <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$rev->GetHash()}">{t}commit{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$rev->GetHash()}">{t}commitdiff{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&h={$rev->GetHash()}&hb={$rev->GetHash()}">{t}tree{/t}</a>
       <br />
       {if $mark}
         {if $mark->GetHash() == $rev->GetHash()}
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}&pg={$page}">{t}deselect{/t}</a>
	 {else}
	   {if $mark->GetCommitterEpoch() > $rev->GetCommitterEpoch()}
	     {assign var=markbase value=$mark}
	     {assign var=markparent value=$rev}
	   {else}
	     {assign var=markbase value=$rev}
	     {assign var=markparent value=$mark}
	   {/if}
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$markbase->GetHash()}&hp={$markparent->GetHash()}">{t}diff with selected{/t}</a>
	 {/if}
       {else}
         <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log&h={$commit->GetHash()}&pg={$page}&m={$rev->GetHash()}">{t}select for diff{/t}</a>
       {/if}
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
     {if $commit}
       {assign var=commitage value=$commit->GetAge()|agestring}
       {t 1=$commitage}Last change %1{/t}
     {else}
     <em>{t}No commits{/t}</em>
     {/if}
     <br /><br />
   </div>
 {/foreach}

 {include file='footer.tpl'}

