{*
 *  project_taglist_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project tag list item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 {if $truncate}
   <td><a href="{$SCRIPT_NAME}?p={$project}&a=tags">...</a></td>
 {else}
   <td><i>{$tagage}</i></td>
   <td><a href="{$SCRIPT_NAME}?p={$project}&a={$reftype}&h={$refid}" class="list"><b>{$tagname}</b></a></td>
   <td>
   {if $comment}
     <a class="list" href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$tagid}">{$comment}</a>
   {/if}
   </td>
   <td class="link">
   {if $tagtype == "tag"}
   	<a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$tagid}">tag</a> | 
   {/if}
   <a href="{$SCRIPT_NAME}?p={$project}&a={$reftype}&h={$refid}">{$reftype}</a>{if $reftype == "commit"} | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/tags/{$tagname}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/tags/{$tagname}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$refid}">snapshot</a>{/if}
   </td>
 {/if}
 </tr>
