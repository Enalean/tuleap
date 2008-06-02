{*
 *  commit_data.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view data template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <div>
 {if $parent}
   <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}" class="title">{$title}
   {if $commitref}
   <span class="tag">{$commitref}</span>
   {/if}
   </a>
 {else}
   <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}" class="title">{$title}</a>
 {/if}
 </div>
 <div class="title_text">
 <table cellspacing="0">
 <tr><td>author</td><td>{$author}</td></tr>
 <tr><td></td><td> {$adrfc2822} ({if $adhourlocal < 6}<span style="color: #cc0000;">{/if}{$adhourlocal}:{$adminutelocal}{if $adhourlocal < 6}</span>{/if} {$adtzlocal})
 </td></tr>
 <tr><td>committer</td><td>{$committer}</td></tr>
 <tr><td></td><td> {$cdrfc2822} ({$cdhourlocal}:{$cdminutelocal} {$cdtzlocal})</td></tr>
 <tr><td>commit</td><td style="font-family:monospace">{$id}</td><tr>
 <tr><td>tree</td><td style="font-family:monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}" class="list">{$tree}</a></td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$hash}">snapshot</a></td></tr>
 {foreach from=$parents item=par}
 <tr><td>parent</td><td style="font-family:monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}" class="list">{$par}</a></td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}&hp={$par}">commitdiff</a></td></tr>
 {/foreach}
 </table>
 </div>
 <div class="page_body">
 {foreach from=$comment item=line}
 {$line}<br />
 {/foreach}
 </div>
 <div class="list_head">
 {if $difftreesize > 11}
   {$difftreesize} files changed:
 {/if}
 </div>
 <table cellspacing="0">
