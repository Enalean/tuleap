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
   {include file='nav.tpl' current='shortlog' logcommit=$commit treecommit=$commit logmark=$mark}
   <br />
   {if ($commit && $head) && (($commit->GetHash() != $head->GetHash()) || ($page > 0))}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog{if $mark}&m={$mark->GetHash()}{/if}">{t}HEAD{/t}</a>
   {else}
     {t}HEAD{/t}
   {/if}
     &sdot; 
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page-1}{if $mark}&m={$mark->GetHash()}{/if}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
   {else}
     {t}prev{/t}
   {/if}
     &sdot; 
   {if $hasmorerevs}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page+1}{if $mark}&m={$mark->GetHash()}{/if}" accesskey="n" title="Alt-n">{t}next{/t}</a>
   {else}
     {t}next{/t}
   {/if}
   <br />
   {if $mark}
     {t}selected{/t} &sdot;
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$mark->GetHash()}" class="list commitTip" {if strlen($mark->GetTitle()) > 30}title="{$mark->GetTitle()}"{/if}><strong>{$mark->GetTitle(30)}</strong></a>
     &sdot;
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog&h={$commit->GetHash()}&pg={$page}">{t}deselect{/t}</a>
     <br />
   {/if}
 </div>

 {include file='title.tpl' target='summary'}

 {include file='shortloglist.tpl' source='shortlog'}

 {include file='footer.tpl'}

