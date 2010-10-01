{*
 *  commitdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   {if $commit}
   {assign var=tree value=$commit->GetTree()}
   {/if}
   {include file='nav.tpl' current='commitdiff' logcommit=$commit treecommit=$commit}
   <br />
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff_plain&h={$commit->GetHash()}{if $hashparent}&hp={$hashparent}{/if}">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}
 
 <div class="page_body">
   {foreach from=$commit->GetComment() item=line}
     {$line}<br />
   {/foreach}
   <br />
   {* Diff each file changed *}
   {foreach from=$treediff item=filediff}
     <div class="diff_info">
     {if ($filediff->GetStatus() == 'D') || ($filediff->GetStatus() == 'M')}
       {assign var=localfromtype value=$filediff->GetFromFileType(1)}
       {$localfromtype}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$filediff->GetFromHash()}&hb={$commit->GetHash()}{if $filediff->GetFromFile()}&f={$filediff->GetFromFile()}{/if}">{if $filediff->GetFromFile()}{$filediff->GetFromFile()}{else}{$filediff->GetFromHash()}{/if}</a>
       {if $filediff->GetStatus() == 'D'}
         {t}(deleted){/t}
       {/if}
     {/if}

     {if $filediff->GetStatus() == 'M'}
       -&gt;
     {/if}

     {if ($filediff->GetStatus() == 'A') || ($filediff->GetStatus() == 'M')}
       {assign var=localtotype value=$filediff->GetToFileType(1)}
       {$localtotype}:<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$filediff->GetToHash()}&hb={$commit->GetHash()}{if $filediff->GetToFile()}&f={$filediff->GetToFile()}{/if}">{if $filediff->GetToFile()}b/{$filediff->GetToFile()}{else}{$filediff->GetToHash()}{/if}</a>

       {if $filediff->GetStatus() == 'A'}
         {t}(new){/t}
       {/if}
     {/if}
     </div>
     {include file='filediff.tpl' diff=$filediff->GetDiff('', true, true)}
   {/foreach}
 </div>

 {include file='footer.tpl'}

