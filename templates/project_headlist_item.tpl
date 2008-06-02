{*
 *  project_headlist_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project head list item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 {if $truncate}
   <td><a href="{$SCRIPT_NAME}?p={$project}&a=heads">...</a></td>
 {else}
   <td><i>{$headage}</i></td>
   <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headname}" class="list"><b>{$headname}</b></td>
   <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headname}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$headname}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$headname}&hb={$headname}">tree</a></td>
 {/if}
 </tr>
