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
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}">commit</a> | commitdiff | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">tree</a><br /><a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff_plain&h={$hash}&hp={$hashparent}&noheader=1">plain</a>
 </div>
 <div>
   <br /><br />
 </div>
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}" class="title">{$title}{if $commitref} <span class="tag">{$commitref}</span>{/if}</a>
 </div>
 <div class="page_body">
   {foreach from=$comment item=line}
     {$line}<br />
   {/foreach}
   <br />
   {* Diff each file changed *}
   {section name=difftree loop=$difftreelines}
     {if $difftreelines[difftree].status == "A"}
       <div class="diff_info">
         {$difftreelines[difftree].to_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}">{if $difftreelines[difftree].file}{$difftreelines[difftree].file}{else}{$difftreelines[difftree].to_id}{/if}</a>(new)
       </div>
     {elseif $difftreelines[difftree].status == "D"}
       <div class="diff_info">
         {$difftreelines[difftree].from_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}">{if $difftreelines[difftree].file}{$difftreelines[difftree].file}{else}{$difftreelines[difftree].from_id}{/if}</a>(deleted)
       </div>
     {elseif $difftreelines[difftree].status == "M"}
       {if $difftreelines[difftree].from_id != $difftreelines[difftree].to_id}
         <div class="diff_info">
	   {$difftreelines[difftree].from_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}">{if $difftreelines[difftree].file}a/{$difftreelines[difftree].file}{else}{$difftreelines[difftree].from_id}{/if}</a> -&gt; {$difftreelines[difftree].to_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}">{if $difftreelines[difftree].file}b/{$difftreelines[difftree].file}{else}{$difftreelines[difftree].to_id}{/if}</a>
	 </div>
       {/if}
     {/if}
     {include file='filediff.tpl' diff=$difftreelines[difftree].diffout}
   {/section}
 </div>

 {include file='footer.tpl'}

