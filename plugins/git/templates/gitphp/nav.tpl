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

   {if $current=='shortlog' || !$commit}
     {t}shortlog{/t}
   {else}
     <a href="{$SCRIPT_NAME}?a=shortlog{if $logcommit}&amp;h={$logcommit->GetHash()|urlencode}{/if}{if $logmark}&amp;m={$logmark->GetHash()|urlencode}{/if}">{t}shortlog{/t}</a>
   {/if}
   | 
   {if $current=='log' || !$commit}
     {t}log{/t}
   {else}
     <a href="{$SCRIPT_NAME}?a=log{if $logcommit}&amp;h={$logcommit->GetHash()|urlencode}{/if}{if $logmark}&amp;m={$logmark->GetHash()|urlencode}{/if}">{t}log{/t}</a>
   {/if}
   | 
   {if $current=='commit' || !$commit}
     {t}commit{/t}
   {else}
     <a href="{$SCRIPT_NAME}?a=commit&amp;h={$commit->GetHash()|urlencode}">{t}commit{/t}</a>
   {/if}
   | 
   {if $current=='commitdiff' || !$commit}
     {t}commitdiff{/t}
   {else}
     <a href="{$SCRIPT_NAME}?a=commitdiff&amp;h={$commit->GetHash()|urlencode}">{t}commitdiff{/t}</a>
   {/if}
   | 
   {if $current=='tree'}
     {t}tree{/t}
   {else}
     <a href="{$SCRIPT_NAME}?a=tree{if $treecommit}&amp;hb={$treecommit->GetHash()|urlencode}{/if}{if $tree}&amp;h={$tree->GetHash()|urlencode}{/if}">{t}tree{/t}</a>
   {/if}
