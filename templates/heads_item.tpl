{*
 *  heads_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Head view item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 <td><i>{$age}</i></td>
 <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$name}" class="list"><b>{$name}</b></a></td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$name}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$name}&hb={$name}">tree</a></td>
 </tr>
