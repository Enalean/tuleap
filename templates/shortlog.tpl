{*
 *  shortlog.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' current='shortlog' logcommit=$commit treecommit=$commit}
   <br />
   {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">{$resources->GetResource('HEAD')}</a>
   {else}
     {$resources->GetResource('HEAD')}
   {/if}
     &sdot; 
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page-1}" accesskey="p" title="Alt-p">{$resources->GetResource('prev')}</a>
   {else}
     {$resources->GetResource('prev')}
   {/if}
     &sdot; 
   {if $hasmorerevs}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page+1}" accesskey="n" title="Alt-n">{$resources->GetResource('next')}</a>
   {else}
     {$resources->GetResource('next')}
   {/if}
   <br />
 </div>

 {include file='title.tpl' target='summary'}

 {include file='shortloglist.tpl'}

 {include file='footer.tpl'}

