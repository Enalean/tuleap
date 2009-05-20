{*
 *  tag_data.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view data template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
 <div>
   <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hash}" class="title">{$title}</a>
 </div>
 <div class="title_text">
 <table cellspacing="0">
 <tr><td>object</td><td class="monospace"><a href="{$SCRIPT_NAME}?p={$project}&a={$type}&h={$object}" class="list">{$object}</a></td><td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a={$type}&h={$object}">{$type}</a></td></tr>
 {if $author}
 <tr><td>author</td><td>{$author}</td></tr>
 <tr><td></td><td> {$adrfc2822} ({if $adhourlocal < 6}<span class="latenight">{/if}{$adhourlocal}:{$adminutelocal}{if $adhourlocal < 6}</span>{/if} {$adtzlocal})
 </td></tr>
 {/if}
 </table>
 </div>
 <div class="page_body">
 {foreach from=$comment item=line}
 {$line}<br />
 {/foreach}
 </div>
