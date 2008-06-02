{*
 *  tree_filelist_item.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view filelist item template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 <tr class="{$class}">
 <td style="font-family:monospace">{$filemode}</td>
 <td class="list">
 {if $type == "blob"}
 <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$name}" class="list">{$name}</a></td>
 <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$name}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hashbase}&f={if $base}{$base}{/if}{$name}">history</a>
 {elseif $type == "tree"}
   <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$name}">{$name}</a></td>
   <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$name}">tree</a>
 {/if}
 </td>
 </tr>
