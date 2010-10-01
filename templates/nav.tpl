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
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">{t}summary{/t}</a>
   {/if}
   | 
   {if $current=='shortlog' || !$commit}
     {t}shortlog{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog{if $logcommit}&h={$logcommit->GetHash()}{/if}">{t}shortlog{/t}</a>
   {/if}
   | 
   {if $current=='log' || !$commit}
     {t}log{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log{if $logcommit}&h={$logcommit->GetHash()}{/if}">{t}log{/t}</a>
   {/if}
   | 
   {if $current=='commit' || !$commit}
     {t}commit{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$commit->GetHash()}">{t}commit{/t}</a>
   {/if}
   | 
   {if $current=='commitdiff' || !$commit}
     {t}commitdiff{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$commit->GetHash()}">{t}commitdiff{/t}</a>
   {/if}
   | 
   {if $current=='tree' || !$commit}
     {t}tree{/t}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree{if $treecommit}&hb={$treecommit->GetHash()}{/if}{if $tree}&h={$tree->GetHash()}{/if}">{t}tree{/t}</a>
   {/if}
