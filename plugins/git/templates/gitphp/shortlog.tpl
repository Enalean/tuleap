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
     <a href="{$SCRIPT_NAME}?a=shortlog{if $mark}&amp;m={$mark->GetHash()|urlencode}{/if}">{t domain="gitphp"}HEAD{/t}</a>
   {else}
     {t domain="gitphp"}HEAD{/t}
   {/if}
     &sdot;
   {if $page > 0}
     <a href="{$SCRIPT_NAME}?a=shortlog&amp;h={$commit->GetHash()|urlencode}&amp;pg={$page-1|urlencode}{if $mark}&amp;m={$mark->GetHash()|urlencode}{/if}" accesskey="p" title="Alt-p">{t domain="gitphp"}prev{/t}</a>
   {else}
     {t domain="gitphp"}prev{/t}
   {/if}
     &sdot;
   {if $hasmorerevs}
     <a href="{$SCRIPT_NAME}?a=shortlog&amp;h={$commit->GetHash()|urlencode}&amp;pg={$page+1}{if $mark}&amp;m={$mark->GetHash()|urlencode}{/if}" accesskey="n" title="Alt-n">{t domain="gitphp"}next{/t}</a>
   {else}
     {t domain="gitphp"}next{/t}
   {/if}
   <br />
   {if $mark}
     {t domain="gitphp"}selected{/t} &sdot;
     <a href="{$SCRIPT_NAME}?a=commit&amp;h={$mark->GetHash()|urlencode}" class="list commitTip" {if strlen($mark->GetTitle()) > 30}title="{$mark->GetTitle()|htmlspecialchars}"{/if}><strong>{$mark->GetTitle(30)|htmlspecialchars}</strong></a>
     &sdot;
     <a href="{$SCRIPT_NAME}?a=shortlog&amp;h={$commit->GetHash()|urlencode}&amp;pg={$page}">{t domain="gitphp"}deselect{/t}</a>
     <br />
   {/if}
 </div>

 {include file='title.tpl' target='tree'}

 {include file='shortloglist.tpl' source='shortlog'}

 {include file='footer.tpl'}

