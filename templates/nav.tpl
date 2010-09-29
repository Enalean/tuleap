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
     {$resources->GetResource('summary')}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">{$resources->GetResource('summary')}</a>
   {/if}
   | 
   {if $current=='shortlog' || !$commit}
     {$resources->GetResource('shortlog')}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog{if $logcommit}&h={$logcommit->GetHash()}{/if}">{$resources->GetResource('shortlog')}</a>
   {/if}
   | 
   {if $current=='log' || !$commit}
     {$resources->GetResource('log')}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log{if $logcommit}&h={$logcommit->GetHash()}{/if}">{$resources->GetResource('log')}</a>
   {/if}
   | 
   {if $current=='commit' || !$commit}
     {$resources->GetResource('commit')}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$commit->GetHash()}">{$resources->GetResource('commit')}</a>
   {/if}
   | 
   {if $current=='commitdiff' || !$commit}
     {$resources->GetResource('commitdiff')}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$commit->GetHash()}">{$resources->GetResource('commitdiff')}</a>
   {/if}
   | 
   {if $current=='tree' || !$commit}
     {$resources->GetResource('tree')}
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree{if $treecommit}&hb={$treecommit->GetHash()}{/if}{if $tree}&h={$tree->GetHash()}{/if}">{$resources->GetResource('tree')}</a>
   {/if}
