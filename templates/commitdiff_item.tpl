{*
 *  commitdiff_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 {if $status == "A"}
   <div class="diff_info">
   {$to_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">{$to_id}</a>(new)
   </div>
 {elseif $status == "D"}
   <div class="diff_info">
   {$from_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}">{$from_id}</a>(deleted)
   </div>
 {elseif $status == "M"}
   {if $from_id != $to_id}
     <div class="diff_info">
     {$from_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$from_id}&hb={$hash}&f={$file}">{$from_id}</a> -&gt; {$to_type}:<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$to_id}&hb={$hash}&f={$file}">{$to_id}</a>
     </div>
   {/if}
 {/if}
