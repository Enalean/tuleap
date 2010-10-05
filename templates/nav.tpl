{*
 * Nav
 *
 * Nav links template fragment
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

   {if $current=='summary'}
     {t}summary{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=summary">{t}summary{/t}</a>
   {/if}
   | 
   {if $current=='shortlog' || !$commit}
     {t}shortlog{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog{if $logcommit}&amp;h={$logcommit->GetHash()}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}shortlog{/t}</a>
   {/if}
   | 
   {if $current=='log' || !$commit}
     {t}log{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=log{if $logcommit}&amp;h={$logcommit->GetHash()}{/if}{if $logmark}&amp;m={$logmark->GetHash()}{/if}">{t}log{/t}</a>
   {/if}
   | 
   {if $current=='commit' || !$commit}
     {t}commit{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$commit->GetHash()}">{t}commit{/t}</a>
   {/if}
   | 
   {if $current=='commitdiff' || !$commit}
     {t}commitdiff{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$commit->GetHash()}">{t}commitdiff{/t}</a>
   {/if}
   | 
   {if $current=='tree' || !$commit}
     {t}tree{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree{if $treecommit}&amp;hb={$treecommit->GetHash()}{/if}{if $tree}&amp;h={$tree->GetHash()}{/if}">{t}tree{/t}</a>
   {/if}
