{*
 *  history_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: History view item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 <td title="{$agestringage}"><i>{$agestringdate}</i></td>
 <td><i>{$authorname}</i></td>
 <td><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}" class="list"><b>{$title}{if $commitref} <span class="tag">{$commitref}</span>{/if}</b></a></td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=blob&hb={$commit}&f={$file}">blob</a>{if $difftocurrent} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$blob}&hp={$blobparent}&hb={$commit}&f={$file}">diff to current</a>{/if}
 </td></tr>
