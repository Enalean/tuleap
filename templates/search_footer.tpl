{*
 *  search_footer.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search view footer template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
 {if $nextlink}
 <tr><td><a href="{$SCRIPT_NAME}?p={$project}&a=search&h={$hash}&s={$search}&st={$searchtype}&pg={$nextpage}" title="Alt-n">next</a></td></tr>
 {/if}
 </table>
