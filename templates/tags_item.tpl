{*
 *  tags_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 <td><i>{$age}</i></td>
 <td><a href="{$SCRIPT_NAME}?p={$project}&a={$reftype}&h={$refid}" class="list"><b>{$name}</b></a></td>
 <td>
 {if $comment}
 <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$id}" class="list">{$comment}</a>
 {/if}
 </td>
 <td class="link">
 {if $type == "tag"}<a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$id}">tag</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project}&a={$reftype}&h={$refid}">{$reftype}</a>{if $reftype == "commit"} | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$refid}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$refid}">snapshot</a>{/if}
 </td>
 </tr>
