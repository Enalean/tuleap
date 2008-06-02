{*
 *  shortlog_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 <td title="{$agestringage}"><i>{$agestringdate}</i></td>
 <td><i>{$authorname}</i></td>
 <td>
 <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}" class="list" {if $title}title="{$title}"{/if}><b>{$title_short}
 {if $commitref}
 <span class="tag">{$commitref}</span>
 {/if}
 </b>
 </td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$commit}&hb={$commit}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$commit}">snapshot</a>
 </td>
 </tr>
