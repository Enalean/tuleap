{*
 *  shortlog_footer.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Shortlog view footer template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
 {if $nextlink}
 <tr><td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}&pg={$nextpage}" title="Alt-n">next</a></td></tr>
 {/if}
 </table>
