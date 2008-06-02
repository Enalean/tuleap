{*
 *  log_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Log view item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div>
 <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}" class="title"><span class="age">{$agestring}</span>{$title}
 {if $commitref}
  <span class="tag">{$commitref}</span>
 {/if}
 </a>
 </div>
 <div class="title_text">
 <div class="log_link">
 <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$commit}">snapshot</a>
 <br />
 </div>
 <i>{$authorname} [{$rfc2822}]</i><br />
 </div>
 <div class="log_body">
 {foreach from=$comment item=line}
 {$line}<br />
 {/foreach}
 {if $notempty}
 <br />
 {/if}
 </div>
